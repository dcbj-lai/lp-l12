<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\GoogleCalendar\Event;

class CalendarWidget extends Component
{
    public $events = [];

    public function mount()
{
    /** Override ENV default Leave Calendar with  */
    $college_calendar = env('COLLEGE_CALENDAR_ID','c_3d8ded25454ed4170491c6445ad656fd5ef82d536287fe06cd4bc8b6acba5fe7@group.calendar.google.com');
    $googleEvents = Event::get(null,null,[],$college_calendar);

    $this->events = $googleEvents
        ->sortByDesc(fn($event) => $event->startDateTime ?? $event->startDate)
        ->take(3)
        ->map(fn($event) => [
            'title' => $event->name,
            'start' => $event->startDateTime ?? $event->startDate,
            'link'  => $event->googleEvent['htmlLink'] ?? null,
        ])
        ->values()
        ->toArray();

}

    public function render()
    {
        return view('livewire.calendar-widget');
    }
}
