<?php

namespace App\Http\Controllers;

use App\Models\PreEnrollmentMedicalClearance;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreEnrollmentMedicalClearanceController extends Controller
{
    public function index(Request $request)
    {
        $query = PreEnrollmentMedicalClearance::query()
            ->with('issuedBy:id,name');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($query) use ($q) {
                $query->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('intended_course', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('clearance_status', $request->status);
        }

        $clearances = $query
            ->orderByDesc('assessment_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('clinic.pre-enrollment-clearances.index', [
            'clearances' => $clearances,
            'statusOptions' => PreEnrollmentMedicalClearance::statusOptions(),
        ]);
    }

    public function create()
    {
        $studentPatients = Patient::query()
            ->where('type', 'student')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'course', 'emergency_contact_number'])
            ->map(function (Patient $patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->full_name,
                    'email' => $patient->email ?? '',
                    'course' => $patient->course ?? '',
                    'contact_number' => $patient->emergency_contact_number ?? '',
                    'details' => implode(' - ', array_filter([
                        $patient->email,
                        $patient->course,
                    ])),
                ];
            })
            ->values();

        return view('clinic.pre-enrollment-clearances.create', [
            'statusOptions' => PreEnrollmentMedicalClearance::statusOptions(),
            'defaultStatus' => PreEnrollmentMedicalClearance::STATUS_CLEARED,
            'defaultAssessmentDate' => now()->toDateString(),
            'studentPatients' => $studentPatients,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'applicant_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'intended_course' => ['nullable', 'string', 'max:255'],
            'assessment_date' => ['required', 'date'],
            'clearance_status' => ['required', Rule::in(array_keys(PreEnrollmentMedicalClearance::statusOptions()))],
            'findings' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $clearance = PreEnrollmentMedicalClearance::create(array_merge($data, [
            'issued_by_id' => $user->id,
            'issued_by_name' => $user->name,
            'issued_at' => now(),
        ]));

        return redirect()
            ->route('clinic.pre-enrollment-clearances.show', $clearance)
            ->with('flash', [
                'type' => 'success',
                'message' => 'Pre-enrollment medical clearance created.',
            ]);
    }

    public function show(PreEnrollmentMedicalClearance $clearance)
    {
        return view('clinic.pre-enrollment-clearances.show', compact('clearance'));
    }

    public function pdf(PreEnrollmentMedicalClearance $clearance)
    {
        $pdf = Pdf::loadView('clinic.pre-enrollment-clearances.pdf', [
            'clearance' => $clearance,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pre_enrollment_medical_clearance_' . $clearance->id . '.pdf');
    }
}
