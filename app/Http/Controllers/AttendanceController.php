<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use App\Models\User;
use App\Models\QrToken;
use \App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceController extends Controller
{
    public function myAttendance()
{
    $user = auth()->user();
    $today = Carbon::now()->toDateString();

    $attendances = Attendance::where('user_id', $user->id)
        ->orderBy('date', 'desc')
        ->simplePaginate(5);

    $latestAttendance = Attendance::where('user_id', $user->id)
        ->whereDate('date', $today)
        ->first();

    $hasCheckedIn = $latestAttendance && $latestAttendance->check_in;
    $hasCheckedOut = $latestAttendance && $latestAttendance->check_out;
    // $lastCheckIn = $latestAttendance ? $latestAttendance->check_in : null;
    $lastCheckIn = $latestAttendance?->check_in;


    return view('attendance.my_attendance', compact('attendances', 'hasCheckedIn', 'hasCheckedOut', 'lastCheckIn'));
}

    
    public function checkIn(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        $currentTime = Carbon::now()->format('H:i:s'); // Get current time
        $officialTimeIn = config('app.official_time_in');
    
        // Check if the user has already checked in today
        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();
    
        if ($attendance) {
            return back()->with('error', 'You have already checked in today.');
        }
    
        // Determine status & remarks based on check-in time
        $status = 'Present';
        $remarks = 'User';
    
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in' => Carbon::now(),
            'status' => $status,
            'remarks' => $remarks,
        ]);
    
        return back()->with('success', 'Checked in successfully.');
    }
    

    public function checkOut()
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
    
        if (!$attendance || $attendance->check_out) {
            return back()->with('error', 'You have already checked out.');
        }
    
        $attendance->check_out = Carbon::now();
    
        // Calculate hours worked
        $attendance->hours_worked = Carbon::parse($attendance->check_in)
            ->diffInMinutes($attendance->check_out) / 60;
    
        $attendance->save();
    
        return back()->with('success', 'Checked out successfully.');
    }

    public function index()
{
    $users = User::all();
    $attendances = Attendance::with('user')
    ->orderBy('created_at','desc')
    ->get(); // Ensure user relationship is loaded

    return view('attendance.index', compact('users', 'attendances'));
}



public function week(Request $request)
{
    // Get current week offset from query (default to 0)
    $weekOffset = $request->query('week', 0);
    $weekOffset = (int) $weekOffset;

    // Get the current date and adjust for week offset
    $startOfWeek = Carbon::now()->startOfWeek()->addWeeks($weekOffset);
    $endOfWeek = $startOfWeek->copy()->endOfWeek();

    // Generate an array of weekdays (excluding Saturday & Sunday)
    $weekDays = collect(range(0, 4))->map(function ($day) use ($startOfWeek) {
    return Carbon::parse($startOfWeek)->addDays($day);
});


    // Get attendance records for the given week
    $attendances = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
        ->get()
        ->groupBy('user_id');
    // dd($attendances);
    // Fetch all employees
    $employees = User::all();

    return view('attendance.week', compact('weekDays', 'attendances', 'employees', 'weekOffset'));
}

public function showQr(Request $request)
{
    $user = auth()->user();
    if (Gate::denies('is-acad-admin', $user)) {
        abort(403, 'Unauthorized');
    }

    $type = $request->get('type', 'check_in');

    // Find active token of this type
    $token = QrToken::where('active', true)
        ->where('type', $type)
        ->where('expires_at', '>', now())
        ->latest()
        ->first();

    if (!$token) {
        $token = QrToken::generate($type);
    }

    $qrUrl = config('app.url') . "/qr_{$type}/" . $token->token;
    $qrImage = base64_encode(QrCode::format('svg')->size(250)->generate($qrUrl));

    return view('attendance.show-qr', compact('qrUrl', 'qrImage', 'token', 'type'));
}



public function qrCheckIn(Request $request, $token)
{
    $qrToken = QrToken::where('token', $token)
        ->where('type', 'check_in')
        ->first();

    if (!$qrToken || !$qrToken->isValid()) {
        return redirect()->route('attendance.qr-result', [
            'status' => 'error',
            'message' => 'Invalid or expired QR token.',
        ]);
    }

    $user = auth()->user();

    if (strtolower(optional($user->department)->name ?? '') !== 'faculty') {
        return redirect()->route('attendance.qr-result', [
            'status' => 'error',
            'message' => 'Unauthorized department.',
        ]);
    }

    // 🧠 Check if already checked in today
    $existing = Attendance::where('user_id', $user->id)
        ->whereDate('created_at', today())
        ->whereNotNull('check_in')
        ->first();

    if ($existing) {
        return redirect()->route('attendance.qr-result', [
            'status' => 'warning',
            'message' => 'You have already checked in today.',
        ]);
    }

    // ✅ Perform actual check-in
    $this->checkIn($request);

    return redirect()->route('attendance.qr-result', [
        'status' => 'success',
        'message' => 'Successfully checked in!',
    ]);
}

public function qrCheckOut(Request $request, $token)
{
    $qrToken = QrToken::where('token', $token)
        ->where('type', 'check_out')
        ->first();

    if (!$qrToken || !$qrToken->isValid()) {
        return redirect()->route('attendance.qr-result', [
            'status' => 'error',
            'message' => 'Invalid or expired QR token.',
        ]);
    }

    $user = auth()->user();

    if (strtolower(optional($user->department)->name ?? '') !== 'faculty') {
        return redirect()->route('attendance.qr-result', [
            'status' => 'error',
            'message' => 'Unauthorized department.',
        ]);
    }

    // Check if already checked out today
    $existing = Attendance::where('user_id', $user->id)
        ->whereDate('created_at', today())
        ->whereNotNull('check_out')
        ->first();

    if ($existing) {
        return redirect()->route('attendance.qr-result', [
            'status' => 'warning',
            'message' => 'You have already checked out today.',
        ]);
    }

    // ✅ Perform actual check-out
    $this->checkOut();

    return redirect()->route('attendance.qr-result', [
        'status' => 'success',
        'message' => 'Successfully checked out!',
    ]);
}


}
