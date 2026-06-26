<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
// use App\Models\RequestCredit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    // User Index Page
    public function index(Request $request)
    {
        $search = trim($request->input('search'));

        $users = $this->filteredUsersQuery($request)
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $users = $this->filteredUsersQuery($request)->get();
        $fileName = $this->exportFileName('csv');

        return response()->stream(function () use ($users) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Email',
                'Preferred Name',
                'Vcard URL',
            ]);

            foreach ($users as $user) {
                fputcsv($handle, $this->exportRowFor($user));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $users = $this->filteredUsersQuery($request)->get();

        $pdf = Pdf::loadView('users.export-pdf', [
            'users' => $users,
            'generatedAt' => now(),
            'search' => trim((string) $request->input('search', '')),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->exportFileName('pdf'));
    }

    public function apiIndex(Request $request)
    {
        $users = $this->filteredUsersQuery($request)
            ->with('department:id,name')
            ->limit(max(1, min((int) $request->integer('limit', 500), 1000)))
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'employee_number' => $user->employee_number,
                'name' => $user->name,
                'preferred_name' => $user->preferred_name,
                'email' => $user->email,
                'department' => $user->department?->name,
                'position' => $user->position,
                'rank' => $user->rank,
            ]);

        return response()->json([
            'data' => $users,
            'count' => $users->count(),
        ]);
    }

    public function apiBackfillEmployeeNumbers(Request $request)
    {
        $validated = $request->validate([
            'dry_run' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
            'employees' => ['required', 'array', 'min:1', 'max:2000'],
            'employees.*.user_id' => ['nullable', 'integer'],
            'employees.*.email' => ['nullable', 'email'],
            'employees.*.name' => ['nullable', 'string', 'max:255'],
            'employees.*.employee_number' => ['required'],
        ]);

        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $force = (bool) ($validated['force'] ?? false);
        $users = User::query()->get(['id', 'employee_number', 'name', 'preferred_name', 'email']);
        $results = [];
        $updated = 0;

        DB::transaction(function () use ($validated, $dryRun, $force, $users, &$results, &$updated) {
            foreach ($validated['employees'] as $index => $row) {
                $match = $this->findUserForEmployeeNumberRow($row, $users);

                if ($match['status'] !== 'matched') {
                    $results[] = [
                        'row' => $index,
                        'employee_number' => $row['employee_number'],
                        'status' => $match['status'],
                        'reason' => $match['reason'],
                    ];

                    continue;
                }

                $user = $match['user'];
                $employeeNumber = trim((string) $row['employee_number']);
                $numberOwner = $users->first(fn (User $candidate) => $candidate->employee_number === $employeeNumber && $candidate->id !== $user->id);

                if ($numberOwner) {
                    $results[] = [
                        'row' => $index,
                        'user_id' => $user->id,
                        'employee_number' => $employeeNumber,
                        'status' => 'skipped',
                        'reason' => "Employee number already belongs to {$numberOwner->name}.",
                    ];

                    continue;
                }

                if ($user->employee_number && $user->employee_number !== $employeeNumber && ! $force) {
                    $results[] = [
                        'row' => $index,
                        'user_id' => $user->id,
                        'employee_number' => $employeeNumber,
                        'status' => 'skipped',
                        'reason' => "User already has employee number {$user->employee_number}.",
                    ];

                    continue;
                }

                $status = $user->employee_number === $employeeNumber ? 'unchanged' : ($dryRun ? 'matched' : 'updated');

                if (! $dryRun && $user->employee_number !== $employeeNumber) {
                    $user->employee_number = $employeeNumber;
                    $user->save();
                    $updated++;
                }

                $results[] = [
                    'row' => $index,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_number' => $employeeNumber,
                    'status' => $status,
                ];
            }
        });

        return response()->json([
            'dry_run' => $dryRun,
            'force' => $force,
            'received' => count($validated['employees']),
            'updated' => $updated,
            'results' => $results,
        ]);
    }

    protected function filteredUsersQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return User::query()
            ->when($search !== '', function ($query) use ($search) {
                $search = mb_strtolower($search);

                $query->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(employee_number) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(preferred_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderBy('name');
    }

    protected function findUserForEmployeeNumberRow(array $row, Collection $users): array
    {
        if (! empty($row['user_id'])) {
            $user = $users->firstWhere('id', (int) $row['user_id']);

            return $user
                ? ['status' => 'matched', 'user' => $user]
                : ['status' => 'skipped', 'reason' => 'No user matched user_id.'];
        }

        if (! empty($row['email'])) {
            $email = Str::lower(trim((string) $row['email']));
            $user = $users->first(fn (User $candidate) => Str::lower($candidate->email) === $email);

            return $user
                ? ['status' => 'matched', 'user' => $user]
                : ['status' => 'skipped', 'reason' => 'No user matched email.'];
        }

        if (! empty($row['name'])) {
            $matches = $users->filter(fn (User $candidate) => $this->candidateMatchesEmployeeName($candidate, (string) $row['name']))->values();

            if ($matches->count() === 1) {
                return ['status' => 'matched', 'user' => $matches->first()];
            }

            return [
                'status' => $matches->count() > 1 ? 'ambiguous' : 'skipped',
                'reason' => $matches->count() > 1 ? 'Multiple users matched name.' : 'No user matched name.',
            ];
        }

        return ['status' => 'skipped', 'reason' => 'Provide user_id, email, or name.'];
    }

    protected function candidateMatchesEmployeeName(User $user, string $employeeName): bool
    {
        $rowVariants = $this->employeeNameVariants($employeeName);
        $userVariants = array_filter([
            $this->normalizeName($user->name),
            $this->normalizeName((string) $user->preferred_name),
        ]);

        if (array_intersect($rowVariants, $userVariants) !== []) {
            return true;
        }

        $parsed = $this->parseEmployeeName($employeeName);
        $tokens = collect(explode(' ', $this->normalizeName($user->name)))->filter()->values();
        $userLast = $tokens->last();
        $userFirstInitial = Str::substr($tokens->first() ?? '', 0, 1);

        return $parsed['last'] !== ''
            && $parsed['first'] !== ''
            && $userLast === $parsed['last']
            && $userFirstInitial === Str::substr($parsed['first'], 0, 1);
    }

    protected function employeeNameVariants(string $employeeName): array
    {
        $parsed = $this->parseEmployeeName($employeeName);

        return array_values(array_unique(array_filter([
            $this->normalizeName($employeeName),
            trim($parsed['first'] . ' ' . $parsed['last']),
            trim($parsed['first'] . ' ' . $parsed['middle'] . ' ' . $parsed['last']),
            trim($parsed['last'] . ' ' . $parsed['first']),
        ])));
    }

    protected function parseEmployeeName(string $employeeName): array
    {
        $parts = array_map(fn (string $part) => $this->normalizeName($part), explode(',', $employeeName));
        $last = $this->stripNameSuffix($parts[0] ?? '');
        $first = $parts[1] ?? '';
        $middle = $parts[2] ?? '';

        if ($first === '') {
            $tokens = collect(explode(' ', $this->normalizeName($employeeName)))->filter()->values();
            $first = $tokens->first() ?? '';
            $last = $tokens->last() ?? $last;
        }

        return [
            'first' => $first,
            'middle' => $middle,
            'last' => $last,
        ];
    }

    protected function stripNameSuffix(string $value): string
    {
        return trim(preg_replace('/\b(JR|SR|II|III|IV)\b/', '', $value));
    }

    protected function normalizeName(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z0-9]+/', ' ', Str::upper(Str::ascii($value)))));
    }

    protected function exportRowFor(User $user): array
    {
        return [
            $user->name,
            $user->email,
            $user->preferred_name ?? '-',
            $user->cardUrl(),
        ];
    }

    protected function exportFileName(string $extension): string
    {
        return 'users_' . Str::slug(now()->format('Y-m-d_H-i-s')) . '.' . $extension;
    }

    // Edit User Page (placeholder for now)
    public function edit(User $user)
    {
        $user->load('requestCredit');
        // dd($user);
        $supervisors = User::where('rank', 'manager')->get();
        $departments = Department::orderBy('name')->get();
        return view('users.edit', compact('user', 'departments', 'supervisors'));

    }

    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'employee_number' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('users', 'employee_number')->ignore($user->id),
                ],
                'name' => ['required', 'string', 'max:255'],
                'preferred_name' => ['nullable', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'supervisor_id' => ['nullable', 'exists:users,id'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'roles' => ['nullable', 'string'],
                'payroll_on' => ['nullable', 'boolean'],
                'rank' => ['nullable', 'string', 'in:employee,manager'],
                'position' => ['nullable', 'string'],
                'monthly_rate' => ['nullable', 'string'],
                'check_in_mode' => ['required', 'string', 'in:virtual,onsite'],
                'phone_work' => ['nullable', 'string', 'max:20'],
                'phone_mobile' => ['nullable', 'string', 'max:20'],
                'address' => ['nullable', 'string', 'max:255'], // ✅ added validation for address

                // Event-related profile fields (normalized for event registration)
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
                'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
                'dietary_preference' => ['nullable', 'string', 'max:255'],
                'medical_notes' => ['nullable', 'string', 'max:2000'],

                // 🔒 Hardened date fields
                'birthdate' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                ],
                'hire_date' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                    'after:1900-01-01',
                ],
            ]);


            // ✅ Safe roles decoding
            $legacy_roles = [];
            if (!empty($validated['roles'])) {
                $decoded = json_decode($validated['roles'], true);
                $legacy_roles = is_array($decoded) ? $decoded : [];
            }
            // dd($legacy_roles);
            // Ensure default role
            if (!in_array('user', $legacy_roles)) {
                $legacy_roles[] = 'user';
            }

            // ✅ Clean monthly rate safely
            $monthlyRate = null;
            if (!empty($validated['monthly_rate'])) {
                $clean = str_replace(',', '', $validated['monthly_rate']);
                if (is_numeric($clean)) {
                    $monthlyRate = $clean;
                }
            }

            // ✅ Optional logical guard (only if both exist)
            if (!empty($validated['birthdate']) && !empty($validated['hire_date'])) {
                if ($validated['hire_date'] < $validated['birthdate']) {
                    return back()
                        ->withErrors(['hire_date' => 'Hire date must be after birthdate.'])
                        ->withInput()
                        ->with('error', 'Invalid date relationship.');
                }
            }

            // ✅ Update safely
            $user->update([
                'employee_number' => $validated['employee_number'] ?? null,
                'name' => $validated['name'],
                'preferred_name' => $validated['preferred_name'] ?? null,
                'email' => $validated['email'],
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'legacy_roles' => $legacy_roles,
                'payroll_on' => $validated['payroll_on'] ?? false,
                'rank' => $validated['rank'] ?? 'employee',
                'position' => $validated['position'] ?? null,
                'monthly_rate' => $monthlyRate,
                'check_in_mode' => $validated['check_in_mode'],
                'phone_work' => $validated['phone_work'] ?? null,
                'phone_mobile' => $validated['phone_mobile'] ?? null,
                'address' => $validated['address'] ?? null, // ✅ added address update

                // Event-related profile fields
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'dietary_preference' => $validated['dietary_preference'] ?? null,
                'medical_notes' => $validated['medical_notes'] ?? null,

                // Dates (safe null fallback)
                'birthdate' => $validated['birthdate'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
            ]);

            return redirect()->back()->with('flash', [
                'type' => 'success',
                'message' => 'User updated successfully!',
            ]);

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Please correct the errors and try again.',
                ]);
        }
    }

    public function updateLeaveCredits(Request $request, User $user)
    {
        $validated = $request->validate([
            'pto' => ['required', 'numeric', 'min:0'],
            'wfh' => ['required', 'numeric', 'min:0'],
        ]);

        // Ensure the user has a related requestCredits record
        $credits = $user->requestCredit()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        // Update the fields
        $credits->update([
            'pto' => $validated['pto'],
            'wfh' => $validated['wfh'],
        ]);

        return redirect()
            ->route('users.edit', $user->id)
            ->with('flash', [
                'type' => 'success',
                'message' => 'Leave credits updated successfully.',
            ]);
    }

    public function delete(User $user)
    {
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('flash', [
                'type' => 'success',
                'message' => "User {$user->name} has been deleted.",
            ]);
    }



}
