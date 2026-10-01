<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoFacilityReservationSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::where('email', 'test@example.com')->firstOrFail();
        $meetingRoom = Resource::where('name', 'Demo Meeting Room A')->where('type', 'room')->firstOrFail();
        $workshopRoom = Resource::where('name', 'Demo Workshop Room')->where('type', 'room')->firstOrFail();
        $display = Resource::where('name', 'Demo Movable Display')->where('type', 'equipment')->firstOrFail();

        $bookings = [
            [
                'title' => '[Demo] Staff orientation',
                'resource_id' => $meetingRoom->id,
                'start_datetime' => today()->addDays(5)->setTime(9, 0),
                'end_datetime' => today()->addDays(5)->setTime(11, 0),
                'status' => 'pending',
                'number_of_pax' => 18,
                'setup_arrangement' => 'Classroom',
                'notes' => 'Registration table near the entrance.',
            ],
            [
                'title' => '[Demo] Planning meeting',
                'resource_id' => $meetingRoom->id,
                'start_datetime' => today()->addDays(6)->setTime(13, 0),
                'end_datetime' => today()->addDays(6)->setTime(15, 0),
                'status' => 'pending',
                'number_of_pax' => 10,
                'setup_arrangement' => 'Workshop',
                'notes' => 'Arrange tables in small groups.',
            ],
            [
                'title' => '[Demo] Faculty workshop',
                'resource_id' => $workshopRoom->id,
                'start_datetime' => today()->addDays(5)->setTime(13, 0),
                'end_datetime' => today()->addDays(5)->setTime(16, 0),
                'status' => 'approved',
                'approved_by' => $organizer->id,
                'approved_at' => now(),
                'number_of_pax' => 24,
                'setup_arrangement' => 'Workshop',
                'notes' => 'One movable display requested.',
            ],
            [
                'title' => '[Demo] Rehearsal',
                'resource_id' => $workshopRoom->id,
                'start_datetime' => today()->addDays(6)->setTime(9, 0),
                'end_datetime' => today()->addDays(6)->setTime(11, 0),
                'status' => 'rejected',
                'approved_by' => $organizer->id,
                'approved_at' => now(),
                'approval_note' => 'Sample rejection: please submit an updated floor plan.',
                'number_of_pax' => 12,
                'setup_arrangement' => 'Theater',
            ],
        ];

        foreach ($bookings as $booking) {
            $reservation = ResourceReservation::withTrashed()->firstOrCreate(
                ['title' => $booking['title'], 'requester_email' => $organizer->email],
                ['user_id' => $organizer->id, 'contact_number' => '09170000000'] + $booking,
            );

            if ($booking['title'] === '[Demo] Faculty workshop') {
                $reservation->equipment()->syncWithoutDetaching([$display->id => ['quantity' => 1]]);
            }
        }
    }
}
