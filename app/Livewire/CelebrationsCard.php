<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Carbon\Carbon;

class CelebrationsCard extends Component
{
    public $birthdays = [];
    public $anniversaries = [];

    public function mount()
    {
        $currentMonth = Carbon::now()->month;

        // 🎂 Birthdays
        $this->birthdays = User::query()
            ->whereNotNull('birthdate')
            ->whereMonth('birthdate', $currentMonth)
            ->orderByRaw('DAY(birthdate)')
            ->get();

        // 🎉 Work Anniversaries
        $this->anniversaries = User::query()
            ->whereNotNull('hire_date')
            ->whereMonth('hire_date', $currentMonth)
            ->orderByRaw('DAY(hire_date)')
            ->get();
    }

    public function render()
    {
        return view('livewire.celebrations-card');
    }
}
