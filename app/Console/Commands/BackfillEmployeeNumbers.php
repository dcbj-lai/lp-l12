<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BackfillEmployeeNumbers extends Command
{
    protected $signature = 'users:backfill-employee-numbers
        {path : CSV path with employee_number and name columns}
        {--dry-run : Preview matches without updating users}
        {--force : Overwrite existing employee numbers}';

    protected $description = 'Backfill users.employee_number from a People & Culture employee number CSV';

    public function handle(): int
    {
        $path = $this->argument('path');
        $fullPath = $this->resolvePath($path);

        if (!is_readable($fullPath)) {
            $this->error("File is not readable: {$path}");

            return Command::FAILURE;
        }

        $entries = $this->readEntries($fullPath);

        if ($entries->isEmpty()) {
            $this->error('No employee number rows found.');

            return Command::FAILURE;
        }

        $updated = 0;
        $matched = 0;
        $skipped = 0;

        $users = User::query()
            ->orderBy('name')
            ->get();

        foreach ($users as $user) {
            $matches = $this->matchingEntries($user, $entries);

            if ($matches->count() !== 1) {
                $skipped++;

                if ($matches->count() > 1) {
                    $this->warn("Ambiguous match for {$user->name}; skipped.");
                }

                continue;
            }

            $entry = $matches->first();
            $matched++;

            if ($user->employee_number && ! $this->option('force')) {
                $this->line("Already set: {$user->name} ({$user->employee_number})");
                continue;
            }

            $this->line("Matched {$user->name} => {$entry['employee_number']} ({$entry['name']})");

            if (! $this->option('dry-run')) {
                $user->employee_number = $entry['employee_number'];
                $user->save();
                $updated++;
            }
        }

        $this->info(($this->option('dry-run') ? 'Dry run complete.' : 'Backfill complete.'));
        $this->info("Rows loaded: {$entries->count()}; users matched: {$matched}; users updated: {$updated}; users skipped: {$skipped}.");

        return Command::SUCCESS;
    }

    protected function resolvePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $storagePath = storage_path('app/' . ltrim($path, '/\\'));

        return is_file($storagePath) ? $storagePath : base_path($path);
    }

    protected function readEntries(string $path): Collection
    {
        $handle = fopen($path, 'r');
        $rows = [];
        $header = null;

        while (($row = fgetcsv($handle)) !== false) {
            if (! array_filter($row, fn ($value) => trim((string) $value) !== '')) {
                continue;
            }

            if ($header === null && $this->looksLikeHeader($row)) {
                $header = array_map(fn ($value) => Str::snake(trim((string) $value)), $row);
                continue;
            }

            [$employeeNumber, $name] = $this->extractEmployeeNumberAndName($row, $header);

            if ($employeeNumber === '' || $name === '') {
                continue;
            }

            $rows[] = [
                'employee_number' => $employeeNumber,
                'name' => $name,
                'normalized_name' => $this->normalize($name),
                'parsed' => $this->parsePncName($name),
            ];
        }

        fclose($handle);

        return collect($rows)
            ->unique('employee_number')
            ->values();
    }

    protected function looksLikeHeader(array $row): bool
    {
        $joined = Str::lower(implode(' ', $row));

        return str_contains($joined, 'employee') || str_contains($joined, 'name');
    }

    protected function extractEmployeeNumberAndName(array $row, ?array $header): array
    {
        if ($header) {
            $mapped = array_combine($header, array_pad($row, count($header), '')) ?: [];

            return [
                $this->cleanEmployeeNumber($mapped['employee_number'] ?? $mapped['employee_no'] ?? $mapped['number'] ?? ''),
                trim((string) ($mapped['name'] ?? $mapped['employee_name'] ?? '')),
            ];
        }

        $employeeNumber = $row[1] ?? $row[0] ?? '';
        $name = $row[2] ?? $row[1] ?? '';

        return [
            $this->cleanEmployeeNumber($employeeNumber),
            trim((string) $name),
        ];
    }

    protected function cleanEmployeeNumber(mixed $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^\d+\.0+$/', $value)) {
            $value = preg_replace('/\.0+$/', '', $value);
        }

        return $value;
    }

    protected function matchingEntries(User $user, Collection $entries): Collection
    {
        $userName = $this->normalize($user->name);
        $preferredName = $this->normalize((string) $user->preferred_name);
        $emailLocal = $this->normalize(strstr($user->email, '@', true) ?: '');
        $userTokens = collect(explode(' ', $userName))->filter()->values();
        $userLast = $userTokens->last();
        $userFirstInitial = Str::substr($userTokens->first() ?? '', 0, 1);

        return $entries->filter(function (array $entry) use ($userName, $preferredName, $emailLocal, $userLast, $userFirstInitial) {
            if (in_array($userName, $this->nameVariants($entry), true)) {
                return true;
            }

            if ($preferredName !== '' && in_array($preferredName, $this->nameVariants($entry), true)) {
                return true;
            }

            $parsed = $entry['parsed'];

            if (
                $userLast !== '' &&
                $userFirstInitial !== '' &&
                $userLast === $parsed['last'] &&
                $userFirstInitial === Str::substr($parsed['first'], 0, 1)
            ) {
                return true;
            }

            if ($emailLocal !== '' && str_contains($emailLocal, $parsed['last'])) {
                return $parsed['first'] !== '' && str_contains($emailLocal, Str::substr($parsed['first'], 0, 1));
            }

            return false;
        })->values();
    }

    protected function nameVariants(array $entry): array
    {
        $parsed = $entry['parsed'];
        $variants = [
            $entry['normalized_name'],
            trim($parsed['first'] . ' ' . $parsed['last']),
            trim($parsed['first'] . ' ' . $parsed['middle'] . ' ' . $parsed['last']),
            trim($parsed['last'] . ' ' . $parsed['first']),
        ];

        return array_values(array_unique(array_filter($variants)));
    }

    protected function parsePncName(string $name): array
    {
        $parts = array_map(fn ($part) => $this->normalize($part), explode(',', $name));
        $last = $this->stripSuffix($parts[0] ?? '');
        $first = $parts[1] ?? '';
        $middle = $parts[2] ?? '';

        if ($first === '' && str_contains($this->normalize($name), ' ')) {
            $tokens = collect(explode(' ', $this->normalize($name)))->filter()->values();
            $first = $tokens->first() ?? '';
            $last = $tokens->last() ?? '';
        }

        return [
            'first' => $first,
            'middle' => $middle,
            'last' => $last,
        ];
    }

    protected function stripSuffix(string $value): string
    {
        return trim(preg_replace('/\b(JR|SR|II|III|IV)\b/', '', $value));
    }

    protected function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z0-9]+/', ' ', Str::upper(Str::ascii($value)))));
    }
}
