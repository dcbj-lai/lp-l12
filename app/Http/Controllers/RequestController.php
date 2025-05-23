<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\RequestMade;
use Illuminate\Http\Request;
use App\Mail\ResponseReceived;
use Spatie\GoogleCalendar\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestStatusNotification;
use App\Models\Request as StaffRequest;


class RequestController extends Controller
{
 public function index()
{
    $requests = StaffRequest::with('approver')
        ->where('user_id', auth()->id())
        ->latest('created_at')
        ->paginate(15);

    return view('requests.my-requests', compact('requests'));
}

public function create()
{
    $user = Auth::user();

    // Fetch current request credits
    $credits = $user->requestCredit; // Assuming relationship exists

    return view('requests.create', compact('credits'));
}

public function store(Request $request)
{
    try {
        $request->validate([
            'type' => 'required|in:PTO,WFH,LWOP', // removed 'Offset', added 'LWOP'
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_date_type' => 'required|in:full,half',
            'reason' => 'required|string',
        ]);

        $user = Auth::user();
        $credits = $user->requestCredit;

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $days = $start->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday(); // Optional: skip weekends
        }, $end) + 1;

        if ($request->end_date_type === 'half') {
            $days -= 0.5;
        }
        $hasOverlap = StaffRequest::where('user_id', $user->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors([
                'start_date' => 'Your selected dates overlap with an existing request.',
                'end_date' => 'Please choose a non-conflicting range.',
            ]);
        }


        $creditExceeded = false;

        if (!in_array($request->type, ['LWOP'])) {
            $outstanding = StaffRequest::where('user_id', $user->id)
                ->where('type', $request->type)
                ->whereIn('status', ['pending'])
                ->sum('number_of_days');
            // dd($outstanding);
            switch ($request->type) {
                case 'PTO':
                    if ($days > ($credits->pto - $outstanding)) $creditExceeded = true;
                    break;
                case 'WFH':
                    if ($days > ($credits->wfh - $outstanding)) $creditExceeded = true;
                    break;
            }

            if ($creditExceeded) {
                return back()->withErrors([
                    'type' => "Insufficient credits for selected request type. You requested {$days} day(s)."
                ]);
            }
        }

        

        $requestRecord = StaffRequest::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'reason' => $request->reason,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'end_date_type' => $request->end_date_type,
            'number_of_days' => $days,
            'status' => 'pending',
        ]);
        
        // Notify supervisor
        $supervisor = $user->supervisor;
        if ($supervisor && $supervisor->email) {
            Mail::to($supervisor)->queue(new RequestMade($requestRecord));
        }
        

        return redirect()->route('my-requests')->with('success', 'Request submitted successfully.');
    } catch (\Exception $e) {
        // Log the actual error for debugging (optional)
        \Log::error('Request submission failed', ['error' => $e->getMessage()]);

        return back()->withErrors([
            'general' => 'Something went wrong while submitting the request. Please try again.',
        ]);
    }
}

public function manage()
{
    if (!Gate::allows('is-manager-or-hr')) {
        abort(403, "Unauthorized Access.");
    }
    $requests = StaffRequest::with('user')
        ->latest()
        ->paginate(10);

    return view('requests.manage', compact('requests'));
}



public function process(Request $request, $id)
{
    if (!Gate::allows('is-manager-or-hr')) {
        // Log::warning("Access denied for user", ['user_id' => auth()->id()]);
        abort(403);
    }

    $staffRequest = StaffRequest::findOrFail($id);

    if (($staffRequest->status === 'approved' && $request->input('action_type') === 'approve') || ($staffRequest->status === 'rejected' && $request->input('action_type') === 'reject'))
    {   
        return redirect()->back()
            ->with('error', "Request already {$staffRequest->status}.");
    }

    $originalStatus = $staffRequest->status;

    $validated = $request->validate([
        'remarks' => 'nullable|string|max:500',
        'action_type' => 'required|in:approve,reject',
    ]);

    $staffRequest->remarks = $validated['remarks'];
    $staffRequest->status = $validated['action_type'] === 'approve' ? 'approved' : 'rejected';
    $staffRequest->approver_id = auth()->id();
    $staffRequest->save();


    if ($staffRequest->status === 'approved' && !in_array($staffRequest->type, ['LWOP'])) {
        // Log::info("Handling credit deduction for approval");

        $credit = $staffRequest->user->requestCredit;

        if ($credit) {

            switch ($staffRequest->type) {
                case 'PTO':
                    $credit->pto -= $staffRequest->number_of_days;
                    // Log::info("Deducting PTO", ['deducted_days' => $staffRequest->number_of_days]);
                    break;
                case 'WFH':
                    $credit->wfh -= $staffRequest->number_of_days;
                    // Log::info("Deducting WFH", ['deducted_days' => $staffRequest->number_of_days]);
                    break;
            }

            $credit->save();
        } else {
            Log::warning("No credit record found for user", ['user_id' => $staffRequest->user_id]);
        }

    }

    if (
        $originalStatus === 'approved' &&
        $staffRequest->status === 'rejected' &&
        !in_array($staffRequest->type, ['LWOP'])
    ) {

        $credit = $staffRequest->user->requestCredit;

        if ($credit) {
            switch ($staffRequest->type) {
                case 'PTO':
                    $credit->pto += $staffRequest->number_of_days;
                    break;
                case 'WFH':
                    $credit->wfh += $staffRequest->number_of_days;
                    break;
            }

            $credit->save();
        } else {
            Log::warning("No credit found to reverse for user", ['user_id' => $staffRequest->user_id]);
        }
    }

    $requester = $staffRequest->user;
    if ($requester && $requester->email) {
        Mail::to($requester->email)->queue(new ResponseReceived($staffRequest));
    }


    return redirect()->route('requests.manage')
        ->with('success', "Request {$staffRequest->status} successfully.");
}

    

public function show(StaffRequest $request)
{
    if (!Gate::allows('is-manager-or-hr')) {
        abort(403, "Unauthorized Access.");
    }

    return view('requests.show', [
        'request' => $request->load('user', 'approver'),
    ]);
}

/**Requester view for his own request */
public function view(Request $request, StaffRequest $requestModel)
{

    $user = $request->user();
    if ($user->id !== $requestModel->user_id) {
        abort(403, 'Unauthorized access to request.');
    }
    
    $canEdit = $requestModel->status === 'pending';
    
    return view('requests.edit-view', [
        'request' => $requestModel,
        'canEdit' => $canEdit,
    ]);
}


public function destroy(StaffRequest $request, Request $httpRequest)
{
    if (!Gate::allows('is-manager-or-hr')) {
        if ($request->status != 'pending') {
            abort(409, 'Cannot delete an approved or rejected request');
        }

        if ($request->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
    }

    // Refresh credit if approved
    if ($request->status === 'approved') {
        $credits = $request->user->requestCredit;
        // dd($credits);
        if ($credits) {
            switch ($request->type) {
                case 'PTO':
                    $credits->pto += $request->number_of_days;
                    break;
                case 'WFH':
                    $credits->wfh += $request->number_of_days;
                    break;
            }

            $credits->save();
        }
    }

    $request->delete();

    return redirect()
        ->route('requests.manage')
        ->with('success', 'Request deleted successfully.');
}



public function update(Request $request, StaffRequest $requestModel)
{
    $user = $request->user();

    if ($user->id !== $requestModel->user_id) {
        abort(403, 'Unauthorized update attempt.');
    }

    if ($requestModel->status !== 'pending') {
        return back()->with('error', 'Only pending requests can be updated.');
    }

    $validated = $request->validate([
        'type' => 'required|in:PTO,WFH,LWOP',
        'reason' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'end_date_type' => 'required|in:full,half',
    ]);

    // Compute number of days
    $start = Carbon::parse($validated['start_date']);
    $end = Carbon::parse($validated['end_date']);
    $days = $start->diffInDays($end) + 1;
    if ($validated['end_date_type'] === 'half') {
        $days -= 0.5;
    }

    $requestModel->update([
        'type' => $validated['type'],
        'reason' => $validated['reason'],
        'start_date' => $validated['start_date'],
        'end_date' => $validated['end_date'],
        'end_date_type' => $validated['end_date_type'],
        'number_of_days' => $days,
    ]);

    return redirect()->route('my-requests', $requestModel)->with('success', 'Request updated.');
}




}
