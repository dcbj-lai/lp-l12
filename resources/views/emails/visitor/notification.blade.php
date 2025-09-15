@component('mail::message')
# New Visitor: {{ $visitor->full_name }}

**Mobile:** {{ $visitor->mobile }}<br>
**Purpose:** {{ $visitor->purpose }}<br>
**Check-In Time:** {{ \Carbon\Carbon::parse($visitor->check_in_at)->toDayDateTimeString() }}<br>

@component('mail::button', ['url' => route('visitors.show', $visitor->id)])
View Visitor
@endcomponent

Thanks,<br>
The Life Portal Team
@endcomponent
