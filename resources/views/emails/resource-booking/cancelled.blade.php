<h2>{{ $label }}</h2>
<p><strong>{{ $details['title'] }}</strong> · #{{ $details['id'] }}</p>
<p>{{ $details['schedule'] }} (Asia/Manila)</p>
<p>Rooms: {{ $details['rooms'] }}</p>
@if ($details['reason'])<p>Reason: {{ $details['reason'] }}</p>@endif
@if ($details['status'] === 'review')
    <p>This reservation remains active while facilities reviews the cancellation and any billing or payment arrangements.</p>
@elseif ($details['status'] === 'cancelled')
    <p>This event date has been cancelled and its room and equipment allocations released. Existing billing and payment records have been retained. Cancellation does not automatically issue a refund or void an SOA.</p>
@else
    <p>The reservation remains active. Please contact facilities to discuss your request.</p>
@endif
<p>For questions or concerns, contact events.facilities@life.edu.ph.</p>
