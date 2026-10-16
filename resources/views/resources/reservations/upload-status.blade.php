@if ($booking->soa_path || $booking->payment_proof_path || $booking->payment_proofs_exists)
    <div class="flex flex-wrap gap-2 text-xs" aria-label="Billing upload status">
        @if ($booking->soa_path)
            <span class="rounded-md bg-blue-100 px-2 py-1 text-blue-800">SOA Uploaded</span>
        @endif
        @if ($booking->payment_proof_path || $booking->payment_proofs_exists)
            <span class="rounded-md bg-green-100 px-2 py-1 text-green-800">Proof of Payment Uploaded</span>
        @endif
    </div>
@endif
