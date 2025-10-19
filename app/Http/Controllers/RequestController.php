<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Carbon\CarbonPeriod;
use App\Mail\RequestMade;
use App\Models\OrgSetting;
use Illuminate\Http\Request;
use App\Models\RequestCredit;
use App\Mail\RequestCancelled;
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
        ->paginate(10);

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
    $request->validate(
        [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ],
        [
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.after_or_equal:today' => 'Start date cannot be in the past.',
            

            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal:today' => 'End date must be the same or after the start date.',

            'reason.required' => 'Please provide a reason for your request.',
            'reason.string' => 'Reason must be valid text.',    
        ]
    );

    $user = Auth::user();
    $credits = $user->requestCredit;

    $start = Carbon::parse($request->start_date);
    $end = Carbon::parse($request->end_date);

    // Count weekdays only
    $days = $start->diffInDaysFiltered(function (Carbon $date) {
        return $date->isWeekday();
    }, $end) + 1;

    if (($request->end_date_type === 'half-am-off') || ($request->end_date_type === 'half-pm-off'))  {
        $days -= 0.5;
    }

    // If 1 or 2 day request
    if ($days <= 2) {
        // Build a date period for the requested days
        $period = CarbonPeriod::create($start, $end);

        $allWeekend = true;
        foreach ($period as $date) {
            if (!$date->isWeekend()) {
                $allWeekend = false;
                break;
            }
        }

        if ($allWeekend) {
            return back()->withErrors([
                'date' => 'Leave requests of 1–2 days cannot fall entirely on weekends.'
            ]);
        }
    }

    // Check overlapping requests
    $hasOverlap = StaffRequest::where('user_id', $user->id)
        ->whereNotIn('status', ['cancelled', 'rejected'])
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

    // Check credit availability
    if ($request->type !== 'LWOP') {
        $outstanding = StaffRequest::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('status', 'pending')
            ->sum('number_of_days');

        $creditExceeded = match ($request->type) {
            'PTO' => $days > ($credits->pto - $outstanding),
            'WFH' => $days > ($credits->wfh - $outstanding),
            default => false,
        };

        if ($creditExceeded) {
            return back()->withErrors([
                'type' => "Insufficient credits for selected request type. You requested {$days} day(s)."
            ]);
        }
    }

    // Create the request
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
        Mail::to($supervisor->email)
            ->cc(env('REQUESTS_HR_EMAIL'))
            ->queue(new RequestMade($requestRecord));
    }

    return redirect()->route('my-requests')->with('success', 'Request submitted.');
}


public function manage()
{
    if (!Gate::allows('is-manager-or-hr')) {
        abort(403, "Unauthorized Access.");
    }

    $query = StaffRequest::with(['user.requestCredit'])->latest();

    // Managers only see requests of their direct reports
    if (auth()->user()->isManager() && !Gate::allows('is-super-admin')) {
    $query->whereHas('user', function ($q) {
        $q->where('supervisor_id', auth()->id());
    });
}

    $requests = $query->paginate(10);
    // dd($requests);
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

    if($staffRequest->status==='cancelled') {
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
    
    $withHalfDay = '';

    $endType = strtolower($staffRequest->end_date_type ?? '');
    $days = $staffRequest->number_of_days ?? 0;

    if ($endType !== 'full' && $days > 0) {
        $isMorning = $endType === 'half-am-off';
        $halfDayLabel = $isMorning ? 'Morning Off' : 'Afternoon Off';
        $withHalfDay = $days > 0.5
            ? "Includes 1/2 Day: {$halfDayLabel}"
            : "1/2 Day: {$halfDayLabel}";
    }
     
    // Google Calendar
    try {
        if ($staffRequest->status === 'approved') {
            $eventTitle = match ($staffRequest->type) {
                'PTO' => "{$staffRequest->user->name} - On Leave",
                'WFH' => "{$staffRequest->user->name} - Work From Home",
                default => "{$staffRequest->user->name} - Approved Request",
            };

            $startDate = Carbon::parse($staffRequest->start_date);
            $endDate = Carbon::parse($staffRequest->end_date)->addDay();


            if ($staffRequest->google_event_id) {
                $event = Event::find($staffRequest->google_event_id);
                if ($event) {
                    $event->name = trim($eventTitle .'; '. $withHalfDay);
                    $event->description = "Event created by Life Portal";
                    $event->startDate = $startDate;
                    $event->endDate = $endDate;
                    $event->save();
                }
            } else {
                $event = new Event;
                $event->name = trim($eventTitle .'; '. $withHalfDay);
                $event->description = "Event created by Life Portal";
                $event->startDate = $startDate;
                $event->endDate = $endDate;
                $event->addAttendee(['email' => $staffRequest->user->email]);
                $newEvent = $event->save();

                $staffRequest->google_event_id = $newEvent->id;
                $staffRequest->save();
            }

            Log::info('Google Calendar event synced', [
                'request_id' => $staffRequest->id,
                'event_id' => $staffRequest->google_event_id,
            ]);
        }

        // Delete event if request is rejected
        if ($staffRequest->status === 'rejected' && $staffRequest->google_event_id) {
            $event = Event::find($staffRequest->google_event_id);
            if ($event) {
                $event->delete();
                Log::info('Google Calendar event deleted after rejection', [
                    'request_id' => $staffRequest->id,
                    'event_id' => $staffRequest->google_event_id,
                ]);
            }

            $staffRequest->google_event_id = null;
            $staffRequest->save();
        }

    } catch (\Throwable $e) {
        Log::error('Google Calendar sync failed', [
            'error' => $e->getMessage(),
            'request_id' => $staffRequest->id,
        ]);
    }



    $ccRecipients = [env('REQUESTS_HR_EMAIL'),env('REQUESTS_OP_EMAIL')];

    $requester = $staffRequest->user;
    if ($requester && $requester->email) {
        Mail::to($requester->email)
            ->cc($ccRecipients)
            ->queue(new ResponseReceived($staffRequest));
    }

    return redirect()->route('requests.manage')
        ->with('info', "Request {$staffRequest->status}.");
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
    // dd($requestModel);
    return view('requests.edit-view', [
        'request' => $requestModel,
        'canEdit' => $canEdit,
    ]);
}


public function archive(Request $request, $id)
{
    $staffRequest = StaffRequest::findOrFail($id);
    if ($staffRequest->user_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }

    // Allow only if pending and in the future
    if (
        $staffRequest->status !== 'pending'
    ) {
        abort(409, 'Request cannot be cancelled');
    }

    $staffRequest->update(['status' => 'cancelled']);

     // Notify supervisor + HR
    $user = auth()->user();
    $supervisor = $user->supervisor;

    Mail::to($supervisor?->email)
    ->cc(env('REQUESTS_HR_EMAIL'))
    ->queue(new RequestCancelled($staffRequest));

    return redirect()
        ->route('my-requests')
        ->with('info', 'Request has been cancelled and notifications sent.');
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
        'end_date_type' => 'required|in:full,half-am-off,half-pm-off',
    ]);

    // Compute number of days
    $start = Carbon::parse($validated['start_date']);
    $end = Carbon::parse($validated['end_date']);
    $days = $start->diffInDays($end) + 1;
    if (($request->end_date_type === 'half-am-off') || ($request->end_date_type === 'half-pm-off'))  {
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
    public function initiateLeave()
{
    $settings = OrgSetting::firstOrFail();

    // loop through all users
    $users = User::all();

    foreach ($users as $user) {
        RequestCredit::updateOrCreate(
            ['user_id' => $user->id],
            [
                'pto' => $settings->pto_default,
                'wfh' => $settings->wfh_default,
            ]
        );
    }

    return redirect()->back()->with('success', 'Leave credits updated for all users.');
}

public function forceDestroy(StaffRequest $request)
{
    $request->delete();
    return redirect()->route('requests.manage')->with('success', 'Request permanently deleted.');
}


// Manage HR View

public function manageHr(Request $request)
{
    if (!Gate::allows('is-pnc')) {
        abort(403, 'Unauthorized Access.');
    }

    // Eager-load user, department, request credits, and approver
    $query = StaffRequest::with(['user.requestCredit', 'user.department', 'approver']);

    // 🔍 Filters
    if ($request->filled('employee')) {
        $employee = strtolower($request->employee);
        $query->whereHas('user', function ($q) use ($employee) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$employee}%"]);
        });
    }

    if ($request->filled('department')) {
        // Filter by department_id (dropdown returns the ID)
        $query->whereHas('user.department', function ($q) use ($request) {
            $q->where('id', $request->department);
        });
    }

    if ($request->filled('type')) {
        $query->where('type', $request->type);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('date_from') && $request->filled('date_to')) {
        $query->whereBetween('start_date', [$request->date_from, $request->date_to]);
    }

    // 🔽 Sorting
    $sortable = ['employee', 'department', 'approver', 'type', 'start_date', 'number_of_days', 'status'];
    $sort = $request->get('sort', 'created_at');
    $direction = $request->get('direction', 'desc');

    if ($sort === 'employee') {
        $query->join('users', 'requests.user_id', '=', 'users.id')
              ->select('requests.*')
              ->orderByRaw('LOWER(users.name) ' . ($direction === 'asc' ? 'ASC' : 'DESC'));
    } elseif ($sort === 'department') {
        $query->join('users', 'requests.user_id', '=', 'users.id')
              ->join('departments', 'users.department_id', '=', 'departments.id')
              ->select('requests.*')
              ->orderByRaw('LOWER(departments.name) ' . ($direction === 'asc' ? 'ASC' : 'DESC'));
    } elseif ($sort === 'approver') {
        $query->leftJoin('users as approvers', 'requests.approver_id', '=', 'approvers.id')
              ->select('requests.*')
              ->orderByRaw('LOWER(approvers.name) ' . ($direction === 'asc' ? 'ASC' : 'DESC'));
    } elseif (in_array($sort, $sortable)) {
        $query->orderBy($sort, $direction);
    } else {
        $query->latest();
    }

    $requests = $query->paginate(10)->withQueryString();

    return view('requests.manage-hr', compact('requests', 'sort', 'direction'));
}





public function showHr(Request $request, StaffRequest $requestModel)
{
    if (!Gate::allows('is-pnc')) {
        abort(403, 'Unauthorized Access.');
    }

    // HR can view all requests, no restriction by user/supervisor
    return view('requests.show-hr', [
        'request' => $requestModel,
    ]);
}

public function purgeCancelled()
{
    if (!Gate::allows('is-pnc')) {
        abort(403, 'Unauthorized Access.');
    }

    $deletedCount = StaffRequest::where('status', 'cancelled')->delete();

    return back()->with('info', "{$deletedCount} records deleted.");
    }


// Manage HR View

}
