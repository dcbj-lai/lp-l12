<?php

namespace Tests\Feature;

use App\Livewire\Resources\ResourceIndex;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EquipmentBookingsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_view_filters_dates_status_and_deleted_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $equipment = Resource::create(['name' => 'Display', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $admin->id]);
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 10, 'created_by' => $admin->id]);
        $otherRoom = Resource::create(['name' => 'Room B', 'type' => 'room', 'capacity' => 10, 'created_by' => $admin->id]);
        $make = function ($title, $status, $start, $end) use ($equipment, $room, $otherRoom) {
            $booking = ResourceReservation::create(['title' => $title, 'resource_id' => $room->id,
                'status' => $status, 'start_datetime' => $start, 'end_datetime' => $end]);
            $booking->rooms()->sync([$room->id, $otherRoom->id]);
            $booking->equipment()->attach($equipment->id, ['quantity' => 2]);
            return $booking;
        };
        $this->travelTo(now()->setDate(2026, 10, 14)->startOfDay());
        $approved = $make('Approved display event', 'approved', '2026-10-15 23:00', '2026-10-16 01:00');
        $pending = $make('Pending display event', 'pending', '2026-10-17 10:00', '2026-10-17 11:00');
        $make('Rejected event', 'rejected', '2026-10-17 10:00', '2026-10-17 11:00');
        $make('Deleted event', 'approved', '2026-10-17 10:00', '2026-10-17 11:00')->delete();
        $past = $make('Past event', 'approved', '2026-10-12 10:00', '2026-10-12 11:00');
        $component = Livewire::actingAs($admin)->test(ResourceIndex::class)
            ->call('viewEquipmentBookings', $equipment->id)
            ->assertSee('Room A, Room B')->assertSee('Quantity: 2');
        $this->assertSame([$approved->id, $pending->id], $component->instance()->equipmentBookings->pluck('id')->all());
        $component->set('bookingsFrom', '2026-10-16')->set('bookingsTo', '2026-10-16');
        $this->assertSame([$approved->id], $component->instance()->equipmentBookings->pluck('id')->all());
        $component->set('bookingsFrom', '')->set('bookingsTo', '')->set('bookingsPeriod', 'past');
        $this->assertSame([$past->id], $component->instance()->equipmentBookings->pluck('id')->all());
    }

    public function test_non_admin_cannot_open_resources(): void
    {
        Role::findOrCreate('facility.admin', 'web');
        Livewire::actingAs(User::factory()->create())->test(ResourceIndex::class)->assertForbidden();
    }
}

