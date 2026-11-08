<?php

namespace App\Http\Controllers;

use App\Models\{FacilityPaymentSubmission, ResourceReservation};
use App\Services\FacilityPaymentVerificationService as Payments;
use App\Support\{FacilityBillingStorage, FacilitySoaFilename};
use Illuminate\Http\Request;

class FacilityPaymentVerificationController extends Controller
{
    public function show(string $token, Payments $payments)
    {
        [, $reservation] = $payments->resolve($token);
        $latest = $reservation->paymentSubmissions()->latest('id')->first();
        return response()->view('public.resources.payment-upload', compact('token', 'reservation', 'latest', 'payments'))
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function upload(Request $request, string $token, Payments $payments)
    {
        $data = $request->validate(['paid_on' => 'required|date|before_or_equal:today', 'reference' => 'required|string|max:255',
            'proofs' => 'required|array|min:1|max:10', 'proofs.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 'full_payment' => 'accepted'],
            ['full_payment.accepted' => 'Confirm that the payment is for the full SOA amount.', 'proofs.required' => 'Upload at least one payment proof.']);
        $payments->submit($token, $data);
        return redirect()->route('facilities.payment.show', $token)->with('success', 'Proof uploaded. Payment verification pending. You will receive an email after finance reviews it.');
    }

    public function soa(string $token, Payments $payments)
    {
        [, $reservation] = $payments->resolve($token);
        abort_unless(FacilityBillingStorage::disk($reservation->soa_path)->exists($reservation->soa_path), 404);
        return FacilityBillingStorage::disk($reservation->soa_path)->download($reservation->soa_path, FacilitySoaFilename::forAttachment($reservation),
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole([Payments::ROLE, 'facility.admin']), 403);
        $status = $request->query('status', 'pending');
        abort_unless(in_array($status, ['pending', 'confirmed', 'correction', 'superseded'], true), 422);
        $submissions = FacilityPaymentSubmission::with(['reservation', 'submitter'])->where('status', $status)
            ->latest('id')->paginate(20)->withQueryString();
        return response()->view('resources.payment-review', compact('submissions', 'status'))->header('Cache-Control', 'private, no-store');
    }

    public function detail(Request $request, ResourceReservation $reservation, Payments $payments)
    {
        abort_unless($payments->canView($request->user(), $reservation), 403);
        $submissions = $reservation->paymentSubmissions()->with('submitter')->latest('id')->get();
        $canReview = $payments->canReview($request->user(), $reservation);
        return response()->view('resources.payment-review', compact('reservation', 'submissions', 'canReview'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function review(Request $request, FacilityPaymentSubmission $submission, Payments $payments)
    {
        $data = $request->validate(['decision' => 'required|in:confirmed,correction',
            'review_note' => 'required_if:decision,correction|nullable|string|max:2000'],
            ['review_note.required_if' => 'Enter a note explaining what the requester needs to correct.']);
        if ($data['decision'] === 'confirmed') $request->validate(['confirm_full_payment' => 'accepted'],
            ['confirm_full_payment.accepted' => 'Confirm that you verified receipt of the full SOA amount.']);
        $payments->review($submission->id, $request->user(), $data['decision'], $data['review_note'] ?? null);
        return redirect()->route('facilities.payments.review', $submission->reservation_id)
            ->with('success', $data['decision'] === 'confirmed' ? 'Payment confirmed. Reservation marked Paid.' : 'Correction requested. Reservation remains unpaid.');
    }

    public function file(Request $request, FacilityPaymentSubmission $submission, int $file, Payments $payments)
    {
        abort_unless($payments->canView($request->user(), $submission->reservation), 403);
        $item = $submission->files[$file] ?? abort(404);
        abort_unless(FacilityBillingStorage::disk($item['path'])->exists($item['path']), 404);
        return FacilityBillingStorage::disk($item['path'])->download($item['path'], $item['original_name'],
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function financeSoa(Request $request, ResourceReservation $reservation, Payments $payments)
    {
        abort_unless($payments->canView($request->user(), $reservation), 403);
        abort_unless($reservation->soa_path && FacilityBillingStorage::disk($reservation->soa_path)->exists($reservation->soa_path), 404);
        return FacilityBillingStorage::disk($reservation->soa_path)->download($reservation->soa_path, FacilitySoaFilename::forAttachment($reservation),
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
