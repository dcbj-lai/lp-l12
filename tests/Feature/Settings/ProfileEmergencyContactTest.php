<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Profile2;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_dietary_and_medical_fields_are_hydrated_on_mount(): void
    {
        $user = User::factory()->create([
            'dietary_preference' => 'Vegetarian',
            'medical_notes' => 'Peanut allergy',
        ]);

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->assertSet('dietary_preference', 'Vegetarian')
            ->assertSet('medical_notes', 'Peanut allergy');
    }

    public function test_user_can_save_dietary_and_medical_details(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('dietary_preference', 'Halal')
            ->set('medical_notes', 'Asthma; carries inhaler')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Halal', $user->dietary_preference);
        $this->assertEquals('Asthma; carries inhaler', $user->medical_notes);
    }

    public function test_medical_notes_max_length_is_enforced(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('medical_notes', str_repeat('a', 2001))
            ->call('updateProfileInformation')
            ->assertHasErrors(['medical_notes']);
    }

    public function test_user_can_upload_high_resolution_profile_photo(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $avatar = UploadedFile::fake()->image('avatar.jpg')->size(4096);

        Livewire::actingAs($user)
            ->test(Profile2::class)
            ->set('avatar', $avatar)
            ->call('updateAvatar')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('s3')->assertExists($user->profile_photo_path);
    }
}
