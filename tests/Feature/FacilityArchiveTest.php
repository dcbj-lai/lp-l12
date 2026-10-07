<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_finished_check_uses_manila_time_at_the_event_end_boundary(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $event = ResourceReservation::create([
            'title' => 'Manila afternoon meeting',
            'status' => 'approved',
            'billing_status' => 'paid',
            'start_datetime' => '2026-10-07 13:00:00',
            'end_datetime' => '2026-10-07 15:00:00',
        ]);

        $this->travelTo(\Carbon\Carbon::parse('2026-10-07 06:59:59', 'UTC')->setTimezone('Asia/Manila'));
        $this->assertSame('14:59:59', now()->format('H:i:s'));
        $this->assertFalse($event->is_archived);
        $this->assertFalse(ResourceReservation::archived()->whereKey($event->id)->exists());

        $this->travelTo(\Carbon\Carbon::parse('2026-10-07 07:00:00', 'UTC')->setTimezone('Asia/Manila'));
        $this->assertSame('15:00:00', now()->format('H:i:s'));
        $this->assertTrue($event->is_archived);
        $this->assertTrue(ResourceReservation::archived()->whereKey($event->id)->exists());

        $event->update(['billing_status' => 'unbilled']);
        Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->set('finishId', $event->id)->call('confirmFinishedEvent');
        $this->assertNotNull($event->fresh()->finished_confirmed_at);
    }

    public function test_paid_finished_dates_archive_but_unpaid_and_future_dates_do_not(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 7)->setTime(12, 0));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $data = ['status' => 'approved', 'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00'];
        $paid = ResourceReservation::create($data + ['title' => 'Paid finished', 'billing_status' => 'paid']);
        $billed = ResourceReservation::create($data + ['title' => 'Unpaid finished', 'billing_status' => 'billed']);
        $future = ResourceReservation::create(array_merge($data, ['title' => 'Paid future', 'billing_status' => 'paid', 'end_datetime' => '2026-10-08 10:00']));
        $component = Livewire::actingAs($admin)->test(ReservationIndex::class)->set('statusFilter', 'archive');
        $ids = $component->instance()->reservations->flatten(1)->pluck('id')->all();
        $this->assertSame([$paid->id], $ids);
        $component->set('statusFilter', 'approved');
        $this->assertNotContains($paid->id, $component->instance()->reservations->flatten(1)->pluck('id'));
        $component->set('statusFilter', 'billed');
        $this->assertSame([$billed->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'paid');
        $this->assertSame([$future->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
    }

    public function test_manual_confirmation_requires_finished_approved_unbilled_event_without_soa(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 7)->setTime(12, 0));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $data = ['title' => 'Free event', 'status' => 'approved', 'billing_status' => 'unbilled', 'start_datetime' => '2026-10-06 09:00', 'end_datetime' => '2026-10-06 10:00'];
        $free = ResourceReservation::create($data);
        $component = Livewire::actingAs($admin)->test(ReservationIndex::class)->set('finishId', $free->id)->call('confirmFinishedEvent');
        $this->assertTrue($free->fresh()->is_archived);
        $this->assertSame($admin->id, $free->fresh()->finished_confirmed_by);
        foreach ([['status' => 'pending'], ['end_datetime' => '2026-10-08 10:00'], ['soa_path' => 'soa/test.pdf'], ['billing_status' => 'billed']] as $override) {
            $blocked = ResourceReservation::create(array_merge($data, $override));
            $component->set('finishId', $blocked->id)->call('confirmFinishedEvent');
            $this->assertNull($blocked->fresh()->finished_confirmed_at);
        }
    }
}
