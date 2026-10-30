<details class="rounded-md border p-3 text-sm" wire:key="soa-rate-reference-{{ $code }}">
    <summary class="cursor-pointer font-medium">{{ $labels[$code] }} · Rate reference</summary>
    <table class="mt-3 w-full text-sm">
        <caption class="sr-only">Configured {{ $labels[$code] }} rates in PHP per person per hour</caption>
        <thead><tr class="border-b"><th scope="col" class="py-2 text-left">Period</th><th scope="col" class="py-2 text-right">PHP/hour</th></tr></thead>
        <tbody>
            @php
                $staffPeriods = [$code => $code === 'security' ? 'Day shift' : 'Regular fee'];
                if ($code === 'security') $staffPeriods['security_night'] = 'Night shift';
                foreach ($ratePeriods as $period => $periodLabel) $staffPeriods[$code.'_'.$period] = $periodLabel;
            @endphp
            @foreach ($staffPeriods as $rateCode => $periodLabel)
                @php $amount = $referenceRates[$rateCode] ?? null; @endphp
                <tr class="border-b last:border-0">
                    <th scope="row" class="py-2 pr-3 text-left font-normal">{{ $periodLabel }}</th>
                    <td class="py-2 text-right whitespace-nowrap">{{ $amount === null ? 'Not configured' : number_format($amount / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($code !== 'security')
        <p class="mt-2 text-xs text-zinc-500">OT/holiday amounts use the PHP 755/day reference schedule. First-eight-hour special/legal holiday entries retain the listed charges, including premium-only amounts.</p>
    @endif
</details>
