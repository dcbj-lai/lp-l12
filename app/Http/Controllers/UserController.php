<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\RequestCredit;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // User Index Page
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::query()
            ->when($search, fn($query) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('users.index', compact('users', 'search'));
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
            $roles = [];
            if (!empty($validated['roles'])) {
                $decoded = json_decode($validated['roles'], true);
                $roles = is_array($decoded) ? $decoded : [];
            }

            // Ensure default role
            if (!in_array('user', $roles)) {
                $roles[] = 'user';
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
                'roles' => $roles,
                'payroll_on' => $validated['payroll_on'] ?? false,
                'rank' => $validated['rank'] ?? 'employee',
                'position' => $validated['position'] ?? null,
                'monthly_rate' => $monthlyRate,
                'check_in_mode' => $validated['check_in_mode'],

                // Dates (safe null fallback)
                'birthdate' => $validated['birthdate'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
            ]);

            return redirect()
                ->route('users.index')
                ->with('success', 'User updated successfully!');

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please correct the errors and try again.');
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
            ->with('success', 'Leave credits updated successfully.');
    }

    public function delete(User $user)
    {
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} has been deleted.");
    }



}

