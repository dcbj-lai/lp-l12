@php($reg = $registration)
@php($event = $registration->event)
@component('mail::message')
# Your RSVP is recorded

Hi {{ $reg->user->preferred_name ?? $reg->user->name }},

We've recorded your response for **{{ $event->title }}**.

**Your response:** {{ $reg->status === 'attending' ? '✅ Attending' : '❌ Not attending' }}<br>
@if ($event->start_datetime)
**When:** {{ $event->start_datetime->format('M d, Y g:i A') }}<br>
@endif
@if ($event->location)
**Where:** {{ $event->location }}<br>
@endif

You can change your response anytime before the RSVP closes.

@component('mail::button', ['url' => route('events.show', $event->id)])
View Event
@endcomponent

Thanks,<br>The Life Portal Team
@endcomponent
