<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Step;
use Carbon\Carbon;

class StepsLeaderboard extends Component
{
    public $leaders = [];

    public $mode = 'full';
    public $startDate;
    public $endDate;
    public $appliedStartDate;
    public $appliedEndDate;

    public function mount($mode = 'full')
    {
        $this->mode = $mode;

        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();

        $this->appliedStartDate = $this->startDate;
        $this->appliedEndDate = $this->endDate;

        $this->loadLeaders();
    }

    public function filter()
    {
        $this->appliedStartDate = $this->startDate;
        $this->appliedEndDate = $this->endDate;

        $this->loadLeaders();
    }

    public function resetMonthToDate()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();

        $this->appliedStartDate = $this->startDate;
        $this->appliedEndDate = $this->endDate;

        $this->loadLeaders();
    }

    public function loadLeaders()
    {
        if ($this->appliedStartDate > $this->appliedEndDate) {
            session()->flash('error', 'Invalid date range.');
            return;
        }

        $query = Step::with('user')
            ->selectRaw('user_id, SUM(steps) as total_steps, COUNT(*) as days_logged')
            ->whereBetween('date', [$this->appliedStartDate, $this->appliedEndDate])
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
