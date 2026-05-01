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

    public $avatar;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->preferred_name = $user->preferred_name ?? '';
        $this->phone_work = $user->phone_work ?? '';
        $this->phone_mobile = $user->phone_mobile ?? '';
    }

    public function updateAvatar(): void
    {
        $user = Auth::user();

        $this->validate([
            'avatar' => ['required', 'image', 'max:2048'],
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
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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
