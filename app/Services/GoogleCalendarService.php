<?php

namespace App\Services;

use App\Models\ResourceReservation;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected Calendar $calendar;

    public function __construct()
    {
        $client = new Client();

        // 🔥 Load credentials from S3/local via your loader
        $client->setAuthConfig(GoogleCredentialLoader::load());

        $client->addScope(Calendar::CALENDAR);

        // 🔥 Impersonation (CRITICAL)
        $client->setSubject(config('google-calendar.user_to_impersonate'));

        $this->calendar = new Calendar($client);
    }

    public function createEvent(ResourceReservation $reservation): ?string
    {
        $calendarId = config('services.google.resource_calendar_id');

        $event = new Event([
            'summary' => $this->buildTitle($reservation),
            'description' => $this->buildDescription($reservation),
            'location' => $reservation->resource?->name,

            'start' => [
                'dateTime' => Carbon::parse($reservation->start_datetime)->toIso8601String(),
                'timeZone' => 'Asia/Manila',
            ],

            'end' => [
                'dateTime' => Carbon::parse($reservation->end_datetime)->toIso8601String(),
                'timeZone' => 'Asia/Manila',
            ],
        ]);

        // 🔥 Add attendee (requester)
        if ($reservation->requester_email) {
            $event->setAttendees([
                new EventAttendee([
                    'email' => $reservation->requester_email,
                ]),
            ]);
        }

        // 🔥 Create event
        $createdEvent = $this->calendar->events->insert($calendarId, $event);

        return $createdEvent->getId();
    }

    protected function buildTitle(ResourceReservation $reservation): string
    {
        return '[Resource] ' . $reservation->title;
    }

    protected function buildDescription(ResourceReservation $reservation): string
    {
        $lines = [];

        if ($reservation->resource) {
            $lines[] = 'Room: ' . $reservation->resource->name;
        }

        if ($reservation->equipment->count()) {
            $lines[] = 'Equipment: ' . $reservation->equipment->pluck('name')->join(', ');
        }

        if ($reservation->requester_email) {
            $lines[] = 'Requester: ' . $reservation->requester_email;
        }

        return implode("\n", $lines);
    }

    public function deleteEvent(string $eventId): void
    {
        $calendarId = config('services.google.resource_calendar_id');

        $this->calendar->events->delete($calendarId, $eventId);
    }
}
