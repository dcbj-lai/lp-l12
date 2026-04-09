<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use App\Mail\VisitorApprovedMail;
use App\Mail\VisitorDeclinedMail;
use App\Mail\VisitorNotification;
use App\Mail\VisitorPreApprovedMail;
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
            'full_name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'mobile' => 'required|string|max:20',
            'visited_user_id' => 'required|exists:users,id',
            'purpose' => 'required|string|max:500',
            'meetup_spot' => 'nullable|string|max:255',
        ]);


        $visitor = VisitorLog::findOrFail($id);
        $visitor->update([
            'full_name' => $request->full_name,
            'company' => $request->company,
            'address' => $request->address,
            'mobile' => $request->mobile,
            'visited_user_id' => $request->visited_user_id,
            'purpose' => $request->purpose,
        ]);

        return redirect()->route('visitor.thankyou');
    }


    // public function frontdeskIndex(Request $request)
// {
//     $query = VisitorLog::query()->with('visitedUser');

    //     if ($request->filled('search')) {
//         $search = strtolower($request->search); // normalize input
//         $query->where(function($q) use ($search) {
//             $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
//               ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
//               ->orWhereHas('visitedUser', function($q2) use ($search) {
//                   $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
//               });
//         });
//     }

    //     $visitors = $query->latest()->paginate(10)->withQueryString();

    //     return view('frontdesk.visitors', compact('visitors'));
// }
    public function frontdeskIndex(Request $request)
    {
        $query = VisitorLog::query()->with('visitedUser');

        // 🔍 Search
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(mobile) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(purpose) LIKE ?', ["%{$search}%"])
                    ->orWhereHas(
                        'visitedUser',
                        fn($sub) =>
                        $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    );
            });
        }

        // 🏢 Company filter
        if ($request->filled('company')) {
            $query->whereRaw('LOWER(company) LIKE ?', ["%" . strtolower($request->company) . "%"]);
        }

        // 📅 Visit date filter
        if ($request->filled('visit_date')) {
            $query->whereDate('visit_date', $request->visit_date);
        }

        // 📊 Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 📋 Sorting
        $sortable = ['full_name', 'company', 'visit_date', 'email', 'mobile', 'visited_user_id', 'purpose', 'status', 'check_in_at', 'check_out_at'];
        if ($request->filled('sort') && in_array($request->get('sort'), $sortable)) {
            $sort = $request->get('sort');
            $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sort, $direction);
        } else {
            // Default: most recent visitors first
            $query->orderBy('created_at', 'desc');
        }

        $visitors = $query->paginate(10)->withQueryString();

        return view('frontdesk.visitors', compact('visitors'));
    }





    public function checkIn(Request $request, VisitorLog $visitor)
    {

        // Update the visitor record with status, check-in, and meetup_spot
        $visitor->update([
            'status' => 'checked_in',
            'check_in_at' => now(),
        ]);

        return back()->with('success', 'Visitor checked in.');
    }
    public function endorse(Request $request, VisitorLog $visitor)
    {
        // Validate the meetup_spot input
        $validated = $request->validate([
            'meetup_spot' => 'nullable|string|max:255',
        ]);

        // Update the visitor record with status, check-in, and meetup_spot
        $visitor->update([
            'status' => 'endorsed',
        ]);

        // Send email to the visited party if email exists
        if ($visitor->visitedUser && $visitor->visitedUser->email) {
            Mail::to($visitor->visitedUser->email)
                ->send(new VisitorNotification($visitor));
        }
        return back()->with('success', 'Visitor endorsed and notification sent.');
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

        return redirect()->back()->with('flash', [
            'type' => 'success',
            'message' => $message,
        ]);
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
        $search = $request->input('search');

        $visitors = VisitorLog::where('visited_user_id', auth()->id()) // adjust if your FK differs
            ->when($search, function ($query) use ($search) {
                $search = strtolower($search); // normalize input
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(mobile) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(purpose) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest()
            ->paginate(10);

        return view('frontdesk.visitors.mine', compact('visitors'));
    }


    public function downloadCsv(Request $request): StreamedResponse
    {
        $fileName = 'visitor_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $query = VisitorLog::with('visitedUser');

        // 🔍 Search filter
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(mobile) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(purpose) LIKE ?', ["%{$search}%"])
                    ->orWhereHas(
                        'visitedUser',
                        fn($sub) =>
                        $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    );
            });
        }

        // 🏢 Company filter
        if ($request->filled('company')) {
            $query->whereRaw('LOWER(company) LIKE ?', ["%" . strtolower($request->company) . "%"]);
        }

        // 📅 Visit date filter
        if ($request->filled('visit_date')) {
            $query->whereDate('visit_date', $request->visit_date);
        }

        // 📊 Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 📋 Sorting — match UI + default to latest visits first
        $sortable = ['full_name', 'company', 'visit_date', 'email', 'mobile', 'visited_user_id', 'purpose', 'status', 'check_in_at', 'check_out_at'];
        if ($request->filled('sort') && in_array($request->get('sort'), $sortable)) {
            $sort = $request->get('sort');
            $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sort, $direction);
        } else {
            // Default: most recent visit first
            $query->orderBy('created_at', 'desc');
        }

        $visitors = $query->get();

        // 🧾 CSV headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $columns = [
            'Full Name',
            'Email',
            'Mobile',
            'Company',
            'Person Visited',
            'Purpose',
            'Status',
            'Visit Date',
            'Check-In',
            'Check-Out',
        ];

        return response()->stream(function () use ($visitors, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($visitors as $v) {
                fputcsv($handle, [
                    $v->full_name ?? '-',
                    $v->email ?? '-',
                    $v->mobile ?? '-',
                    $v->company ?? '-',
                    optional($v->visitedUser)->name ?? '-',
                    $v->purpose ?? '-',
                    ucfirst($v->status ?? '-'),
                    $v->visit_date ? Carbon::parse($v->visit_date)->format('Y-m-d H:i') : '-',
                    $v->check_in_at ? $v->check_in_at->format('Y-m-d H:i') : '-',
                    $v->check_out_at ? $v->check_out_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**Webhook */

    public function webhookPreapproved(Request $request)
    {
        // -----------------------------------------------------
        // 1. Validate HMAC signature
        // -----------------------------------------------------
        $secret = config('services.webhook.secret');
        // dd($secret);
        $signature = $request->header('X-Signature');

        if (!$signature) {
            return response()->json(['message' => 'Missing signature'], 401);
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($computed, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // -----------------------------------------------------
        // 2. Validate payload
        // -----------------------------------------------------
        $data = $request->validate([
            'visit_date' => 'required|date|after_or_equal:today',
            'purpose' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'visitors' => 'required|array|min:1',
            'visitors.*.name' => 'required|string|max:255',
            'visitors.*.email' => 'required|email',
        ]);

        // -----------------------------------------------------
        // 3. Identify visited_user_id via GOOGLE_CALENDAR_IMPERSONATE
        // -----------------------------------------------------
        $impersonateEmail = config('visitors.impersonate_email');

        $visitedUserId = null;

        if ($impersonateEmail) {
            $user = \App\Models\User::where('email', $impersonateEmail)->first();
            $visitedUserId = $user?->id; // null if not found
        }

        // -----------------------------------------------------
        // 4. Generate batch ID
        // -----------------------------------------------------
        $isBatch = count($data['visitors']) >= 1;
        $batchId = $isBatch ? VisitorLog::generateBatchId() : null;

        $created = [];

        // -----------------------------------------------------
        // 5. Reject if duplicate active visit exists
        // -----------------------------------------------------
        foreach ($data['visitors'] as $visitor) {
            $exists = VisitorLog::where('email', $visitor['email'])
                ->where('visit_date', $data['visit_date'])
                ->whereIn('status', ['approved', 'pending'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => "{$visitor['name']} already has an active visit on {$data['visit_date']}."
                ], 409);
            }
        }

        // -----------------------------------------------------
        // 6. Create the records
        // -----------------------------------------------------
        foreach ($data['visitors'] as $visitor) {
            $record = VisitorLog::create([
                'full_name' => $visitor['name'],
                'email' => $visitor['email'],
                'purpose' => $data['purpose'],
                'company' => $data['company'],
                'visit_date' => $data['visit_date'],
                'meetup_spot' => $data['notes'],
                'visited_user_id' => $visitedUserId,
                'status' => 'approved',
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
                'emailed_at' => now(),
            ]);

            try {
                \Mail::to($record->email)->queue(new VisitorPreApprovedMail($record));
            } catch (\Throwable $e) {
                \Log::error("Failed to queue pre-approved email for visitor {$record->id}: " . $e->getMessage(), [
                    'visitor_id' => $record->id,
                    'email' => $record->email,
                ]);
            }

            $created[] = [
                'id' => $record->id,
                'email' => $record->email,
            ];
        }

        // -----------------------------------------------------
        // 7. Return success
        // -----------------------------------------------------
        return response()->json([
            'message' => 'Webhook received. Pre-approved visit(s) created.',
            'batch_id' => $batchId,
            'created' => $created,
        ]);
    }


    public function visitorDestroy(VisitorLog $visitor)
    {
        $visitor->delete();
        return redirect()->route('frontdesk.visitors')->with('flash', [
            'type' => 'success',
            'message' => 'Visitor log permanently deleted.',
        ]);

    }

    public function cleanUp()
    {
        \DB::table('visitor_logs')
            ->where('status', 'pending')
            ->whereNull('full_name')
            ->delete();

        return redirect()
            ->route('frontdesk.visitors')
            ->with('flash', [
                'type' => 'success',
                'message' => 'All incomplete pending visitor records have been cleaned up.',
            ]);
    }



    // Add Pre-approved 
    public function createPreapproved()
    {
        return view('frontdesk.visitors.create-preapproved');
    }

    public function cancelBatch($batchId)
    {
        $count = VisitorLog::where('batch_id', $batchId)->count();

        VisitorLog::where('batch_id', $batchId)->delete();

        return redirect()->route('visitors.mine')
            ->with('flash', [
                'type' => 'success',
                'message' => "Cancelled {$count} pre-approved visit(s).",
            ]);
    }

    public function showValidQr($visitor_id, $batch_id)
    {
        $visitor = VisitorLog::where('id', $visitor_id)
            ->where('batch_id', $batch_id)
            ->first();

        if (
            !$visitor ||
            Carbon::parse($visitor->visit_date)->lt(Carbon::now()->subDay())
        ) {
            // Visitor does not exist or visit_date is more than 24 hours old
            return view('frontdesk.visitors.qr-invalid');
        }

        return view('frontdesk.visitors.qr-valid', compact('visitor'));

    }
}
