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
        // $attendance->hours_worked = Carbon::parse($attendance->check_in)
        //     ->diffInMinutes($attendance->check_out) / 60;

        $checkIn = Carbon::parse($attendance->check_in);
        $checkOut = Carbon::parse($attendance->check_out);

        if ($checkOut->lessThan($checkIn)) {
            // Overnight shift — add 1 day to check-out
            $checkOut->addDay();
        }

        $attendance->hours_worked = $checkIn->diffInMinutes($checkOut) / 60;
    
        $attendance->save();
    
        return back()->with('success', 'Checked out successfully.');
    }

 public function index(Request $request)
{
    if (!Gate::allows('is-pnc')) {
        abort(403, 'Unauthorized Access.');
    }

    $query = Attendance::with('user.department');

    // 🔍 Filters
    if ($request->filled('employee')) {
        $employee = strtolower($request->employee);
        $query->whereHas('user', function ($q) use ($employee) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$employee}%"]);
        });
    }

    if ($request->filled('department')) {
        $query->whereHas('user.department', function ($q) use ($request) {
            $q->where('id', $request->department);
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('date_from') && $request->filled('date_to')) {
        $query->whereBetween('date', [$request->date_from, $request->date_to]);
    }

    // 🔽 Sorting
    $sortable = ['employee', 'department', 'date', 'check_in', 'check_out', 'hours_worked', 'status', 'created_at'];
    $sort = $request->get('sort', 'date');
    $direction = $request->get('direction', 'desc');

    if ($sort === 'employee') {
        $query->join('users', 'attendances.user_id', '=', 'users.id')
              ->select('attendances.*')
              ->orderByRaw('LOWER(users.name) ' . ($direction === 'asc' ? 'ASC' : 'DESC'));
    } elseif ($sort === 'department') {
        $query->join('users', 'attendances.user_id', '=', 'users.id')
              ->join('departments', 'users.department_id', '=', 'departments.id')
              ->select('attendances.*')
              ->orderByRaw('LOWER(departments.name) ' . ($direction === 'asc' ? 'ASC' : 'DESC'));
    } elseif (in_array($sort, $sortable)) {
        $query->orderBy($sort, $direction);
    } else {
        $query->latest('date');
    }

    $attendances = $query->paginate(20)->withQueryString();

    return view('attendance.index', compact('attendances', 'sort', 'direction'));
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

// Modify by P&C

    public function edit(Attendance $attendance)
        {
            $attendance->load('user.department');
            return view('attendance.edit', compact('attendance'));
        }


public function update(Request $request, $id)
{
    $attendance = Attendance::findOrFail($id);

    $validated = $request->validate([
        'check_in'  => 'nullable|string',
        'check_out' => 'nullable|string',
    ]);

    $date = $attendance->date instanceof Carbon
        ? $attendance->date->format('Y-m-d')
        : $attendance->date;

    // Build timestamps from request input
    $checkIn = $validated['check_in']
        ? Carbon::parse("{$date} {$validated['check_in']}")
        : null;

    $checkOut = $validated['check_out']
        ? Carbon::parse("{$date} {$validated['check_out']}")
        : null;

    // Compute hours worked only if both times exist
    $hoursWorked = ($checkIn && $checkOut)
        ? round($checkIn->diffInMinutes($checkOut) / 60, 2)
        : $attendance->hours_worked;

    // Compose remarks automatically
    $remarks = 'Manual edit - ' . auth()->user()->name;

    // Update record
    $attendance->update([
        'check_in'     => $checkIn,
        'check_out'    => $checkOut,
        'hours_worked' => $hoursWorked,
        'remarks'      => $remarks,
    ]);

    return redirect()
        ->route('attendance.index')
        ->with('success', 'Attendance manually updated (P&C logged).');
}

public function create()
{
    $employees = User::orderBy('name')->get(['id', 'name']);
    return view('attendance.create', compact('employees'));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'user_id'    => 'required|exists:users,id',
        'date'       => 'required|date',
        'check_in'   => 'required|date_format:H:i',
        'check_out'  => 'nullable|date_format:H:i|after:check_in',
        'remarks'    => 'nullable|string|max:255',
    ]);

    $date = Carbon::parse($validated['date'])->format('Y-m-d');

    $checkIn = Carbon::parse($request->check_in);
    $checkOut = Carbon::parse($request->check_out);

    if ($checkOut->lessThan($checkIn)) {
        // Overnight shift — add 1 day to check-out
        $checkOut->addDay();
    }

    $hoursWorked = $checkIn->diffInMinutes($checkOut) / 60;


    Attendance::create([
        'user_id'      => $validated['user_id'],
        'date'         => $date,
        'check_in'     => $checkIn,
        'check_out'    => $checkOut,
        'hours_worked' => $hoursWorked,
        'status'       => 'Present',
        'remarks'      => ($validated['remarks'] ?? 'Manual entry') . ' - ' . auth()->user()->name,
    ]);

    return redirect()->route('attendance.index')->with('success', 'Attendance record added successfully.');
}



}
