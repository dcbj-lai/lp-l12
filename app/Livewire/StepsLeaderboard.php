<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Step;
use Carbon\Carbon;

class StepsLeaderboard extends Component
{
    public $leaders;

    public $mode = 'full'; // 'full' or 'card'
    public $startDate;
    public $endDate;

    public function mount($mode = 'full')
    {
        $this->mode = $mode;

        if ($this->mode === 'card') {
            $this->startDate = Carbon::now()->startOfMonth()->toDateString();
            $this->endDate = Carbon::now()->endOfMonth()->toDateString();
        } else {
            $this->startDate = Carbon::now()->startOfMonth()->toDateString();
            $this->endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        $this->loadLeaders();
    }

    public function updatedStartDate()
    {
        $this->loadLeaders();
    }

    public function updatedEndDate()
    {
        $this->loadLeaders();
    }

    public function loadLeaders()
    {
        $query = Step::with('user')
            ->selectRaw('user_id, SUM(steps) as total_steps')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->groupBy('user_id')
            ->orderByDesc('total_steps');

        if ($this->mode === 'card') {
            $query->take(3);
        }

        $this->leaders = $query->get();
    }

    public function render()
    {
        return view('livewire.steps-leaderboard');
    }
}
