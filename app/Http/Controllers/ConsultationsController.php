<?php

namespace App\Http\Controllers;

use App\Models\Client;

class ConsultationsController extends Controller
{
    public function create(Client $client)
    {
        return view('guidance.consultations.create', compact('client'));
    }
}