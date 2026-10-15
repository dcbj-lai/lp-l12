<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityReservationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_fields_rooms_and_literal_text_without_leaking_other_statuses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'Meeting Room East', 'type' => 'room', 'capacity' => 10, 'created_by' => $admin->id]);
        $booking = ResourceReservation::create(['title' => 'Workshop 100%', 'requester_name' => 'Maria Cruz',
            'requester_email' => 'maria@example.test', 'status' => 'pending', 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour()]);
        $booking->rooms()->attach($room);
        $other = ResourceReservation::create(['title' => 'Other event', 'status' => 'pending',
            'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour()]);
        $approved = $booking->replicate();
        $approved->status = 'approved';
        $approved->save();
        $deleted = $booking->replicate();
        $deleted->save();
        $deleted->delete();
        $component = Livewire::actingAs($admin)->test(ReservationIndex::class);
        foreach (['WORKSHOP', 'maria cruz', 'MARIA@EXAMPLE', 'meeting room east', '#'.$booking->id, '100%'] as $search) {
            $component->set('search', $search);
            $this->assertSame([$booking->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        }
        $component->set('search', 'missing event');
        $this->assertCount(0, $component->instance()->reservations);
        $component->set('search', 'workshop')->set('statusFilter', 'approved');
        $this->assertSame([$approved->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'deleted');
        $this->assertSame([$deleted->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'pending')->set('search', '');
        $this->assertCount(2, $component->instance()->reservations->flatten(1));
    }

    public function test_viewer_search_keeps_permissions_status_and_sort(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::findOrCreate('facility.viewer', 'web'));
        foreach (['approved', 'rejected'] as $status) ResourceReservation::create([
            'title' => $status.' accessible workshop', 'status' => $status,
            'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour(),
        ]);
        $this->actingAs($viewer)->get(route('resources.reservations.index', ['search' => 'WORKSHOP', 'sort' => 'desc']))
            ->assertOk()->assertSee('approved accessible workshop')->assertDontSee('rejected accessible workshop')
            ->assertSee('search=WORKSHOP', false)->assertSee('sort=desc', false);
    }
}
