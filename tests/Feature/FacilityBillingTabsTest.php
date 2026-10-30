<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityBillingTabsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        return $admin;
    }

    private function booking(array $data = []): ResourceReservation
    {
        return ResourceReservation::create($data + ['title' => 'Billing tab test', 'status' => 'approved', 'billing_status' => 'unbilled',
            'payment_required' => true, 'start_datetime' => now()->subHours(3), 'end_datetime' => now()->subHours(2)]);
    }

    public function test_completion_requires_explicit_waiver_to_enter_done(): void
    {
        $admin = $this->admin();
        $required = $this->booking();
        $free = $this->booking();
        Livewire::actingAs($admin)->test(ReservationIndex::class)->call('selectForFinish', $required->id)
            ->assertSet('finishNoPayment', false)->set('finishNotes', 'Ready for billing')->call('confirmFinishedEvent')
            ->assertHasNoErrors()->assertSet('statusFilter', 'approved')->assertSet('approvedFilter', 'unbilled');
        $this->assertFalse($required->fresh()->is_archived);
        $this->assertSame('Ready for billing', $required->fresh()->completion_notes);
        Livewire::actingAs($admin)->test(ReservationIndex::class)->call('selectForFinish', $free->id)
            ->set('finishNoPayment', true)->call('confirmFinishedEvent')->assertSet('statusFilter', 'archive');
        $this->assertTrue($free->fresh()->is_archived);
        $this->assertFalse($free->fresh()->payment_required);
    }

    public function test_tabs_and_done_filters_separate_billing_states(): void
    {
        $admin = $this->admin();
        $unsent = $this->booking(['soa_path' => 'dummy.pdf', 'soa_email_pending' => true]);
        $finished = $this->booking(['finished_confirmed_at' => now()]);
        $replacement = $this->booking(['soa_path' => 'replacement.pdf', 'soa_sent_at' => today(), 'soa_email_pending' => true, 'billing_status' => 'billed']);
        $billed = $this->booking(['billing_status' => 'billed', 'soa_path' => 'dummy.pdf', 'soa_sent_at' => today()]);
        $paid = $this->booking(['billing_status' => 'paid', 'paid_at' => now(), 'finished_confirmed_at' => now()]);
        $free = $this->booking(['finished_confirmed_at' => now(), 'payment_required' => false]);
        $upcomingPaid = $this->booking(['billing_status' => 'paid', 'paid_at' => now(), 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour()]);
        $component = Livewire::actingAs($admin)->test(ReservationIndex::class)->set('statusFilter', 'approved')->set('approvedFilter', 'unbilled');
        $this->assertEqualsCanonicalizing([$unsent->id, $finished->id, $replacement->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'approved')->set('approvedFilter', 'billed');
        $this->assertSame([$billed->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'archive');
        $this->assertEqualsCanonicalizing([$paid->id, $free->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('doneFilter', 'paid');
        $this->assertSame([$paid->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('doneFilter', 'no_payment');
        $this->assertSame([$free->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'approved')->set('approvedFilter', 'paid');
        $this->assertSame([$upcomingPaid->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('approvedFilter', 'all');
        $this->assertEqualsCanonicalizing([$unsent->id, $finished->id, $replacement->id, $billed->id, $upcomingPaid->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
    }

    public function test_done_waits_for_pending_soa_without_polling(): void
    {
        $admin = $this->admin();
        $paid = $this->booking(['billing_status' => 'paid', 'paid_at' => now()]);
        $replacement = $this->booking(['billing_status' => 'paid', 'paid_at' => now(), 'soa_email_pending' => true, 'soa_path' => 'replacement.pdf']);
        $ended = $this->booking();
        $component = Livewire::actingAs($admin)->test(ReservationIndex::class)->set('statusFilter', 'archive');
        $this->assertSame([$paid->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->set('statusFilter', 'approved')->set('approvedFilter', 'paid');
        $this->assertSame([$replacement->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $component->assertSee('SOA pending')->assertDontSee('wire:poll', false);
        $component->set('approvedFilter', 'unbilled')->assertSee('Confirm finished');
        $this->assertSame([$ended->id], $component->instance()->reservations->flatten(1)->pluck('id')->all());
        $replacement->update(['soa_email_pending' => false]);
        $this->assertTrue($replacement->fresh()->is_archived);
    }

    public function test_billed_completion_stays_billed_and_cannot_be_waived(): void
    {
        $admin = $this->admin();
        $billed = $this->booking(['billing_status' => 'billed', 'soa_path' => 'dummy.pdf', 'soa_sent_at' => today()]);
        Livewire::actingAs($admin)->test(ReservationIndex::class)->call('selectForFinish', $billed->id)
            ->set('finishNoPayment', true)->call('confirmFinishedEvent');
        $this->assertNull($billed->fresh()->finished_confirmed_at);
        Livewire::actingAs($admin)->test(ReservationIndex::class)->call('selectForFinish', $billed->id)
            ->call('confirmFinishedEvent')->assertSet('statusFilter', 'approved');
        $this->assertFalse($billed->fresh()->is_archived);
    }
}


