<?php

namespace App\Http\Controllers;

use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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


}
