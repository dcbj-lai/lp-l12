<?php

namespace App\Http\Controllers;

use App\Models\OnlineDay;
use Illuminate\Http\Request;

class OnlineDayController extends Controller
{
    public function index()
    {
        $onlineDays = OnlineDay::orderBy('date', 'desc')->paginate(15);
        return view('onlinedays.index', compact('onlineDays'));
    }

    public function create()
    {
        return view('onlinedays.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:online_days,date',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string',
        ]);

        OnlineDay::create([
            'date' => $request->date,
            'is_active' => $request->boolean('is_active', true),
            'declared_by' => auth()->user()->name ?? 'System',
            'remarks' => $request->remarks,
        ]);

        return redirect()->route('onlinedays.index')->with('success', 'Online day declared.');
    }

    public function edit(OnlineDay $onlineday)
    {
        return view('onlinedays.edit', [
            'onlineDay' => $onlineday,
        ]);
    }

    public function update(Request $request, OnlineDay $onlineday)
{
    $validated = $request->validate([
        'date' => 'required|date',
        'declared_by' => 'required|string|max:255',
        'remarks' => 'nullable|string|max:255',
    ]);

    // Explicitly handle checkbox
    $validated['is_active'] = $request->has('is_active');

    $onlineday->update($validated);

    return redirect()->route('onlinedays.index')
        ->with('success', 'Online day updated successfully.');
}


        public function destroy(OnlineDay $onlineday)
    {
        $onlineday->delete();

        return redirect()->route('onlinedays.index')
                        ->with('info', 'Online day deleted successfully.');
    }

}
