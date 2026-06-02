<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Attributes\On;
use Livewire\Component;

class EventsCard extends Component
{
    #[On('rsvp-updated')]
    public function refresh()
    {
        // re-render
    }

    public function render()
    {
        $events = Event::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_datetime')
                    ->orWhere('start_datetime', '>=', now()->startOfDay());
            })
            ->orderBy('start_datetime')
            ->take(4)
            ->get();

        return view('livewire.events-card', compact('events'));
    }
}
