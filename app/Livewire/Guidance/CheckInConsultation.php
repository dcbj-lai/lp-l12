<?php

namespace App\Livewire\Guidance;

use Livewire\Component;
use App\Models\Client;
use App\Models\Consultation;
use Illuminate\Support\Facades\Mail;

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
            'teacherName'  => ['required', 'string'],
            'teacherEmail' => ['required', 'email'],
        ]);

        if ($this->checkedIn) return;

        $consultation = Consultation::create([
            'client_id'       => $this->client->id,
            'current_teacher' => $this->teacherName,
            'time_in'         => now(),
            'time_out'        => null,

            // If your DB requires this field, keep a safe default:
            'after_consultation' => 'resume',
        ]);

        $studentName = "{$this->client->first_name} {$this->client->last_name}";
        $timeInDisplay = $consultation->time_in->format('M d, Y h:i A');
        $timeInIso = $consultation->time_in->toISOString();

        // hard-coded CC list (edit)
        $ccList = ['lem.fajarda@laicollege.edu.ph', 'lcfajarda@gmail.com'];

        Mail::raw(
            "Student checked in.\n\nStudent: {$studentName}\nTime In: {$timeInDisplay}\nTeacher: {$this->teacherName}\n",
            function ($m) use ($studentName, $ccList) {
                $m->to($this->teacherEmail)
                  ->cc($ccList)
                  ->subject("Student Check-in: {$studentName}");
            }
        );

        $this->checkedIn = true;
        $this->timeInIso = $timeInIso;
        $this->timeInDisplay = $timeInDisplay;

        // send to Alpine (parent page)
        $this->dispatch('guidance-checked-in',
            teacherName: $this->teacherName,
            teacherEmail: $this->teacherEmail,
            timeInIso: $timeInIso,
            timeInDisplay: $timeInDisplay
        );
    }

    public function render()
    {
        $teachers = [
            ['name' => 'Prof. Maria Santos', 'email' => 'maria@school.edu'],
            ['name' => 'Prof. Juan Dela Cruz', 'email' => 'juan@school.edu'],
            ['name' => 'Prof. James Johnson', 'email' => 'james@school.edu'],
        ];

        return view('livewire.guidance.check-in-consultation', compact('teachers'));
    }
}