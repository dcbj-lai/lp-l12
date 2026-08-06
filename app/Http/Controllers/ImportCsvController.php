<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCsvController extends Controller
{
    public function index()
    {
        return view('guidance.import-csv.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Unable to read the uploaded file.');
        }

        $rawHeader = fgetcsv($handle);
        if (! $rawHeader) {
            fclose($handle);
            return back()->with('error', 'CSV is empty or invalid.');
        }

        // Normalize headers: trim, lowercase, remove BOM
        $header = array_map(function ($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h);
            return Str::of($h)->trim()->lower()->toString();
        }, $rawHeader);

        $requiredHeaders = ['first_name', 'last_name', 'email', 'course', 'section'];
        $missing = array_values(array_diff($requiredHeaders, $header));
        if (! empty($missing)) {
            fclose($handle);
            return back()->with('error', 'Missing headers: ' . implode(', ', $missing));
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

                $firstName = trim((string) ($row[$idx['first_name']] ?? ''));
                $lastName  = trim((string) ($row[$idx['last_name']] ?? ''));
                $email     = trim((string) ($row[$idx['email']] ?? ''));
                $course    = trim((string) ($row[$idx['course']] ?? ''));
                $section   = trim((string) ($row[$idx['section']] ?? ''));

                // Required fields
                if ($firstName === '' || $lastName === '' || $email === '' || $course === '' || $section === '') {
                    $skipped++;
                    continue;
                }

                // Skip duplicates by email
                if (Client::where('email', $email)->exists()) {
                    $skipped++;
                    continue;
                }

                Client::create([
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'email'      => $email,
                    'is_under_accessibility' => false,
                    'course'     => $course,
                    'section'    => $section,
                ]);

                $inserted++;
            }

            DB::commit();
            fclose($handle);

            return back()->with('success', "Import complete. Inserted: {$inserted}, Skipped: {$skipped}");
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
