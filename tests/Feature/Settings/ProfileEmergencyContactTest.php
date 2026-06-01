<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Profile2;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileEmergencyContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_contact_fields_are_hydrated_on_mount(): void
    {
        $user = User::factory()->create([
            'emergency_contact_name' => 'Maria Dela Cruz',
            'emergency_contact_relationship' => 'Spouse',
            'emergency_contact_phone' => '+639171234567',
        ]);

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->assertSet('emergency_contact_name', 'Maria Dela Cruz')
            ->assertSet('emergency_contact_relationship', 'Spouse')
            ->assertSet('emergency_contact_phone', '+639171234567');
    }

    public function test_user_can_save_emergency_contact_details(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('emergency_contact_name', 'Juan Dela Cruz')
            ->set('emergency_contact_relationship', 'Father')
            ->set('emergency_contact_phone', '+639170000000')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Juan Dela Cruz', $user->emergency_contact_name);
        $this->assertEquals('Father', $user->emergency_contact_relationship);
        $this->assertEquals('+639170000000', $user->emergency_contact_phone);
    }

    public function test_relationship_must_be_from_the_allowed_list(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('emergency_contact_relationship', 'Cousin') // not allowed
            ->call('updateProfileInformation')
            ->assertHasErrors(['emergency_contact_relationship']);
    }

    public function test_emergency_contact_phone_is_validated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('emergency_contact_phone', 'not-a-number')
            ->call('updateProfileInformation')
            ->assertHasErrors(['emergency_contact_phone']);
    }

    public function test_emergency_contact_fields_are_optional(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->call('updateProfileInformation')
            ->assertHasNoErrors();
    }
}
