<!DOCTYPE html><html><head><title>Facilities billing email previews</title><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f5;color:#18181b;padding:24px">
<h1>Facilities billing email previews</h1><p>Sample content only. No emails are sent from this page. Each actual email includes the SOA as an attachment.</p>
@foreach ($scenarios as $scenario => $label)
<section style="margin-bottom:32px"><h2>{{ $label }}</h2><a href="{{ url('/dev/facilities/billing-email/' . $scenario) }}" target="_blank">Open email preview</a><iframe title="{{ $label }}" src="{{ url('/dev/facilities/billing-email/' . $scenario) }}" style="display:block;width:100%;max-width:700px;height:850px;border:1px solid #ddd;margin-top:12px"></iframe></section>
@endforeach
</body></html>
