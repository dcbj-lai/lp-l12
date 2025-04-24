<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Step;
use Carbon\Carbon;

class StepsLeaderboard extends Component
{
    public $leaders;

    public function mount()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $this->leaders = Step::with('user')
            ->selectRaw('user_id, SUM(steps) as total_steps')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->groupBy('user_id')
            ->orderByDesc('total_steps')
            ->take(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.steps-leaderboard');
    }
}

