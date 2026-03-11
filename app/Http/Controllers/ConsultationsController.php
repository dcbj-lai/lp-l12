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
        /*
        |--------------------------------------------------------------------------
        | Auto-archive consultations with no timeout after 2 minutes (TEST)
        |--------------------------------------------------------------------------
        */

        Consultation::whereNull('time_out')
            ->whereNotNull('time_in')
            ->where('time_in', '<', Carbon::now()->subHours(4))
            ->delete(); // Soft delete

        /*
        |--------------------------------------------------------------------------
        | Existing query
        |--------------------------------------------------------------------------
        */

        $q = $request->input('q');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $consultations = Consultation::with('client')
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
        return view('guidance.consultations.create', compact('client'));
    }

    public function store(Request $request, Client $client)
    {
        /*
        |--------------------------------------------------------------------------
        | Cancel Action
        |--------------------------------------------------------------------------
        */

        if ($request->input('action') === 'cancel') {

            $consultation = Consultation::where('client_id', $client->id)
                ->whereNull('time_out')
                ->latest()
                ->first();

            // If no consultation exists (no check-in)
            if (!$consultation) {
                return redirect()
                    ->route('guidance.clients.show', $client)
                     ->with('info', 'Consultation cancelled.');
            }

            // Archive consultation
            $consultation->delete(); // Soft delete

            return redirect()
                ->route('guidance.clients.show', $client)
                ->with('info', 'Consultation cancelled and archived.');
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Save Validation
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'current_teacher'     => ['nullable', 'string'],
            'type_of_session'     => ['nullable', 'string'],
            'risk_assessment'     => ['nullable', 'string'],
            'issue_concern'       => ['nullable', 'string'],
            'intervention'        => ['nullable', 'string'],
            'remarks'             => ['nullable', 'string'],
            'after_consultation'  => ['required', 'in:resume,go_home'],
            'going_home_method'   => ['nullable', 'in:fetcher,self'],
            'fetcher_name'        => ['nullable', 'string'],
            'self_approved_by'    => ['nullable', 'string'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Active Consultation
        |--------------------------------------------------------------------------
        */

        $consultation = Consultation::where('client_id', $client->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if (!$consultation) {
            return back()->with('error', 'No active consultation found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Update Consultation
        |--------------------------------------------------------------------------
        */

        $consultation->update([
            'type_of_session'    => $data['type_of_session'] ?? null,
            'risk_assessment'    => $data['risk_assessment'] ?? null,
            'issue_concern'      => $data['issue_concern'] ?? null,
            'intervention'       => $data['intervention'] ?? null,
            'remarks'            => $data['remarks'] ?? null,
            'after_consultation' => $data['after_consultation'],
            'going_home_method'  => $data['going_home_method'] ?? null,
            'fetcher_name'       => $data['fetcher_name'] ?? null,
            'self_approved_by'   => $data['self_approved_by'] ?? null,
            'time_out'           => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Email Notification
        |--------------------------------------------------------------------------
        */

        $studentName = "{$client->first_name} {$client->last_name}";
        $timeOutDisplay = $consultation->time_out->format('M d, Y h:i A');
        $ccRecipients = [env('REQUESTS_ACADCORE_EMAIL'), env('REQUESTS_GC_EMAIL')];


        $mail = null;

        if ($consultation->after_consultation === 'resume') {
            $mail = new StudentResumeClassMail(
                $studentName,
                $consultation->current_teacher ?? 'N/A',
                $timeOutDisplay
            );
        }

        if ($consultation->after_consultation === 'go_home') {
            $mail = new StudentGoHomeMail(
                $studentName,
                $consultation->current_teacher ?? 'N/A',
                $timeOutDisplay,
                $consultation->going_home_method ?? 'N/A',
                $consultation->going_home_method === 'fetcher'
                    ? $consultation->fetcher_name
                    : $consultation->self_approved_by
            );
        }

        if ($mail) {

        if (!empty($consultation->teacher_email)) {
            Mail::to($consultation->teacher_email)
                ->cc($ccRecipients)
                ->queue($mail);
        } else {
            Mail::to($ccRecipients)
                ->queue($mail);
        }
    }

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', 'Consultation saved successfully.');
    }
    public function update(Request $request, Consultation $consultation)
{
    // --------------------------------------------
    // Do not allow editing while consultation active
    // --------------------------------------------

    if (!$consultation->time_out) {
        return redirect()->back()
            ->with('error', 'Consultation must be completed before editing session notes.');
    }

    // --------------------------------------------
    // Validate session information only
    // --------------------------------------------

    $data = $request->validate([
        'type_of_session' => ['nullable','string'],
        'risk_assessment' => ['nullable','string'],
        'issue_concern'   => ['nullable','string'],
        'intervention'    => ['nullable','string'],
        'remarks'         => ['nullable','string'],
    ]);

    // --------------------------------------------
    // Update session notes only
    // --------------------------------------------

    $consultation->update($data);

    // --------------------------------------------
    // Redirect
    // --------------------------------------------

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
        'return' => $return
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