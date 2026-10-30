<?php

namespace App\Services;

use App\Models\FacilityGeneratedSoa;
use App\Models\ResourceReservation;
use App\Models\Resource;
use App\Support\FacilityBillingStorage;
use App\Support\FacilitySoaFilename;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FacilitySoaGenerator
{
    public const LABELS = ['security' => 'Security', 'logistics' => 'Logistics', 'housekeeping' => 'Housekeeping',
        'plates' => 'Plates', 'spoons' => 'Spoons', 'forks' => 'Forks', 'coffee_set' => 'Coffee Set', 'cups' => 'Cups'];
    public const STAFF = ['security', 'logistics', 'housekeeping'];
    public const CONSUMABLES = ['plates', 'spoons', 'forks', 'coffee_set', 'cups'];

    public function dueDate($issueDate): CarbonImmutable
    {
        return CarbonImmutable::parse($issueDate, 'Asia/Manila')->startOfDay()->addWeekdays(15);
    }

    public function quote(ResourceReservation $reservation, array $data): array
    {
        Validator::make($data, ['event_type' => 'required|in:ministry,non_ministry', 'quantities' => 'required|array',
            'quantities.*' => 'required|integer|min:0|max:10000', 'hours' => 'required|array', 'hours.*' => 'required|numeric|min:0|max:10000|decimal:0,2',
            'extra_hours' => 'sometimes|array:security,logistics,housekeeping',
            'extra_hours.*' => 'array', 'extra_hours.*.*' => 'required|numeric|min:0|max:10000|decimal:0,2'])->validate();
        $rooms = Resource::whereIn('id', $reservation->roomIds())->where('type', 'room')->count();
        if (!$rooms || $reservation->end_datetime->lte($reservation->start_datetime)) throw ValidationException::withMessages(['event_type' => 'The booking must have a room and a valid event schedule.']);
        $seconds = $reservation->end_datetime->timestamp - $reservation->start_datetime->timestamp;
        $blocks = (int) ceil($seconds / (4 * 3600));
        $venueRate = $data['event_type'] === 'ministry' ? 120000 : 240000;
        $lines = [['label' => 'Venue Fee', 'quantity' => $rooms * $blocks, 'rate_cents' => $venueRate,
            'unit' => 'room / 4-hour block', 'amount_cents' => $rooms * $blocks * $venueRate]];
        $rates = DB::table('facility_charge_rates')->pluck('amount_cents', 'code');
        foreach (self::LABELS as $code => $label) {
            $quantity = (int) ($data['quantities'][$code] ?? 0);
            if (!$quantity) continue;
            $staff = in_array($code, self::STAFF, true);
            $hours = $staff ? ($data['hours'][$code] ?? 0) : 1;
            if ($staff && (float) $hours <= 0) {
                if (array_sum($data['extra_hours'][$code] ?? []) > 0) continue;
                throw ValidationException::withMessages(['hours.'.$code => 'Enter positive billable hours for '.$label.'.']);
            }
            if (!isset($rates[$code])) throw ValidationException::withMessages(['quantities.'.$code => 'Configure the '.$label.' rate under Facilities → Billing configuration first.']);
            $hundredths = (int) round((float) $hours * 100);
            $rate = (int) $rates[$code];
            $amount = intdiv($quantity * $rate * $hundredths + 50, 100);
            $lines[] = ['label' => $label, 'quantity' => $quantity, 'hours' => $staff ? $hours : null,
                'rate_cents' => $rate, 'unit' => $staff ? 'person / hour' : 'unit', 'amount_cents' => $amount];
        }
        foreach ($data['extra_hours'] ?? [] as $staff => $periods) {
            foreach ($periods as $period => $hours) {
                $rateCode = $staff.'_'.$period;
                $label = \App\Support\FacilityStaffRates::labels()[$rateCode] ?? null;
                if (!$label) throw ValidationException::withMessages(['extra_hours.'.$staff => 'Choose a valid staff rate period.']);
                if ((float) $hours <= 0) continue;
                $quantity = (int) ($data['quantities'][$staff] ?? 0);
                if (!$quantity) throw ValidationException::withMessages(['quantities.'.$staff => 'Enter a staff quantity for the selected rate hours.']);
                if (!isset($rates[$rateCode])) throw ValidationException::withMessages(['extra_hours.'.$staff.'.'.$period => 'Configure the '.$label.' rate under Facilities → Billing configuration first.']);
                $rate = (int) $rates[$rateCode];
                $lines[] = ['label' => $label, 'staff_code' => $staff, 'period' => $period, 'quantity' => $quantity, 'hours' => $hours,
                    'rate_cents' => $rate, 'unit' => 'person / hour',
                    'amount_cents' => intdiv($quantity * $rate * (int) round((float) $hours * 100) + 50, 100)];
            }
        }
        return ['lines' => $lines, 'total_cents' => array_sum(array_column($lines, 'amount_cents')), 'rooms' => $rooms,
            'blocks' => $blocks, 'event_type' => $data['event_type'], 'duration_hours' => round($seconds / 3600, 2)];
    }

    public function snapshot(ResourceReservation $reservation, array $data): array
    {
        return $this->quote($reservation, $data) + ['filename' => FacilitySoaFilename::forReservation($reservation), 'client_name' => trim($data['client_name']),
            'business_address' => trim($data['business_address'] ?? ''), 'event_name' => $reservation->title,
            'event_date' => $reservation->start_datetime->format('M j, Y').($reservation->start_datetime->isSameDay($reservation->end_datetime) ? '' : '–'.$reservation->end_datetime->format('M j, Y')),
            'venue' => $reservation->room_names, 'event_time' => $reservation->start_datetime->format('g:i A').'–'.$reservation->end_datetime->format('g:i A')];
    }

    public function pdf(array $snapshot, $issueDate, bool $draft = true): string
    {
        $fontCache = storage_path('framework/cache/dompdf-fonts');
        File::ensureDirectoryExists($fontCache);
        $pdf = Pdf::setOptions(['isRemoteEnabled' => false, 'fontDir' => $fontCache, 'fontCache' => $fontCache], true);
        foreach ([400 => 'Regular', 700 => 'Bold'] as $weight => $style) {
            if (!$pdf->getDomPDF()->getFontMetrics()->registerFont(
                ['family' => 'Crimson Pro', 'style' => 'normal', 'weight' => $weight],
                'file://'.resource_path('soa/fonts/CrimsonPro-'.$style.'.ttf')
            )) throw new \RuntimeException('Could not load the SOA font.');
        }
        return $pdf->loadView('pdf.facility-soa', ['soa' => $snapshot, 'issueDate' => CarbonImmutable::parse($issueDate),
            'dueDate' => $this->dueDate($issueDate), 'draft' => $draft])->setPaper('letter')->output();
    }

    public function create(int $id, array $data): FacilityGeneratedSoa
    {
        abort_unless(auth()->user()?->hasRole('facility.admin'), 403);
        Validator::make($data, ['client_name' => 'required|string|max:255', 'business_address' => 'nullable|string|max:1000',
            'replacement_reason' => 'nullable|string|max:2000'])->validate();
        $path = null;
        try {
            return DB::transaction(function () use ($id, $data, &$path) {
                $reservation = ResourceReservation::whereKey($id)->lockForUpdate()->firstOrFail();
                abort_unless($reservation->status === 'approved', 422);
                if ($reservation->soa_locked) throw ValidationException::withMessages(['client_name' => 'Payment is already recorded. Use the existing Replace SOA workflow to retain the payment audit trail.']);
                if ($reservation->soa_path && trim($data['replacement_reason'] ?? '') === '') throw ValidationException::withMessages(['replacement_reason' => 'Enter a reason for replacing the existing SOA.']);
                if ($reservation->billingEmails()->where('status', 'queued')->exists()) throw ValidationException::withMessages(['client_name' => 'Wait for the queued billing email to finish before generating a replacement.']);
                $snapshot = $this->snapshot($reservation, $data);
                $disk = FacilityBillingStorage::uploadDisk();
                $path = 'facility-billing/generated/'.Str::uuid().'/'.$snapshot['filename'];
                if (!Storage::disk($disk)->put($path, $this->pdf($snapshot, today()), ['visibility' => 'private'])) throw new \RuntimeException('SOA could not be saved.');
                $document = FacilityGeneratedSoa::create(['reservation_id' => $id, 'path' => $path, 'snapshot' => $snapshot,
                    'issue_date' => today(), 'due_date' => $this->dueDate(today()), 'created_by' => auth()->id()]);
                if ($reservation->soa_path) $reservation->soaRevisions()->create(['previous_path' => $reservation->soa_path, 'replacement_path' => $path,
                    'reason' => trim($data['replacement_reason']), 'replaced_by' => auth()->id()]);
                $reservation->update(['soa_path' => $path, 'soa_email_pending' => true, 'payment_required' => true,
                    'payment_due_at' => $document->due_date]);
                return $document;
            });
        } catch (\Throwable $e) {
            if ($path) FacilityBillingStorage::disk($path)->delete($path);
            throw $e;
        }
    }

    // Called by the email job on the actual send day, not the draft creation day.
    public function prepareEmail(ResourceReservation $reservation, array $details): array
    {
        $document = FacilityGeneratedSoa::where('reservation_id', $reservation->id)->where('path', $reservation->soa_path)->first();
        if (!$document) return $details;
        $issue = today();
        $bytes = $this->pdf($document->snapshot, $issue, false);
        if (!Storage::disk($details['disk'])->put($details['attachment'], $bytes, ['visibility' => 'private'])
            || !FacilityBillingStorage::disk($document->path)->put($document->path, $bytes, ['visibility' => 'private'])) {
            throw new \RuntimeException('Could not finalize the generated SOA for sending.');
        }
        $document->update(['issue_date' => $issue, 'due_date' => $this->dueDate($issue), 'sent_at' => now()]);
        $reservation->update(['payment_due_at' => $document->due_date]);
        $details['due_date'] = $document->due_date->format('M j, Y');
        return $details;
    }
}
