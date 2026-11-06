<x-layouts.facilities title="Upload proof of payment" subtitle="Submit your receipt securely. Finance will review it and confirm your payment.">
    @if(session('success'))<div role="status" class="facility-alert"><x-facilities.icon name="check" /><div>{{ session('success') }}</div></div>@endif
    <div class="facility-grid">
        <aside class="facility-card facility-sticky">
            <div class="facility-icon-tile"><x-facilities.icon name="calendar" /></div>
            <div class="facility-card-heading"><span class="facility-eyebrow">Event details · #{{ $reservation->id }}</span><span class="facility-badge {{ $reservation->soa_locked ? 'facility-badge--green' : 'facility-badge--amber' }}">{{ $reservation->soa_locked ? 'Paid' : 'Awaiting payment' }}</span></div>
            <h2>{{ $reservation->title }}</h2>
            <dl class="facility-facts">
                <div class="facility-wide"><dt>Event schedule</dt><dd>{{ $reservation->start_datetime->format('M j, Y g:i A') }}–{{ $reservation->end_datetime->format('M j, Y g:i A') }}</dd></div>
                <div class="facility-wide"><dt>Rooms</dt><dd>{{ $reservation->room_names }}</dd></div>
                <div><dt>Payment due</dt><dd>{{ $reservation->payment_due_at?->format('M j, Y') ?? 'See SOA' }}</dd></div>
                <div><dt>Time zone</dt><dd>Asia/Manila</dd></div>
            </dl>
            <a class="facility-button facility-button--outline facility-button--block" href="{{ route('facilities.payment.soa', $token) }}"><x-facilities.icon name="document" /> View SOA</a>
            <div class="facility-tip"><x-facilities.icon name="lock" /><p>Your proof is shared only with the authorized Facilities and Finance teams.</p></div>
        </aside>
        <div class="facility-stack">
            @if($reservation->soa_locked)
                <section class="facility-card facility-state"><div class="facility-icon-tile facility-icon-tile--green"><x-facilities.icon name="check" /></div><span class="facility-badge facility-badge--green">Payment complete</span><h2>Payment confirmed · Paid</h2><p>Finance has confirmed your payment. There is no need to upload another receipt.</p></section>
            @elseif($latest?->status === 'pending')
                <section class="facility-card facility-state"><div class="facility-icon-tile facility-icon-tile--amber"><x-facilities.icon name="clock" /></div><span class="facility-badge facility-badge--amber">Awaiting finance review</span><h2>Payment verification pending</h2><p>Your proof has been received. Uploading proof does not mark your reservation paid. We will email you after Finance reviews it.</p></section>
            @elseif(!$payments->payable($reservation))
                <section class="facility-card facility-state"><div class="facility-icon-tile"><x-facilities.icon name="alert" /></div><h2>Uploads are currently unavailable</h2><p>This reservation is not accepting payment submissions. Please contact Facilities.</p></section>
            @else
                @if($latest?->status === 'correction')<div class="facility-alert facility-alert--amber"><x-facilities.icon name="alert" /><div><strong>Correction requested</strong><p>{{ $latest->review_note }}</p></div></div>@endif
                <form action="{{ route('facilities.payment.upload', $token) }}" method="post" enctype="multipart/form-data" class="facility-card">@csrf
                    <h2>Payment details</h2><p class="facility-muted">Submit proof for the full SOA amount. Finance will verify it before marking the reservation Paid.</p>
                    <div class="facility-field"><label for="paid_on">Payment date <span aria-hidden="true">*</span></label><input id="paid_on" name="paid_on" type="date" required max="{{ today()->toDateString() }}" value="{{ old('paid_on', today()->toDateString()) }}"></div>
                    <div class="facility-field"><label for="reference">Payment reference number <span aria-hidden="true">*</span></label><input id="reference" name="reference" required maxlength="255" value="{{ old('reference') }}" placeholder="Enter the bank or transaction reference"></div>
                    <div class="facility-upload"><div class="facility-upload-heading"><x-facilities.icon name="upload" /><span>Add your payment receipt</span></div><div class="facility-field"><label for="proofs">Proof files</label><input id="proofs" name="proofs[]" type="file" multiple required accept=".pdf,.jpg,.jpeg,.png" aria-describedby="proof-help"><p id="proof-help" class="facility-small facility-muted">PDF, JPG or PNG. Up to 10 files, 10 MB each. Files remain private.</p></div></div>
                    <label class="facility-check"><input type="checkbox" name="full_payment" value="1" required><span>I confirm this submission is for the full SOA amount.</span></label>
                    <button type="submit" class="facility-button facility-button--primary facility-button--block">Submit proof for verification <x-facilities.icon name="arrow-right" /></button>
                    <p class="facility-small facility-muted">Keep this private link confidential. No login is required.</p>
                </form>
            @endif
        </div>
    </div>
</x-layouts.facilities>