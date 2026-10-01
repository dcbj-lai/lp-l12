<?php

namespace Tests\Unit;

use App\Support\ReservationRecurrence;
use PHPUnit\Framework\TestCase;

class ReservationRecurrenceTest extends TestCase
{
    public function test_custom_weekly_pattern_can_repeat_on_multiple_weekdays(): void
    {
        $this->assertSame(
            ['2026-10-01', '2026-10-06', '2026-10-08', '2026-10-13'],
            ReservationRecurrence::dates('2026-10-01', 'weekly', 1, [2, 4], 'after', null, 4)
        );
    }

    public function test_monthly_pattern_uses_the_same_weekday_position(): void
    {
        $this->assertSame(
            ['2026-10-01', '2026-11-05', '2026-12-03'],
            ReservationRecurrence::dates('2026-10-01', 'monthly', 1, [], 'after', null, 3)
        );
    }

    public function test_recurrence_can_end_on_a_date(): void
    {
        $this->assertSame(
            ['2026-10-01', '2026-10-08', '2026-10-15'],
            ReservationRecurrence::dates('2026-10-01', 'weekly', 1, [4], 'on', '2026-10-15')
        );
    }
}
