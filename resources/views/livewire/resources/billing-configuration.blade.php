<div class="max-w-2xl space-y-5" x-data x-on:wheel.capture="if ($event.target.matches('input[type=number]')) $event.target.blur()">
    <h1 class="text-2xl font-semibold">Billing configuration</h1>
    <p class="text-sm text-zinc-500">Amounts in PHP. Blank means not configured; zero means free. Existing SOAs retain their saved rates.</p>
    @if ($saved)<p role="status" class="rounded-md bg-green-50 p-3 text-green-800">Billing rates saved.</p>@endif
    <form wire:submit="save" class="space-y-4">
        <h2 class="font-semibold">Staff fees per person per hour</h2>
        @foreach (\App\Services\FacilitySoaGenerator::STAFF as $code)
            <flux:input type="number" min="0" step="0.01" wire:model="rates.{{ $code }}" label="{{ $code === 'security' ? 'Security — day shift' : $labels[$code] }} (PHP/hour)" />
        @endforeach
        <flux:input type="number" min="0" step="0.01" wire:model="rates.security_night" label="Security — night shift (PHP/hour)" />
        <section class="space-y-3 border-t pt-4">
            <h2 class="font-semibold">Holiday and overtime rates</h2>
            <p class="text-sm text-zinc-500">PHP per person per hour, grouped using the OT rate list. Enter the actual charge for each period, not a percentage. Blank means not configured.</p>
            <p class="text-sm text-zinc-500">Housekeeping and Logistics follow the PHP 755/day reference schedule. Special-holiday and legal-holiday first-eight-hour entries retain the amounts shown in the list, including premium-only charges. Security OT rates are awaiting confirmation.</p>
            <p class="text-sm text-zinc-500">Holiday, OT and Security night-shift rates are saved separately; selecting these rates in Create SOA is not yet enabled. Current SOAs use the regular staff fees above.</p>
            @foreach (\App\Services\FacilitySoaGenerator::STAFF as $staff)
                <details class="rounded-lg border p-3" wire:key="holiday-rates-{{ $staff }}">
                    <summary class="cursor-pointer font-medium">{{ $labels[$staff] }}</summary>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($periods as $period => $label)
                            <flux:input type="number" min="0" step="0.01" wire:model="rates.{{ $staff }}_{{ $period }}" label="{{ $label }} (PHP/hour)" />
                        @endforeach
                    </div>
                </details>
            @endforeach
            <a href="https://drive.google.com/file/d/1hMOXkeQd4L8P9ys4HTrKQy6k-sBSMNne/view" target="_blank" rel="noopener noreferrer" class="inline-block text-sm text-blue-600 underline">Reference: LIFE Academy OT rates</a>
        </section>
        <h2 class="pt-3 font-semibold">Consumables per unit</h2>
        @foreach (\App\Services\FacilitySoaGenerator::CONSUMABLES as $code)
            <flux:input type="number" min="0" step="0.01" wire:model="rates.{{ $code }}" label="{{ $labels[$code] }} (PHP/unit)" />
        @endforeach
        <p class="text-sm text-zinc-500">Venue: Ministry PHP 1,200 / Non-Ministry PHP 2,400 per room per 4-hour block. Each additional started block is charged in full.</p>
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Save configuration</flux:button>
    </form>
</div>
