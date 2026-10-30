<?php

namespace App\Support;

use App\Models\FacilityGeneratedSoa;
use App\Models\ResourceReservation;

class FacilitySoaFilename
{
    public static function forReservation(ResourceReservation $reservation, string $extension = 'pdf'): string
    {
        $title = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $reservation->title ?? '');
        $title = preg_replace('/[\x00-\x1F\x7F]/u', '', $title);
        $title = preg_replace('/\s+/u', ' ', $title);
        $title = trim(rtrim(mb_strcut($title, 0, 140, 'UTF-8'), '. '));
        $extension = in_array(strtolower($extension), ['pdf', 'doc', 'docx'], true) ? strtolower($extension) : 'pdf';
        return $reservation->start_datetime->copy()->setTimezone('Asia/Manila')->format('mdy')
            .' - '.($title ?: 'Event').'.'.$extension;
    }

    public static function forAttachment(ResourceReservation $reservation): string
    {
        $document = self::generatedDocument($reservation);
        return $document?->snapshot['filename']
            ?? self::forReservation($reservation, pathinfo((string) $reservation->soa_path, PATHINFO_EXTENSION));
    }

    public static function generatedDocument(ResourceReservation $reservation): ?FacilityGeneratedSoa
    {
        $document = $reservation->latestGeneratedSoa;
        return $document && $document->path === $reservation->soa_path ? $document : null;
    }
}
