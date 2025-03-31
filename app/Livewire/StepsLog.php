<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Step;
use Carbon\Carbon;

class StepsLog extends Component
{
    public $dateFrom;
    public $dateTo;
    public $leaderboard = [];

    public function mount()
    {
        // Set default dates: start of the month to today
        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->toDateString();

        $this->loadLeaderboard();
    }
    /**
     * Summary of loadLeaderboard
     * @return void
     */
    public function loadLeaderboard()
    {
        // Ensure dateFrom is not after dateTo
        if ($this->dateFrom > $this->dateTo) {
            session()->flash('error', 'Invalid date range!');
            $this->leaderboard = [];
            return;
        }

        // Load leaderboard data
        $this->leaderboard = Step::with('user')
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('user_id, SUM(steps) as total_steps')
            ->groupBy('user_id')
            ->orderByDesc('total_steps')
            ->get();
    }

    public function clearFilters()
{
    $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
    $this->dateTo = Carbon::now()->toDateString();
    $this->loadLeaderboard();
}

    public function render()
    {
        return view('livewire.steps-log');
    }
}
