<x-layouts.app title="{{ ($requestKind ?? 'leave') === 'credit-carry-over' ? 'New Credit Carry Over Request' : 'New Request' }}">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-900 shadow-xl sm:rounded-lg p-6">

            <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6"
                x-data="{ offset: {{ old('is_offset') ? 'true' : 'false' }} }">
                @csrf
                <input type="hidden" name="request_kind" value="{{ $requestKind ?? 'leave' }}">

                {{-- Credits + Offset --}}
                <div class="flex items-start justify-between gap-6">
                    <livewire:request-credits-widget />

                    @if (($requestKind ?? 'leave') !== 'credit-carry-over')
                        <div class="w-full max-w-sm">
                            {{-- Offset toggle --}}
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_offset" id="is_offset" value="1" x-model="offset"
                                    class="rounded border-gray-300 dark:border-gray-600">
                                <label for="is_offset" class="text-sm text-gray-700 dark:text-gray-300">
                                    Offset (no credit deduction)
                                </label>
                            </div>

                            {{-- Proof upload --}}
                            <div x-show="offset" x-cloak class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Proof of Extra Work
                                </label>

                                <input type="file" name="offset_proof" accept=".pdf,.jpg,.jpeg,.png"
                                    x-bind:required="offset" x-bind:disabled="!offset"
                                    class="mt-1 block w-full text-sm
                                              file:mr-4 file:rounded file:border-0
                                              file:bg-sky-100 file:text-sky-700
                                              dark:file:bg-sky-800/30 dark:file:text-sky-300">

                                <p class="text-xs text-neutral-500 mt-1">
                                    PDF or image (max 5MB)
                                </p>

                                @error('offset_proof')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Header --}}
                <div class="flex items-center justify-between mb-6 mt-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {{ ($requestKind ?? 'leave') === 'credit-carry-over' ? 'New Credit Carry Over Request' : 'New Request' }}
                    </h2>

                    <flux:button variant="ghost" href="{{ route('my-requests') }}">
                        ← Back to My Requests
                    </flux:button>
                </div>

                {{-- Fields --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @if (($requestKind ?? 'leave') === 'credit-carry-over')
                        <div>
                            <label for="carry_over_days" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                Carry Over Credits
                            </label>
                            <input type="number" step="0.01" min="0.01" name="carry_over_days" id="carry_over_days" required
                                value="{{ old('carry_over_days') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                          dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                            @error('carry_over_days')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reason" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                Reason / Notes
                            </label>
                            <input type="text" name="reason" id="reason" required value="{{ old('reason') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                          dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                            @error('reason')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                    {{-- Type --}}
                    <div>
                        <label for="type" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            Type
                        </label>
                        <select name="type" id="type" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                       dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                            <option value="PTO" @selected(old('type') === 'PTO')>Leave</option>
                            <option value="WFH" @selected(old('type') === 'WFH')>Work from Home</option>
                            <option value="LWOP" @selected(old('type') === 'LWOP')>Leave w/o Pay</option>
                        </select>
                        @error('type')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label for="reason" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            Reason / Notes
                        </label>
                        <input type="text" name="reason" id="reason" required value="{{ old('reason') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                      dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                        @error('reason')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <label for="start_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            Start Date
                        </label>
                        <input type="date" name="start_date" id="start_date" required value="{{ old('start_date') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                      dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                        @error('start_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label for="end_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            End Date
                        </label>
                        <input type="date" name="end_date" id="end_date" required value="{{ old('end_date') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                      dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                        @error('end_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- End Date Type --}}
                    <div>
                        <label for="end_date_type" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            End Date Type
                        </label>
                        <select name="end_date_type" id="end_date_type" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600
                                       dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm">
                            <option value="full" @selected(old('end_date_type') === 'full')>Full Day</option>
                            <option value="half-am-off" @selected(old('end_date_type') === 'half-am-off')>
                                Half Day: Morning Off
                            </option>
                            <option value="half-pm-off" @selected(old('end_date_type') === 'half-pm-off')>
                                Half Day: Afternoon Off
                            </option>
                        </select>
                        @error('end_date_type')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex justify-end gap-4">
                    <flux:button variant="ghost" href="{{ route('my-requests') }}">
                        Cancel
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ ($requestKind ?? 'leave') === 'credit-carry-over' ? 'Submit Carry Over Request' : 'Submit Request' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
