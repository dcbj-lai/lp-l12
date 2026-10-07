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

        $direction = $request->query('sort') === 'desc' ? 'desc' : 'asc';
        $status = $request->query('status') === 'pending' ? 'pending' : 'approved';
        $reservations = ResourceReservation::with(['resource', 'rooms', 'equipment'])
            ->where('status', $status)->orderBy('start_datetime', $direction)
            ->orderBy('id', $direction)->paginate(30)->withQueryString();
        $seriesDates = ResourceReservation::with(['resource', 'rooms', 'equipment'])
            ->whereIn('recurrence_series_id', $reservations->pluck('recurrence_series_id')->filter()->unique())
            ->whereIn('status', ['pending', 'approved'])->orderBy('start_datetime')->orderBy('id')
            ->get()->groupBy('recurrence_series_id');

        return view('resources.reservations.viewer', compact('reservations', 'direction', 'status', 'seriesDates'));
    }

    public function floorPlan(Request $request, ResourceReservation $reservation)
    {
        abort_unless($request->user()->hasRole('facility.viewer') && in_array($reservation->status, ['pending', 'approved'], true) && $reservation->floor_plan_path, 403);
        return Storage::disk(config('filesystems.facility_upload_disk'))->response($reservation->floor_plan_path);
    }
}
