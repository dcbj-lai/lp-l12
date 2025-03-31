<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use App\Models\User;
use \App\Models\Attendance;
use Illuminate\Http\Request;

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
    $attendances = Attendance::with('user')->get(); // Ensure user relationship is loaded

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





    

}
