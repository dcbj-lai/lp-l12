@component('mail::message')
# You're Invited!

@if ($inviteeName)
Hi {{ $inviteeName }},
@endif

You've been invited to **{{ $event->title }}**.

@if ($event->start_datetime)
**When:** {{ $event->start_datetime->format('M d, Y g:i A') }}<br>
@endif
@if ($event->location)
**Where:** {{ $event->location }}<br>
@endif
@if ($event->rsvp_deadline)
**Please RSVP by:** {{ $event->rsvp_deadline->format('M d, Y g:i A') }}<br>
@endif

@if ($event->description)
{{ \Illuminate\Support\Str::limit($event->description, 300) }}
@endif

@component('mail::button', ['url' => route('events.show', $event->id)])
View Event &amp; RSVP
@endcomponent

Thanks,<br>The Life Portal Team
@endcomponent
