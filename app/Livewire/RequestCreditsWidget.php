<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class RequestCreditsWidget extends Component
{
    public $pto = 0;
    public $wfh = 0;
    // public $offset = 0;

    public function mount()
    {
        $credit = Auth::user()->requestCredit;
        // dd($credit);

        $this->pto = $credit?->pto ?? 0;
        $this->wfh = $credit?->wfh ?? 0;
        // $this->offset = $credit?->offset ?? 0;
    }

    public function render()
    {
        return view('livewire.request-credits-widget');
    }
}
