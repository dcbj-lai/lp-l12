<x-layouts.app title="Request Details">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6 mt-4">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">
                    Request Details
                </h2>

                <flux:button variant="ghost" href="{{ route('my-requests') }}">
                    ← Back to My Requests
                </flux:button>
            </div>

            @if ($canEdit)
                {{-- ================= EDIT MODE ================= --}}
                <form method="POST" action="{{ route('requests.update', $request->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- 🔥 OFFSET PROOF (LIVEWIRE) --}}
                    @if ($request->is_offset)
                        <div
                            class="border border-sky-200 dark:border-sky-800 rounded-lg p-4 bg-sky-50/50 dark:bg-sky-900/10">
                            <h3 class="text-sm font-semibold text-sky-700 dark:text-sky-300 mb-3">
                                Offset Proof
                            </h3>

                            <livewire:offset-proof :request="$request" />
                        </div>
                    @endif

                    {{-- Other Fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        {{-- Type --}}
                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                Type
                            </label>

                            <select name="type"
                                class="mt-1 block w-full rounded-md shadow-sm
                   border-gray-300 dark:border-gray-600
                   dark:bg-gray-800 dark:text-gray-100
                   focus:border-sky-500 focus:ring-sky-500">
                                <option value="PTO" @selected(old('type', $request->type) === 'PTO')>Leave</option>
                                <option value="WFH" @selected(old('type', $request->type) === 'WFH')>Work from Home</option>
                                <option value="LWOP" @selected(old('type', $request->type) === 'LWOP')>Leave w/o Pay</option>
                            </select>

                            @error('type')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                Reason
                            </label>

                            <input type="text" name="reason" value="{{ old('reason', $request->reason) }}"
                                class="mt-1 block w-full rounded-md shadow-sm
                   border-gray-300 dark:border-gray-600
                   dark:bg-gray-800 dark:text-gray-100
                   focus:border-sky-500 focus:ring-sky-500">

                            @error('reason')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Start Date --}}
                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                Start Date
                            </label>

                            <input type="date" name="start_date"
                                value="{{ old('start_date', $request->start_date) }}"
                                class="mt-1 block w-full rounded-md shadow-sm
                   border-gray-300 dark:border-gray-600
                   dark:bg-gray-800 dark:text-gray-100
                   focus:border-sky-500 focus:ring-sky-500">

                            @error('start_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                End Date
                            </label>

                            <input type="date" name="end_date" value="{{ old('end_date', $request->end_date) }}"
                                class="mt-1 block w-full rounded-md shadow-sm
                   border-gray-300 dark:border-gray-600
                   dark:bg-gray-800 dark:text-gray-100
                   focus:border-sky-500 focus:ring-sky-500">

                            @error('end_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- End Date Type --}}
                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                                End Date Type
                            </label>

                            <select name="end_date_type"
                                class="mt-1 block w-full rounded-md shadow-sm
                   border-gray-300 dark:border-gray-600
                   dark:bg-gray-800 dark:text-gray-100
                   focus:border-sky-500 focus:ring-sky-500">
                                <option value="full" @selected(old('end_date_type', $request->end_date_type) === 'full')>
                                    Full Day
                                </option>
                                <option value="half-am-off" @selected(old('end_date_type', $request->end_date_type) === 'half-am-off')>
                                    Half Day – Morning Off
                                </option>
                                <option value="half-pm-off" @selected(old('end_date_type', $request->end_date_type) === 'half-pm-off')>
                                    Half Day – Afternoon Off
                                </option>
                            </select>

                            @error('end_date_type')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>



                    {{-- Actions --}}
                    <div class="mt-6 flex items-center justify-between">
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
        </div>
    @else
        {{-- ================= READ-ONLY ================= --}}
        <div class="space-y-3 text-sm text-neutral-700 dark:text-neutral-300">
            <p><strong>Type:</strong> {{ ucfirst($request->type) }}</p>
            <p><strong>Offset:</strong> {{ $request->is_offset ? 'Yes' : 'No' }}</p>

            {{-- Offset Proof (ONLY if offset) --}}
            @if ($request->is_offset && $request->offset_proof_path)
                <div class="border border-sky-200 dark:border-sky-800 rounded-lg p-3 bg-sky-50/50 dark:bg-sky-900/10">
                    <p class="font-medium text-sky-700 dark:text-sky-300 mb-1">
                        Offset Proof
                    </p>

                    <a href="{{ route('requests.documents.show', $request->offset_proof_path) }}" target="_blank"
                        class="inline-flex items-center px-3 py-1 rounded-full
                                      bg-sky-100 text-sky-700 text-xs hover:underline
                                      dark:bg-sky-800/30 dark:text-sky-300">
                        {{ basename($request->offset_proof_path) }}
                    </a>
                </div>
            @endif

            <p><strong>Date Range:</strong> {{ $request->start_date }} → {{ $request->end_date }}</p>
            <p><strong>Days:</strong> {{ $request->number_of_days }}</p>
            <p><strong>Status:</strong> {{ ucfirst($request->status) }}</p>
        </div>
        @endif
    </div>
    </div>
</x-layouts.app>
