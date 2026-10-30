<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityBillingAttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_migration_keeps_public_originals_and_moves_all_references(): void
    {
        config(['filesystems.facility_upload_disk' => 'public', 'filesystems.facility_billing_disk' => 'local']);
        Storage::fake('public');
        Storage::fake('local');
        $old = 'reservations/soa/old.pdf';
        Storage::disk('public')->put($old, 'original PDF bytes');
        $reservation = ResourceReservation::create(['title' => 'Legacy', 'status' => 'approved', 'soa_path' => $old,
            'start_datetime' => now(), 'end_datetime' => now()->addHour()]);
        $reservation->soaRevisions()->create(['previous_path' => $old, 'replacement_path' => $old, 'reason' => 'Legacy revision']);
        $reservation->delete();
        $this->artisan('facilities:privatize-billing')->assertSuccessful();
        $this->assertSame($old, $reservation->fresh()->soa_path);
        $this->artisan('facilities:privatize-billing', ['--apply' => true])->assertSuccessful();
        $new = $reservation->fresh()->soa_path;
        $this->assertStringStartsWith('facility-billing/legacy/', $new);
        $this->assertSame('original PDF bytes', Storage::disk('local')->get($new));
        Storage::disk('public')->assertExists($old);
        $this->assertSame($new, $reservation->soaRevisions()->first()->previous_path);
        $this->assertTrue($reservation->fresh()->trashed());
    }

    public function test_billing_files_are_private_and_only_accessible_to_admins(): void
    {
        config(['filesystems.facility_billing_disk' => 'local']);
        Storage::fake('local');
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::findOrCreate('facility.viewer', 'web'));
        $reservation = ResourceReservation::create(['title' => 'Private billing', 'status' => 'approved',
            'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour()]);
        Livewire::actingAs($admin)->test(ReservationIndex::class)->call('selectForBilling', $reservation->id)
            ->set('soaFile', UploadedFile::fake()->create('soa.pdf', 10, 'application/pdf'))
            ->call('markBilled')->assertHasNoErrors();
        $path = $reservation->fresh()->soa_path;
        $this->assertStringStartsWith('facility-billing/soa/', $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk(config('filesystems.facility_upload_disk'))->assertMissing($path);
        $url = route('resources.reservations.billing-file', [$reservation, 'soa']);
        $this->actingAs($admin)->get($url)->assertOk()->assertHeader('cache-control', 'no-store, private');
        $this->actingAs($viewer)->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $proof = $reservation->paymentProofs()->create(['path' => 'facility-billing/payments/test.pdf', 'original_name' => 'test.pdf']);
        Storage::disk('local')->put($proof->path, 'dummy');
        $proofUrl = route('resources.reservations.billing-file', [$reservation, 'payment', $proof->id]);
        $this->actingAs($admin)->get($proofUrl)->assertOk();
        Livewire::actingAs($admin)->test(ReservationIndex::class)->set('statusFilter', 'approved')->set('approvedFilter', 'unbilled')
            ->assertSee($proofUrl, false)
            ->assertDontSee('/storage/facility-billing/payments/test.pdf', false);
        $other = ResourceReservation::create(['title' => 'Other event', 'status' => 'approved',
            'start_datetime' => now(), 'end_datetime' => now()->addHour()]);
        $this->get(route('resources.reservations.billing-file', [$other, 'payment', $proof->id]))->assertNotFound();
        $this->actingAs($viewer)->get($proofUrl)->assertForbidden();
    }
}

