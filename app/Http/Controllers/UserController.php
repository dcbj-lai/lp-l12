<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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

    protected function filteredUsersQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return User::query()
            ->when($search !== '', function ($query) use ($search) {
                $search = mb_strtolower($search);

                $query->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(preferred_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderBy('name');
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
