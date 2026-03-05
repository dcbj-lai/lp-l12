<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Step;
use Illuminate\Support\Facades\Auth;

class StepController extends Controller
{
    public function index()
    {
        $stepsLogs = Step::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('my-steps.index', compact('stepsLogs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'steps' => 'required|integer|min:1',
            'date' => 'required|date|before_or_equal:today',
        ]);

        $existingLog = Step::where('user_id', Auth::id())
            ->where('date', $request->date)
            ->first();

        if ($existingLog) {
            return redirect()
                ->route('my-steps.index')
                ->with('error', 'You already logged steps for this date. Nice try, time traveler. ⏳😏');
        }

        $steps = (int) $request->steps;

        Step::create([
            'user_id' => Auth::id(),
            'steps' => $steps,
            'date' => $request->date,
        ]);

        // Funny message generator
        $message = match (true) {
            $steps < 1000 => "{$steps} steps lang? Parang nagbanyo lang ah! 😅",
            $steps < 5000 => "{$steps} steps — Push mo pa 'te! 🐢",
            $steps < 8000 => "{$steps} Uyy..umeefort maglakad! May tumatakbo sa isip mo??? 👍",
            $steps === 10000 => "{$steps} Hala nakikiuso sa WHO! ⚖️",
            $steps < 12000 => "{$steps} Feeling fitness influencer ah! 💪",
            $steps < 20000 => "{$steps} steps?! Nagwo-work ka pa ba niyan?? 🏃‍♂️",
            default => "{$steps} steps?! Ano ito, Bataan Death March???. 🔥🥇",
        };

        return redirect()
            ->route('my-steps.index')
            ->with('success', $message);
    }

    public function edit(Step $step)
    {
        if ($step->user_id !== Auth::id()) {
            abort(403);
        }

        return view('my-steps.edit', compact('step'));
    }

    public function update(Request $request, Step $step)
    {
        if ($step->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'steps' => 'required|integer|min:1',
        ]);

        $oldSteps = (int) $step->steps;
        $newSteps = (int) $request->steps;

        $step->update([
            'steps' => $newSteps,
        ]);

        // Base funny message
        $message = match (true) {
            $newSteps < 1000 => "Updated to {$newSteps} steps. Still fridge cardio? 😏",
            $newSteps < 5000 => "{$newSteps} steps now. Slow and steady... very steady. 🐢",
            $newSteps < 8000 => "{$newSteps} steps! Respectable edit 👍",
            $newSteps === 10000 => "Exactly 10,000 steps. Balance restored to the universe ⚖️",
            $newSteps < 12000 => "{$newSteps} steps! Now we’re talking 💪",
            $newSteps < 20000 => "{$newSteps} steps?! Sudden athletic awakening? 🏃‍♂️",
            default => "{$newSteps} steps?! HR wants a doping test. 🥇🔥",
        };

        // Add comparison humor
        if ($newSteps > $oldSteps) {
            $message .= " 📈 Up from {$oldSteps}? We love growth.";
        } elseif ($newSteps < $oldSteps) {
            $message .= " 📉 Down from {$oldSteps}? Inflation hitting steps too?";
        } else {
            $message .= " 🤨 Same number? Suspicious consistency.";
        }

        return redirect()
            ->route('my-steps.index')
            ->with('success', $message);
    }
    public function destroy(Step $step)
    {
        if ($step->user_id !== auth()->id()) {
            abort(403);
        }

        $step->delete();

        return redirect()->route('my-steps.index')
            ->with('success', 'Step log deleted successfully.');
    }

}
