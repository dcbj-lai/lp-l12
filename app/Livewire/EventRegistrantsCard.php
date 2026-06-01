<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Attributes\On;
use Livewire\Component;

class EventRegistrantsCard extends Component
{
    #[On('rsvp-updated')]
    public function refresh()
    {
        // re-render to reflect new RSVPs
    }

    public function render()
    {
        // Featured event: soonest upcoming published event (fallback: latest published)
        $event = Event::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_datetime')
                    ->orWhere('start_datetime', '>=', now()->startOfDay());
            })
            ->orderBy('start_datetime')
            ->first()
            ?? Event::where('status', 'published')->latest()->first();

        $registrants = collect();
        $attendingCount = 0;

        if ($event) {
            $attendingCount = $event->attendingRegistrations()->count();
            $registrants = $event->attendingRegistrations()
                ->with('user')
                ->orderBy('responded_at')
                ->take(3)
                ->get();
        }

        return view('livewire.event-registrants-card', compact('event', 'registrants', 'attendingCount'));
    }
}
