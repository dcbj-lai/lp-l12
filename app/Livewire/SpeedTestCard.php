<?php

namespace App\Livewire;

use Livewire\Component;

class SpeedTestCard extends Component
{
    public $latency = null;

    protected $listeners = [
        'latency-result' => 'setLatency',
    ];

    public function runLatency()
    {
        $this->dispatch('run-latency', componentId: $this->getId());
    }

    public function setLatency($latency)
    {
        $this->latency = round($latency, 2);
    }

    public function render()
    {
        return view('livewire.speed-test-card');
    }

}
