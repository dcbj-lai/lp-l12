<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RequestCredit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveCreditController extends Controller
{
    public function index(Request $request)
    {
        [$periodStart, $asOf] = $this->reportPeriod($request);
        $users = $this->usersWithCredits($request);
        $status = $this->userStatusFilter($request);

        return view('leave-credits.index', compact('users', 'periodStart', 'asOf', 'status'));
    }

    public function csv(Request $request): StreamedResponse
    {
        $users = $this->usersWithCredits($request);
        $fileName = $this->reportFileName('csv');

        return response()->stream(function () use ($users) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee number',
                'Employee name',
                'Starting leave credits',
                'Total leave days used to-date',
                'Leave balance to-date',
                'Compensatory time-off total',
            ]);

            foreach ($users as $user) {
                fputcsv($handle, $this->rowFor($user));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function pdf(Request $request)
    {
        [$periodStart, $asOf] = $this->reportPeriod($request);
        $users = $this->usersWithCredits($request);

        $pdf = Pdf::loadView('leave-credits.report-pdf', [
            'users' => $users,
            'periodStart' => $periodStart,
            'asOf' => $asOf,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->reportFileName('pdf'));
    }

    public function apiIndex(Request $request)
    {
        [$periodStart, $asOf] = $this->reportPeriod($request);
        $users = $this->usersWithCredits($request);

        $totals = [
            'employees' => $users->count(),
            'starting_leave_credits' => $users->sum(fn (User $user) => $this->startingLeaveCredits($user)),
            'leave_days_used' => $users->sum(fn (User $user) => $this->leaveDaysUsed($user)),
            'leave_balance' => $users->sum(fn (User $user) => $this->leaveBalance($user)),
            'compensatory_time_off' => $users->sum(fn (User $user) => $this->compensatoryTimeOff($user)),
        ];

        return response()->json([
            'period' => [
                'date_from' => $periodStart->toDateString(),
                'date_to' => $asOf->toDateString(),
            ],
            'totals' => $this->formattedTotals($totals),
            'data' => $users->map(fn (User $user) => $this->apiRowFor($user))->values(),
        ]);
    }

    public function apiUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'pto' => ['required_without:wfh', 'numeric', 'min:0'],
            'wfh' => ['required_without:pto', 'numeric', 'min:0'],
        ]);

        $credit = $this->updateCreditFor($user, $validated);

        return response()->json([
            'updated' => true,
            'user' => $this->creditPayload($user, $credit),
        ]);
    }

    public function apiBulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'dry_run' => ['nullable', 'boolean'],
            'credits' => ['required', 'array', 'min:1', 'max:1000'],
            'credits.*.user_id' => ['nullable', 'integer'],
            'credits.*.email' => ['nullable', 'email'],
            'credits.*.employee_number' => ['nullable'],
            'credits.*.pto' => ['nullable', 'numeric', 'min:0'],
            'credits.*.wfh' => ['nullable', 'numeric', 'min:0'],
        ]);

        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $results = [];
        $updated = 0;

        DB::transaction(function () use ($validated, $dryRun, &$results, &$updated) {
            foreach ($validated['credits'] as $index => $row) {
                $user = $this->findUserForCreditRow($row);

                if (! $user) {
                    $results[] = [
                        'row' => $index,
                        'status' => 'skipped',
                        'reason' => 'No matching user found.',
                    ];

                    continue;
                }

                $creditValues = collect($row)
                    ->only(['pto', 'wfh'])
                    ->reject(fn ($value) => $value === null)
                    ->all();

                if ($creditValues === []) {
                    $results[] = [
                        'row' => $index,
                        'user_id' => $user->id,
                        'status' => 'skipped',
                        'reason' => 'No credit values provided.',
                    ];

                    continue;
                }

                if (! $dryRun) {
                    $this->updateCreditFor($user, $creditValues);
                    $updated++;
                }

                $results[] = [
                    'row' => $index,
                    'user_id' => $user->id,
                    'employee_number' => $user->employee_number,
                    'email' => $user->email,
                    'status' => $dryRun ? 'matched' : 'updated',
                    'pto' => array_key_exists('pto', $creditValues) ? (float) $creditValues['pto'] : null,
                    'wfh' => array_key_exists('wfh', $creditValues) ? (float) $creditValues['wfh'] : null,
                ];
            }
        });

        return response()->json([
            'dry_run' => $dryRun,
            'received' => count($validated['credits']),
            'updated' => $updated,
            'results' => $results,
        ]);
    }

    protected function usersWithCredits(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $normalizedSearch = mb_strtolower($search);
        $status = $this->userStatusFilter($request);
        [$periodStart, $asOf] = $this->reportPeriod($request);

        return User::query()
            ->with(['department', 'requestCredit'])
            ->withSum(['requests as leave_days_used_to_date' => function ($query) use ($periodStart, $asOf) {
                $query->where('status', 'approved')
                    ->where('type', 'PTO')
                    ->where('is_offset', false)
                    ->whereBetween('start_date', [$periodStart->toDateString(), $asOf->toDateString()]);
            }], 'number_of_days')
            ->withSum(['requests as compensatory_time_off_total' => function ($query) use ($periodStart, $asOf) {
                $query->where('status', 'approved')
                    ->where('is_offset', true)
                    ->whereBetween('start_date', [$periodStart->toDateString(), $asOf->toDateString()]);
            }], 'number_of_days')
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($search !== '', function ($query) use ($normalizedSearch) {
                $query->where(function ($query) use ($normalizedSearch) {
                    $like = "%{$normalizedSearch}%";

                    $query->whereRaw('LOWER(employee_number) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(preferred_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(position) LIKE ?', [$like])
                        ->orWhereHas('department', fn ($department) => $department->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->orderBy('name')
            ->get();
    }

    protected function rowFor(User $user): array
    {
        return [
            $user->employee_number ?? '-',
            $user->name,
            $this->formatCredit($this->startingLeaveCredits($user)),
            $this->formatCredit($this->leaveDaysUsed($user)),
            $this->formatCredit($this->leaveBalance($user)),
            $this->formatCredit($this->compensatoryTimeOff($user)),
        ];
    }

    protected function apiRowFor(User $user): array
    {
        return [
            'user_id' => $user->id,
            'employee_number' => $user->employee_number,
            'employee_name' => $user->name,
            'preferred_name' => $user->preferred_name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'starting_leave_credits' => $this->roundCredit($this->startingLeaveCredits($user)),
            'total_leave_days_used_to_date' => $this->roundCredit($this->leaveDaysUsed($user)),
            'leave_balance_to_date' => $this->roundCredit($this->leaveBalance($user)),
            'compensatory_time_off_total' => $this->roundCredit($this->compensatoryTimeOff($user)),
        ];
    }

    protected function formattedTotals(array $totals): array
    {
        return [
            'employees' => $totals['employees'],
            'starting_leave_credits' => $this->roundCredit($totals['starting_leave_credits']),
            'leave_days_used' => $this->roundCredit($totals['leave_days_used']),
            'leave_balance' => $this->roundCredit($totals['leave_balance']),
            'compensatory_time_off' => $this->roundCredit($totals['compensatory_time_off']),
        ];
    }

    protected function reportPeriod(Request $request): array
    {
        $asOf = $request->filled('date_to')
            ? CarbonImmutable::parse($request->query('date_to'))->endOfDay()
            : ($request->filled('as_of')
                ? CarbonImmutable::parse($request->query('as_of'))->endOfDay()
                : CarbonImmutable::today()->endOfDay());

        $periodStart = $request->filled('date_from')
            ? CarbonImmutable::parse($request->query('date_from'))->startOfDay()
            : ($request->filled('period_start')
                ? CarbonImmutable::parse($request->query('period_start'))->startOfDay()
                : $this->defaultPeriodStart($asOf));

        if ($periodStart->gt($asOf)) {
            $periodStart = $asOf->startOfDay();
        }

        return [$periodStart, $asOf];
    }

    protected function userStatusFilter(Request $request): string
    {
        $status = (string) $request->query('status', 'active');

        return in_array($status, ['active', 'inactive', 'all'], true) ? $status : 'active';
    }

    protected function defaultPeriodStart(CarbonImmutable $asOf): CarbonImmutable
    {
        $firstAnnualCycle = CarbonImmutable::create(2026, 7, 1)->startOfDay();

        if ($asOf->lt($firstAnnualCycle)) {
            return CarbonImmutable::create(2025, 10, 1)->startOfDay();
        }

        $year = $asOf->month >= 7 ? $asOf->year : $asOf->year - 1;

        return CarbonImmutable::create($year, 7, 1)->startOfDay();
    }

    protected function startingLeaveCredits(User $user): float
    {
        return $this->leaveBalance($user) + $this->leaveDaysUsed($user);
    }

    protected function leaveDaysUsed(User $user): float
    {
        return (float) ($user->leave_days_used_to_date ?? 0);
    }

    protected function leaveBalance(User $user): float
    {
        return (float) ($user->requestCredit?->pto ?? 0);
    }

    protected function compensatoryTimeOff(User $user): float
    {
        return (float) ($user->compensatory_time_off_total ?? 0);
    }

    protected function updateCreditFor(User $user, array $values): RequestCredit
    {
        $credit = $user->requestCredit()->firstOrCreate(['user_id' => $user->id]);

        if (array_key_exists('pto', $values)) {
            $credit->pto = $values['pto'];
        }

        if (array_key_exists('wfh', $values)) {
            $credit->wfh = $values['wfh'];
        }

        $credit->save();

        return $credit;
    }

    protected function findUserForCreditRow(array $row): ?User
    {
        return match (true) {
            ! empty($row['user_id']) => User::find($row['user_id']),
            ! empty($row['email']) => User::where('email', $row['email'])->first(),
            ! empty($row['employee_number']) => User::where('employee_number', trim((string) $row['employee_number']))->first(),
            default => null,
        };
    }

    protected function creditPayload(User $user, RequestCredit $credit): array
    {
        return [
            'id' => $user->id,
            'employee_number' => $user->employee_number,
            'name' => $user->name,
            'email' => $user->email,
            'pto' => $this->roundCredit($credit->pto),
            'wfh' => $this->roundCredit($credit->wfh),
        ];
    }

    protected function roundCredit(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    protected function formatCredit(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2);
    }

    protected function reportFileName(string $extension): string
    {
        return 'leave_credits_' . Str::slug(now()->format('Y-m-d_H-i-s')) . '.' . $extension;
    }
}
