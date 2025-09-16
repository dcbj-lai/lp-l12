<?php

namespace App\Http\Controllers;

use App\Models\VisitorLog;
use Illuminate\Http\Request;
use App\Mail\VisitorApprovedMail;
use App\Mail\VisitorDeclinedMail;
use App\Mail\VisitorNotification;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorLogController extends Controller
{
    public function showStart()
    {
        return view('visitor.start');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $otp = rand(100000, 999999);

        $visitor = VisitorLog::create([
            'email' => $request->email,
            'otp' => $otp,
            'otp_sent_at' => now(),
        ]);

        // send OTP via email
        Mail::raw("Your OTP code is: $otp", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Your Visitor OTP');
        });

        return back()->with([
            'email' => $request->email,
            'step' => 'verify',
        ]);
    }

    public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6',
    ]);

    $visitor = VisitorLog::where('email', $request->email)
        ->latest()
        ->first();

    if (!$visitor || $visitor->otp !== $request->otp) {
        return back()->withErrors(['otp' => 'Invalid OTP. Please try again.'])
                     ->with(['email' => $request->email, 'step' => 'verify']);
    }

    // Check if OTP is older than 5 minutes
    if ($visitor->created_at->lt(now()->subMinutes(5))) {
        return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.'])
                     ->with(['email' => $request->email]);
    }

    // OTP valid → redirect to main visitor form
    return redirect()->route('visitor.form', ['id' => $visitor->id]);
}

public function showForm($id)
{
    $visitor = VisitorLog::findOrFail($id);

    $users = \App\Models\User::orderBy('name')
        ->pluck('name', 'id'); // key = id, value = name

    return view('visitor.form', compact('visitor', 'users'));
}


public function submitForm(Request $request, $id)
{
    $request->validate([
    'full_name'       => 'required|string|max:255',
    'address'         => 'required|string|max:500',
    'mobile'          => 'required|string|max:20',
    'visited_user_id' => 'required|exists:users,id',
    'purpose'         => 'required|string|max:500',
    'meetup_spot'     => 'nullable|string|max:255',
]);


    $visitor = VisitorLog::findOrFail($id);
    $visitor->update([
        'full_name' => $request->full_name,
        'address'   => $request->address,
        'mobile'    => $request->mobile,
        'visited_user_id'    => $request->visited_user_id,
        'purpose'    => $request->purpose,
    ]);

    return redirect()->route('visitor.thankyou');
}


public function frontdeskIndex(Request $request)
{
    $query = VisitorLog::query()->with('visitedUser');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('full_name', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhereHas('visitedUser', function($q2) use ($search) {
                  $q2->where('name', 'like', "%$search%");
              });
        });
    }

    $visitors = $query->latest()->paginate(10)->withQueryString();

    return view('frontdesk.visitors', compact('visitors'));
}

public function checkIn(Request $request, VisitorLog $visitor)
{
    // Validate the meetup_spot input
    $validated = $request->validate([
        'meetup_spot' => 'nullable|string|max:255',
    ]);

    // Update the visitor record with status, check-in, and meetup_spot
    $visitor->update([
        'status' => 'endorsed',
        'check_in_at' => now(),
    ]);

    // Send email to the visited party if email exists
    if ($visitor->visitedUser && $visitor->visitedUser->email) {
        Mail::to($visitor->visitedUser->email)
            ->send(new VisitorNotification($visitor));
    }

    return redirect()
        ->route('frontdesk.visitors')
        ->with('success', 'Visitor endorsed, meetup spot updated, and notification sent.');
}

public function checkOut(Request $request, VisitorLog $visitor)
{
    $visitor->update([
        'status' => 'checked_out',
        'check_out_at' => now(),
    ]);
    return back()->with('success', 'Visitor checked out.');
}

public function userIndex(Request $request)
{
    // Show visitors assigned to this user
    $visitors = VisitorLog::where('visited_user_id', auth()->id())
                    ->latest()
                    ->paginate(10);

    return view('user.visitors', compact('visitors'));
}

public function approveVisit(Request $request, VisitorLog $visitor)
{
    $request->validate([
        'meetup_spot' => 'nullable|string|max:255',
        'action_type' => 'required|string|in:approve,decline',
    ]);

    // Ensure current user is the visited party
    if ($visitor->visited_user_id !== auth()->id()) {
        abort(403);
    }

    if ($request->action_type === 'approve') {
        $visitor->update([
            'meetup_spot' => $request->meetup_spot,
            'status' => 'approved',
        ]);
        // Notify frontdesk via email
        Mail::to(config('visitor.frontdesk_email'))->send(new VisitorApprovedMail($visitor));

        $message = 'Visitor approved successfully. Frontdesk has been notified.';
    } else {
        $visitor->update([
            'status' => 'declined',
        ]);

        // Notify frontdesk via email
        Mail::to(config('visitor.frontdesk_email'))->send(new VisitorDeclinedMail($visitor));

        $message = 'Visitor visit declined. Frontdesk has been notified.';
    }

    return redirect()->back()->with('success', $message);
}



public function showVisitor(VisitorLog $visitor)
{
    // Ensure only the visited user can view this
    if (auth()->id() !== $visitor->visited_user_id) {
        abort(403, 'You are not authorized to view this visitor log.');
    }

    return view('frontdesk.show', compact('visitor'));
}

public function show(VisitorLog $visitor)
{
    return view('frontdesk.visitors.show', compact('visitor'));
}

public function mine(Request $request)
    {
        // dd('hello world!');
        $search = $request->input('search');

        $visitors = VisitorLog::where('visited_user_id', auth()->id()) // adjust if your FK differs
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('frontdesk.visitors.mine', compact('visitors'));
    }

    public function downloadCsv(): StreamedResponse
{
    $fileName = 'visitor_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

    $visitors = VisitorLog::with('visitedUser')->latest()->get();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $columns = ['Full Name', 'Email', 'Mobile', 'Person Visited', 'Purpose', 'Status', 'Check-In', 'Check-Out'];

    return response()->stream(function () use ($visitors, $columns) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, $columns);

        foreach ($visitors as $v) {
            fputcsv($handle, [
                $v->full_name,
                $v->email,
                $v->mobile,
                optional($v->visitedUser)->name ?? '-',
                $v->purpose ?? '-',
                ucfirst($v->status),
                $v->check_in_at ? $v->check_in_at->format('Y-m-d H:i') : '-',
                $v->check_out_at ? $v->check_out_at->format('Y-m-d H:i') : '-',
            ]);
        }

        fclose($handle);
    }, 200, $headers);
}

public function visitorDestroy(VisitorLog $visitor)
{
    $visitor->delete();
    return redirect()->route('frontdesk.visitors')->with('success', 'Visitor log permanently deleted.');

}

public function visitorDestroyAll()
{
    \DB::table('visitor_logs')->truncate();

    return redirect()
        ->route('frontdesk.visitors')
        ->with('success', 'All visitor logs have been permanently deleted.');
}

}
