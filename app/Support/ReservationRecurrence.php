<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class ReservationRecurrence
{
    public static function dates(string $date, string $frequency, int $interval = 1, array $weekdays = [], string $ends = 'after', ?string $until = null, int $count = 2): array
    {
        if ($interval < 1 || $interval > 52 || $count < 2 || $count > 52) {
            throw new InvalidArgumentException('Repeat interval and occurrences must be between 1 and 52.');
        }
        if (!in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly', 'weekdays'], true)
            || !in_array($ends, ['on', 'after'], true)) {
            throw new InvalidArgumentException('Choose a valid recurrence pattern and ending.');
        }

        $first = CarbonImmutable::parse($date)->startOfDay();
        $last = $first->addYear();
        if ($ends === 'on') {
            if (!$until || CarbonImmutable::parse($until)->startOfDay()->lt($first) || CarbonImmutable::parse($until)->startOfDay()->gt($last)) {
                throw new InvalidArgumentException('End date must be within one year of the first event date.');
            }
            $last = CarbonImmutable::parse($until)->startOfDay();
        }

        $selectedDays = array_values(array_unique(array_map('intval', $weekdays)));
        if ($frequency === 'weekly' && (!$selectedDays || array_diff($selectedDays, range(0, 6)))) {
            throw new InvalidArgumentException('Select at least one weekday for weekly recurrence.');
        }
        if ($frequency === 'weekly' && !in_array($first->dayOfWeek, $selectedDays, true)) {
            throw new InvalidArgumentException('Include the first event date weekday in the selected repeat days.');
        }
        if ($frequency === 'weekdays' && !$first->isWeekday()) {
            throw new InvalidArgumentException('Choose a weekday as the first event date.');
        }

        $dates = [];
        $monthlyWeek = (int) ceil($first->day / 7);
        $monthlyLast = $first->addWeek()->month !== $first->month;
        for ($candidate = $first; $candidate->lte($last); $candidate = $candidate->addDay()) {
            $days = $first->diffInDays($candidate);
            $weeks = intdiv($days, 7);
            $months = ($candidate->year - $first->year) * 12 + $candidate->month - $first->month;
            $matches = match ($frequency) {
                'daily' => $days % $interval === 0,
                'weekly' => $weeks % $interval === 0 && in_array($candidate->dayOfWeek, $selectedDays, true),
                'weekdays' => $candidate->isWeekday(),
                'monthly' => $months % $interval === 0 && $candidate->dayOfWeek === $first->dayOfWeek
                    && ($monthlyLast ? $candidate->addWeek()->month !== $candidate->month : (int) ceil($candidate->day / 7) === $monthlyWeek),
                'yearly' => ($candidate->year - $first->year) % $interval === 0 && $candidate->month === $first->month && $candidate->day === $first->day,
            };
            if ($matches) {
                $dates[] = $candidate->toDateString();
                if (count($dates) >= ($ends === 'after' ? $count : 52)) {
                    break;
                }
            }
        }

        if (count($dates) < 2) {
            throw new InvalidArgumentException('This pattern produces fewer than two event dates within the booking limit.');
        }
        if ($ends === 'after' && count($dates) < $count) {
            throw new InvalidArgumentException('The requested number of dates extends beyond the one-year booking limit.');
        }

        return $dates;
    }
}
