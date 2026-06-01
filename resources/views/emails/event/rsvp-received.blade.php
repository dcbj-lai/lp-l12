@php($reg = $registration)
@php($event = $registration->event)
@php($user = $registration->user)
@component('mail::message')
# Event RSVP Update

**{{ $user->name }}** has responded to an event.

**Event:** {{ $event->title }}<br>
**Response:** {{ $reg->status === 'attending' ? '✅ Attending' : '❌ Not attending' }}<br>
@if ($event->start_datetime)
**When:** {{ $event->start_datetime->format('M d, Y g:i A') }}<br>
@endif
@if ($event->location)
**Where:** {{ $event->location }}<br>
@endif
@if ($reg->status === 'attending' && $reg->guest_count)
**Guests:** {{ $reg->guest_count }}<br>
@endif

**Staff details (from profile):**<br>
Department: {{ $user->department->name ?? '—' }}<br>
Mobile: {{ $user->phone_mobile ?? '—' }}<br>
Dietary: {{ $user->dietary_preference ?? '—' }}<br>
Allergies / Medical: {{ $user->medical_notes ?? '—' }}<br>
Emergency Contact: {{ $user->emergency_contact_name ?? '—' }}
@if ($user->emergency_contact_phone)({{ $user->emergency_contact_phone }})@endif<br>

@component('mail::button', ['url' => route('events.show', $event->id)])
View Event
@endcomponent

Thanks,<br>The Life Portal Team
@endcomponent
