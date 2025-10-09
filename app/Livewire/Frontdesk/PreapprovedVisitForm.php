<?php

namespace App\Livewire\Frontdesk;

use Livewire\Component;
use App\Models\VisitorLog;
use App\Mail\VisitorPreApprovedMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PreapprovedVisitForm extends Component
{
    public $visit_date;
    public $purpose;
    public $company;
    public $notes;
    public $visitor_name = '';
    public $visitor_email = '';
    public $visitors = [];

    public function addVisitor()
    {
        if (!$this->visitor_name || !$this->visitor_email || !filter_var($this->visitor_email, FILTER_VALIDATE_EMAIL)) {
            $this->dispatch('notify', type: 'error', message: 'Please enter a valid name and email.');
            return;
        }

        $this->visitors[] = [
            'name' => trim($this->visitor_name),
            'email' => trim($this->visitor_email),
        ];

        $this->reset(['visitor_name', 'visitor_email']);
    }

    public function removeVisitor($index)
    {
        unset($this->visitors[$index]);
        $this->visitors = array_values($this->visitors);
    }

    public function save()
    {
        $this->validate([
            'visit_date' => 'required|date|after_or_equal:today',
            'purpose' => 'required|string|max:255',
            'visitors' => 'required|array|min:1',
            'visitors.*.name' => 'required|string|max:255',
            'visitors.*.email' => 'required|email',
            'company' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $isBatch = count($this->visitors) >= 1;
        $batchId = $isBatch ? VisitorLog::generateBatchId() : null;

        // Check for existing active visit on the same date
        foreach ($this->visitors as $visitor) {
            $hasActiveVisit = VisitorLog::where('email', $visitor['email'])
                ->where('visit_date', $this->visit_date)
                ->whereIn('status', ['approved', 'pending'])
                ->exists();

            if ($hasActiveVisit) {
                $this->addError('visitors', "Visitor {$visitor['name']} already has an active visit scheduled for {$this->visit_date}.");
                return; // stop execution, don’t create duplicates
            }
        }

        foreach ($this->visitors as $visitor) {
            $record = VisitorLog::create([
                'full_name' => $visitor['name'],
                'email' => $visitor['email'],
                'purpose' => $this->purpose,
                'company' => $this->company,
                'visit_date' => $this->visit_date,
                'meetup_spot' => $this->notes,
                'visited_user_id' => Auth::id(),
                'status' => 'approved', // since it’s pre-approved
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
                'emailed_at' => now(),
                
            ]);

            //Queue Email
            Mail::to($record->email)->queue(new VisitorPreApprovedMail($record));
        }
        return redirect()->route('visitors.mine')
        ->with('success', "Pre-approved visit(s) successfully created.");

    }
}
