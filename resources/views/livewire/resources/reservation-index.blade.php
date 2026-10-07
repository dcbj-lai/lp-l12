<div class="space-y-5" wire:poll.60s x-data x-on:wheel.capture="if ($event.target.matches('input[type=number]')) $event.target.blur()">
    @php
        $isFacilityAdmin = auth()->user()->hasRole('facility.admin');
        $unsentEditCounts = $isFacilityAdmin ? $this->unsentEditCounts : [];
    @endphp

    <div class="inline-flex max-w-full flex-wrap rounded-lg border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800" role="tablist" aria-label="Reservation status">
        @foreach (['all' => 'All', 'pending' => 'For Approval', 'approved' => 'Approved', 'billed' => 'Billed', 'paid' => 'Paid', 'archive' => 'Done', 'rejected' => 'Rejected', 'deleted' => 'Deleted'] as $status => $label)
            <button type="button" role="tab" aria-selected="{{ $statusFilter === $status ? 'true' : 'false' }}" wire:click="$set('statusFilter', '{{ $status }}')"
                class="rounded-md px-4 py-2 text-sm font-medium {{ $statusFilter === $status ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white' }}">{{ $label }}</button>
        @endforeach
    </div>

    <flux:modal name="bulk-payment-reminders" wire:model="showBulkReminderModal"><div class="space-y-4 p-4">
        <h2 class="font-semibold">Send payment reminders</h2>
        <p>Send reminders to {{ count($bulkReminderIds) }} eligible unpaid billed reservations. Paid reservations, unsent SOAs, missing email addresses, queued emails, and reminders already sent today are excluded.</p>
        <div class="flex justify-end gap-2"><flux:button wire:click="cancelBulkReminders">Cancel</flux:button><flux:button variant="primary" wire:click="sendBulkReminders" wire:loading.attr="disabled" wire:target="sendBulkReminders" :disabled="count($bulkReminderIds) === 0">Send reminders</flux:button></div>
    </div></flux:modal>

    <div class="flex flex-wrap items-center gap-2 text-sm">
        @if ($statusFilter === 'pending')
            <label for="request-date-sort" class="font-medium">Sort by request date</label>
            <select id="request-date-sort" wire:model.live="requestDateSort" class="rounded-md border bg-white px-3 py-2 dark:bg-zinc-800">
                <option value="desc">Newest first</option>
                <option value="asc">Oldest first</option>
            </select>
        @else
            <label for="event-date-sort" class="font-medium">Sort by event date</label>
            <select id="event-date-sort" wire:model.live="eventDateSort" class="rounded-md border bg-white px-3 py-2 dark:bg-zinc-800">
                <option value="asc">Earliest first</option>
                <option value="desc">Latest first</option>
            </select>
        @endif
        @if ($statusFilter === 'billed' && $isFacilityAdmin)
            <div class="ml-auto"><flux:button size="sm" wire:click="selectBulkReminders">Send payment reminders</flux:button></div>
        @endif
    </div>

    @forelse ($this->bookingGroups as $month => $monthlyReservations)
        <section wire:key="reservation-month-{{ $statusFilter }}-{{ $month }}" class="space-y-3">
            <h2 class="text-lg font-semibold">{{ $month }}</h2>
            @foreach ($monthlyReservations->groupBy(fn ($group) => $group['display_date']->format('Y-m-d')) as $date => $dailyReservations)
                <div wire:key="reservation-date-{{ $statusFilter }}-{{ $date }}" class="space-y-2">
                    <h3 class="text-sm font-medium text-zinc-500">{{ $statusFilter === 'pending' ? 'Requested ' : '' }}{{ \Carbon\Carbon::parse($date)->format('l, F j') }}</h3>
                    @foreach ($dailyReservations as $group)
                        @php $res = $group['representative']; @endphp
                        @if ($group['is_series'])
                            <article wire:key="series-{{ $group['series_id'] }}" class="rounded-lg border bg-white p-4 shadow-sm dark:bg-zinc-800 space-y-3">
                                <div>
                                    <div class="font-semibold">{{ $res->title }} <span class="text-xs font-normal text-zinc-500">· Recurring event</span></div>
                                    <div class="text-xs text-zinc-500">{{ $res->recurrence_label ?: 'Recurring reservation' }} · {{ $res->room_names }} · {{ $res->requester_email }}</div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <flux:badge color="zinc">{{ $res->recurrence_total ?: $group['matched_count'] }} dates</flux:badge>
                                        @if ($group['conflict_count']) <flux:badge color="red">Conflict with approved booking: {{ $group['conflict_count'] }} {{ \Illuminate\Support\Str::plural('date', $group['conflict_count']) }}</flux:badge> @endif
                                        @if ($statusFilter === 'all')
                                            @foreach (['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $status => $label)
                                                @if ($group['status_counts']->get($status, 0)) <flux:badge color="{{ $status === 'approved' ? 'green' : ($status === 'rejected' ? 'red' : 'yellow') }}">{{ $label }}: {{ $group['status_counts']->get($status) }}</flux:badge> @endif
                                            @endforeach
                                        @else
                                            <flux:badge color="{{ $statusFilter === 'approved' ? 'green' : ($statusFilter === 'rejected' ? 'red' : 'yellow') }}">{{ $statusFilter === 'pending' ? 'For Approval' : ucfirst($statusFilter) }}: {{ $group['matched_count'] }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $group['has_upcoming'] ? 'Next event' : 'Last event' }}: {{ $res->start_datetime->format('M j, Y g:i A') }}–{{ $res->end_datetime->format('g:i A') }}
                                </div>
                                @if ($group['conflict_count']) <div class="text-xs text-red-700 dark:text-red-300">Conflicting resources: {{ implode(', ', $group['conflict_names']) }}. Open the series to see affected dates.</div> @endif
                                <flux:button size="sm" wire:click="showRecurringSeries('{{ $group['series_id'] }}')">View recurring events</flux:button>
                            </article>
                        @else
                        <article id="res-{{ $res->id }}" wire:key="reservation-{{ $res->id }}" class="relative rounded-lg border bg-white p-4 shadow-sm dark:bg-zinc-800 space-y-3">
                            @if ($statusFilter !== 'deleted' && $isFacilityAdmin)
                                <div class="absolute right-4 top-4" @if($res->soa_path) title="SOA is uploaded" @endif>
                                    @if ($res->soa_path)
                                        <flux:button size="sm" variant="danger" icon="trash" disabled aria-label="Delete reservation #{{ $res->id }}" title="SOA is uploaded"></flux:button>
                                    @else
                                        <flux:button size="sm" variant="danger" icon="trash" wire:click="selectForDelete({{ $res->id }})" aria-label="Delete reservation #{{ $res->id }}" title="Delete reservation #{{ $res->id }}"></flux:button>
                                    @endif
                                </div>
                            @endif
                            <div @if ($statusFilter !== 'deleted' && $isFacilityAdmin) style="padding-right: 3rem;" @endif>
                                <div class="font-semibold">{{ $res->title }} <span class="text-xs font-normal text-zinc-500">#{{ $res->id }}</span></div>
                                <div class="text-xs text-zinc-500">Event: {{ $res->start_datetime->format('M j, Y g:i A') }}–{{ $res->end_datetime->format('M j, Y g:i A') }} · {{ $res->room_names }} · {{ $res->requester_email }}</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <flux:badge color="{{ $res->status === 'approved' ? 'green' : ($res->status === 'rejected' ? 'red' : 'yellow') }}">{{ ['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$res->status] ?? $res->status }}</flux:badge>
                                    @if ($res->is_archived) <flux:badge color="zinc">Done{{ $res->finished_confirmed_at ? ' · No payment required' : '' }}</flux:badge> @endif
                                    @if ($group['conflict_count']) <flux:badge color="red">Conflict with approved booking</flux:badge> @endif
                                    <flux:badge color="{{ $res->billing_status === 'paid' ? 'green' : 'zinc' }}">{{ ucfirst($res->billing_status) }}</flux:badge>
                                    @if ($res->billing_status === 'billed' && $res->payment_due_at && $res->payment_due_at->isBefore(today())) <flux:badge color="red">Overdue</flux:badge> @endif
                                </div>
                            </div>
                            @if ($group['conflict_count']) <div class="flex flex-wrap items-center gap-2 rounded-md border border-red-200 bg-red-50 p-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"><span>Conflicting resources: {{ implode(', ', $group['conflict_names']) }}. Resolve before approval.</span><flux:button size="sm" wire:click="showConflictManager({{ $res->id }})">Manage conflict</flux:button></div> @endif
                            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                @if ($res->number_of_pax) {{ $res->number_of_pax }} pax · @endif
                                @if ($res->setup_arrangement) {{ $res->setup_arrangement }} setup · @endif
                                @if ($res->contact_number) {{ $res->contact_number }} @endif
                                @if ($res->recurrence_series_id)
                                    <div class="mt-1 rounded-md bg-blue-50 px-2 py-1 text-xs text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                        Recurring event{{ $res->recurrence_position && $res->recurrence_total ? ' · ' . $res->recurrence_position . ' of ' . $res->recurrence_total : '' }}
                                        @if ($res->recurrence_label) · {{ $res->recurrence_label }} @endif
                                    </div>
                                @endif
                                @if ($res->equipment->isNotEmpty()) <div>Equipment: {{ $res->equipment->map(fn ($item) => $item->name . ' × ' . $item->pivot->quantity)->join(', ') }}</div> @endif
                                @if ($res->consumables) <div>Consumables: {{ $res->consumables }}</div> @endif
                                @if ($res->notes) <div class="whitespace-pre-line">Notes: {{ $res->notes }}</div> @endif
                            </div>
                            @if ($res->status === 'rejected' && $res->approval_note)
                                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">Rejection reason: {{ $res->approval_note }}</div>
                            @endif
                            <div class="flex flex-wrap gap-3 text-sm">
                                @foreach (['floor_plan_path' => 'Floor plan', 'gate_pass_path' => 'Gate pass', 'attachment_path' => 'Attachment', 'soa_path' => 'SOA'] as $field => $label)
                                    @if ($res->{$field}) <a class="text-blue-700 underline" href="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($res->{$field}) }}" target="_blank" rel="noopener">{{ $label }}</a> @endif
                                @endforeach
                                @include('livewire.resources.payment-proofs', ['reservation' => $res])
                            </div>
                            @if ($res->soa_sent_at) <div class="text-xs text-zinc-500">{{ $res->soa_email_pending ? 'Previous SOA sent' : 'SOA sent' }} {{ $res->soa_sent_at->format('M j, Y') }} · Due {{ $res->payment_due_at?->format('M j, Y') }}</div> @endif
                            @if ($res->paid_at) <div class="text-xs text-zinc-500">Paid {{ $res->paid_at->format('M j, Y') }}</div> @endif
                            <div class="flex flex-wrap gap-2">
                                @if ($res->recurrence_series_id) <flux:button size="sm" wire:click="showRecurringSeries('{{ $res->recurrence_series_id }}')">View recurring events</flux:button> @endif
                                @if ($statusFilter === 'deleted')
                                    @if ($isFacilityAdmin) <flux:button size="sm" wire:click="restoreReservation({{ $res->id }})">Restore for approval</flux:button> @endif
                                @else
                                @if ($res->status !== 'approved')
                                    @if ($group['conflict_count'])
                                        <flux:button size="sm" variant="primary" disabled title="Resolve the conflict before approval">Approve</flux:button>
                                    @else
                                        <flux:modal.trigger name="approve-reservation"><flux:button size="sm" variant="primary" wire:click="selectForDecision({{ $res->id }})">Approve</flux:button></flux:modal.trigger>
                                    @endif
                                @endif
                                @if ($res->status !== 'rejected')
                                    <flux:modal.trigger name="reject-reservation"><flux:button size="sm" variant="danger" wire:click="selectForDecision({{ $res->id }})">Reject</flux:button></flux:modal.trigger>
                                @endif
                                @if ($isFacilityAdmin)
                                    @if ($res->status === 'approved' && !$res->is_archived && $res->end_datetime->lte(now()) && !$res->soa_path && $res->billing_status === 'unbilled')
                                        <flux:modal.trigger name="finish-reservation"><flux:button size="sm" wire:click="$set('finishId', {{ $res->id }})">Confirm finished</flux:button></flux:modal.trigger>
                                    @endif
                                    <flux:button wire:key="edit-action-{{ $res->id }}" size="sm" variant="primary" color="yellow" wire:click="selectForEdit({{ $res->id }})" wire:loading.attr="disabled">Edit</flux:button>
                                    @if (($unsentEditCounts[$res->id] ?? 0) > 0) <flux:button size="sm" wire:click="emailReservationChanges({{ $res->id }})" wire:loading.attr="disabled" wire:target="emailReservationChanges({{ $res->id }})">Email changes to requester</flux:button> @endif
                                    @if ($res->status === 'approved')
                                        @include('livewire.resources.soa-actions', ['reservation' => $res])
                                    @endif
                                    @if ($res->status === 'approved' && $res->soa_path) <flux:modal.trigger name="payment-reservation"><flux:button size="sm" wire:click="selectForPayment({{ $res->id }})">Record payment</flux:button></flux:modal.trigger> @endif
                                @endif
                                @endif
                            </div>
                        </article>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </section>
    @empty
        <p class="text-sm text-zinc-500">No reservations found.</p>
    @endforelse

    <flux:modal name="recurring-series" class="md:w-[760px]">
        <div class="space-y-4 p-4">
            @php $seriesReservations = $this->selectedSeriesReservations; @endphp
            <div>
                <h2 class="text-lg font-semibold">{{ $seriesReservations->first()?->title ?? 'Recurring events' }}</h2>
                <p class="text-sm text-zinc-500">{{ $seriesReservations->first()?->recurrence_label }} · {{ $seriesReservations->count() }} event dates</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($isFacilityAdmin && $seriesReservations->filter(fn ($item) => !$item->trashed())->isNotEmpty())
                    <flux:button size="sm" variant="danger" icon="trash" wire:click="selectSeriesForDelete">Delete all dates</flux:button>
                @endif
                @if ($seriesReservations->where('status', 'pending')->filter(fn ($item) => !$item->trashed())->isNotEmpty())
                    <flux:modal.trigger name="approve-series"><flux:button size="sm" variant="primary">Approve all pending dates</flux:button></flux:modal.trigger>
                @endif
                @if ($isFacilityAdmin && $seriesReservations->filter(fn ($item) => $item->trashed())->isNotEmpty())
                    <flux:modal.trigger name="restore-series"><flux:button size="sm">Restore all deleted dates</flux:button></flux:modal.trigger>
                @endif
            </div>
            <div class="max-h-[65vh] space-y-3 overflow-y-auto pr-1">
                @foreach ($seriesReservations as $occurrence)
                    @php $occurrenceConflicts = $this->conflictsFor($occurrence); @endphp
                    <div wire:key="series-occurrence-{{ $occurrence->id }}" class="relative rounded-md border border-zinc-200 p-3 space-y-2 dark:border-zinc-700">
                        @if (!$occurrence->trashed() && $isFacilityAdmin)
                            <div class="absolute right-4 top-4" @if($occurrence->soa_path) title="SOA is uploaded" @endif>
                                @if ($occurrence->soa_path)
                                    <flux:button size="sm" variant="danger" icon="trash" disabled aria-label="Delete reservation #{{ $occurrence->id }}" title="SOA is uploaded"></flux:button>
                                @else
                                    <flux:button size="sm" variant="danger" icon="trash" wire:click="selectForDelete({{ $occurrence->id }})" aria-label="Delete reservation #{{ $occurrence->id }}" title="Delete reservation #{{ $occurrence->id }}"></flux:button>
                                @endif
                            </div>
                        @endif
                        <div @if (!$occurrence->trashed() && $isFacilityAdmin) style="padding-right: 3rem;" @endif>
                            <div class="font-medium">{{ $occurrence->start_datetime->format('D, M j, Y') }} · {{ $occurrence->start_datetime->format('g:i A') }}–{{ $occurrence->end_datetime->format('g:i A') }}</div>
                            <div class="text-xs text-zinc-500">#{{ $occurrence->id }} · {{ $occurrence->recurrence_position }} of {{ $occurrence->recurrence_total }} · {{ $occurrence->room_names }}</div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <flux:badge color="{{ $occurrence->trashed() ? 'zinc' : ($occurrence->status === 'approved' ? 'green' : ($occurrence->status === 'rejected' ? 'red' : 'yellow')) }}">{{ $occurrence->trashed() ? 'Deleted' : (['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$occurrence->status] ?? $occurrence->status) }}</flux:badge>
                                @if ($occurrenceConflicts) <flux:badge color="red">Conflict with approved booking</flux:badge> @endif
                            </div>
                        </div>
                        @if ($occurrenceConflicts) <div class="flex flex-wrap items-center gap-2 text-xs text-red-700 dark:text-red-300"><span>Conflicting resources: {{ implode(', ', $occurrenceConflicts) }}. Resolve before approval.</span><flux:button size="sm" wire:click="showConflictManager({{ $occurrence->id }})">Manage conflict</flux:button></div> @endif
                        @if ($occurrence->equipment->isNotEmpty()) <div class="text-xs">Equipment: {{ $occurrence->equipment->map(fn ($item) => $item->name . ' × ' . $item->pivot->quantity)->join(', ') }}</div> @endif
                        @if ($occurrence->notes) <div class="text-xs whitespace-pre-line">Notes: {{ $occurrence->notes }}</div> @endif
                        @if ($occurrence->status === 'rejected' && $occurrence->approval_note) <div class="text-xs text-red-700">Rejection reason: {{ $occurrence->approval_note }}</div> @endif
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            @foreach (['floor_plan_path' => 'Floor plan', 'gate_pass_path' => 'Gate pass', 'soa_path' => 'SOA'] as $field => $label)
                                @if ($occurrence->{$field}) <a class="text-blue-700 underline" href="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($occurrence->{$field}) }}" target="_blank" rel="noopener">{{ $label }}</a> @endif
                            @endforeach
                                @include('livewire.resources.payment-proofs', ['reservation' => $occurrence])
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($occurrence->trashed())
                                @if ($isFacilityAdmin) <flux:button size="sm" wire:click="restoreReservation({{ $occurrence->id }})">Restore for approval</flux:button> @endif
                            @else
                                @if ($occurrence->status !== 'approved')
                                    @if ($occurrenceConflicts)
                                        <flux:button size="sm" variant="primary" disabled title="Resolve the conflict before approval">Approve</flux:button>
                                    @else
                                        <flux:modal.trigger name="approve-reservation"><flux:button size="sm" variant="primary" wire:click="selectForDecision({{ $occurrence->id }})">Approve</flux:button></flux:modal.trigger>
                                    @endif
                                @endif
                                @if ($occurrence->status !== 'rejected') <flux:modal.trigger name="reject-reservation"><flux:button size="sm" variant="danger" wire:click="selectForDecision({{ $occurrence->id }})">Reject</flux:button></flux:modal.trigger> @endif
                                @if ($isFacilityAdmin)
                                    <flux:button wire:key="edit-occurrence-action-{{ $occurrence->id }}" size="sm" variant="primary" color="yellow" wire:click="selectForEdit({{ $occurrence->id }})" wire:loading.attr="disabled">Edit</flux:button>
                                    @if (($unsentEditCounts[$occurrence->id] ?? 0) > 0) <flux:button size="sm" wire:click="emailReservationChanges({{ $occurrence->id }})">Email changes to requester</flux:button> @endif
                                    @if ($occurrence->status === 'approved')
                                        @include('livewire.resources.soa-actions', ['reservation' => $occurrence])
                                        @if ($occurrence->soa_path)
                                            <flux:modal.trigger name="payment-reservation"><flux:button size="sm" wire:click="selectForPayment({{ $occurrence->id }})">Record payment</flux:button></flux:modal.trigger>
                                        @endif
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end"><flux:modal.close><flux:button>Close</flux:button></flux:modal.close></div>
        </div>
    </flux:modal>

    <flux:modal name="finish-reservation" size="sm"><div class="space-y-4 p-4">
        <h2 class="font-semibold">Confirm finished event #{{ $finishId }}</h2>
        <p>This event has finished and does not require payment. Confirm to move it to Done.</p>
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="confirmFinishedEvent" wire:loading.attr="disabled">Confirm finished event</flux:button></div>
    </div></flux:modal>
    <flux:modal name="delete-series" wire:key="delete-series-modal" wire:model="showDeleteSeriesModal" class="md:w-[600px]">
        <div class="space-y-4 p-4">
            @php $deletionDates = $this->seriesDeletionReservations; $deletableDates = $deletionDates->filter(fn ($item) => !$item->soa_path); @endphp
            <h2 class="font-semibold">Delete recurring dates?</h2>
            <p>{{ $deletionDates->first()?->title }}</p>
            <p class="text-sm">{{ $deletableDates->count() }} dates will be deleted. {{ $deletionDates->count() - $deletableDates->count() }} dates with an uploaded SOA will be kept. Deleted dates can be restored from the Deleted tab.</p>
            <ul class="max-h-[40vh] overflow-y-auto space-y-2 text-sm">
                @foreach ($deletionDates as $date)
                    <li>{{ $date->start_datetime->format('M j, Y g:i A') }} · #{{ $date->id }} — {{ $date->soa_path ? 'Protected: SOA uploaded' : 'Will be deleted' }}</li>
                @endforeach
            </ul>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="cancelSeriesDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="confirmSeriesDelete" wire:loading.attr="disabled" wire:target="confirmSeriesDelete" :disabled="$deletableDates->isEmpty()">Delete eligible dates</flux:button>
            </div>
        </div>
    </flux:modal>
    <flux:modal name="approve-series" size="sm"><div class="space-y-4 p-4">
        <h2 class="font-semibold">Approve pending recurring dates?</h2>
        <p class="text-sm">Each pending date will be checked for room and equipment conflicts. Conflicting dates will remain for approval.</p>
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="approvePendingSeries" wire:loading.attr="disabled" wire:target="approvePendingSeries">Approve eligible dates</flux:button></div>
    </div></flux:modal>
    <flux:modal name="restore-series" size="sm"><div class="space-y-4 p-4">
        <h2 class="font-semibold">Restore deleted recurring dates?</h2>
        <p class="text-sm">Deleted dates in this series will return to For Approval. Existing active dates will remain unchanged.</p>
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="restoreDeletedSeries" wire:loading.attr="disabled" wire:target="restoreDeletedSeries">Restore deleted dates</flux:button></div>
    </div></flux:modal>

    <flux:modal name="conflict-manager" class="md:w-[680px]">
        <div class="space-y-4 p-4">
            @php $conflictRequest = $this->conflictRequest; @endphp
            <div>
                <h2 class="text-lg font-semibold">Manage conflict for {{ $conflictRequest?->title }}</h2>
                @if ($conflictRequest)
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $conflictRequest->start_datetime->format('M j, Y g:i A') }}–{{ $conflictRequest->end_datetime->format('g:i A') }} · Request #{{ $conflictRequest->id }}</p>
                @endif
            </div>
            <p class="text-sm">Move or reject an approved booking, or edit this request. Approval becomes available once the conflict is resolved.</p>
            <div class="max-h-[55vh] space-y-3 overflow-y-auto">
                @forelse ($this->blockingBookings as $booking)
                    <div wire:key="blocking-booking-{{ $booking->id }}" class="space-y-2 rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="font-medium">Approved #{{ $booking->id }} · {{ $booking->title }}</div>
                        <div class="text-sm">{{ $booking->start_datetime->format('M j, Y g:i A') }}–{{ $booking->end_datetime->format('g:i A') }} · {{ $booking->room_names }}</div>
                        @if ($booking->equipment->isNotEmpty()) <div class="text-xs">Equipment: {{ $booking->equipment->map(fn ($item) => $item->name . ' × ' . $item->pivot->quantity)->join(', ') }}</div> @endif
                        <div class="flex flex-wrap gap-2">
                            @if ($isFacilityAdmin) <flux:button size="sm" variant="primary" color="yellow" wire:click="editConflictingBooking({{ $booking->id }})">Edit approved booking</flux:button> @endif
                            <flux:button size="sm" variant="danger" wire:click="rejectConflictingBooking({{ $booking->id }})">Reject approved booking</flux:button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-green-700">No approved booking currently blocks this request.</p>
                @endforelse
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                @if ($isFacilityAdmin && $conflictRequest?->status === 'pending') <flux:button variant="primary" color="yellow" wire:click="editConflictedRequest">Edit request</flux:button> @endif
                <flux:modal.close><flux:button>Close</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="approve-reservation" size="md"><div class="space-y-4 p-4"><h2 class="font-semibold">Approve reservation</h2>
        @if ($this->decisionConflicts)
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">Cannot approve while this conflicts with an approved booking: {{ implode(', ', $this->decisionConflicts) }}. Edit the request or resolve the existing booking first.</div>
        @endif
        <textarea wire:model="approvalNote" rows="3" class="w-full rounded-md border p-2" placeholder="Note (optional)"></textarea><div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="confirmApprove" wire:loading.attr="disabled" wire:target="confirmApprove" :disabled="(bool) $this->decisionConflicts"><span wire:loading.remove wire:target="confirmApprove">Confirm</span><span wire:loading wire:target="confirmApprove">Approving…</span></flux:button></div></div></flux:modal>
    <flux:modal name="reject-reservation" size="md"><div class="space-y-4 p-4"><h2 class="font-semibold">Reject reservation</h2><textarea wire:model="approvalNote" rows="3" class="w-full rounded-md border p-2" placeholder="Reason (required)"></textarea>@error('approvalNote') <span class="text-xs text-red-600">{{ $message }}</span> @enderror<div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="danger" wire:click="confirmReject">Confirm</flux:button></div></div></flux:modal>
    <flux:modal name="delete-reservation" size="sm" wire:key="delete-reservation-modal" wire:model="showDeleteModal"><div class="space-y-4 p-4"><h2 class="font-semibold">Delete reservation #{{ $deleteId }}</h2><p class="text-sm">This reservation will be removed from the active list and can be restored from the database.</p><div class="flex justify-end gap-2"><flux:button wire:click="cancelDelete">Cancel</flux:button><flux:button variant="danger" wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete" :disabled="$deleteId === null">Delete this reservation</flux:button></div></div></flux:modal>
    <flux:modal name="remove-soa" size="sm" wire:key="remove-soa-modal" wire:model="showRemoveSoaModal"><div class="space-y-4 p-4"><h2 class="font-semibold">Remove SOA from reservation #{{ $removeSoaId }}</h2><p class="text-sm">This deletes the SOA file and clears billing and payment status and dates. The reservation will remain approved.</p><div class="flex justify-end gap-2"><flux:button wire:click="cancelSoaRemoval">Cancel</flux:button><flux:button variant="danger" wire:click="confirmSoaRemoval" wire:loading.attr="disabled" wire:target="confirmSoaRemoval" :disabled="$removeSoaId === null">Remove SOA</flux:button></div></div></flux:modal>
    <flux:modal name="edit-reservation" class="md:w-[600px]"><div class="space-y-3 p-4"><h2 class="font-semibold">Edit reservation #{{ $editId }}</h2>
        <flux:input wire:model="editTitle" label="Event name" />
        <fieldset class="space-y-2 rounded-md border p-3"><legend class="px-1 text-sm font-medium">Rooms</legend>
            @foreach ($this->rooms->groupBy(fn ($room) => $room->floorLabel()) as $floor => $floorRooms)
                <div class="text-xs font-semibold text-zinc-500">{{ $floor }}</div>
                @foreach($floorRooms as $room)<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="editRoomIds" value="{{ $room->id }}">{{ $room->name }}</label>@endforeach
            @endforeach
            @error('editRoomIds')<p class="text-sm text-red-600">Select at least one room.</p>@enderror
        </fieldset>
        <div class="grid grid-cols-2 gap-2"><flux:input type="datetime-local" wire:model="editStart" label="Start" /><flux:input type="datetime-local" wire:model="editEnd" label="End" /></div>
        <div class="grid grid-cols-2 gap-2"><flux:input type="number" min="1" wire:model="editPax" label="Pax" /><flux:input wire:model="editContact" label="Contact" /></div>
        <flux:input wire:model="editSetup" label="Setup" /><flux:textarea wire:model="editNotes" label="Notes" /><flux:textarea wire:model="editConsumables" label="Consumables" />
        <flux:input type="file" wire:model="editFloorPlan" label="Replace floor plan (optional)" />
        <flux:input type="file" wire:model="editGatePass" label="Replace gate pass (optional)" />
        <div class="space-y-2"><h3 class="text-sm font-semibold">Equipment quantities</h3>
            @forelse ($editEquipment as $id => $quantity)
                @php $item = $this->equipment->firstWhere('id', (int) $id); @endphp
                @if ($item)
                    <div wire:key="edit-equipment-{{ $id }}" class="flex items-center gap-2 py-1"><label for="edit-equipment-{{ $id }}" class="flex-1 text-sm">{{ $item->name }}</label><input id="edit-equipment-{{ $id }}" type="number" min="0" wire:model="editEquipment.{{ $id }}" aria-label="Requested {{ $item->name }}" class="w-20 rounded-md border p-1 text-sm"><span class="text-sm text-zinc-500">/ {{ $item->total_quantity }}</span><button type="button" wire:click="removeEditEquipment({{ $id }})" aria-label="Remove {{ $item->name }}" title="Remove {{ $item->name }}" class="rounded px-2 text-lg font-semibold leading-none text-red-600 hover:bg-red-50 hover:text-red-800 dark:hover:bg-red-950">×</button></div>
                @endif
            @empty
                <p class="text-xs text-zinc-500">No equipment requested.</p>
            @endforelse
            <div class="flex items-end gap-2"><div class="flex-1"><flux:select wire:model="editEquipmentToAdd" label="Add equipment"><option value="">Select equipment</option>@foreach ($this->equipment as $item)@if (!array_key_exists($item->id, $editEquipment))<option value="{{ $item->id }}">{{ $item->name }}</option>@endif @endforeach</flux:select></div><flux:button wire:click="addEditEquipment">Add</flux:button></div>
        </div>
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit"><span wire:loading.remove wire:target="saveEdit">Save changes</span><span wire:loading wire:target="saveEdit">Saving…</span></flux:button></div>
    </div></flux:modal>
    <flux:modal name="billing-email" size="md" wire:model="showBillingEmailModal"><div class="space-y-4 p-4">
        @php $emailReservation = $this->billingEmailReservation; @endphp
        <h2 class="font-semibold">{{ $billingEmailAction === 'reminder' ? 'Send payment reminder' : 'Send SOA to requester' }}</h2>
        <p>{{ $emailReservation?->title }} · #{{ $billingEmailId }}</p>
        <p>To: {{ $emailReservation?->requester_email ?: 'No requester email address' }}</p>
        <p class="text-sm">The current SOA will be attached. Payment due: {{ $emailReservation?->payment_due_at?->format('M j, Y') }}. This date is based on the latest SOA upload.</p>
        @if ($billingEmailAction === 'reminder') <p class="text-sm">{{ $emailReservation?->payment_due_at?->isBefore(today()) ? 'This email will indicate that payment is overdue.' : 'This email will state the payment due date.' }}</p> @endif
        <div class="flex justify-end gap-2"><flux:button wire:click="cancelBillingEmail">Cancel</flux:button><flux:button variant="primary" wire:click="sendBillingEmail" wire:loading.attr="disabled" wire:target="sendBillingEmail">Send email</flux:button></div>
    </div></flux:modal>
    <flux:modal name="billing-reservation" size="md"><div class="space-y-4 p-4">
        <h2 class="font-semibold">Statement of Account for #{{ $billingId }}</h2>
        @if ($billingFromDone && !$replacingPaidSoa)
            <p class="text-sm text-amber-700">Uploading an SOA removes the “No payment required” designation. This booking will move from Done to Approved until payment is recorded.</p>
        @endif
        <flux:input type="file" wire:model="soaFile" label="Upload SOA (PDF or Word)" accept=".pdf,.doc,.docx" />
        <div wire:loading wire:target="soaFile" class="text-xs text-zinc-500">Uploading SOA...</div>
        @if ($soaFile) <p class="text-xs text-green-700">Ready: {{ $soaFile->getClientOriginalName() }}</p> @endif
        @if ($replacingSoa) <flux:textarea wire:model="soaReplacementReason" label="Reason for replacing SOA" required maxlength="2000" /> @endif
        <p class="text-xs text-zinc-500">@if ($replacingPaidSoa) Recorded payment and existing proofs will be retained. The previous SOA and replacement reason are kept for the record. @else Saving does not send an email. Each upload or replacement resets the payment due date to 15 days from today. Use Send SOA to requester after upload. @endif</p>
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="markBilled" wire:loading.attr="disabled" wire:target="soaFile,markBilled">Save SOA</flux:button></div>
    </div></flux:modal>
    <flux:modal name="payment-reservation" size="md"><div class="space-y-4 p-4">
        <h2 class="font-semibold">Record payment for #{{ $paymentId }}</h2>
        @php $paymentReservation = $this->paymentReservation; @endphp
        @if ($paymentReservation?->paymentProofs->isNotEmpty())
            <h3 class="text-sm font-semibold">Recorded payment proofs</h3>
            @foreach ($paymentReservation->paymentProofs as $proof)
                <p class="text-sm"><a href="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($proof->path) }}" target="_blank" rel="noopener" class="text-blue-700 underline">{{ $proof->original_name }}</a> · {{ $proof->paid_on?->format('M j, Y') }}</p>
            @endforeach
        @endif
        <flux:input type="date" wire:model="paymentDate" label="Date paid" />
        <flux:input type="file" wire:model="paymentProofs" label="Payment proofs (PDF or images)" accept=".pdf,.jpg,.jpeg,.png" multiple />
        <p class="text-xs text-zinc-500">Add up to 10 files, 10 MB each. Previously recorded files are retained.</p>
        <div wire:loading wire:target="paymentProofs" class="text-xs text-zinc-500">Uploading proofs...</div>
        @foreach ($paymentProofs as $proof) <p class="text-xs text-green-700">Ready: {{ $proof->getClientOriginalName() }}</p> @endforeach
        @error('paymentProof') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('paymentProofs') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @foreach ($errors->get('paymentProofs.*') as $messages) @foreach ($messages as $message) <p class="text-sm text-red-600">{{ $message }}</p> @endforeach @endforeach
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="primary" wire:click="recordPayment" wire:loading.attr="disabled" wire:target="paymentProofs,recordPayment">Save payment</flux:button></div>
    </div></flux:modal>
</div>
