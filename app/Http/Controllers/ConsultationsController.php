<?php

namespace App\Http\Controllers;

use App\Jobs\SendGuidanceConsultationEmail;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class ConsultationsController extends Controller
{
    public function index(Request $request)
    {
        Consultation::whereNull('time_out')
            ->whereNotNull('time_in')
            ->where('time_in', '<', Carbon::now()->subHours(4))
            ->delete();

        $q = $request->input('q');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $consultations = Consultation::with('client')
            ->whereNotNull('time_out')
            ->when($q, function ($query) use ($q) {
                $query->whereHas('client', function ($clientQuery) use ($q) {
                    $clientQuery->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom, fn ($query) => $query->whereDate('time_in', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('time_in', '<=', $dateTo))
            ->latest()
            ->paginate(10);

        return view('guidance.consultations.index', compact(
            'consultations',
            'q',
            'dateFrom',
            'dateTo',
        ));
    }

    public function create(Client $client)
    {
        $teachers = User::activeFaculty()
            ->select('name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->toArray();

        return view('guidance.consultations.create', compact('client', 'teachers'));
    }

    public function store(Request $request, Client $client)
    {
        if ($request->input('action') === 'cancel') {
            $consultation = Consultation::where('client_id', $client->id)
                ->whereNull('time_out')
                ->latest()
                ->first();

            if (! $consultation) {
                return redirect()
                    ->route('guidance.clients.show', $client)
                    ->with('info', 'Consultation cancelled.');
            }

            $consultation->delete();

            return redirect()
                ->route('guidance.clients.show', $client)
                ->with('info', 'Consultation cancelled and archived.');
        }

        $rules = [
            'type_of_session' => ['nullable', 'string'],
            'risk_assessment' => ['nullable', 'string'],
            'issue_concern' => ['nullable', 'string'],
            'intervention' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ];

        $rules['current_teacher'] = ['nullable', 'string'];
        $rules['teacher_email'] = ['nullable', 'email'];
        $rules['after_consultation'] = ['required', 'in:resume,go_home'];
        $rules['going_home_method'] = ['nullable', 'in:fetcher,self', 'required_if:after_consultation,go_home'];
        $rules['fetcher_name'] = ['nullable', 'string', 'required_if:going_home_method,fetcher'];
        $rules['self_approved_by'] = ['nullable', 'string', 'required_if:going_home_method,self'];

        $data = $request->validate($rules);

        $consultation = Consultation::where('client_id', $client->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if (! $consultation) {
            return back()->with('error', 'No active consultation found.');
        }

        $consultation->update([
            'current_teacher' => $data['current_teacher'] ?? null,
            'teacher_email' => $data['teacher_email'] ?? null,
            'type_of_session' => $data['type_of_session'] ?? null,
            'risk_assessment' => $data['risk_assessment'] ?? null,
            'issue_concern' => $data['issue_concern'] ?? null,
            'intervention' => $data['intervention'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'after_consultation' => $data['after_consultation'] ?? null,
            'going_home_method' => $data['going_home_method'] ?? null,
            'fetcher_name' => $data['fetcher_name'] ?? null,
            'self_approved_by' => $data['self_approved_by'] ?? null,
            'time_out' => now(),
        ]);

        $consultation = $consultation->fresh();
        $emailQueued = $this->queueConsultationEmail($consultation);
        $message = $emailQueued
            ? 'Consultation submitted. Email notification queued.'
            : 'Consultation submitted.';

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', $message);
    }

    public function retryEmail(Consultation $consultation)
    {
        $consultation->load('client');

        if (
            ! $consultation->client ||
            ! in_array($consultation->after_consultation, ['resume', 'go_home'], true)
        ) {
            return back()->with('error', 'This consultation does not have an email notification to retry.');
        }

        if (! $this->queueConsultationEmail($consultation)) {
            return back()->with('error', 'Email notification could not be queued. Please check the logs.');
        }

        return back()->with('success', 'Email notification queued for retry.');
    }

    private function queueConsultationEmail(Consultation $consultation): bool
    {
        $consultation->loadMissing('client');

        if (
            ! $consultation->client ||
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
            'email_status' => Consultation::EMAIL_STATUS_QUEUED,
            'email_sent_at' => null,
            'email_failed_at' => null,
            'email_failure_message' => null,
        ]);

        try {
            SendGuidanceConsultationEmail::dispatch($consultation->id);

            return true;
        } catch (Throwable $exception) {
            $consultation->update([
                'email_status' => Consultation::EMAIL_STATUS_FAILED,
                'email_failed_at' => now(),
                'email_failure_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            report($exception);

            return false;
        }
    }

    public function update(Request $request, Consultation $consultation)
    {
        if (! $consultation->time_out) {
            return redirect()->back()
                ->with('error', 'Consultation must be completed before editing session notes.');
        }

        $data = $request->validate([
            'type_of_session' => ['nullable', 'string'],
            'risk_assessment' => ['nullable', 'string'],
            'issue_concern' => ['nullable', 'string'],
            'intervention' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $consultation->update($data);

        if ($request->filled('return_url')) {
            return redirect($request->return_url)
                ->with('success', 'Session information updated.');
        }

        return redirect()
            ->route('guidance.consultations.index')
            ->with('success', 'Session information updated.');
    }

    public function edit(Consultation $consultation)
    {
        $consultation->load('client');

        return view('guidance.consultations.edit', compact('consultation'));
    }

    public function show(Consultation $consultation, Request $request)
    {
        $consultation->load('client');

        return view('guidance.consultations.show', [
            'consultation' => $consultation,
            'return' => $request->input('return', 'clients'),
        ]);
    }

    public function archive(Request $request, Consultation $consultation)
    {
        $consultation->delete();

        if ($request->return === 'client') {
            return redirect()
                ->route('guidance.clients.show', $consultation->client_id)
                ->with('success', 'Consultation archived successfully.');
        }

        return redirect()
            ->route('guidance.consultations.index')
            ->with('success', 'Consultation archived successfully.');
    }
}
