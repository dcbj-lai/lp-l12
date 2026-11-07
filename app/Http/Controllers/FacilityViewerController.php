<?php

namespace App\Http\Controllers;

use App\Models\ResourceReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FacilityViewerController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasRole('facility.viewer')) {
            return view('resources.reservations.index');
        }

        $direction = $request->query('sort') === 'asc' ? 'asc' : 'desc';
        $status = $request->query('status') === 'pending' ? 'pending' : 'approved';
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 200);
        $now = now();
        $reservations = ResourceReservation::with(['resource', 'rooms', 'equipment'])
            ->withExists('paymentProofs')
            ->search($search)
            ->where('end_datetime', '>', $now)
            ->where('status', $status)->orderBy('start_datetime', $direction)
            ->orderBy('id', $direction)->paginate(30)->withQueryString();
        $seriesDates = ResourceReservation::with(['resource', 'rooms', 'equipment'])
            ->withExists('paymentProofs')
            ->whereIn('recurrence_series_id', $reservations->pluck('recurrence_series_id')->filter()->unique())
            ->where('end_datetime', '>', $now)
            ->whereIn('status', ['pending', 'approved'])->orderBy('start_datetime')->orderBy('id')
            ->get()->groupBy('recurrence_series_id');

        return view('resources.reservations.viewer', compact('reservations', 'direction', 'status', 'seriesDates', 'search'));
    }

    public function floorPlan(Request $request, ResourceReservation $reservation)
    {
        $viewer = $request->user()->hasRole('facility.viewer');
        abort_if($viewer && $reservation->trashed(), 404);
        abort_unless($reservation->floor_plan_path && ($viewer
            ? (!$reservation->trashed() && in_array($reservation->status, ['pending', 'approved'], true))
            : $request->user()->hasAnyRole(['facility.admin', 'facility.approver'])), 403);
        $disk = Storage::disk(config('filesystems.facility_upload_disk'));
        abort_unless($disk->exists($reservation->floor_plan_path), 404);
        $mime = match (strtolower(pathinfo($reservation->floor_plan_path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', default => null,
        };
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($request->boolean('download') || !$mime) return $disk->download($reservation->floor_plan_path, null, $headers);
        return $disk->response($reservation->floor_plan_path, null, $headers + ['Content-Type' => $mime]);
    }
}
