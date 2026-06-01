<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EventList extends Component
{
    public function render()
    {
        $events = Event::where('status', 'published')
            ->withCount(['attendingRegistrations as attending_count'])
            ->orderBy('start_datetime')
            ->get();

        // Map the current user's RSVP status per event
        $myResponses = \App\Models\EventRegistration::where('user_id', Auth::id())
            ->pluck('status', 'event_id');

        return view('livewire.events.event-list', compact('events', 'myResponses'));
    }
}
