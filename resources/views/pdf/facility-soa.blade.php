<!DOCTYPE html><html><head><meta charset="utf-8"><style>
@page { margin: 145pt 58pt 80pt; }
body { font-family: 'Crimson Pro', serif; font-size: 10.5pt; line-height:1.1; color:#161616; }
strong, th, h1, h2 { font-weight:700; }
.letterhead { position:fixed; top:-145pt; left:-58pt; width:612pt; height:792pt; }
h1 { font-size:17pt; margin:15pt 0 10pt; } h2 { font-size:10pt; margin:12pt 0 5pt; }
table { width:100%; border-collapse:collapse; } td,th { vertical-align:top; padding:5pt; }
.details td { padding:3pt 0; } .charges td,.charges th { border:0.6pt solid #555; }
.charges th { background:#eee; text-align:left; } .right { text-align:right; } .notes { font-size:9.5pt; line-height:1.3; }
.draft { color:#9e1d20; font-size:8pt; margin-bottom:8pt; } tr { page-break-inside:avoid; }
</style></head><body>
<img class="letterhead" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('soa/letterhead.png'))) }}">
@if ($draft)<p class="draft">DRAFT — Not sent. Issue and due dates are finalized when the SOA is emailed.</p>@endif
<table class="details"><tr><td style="width:65%"><strong>Statement To:</strong><br><strong>{{ $soa['client_name'] }}</strong><br><span style="white-space:pre-line">{{ $soa['business_address'] }}</span></td><td><strong>Issue Date:</strong> {{ $issueDate->format('M j, Y') }}<br><strong>Due Date:</strong> {{ $dueDate->format('M j, Y') }}</td></tr></table>
<h2>Event Details</h2>
<table class="details"><tr><td><strong>Event Name:</strong> {{ $soa['event_name'] }}</td><td><strong>Event Date:</strong> {{ $soa['event_date'] }}</td></tr><tr><td><strong>Venue:</strong> {{ $soa['venue'] }}</td><td><strong>Event Time:</strong> {{ $soa['event_time'] }}</td></tr></table>
<h1>Statement of Account</h1>
<table class="charges"><thead><tr><th style="width:40%"></th><th style="width:12%">QTY.</th><th style="width:25%">RATE</th><th>AMOUNT</th></tr></thead><tbody>
@foreach ($soa['lines'] as $line)<tr><td>{{ $line['label'] }}@if ($line['hours'] ?? null)<br><small>{{ $line['hours'] }} billable hour(s)</small>@endif</td><td>{{ $line['quantity'] }}</td><td>Php {{ number_format($line['rate_cents']/100, 2) }}<br><small>/ {{ $line['unit'] }}</small></td><td class="right">{{ number_format($line['amount_cents']/100, 2) }}</td></tr>@endforeach
<tr><td colspan="3" class="right"><strong>TOTAL AMOUNT DUE:</strong></td><td class="right"><strong>Php {{ number_format($soa['total_cents']/100, 2) }}</strong></td></tr>
</tbody></table>
<h2>Notes:</h2><div class="notes">Reservation is confirmed upon receipt of required downpayment.<br>Full payment must be settled prior to the event date unless otherwise agreed upon.<br>Additional charges may apply for overtime and damages.</div>
<h2>Payment Details</h2><div class="notes"><strong>Mode of Payment:</strong> Bank Transfer<br><strong>Bank Name:</strong> UnionBank<br><strong>Account Name:</strong> The Leadership, Innovation, Faith and Excellence Academy International Inc.<br><strong>Account Number:</strong> 0016 2000 5909<br><br>Kindly send a copy of your proof of payment to finance@life.edu.ph and events.facilities@life.edu.ph.</div>
</body></html>
