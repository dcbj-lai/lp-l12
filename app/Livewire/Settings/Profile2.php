<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class Profile2 extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $preferred_name = '';
    public string $phone_work = '';
    public string $phone_mobile = '';
    public string $emergency_contact_name = '';
    public string $emergency_contact_relationship = '';
    public string $emergency_contact_phone = '';
    public string $dietary_preference = '';
    public string $medical_notes = '';

    public $avatar;

    /** Allowed values for the relationship dropdown. */
    public array $relationshipOptions = ['Father', 'Mother', 'Sister', 'Brother', 'Spouse', 'Others'];

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->preferred_name = $user->preferred_name ?? '';
        $this->phone_work = $user->phone_work ?? '';
        $this->phone_mobile = $user->phone_mobile ?? '';
        $this->emergency_contact_name = $user->emergency_contact_name ?? '';
        $this->emergency_contact_relationship = $user->emergency_contact_relationship ?? '';
        $this->emergency_contact_phone = $user->emergency_contact_phone ?? '';
        $this->dietary_preference = $user->dietary_preference ?? '';
        $this->medical_notes = $user->medical_notes ?? '';
    }

    public function updateAvatar(): void
    {
        $user = Auth::user();

        $this->validate([
            'avatar' => ['required', 'image', 'max:10240'],
        ]);

        if ($user->profile_photo_path) {
            Storage::disk('s3')->delete($user->profile_photo_path);
        }

        $filename = 'avatar_' . time() . '.' . $this->avatar->getClientOriginalExtension();

        $path = $this->avatar->storeAs('avatars/' . $user->id, $filename, 's3');

        Storage::disk('s3')->setVisibility($path, 'public');

        $user->update([
            'profile_photo_path' => $path,
        ]);

        $this->modal('avatar-modal')->close();
        $this->reset('avatar');

        $this->dispatch('flash', type: 'success', message: 'Avatar updated.');
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'phone_work' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'phone_mobile' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', Rule::in($this->relationshipOptions)],
            'emergency_contact_phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'dietary_preference' => ['nullable', 'string', 'max:255'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->update($validated);

        if ($user->wasChanged('email')) {
            $user->email_verified_at = null;
            $user->save();
        }

        // 🔥 Rehydrate ALL properties at once
        $this->fill([
            'name' => $user->name,
            'email' => $user->email,
            'preferred_name' => $user->preferred_name ?? '',
            'phone_work' => $user->phone_work ?? '',
            'phone_mobile' => $user->phone_mobile ?? '',
            'emergency_contact_name' => $user->emergency_contact_name ?? '',
            'emergency_contact_relationship' => $user->emergency_contact_relationship ?? '',
            'emergency_contact_phone' => $user->emergency_contact_phone ?? '',
            'dietary_preference' => $user->dietary_preference ?? '',
            'medical_notes' => $user->medical_notes ?? '',
        ]);

        $this->dispatch('flash', type: 'success', message: 'Profile updated.');
    }

    public function resendVerificationNotification()
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function render()
    {
        return view('livewire.settings.profile2');
    }
}
