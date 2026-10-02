<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Livewire\Resources\CreateReservation;
use App\Livewire\Resources\ResourceIndex;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\ResourceReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityReservationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_resources_without_capacity_cannot_be_booked(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'No Capacity Room', 'type' => 'room', 'created_by' => $admin->id]);
        $display = Resource::create(['name' => 'Empty Display', 'type' => 'equipment', 'total_quantity' => 0, 'created_by' => $admin->id]);
        $service = app(ResourceReservationService::class);

        $this->assertFalse($service->isResourceAvailable($room->id, '2026-10-06 09:00', '2026-10-06 10:00'));
        $this->assertSame(0, $service->availableEquipmentQuantity($display->id, '2026-10-06 09:00', '2026-10-06 10:00'));
        $form = Livewire::actingAs($admin)->test(CreateReservation::class);
        $this->assertFalse($form->instance()->rooms->contains('id', $room->id));
        $this->assertFalse($form->instance()->equipment->contains('id', $display->id));
    }

    public function test_admin_can_create_resources_without_control_numbers_and_sees_duplicate_error(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));

        Livewire::actingAs($admin)->test(ResourceIndex::class)
            ->set('name', 'First Room')->set('capacity', 5)->call('store')->assertHasNoErrors();
        Livewire::actingAs($admin)->test(ResourceIndex::class)
            ->set('name', 'Second Room')->set('capacity', 8)->call('store')->assertHasNoErrors();
        $this->assertSame(2, Resource::whereNull('control_number')->count());

        Livewire::actingAs($admin)->test(ResourceIndex::class)
            ->set('name', 'Numbered Room')->set('capacity', 5)->set('control_number', 'RM-101')->call('store')->assertHasNoErrors();
        Livewire::actingAs($admin)->test(ResourceIndex::class)
            ->set('name', 'Duplicate Number')->set('capacity', 5)->set('control_number', 'RM-101')->call('store')
            ->assertHasErrors(['control_number' => 'unique']);
    }

    public function test_resource_availability_replenishes_after_approved_booking_ends(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $admin->id]);
        $display = Resource::create(['name' => 'Display', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $admin->id]);
        $approved = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'resource_id' => $room->id,
            'title' => 'Approved event', 'start_datetime' => '2026-10-06 09:00',
            'end_datetime' => '2026-10-06 10:00', 'status' => 'approved',
        ]);
        $approved->equipment()->attach($display->id, ['quantity' => 1]);
        $pending = ResourceReservation::create([
            'requester_email' => 'b@example.com', 'title' => 'Pending equipment',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'pending',
        ]);
        $pending->equipment()->attach($display->id, ['quantity' => 1]);

        try {
            \Carbon\Carbon::setTestNow('2026-10-05 09:30:00');
            $component = Livewire::actingAs($admin)->test(ResourceIndex::class);
            $this->assertSame(['total' => 3, 'approved' => 1, 'bookable' => 2], $component->instance()->availabilityNow()[$display->id]);
            $this->assertSame(['total' => 1, 'approved' => 1, 'bookable' => 0], $component->instance()->availabilityNow()[$room->id]);

            \Carbon\Carbon::setTestNow('2026-10-06 09:30:00');
            $this->assertSame(['total' => 3, 'approved' => 1, 'bookable' => 2], $component->instance()->availabilityNow()[$display->id]);
            $this->assertSame(['total' => 1, 'approved' => 1, 'bookable' => 0], $component->instance()->availabilityNow()[$room->id]);

            \Carbon\Carbon::setTestNow('2026-10-06 10:00:00');
            $this->assertSame(3, $component->instance()->availabilityNow()[$display->id]['bookable']);
            $this->assertSame(1, $component->instance()->availabilityNow()[$room->id]['bookable']);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_only_approved_equipment_requests_deplete_the_pool(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $roomA = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);
        $roomB = Resource::create(['name' => 'Room B', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);
        $board = Resource::create(['name' => 'Smart Board', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $owner->id]);
        $service = app(ResourceReservationService::class);
        $base = ['requester_email' => 'booker@example.com', 'title' => 'Workshop', 'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00'];

        $first = $service->create($base + ['resource_id' => $roomA->id, 'equipment_quantities' => [$board->id => 2]]);
        $second = $service->create($base + ['resource_id' => $roomB->id, 'equipment_quantities' => [$board->id => 1]]);
        $this->assertSame(3, $service->availableEquipmentQuantity($board->id, $base['start_datetime'], $base['end_datetime']));

        $first->update(['status' => 'approved']);
        $this->assertSame(1, $service->availableEquipmentQuantity($board->id, $base['start_datetime'], $base['end_datetime']));
        $this->assertSame([], $service->approvedConflictsForReservation($second));

        $first->update(['status' => 'rejected']);
        $this->assertSame(3, $service->availableEquipmentQuantity($board->id, $base['start_datetime'], $base['end_datetime']));
    }

    public function test_adjacent_approved_equipment_bookings_do_not_double_count_for_a_spanning_request(): void
    {
        $owner = User::factory()->create();
        $board = Resource::create(['name' => 'Smart Board', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $owner->id]);
        foreach ([['08:00', '09:00'], ['09:00', '10:00']] as [$start, $end]) {
            $reservation = ResourceReservation::create([
                'title' => 'Approved', 'start_datetime' => "2026-10-06 $start", 'end_datetime' => "2026-10-06 $end", 'status' => 'approved',
            ]);
            $reservation->equipment()->attach($board->id, ['quantity' => 2]);
        }

        $service = app(ResourceReservationService::class);
        $this->assertSame(1, $service->availableEquipmentQuantity($board->id, '2026-10-06 08:00', '2026-10-06 10:00'));
    }

    public function test_reapproving_rejected_booking_checks_availability_again(): void
    {
        $admin = User::factory()->create();
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $admin->id]);
        $service = app(ResourceReservationService::class);
        $rejected = ResourceReservation::create([
            'title' => 'Earlier request', 'resource_id' => $room->id,
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'rejected',
        ]);
        ResourceReservation::create([
            'title' => 'Current booking', 'resource_id' => $room->id,
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'approved',
        ]);

        try {
            $service->approveReservation($rejected, $admin->id);
            $this->fail('A conflicting reservation should not be approved.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('not available', $e->getMessage());
        }
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_public_form_requires_floor_plan_and_event_details(): void
    {
        $owner = User::factory()->create();
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);

        Livewire::test(CreateReservation::class)
            ->set('requester_email', 'booker@example.com')
            ->set('resource_id', $room->id)
            ->set('title', 'Workshop')
            ->set('event_date', '2026-10-06')
            ->set('start_time', '09:00')
            ->set('end_time', '10:00')
            ->call('submitReservation')
            ->assertHasErrors(['floor_plan', 'number_of_pax', 'setup_arrangement', 'contact_number']);
    }

    public function test_public_form_creates_recurring_booking_with_floor_plan_and_equipment_quantity(): void
    {
        Mail::fake();
        Storage::fake(config('filesystems.facility_upload_disk'));
        $owner = User::factory()->create();
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);
        $display = Resource::create(['name' => 'Display', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $owner->id]);

        Livewire::test(CreateReservation::class)
            ->set('requester_email', 'booker@example.com')
            ->set('resource_id', $room->id)
            ->set('title', 'Workshop')
            ->set('number_of_pax', 10)
            ->set('setup_arrangement', 'Workshop')
            ->set('contact_number', '09171234567')
            ->set('equipment_quantities', [$display->id => 2])
            ->set('event_date', '2026-10-06')
            ->set('start_time', '09:00')
            ->set('end_time', '10:00')
            ->set('recurrence', 'weekly')
            ->set('occurrences', 2)
            ->set('floor_plan', UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'))
            ->call('submitReservation')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('resource_reservations', 2);
        $this->assertDatabaseHas('resource_reservations', ['start_datetime' => '2026-10-06 09:00:00', 'end_datetime' => '2026-10-06 10:00:00']);
        $this->assertDatabaseHas('resource_reservation_items', ['resource_id' => $display->id, 'quantity' => 2]);
        $seriesIds = ResourceReservation::pluck('recurrence_series_id')->unique();
        $this->assertCount(1, $seriesIds);
        Storage::disk(config('filesystems.facility_upload_disk'))->assertExists(ResourceReservation::first()->floor_plan_path);
    }

    public function test_public_form_rejects_an_end_time_before_the_start_time(): void
    {
        $owner = User::factory()->create();
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);

        Livewire::test(CreateReservation::class)
            ->set('requester_email', 'booker@example.com')
            ->set('resource_id', $room->id)
            ->set('title', 'Workshop')
            ->set('number_of_pax', 10)
            ->set('setup_arrangement', 'Workshop')
            ->set('contact_number', '09171234567')
            ->set('event_date', '2026-10-06')
            ->set('start_time', '10:00')
            ->set('end_time', '09:00')
            ->set('floor_plan', UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'))
            ->call('submitReservation')
            ->assertHasErrors(['end_time']);

        $this->assertDatabaseCount('resource_reservations', 0);
    }

    public function test_custom_weekly_booking_shows_series_details_to_admin(): void
    {
        Mail::fake();
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $admin->id]);

        Livewire::test(CreateReservation::class)
            ->set('requester_email', 'booker@example.com')
            ->set('resource_id', $room->id)
            ->set('title', 'Weekly workshop')
            ->set('number_of_pax', 10)
            ->set('setup_arrangement', 'Workshop')
            ->set('contact_number', '09171234567')
            ->set('event_date', '2026-10-01')
            ->set('start_time', '09:00')
            ->set('end_time', '10:00')
            ->set('recurrence', 'custom')
            ->set('custom_unit', 'week')
            ->set('custom_interval', 1)
            ->set('custom_weekdays', [2, 4])
            ->set('recurrence_ends', 'after')
            ->set('occurrences', 4)
            ->set('floor_plan', UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'))
            ->call('submitReservation')
            ->assertHasNoErrors();

        $this->assertSame(
            ['2026-10-01', '2026-10-06', '2026-10-08', '2026-10-13'],
            ResourceReservation::orderBy('start_datetime')->get()->map(fn ($reservation) => $reservation->start_datetime->toDateString())->all()
        );
        $this->assertDatabaseHas('resource_reservations', ['recurrence_position' => 4, 'recurrence_total' => 4]);
        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->assertSee('Recurring event')
            ->assertSee('Every 1 week on Tue, Thu');
    }

    public function test_recurring_request_is_accepted_and_flags_only_conflicting_dates(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);
        $service = app(ResourceReservationService::class);
        $existing = $service->create(['requester_email' => 'a@example.com', 'resource_id' => $room->id, 'title' => 'Existing', 'start_datetime' => '2026-10-13 09:00', 'end_datetime' => '2026-10-13 10:00']);
        $existing->update(['status' => 'approved']);

        $series = $service->createSeries(['requester_email' => 'b@example.com', 'resource_id' => $room->id, 'title' => 'Weekly', 'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00'], 'weekly', 3);

        $this->assertDatabaseCount('resource_reservations', 4);
        $this->assertSame([], $service->approvedConflictsForReservation($series[0]));
        $this->assertSame(['Room A'], $service->approvedConflictsForReservation($series[1]));
        $this->assertSame([], $service->approvedConflictsForReservation($series[2]));
    }

    public function test_room_overlap_is_accepted_for_review_but_blocked_at_approval(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $owner->id]);
        $service = app(ResourceReservationService::class);
        $base = ['requester_email' => 'a@example.com', 'resource_id' => $room->id, 'title' => 'Workshop'];
        $first = $service->create($base + ['start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00']);
        $service->create($base + ['start_datetime' => '2026-10-06 10:00', 'end_datetime' => '2026-10-06 11:00']);
        $overlap = $service->create($base + ['start_datetime' => '2026-10-06 09:30', 'end_datetime' => '2026-10-06 10:30']);
        $this->assertDatabaseCount('resource_reservations', 3);
        $this->assertSame([], $service->approvedConflictsForReservation($overlap));
        $first->update(['status' => 'approved']);
        $this->assertSame(['Room A'], $service->approvedConflictsForReservation($overlap));

        $this->expectException(\Exception::class);
        $service->approveReservation($overlap, $owner->id);
    }

    public function test_public_conflict_warning_and_admin_tag_update_with_approved_bookings(): void
    {
        Mail::fake();
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $admin->id]);
        $approved = ResourceReservation::create([
            'title' => 'Existing', 'resource_id' => $room->id,
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00', 'status' => 'approved',
        ]);

        Livewire::test(CreateReservation::class)
            ->set('requester_email', 'booker@example.com')
            ->set('resource_id', $room->id)
            ->set('title', 'Conflicting workshop')
            ->set('number_of_pax', 10)
            ->set('setup_arrangement', 'Workshop')
            ->set('contact_number', '09171234567')
            ->set('event_date', '2026-10-06')
            ->set('start_time', '09:30')
            ->set('end_time', '10:30')
            ->call('checkSchedule')
            ->assertSet('scheduleConflictWarnings', ['2026-10-06' => ['Room A']])
            ->set('end_time', '11:00')
            ->assertSet('scheduleConflictWarnings', [])
            ->set('end_time', '10:30')
            ->call('checkSchedule')
            ->assertSet('scheduleConflictWarnings', ['2026-10-06' => ['Room A']])
            ->set('floor_plan', UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'))
            ->call('submitReservation')
            ->assertHasNoErrors();

        $request = ResourceReservation::where('title', 'Conflicting workshop')->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame([$approved->id], app(ResourceReservationService::class)
            ->blockingApprovedReservations($request)->pluck('id')->all());
        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->assertSee('Conflict with approved booking')
            ->call('showConflictManager', $request->id)
            ->assertSee('Manage conflict for request #' . $request->id)
            ->assertSee('Approved #' . $approved->id)
            ->assertSee('Edit approved booking')
            ->call('selectForDecision', $request->id)
            ->assertSee('Cannot approve while this conflicts')
            ->call('confirmApprove');
        $this->assertSame('pending', $request->fresh()->status);

        $approved->update(['status' => 'rejected']);
        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->assertDontSee('Conflict with approved booking');
    }

    public function test_admin_can_sort_reservations_by_event_date_independent_of_submission_order(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));

        ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Later event submitted first',
            'start_datetime' => '2026-10-09 09:00', 'end_datetime' => '2026-10-09 10:00',
            'status' => 'approved',
        ]);
        ResourceReservation::create([
            'requester_email' => 'b@example.com', 'title' => 'Earlier event submitted second',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->set('statusFilter', 'approved')
            ->assertSeeInOrder(['Earlier event submitted second', 'Later event submitted first'])
            ->set('eventDateSort', 'desc')
            ->assertSeeInOrder(['Later event submitted first', 'Earlier event submitted second']);
    }

    public function test_recurring_series_uses_its_next_upcoming_event_for_sorting(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $seriesId = (string) \Illuminate\Support\Str::uuid();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 12:00:00'));

        ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Recurring workshop',
            'start_datetime' => '2026-10-01 09:00', 'end_datetime' => '2026-10-01 10:00',
            'status' => 'approved', 'recurrence_series_id' => $seriesId,
            'recurrence_label' => 'Weekly', 'recurrence_position' => 1, 'recurrence_total' => 2,
        ]);
        ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Recurring workshop',
            'start_datetime' => '2026-10-15 09:00', 'end_datetime' => '2026-10-15 10:00',
            'status' => 'pending', 'recurrence_series_id' => $seriesId,
            'recurrence_label' => 'Weekly', 'recurrence_position' => 2, 'recurrence_total' => 2,
        ]);
        ResourceReservation::create([
            'requester_email' => 'b@example.com', 'title' => 'Single booking',
            'start_datetime' => '2026-10-12 09:00', 'end_datetime' => '2026-10-12 10:00',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->set('statusFilter', 'all')
            ->assertSeeInOrder(['Single booking', 'Recurring workshop'])
            ->assertSee('Next event: Oct 15, 2026')
            ->assertSee('For Approval: 1')
            ->assertSee('Approved: 1');
    }

    public function test_for_approval_sorts_and_groups_by_request_date(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $older = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Older request, later event',
            'start_datetime' => '2026-10-20 09:00', 'end_datetime' => '2026-10-20 10:00',
            'status' => 'pending',
        ]);
        $newer = ResourceReservation::create([
            'requester_email' => 'b@example.com', 'title' => 'Newer request, earlier event',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'pending',
        ]);
        DB::table('resource_reservations')->where('id', $older->id)->update(['created_at' => '2026-09-25 09:00:00']);
        DB::table('resource_reservations')->where('id', $newer->id)->update(['created_at' => '2026-10-01 09:00:00']);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->assertSee('Sort by request date')
            ->assertSeeInOrder(['October 2026', 'Newer request, earlier event', 'September 2026', 'Older request, later event'])
            ->set('requestDateSort', 'asc')
            ->assertSeeInOrder(['September 2026', 'Older request, later event', 'October 2026', 'Newer request, earlier event']);
    }

    public function test_deleting_one_reservation_soft_deletes_only_that_reservation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $first = ResourceReservation::create(['requester_email' => 'a@example.com', 'title' => 'First', 'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00', 'status' => 'pending']);
        $second = ResourceReservation::create(['requester_email' => 'b@example.com', 'title' => 'Second', 'start_datetime' => '2026-10-07 09:00', 'end_datetime' => '2026-10-07 10:00', 'status' => 'pending']);

        $component = Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForDelete', $first->id)
            ->assertSet('deleteId', $first->id)
            ->assertSet('showDeleteModal', true)
            ->call('confirmDelete')
            ->assertSet('deleteId', null)
            ->assertSet('showDeleteModal', false)
            ->call('confirmDelete')
            ->assertSet('deleteId', null);

        $this->assertSoftDeleted('resource_reservations', ['id' => $first->id]);
        $this->assertDatabaseHas('resource_reservations', ['id' => $second->id, 'deleted_at' => null]);

        $component->call('selectForDelete', $second->id)
            ->assertSet('deleteId', $second->id)
            ->call('cancelDelete')
            ->assertSet('deleteId', null)
            ->assertSet('showDeleteModal', false)
            ->call('confirmDelete');
        $this->assertDatabaseHas('resource_reservations', ['id' => $second->id, 'deleted_at' => null]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->set('statusFilter', 'deleted')
            ->call('restoreReservation', $first->id);
        $this->assertDatabaseHas('resource_reservations', ['id' => $first->id, 'deleted_at' => null, 'status' => 'pending']);
    }

    public function test_admin_edit_of_approved_booking_keeps_it_approved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $admin->id]);
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'resource_id' => $room->id,
            'title' => 'Original', 'start_datetime' => '2026-10-06 09:00',
            'end_datetime' => '2026-10-06 10:00', 'status' => 'approved',
            'approved_by' => $admin->id, 'approved_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForEdit', $reservation->id)
            ->set('editTitle', 'Revised')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertSame('approved', $reservation->fresh()->status);
        $this->assertSame('Revised', $reservation->fresh()->title);
        $this->assertSame($admin->id, $reservation->fresh()->approved_by);
        $this->assertNotNull($reservation->fresh()->approved_at);
        $this->assertDatabaseHas('resource_reservation_edits', ['reservation_id' => $reservation->id, 'edited_by' => $admin->id]);
    }

    public function test_edit_shows_only_requested_equipment_and_can_add_another_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $requested = Resource::create(['name' => 'Requested Display', 'type' => 'equipment', 'total_quantity' => 2, 'created_by' => $admin->id]);
        $other = Resource::create(['name' => 'Other Display', 'type' => 'equipment', 'total_quantity' => 3, 'created_by' => $admin->id]);
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Workshop',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'pending',
        ]);
        $reservation->equipment()->attach($requested->id, ['quantity' => 1]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForEdit', $reservation->id)
            ->assertSet('editEquipment', [$requested->id => 1])
            ->assertSee('Requested Requested Display')
            ->assertDontSee('Requested Other Display')
            ->set('editEquipmentToAdd', $other->id)
            ->call('addEditEquipment')
            ->assertSet('editEquipment', [$requested->id => 1, $other->id => 1]);
    }

    public function test_overdue_bill_remains_visible_for_manual_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Workshop',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'approved', 'billing_status' => 'billed',
            'soa_sent_at' => today()->subDays(16), 'payment_due_at' => today()->subDay(),
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)->set('statusFilter', 'approved')->assertSee('Overdue');

        $reservation->update(['billing_status' => 'paid', 'paid_at' => now()]);
        Livewire::actingAs($admin)->test(ReservationIndex::class)->set('statusFilter', 'approved')->assertDontSee('Overdue');
    }

    public function test_admin_uploads_soa_then_marks_reservation_paid(): void
    {
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Workshop',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForBilling', $reservation->id)
            ->set('soaSentDate', '2026-09-28')
            ->call('markBilled')
            ->assertHasErrors(['soaFile'])
            ->set('soaFile', UploadedFile::fake()->create('soa.pdf', 100, 'application/pdf'))
            ->assertHasNoErrors()
            ->call('markBilled')
            ->assertHasNoErrors();

        $reservation->refresh();
        $this->assertSame('billed', $reservation->billing_status);
        $this->assertSame('2026-10-13', $reservation->payment_due_at->toDateString());
        Storage::disk(config('filesystems.facility_upload_disk'))->assertExists($reservation->soa_path);

        Livewire::actingAs($admin)->test(ReservationIndex::class)->call('markPaid', $reservation->id);
        $this->assertSame('paid', $reservation->fresh()->billing_status);
    }

    public function test_admin_can_mark_billed_reservation_paid_while_editing(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $room = Resource::create(['name' => 'Room A', 'type' => 'room', 'capacity' => 20, 'created_by' => $admin->id]);
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'resource_id' => $room->id,
            'title' => 'Workshop', 'start_datetime' => '2026-10-06 09:00',
            'end_datetime' => '2026-10-06 10:00', 'status' => 'approved',
            'billing_status' => 'billed', 'soa_path' => 'reservations/soa/example.pdf',
            'soa_sent_at' => '2026-09-28', 'payment_due_at' => '2026-10-13',
            'approved_by' => $admin->id, 'approved_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForEdit', $reservation->id)
            ->assertSet('editBillingStatus', 'billed')
            ->set('editBillingStatus', 'paid')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $reservation->refresh();
        $this->assertSame('approved', $reservation->status);
        $this->assertSame('paid', $reservation->billing_status);
        $this->assertNotNull($reservation->paid_at);
        $this->assertSame('2026-10-13', $reservation->payment_due_at->toDateString());
    }

    public function test_approved_reservation_cannot_be_deleted_until_soa_is_removed(): void
    {
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $path = 'reservations/soa/existing.pdf';
        Storage::disk(config('filesystems.facility_upload_disk'))->put($path, 'test SOA');
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Workshop',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'approved', 'billing_status' => 'billed', 'soa_path' => $path,
            'soa_sent_at' => '2026-09-28', 'payment_due_at' => '2026-10-13',
        ]);

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForDelete', $reservation->id)
            ->assertSet('deleteId', null)
            ->call('selectForSoaRemoval', $reservation->id)
            ->assertSet('showRemoveSoaModal', true)
            ->call('confirmSoaRemoval')
            ->assertSet('showRemoveSoaModal', false)
            ->call('selectForDelete', $reservation->id)
            ->assertSet('deleteId', $reservation->id)
            ->call('confirmDelete');

        Storage::disk(config('filesystems.facility_upload_disk'))->assertMissing($path);
        $this->assertSoftDeleted('resource_reservations', ['id' => $reservation->id]);
        $this->assertDatabaseHas('resource_reservations', ['id' => $reservation->id, 'soa_path' => null, 'billing_status' => 'unbilled']);
    }

    public function test_replacing_paid_soa_keeps_payment_and_removes_old_file(): void
    {
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $oldPath = 'reservations/soa/old.pdf';
        Storage::disk(config('filesystems.facility_upload_disk'))->put($oldPath, 'old SOA');
        $reservation = ResourceReservation::create([
            'requester_email' => 'a@example.com', 'title' => 'Workshop',
            'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00',
            'status' => 'approved', 'billing_status' => 'paid', 'soa_path' => $oldPath,
            'soa_sent_at' => '2026-09-28', 'payment_due_at' => '2026-10-13', 'paid_at' => now(),
        ]);
        $paidAt = $reservation->paid_at;

        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('selectForBilling', $reservation->id)
            ->set('soaFile', UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'))
            ->call('markBilled')
            ->assertHasNoErrors();

        $reservation->refresh();
        $this->assertSame('paid', $reservation->billing_status);
        $this->assertTrue($reservation->paid_at->equalTo($paidAt));
        $this->assertNotSame($oldPath, $reservation->soa_path);
        Storage::disk(config('filesystems.facility_upload_disk'))->assertMissing($oldPath);
        Storage::disk(config('filesystems.facility_upload_disk'))->assertExists($reservation->soa_path);
    }
}
