<?php

namespace App\Livewire\Clinic;

use Livewire\Component;
use App\Models\Patient;
use App\Models\ClinicConsultation;
use App\Models\User;

class CheckInConsultation extends Component
{
    public Patient $patient;

    public string $teacherName = '';
    public string $teacherEmail = '';

    public bool $checkedIn = false;
    public ?string $timeInIso = null;
    public ?string $timeInDisplay = null;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;

        // Teacher selection only applies to students
        if ($this->patient->type !== 'student') {
            $this->teacherName = '';
            $this->teacherEmail = '';
        }
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

        // Staff: force teacher fields empty
        if ($this->patient->type !== 'student') {
            $this->teacherName = '';
            $this->teacherEmail = '';
        }

        $consultation = ClinicConsultation::create([
            'patient_id'             => $this->patient->id,

            'check_in_teacher'       => $this->patient->type === 'student' ? ($this->teacherName ?: null) : null,
            'check_in_teacher_email' => $this->patient->type === 'student' ? ($this->teacherEmail ?: null) : null,

            'current_teacher'        => $this->patient->type === 'student' ? ($this->teacherName ?: null) : null,
            'teacher_email'          => $this->patient->type === 'student' ? ($this->teacherEmail ?: null) : null,

            'time_in'                => now(),
            'time_out'               => null,

            'after_consultation'     => $this->patient->type === 'student' ? 'resume' : null,
        ]);

        $timeInDisplay = $consultation->time_in->format('M d, Y h:i A');
        $timeInIso = $consultation->time_in->toISOString();

        $this->checkedIn = true;
        $this->timeInIso = $timeInIso;
        $this->timeInDisplay = $timeInDisplay;

        $this->dispatch(
            'clinic-checked-in',
            consultationId: $consultation->id,
            teacherName: $this->teacherName,
            teacherEmail: $this->teacherEmail,
            timeInIso: $timeInIso,
            timeInDisplay: $timeInDisplay
        );
    }

    public function render()
    {
        $teachers = User::activeFaculty()
            ->select('name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->toArray();

        return view('livewire.clinic.check-in-consultation', compact('teachers'));
    }
}