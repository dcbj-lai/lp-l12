<?php

namespace App\Http\Controllers;

use App\Models\ResourceReservation;
use App\Support\FacilityBillingStorage;
use Illuminate\Http\Request;

class FacilityBillingAttachmentController extends Controller
{
    public function show(Request $request, ResourceReservation $reservation, string $kind, ?int $proof = null)
    {
        abort_unless($request->user()->hasRole('facility.admin') && !$request->user()->hasRole('facility.viewer'), 403);
        $path = match ($kind) {
            'soa' => $reservation->soa_path,
            'payment' => $proof ? $reservation->paymentProofs()->findOrFail($proof)->path : $reservation->payment_proof_path,
            default => abort(404),
        };
        abort_unless($path && FacilityBillingStorage::disk($path)->exists($path), 404);
        $filename = $kind === 'soa' ? \App\Support\FacilitySoaFilename::forAttachment($reservation) : basename($path);
        return FacilityBillingStorage::disk($path)->download($path, $filename, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
