<?php

namespace App\Livewire\Guidance;

use Livewire\Component;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\User;

class CheckInConsultation extends Component
{
    public Client $client;

    public string $teacherName = '';
    public string $teacherEmail = '';

    public bool $checkedIn = false;
    public ?string $timeInIso = null;
    public ?string $timeInDisplay = null;

    public function mount(Client $client): void
    {
        $this->client = $client;
    }

    public function checkIn(): void
    {
        $this->validate([
            'teacherName'  => ['nullable', 'string'],
            'teacherEmail' => ['nullable', 'email'],
        ]);

        if ($this->checkedIn) {
            return;
        }

        $consultation = Consultation::create([
            'client_id'              => $this->client->id,
            'check_in_teacher'       => $this->teacherName,
            'check_in_teacher_email' => $this->teacherEmail,
            'current_teacher'        => $this->teacherName,
            'teacher_email'          => $this->teacherEmail,
            'time_in'                => now(),
            'time_out'               => null,
            'after_consultation'     => 'resume',
        ]);

        $timeInDisplay = $consultation->time_in->format('M d, Y h:i A');
        $timeInIso = $consultation->time_in->toISOString();

        $this->checkedIn = true;
        $this->timeInIso = $timeInIso;
        $this->timeInDisplay = $timeInDisplay;

        $this->dispatch(
            'guidance-checked-in',
            teacherName: $this->teacherName,
            teacherEmail: $this->teacherEmail,
            timeInIso: $timeInIso,
            timeInDisplay: $timeInDisplay
        );
    }

    public function render()
    {
        $facultyDepartment = \App\Models\Department::whereRaw('LOWER(name) = ?', ['faculty'])->first();

        $teachers = [];

        if ($facultyDepartment) {
            $teachers = User::where('department_id', $facultyDepartment->id)
                ->select('name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => [
                    'name' => $u->name,
                    'email' => $u->email,
                ])
                ->toArray();
        }

        return view('livewire.guidance.check-in-consultation', compact('teachers'));
    }
}