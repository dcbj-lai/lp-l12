<?php

namespace App\Livewire\Frontdesk;

use Livewire\Component;
use App\Models\VisitorLog;
use Livewire\WithFileUploads;
use App\Mail\VisitorPreApprovedMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PreapprovedVisitForm extends Component
{
    use WithFileUploads;
    public $visit_date;
    public $purpose;
    public $company;
    public $notes;
    public $visitor_name = '';
    public $visitor_email = '';
    public $visitors = [];
    public $csvFile;

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

    public function uploadCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt',
        ]);

        $path = $this->csvFile->getRealPath();
        $rows = array_map('str_getcsv', file($path));

        // Expect: name,email
        $header = array_map('strtolower', array_shift($rows));

        if ($header !== ['name', 'email']) {
            $this->addError('csvFile', 'CSV header must be exactly: name,email');
            return;
        }

        foreach ($rows as $row) {
            if (count($row) !== 2)
                continue;

            $name = trim($row[0]);
            $email = trim($row[1]);

            // Basic validation to match UI behavior
            if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Avoid duplicates in the pill list before save()
            if (!collect($this->visitors)->contains(fn($v) => $v['email'] === $email)) {
                $this->visitors[] = [
                    'name' => $name,
                    'email' => $email,
                ];
            }
        }

        $this->dispatch('notify', type: 'success', message: 'CSV uploaded. Review and click Create to proceed.');
        $this->reset('csvFile');
    }

}
