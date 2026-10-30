<?php

namespace App\Support;

class FacilityStaffRates
{
    // Separate amounts match the supplied OT sheet; do not infer premiums from base fees.
    public const PERIODS = [
        'weekday_ot' => 'Monday–Saturday · over 8 hours',
        'sunday' => 'Sunday · first 8 hours',
        'sunday_ot' => 'Sunday · over 8 hours',
        'special' => 'Special holiday, not Sunday · first 8 hours',
        'special_ot' => 'Special holiday, not Sunday · over 8 hours',
        'special_sunday' => 'Special holiday on Sunday · first 8 hours',
        'special_sunday_ot' => 'Special holiday on Sunday · over 8 hours',
        'legal' => 'Legal holiday, not Sunday · first 8 hours',
        'legal_ot' => 'Legal holiday, not Sunday · over 8 hours',
        'legal_sunday' => 'Legal holiday on Sunday · first 8 hours',
        'legal_sunday_ot' => 'Legal holiday on Sunday · over 8 hours',
    ];

    public static function labels(): array
    {
        $labels = ['security_night' => 'Security — night shift'];
        foreach (\App\Services\FacilitySoaGenerator::STAFF as $staff) {
            foreach (self::PERIODS as $period => $label) {
                $labels[$staff.'_'.$period] = \App\Services\FacilitySoaGenerator::LABELS[$staff].' — '.$label;
            }
        }
        return $labels;
    }
}
