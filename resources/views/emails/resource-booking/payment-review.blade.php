<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;color:#18181b;line-height:1.6">
<div style="max-width:620px;margin:24px auto;padding:28px;background:#fafafa">
<p>LIFE Portal · Facilities</p><h2>{{ $title }}</h2><p style="white-space:pre-line">{{ $messageText }}</p>
@if(!$reservationId)<p><a href="{{ $actionUrl }}" style="display:inline-block;background:#18181b;color:white;padding:12px 18px;border-radius:6px">{{ $actionLabel }}</a></p>@endif
@include('emails.resource-booking.requester-actions')
<p>For questions, contact <a href="mailto:events.facilities@life.edu.ph">events.facilities@life.edu.ph</a>.</p>
</div></body></html>
