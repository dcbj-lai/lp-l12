@if (!empty($requesterActions['manage_url']))
    <p style="margin-top:20px"><a href="{{ $requesterActions['manage_url'] }}" style="display:inline-block;padding:10px 16px;background:#18181b;color:#fff;text-decoration:none;border-radius:6px">{{ $requesterActions['manage_label'] }}</a></p>
    <p style="font-size:12px;color:#71717a">No login is required. Keep this link private. Opening it does not cancel your reservation. Cancellation of billed or paid events requires facilities review.</p>
@endif
@if (!empty($requesterActions['payment_url']))
    <p style="margin-top:16px"><a href="{{ $requesterActions['payment_url'] }}" style="display:inline-block;padding:10px 16px;background:#1d4ed8;color:#fff;text-decoration:none;border-radius:6px">Upload proof of payment</a></p>
    <p style="font-size:12px;color:#71717a">After paying the full SOA amount, upload your proof for finance verification. No login is required. This private link expires in 90 days and only applies to this reservation’s current SOA. Uploading proof does not mark the reservation Paid.</p>
@elseif (!empty($requesterActions['payment_pending']))
    <p style="padding:10px;background:#fef3c7;color:#92400e">Payment verification pending. Finance is reviewing your submitted proof.</p>
@endif
