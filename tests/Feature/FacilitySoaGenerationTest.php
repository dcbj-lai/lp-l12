<?php

namespace Tests\Feature;

use App\Jobs\SendFacilityBillingEmail;
use App\Livewire\Resources\BillingConfiguration;
use App\Livewire\Resources\CreateSoa;
use App\Models\FacilityBillingEmail;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\FacilityBillingEmailService;
use App\Services\FacilitySoaGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilitySoaGenerationTest extends TestCase
{
    use RefreshDatabase;

    private ResourceReservation $booking;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.facility_billing_disk' => 'local']);
        Storage::fake('local');
        Storage::fake(config('filesystems.facility_upload_disk'));
        Mail::fake();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($this->admin);
        $room = Resource::create(['name' => 'SOA Room A', 'type' => 'room', 'capacity' => 10, 'created_by' => $this->admin->id]);
        $roomB = Resource::create(['name' => 'SOA Room B', 'type' => 'room', 'capacity' => 10, 'created_by' => $this->admin->id]);
        $this->booking = ResourceReservation::create(['title' => 'SOA pricing event', 'resource_id' => $room->id,
            'requester_name' => 'Requestor', 'requester_email' => 'soa@example.test', 'status' => 'approved', 'billing_status' => 'unbilled',
            'start_datetime' => '2027-01-10 09:00', 'end_datetime' => '2027-01-10 14:00']);
        $this->booking->rooms()->attach([$room->id, $roomB->id]);
    }

    private function data(): array
    {
        return ['client_name' => 'Example Client', 'business_address' => '', 'event_type' => 'ministry',
            'quantities' => array_fill_keys(array_keys(FacilitySoaGenerator::LABELS), 0),
            'hours' => array_fill_keys(FacilitySoaGenerator::STAFF, 5)];
    }

    public function test_mixed_ot_night_rates_and_editing_preserve_selected_hours(): void
    {
        $data = $this->data();
        $data['quantities']['security'] = 1;
        $data['hours']['security'] = 0;
        $data['quantities']['logistics'] = 2;
        $data['extra_hours'] = ['security' => ['night' => 2], 'logistics' => ['sunday_ot' => 1.5]];
        $service = app(FacilitySoaGenerator::class);
        $quote = $service->quote($this->booking, $data);
        $this->assertSame(777199, $quote['total_cents']); // 4800 + 2000 + 243.98 + 728.01
        $document = $service->create($this->booking->id, $data);
        Livewire::test(CreateSoa::class)->call('open', $this->booking->id)
            ->assertSet('hours.security', 0)->assertSet('hours.logistics', 5)
            ->assertSet('extra_hours.security.night', 2)->assertSet('extra_hours.logistics.sunday_ot', 1.5);
        DB::table('facility_charge_rates')->where('code', 'security_night')->update(['amount_cents' => 99999]);
        $this->assertSame(777199, $document->fresh()->snapshot['total_cents']);
    }

    public function test_extra_rates_reject_missing_unknown_negative_and_missing_quantity(): void
    {
        $service = app(FacilitySoaGenerator::class);
        foreach ([['security' => ['weekday_ot' => 1]], ['security' => ['unknown' => 1]],
            ['security' => ['night' => -1]], ['security' => ['night' => 1.001]], ['cups' => ['night' => 1]]] as $extra) {
            $data = $this->data();
            $data['quantities']['security'] = 1;
            $data['extra_hours'] = $extra;
            try { $service->quote($this->booking, $data); $this->fail('Invalid staff rate accepted.'); }
            catch (\Illuminate\Validation\ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $data = $this->data();
        $data['extra_hours'] = ['security' => ['night' => 1]];
        try { $service->quote($this->booking, $data); $this->fail('Missing staff quantity accepted.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('quantities.security', $e->errors()); }
        $data['quantities']['security'] = 1;
        $data['hours']['security'] = 0;
        DB::table('facility_charge_rates')->where('code', 'security_night')->update(['amount_cents' => 0]);
        $this->assertSame(480000, $service->quote($this->booking, $data)['total_cents']);
    }

    public function test_full_four_hour_blocks_multiple_rooms_staff_and_consumable_rates(): void
    {
        $service = app(FacilitySoaGenerator::class);
        $data = $this->data();
        $data['quantities']['logistics'] = 2;
        $data['quantities']['cups'] = 10;
        DB::table('facility_charge_rates')->where('code', 'cups')->update(['amount_cents' => 250]);
        $quote = $service->quote($this->booking, $data);
        $this->assertSame(2, $quote['blocks']);
        $this->assertSame(2, $quote['rooms']);
        $this->assertSame(682500, $quote['total_cents']); // 4800 venue + 2000 staff + 25 cups
        $data['event_type'] = 'non_ministry';
        $this->assertSame(1162500, $service->quote($this->booking, $data)['total_cents']);
        $this->booking->update(['end_datetime' => '2027-01-10 13:00']);
        $this->assertSame(1, $service->quote($this->booking, $data)['blocks']);
        $this->booking->update(['end_datetime' => '2027-01-10 13:00:01']);
        $this->assertSame(2, $service->quote($this->booking, $data)['blocks']);
    }

    public function test_generator_validates_client_and_rates_and_retains_a_saved_snapshot(): void
    {
        DB::table('facility_charge_rates')->where('code', 'security')->update(['amount_cents' => null]);
        Livewire::test(CreateSoa::class)->call('open', $this->booking->id)->set('client_name', '')
            ->set('event_type', 'ministry')->call('save')->assertHasErrors('client_name');
        $component = Livewire::test(CreateSoa::class)->call('open', $this->booking->id)->set('client_name', 'Client')
            ->set('event_type', 'ministry')->set('quantities.security', 1)->call('save')->assertHasErrors('quantities.security');
        $component->set('quantities.security', 0)->call('save')->assertHasNoErrors();
        $document = \App\Models\FacilityGeneratedSoa::firstOrFail();
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($document->path));
        $this->assertTrue($this->booking->fresh()->soa_email_pending);
        $this->assertSame('approved', $this->booking->fresh()->billing_view);
        $this->assertSame('SOA Room A, SOA Room B', $document->snapshot['venue']);
        Mail::assertNothingSent();
        DB::table('facility_charge_rates')->where('code', 'logistics')->update(['amount_cents' => 90000]);
        $this->assertSame(480000, $document->fresh()->snapshot['total_cents']);
    }

    public function test_send_day_controls_issue_and_due_dates_and_reminders_do_not_reset_them(): void
    {
        Queue::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 12:00', 'Asia/Manila'));
        $document = app(FacilitySoaGenerator::class)->create($this->booking->id, $this->data());
        $this->assertSame('2026-11-20', $document->due_date->toDateString());
        app(FacilityBillingEmailService::class)->queue($this->booking->id, 'soa');
        $message = FacilityBillingEmail::firstOrFail();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-02 09:00', 'Asia/Manila'));
        (new SendFacilityBillingEmail($message->id))->handle();
        $this->assertSame('2026-11-02', $document->fresh()->issue_date->toDateString());
        $this->assertSame('2026-11-23', $this->booking->fresh()->payment_due_at->toDateString());
        $this->assertSame('Nov 23, 2026', $message->fresh()->snapshot['due_date']);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-16 09:00', 'Asia/Manila'));
        app(FacilityBillingEmailService::class)->queue($this->booking->id, 'reminder');
        (new SendFacilityBillingEmail(FacilityBillingEmail::latest('id')->first()->id))->handle();
        $this->assertSame('2026-11-02', $document->fresh()->issue_date->toDateString());
        $this->assertSame('2026-11-23', $this->booking->fresh()->payment_due_at->toDateString());
    }

    public function test_replacement_requires_reason_preserves_prior_document_and_paid_generation_is_blocked(): void
    {
        $service = app(FacilitySoaGenerator::class);
        $first = $service->create($this->booking->id, $this->data());
        Livewire::test(CreateSoa::class)->call('open', $this->booking->id)->set('event_type', 'ministry')
            ->call('save')->assertHasErrors('replacement_reason');
        $second = $service->create($this->booking->id, $this->data() + ['replacement_reason' => 'Updated details']);
        $this->assertNotSame($first->path, $second->path);
        Storage::disk('local')->assertExists($first->path);
        $this->assertSame($first->path, $this->booking->soaRevisions()->first()->previous_path);
        $this->booking->update(['billing_status' => 'paid', 'paid_at' => now()]);
        Livewire::test(CreateSoa::class)->call('open', $this->booking->id)->assertStatus(422);
    }

    public function test_configuration_is_admin_only_and_rejects_negative_rates(): void
    {
        Livewire::test(BillingConfiguration::class)->set('rates.security', '-1')->call('save')->assertHasErrors('rates.security')
            ->set('rates.security', '250.50')->call('save')->assertHasNoErrors();
        $this->assertSame(25050, (int) DB::table('facility_charge_rates')->where('code', 'security')->value('amount_cents'));
        $this->actingAs(User::factory()->create())->get(route('resources.billing-configuration'))->assertForbidden();
    }

    public function test_generated_soa_inline_edit_restores_saved_inputs_and_creates_a_revision(): void
    {
        $data = $this->data();
        $data['quantities']['logistics'] = 2;
        $data['client_name'] = 'Saved SOA Client';
        $data['business_address'] = 'Saved address';
        $first = app(FacilitySoaGenerator::class)->create($this->booking->id, $data);
        $component = Livewire::test(CreateSoa::class)->call('open', $this->booking->id)
            ->assertSet('editing', true)->assertSet('client_name', 'Saved SOA Client')
            ->assertSet('business_address', 'Saved address')->assertSet('event_type', 'ministry')
            ->assertSet('quantities.logistics', 2)->assertSet('hours.logistics', 5);
        $component->set('quantities.logistics', 3)->set('replacement_reason', 'Updated staff quantity')->call('save')->assertHasNoErrors();
        $replacement = \App\Models\FacilityGeneratedSoa::latest('id')->firstOrFail();
        $this->assertNotSame($first->path, $replacement->path);
        $this->assertSame(780000, $replacement->snapshot['total_cents']);
        Storage::disk('local')->assertExists($first->path);
        $this->assertSame('Updated staff quantity', $this->booking->soaRevisions()->first()->reason);
        $rendered = Livewire::test(\App\Livewire\Resources\ReservationIndex::class)->set('statusFilter', 'approved')->set('approvedFilter', 'unbilled');
        $rendered->assertSee($replacement->snapshot['filename'])->assertSee('Edit SOA for reservation #'.$this->booking->id)
            ->assertSee('Delete SOA for reservation #'.$this->booking->id);
        $this->booking->update(['billing_status' => 'paid', 'paid_at' => now()]);
        $rendered->set('statusFilter', 'approved')->set('approvedFilter', 'paid')->assertSee('Replace SOA for reservation #'.$this->booking->id);
        $html = $rendered->html();
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*aria-label="Delete SOA for reservation #'.$this->booking->id.'"|<button[^>]*aria-label="Delete SOA for reservation #'.$this->booking->id.'"[^>]*disabled/', $html);
    }

    public function test_generated_filename_matches_event_and_is_kept_for_downloads_and_email(): void
    {
        Queue::fake();
        $document = app(FacilitySoaGenerator::class)->create($this->booking->id, $this->data());
        $filename = '011027 - SOA pricing event.pdf';
        $this->assertSame($filename, basename($document->path));
        $this->assertSame($filename, $document->snapshot['filename']);
        $this->booking->update(['title' => 'Changed event title']);
        $this->get(route('resources.reservations.billing-file', [$this->booking, 'soa']))->assertDownload($filename);
        app(FacilityBillingEmailService::class)->queue($this->booking->id, 'soa');
        $message = FacilityBillingEmail::firstOrFail();
        $this->assertSame($filename, $message->snapshot['attachment_name']);
        $mail = (new \App\Mail\FacilityBillingMail($message->snapshot, 'soa'))->build();
        $this->assertSame($filename, $mail->diskAttachments[0]['name']);
    }

    public function test_soa_filenames_handle_unsafe_characters_and_long_event_names(): void
    {
        $this->booking->title = "Planning / Workshop: Q4\\Team\n";
        $filename = \App\Support\FacilitySoaFilename::forReservation($this->booking);
        $this->assertSame('011027 - Planning - Workshop- Q4-Team.pdf', $filename);
        $this->booking->title = str_repeat('会議', 200);
        $filename = \App\Support\FacilitySoaFilename::forReservation($this->booking);
        $this->assertLessThanOrEqual(153, strlen($filename));
        $this->assertTrue(mb_check_encoding($filename, 'UTF-8'));
        $this->booking->title = '   ';
        $this->assertSame('011027 - Event.docx', \App\Support\FacilitySoaFilename::forReservation($this->booking, 'docx'));
    }

    public function test_holiday_rates_are_separate_nullable_amounts_and_validate_precision(): void
    {
        $this->assertSame(11594, (int) DB::table('facility_charge_rates')->where('code', 'security')->value('amount_cents'));
        $this->assertSame(12199, (int) DB::table('facility_charge_rates')->where('code', 'security_night')->value('amount_cents'));
        $this->assertSame(34, DB::table('facility_charge_rates')->whereNotIn('code', array_keys(FacilitySoaGenerator::LABELS))->count());
        $this->assertSame(4308, (int) DB::table('facility_charge_rates')->where('code', 'logistics_special')->value('amount_cents'));
        Livewire::test(BillingConfiguration::class)->assertSee('Holiday and overtime rates')
            ->set('rates.logistics_special', '-1')->call('save')->assertHasErrors('rates.logistics_special')
            ->set('rates.logistics_special', '43.081')->call('save')->assertHasErrors('rates.logistics_special')
            ->set('rates.logistics_special', '43.08')->set('rates.security_legal', '0')->set('rates.security_night', '125.99')
            ->call('save')->assertHasNoErrors();
        $this->assertSame(4308, (int) DB::table('facility_charge_rates')->where('code', 'logistics_special')->value('amount_cents'));
        $this->assertSame(0, (int) DB::table('facility_charge_rates')->where('code', 'security_legal')->value('amount_cents'));
        $this->assertSame(4308, (int) DB::table('facility_charge_rates')->where('code', 'housekeeping_special')->value('amount_cents'));
        $this->assertSame(12599, (int) DB::table('facility_charge_rates')->where('code', 'security_night')->value('amount_cents'));
        $this->assertNull(DB::table('facility_charge_rates')->where('code', 'security_special')->value('amount_cents'));
        $this->assertSame(20000, (int) DB::table('facility_charge_rates')->where('code', 'logistics')->value('amount_cents'));
    }
}

