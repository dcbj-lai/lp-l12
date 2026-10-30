<div>
<flux:modal name="create-facility-soa" class="w-full md:max-w-3xl">
@if ($this->booking)
    <form wire:submit="save" class="space-y-4" x-data x-on:wheel.capture="if ($event.target.matches('input[type=number]')) $event.target.blur()">
        <flux:heading size="lg">{{ $editing ? 'Edit SOA' : 'Create SOA' }}</flux:heading>
        <flux:input wire:model="client_name" label="Client Name" required maxlength="255" />
        <flux:textarea wire:model="business_address" label="Business Address (optional)" maxlength="1000" />
        <div class="grid grid-cols-2 gap-3">
            <div class="text-sm"><strong>Issue Date</strong><p>{{ today()->format('M j, Y') }} (preview)</p></div>
            <div class="text-sm"><strong>Due Date</strong><p>{{ $dueDate->format('M j, Y') }} (preview)</p></div>
        </div>
        <p class="text-xs text-zinc-500">Issue date is finalized on the actual SOA email send date. Due date is 15 business days later, excluding Saturdays and Sundays. Draft dates update if sent on a later day.</p>
        <div class="rounded-md border p-3 text-sm space-y-1">
            <p>Event Name: {{ $this->booking->title }}</p>
            <p>Event Date: {{ $this->booking->start_datetime->format('M j, Y') }}@if (!$this->booking->start_datetime->isSameDay($this->booking->end_datetime))–{{ $this->booking->end_datetime->format('M j, Y') }}@endif</p>
            <p>Venue: {{ $this->booking->room_names }}</p>
            <p>Event Time: {{ $this->booking->start_datetime->format('g:i A') }}–{{ $this->booking->end_datetime->format('g:i A') }} (Manila)</p>
        </div>
        <flux:select wire:model.live="event_type" label="Event type" required><option value="">Select event type</option><option value="ministry">Ministry — PHP 1,200 / 4 hrs / room</option><option value="non_ministry">Non-Ministry — PHP 2,400 / 4 hrs / room</option></flux:select>
        <h3 class="font-semibold">Staff</h3>
        <p class="text-xs text-zinc-500">Allocate hours to the applicable regular, holiday, OT or night-shift rate. Each line is charged separately using the staff quantity below. Set regular hours to zero when all hours use another rate. Rates are selected manually; no holiday or shift is inferred from the event date.</p>
        @foreach (\App\Services\FacilitySoaGenerator::STAFF as $code)
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model.live.debounce.400ms="quantities.{{ $code }}" type="number" min="0" step="1" label="{{ $labels[$code] }} quantity" />
                <flux:input wire:model.live.debounce.400ms="hours.{{ $code }}" type="number" min="0" step="0.01" label="Regular billable hours" />
            </div>
            @foreach ($extra_hours[$code] ?? [] as $period => $value)
                <div wire:key="staff-rate-{{ $code }}-{{ $period }}" class="flex items-end gap-2">
                    <div class="flex-1"><flux:input wire:model.live.debounce.400ms="extra_hours.{{ $code }}.{{ $period }}" type="number" min="0" step="0.01" label="{{ $period === 'night' ? 'Night shift' : $ratePeriods[$period] }} hours" /></div>
                    <flux:button type="button" size="sm" icon="x-mark" aria-label="Remove {{ $labels[$code] }} {{ $period }} rate" wire:click="removeRate('{{ $code }}', '{{ $period }}')" />
                </div>
            @endforeach
            <div class="flex items-end gap-2">
                <div class="flex-1"><flux:select wire:model.live="rate_to_add.{{ $code }}" label="Additional {{ $labels[$code] }} rate">
                    <option value="">Select rate period</option>
                    @foreach (($code === 'security' ? ['night' => 'Night shift'] : []) + $ratePeriods as $period => $label)
                        @unless (array_key_exists($period, $extra_hours[$code] ?? []))
                            <option value="{{ $period }}">{{ $label }} — {{ isset($referenceRates[$code.'_'.$period]) ? 'PHP '.number_format($referenceRates[$code.'_'.$period]/100, 2).'/hr' : 'Not configured' }}</option>
                        @endunless
                    @endforeach
                </flux:select></div>
                <flux:button type="button" size="sm" wire:click="addRate('{{ $code }}')" :disabled="empty($rate_to_add[$code])">Add rate</flux:button>
            </div>
            @include('livewire.resources.staff-rate-reference')
        @endforeach
        <h3 class="font-semibold">Consumables</h3>
        <div class="grid grid-cols-2 gap-3">
        @foreach (\App\Services\FacilitySoaGenerator::CONSUMABLES as $code)
            <flux:input wire:model.live.debounce.400ms="quantities.{{ $code }}" type="number" min="0" step="1" label="{{ $labels[$code] }} quantity" />
        @endforeach
        </div>
        @php $quote = $this->quote; @endphp
        @if (isset($quote['errors']))<div role="alert" class="text-sm text-red-600">@foreach ($quote['errors'] as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if (isset($quote['lines']))
            <div class="rounded-md border p-3 text-sm space-y-2">
                <p>{{ $quote['rooms'] }} room(s) × {{ $quote['blocks'] }} full 4-hour block(s). Staff and consumables use configured rates.</p>
                @foreach ($quote['lines'] as $line)
                    <div class="flex justify-between gap-2">
                        <span>{{ $line['label'] }} · {{ $line['quantity'] }}
                            @if ($line['hours'] ?? null) × {{ $line['hours'] }} hrs @endif
                        </span>
                        <span>PHP {{ number_format($line['amount_cents']/100, 2) }}</span>
                    </div>
                @endforeach
                <p class="border-t pt-2 font-semibold">Total amount due: PHP {{ number_format($quote['total_cents']/100, 2) }}</p>
            </div>
        @endif
        @if ($this->booking->soa_path)<flux:textarea wire:model="replacement_reason" label="Reason for replacing existing SOA" required maxlength="2000" />@endif
        @if ($errors->any())<div role="alert" class="text-sm text-red-600">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="flex flex-wrap justify-end gap-2"><flux:modal.close><flux:button type="button">Cancel</flux:button></flux:modal.close><flux:button type="button" wire:click="preview" wire:loading.attr="disabled">Preview PDF</flux:button><flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ $editing ? 'Save SOA' : 'Create SOA' }}</flux:button></div>
        <p class="text-xs text-zinc-500">{{ $editing ? 'Saving keeps the previous file in the replacement history and saves a new draft SOA.' : 'Creating saves a draft SOA to the reservation.' }} Use Billing → Send SOA to requester to issue and email it.</p>
    </form>
@endif
</flux:modal>
</div>
