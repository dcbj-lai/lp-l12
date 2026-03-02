<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConsultationsController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from'); // YYYY-MM-DD
        $dateTo   = $request->input('date_to');   // YYYY-MM-DD

        $query = Consultation::query()
            ->select([
                'consultations.id',
                'consultations.client_id',
                'consultations.current_teacher',
                'consultations.time_in',
                'consultations.time_out',
                'consultations.type_of_session',
                'consultations.risk_assessment',
                'consultations.issue_concern',
                'consultations.intervention',
                'consultations.remarks',
                'consultations.after_consultation',
                'consultations.going_home_method',
                'consultations.fetcher_name',
                'consultations.self_approved_by',
                'consultations.created_at',
                'consultations.updated_at',
            ])
            ->with(['client:id,first_name,last_name,email'])
            ->orderBy('consultations.id', 'asc');

        // Date filter (inclusive) using time_in
        if (!empty($dateFrom)) {
            $query->whereDate('consultations.time_in', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('consultations.time_in', '<=', $dateTo);
        }

        // Search by client name OR email only
        if ($q !== '') {
            $query->whereHas('client', function ($c) use ($q) {
                $c->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                // full name search: "Juan Dela Cruz"
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$q}%"])
                // optional: reverse order "Dela Cruz, Juan"
                ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$q}%"]);
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

    // 🔎 Find the ACTIVE consultation (created during check-in)
    $consultation = Consultation::where('client_id', $client->id)
        ->whereNull('time_out')
        ->latest()
        ->first();

    if (!$consultation) {
        return back()->with('error', 'No active consultation found.');
    }

    // ✅ Update existing record instead of creating new one
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
        'time_out'           => now(), // ✅ checkout moment
    ]);

    return redirect()
        ->route('guidance.clients.show', $client)
        ->with('success', 'Consultation saved successfully.');
    }

    public function show(Consultation $consultation)
    {
        $consultation->load('client:id,first_name,last_name,email');
        return view('guidance.consultations.show', compact('consultation'));
    }
}