<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UsersCsvUpload extends Component
{
    use WithFileUploads;

    public $file;

    public int $successCount = 0;
    public array $csvErrors = [];

    protected $rules = [
        'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
    ];

    public function processUpload()
    {
        Log::info('CSV Upload: triggered');

        // ✅ Reset state FIRST (important for UI consistency)
        $this->reset(['successCount', 'csvErrors']);

        // ✅ Validate safely (never throw)
        try {
            $this->validate();
        } catch (\Throwable $e) {
            Log::warning('CSV Upload: validation failed', [
                'error' => $e->getMessage()
            ]);

            $this->csvErrors[] = 'Please upload a valid CSV file.';
            return;
        }

        // ✅ File safety
        if (!$this->file || !$this->file->isValid()) {
            Log::warning('CSV Upload: invalid file');
            $this->csvErrors[] = 'Invalid file upload.';
            return;
        }

        try {
            $path = $this->file->getRealPath();

            if (!$path || !file_exists($path)) {
                Log::error('CSV Upload: file path issue');
                $this->csvErrors[] = 'Uploaded file not found.';
                return;
            }

            $rows = array_map('str_getcsv', file($path));

            if (empty($rows)) {
                $this->csvErrors[] = 'CSV file is empty.';
                return;
            }

            // Remove header
            array_shift($rows);

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    if (!is_array($row) || count($row) < 3) {
                        $this->csvErrors[] = "Row {$rowNumber}: Invalid format";
                        continue;
                    }

                    [$email, $birthRaw, $hireRaw] = $row;

                    $email = trim($email);

                    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->csvErrors[] = "Row {$rowNumber}: Invalid email";
                        continue;
                    }

                    $user = User::where('email', $email)->first();

                    if (!$user) {
                        $this->csvErrors[] = "{$email} not found";
                        continue;
                    }

                    $birthdate = $this->parseDate($birthRaw);
                    $hireDate = $this->parseDate($hireRaw);

                    if ($birthdate === false || $hireDate === false) {
                        $this->csvErrors[] = "{$email} invalid date format";
                        continue;
                    }

                    if ($birthdate && $hireDate && $hireDate < $birthdate) {
                        $this->csvErrors[] = "{$email} hire date before birthdate";
                        continue;
                    }

                    if ($birthdate && $birthdate > now()->toDateString()) {
                        $this->csvErrors[] = "{$email} birthdate in future";
                        continue;
                    }

                    if ($hireDate && $hireDate > now()->toDateString()) {
                        $this->csvErrors[] = "{$email} hire date in future";
                        continue;
                    }

                    $user->update([
                        'birthdate' => $birthdate,
                        'hire_date' => $hireDate,
                    ]);

                    $this->successCount++;

                } catch (\Throwable $e) {
                    Log::error("CSV Upload: row {$rowNumber} failed", [
                        'error' => $e->getMessage()
                    ]);

                    $this->csvErrors[] = "Row {$rowNumber}: unexpected error";
                }
            }

            DB::commit();

            Log::info('CSV Upload: completed', [
                'success' => $this->successCount,
                'errors' => count($this->csvErrors),
            ]);

            session()->flash('success', "{$this->successCount} users updated.");

            // ✅ Reset file after success
            $this->reset('file');

            // ✅ Auto-close modal ONLY if clean
            if ($this->successCount > 0 && empty($this->csvErrors)) {
                $this->dispatch('close-modal', name: 'tools-modal');
            }

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('CSV Upload: fatal error', [
                'error' => $e->getMessage()
            ]);

            $this->csvErrors[] = 'CSV processing failed.';
            session()->flash('error', 'Upload failed.');
        }
    }

    private function parseDate($value)
    {
        try {
            $value = trim((string) $value);

            if ($value === '') {
                return null;
            }

            return Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d');

        } catch (\Throwable $e) {
            return false;
        }
    }

    public function render()
    {
        return view('livewire.users-csv-upload');
    }
}
