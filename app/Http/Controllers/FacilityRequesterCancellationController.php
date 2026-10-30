<?php

namespace App\Http\Controllers;

use App\Services\FacilityRequesterCancellationService;
use Illuminate\Http\Request;

class FacilityRequesterCancellationController extends Controller
{
    public function show(string $token, FacilityRequesterCancellationService $service)
    {
        $link = $service->resolve($token);
        return response()->view('public.resources.manage-booking', ['bookings' => $service->bookings($link),
            'seriesBookings' => $service->seriesBookings($link), 'token' => $token])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function cancel(Request $request, string $token, FacilityRequesterCancellationService $service)
    {
        $link = $service->resolve($token);
        $data = $request->validate(['scope' => 'required|in:date,series,selected', 'reservation_id' => 'required|integer',
            'booking_ids' => 'required_if:scope,series|array|max:366', 'booking_ids.*' => 'integer|distinct',
            'selected_booking_ids' => 'required_if:scope,selected|array|min:1|max:366', 'selected_booking_ids.*' => 'integer|distinct',
            'reason' => 'nullable|string|max:2000', 'confirm' => 'accepted'],
            ['selected_booking_ids.required_if' => 'Select at least one event date to cancel.']);
        $ids = $data['scope'] === 'selected' ? ($data['selected_booking_ids'] ?? []) : ($data['booking_ids'] ?? []);
        $results = $service->request($link, (int) $data['reservation_id'], trim($data['reason'] ?? ''), $data['scope'], $ids);
        return redirect()->route('facilities.requester.show', ['token' => $token])->with('cancellation_results', $results)
            ->with('cancellation_submitted', true)->header('Cache-Control', 'private, no-store');
    }
}
