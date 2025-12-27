<x-layouts.app title="Leave Request Details">
    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">
                    Leave Request Details
                </h2>

                <flux:button variant="ghost" href="{{ route('requests.manage') }}">
                    ← Back to Manage Requests
                </flux:button>
            </div>

            <!-- Details -->
            <div class="space-y-3 text-sm text-neutral-800 dark:text-neutral-100">
                <p><strong>Employee:</strong> {{ $request->user->name }}</p>

                <p class="flex items-center gap-2">
                    <strong>Type:</strong>

                    <span>
                        {{ $request->type === 'PTO' ? 'Leave' : ($request->type === 'WFH' ? 'Work from Home' : ucfirst($request->type)) }}
                    </span>

                    @if ($request->is_offset)
                        <span title="Does not deduct leave credits"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                                     bg-sky-100 text-sky-700
                                     dark:bg-sky-800/30 dark:text-sky-300 cursor-help">
                            Offset
                        </span>
                    @endif
                </p>

                <p><strong>Start Date:</strong> {{ $request->start_date }}</p>
                <p><strong>End Date:</strong> {{ $request->end_date }}</p>
                <p><strong>Number of Days:</strong> {{ $request->number_of_days }}</p>
                <p><strong>Reason:</strong> {{ $request->reason }}</p>

                {{-- 🔥 OFFSET PROOF (SUPERVISOR VIEW) --}}
                @if ($request->is_offset && $request->offset_proof_path)
                    <div
                        class="mt-3 border border-sky-200 dark:border-sky-800 rounded-lg p-3
                               bg-sky-50/50 dark:bg-sky-900/10">
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

                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                        'cancelled' => 'bg-neutral-300 text-neutral-800 dark:bg-neutral-600 dark:text-neutral-200',
                    ];
                    $isDisabled = strtolower($request->status) === 'cancelled';
                @endphp

                <p>
                    <strong>Status:</strong>
                    <span
                        class="inline-block px-2 py-0.5 rounded-md text-xs font-medium
                               {{ $statusColors[strtolower($request->status)] ?? '' }}">
                        {{ ucfirst($request->status) }}
                    </span>
                </p>
            </div>

            <!-- Actions -->
            <div class="mt-8">
                <form action="{{ route('requests.process', $request->id) }}" method="POST" class="space-y-4">
                    @csrf

                    <textarea name="remarks" rows="2" placeholder="Remarks"
                        class="w-full text-sm bg-neutral-100 border-neutral-300
                                     dark:border-neutral-600 dark:bg-neutral-700 dark:text-white
                                     rounded-md p-2"
                        {{ $isDisabled ? 'disabled' : '' }}>
                        {{ old('remarks', $request->remarks) }}
                    </textarea>

                    <input type="hidden" name="action_type" id="action_type" value="">

                    <div class="flex gap-3">
                        <button type="submit"
                            class="px-4 py-2 rounded-md text-sm font-medium
                                       bg-rose-600 text-white hover:bg-rose-700
                                       disabled:bg-neutral-400 disabled:cursor-not-allowed"
                            onclick="document.getElementById('action_type').value='reject';"
                            {{ $isDisabled ? 'disabled' : '' }}>
                            Reject
                        </button>

                        <button type="submit"
                            class="px-4 py-2 rounded-md text-sm font-medium
                                       bg-emerald-600 text-white hover:bg-emerald-700
                                       disabled:bg-neutral-400 disabled:cursor-not-allowed"
                            onclick="document.getElementById('action_type').value='approve';"
                            {{ $isDisabled ? 'disabled' : '' }}>
                            Approve
                        </button>
                    </div>

                    @if ($isDisabled)
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">
                            Actions are disabled because this request has been cancelled.
                        </p>
                    @endif
                </form>
            </div>

        </div>
    </div>
</x-layouts.app>
