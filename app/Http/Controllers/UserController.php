<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
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
        $supervisors = User::where('rank', 'manager')->get();
        $departments = Department::orderBy('name')->get();
        return view('users.edit', compact('user', 'departments', 'supervisors'));

    }

public function update(Request $request, User $user)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'supervisor_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'roles' => 'nullable|string',
            'payroll_on' => 'nullable|boolean',
            'rank' => 'nullable|string|in:employee,manager',
            'position' => 'nullable|string',
            'monthly_rate' => 'nullable|string',
        ]);

        // Decode roles and ensure it's an array
        $roles = json_decode($validated['roles'], true) ?? [];

        // Ensure 'User' role is always present
        if (!in_array('user', $roles)) {
            $roles[] = 'user';
        }

        // Reassign roles back into the validated data
        $validated['roles'] = $roles;

        // Clean and format the monthly rate (optional but nice)
        $validated['monthly_rate'] = isset($validated['monthly_rate']) && is_numeric(str_replace(',', '', $validated['monthly_rate']))
            ? str_replace(',', '', $validated['monthly_rate'])
            : null;


        // Update the user with validated data
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'supervisor_id' => $validated['supervisor_id'],
            'department_id' => $validated['department_id'],
            'roles' => $validated['roles'],
            'payroll_on' => $validated['payroll_on'] ?? false,
            'rank' => $validated['rank'],
            'position' => $validated['position'],
            'monthly_rate' => $validated['monthly_rate'],
        ]);

        // Flash success and redirect
        return redirect()->route('users.index')->with('success', 'User updated successfully!');

    } catch (ValidationException $e) {
        // Flash error and redirect back with input
        return redirect()->back()
            ->withErrors($e->validator)
            ->withInput()
            ->with('error', 'Please correct the errors and try again.');
    }
}


}

