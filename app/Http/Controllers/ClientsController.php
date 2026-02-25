<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('guidance.clients.index', [
            'clients' => $clients,
            'q'       => $q,
        ]);
    }

    public function show(Client $client)
    {
        $consultations = $client->consultations()
            ->orderByDesc('time_in')   // newest first by check-in time
            ->limit(5)
            ->get();

        return view('guidance.clients.show', compact('client', 'consultations'));
    }
}