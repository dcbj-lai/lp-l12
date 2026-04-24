<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $studentQuery = Patient::query()
            ->where('type', 'student');

        if ($request->filled('student_q')) {
            $q = $request->student_q;

            $studentQuery->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $staffQuery = Patient::query()
            ->where('type', 'staff');

        if ($request->filled('staff_q')) {
            $q = $request->staff_q;

            $staffQuery->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $students = $studentQuery
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10, ['*'], 'students_page');

        $staff = $staffQuery
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10, ['*'], 'staff_page');

        return view('clinic.patients.index', compact('students', 'staff'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Patient $patient)
    {
        $consultations = $patient->clinicConsultations()
            ->whereNotNull('time_out')
            ->orderByDesc('created_at')
            ->paginate(5);

        return view('clinic.patients.show', compact('patient', 'consultations'));
    }

    public function edit(Patient $patient)
    {
        return view('clinic.patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255', 'unique:patients,email,' . $patient->id],
            'type'       => ['required', 'in:student,staff'],

            'course'     => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position'   => ['nullable', 'string', 'max:255'],

            'blood_type' => ['nullable', 'string', 'max:10'],
            'emergency_contact_person' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:50'],
        ]);

        // enforce type rules
        if ($data['type'] === 'student') {
            $data['department'] = null;
            $data['position'] = null;
        } else {
            $data['course'] = null;
        }

        $patient->update($data);

        return redirect()
            ->route('clinic.patients.show', $patient)
            ->with('flash', [
                'type' => 'success',
                'message' => 'Patient profile updated.',
            ]);
    }

    public function destroy(Patient $patient)
    {
        //
    }
}