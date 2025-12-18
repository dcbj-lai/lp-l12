<x-layouts.app title="Request Details">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6">

            <div class="flex items-center justify-between mb-6 mt-4">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">
                    Request Details
                </h2>

                <flux:button variant="ghost" href="{{ route('my-requests') }}">
                    ← Back to My Requests
                </flux:button>
            </div>

            @if ($canEdit)
                <form method="POST" action="{{ route('requests.update', $request->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Offset --}}
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_offset" id="is_offset" value="1"
                            @checked(old('is_offset', $request->is_offset))
                            class="rounded border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800">
                        <label for="is_offset" class="text-sm text-neutral-700 dark:text-neutral-300">
                            Offset (no credit deduction)
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        {{-- Type --}}
                        <div>
                            <label for="type"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">
                                Type
                            </label>
                            <select name="type" id="type"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                                <option value="PTO" @selected(old('type', $request->type) === 'PTO')>
                                    Leave
                                </option>
                                <option value="WFH" @selected(old('type', $request->type) === 'WFH')>
                                    Work from Home
                                </option>
                                <option value="LWOP" @selected(old('type', $request->type) === 'LWOP')>
                                    Leave w/o Pay
                                </option>
                            </select>
                        </div>

                        {{-- Reason --}}
                        <div>
                            <label for="reason"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">
                                Reason
                            </label>
                            <input type="text" name="reason" id="reason"
                                value="{{ old('reason', $request->reason) }}"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        {{-- Start Date --}}
                        <div>
                            <label for="start_date"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">
                                Start Date
                            </label>
                            <input type="date" name="start_date" id="start_date"
                                value="{{ old('start_date', $request->start_date) }}"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label for="end_date"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">
                                End Date
                            </label>
                            <input type="date" name="end_date" id="end_date"
                                value="{{ old('end_date', $request->end_date) }}"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        {{-- End Date Type --}}
                        <div>
                            <label for="end_date_type"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">
                                End Date Type
                            </label>
                            <select name="end_date_type" id="end_date_type"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                                <option value="full" @selected($request->end_date_type === 'full')>
                                    Full Day
                                </option>
                                <option value="half-am-off" @selected($request->end_date_type === 'half-am-off')>
                                    Half Day: Morning Off
                                </option>
                                <option value="half-pm-off" @selected($request->end_date_type === 'half-pm-off')>
                                    Half Day: Afternoon Off
                                </option>
                            </select>
                        </div>

                        {{-- Status --}}
                        @php
                            $status = strtolower($request->status);
                            $statusClasses = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                'approved' =>
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                            ];
                        @endphp

                        <div>
                            <label class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">
                                Status
                            </label>
                            <div
                                class="mt-1 px-3 py-2 rounded-md text-sm font-medium
                                {{ $statusClasses[$status] ?? 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                                {{ ucfirst($request->status) }}
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex gap-2">
                        <flux:button type="submit" variant="primary" size="sm">
                            Save
                        </flux:button>

                        <flux:button variant="ghost" size="sm" href="{{ route('my-requests') }}">
                            Cancel
                        </flux:button>
                    </div>
                </form>

                {{-- Cancel Request --}}
                @if ($request->status === 'pending')
                    <div class="mt-4">
                        <form method="POST" action="{{ route('requests.archive', $request->id) }}">
                            @csrf
                            @method('PUT')

                            <flux:button type="submit" size="sm">
                                Cancel Request
                            </flux:button>
                        </form>
                    </div>
                @endif
            @else
                {{-- Read-only view --}}
                <div class="space-y-2 text-sm text-neutral-700 dark:text-neutral-300">
                    <p>
                        <strong>Type:</strong>
                        @if ($request->type === 'PTO')
                            Leave
                        @elseif ($request->type === 'WFH')
                            Work from Home
                        @elseif ($request->type === 'LWOP')
                            Leave w/o Pay
                        @else
                            {{ ucfirst($request->type) }}
                        @endif
                    </p>

                    <p><strong>Offset:</strong> {{ $request->is_offset ? 'Yes' : 'No' }}</p>
                    <p><strong>Date Range:</strong> {{ $request->start_date }} to {{ $request->end_date }}</p>
                    <p><strong>Days:</strong> {{ $request->number_of_days }}</p>
                    <p><strong>Reason:</strong> {{ $request->reason }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($request->status) }}</p>
                    <p><strong>Approver:</strong> {{ optional($request->approver)->name ?? '—' }}</p>
                    <p><strong>Remarks:</strong> {{ $request->remarks }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
