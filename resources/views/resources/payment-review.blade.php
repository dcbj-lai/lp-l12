<x-layouts.app title="Payment verification">
<div class="facility-workspace space-y-5">
    <header>
        <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Payment verification</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Review payment receipts against the Statement of Account and confirm full payment.</p>
    </header>
    @if($errors->any())<div role="alert" class="facility-alert facility-alert--red"><x-facilities.icon name="alert" /><div><strong>Please check the following</strong>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div></div>@endif
    @if(session('success'))<div role="status" class="facility-alert"><x-facilities.icon name="check" /><div>{{ session('success') }}</div></div>@endif
    @isset($reservation)
        <a class="facility-back" href="{{ route('facilities.payments.index') }}"><x-facilities.icon name="arrow-left" /> Back to payment reviews</a>
        <div class="facility-grid">
            <aside class="facility-card facility-sticky">
                <div class="facility-icon-tile"><x-facilities.icon name="calendar" /></div>
                <div class="facility-card-heading"><span class="facility-eyebrow">Reservation #{{ $reservation->id }}</span><span class="facility-badge {{ $reservation->billing_status === 'paid' ? 'facility-badge--green' : 'facility-badge--amber' }}">{{ ucfirst($reservation->billing_status) }}</span></div>
                <h2>{{ $reservation->title }}</h2>
                <dl class="facility-facts">
                    <div class="facility-wide"><dt>Requested by</dt><dd>{{ $reservation->requester_name ?: 'Not provided' }}<br><span class="facility-small facility-muted">{{ $reservation->requester_email }}</span></dd></div>
                    <div class="facility-wide"><dt>Event schedule</dt><dd>{{ $reservation->start_datetime->format('M j, Y g:i A') }}–{{ $reservation->end_datetime->format('M j, Y g:i A') }}</dd></div>
                    <div class="facility-wide"><dt>Rooms</dt><dd>{{ $reservation->room_names }}</dd></div>
                    <div><dt>Billing</dt><dd>{{ ucfirst($reservation->billing_status) }}{{ $reservation->trashed() ? ' · Deleted' : '' }}</dd></div>
                    <div><dt>Payment due</dt><dd>{{ $reservation->payment_due_at?->format('M j, Y') ?? 'Not set' }}</dd></div>
                </dl>
                @if($reservation->soa_path)<a class="facility-button facility-button--outline facility-button--block" href="{{ route('facilities.payments.soa', $reservation) }}"><x-facilities.icon name="document" /> View current SOA</a>@endif
                <div class="facility-tip"><x-facilities.icon name="lock" /><p>Any active user with the facility.payment-verifier role can review and confirm this payment.</p></div>
            </aside>
            <div class="facility-stack">
    @else
        <nav class="facility-tabs" aria-label="Payment review status">
            @foreach(['pending'=>'Pending', 'confirmed'=>'Confirmed', 'correction'=>'Correction requested', 'superseded'=>'Superseded'] as $key=>$label)
                <a class="facility-tab" href="{{ route('facilities.payments.index', ['status'=>$key]) }}" @if($status === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="facility-stack">
    @endisset
    @forelse($submissions as $submission)
        @php $tone = ['pending'=>'amber','confirmed'=>'green','correction'=>'red','superseded'=>'neutral'][$submission->status]; @endphp
        <article class="facility-card">
            <div class="facility-card-heading">
                <span class="facility-badge facility-badge--{{ $tone }}">{{ ['pending'=>'Payment verification pending', 'confirmed'=>'Payment confirmed', 'correction'=>'Correction requested', 'superseded'=>'Superseded · See review note'][$submission->status] }}</span>
                <span class="facility-small facility-muted">Submission #{{ $submission->id }}</span>
            </div>
            @empty($reservation)
                <h2><a href="{{ route('facilities.payments.review', $submission->reservation_id) }}">{{ $submission->reservation->title }}</a></h2>
                <div class="facility-fact-row"><x-facilities.icon name="calendar" /><span>{{ $submission->reservation->start_datetime->format('M j, Y g:i A') }} · {{ $submission->reservation->room_names }}</span></div>
            @else<h3>Payment submission</h3>@endempty
            <p class="facility-small facility-muted">Submitted {{ $submission->created_at->format('M j, Y g:i A') }}</p>
            <dl class="facility-facts">
                <div><dt>Paid on</dt><dd>{{ $submission->paid_on->format('M j, Y') }}</dd></div>
                <div><dt>Payment reference</dt><dd><span class="facility-reference">{{ $submission->reference }}</span></dd></div>
            </dl>
            <span class="facility-label">Submitted proof{{ count($submission->files) === 1 ? '' : 's' }}</span>
            @foreach($submission->files as $file)
                <a class="facility-file" href="{{ route('facilities.payments.file', [$submission, $loop->index]) }}"><x-facilities.icon name="document" /><span>{{ $file['original_name'] }}</span><x-facilities.icon name="arrow-right" /></a>
            @endforeach
            @if($submission->review_note)<div class="facility-tip"><x-facilities.icon name="mail" /><div><strong>Finance note</strong><p>{{ $submission->review_note }}</p></div></div>@endif
            @if($submission->reviewed_at)<p class="facility-small facility-muted facility-divider">Reviewed {{ $submission->reviewed_at->format('M j, Y g:i A') }}</p>@endif
            @if(isset($reservation) && $canReview && $submission->status === 'pending')
                <form method="post" action="{{ route('facilities.payments.decide', $submission) }}" class="facility-divider">@csrf
                    <h3>Review payment</h3><p class="facility-muted facility-small">Compare the receipt with the SOA before confirming. Request a correction if the proof needs to be clarified.</p>
                    <div class="facility-field"><label for="note-{{ $submission->id }}">Review note (required when requesting correction)</label><textarea id="note-{{ $submission->id }}" name="review_note" maxlength="2000" placeholder="Add a note for the requester">{{ old('review_note') }}</textarea></div>
                    <label class="facility-check"><input type="checkbox" name="confirm_full_payment" value="1"><span>I verified receipt of the full SOA amount.</span></label>
                    <div class="facility-actions"><button type="submit" name="decision" value="confirmed" class="facility-button facility-button--primary"><x-facilities.icon name="check" /> Confirm payment</button><button type="submit" name="decision" value="correction" class="facility-button facility-button--outline">Request correction</button></div>
                </form>
            @elseif(empty($reservation))
                <div class="facility-actions"><a class="facility-button facility-button--outline" href="{{ route('facilities.payments.review', $submission->reservation_id) }}">{{ $submission->status === 'pending' ? 'Review payment' : 'View details' }} <x-facilities.icon name="arrow-right" /></a></div>
            @endif
        </article>
    @empty
        <section class="facility-card facility-empty"><div class="facility-icon-tile"><x-facilities.icon name="document" /></div><h2>Nothing to review here</h2><p class="facility-muted">No payment submissions to display.</p></section>
    @endforelse
        </div>
    @isset($reservation)</div>@else @if($submissions->hasPages())<div class="facility-divider">{{ $submissions->links() }}</div>@endif @endisset
</div>
</x-layouts.app>
