<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Livewire\Resources\ResourceIndex;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\ResourceReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityMultiRoomTest extends TestCase
{
    use RefreshDatabase;

    private function setupRooms(): array
    {
        Mail::fake();
        $this->mock(GoogleCalendarService::class, fn ($mock) => $mock->shouldReceive('createEvent')->andReturn(null));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($admin);
        $a = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 10, 'created_by' => $admin->id]);
        $b = Resource::create(['name' => 'Room B', 'type' => 'room', 'capacity' => 10, 'created_by' => $admin->id]);
        $equipment = Resource::create(['name' => 'Display', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $admin->id]);
        return [$admin, $a, $b, $equipment, app(ResourceReservationService::class)];
    }

    private function payload(array $roomIds): array
    {
        return ['title' => 'Two rooms', 'room_ids' => $roomIds, 'requester_name' => 'Multi Room Booker', 'requester_email' => 'multi@example.test',
            'start_datetime' => now()->addMonth()->startOfDay()->addHours(10), 'end_datetime' => now()->addMonth()->startOfDay()->addHours(11)];
    }

    public function test_all_rooms_block_overlaps_and_equipment_is_counted_once(): void
    {
        [$admin, $a, $b, $equipment, $service] = $this->setupRooms();
        $payload = $this->payload([$a->id, $b->id]);
        $booking = $service->create($payload + ['equipment_quantities' => [$equipment->id => 1]]);
        $service->approveReservation($booking, $admin->id);
        $this->assertSame([$a->id, $b->id], $booking->fresh()->roomIds());
        $this->assertSame(2, $service->availableEquipmentQuantity($equipment->id, $payload['start_datetime'], $payload['end_datetime']));
        $this->assertFalse($service->isResourceAvailable($b->id, $payload['start_datetime'], $payload['end_datetime']));
        $pending = $service->create($this->payload([$b->id]));
        $this->assertSame(['Room B'], $service->approvedConflictsForReservation($pending));
        $this->assertSame([$booking->id], $service->blockingApprovedReservations($pending)->pluck('id')->all());
        try { $service->approveReservation($pending, $admin->id); $this->fail('Conflicting approval was accepted'); }
        catch (\Exception $e) { $this->assertStringContainsString('Room B', $e->getMessage()); }
        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertTrue($service->isResourceAvailable($b->id, $payload['end_datetime'], $payload['end_datetime']->addHour()));
        $availability = Livewire::test(ResourceIndex::class)->instance()->availabilityNow();
        $this->assertSame(1, $availability[$a->id]['approved']);
        $this->assertSame(1, $availability[$b->id]['approved']);
    }

    public function test_series_and_approved_edit_preserve_room_assignments_and_history(): void
    {
        [$admin, $a, $b, $equipment, $service] = $this->setupRooms();
        $data = $this->payload([$a->id, $b->id]);
        $date = $data['start_datetime']->toDateString();
        $series = $service->createSeriesForDates($data, [$date, $data['start_datetime']->addDay()->toDateString()], 'Daily for 2 dates');
        foreach ($series as $booking) $this->assertSame([$a->id, $b->id], $booking->roomIds());
        $booking = $service->approveReservation($series[0], $admin->id);
        $edited = $service->update($booking, ['room_ids' => [$b->id]]);
        $this->assertSame('approved', $edited->status);
        $this->assertSame([$b->id], $edited->roomIds());
        $this->assertTrue($service->isResourceAvailable($a->id, $data['start_datetime'], $data['end_datetime']));
        $history = DB::table('resource_reservation_edits')->where('reservation_id', $booking->id)->first();
        $this->assertSame([$a->id, $b->id], json_decode($history->before, true)['room_ids']);
        Livewire::test(ReservationIndex::class)->call('emailReservationChanges', $booking->id);
        Mail::assertQueued(\App\Mail\ResourceBookingEdited::class, fn ($mail) => str_contains($mail->render(), 'Room A, Room B'));
        $edited->delete();
        $restored = $service->restoreReservation($edited);
        $this->assertSame([$b->id], $restored->roomIds());
        $this->assertSame('pending', $restored->status);
        $this->assertFalse($series[1]->fresh()->trashed());
    }

    public function test_invalid_second_room_rejects_whole_booking_and_legacy_api_still_works(): void
    {
        [$admin, $a, $b, $equipment, $service] = $this->setupRooms();
        $b->update(['capacity' => 0]);
        try { $service->create($this->payload([$a->id, $b->id])); $this->fail('Zero-capacity room accepted'); }
        catch (\InvalidArgumentException $e) { $this->assertStringContainsString('Room B', $e->getMessage()); }
        $this->assertSame(0, ResourceReservation::count());
        $payload = $this->payload([$a->id]);
        unset($payload['room_ids']);
        $booking = $service->create($payload + ['resource_id' => $a->id]);
        $this->assertSame([$a->id], $booking->roomIds());
        $this->assertDatabaseHas('resource_reservation_rooms', ['reservation_id' => $booking->id, 'resource_id' => $a->id]);
    }

    public function test_api_accepts_multiple_rooms_and_partial_edits_keep_them(): void
    {
        [$admin, $a, $b] = $this->setupRooms();
        $payload = $this->payload([$a->id, $b->id]) + ['number_of_pax' => 5, 'setup_arrangement' => 'Workshop',
            'contact_number' => '09170000000', 'floor_plan_path' => 'plan.pdf'];
        $created = $this->postJson('/api/facility-reservations', $payload)->assertCreated()->assertJsonCount(2, 'data.rooms');
        $id = $created->json('data.id');
        $this->patchJson('/api/facility-reservations/'.$id, ['notes' => 'Updated notes'])
            ->assertOk()->assertJsonCount(2, 'data.rooms');
        $this->getJson('/api/facility-reservations?resource_id='.$b->id)->assertOk()->assertJsonPath('meta.total', 1);
        $this->patchJson('/api/facility-reservations/'.$id, ['room_ids' => []])->assertUnprocessable();
        $this->patchJson('/api/facility-reservations/'.$id, ['resource_id' => $b->id])
            ->assertOk()->assertJsonCount(1, 'data.rooms')->assertJsonPath('data.rooms.0.id', $b->id);
    }
}
