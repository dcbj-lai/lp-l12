<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationsController extends Controller
{
    public function create(Client $client)
    {
        return view('guidance.consultations.create', compact('client'));
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'current_teacher'     => ['required', 'string'],
            'time_in'             => ['required', 'date'],

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

        // Enforce go_home rules
        if ($data['after_consultation'] === 'go_home') {
            if (empty($data['going_home_method'])) {
                return back()->with('error', 'Going home method is required.')->withInput();
            }

            if ($data['going_home_method'] === 'fetcher' && empty(trim($data['fetcher_name'] ?? ''))) {
                return back()->with('error', 'Fetcher name is required.')->withInput();
            }

            if ($data['going_home_method'] === 'self' && empty(trim($data['self_approved_by'] ?? ''))) {
                return back()->with('error', 'Approved by is required.')->withInput();
            }
        } else {
            // If resume, clear go-home fields
            $data['going_home_method'] = null;
            $data['fetcher_name'] = null;
            $data['self_approved_by'] = null;
        }

        Consultation::create([
            'client_id'          => $client->id,
            'current_teacher'    => $data['current_teacher'],
            'time_in'            => $data['time_in'],
            'time_out'           => now(),

            'type_of_session'    => $data['type_of_session'] ?? null,
            'risk_assessment'    => $data['risk_assessment'] ?? null,
            'issue_concern'      => $data['issue_concern'] ?? null,
            'intervention'       => $data['intervention'] ?? null,
            'remarks'            => $data['remarks'] ?? null,

            'after_consultation' => $data['after_consultation'],
            'going_home_method'  => $data['going_home_method'] ?? null,
            'fetcher_name'       => $data['fetcher_name'] ?? null,
            'self_approved_by'   => $data['self_approved_by'] ?? null,
        ]);

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', 'Consultation saved successfully.');
    }
}