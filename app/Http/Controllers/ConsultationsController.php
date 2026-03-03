<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentResumeClassMail;
use App\Mail\StudentGoHomeMail;

class ConsultationsController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $query = Consultation::query()
            ->with(['client:id,first_name,last_name,email'])
            ->orderByDesc('created_at');

        if (!empty($dateFrom)) {
            $query->whereDate('time_in', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('time_in', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->whereHas('client', function ($c) use ($q) {
                $c->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$q}%"]);
            });
        }

        $consultations = $query
            ->paginate(10)
            ->appends($request->only(['q', 'date_from', 'date_to']));

        return view('guidance.consultations.index', compact('consultations', 'q', 'dateFrom', 'dateTo'));
    }

    public function create(Client $client)
    {
        return view('guidance.consultations.create', compact('client'));
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'current_teacher'     => ['required', 'string'],
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

        // Find active consultation (created during check-in)
        $consultation = Consultation::where('client_id', $client->id)
            ->whereNull('time_out')
            ->latest()
            ->first();

        if (!$consultation) {
            return back()->with('error', 'No active consultation found.');
        }

        // Update consultation and set time_out
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
        | Email Notification (Always Sends to Lem)
        |--------------------------------------------------------------------------
        */

        $recipientEmail = 'lem.fajarda@laicollege.edu.ph';
        $ccList = ['lcfajarda@gmail.com'];

        $studentName    = "{$client->first_name} {$client->last_name}";
        $teacherName    = $consultation->current_teacher;
        $timeOutDisplay = $consultation->time_out->format('M d, Y h:i A');

        if ($data['after_consultation'] === 'resume') {

            Mail::to($recipientEmail)
                ->cc($ccList)
                ->send(
                    new StudentResumeClassMail(
                        $studentName,
                        $teacherName,
                        $timeOutDisplay
                    )
                );
        }

        if ($data['after_consultation'] === 'go_home') {

            $releaseMode = $data['going_home_method'] === 'fetcher'
                ? 'With Fetcher'
                : 'By Oneself';

            $releaseDetails = $data['going_home_method'] === 'fetcher'
                ? $data['fetcher_name']
                : $data['self_approved_by'];

            Mail::to($recipientEmail)
                ->cc($ccList)
                ->send(
                    new StudentGoHomeMail(
                        $studentName,
                        $teacherName,
                        $timeOutDisplay,
                        $releaseMode,
                        $releaseDetails
                    )
                );
        }

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', 'Consultation saved successfully.');
    }

    public function show(Consultation $consultation)
    {
        $consultation->load('client:id,first_name,last_name,email');
        return view('guidance.consultations.show', compact('consultation'));
    }

    public function archive(Consultation $consultation)
    {
    $consultation->delete(); // soft delete
    return back()->with('success', 'Consultation archived successfully.');
    }
}