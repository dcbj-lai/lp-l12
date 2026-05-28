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
        $currentMonth = now()->month;

        // 🎂 Birthdays
        $this->birthdays = User::query()
            ->whereNotNull('birthdate')
            ->whereMonth('birthdate', $currentMonth)
            ->get()
            ->sortBy(fn($user) => optional($user->birthdate)->day)
            ->values();

        // 🎉 Work Anniversaries (exclude new hires from this year)
        $this->anniversaries = User::query()
            ->whereNotNull('hire_date')
            ->whereMonth('hire_date', $currentMonth)
            ->whereYear('hire_date', '<', now()->year)
            ->get()
            ->sortBy(fn($user) => optional($user->hire_date)->day)
            ->values();
    }

    public function render()
    {
        return view('livewire.celebrations-card');
    }
}
