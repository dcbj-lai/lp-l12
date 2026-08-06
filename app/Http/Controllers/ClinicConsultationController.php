<?php

namespace App\Http\Controllers;

use App\Jobs\SendClinicConsultationEmail;
use App\Models\ClinicConsultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ClinicConsultationController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $query = ClinicConsultation::query()
            ->with(['patient:id,first_name,last_name,email,type'])
            ->orderBy('id', 'desc');

        if (!empty($dateFrom)) {
            $query->whereDate('time_in', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('time_in', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->whereHas('patient', function ($p) use ($q) {
                $p->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$q}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$q}%"]);
            });
        }

        $consultations = $query
            ->paginate(10)
            ->appends($request->only(['q', 'date_from', 'date_to']));

        return view('clinic.consultations.index', compact('consultations', 'q', 'dateFrom', 'dateTo'));
    }

    public function create(Patient $patient)
    {
        $teachers = User::activeFaculty()
            ->select('name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->toArray();

        return view('clinic.consultations.create', compact('patient', 'teachers'));
    }

    public function store(Request $request, Patient $patient)
    {
        if ($request->input('action') === 'cancel') {
            $consultation = ClinicConsultation::where('patient_id', $patient->id)
                ->whereNull('time_out')
                ->latest()
                ->first();

            if ($consultation) {
                $consultation->delete();
            }

            return redirect()
                ->route('clinic.patients.show', [
                    'patient' => $patient->id,
                    'tab' => $patient->type === 'staff' ? 'staff' : 'students',
                ])
                ->with('flash', [
                    'type' => 'info',
                    'message' => 'Consultation cancelled.',
                ]);
        }

        $rules = [
            'consultation_id' => ['required', 'integer', 'exists:clinic_consultations,id'],

            'case_classification' => ['nullable', 'string'],

            'blood_pressure'   => ['nullable', 'string', 'max:20'],
            'pulse_rate'       => ['nullable', 'integer', 'min:0'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0'],
            'temperature'      => ['nullable', 'numeric'],
            'o2_saturation'    => ['nullable', 'integer', 'min:0', 'max:100'],

            'pain_rating'      => ['nullable', 'integer', 'min:0', 'max:10'],

            'chief_complaint'  => ['nullable', 'string'],
            'assessment'       => ['nullable', 'string'],
            'treatment'        => ['nullable', 'string'],

            'medicines' => ['nullable', 'array'],
            'medicines.*.name' => ['nullable', 'string', 'max:255'],
            'medicines.*.qty'  => ['nullable', 'integer', 'min:1'],
            'medicines.*.label' => ['nullable', 'string', 'max:120'],

            'supplies' => ['nullable', 'array'],
            'supplies.*.name' => ['nullable', 'string', 'max:255'],
            'supplies.*.qty'  => ['nullable', 'integer', 'min:1'],

            'remarks' => ['nullable', 'string'],

            'photo_attachments'   => ['nullable', 'array'],
            'photo_attachments.*' => ['file', 'image', 'max:5120'],
        ];

        if ($patient->type === 'student') {
            $rules['current_teacher']    = ['nullable', 'string'];
            $rules['teacher_email']      = ['nullable', 'email'];

            $rules['after_consultation'] = ['required', 'in:resume,go_home'];
            $rules['going_home_method']  = ['nullable', 'in:fetcher,self'];
            $rules['fetcher_name']       = ['nullable', 'string'];
            $rules['self_approved_by']   = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        if ($patient->type === 'student' && ($data['after_consultation'] ?? null) === 'go_home') {
            if (empty($data['going_home_method'])) {
                return back()->withErrors(['going_home_method' => 'Going home method is required.'])->withInput();
            }

            if (($data['going_home_method'] ?? null) === 'fetcher' && empty(trim($data['fetcher_name'] ?? ''))) {
                return back()->withErrors(['fetcher_name' => 'Fetcher name is required.'])->withInput();
            }

            if (($data['going_home_method'] ?? null) === 'self' && empty(trim($data['self_approved_by'] ?? ''))) {
                return back()->withErrors(['self_approved_by' => 'Approved by is required.'])->withInput();
            }
        }

       $medicines = collect($data['medicines'] ?? [])
            ->filter(function ($item) {
                $name = trim((string) ($item['name'] ?? ''));
                $qty  = $item['qty'] ?? null;

                return $name !== '' && $qty !== null && $qty !== '';
            })
            ->map(function ($item) {
                $label = trim((string) ($item['label'] ?? ''));

                return [
                    'name'  => trim((string) $item['name']),
                    'qty'   => (int) $item['qty'],
                    'label' => $label !== '' ? $label : null,
                ];
            })
            ->values()
            ->all();

        $supplies = collect($data['supplies'] ?? [])
            ->filter(function ($item) {
                $name = trim((string) ($item['name'] ?? ''));
                $qty  = $item['qty'] ?? null;

                return $name !== '' && $qty !== null && $qty !== '';
            })
            ->map(function ($item) {
                 $label = trim((string) ($item['label'] ?? ''));

                return [
                    'name' => trim((string) $item['name']),
                    'qty'  => (int) $item['qty'],
                    'label' => $label !== '' ? $label : null,
                ];
            })
            ->values()
            ->all();

        $consultation = ClinicConsultation::where('id', $data['consultation_id'])
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $photoPaths = [];
        $baseFolder = $patient->photoAttachmentsFolder();

        if ($request->hasFile('photo_attachments')) {
            foreach ((array) $request->file('photo_attachments') as $file) {
                if (! $file) {
                    continue;
                }

                $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
                $ext  = strtolower($file->getClientOriginalExtension());
                $name = $base . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $ext;

                $photoPaths[] = $file->storeAs(
                    $baseFolder,
                    $name,
                    'private_s3'
                );
            }
        }

        $existing = is_array($consultation->photo_attachments) ? $consultation->photo_attachments : [];
        $finalPhotos = !empty($photoPaths)
            ? array_values(array_merge($existing, $photoPaths))
            : $existing;

        $consultation->update([
            'case_classification' => $data['case_classification'] ?? null,

            'blood_pressure'   => $data['blood_pressure'] ?? null,
            'pulse_rate'       => $data['pulse_rate'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'temperature'      => $data['temperature'] ?? null,
            'o2_saturation'    => $data['o2_saturation'] ?? null,

            'pain_rating'      => $data['pain_rating'] ?? null,

            'chief_complaint'  => $data['chief_complaint'] ?? null,
            'assessment'       => $data['assessment'] ?? null,
            'treatment'        => $data['treatment'] ?? null,

            'medicines'        => !empty($medicines) ? $medicines : null,
            'supplies'         => !empty($supplies) ? $supplies : null,

            'remarks'          => $data['remarks'] ?? null,

            'photo_attachments' => !empty($finalPhotos) ? $finalPhotos : null,

            'time_out' => now(),

            'current_teacher' => $patient->type === 'student' ? ($data['current_teacher'] ?? null) : null,
            'teacher_email'   => $patient->type === 'student' ? ($data['teacher_email'] ?? null) : null,

            'after_consultation' => $patient->type === 'student' ? ($data['after_consultation'] ?? null) : null,
            'going_home_method'  => $patient->type === 'student' ? ($data['going_home_method'] ?? null) : null,
            'fetcher_name'       => $patient->type === 'student' ? ($data['fetcher_name'] ?? null) : null,
            'self_approved_by'   => $patient->type === 'student' ? ($data['self_approved_by'] ?? null) : null,
        ]);

        $consultation = $consultation->fresh();

        $emailQueued = $this->queueConsultationEmail($consultation);
        $submissionMessage = $emailQueued
            ? 'Consultation submitted. Email notification queued.'
            : 'Consultation submitted.';

        $returnUrl = $request->input('return_url');

        if (
            is_string($returnUrl) &&
            filter_var($returnUrl, FILTER_VALIDATE_URL) &&
            str_starts_with($returnUrl, config('app.url'))
        ) {
            return redirect()
                ->to($returnUrl)
                ->with('flash', [
                    'type' => 'success',
                    'message' => $submissionMessage,
                ]);
        }

        return redirect()
            ->route('clinic.patients.show', [
                'patient' => $patient->id,
                'tab' => $patient->type === 'staff' ? 'staff' : 'students',
            ])
            ->with('flash', [
                'type' => 'success',
                'message' => $submissionMessage,
            ]);
    }

    public function retryEmail(ClinicConsultation $consultation)
    {
        $consultation->load('patient');

        if (
            ! $consultation->patient ||
            $consultation->patient->type !== 'student' ||
            ! in_array($consultation->after_consultation, ['resume', 'go_home'], true)
        ) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'This consultation does not have an email notification to retry.',
            ]);
        }

        if (! $this->queueConsultationEmail($consultation)) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Email notification could not be queued. Please check the logs.',
            ]);
        }

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Email notification queued for retry.',
        ]);
    }

    private function queueConsultationEmail(ClinicConsultation $consultation): bool
    {
        $consultation->loadMissing('patient');

        if (
            ! $consultation->patient ||
            $consultation->patient->type !== 'student' ||
            ! in_array($consultation->after_consultation, ['resume', 'go_home'], true)
        ) {
            return false;
        }

        $teacherRecipients = array_filter([
            $consultation->check_in_teacher_email,
            $consultation->teacher_email,
        ], fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false);

        if ($teacherRecipients === []) {
            $consultation->update([
                'email_status' => null,
                'email_sent_at' => null,
                'email_failed_at' => null,
                'email_failure_message' => null,
            ]);

            return false;
        }

        $consultation->update([
            'email_status' => ClinicConsultation::EMAIL_STATUS_QUEUED,
            'email_sent_at' => null,
            'email_failed_at' => null,
            'email_failure_message' => null,
        ]);

        try {
            SendClinicConsultationEmail::dispatch($consultation->id);

            return true;
        } catch (Throwable $exception) {
            $consultation->update([
                'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
                'email_failed_at' => now(),
                'email_failure_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            report($exception);

            return false;
        }
    }

    public function show(ClinicConsultation $consultation)
    {
        $consultation->load('patient');

        $returnUrl = request('return_url') ?: route('clinic.consultations.index');

        return view('clinic.consultations.show', compact('consultation', 'returnUrl'));
    }

    public function edit(ClinicConsultation $consultation)
    {
        $consultation->load('patient');

        $returnUrl = request('return_url') ?: route('clinic.consultations.index');

        return view('clinic.consultations.edit', compact('consultation', 'returnUrl'));
    }

    public function update(Request $request, ClinicConsultation $consultation)
{
    $patient = $consultation->patient;

    $data = $request->validate([
        'case_classification' => ['nullable', 'string'],

        'blood_pressure'   => ['nullable', 'string', 'max:20'],
        'pulse_rate'       => ['nullable', 'integer', 'min:0'],
        'respiratory_rate' => ['nullable', 'integer', 'min:0'],
        'temperature'      => ['nullable', 'numeric'],
        'o2_saturation'    => ['nullable', 'integer', 'min:0', 'max:100'],
        'pain_rating'      => ['nullable', 'integer', 'min:0', 'max:10'],

        'chief_complaint'  => ['nullable', 'string'],
        'assessment'       => ['nullable', 'string'],
        'treatment'        => ['nullable', 'string'],

        'medicines' => ['nullable', 'array'],
        'medicines.*.name' => ['nullable', 'string', 'max:255'],
        'medicines.*.qty'  => ['nullable', 'integer', 'min:1'],
        'medicines.*.label' => ['nullable', 'string', 'max:120'],

        'supplies' => ['nullable', 'array'],
        'supplies.*.name' => ['nullable', 'string', 'max:255'],
        'supplies.*.qty'  => ['nullable', 'integer', 'min:1'],

        'remarks' => ['nullable', 'string'],

        'photo_attachments'        => ['nullable', 'array'],
        'photo_attachments.*'      => ['file', 'image', 'max:5120'],

        'remove_existing_photos'   => ['nullable', 'array'],
        'remove_existing_photos.*' => ['string'],

        'return_url' => ['nullable', 'string'],
    ]);

    $medicines = collect($data['medicines'] ?? [])
        ->filter(function ($item) {
            $name = trim((string) ($item['name'] ?? ''));
            $qty  = $item['qty'] ?? null;

            return $name !== '' && $qty !== null && $qty !== '';
        })
        ->map(function ($item) {
            $label = trim((string) ($item['label'] ?? ''));

            return [
                'name'  => trim((string) $item['name']),
                'qty'   => (int) $item['qty'],
                'label' => $label !== '' ? $label : null,
            ];
        })
        ->values()
        ->all();

    $supplies = collect($data['supplies'] ?? [])
        ->filter(function ($item) {
            $name = trim((string) ($item['name'] ?? ''));
            $qty  = $item['qty'] ?? null;

            return $name !== '' && $qty !== null && $qty !== '';
        })
        ->map(function ($item) {
            return [
                'name' => trim((string) $item['name']),
                'qty'  => (int) $item['qty'],
            ];
        })
        ->values()
        ->all();

    /*
    |--------------------------------------------------------------------------
    | Photo attachments
    |--------------------------------------------------------------------------
    | Existing photos:
    | - Only delete photos checked under remove_existing_photos[].
    |
    | New photos:
    | - Add them to the remaining existing photos.
    | - Do not replace all existing photos.
    */

    $existingPhotos = is_array($consultation->photo_attachments)
        ? $consultation->photo_attachments
        : [];

    $removePhotos = array_values(array_intersect(
        $existingPhotos,
        $data['remove_existing_photos'] ?? []
    ));

    if (! empty($removePhotos)) {
        Storage::disk('private_s3')->delete($removePhotos);
    }

    $remainingPhotos = array_values(array_filter(
        $existingPhotos,
        fn ($path) => ! in_array($path, $removePhotos, true)
    ));

    $newPhotoPaths = [];
    $baseFolder = $patient->photoAttachmentsFolder();

    if ($request->hasFile('photo_attachments')) {
        foreach ((array) $request->file('photo_attachments') as $file) {
            if (! $file) {
                continue;
            }

            $base = Str::slug(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                '_'
            );

            $ext = strtolower($file->getClientOriginalExtension());

            $name = $base . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $ext;

            $newPhotoPaths[] = $file->storeAs(
                $baseFolder,
                $name,
                'private_s3'
            );
        }
    }

    $finalPhotoPaths = array_values(array_merge(
        $remainingPhotos,
        $newPhotoPaths
    ));

    $consultation->update([
        'case_classification' => $data['case_classification'] ?? null,

        'blood_pressure'   => $data['blood_pressure'] ?? null,
        'pulse_rate'       => $data['pulse_rate'] ?? null,
        'respiratory_rate' => $data['respiratory_rate'] ?? null,
        'temperature'      => $data['temperature'] ?? null,
        'o2_saturation'    => $data['o2_saturation'] ?? null,
        'pain_rating'      => $data['pain_rating'] ?? null,

        'chief_complaint'  => $data['chief_complaint'] ?? null,
        'assessment'       => $data['assessment'] ?? null,
        'treatment'        => $data['treatment'] ?? null,

        'medicines'        => ! empty($medicines) ? $medicines : null,
        'supplies'         => ! empty($supplies) ? $supplies : null,

        'remarks'          => $data['remarks'] ?? null,

        'photo_attachments' => ! empty($finalPhotoPaths) ? $finalPhotoPaths : null,
    ]);

    $returnUrl = $request->input('return_url');

    if (
        is_string($returnUrl) &&
        filter_var($returnUrl, FILTER_VALIDATE_URL) &&
        str_starts_with($returnUrl, config('app.url'))
    ) {
        return redirect()
            ->to($returnUrl)
            ->with('flash', [
                'type' => 'success',
                'message' => 'Consultation updated successfully.',
            ]);
    }

    return redirect()
        ->route('clinic.consultations.show', $consultation)
        ->with('flash', [
            'type' => 'success',
            'message' => 'Consultation updated successfully.',
        ]);
}

    public function archive(Request $request, ClinicConsultation $consultation)
    {
        $patient = $consultation->patient;

        $consultation->delete();

        $returnUrl = $request->input('return_url');

        if (
            is_string($returnUrl) &&
            filter_var($returnUrl, FILTER_VALIDATE_URL) &&
            str_starts_with($returnUrl, config('app.url'))
        ) {
            return redirect()
                ->to($returnUrl)
                ->with('flash', [
                    'type' => 'success',
                    'message' => 'Consultation archived successfully.',
                ]);
        }

        if ($request->input('return') === 'patient' && $patient) {
            return redirect()
                ->route('clinic.patients.show', [
                    'patient' => $patient->id,
                    'tab' => $patient->type === 'staff' ? 'staff' : 'students',
                ])
                ->with('flash', [
                    'type' => 'success',
                    'message' => 'Consultation archived successfully.',
                ]);
        }

        return redirect()
            ->route('clinic.consultations.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Consultation archived successfully.',
            ]);
    }
}
