<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCSVClinicController extends Controller
{
    public function index()
    {
        return view('clinic.import-csv.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Unable to read the uploaded file.',
            ]);
        }

        $rawHeader = fgetcsv($handle);
        if (! $rawHeader) {
            fclose($handle);

            return back()->with('flash', [
                'type' => 'error',
                'message' => 'CSV is empty or invalid.',
            ]);
        }

        // Normalize headers: trim, lowercase, remove BOM
        $header = array_map(function ($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h);
            return Str::of($h)->trim()->lower()->toString();
        }, $rawHeader);

        // REQUIRED headers (section removed)
        $requiredHeaders = [
            'first_name',
            'last_name',
            'email',
            'type',
            'course',
            'department',
            'position',
        ];

        // OPTIONAL headers (NOT required)
        $optionalHeaders = [
            'emergency_contact_number',
            'emergency_contact_person',
            'blood_type',
        ];

        $missing = array_values(array_diff($requiredHeaders, $header));
        if (! empty($missing)) {
            fclose($handle);

            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Missing headers: ' . implode(', ', $missing),
            ]);
        }

        $idx = array_flip($header);

        $inserted = 0;
        $skipped  = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {

                // Skip completely empty lines
                $nonEmpty = array_filter($row, fn ($v) => trim((string) $v) !== '');
                if (count($nonEmpty) === 0) {
                    continue;
                }

                $firstName  = trim((string) ($row[$idx['first_name']] ?? ''));
                $lastName   = trim((string) ($row[$idx['last_name']] ?? ''));
                $email      = trim((string) ($row[$idx['email']] ?? ''));
                $type       = strtolower(trim((string) ($row[$idx['type']] ?? '')));

                $course     = trim((string) ($row[$idx['course']] ?? ''));
                $department = trim((string) ($row[$idx['department']] ?? ''));
                $position   = trim((string) ($row[$idx['position']] ?? ''));

                // OPTIONAL: only read if header exists
                $emergencyNumber = isset($idx['emergency_contact_number'])
                    ? trim((string) ($row[$idx['emergency_contact_number']] ?? ''))
                    : '';

                $emergencyPerson = isset($idx['emergency_contact_person'])
                    ? trim((string) ($row[$idx['emergency_contact_person']] ?? ''))
                    : '';

                $bloodType = isset($idx['blood_type'])
                    ? trim((string) ($row[$idx['blood_type']] ?? ''))
                    : '';

                // Required fields
                if ($firstName === '' || $lastName === '' || $email === '' || $type === '') {
                    $skipped++;
                    continue;
                }

                // Only allow student or staff
                if (! in_array($type, ['student', 'staff'], true)) {
                    $skipped++;
                    continue;
                }

                // Student must have course (section removed)
                if ($type === 'student' && $course === '') {
                    $skipped++;
                    continue;
                }

                // Staff must have department and position
                if ($type === 'staff' && ($department === '' || $position === '')) {
                    $skipped++;
                    continue;
                }

                // Skip duplicates by email
                if (Patient::where('email', $email)->exists()) {
                    $skipped++;
                    continue;
                }

                Patient::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'type' => $type,

                    'course' => $type === 'student' ? $course : null,
                    'department' => $type === 'staff' ? $department : null,
                    'position' => $type === 'staff' ? $position : null,

                    'emergency_contact_number' => $emergencyNumber !== '' ? $emergencyNumber : null,
                    'emergency_contact_person' => $emergencyPerson !== '' ? $emergencyPerson : null,
                    'blood_type' => $bloodType !== '' ? $bloodType : null,
                ]);

                $inserted++;
            }

            DB::commit();
            fclose($handle);

            return back()->with('flash', [
                'type' => 'success',
                'message' => "Import complete. Inserted: {$inserted}, Skipped: {$skipped}",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
            ]);
        }
    }
}