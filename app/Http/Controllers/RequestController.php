<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Carbon\CarbonPeriod;
use App\Mail\RequestMade;
use App\Models\OrgSetting;
use App\Models\LeaveReplenishmentRun;
use Illuminate\Http\Request;
use App\Models\RequestCredit;
use App\Mail\RequestCancelled;
use App\Mail\ResponseReceived;
use Spatie\GoogleCalendar\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
// use App\Mail\RequestStatusNotification;
use App\Models\Request as StaffRequest;
use Illuminate\Support\Facades\Storage;


class RequestController extends Controller
{
    public function apiIndex(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable'],
            'email' => ['nullable', 'email'],
            'department_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:PTO,WFH,LWOP,CREDIT_CARRY_OVER'],
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled,all'],
            'is_offset' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'updated_since' => ['nullable', 'date'],
            'created_since' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $status = $validated['status'] ?? 'all';
        $limit = (int) ($validated['limit'] ?? 1000);

        $query = StaffRequest::query()
            ->with(['user.department:id,name', 'user.requestCredit', 'approver:id,name,email'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when(isset($validated['type']), fn ($query) => $query->where('type', $validated['type']))
            ->when(array_key_exists('is_offset', $validated), fn ($query) => $query->where('is_offset', $request->boolean('is_offset')))
            ->when(isset($validated['date_from']), fn ($query) => $query->whereDate('start_date', '>=', $validated['date_from']))
            ->when(isset($validated['date_to']), fn ($query) => $query->whereDate('start_date', '<=', $validated['date_to']))
            ->when(isset($validated['updated_since']), fn ($query) => $query->where('updated_at', '>=', $validated['updated_since']))
            ->when(isset($validated['created_since']), fn ($query) => $query->where('created_at', '>=', $validated['created_since']))
            ->when(isset($validated['employee_number']), function ($query) use ($validated) {
                $employeeNumber = trim((string) $validated['employee_number']);

                $query->whereHas('user', fn ($userQuery) => $userQuery->where('employee_number', $employeeNumber));
            })
            ->when(isset($validated['email']), function ($query) use ($validated) {
                $email = strtolower($validated['email']);

                $query->whereHas('user', fn ($userQuery) => $userQuery->whereRaw('LOWER(email) = ?', [$email]));
            })
            ->when(isset($validated['department_id']), function ($query) use ($validated) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('department_id', $validated['department_id']));
            })
            ->when(isset($validated['search']), function ($query) use ($validated) {
                $search = mb_strtolower(trim((string) $validated['search']));

                if ($search === '') {
                    return;
                }

                $query->where(function ($query) use ($search) {
                    $like = "%{$search}%";

                    $query->whereRaw('LOWER(reason) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(remarks) LIKE ?', [$like])
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery->whereRaw('LOWER(employee_number) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(preferred_name) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                        });
                });
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit($limit);

        $requests = $query->get();

        return response()->json([
            'filters' => [
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'status' => $status,
                'type' => $validated['type'] ?? null,
                'is_offset' => array_key_exists('is_offset', $validated) ? $request->boolean('is_offset') : null,
                'updated_since' => $validated['updated_since'] ?? null,
                'created_since' => $validated['created_since'] ?? null,
                'limit' => $limit,
            ],
            'count' => $requests->count(),
            'data' => $requests->map(fn (StaffRequest $staffRequest) => $this->apiRowFor($staffRequest))->values(),
        ]);
    }

    public function apiRejectCarryOver(Request $request, StaffRequest $requestModel)
    {
        abort_unless($requestModel->isCreditCarryOver(), 422, 'Only credit carry-over requests can be rejected through this endpoint.');
        abort_unless($this->canProcessLeaveRequest($requestModel, $request->user()), 403);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($requestModel->status !== 'rejected') {
            $requestModel->status = 'rejected';
            $requestModel->approver_id = $request->user()->id;
        }

        $requestModel->remarks = $validated['remarks'] ?? $requestModel->remarks;
        $requestModel->save();

        $this->syncApprovedCarryOverForUser($requestModel->user_id);

        return response()->json([
            'data' => $this->apiRowFor($requestModel->fresh(['user.department:id,name', 'user.requestCredit', 'approver:id,name,email'])),
        ]);
    }

    public function apiApproveCarryOver(Request $request, StaffRequest $requestModel)
    {
        abort_unless($requestModel->isCreditCarryOver(), 422, 'Only credit carry-over requests can be approved through this endpoint.');
        abort_unless($this->canProcessLeaveRequest($requestModel, $request->user()), 403);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if (
            $requestModel->status !== 'approved'
            && ! $this->carryOverApprovalFitsAvailableCredits($requestModel)
        ) {
            return response()->json([
                'message' => 'Request exceeds the remaining available carry-over credits.',
            ], 422);
        }

        if ($requestModel->status !== 'approved') {
            $requestModel->status = 'approved';
            $requestModel->approver_id = $request->user()->id;
        }

        $requestModel->remarks = $validated['remarks'] ?? $requestModel->remarks;
        $requestModel->save();

        $this->syncApprovedCarryOverForUser($requestModel->user_id);

        return response()->json([
            'data' => $this->apiRowFor($requestModel->fresh(['user.department:id,name', 'user.requestCredit', 'approver:id,name,email'])),
        ]);
    }

    public function index()
    {
        $requests = StaffRequest::with('approver')
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->paginate(10);

        return view('requests.my-requests', compact('requests'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        // Fetch current request credits
        $credits = $user->requestCredit; // Assuming relationship exists

        $requestKind = $request->query('kind') === 'credit-carry-over'
            ? 'credit-carry-over'
            : 'leave';

        return view('requests.create', compact('credits', 'requestKind'));
    }


    public function store(Request $request)
    {
        if ($request->input('request_kind') === 'credit-carry-over') {
            $validated = $request->validate([
                'carry_over_days' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
                'reason' => ['required', 'string', 'max:500'],
            ], [
                'carry_over_days.required' => 'Please enter the carry-over credits requested.',
                'carry_over_days.min' => 'Carry-over credits must be greater than zero.',
                'reason.required' => 'Please provide a reason for your request.',
            ]);

            $user = Auth::user();
            $today = now()->toDateString();
            $requestedCarryOver = (float) $validated['carry_over_days'];
            $remainingCarryOver = $this->remainingCarryOverCreditsForRequest($user->id);

            if ($requestedCarryOver > $remainingCarryOver) {
                return back()
                    ->withErrors([
                        'carry_over_days' => 'Requested carry-over credits exceed your remaining available leave credits.',
                    ])
                    ->withInput();
            }

            $requestRecord = StaffRequest::create([
                'user_id' => $user->id,
                'type' => StaffRequest::TYPE_CREDIT_CARRY_OVER,
                'is_offset' => false,
                'reason' => $validated['reason'],
                'start_date' => $today,
                'end_date' => $today,
                'end_date_type' => 'full',
                'number_of_days' => $requestedCarryOver,
                'status' => 'pending',
            ]);

            $supervisor = $user->supervisor;
            if ($supervisor?->email) {
                $mail = Mail::to($supervisor->email);
                $ccRecipients = $this->requestHrRecipients();

                if ($ccRecipients !== []) {
                    $mail->cc($ccRecipients);
                }

                $mail->queue(new RequestMade($requestRecord));
            }

            return redirect()
                ->route('my-requests')
                ->with('flash', [
                    'type' => 'success',
                    'message' => 'Credit carry-over request submitted.',
                ]);
        }

        $validated = $request->validate(
            [
                'type' => 'required|in:PTO,WFH,LWOP',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'end_date_type' => 'required|in:full,half-am-off,half-pm-off',
                'reason' => 'required|string',
                'is_offset' => 'nullable|boolean',

                'offset_proof' => [
                    'nullable', // ✅ FIX
                    'exclude_unless:is_offset,1',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:5120',
                ],
            ],
            [
                'reason.required' => 'Please provide a reason for your request.',
            ]
        );

        $user = Auth::user();
        $credits = $user->requestCredit;

        /*
         |-------------------------------------------------
         | Compute number of days (weekdays only)
         |-------------------------------------------------
         */
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        $days = $start->diffInDaysFiltered(
            fn(Carbon $date) => $date->isWeekday(),
            $end
        ) + 1;

        if (in_array($validated['end_date_type'], ['half-am-off', 'half-pm-off'])) {
            $days -= 0.5;
        }

        /*
         |-------------------------------------------------
         | Weekend-only guard (1–2 days)
         |-------------------------------------------------
         */
        if ($days <= 2) {
            $period = CarbonPeriod::create($start, $end);

            $allWeekend = collect($period)->every(fn($d) => $d->isWeekend());

            if ($allWeekend) {
                return back()->withErrors([
                    'date' => 'Leave requests of 1–2 days cannot fall entirely on weekends.',
                ]);
            }
        }

        /*
         |-------------------------------------------------
         | Overlap check
         |-------------------------------------------------
         */
        $hasOverlap = StaffRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->where(function ($query) use ($validated) {
                $query
                    ->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors([
                'start_date' => 'Your selected dates overlap with an existing request.',
                'end_date' => 'Please choose a non-conflicting range.',
            ]);
        }

        /*
         |-------------------------------------------------
         | Credit availability (skip if offset)
         |-------------------------------------------------
         */
        if ($validated['type'] !== 'LWOP' && !$request->boolean('is_offset')) {
            $outstanding = StaffRequest::where('user_id', $user->id)
                ->where('type', $validated['type'])
                ->where('status', 'pending')
                ->sum('number_of_days');

            $creditExceeded = match ($validated['type']) {
                'PTO' => $days > ($credits->pto - $outstanding),
                'WFH' => $days > ($credits->wfh - $outstanding),
                default => false,
            };

            if ($creditExceeded) {
                return back()->withErrors([
                    'type' => "Insufficient credits. You requested {$days} day(s).",
                ]);
            }
        }

        /*
         |-------------------------------------------------
         | Handle offset proof upload (WORKING PATTERN)
         |-------------------------------------------------
         */
        $offsetProofPath = null;

        if ($request->boolean('is_offset') && $request->hasFile('offset_proof')) {
            $file = $request->file('offset_proof');

            $safeName = StaffRequest::sanitizeFilename(
                $file->getClientOriginalName()
            );

            $username = preg_replace(
                '/[^A-Za-z0-9_-]/',
                '',
                str_replace(' ', '_', $user->name)
            );

            $folder = "requests/{$user->id}-{$username}";

            $offsetProofPath = $file->storeAs(
                $folder,
                $safeName,
                'private_s3'
            );
        }

        /*
         |-------------------------------------------------
         | Create request
         |-------------------------------------------------
         */
        $requestRecord = StaffRequest::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'is_offset' => $request->boolean('is_offset'),
            'offset_proof_path' => $offsetProofPath,
            'reason' => $validated['reason'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'end_date_type' => $validated['end_date_type'],
            'number_of_days' => $days,
            'status' => 'pending',
        ]);



        /*
         |-------------------------------------------------
         | Notify supervisor
         |-------------------------------------------------
         */
        $supervisor = $user->supervisor;
        if ($supervisor?->email) {
            $mail = Mail::to($supervisor->email);
            $ccRecipients = $this->requestHrRecipients();

            if ($ccRecipients !== []) {
                $mail->cc($ccRecipients);
            }

            $mail->queue(new RequestMade($requestRecord));
        }

        return redirect()
            ->route('my-requests')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Request submitted.',
            ]);
    }



    public function manage(Request $request)
    {
        if (!Gate::allows('is-manager-or-hr')) {
            abort(403, "Unauthorized Access.");
        }

        $search = trim((string) $request->query('search', ''));
        $normalizedSearch = mb_strtolower($search);
        $query = StaffRequest::with(['user.requestCredit'])->latest();

        $user = auth()->user();

        // Managers only see requests of their direct reports; P&C super/admin can see all.
        if ($user->isManager() && ! $user->canApproveAnyLeaveRequest()) {
            $query->whereHas('user', function ($q) {
                $q->where('supervisor_id', auth()->id());
            });
        }

        $query->when($search !== '', function ($query) use ($normalizedSearch) {
            $like = "%{$normalizedSearch}%";

            $query->whereHas('user', function ($userQuery) use ($like) {
                $userQuery->whereRaw('LOWER(employee_number) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(preferred_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        });

        $requests = $query->paginate(10)->withQueryString();
        // dd($requests);
        return view('requests.manage', compact('requests', 'search'));
    }



    public function process(Request $request, $id)
    {
        if (!Gate::allows('is-manager-or-hr')) {
            // Log::warning("Access denied for user", ['user_id' => auth()->id()]);
            abort(403);
        }

        $staffRequest = StaffRequest::findOrFail($id);

        abort_unless($this->canProcessLeaveRequest($staffRequest, auth()->user()), 403);

        if (
            ($staffRequest->status === 'approved' && $request->input('action_type') === 'approve') ||
            ($staffRequest->status === 'rejected' && $request->input('action_type') === 'reject')
        ) {
            return redirect()->back()
                ->with('flash', [
                    'type' => 'error',
                    'message' => "Request already {$staffRequest->status}.",
                ]);
        }

        if ($staffRequest->status === 'cancelled') {
            return redirect()->back()
                ->with('flash', [
                    'type' => 'error',
                    'message' => "Request already {$staffRequest->status}.",
                ]);
        }

        $originalStatus = $staffRequest->status;

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
            'action_type' => 'required|in:approve,reject',
        ]);

        if (
            $validated['action_type'] === 'approve'
            && $staffRequest->isCreditCarryOver()
            && ! $this->carryOverApprovalFitsAvailableCredits($staffRequest)
        ) {
            return redirect()->back()
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Request exceeds the remaining available carry-over credits.',
                ]);
        }

        $staffRequest->remarks = $validated['remarks'];
        $staffRequest->status = $validated['action_type'] === 'approve' ? 'approved' : 'rejected';
        $staffRequest->approver_id = auth()->id();
        $staffRequest->save();


        if (
            $staffRequest->status === 'approved'
            && ! $staffRequest->isCreditCarryOver()
            && !in_array($staffRequest->type, ['LWOP'])
            && !$staffRequest->is_offset
        ) {


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

        if ($staffRequest->isCreditCarryOver()) {
            $this->syncApprovedCarryOverForUser($staffRequest->user_id);
        }

        if (
            $originalStatus === 'approved' &&
            $staffRequest->status === 'rejected' &&
            ! $staffRequest->isCreditCarryOver() &&
            !in_array($staffRequest->type, ['LWOP'])
            && !$staffRequest->is_offset
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
            if ($staffRequest->status === 'approved' && ! $staffRequest->isCreditCarryOver()) {
                $calendarDisplayName = $this->calendarDisplayName($staffRequest->user);

                $eventTitle = match ($staffRequest->type) {
                    'PTO' => "{$calendarDisplayName} - On Leave",
                    'WFH' => "{$calendarDisplayName} - Work From Home",
                    default => "{$calendarDisplayName} - Approved Request",
                };

                $startDate = Carbon::parse($staffRequest->start_date);
                $endDate = Carbon::parse($staffRequest->end_date)->addDay();


                if ($staffRequest->google_event_id) {
                    $event = Event::find($staffRequest->google_event_id);
                    if ($event) {
                        $event->name = trim($eventTitle . '; ' . $withHalfDay);
                        $event->description = "Event created by Life Portal";
                        $event->startDate = $startDate;
                        $event->endDate = $endDate;
                        $event->save();
                    }
                } else {
                    $event = new Event;
                    $event->name = trim($eventTitle . '; ' . $withHalfDay);
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



        $ccRecipients = $this->requestHrRecipients();

        $requester = $staffRequest->user;
        if ($requester && $requester->email) {
            $mail = Mail::to($requester->email);

            if ($ccRecipients !== []) {
                $mail->cc($ccRecipients);
            }

            $mail->queue(new ResponseReceived($staffRequest));
        }

        return redirect()->route('requests.manage')
            ->with('flash', [
                'type' => 'info',
                'message' => "Request {$staffRequest->status}.",
            ]);
    }



    public function show(StaffRequest $request)
    {
        if (!Gate::allows('is-manager-or-hr')) {
            abort(403, "Unauthorized Access.");
        }

        abort_unless($this->canProcessLeaveRequest($request, auth()->user()), 403);

        return view('requests.show', [
            'request' => $request->load('user', 'approver'),
        ]);
    }

    protected function canProcessLeaveRequest(StaffRequest $request, User $user): bool
    {
        if ($user->canApproveAnyLeaveRequest()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        $request->loadMissing('user');

        return (int) ($request->user?->supervisor_id ?? 0) === (int) $user->id;
    }

    protected function syncApprovedCarryOverForUser(int $userId): RequestCredit
    {
        $approvedCarryOver = (float) $this->currentCarryOverRequests($userId)
            ->where('status', 'approved')
            ->sum('number_of_days');

        $credit = RequestCredit::firstOrCreate(['user_id' => $userId]);
        $credit->approved_carry_over = $approvedCarryOver;
        $credit->save();

        return $credit;
    }

    protected function remainingCarryOverCreditsForRequest(int $userId): float
    {
        return max(0, $this->eligibleCarryOverCredits($userId) - $this->activeCarryOverRequestedCredits($userId));
    }

    protected function carryOverApprovalFitsAvailableCredits(StaffRequest $request): bool
    {
        $approvedCarryOver = (float) $this->currentCarryOverRequests($request->user_id)
            ->where('status', 'approved')
            ->whereKeyNot($request->id)
            ->sum('number_of_days');

        return ($approvedCarryOver + (float) $request->number_of_days) <= $this->eligibleCarryOverCredits($request->user_id);
    }

    protected function activeCarryOverRequestedCredits(int $userId): float
    {
        return (float) $this->currentCarryOverRequests($userId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('number_of_days');
    }

    protected function eligibleCarryOverCredits(int $userId): float
    {
        return (float) (RequestCredit::query()
            ->where('user_id', $userId)
            ->value('pto') ?? 0);
    }

    protected function currentCarryOverRequests(int $userId)
    {
        $query = StaffRequest::query()
            ->where('user_id', $userId)
            ->where('type', StaffRequest::TYPE_CREDIT_CARRY_OVER);

        $lastReplenishment = LeaveReplenishmentRun::query()
            ->latest('created_at')
            ->value('created_at');

        if ($lastReplenishment) {
            $query->where('updated_at', '>=', $lastReplenishment);
        }

        return $query;
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

        if ($supervisor?->email) {
            $mail = Mail::to($supervisor->email);
            $ccRecipients = $this->requestHrRecipients();

            if ($ccRecipients !== []) {
                $mail->cc($ccRecipients);
            }

            $mail->queue(new RequestCancelled($staffRequest));
        }

        return redirect()
            ->route('my-requests')
            ->with('flash', [
                'type' => 'info',
                'message' => 'Request has been cancelled and notifications sent.',
            ]);
    }



    public function update(Request $request, StaffRequest $requestModel)
    {
        $user = $request->user();

        if ($user->id !== $requestModel->user_id) {
            abort(403, 'Unauthorized update attempt.');
        }

        if ($requestModel->status !== 'pending') {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Only pending requests can be updated.',
            ]);
        }


        if ($requestModel->isCreditCarryOver()) {
            $validated = $request->validate([
                'carry_over_days' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
                'reason' => ['required', 'string', 'max:500'],
            ]);

            $requestModel->fill([
                'reason' => $validated['reason'],
                'number_of_days' => $validated['carry_over_days'],
            ])->save();

            return redirect()
                ->route('my-requests')
                ->with('flash', [
                    'type' => 'success',
                    'message' => 'Request updated.',
                ]);
        }

        $validated = $request->validate([
            'type' => 'required|in:PTO,WFH,LWOP',
            'is_offset' => 'nullable|boolean',
            'offset_proof' => [
                'nullable',
                'exclude_unless:is_offset,1',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'reason' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_date_type' => 'required|in:full,half-am-off,half-pm-off',
        ]);


        /*
        |-------------------------------------------------
        | Recompute number of days
        |-------------------------------------------------
        */
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        $days = $start->diffInDaysFiltered(
            fn(Carbon $date) => $date->isWeekday(),
            $end
        ) + 1;

        if (in_array($validated['end_date_type'], ['half-am-off', 'half-pm-off'])) {
            $days -= 0.5;
        }

        /*
        |-------------------------------------------------
        | Overlap check (EXCLUDE current request)
        |-------------------------------------------------
        */
        $hasOverlap = StaffRequest::where('user_id', $user->id)
            ->where('id', '!=', $requestModel->id) // 👈 critical difference from store()
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->where(function ($query) use ($validated) {
                $query
                    ->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors([
                'start_date' => 'Your selected dates overlap with an existing request.',
                'end_date' => 'Please choose a non-conflicting range.',
            ]);
        }


        /*
        |-------------------------------------------------
        | Offset proof logic (FIXED)
        |-------------------------------------------------
        */
        $isOffset = $request->boolean('is_offset');
        $removeProof = $request->boolean('remove_offset_proof');

        // Offset OFF → delete proof
        if (!$isOffset && $requestModel->offset_proof_path) {
            $requestModel->deleteOffsetProof();
            $requestModel->offset_proof_path = null;
        }

        $isOffset = $request->boolean('is_offset');
        $hasNewFile = $request->hasFile('offset_proof');
        $removeProof = $request->input('remove_offset_proof') === '1';

        /*
        |-------------------------------------------------
        | Offset OFF → wipe everything
        |-------------------------------------------------
        */
        if (!$isOffset) {
            $requestModel->deleteOffsetProof();
            $requestModel->offset_proof_path = null;
        }

        /*
        |-------------------------------------------------
        | Offset ON
        |-------------------------------------------------
        */
        if ($isOffset) {

            // New file ALWAYS wins
            if ($hasNewFile) {

                $requestModel->deleteOffsetProof();

                $file = $request->file('offset_proof');

                $filename = uniqid('offset_', true) . '_' .
                    StaffRequest::sanitizeFilename(
                        $file->getClientOriginalName()
                    );

                $requestModel->offset_proof_path = $file->storeAs(
                    $requestModel->offsetProofFolder(),
                    $filename,
                    'private_s3'
                );

            }
            // Remove only if NO new file
            elseif ($removeProof) {
                $requestModel->deleteOffsetProof();
                $requestModel->offset_proof_path = null;
            }
        }


        /*
        |-------------------------------------------------
        | Update non-file fields
        |-------------------------------------------------
        */
        $requestModel->fill([
            'type' => $validated['type'],
            'is_offset' => $isOffset,
            'reason' => $validated['reason'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'end_date_type' => $validated['end_date_type'],
            'number_of_days' => $days,
        ]);

        $requestModel->save();

        Log::info('OFFSET UPDATE OK', [
            'path' => $requestModel->offset_proof_path,
        ]);

        return redirect()
            ->route('my-requests')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Request updated.',
            ]);
    }




    public function initiateLeave()
    {
        $settings = OrgSetting::firstOrFail();
        $runDate = now()->toDateString();
        $usersCount = 0;
        $totalCarryOver = 0;

        DB::transaction(function () use ($settings, $runDate, &$usersCount, &$totalCarryOver) {
            $users = User::with('requestCredit')->get();

            $run = LeaveReplenishmentRun::create([
                'run_date' => $runDate,
                'pto_default' => $settings->pto_default,
                'wfh_default' => $settings->wfh_default,
                'users_count' => 0,
                'total_approved_carry_over' => 0,
                'run_by' => auth()->id(),
            ]);

            foreach ($users as $user) {
                $previousPto = (float) ($user->requestCredit?->pto ?? 0);
                $previousWfh = (float) ($user->requestCredit?->wfh ?? 0);
                $carryOver = (float) ($user->requestCredit?->approved_carry_over ?? 0);
                $initializedPto = (float) $settings->pto_default + $carryOver;
                $initializedWfh = (float) $settings->wfh_default;

                $totalCarryOver += $carryOver;
                $usersCount++;

                RequestCredit::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'pto' => $initializedPto,
                        'wfh' => $settings->wfh_default,
                        'approved_carry_over' => 0,
                    ]
                );

                $run->items()->create([
                    'user_id' => $user->id,
                    'employee_number' => $user->employee_number,
                    'employee_name' => $user->name,
                    'employee_email' => $user->email,
                    'previous_pto' => $previousPto,
                    'previous_wfh' => $previousWfh,
                    'pto_default' => $settings->pto_default,
                    'wfh_default' => $settings->wfh_default,
                    'approved_carry_over_applied' => $carryOver,
                    'initialized_pto' => $initializedPto,
                    'initialized_wfh' => $initializedWfh,
                ]);
            }

            $settings->update(['last_leave_replenished_on' => $runDate]);

            $run->update([
                'users_count' => $usersCount,
                'total_approved_carry_over' => $totalCarryOver,
            ]);
        });

        return redirect()->back()->with('flash', [
            'type' => 'success',
            'message' => 'Leave credits replenished for all users.',
        ]);
    }

    public function forceDestroy(StaffRequest $request)
    {
        $request->delete();
        return redirect()->route('requests.manage')->with('flash', [
            'type' => 'success',
            'message' => 'Request permanently deleted.',
        ]);
    }


    // Manage HR View

    public function manageHr(Request $request)
    {

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
        // HR can view all requests, no restriction by user/supervisor
        return view('requests.show-hr', [
            'request' => $requestModel,
        ]);
    }

    public function purgeCancelled()
    {
        $deletedCount = StaffRequest::where('status', 'cancelled')->delete();

        return back()->with('info', "{$deletedCount} records deleted.");
    }

    //Offset and File upload

    public function previewOffsetProof(StaffRequest $request)
    {
        $request->loadMissing('user');

        abort_unless($request->canViewOffsetProof(auth()->user()), 403);

        abort_unless($request->offset_proof_path, 404);

        $disk = Storage::disk('private_s3');

        abort_unless($disk->exists($request->offset_proof_path), 404);

        $stream = $disk->readStream($request->offset_proof_path);

        return response()->stream(
            fn() => fpassthru($stream),
            200,
            [
                'Content-Type' => $disk->mimeType($request->offset_proof_path)
                    ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . basename($request->offset_proof_path) . '"',
            ]
        );
    }

    protected function calendarDisplayName(?User $user): string
    {
        if (!$user) {
            return 'Staff member';
        }

        foreach (['preferred_name', 'full_name', 'name'] as $attribute) {
            $name = trim((string) data_get($user, $attribute, ''));

            if ($name !== '') {
                return $name;
            }
        }

        return 'Staff member';
    }

    protected function requestHrRecipients(): array
    {
        return array_values(array_filter([env('REQUESTS_HR_EMAIL')]));
    }

    protected function apiRowFor(StaffRequest $staffRequest): array
    {
        $user = $staffRequest->user;

        return [
            'id' => $staffRequest->id,
            'user_id' => $staffRequest->user_id,
            'employee_number' => $user?->employee_number,
            'employee_name' => $user?->name,
            'preferred_name' => $user?->preferred_name,
            'email' => $user?->email,
            'department' => $user?->department?->name,
            'department_id' => $user?->department_id,
            'type' => $staffRequest->type,
            'type_label' => $staffRequest->typeLabel(),
            'is_offset' => (bool) $staffRequest->is_offset,
            'reason' => $staffRequest->reason,
            'start_date' => $staffRequest->start_date ? (string) $staffRequest->start_date : null,
            'end_date' => $staffRequest->end_date ? (string) $staffRequest->end_date : null,
            'end_date_type' => $staffRequest->end_date_type,
            'number_of_days' => round((float) $staffRequest->number_of_days, 2),
            'status' => $staffRequest->status,
            'remarks' => $staffRequest->remarks,
            'approver' => $staffRequest->approver ? [
                'id' => $staffRequest->approver->id,
                'name' => $staffRequest->approver->name,
                'email' => $staffRequest->approver->email,
            ] : null,
            'current_credit_snapshot' => [
                'pto' => round((float) ($user?->requestCredit?->pto ?? 0), 2),
                'wfh' => round((float) ($user?->requestCredit?->wfh ?? 0), 2),
                'approved_carry_over' => round((float) ($user?->requestCredit?->approved_carry_over ?? 0), 2),
            ],
            'created_at' => optional($staffRequest->created_at)->toISOString(),
            'updated_at' => optional($staffRequest->updated_at)->toISOString(),
        ];
    }



}
