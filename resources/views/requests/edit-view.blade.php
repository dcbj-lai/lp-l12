<x-layouts.app title="Request Details">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-6 mt-4">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">Request Details</h2>

                <flux:button variant="ghost" href="{{ route('my-requests') }}">
                    ← Back to My Requests
                </flux:button>
            </div>

            @if ($canEdit)
                <form method="POST" action="{{ route('requests.update', $request->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="type"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">Type</label>
                            <select name="type" id="type"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                                <option value="PTO" @selected(old('type', $request->type ?? null) === 'PTO')>Leave</option>
                                <option value="WFH" @selected(old('type', $request->type ?? null) === 'WFH')>Work from Home</option>
                                <option value="LWOP" @selected(old('type', $request->type ?? null) === 'LWOP')>Leave w/o Pay</option>
                            </select>

                        </div>

                        <div>
                            <label for="reason"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">Reason</label>
                            <input type="text" name="reason" id="reason" value="{{ $request->reason }}"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div>
                            <label for="start_date"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">Start
                                Date</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $request->start_date }}"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div>
                            <label for="end_date"
                                class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">End
                                Date</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $request->end_date }}"
                                class="mt-1 block w-full border-neutral-300 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>

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

                        @php
                            $status = strtolower($request->status);
                            $statusClasses = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                            ];
                        @endphp

                        <div>
                            <label class="block font-medium text-sm text-neutral-700 dark:text-neutral-300">Status</label>
                            <div
                                class="mt-1 px-3 py-2 rounded-md text-sm font-medium
                                                                                            {{ $statusClasses[$status] ?? 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                                {{ ucfirst($request->status) }}
                            </div>
                        </div>


                    </div>

                    <div class="mt-6 flex justify-between items-center gap-4">
                        <div class="flex gap-2">
                            <flux:button type="submit" variant="primary" size="sm">Save</flux:button>

                            <flux:button variant="ghost" size="sm" href="{{ route('my-requests') }}">
                                Cancel
                            </flux:button>
                        </div>
                    </div>
                </form>
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
                <div class="space-y-2 text-sm text-neutral-700 dark:text-neutral-300">
                    <p><strong>Type:</strong> 
                        @switch($request->type)
                            @case('PTO')
                                Leave
                                @break
                            @case('WFH')
                                Work from Home
                                @break
                            @case('LWOP')
                                Leave w/o Pay
                                @break
                            @default
                                {{ $request->type }}
                        @endswitch
                    </p>
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
