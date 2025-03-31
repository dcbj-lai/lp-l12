<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Step;

class StepController extends Controller
{
    public function index()
{
    $stepsLogs = Step::where('user_id', auth()->id())
        ->orderBy('date', 'desc')
        ->paginate(10); // Add pagination with 10 entries per page

    return view('my-steps.index', compact('stepsLogs'));
}


public function store(Request $request)
{
    $request->validate([
        'steps' => 'required|integer|min:1',
    ]);

    $existingLog = Step::where('user_id', auth()->id())
        ->where('date', now()->toDateString())
        ->first();

    if ($existingLog) {
        return redirect()->route('my-steps.index')->with('error', 'You can only log steps once per day!');
    }

    Step::create([
        'user_id' => auth()->id(),
        'steps' => $request->steps,
        'date' => now()->toDateString(),
    ]);

    return redirect()->route('my-steps.index')->with('success', 'Steps logged successfully!');
}

}
