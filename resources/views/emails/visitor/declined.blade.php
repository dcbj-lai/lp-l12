@component('mail::message')
# Visitor Visit Declined

The visit scheduled by **{{ $visitor->full_name }}** has been declined by the visited party.

**Visitor Details:**
- **Company:** {{ $visitor->company }}
- **Email:** {{ $visitor->email }}
- **Mobile:** {{ $visitor->mobile }}
- **Purpose:** {{ $visitor->purpose ?? '-' }}
- **Meetup Spot:** {{ $visitor->meetup_spot ?? '-' }}

@component('mail::button', ['url' => route('frontdesk.visitors.show', $visitor->id)])
View Visitor
@endcomponent

Thanks,<br>
The Life Portal Team
@endcomponent
