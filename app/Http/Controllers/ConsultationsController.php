<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentResumeClassMail;
use App\Mail\StudentGoHomeMail;
use Carbon\Carbon;

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
                $query->whereHas('client', function ($q2) use ($q) {
                    $q2->where('first_name', 'like', "%{$q}%")
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
            'dateTo'
        ));
    }

    public function create(Client $client)
    {
        $facultyDepartment = \App\Models\Department::whereRaw('LOWER(name) = ?', ['faculty'])->first();

        $teachers = [];

        if ($facultyDepartment) {
            $teachers = \App\Models\User::where('department_id', $facultyDepartment->id)
                ->select('name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => [
                    'name' => $u->name,
                    'email' => $u->email,
                ])
                ->toArray();
        }

        return view('guidance.consultations.create', compact('client', 'teachers'));
    }

    public function store(Request $request, Client $client)
    {
        if ($request->input('action') === 'cancel') {
            $consultation = Consultation::where('client_id', $client->id)
                ->whereNull('time_out')
                ->latest()
                ->first();

            if (!$consultation) {
                return redirect()
                    ->route('guidance.clients.show', $client)
                    ->with('info', 'Consultation cancelled.');
            }

            $consultation->delete();

            return redirect()
                ->route('guidance.clients.show', $client)
                ->with('info', 'Consultation cancelled and archived.');
        }

        $data = $request->validate([
            'current_teacher'          => ['nullable', 'string'],
            'teacher_email'            => ['nullable', 'email'],
            'next_class_teacher'       => ['nullable', 'string'],
            'next_class_teacher_email' => ['nullable', 'email'],
            'type_of_session'          => ['nullable', 'string'],
            'risk_assessment'          => ['nullable', 'string'],
            'issue_concern'            => ['nullable', 'string'],
            'intervention'             => ['nullable', 'string'],
            'remarks'                  => ['nullable', 'string'],
            'after_consultation'       => ['required', 'in:resume,go_home'],
            'going_home_method'        => ['nullable', 'in:fetcher,self', 'required_if:after_consultation,go_home'],
            'fetcher_name'             => ['nullable', 'string', 'required_if:going_home_method,fetcher'],
            'self_approved_by'         => ['nullable', 'string', 'required_if:going_home_method,self'],
        ]);

        $consultation = Consultation::where('client_id', $client->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if (!$consultation) {
            return back()->with('error', 'No active consultation found.');
        }

        $consultation->update([
            'current_teacher'          => $data['current_teacher'] ?? null,
            'teacher_email'            => $data['teacher_email'] ?? null,
            'next_class_teacher'       => $data['next_class_teacher'] ?? null,
            'next_class_teacher_email' => $data['next_class_teacher_email'] ?? null,
            'type_of_session'          => $data['type_of_session'] ?? null,
            'risk_assessment'          => $data['risk_assessment'] ?? null,
            'issue_concern'            => $data['issue_concern'] ?? null,
            'intervention'             => $data['intervention'] ?? null,
            'remarks'                  => $data['remarks'] ?? null,
            'after_consultation'       => $data['after_consultation'],
            'going_home_method'        => $data['going_home_method'] ?? null,
            'fetcher_name'             => $data['fetcher_name'] ?? null,
            'self_approved_by'         => $data['self_approved_by'] ?? null,
            'time_out'                 => now(),
        ]);

        $consultation = $consultation->fresh();

        $studentName = "{$client->first_name} {$client->last_name}";

        $dateDisplay = $consultation->time_in
            ? $consultation->time_in->format('M d, Y')
            : 'N/A';

        $timeInDisplay = $consultation->time_in
            ? $consultation->time_in->format('g:i A')
            : 'N/A';

        $timeOutDisplay = $consultation->time_out
            ? $consultation->time_out->format('g:i A')
            : 'N/A';

        $clientEmail = $client->email ?: null;

        $teacherRecipients = array_values(array_unique(array_filter([
            $consultation->check_in_teacher_email,
            $consultation->teacher_email,
            $consultation->next_class_teacher_email,
        ])));

        $ccRecipients = array_values(array_filter([
            env('REQUESTS_ACADCORE_EMAIL'),
            env('REQUESTS_GC_EMAIL'),
        ]));

        if ($consultation->after_consultation === 'resume') {
            $mail = new StudentResumeClassMail(
                $studentName,
                $consultation->next_class_teacher
                    ?? $consultation->current_teacher
                    ?? null,
                $dateDisplay,
                $timeInDisplay,
                $timeOutDisplay
            );

            if (!empty($teacherRecipients)) {
                $message = Mail::to($teacherRecipients);

                if (!empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                if (!empty($clientEmail)) {
                    $message->bcc($clientEmail);
                }

                $message->send($mail);
            }
        }

        if ($consultation->after_consultation === 'go_home') {
            $mail = new StudentGoHomeMail(
                $studentName,
                $consultation->current_teacher ?? null,
                $dateDisplay,
                $timeInDisplay,
                $timeOutDisplay,
                $consultation->going_home_method ?? null,
                $consultation->fetcher_name ?? null,
                $consultation->self_approved_by ?? null
            );

            if (!empty($teacherRecipients)) {
                $message = Mail::to($teacherRecipients);

                if (!empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                if (!empty($clientEmail)) {
                    $message->bcc($clientEmail);
                }

                $message->send($mail);
            } elseif (!empty($ccRecipients)) {
                $message = Mail::to($ccRecipients);

                if (!empty($clientEmail)) {
                    $message->bcc($clientEmail);
                }

                $message->send($mail);
            }
        }

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', 'Consultation saved successfully.');
    }

    public function update(Request $request, Consultation $consultation)
    {
        if (!$consultation->time_out) {
            return redirect()->back()
                ->with('error', 'Consultation must be completed before editing session notes.');
        }

        $data = $request->validate([
            'type_of_session' => ['nullable', 'string'],
            'risk_assessment' => ['nullable', 'string'],
            'issue_concern'   => ['nullable', 'string'],
            'intervention'    => ['nullable', 'string'],
            'remarks'         => ['nullable', 'string'],
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
        return view('guidance.consultations.edit', [
            'consultation' => $consultation,
        ]);
    }

    public function show(Consultation $consultation, Request $request)
    {
        $return = $request->input('return', 'clients');

        return view('guidance.consultations.show', [
            'consultation' => $consultation,
            'return' => $return,
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