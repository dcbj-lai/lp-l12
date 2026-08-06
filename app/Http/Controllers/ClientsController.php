<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($searchQuery) use ($q) {
                    $searchQuery->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('guidance.clients.index', compact('clients', 'q'));
    }

    public function create()
    {
        return view('guidance.clients.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateClient($request);
        $client = Client::create($data);

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', 'Student client record created.');
    }

    public function show(Client $client)
    {
        $consultations = $client->consultations()
            ->whereNotNull('time_out')
            ->orderByDesc('created_at')
            ->paginate(5);

        return view('guidance.clients.show', compact('client', 'consultations'));
    }

    public function edit(Client $client)
    {
        return view('guidance.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validateClient($request, $client));

        return redirect()
            ->route('guidance.clients.show', $client)
            ->with('success', 'Student client profile updated.');
    }

    private function validateClient(Request $request, ?Client $client = null): array
    {
        $emailRule = Rule::unique('clients', 'email');

        if ($client) {
            $emailRule->ignore($client);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'course' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'is_under_accessibility' => ['sometimes', 'boolean'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'emergency_contact_person' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:50'],
        ]);

        $data['is_under_accessibility'] = $request->boolean('is_under_accessibility');

        return $data;
    }
}
