<x-layouts.app title="Request Details (HR)">
    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">
                    Request Details
                </h2>

                <a href="{{ route('requests.manage-hr') }}"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                    ← Back to All Requests
                </a>
            </div>

            <!-- Details -->
            <div class="space-y-3 text-sm text-neutral-800 dark:text-neutral-100">
                <p><strong>Employee:</strong> {{ $request->user->name }}</p>
                <p><strong>Department:</strong> {{ optional($request->user->department)->name ?? '—' }}</p>
                <p><strong>Approver:</strong> {{ optional($request->approver)->name ?? '—' }}</p>

                <p class="flex items-center gap-2">
                    <strong>Type:</strong>

                    <span>
                        @if ($request->type === 'PTO')
                            Leave
                        @elseif ($request->type === 'WFH')
                            Work From Home
                        @else
                            {{ ucfirst($request->type) }}
                        @endif
                    </span>

                    @if ($request->is_offset)
                        <span title="Does not deduct leave credits"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                   bg-sky-100 text-sky-700
                   dark:bg-sky-800/30 dark:text-sky-300 cursor-help">
                            Offset
                        </span>
                    @endif
                    @if ($request->is_offset)
                        <div
                            class="mt-4 rounded-lg border border-sky-200
               bg-sky-50 p-4
               dark:border-sky-800/40 dark:bg-sky-900/20">

                            <div class="flex items-center justify-between">
                                <span
                                    class="inline-flex items-center gap-1.5
                       text-xs font-semibold uppercase tracking-wide
                       text-sky-700 dark:text-sky-300">
                                    Offset Proof
                                </span>

                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full
                       text-[10px] font-medium
                       bg-sky-100 text-sky-700
                       dark:bg-sky-800/40 dark:text-sky-300">
                                    No credit deduction
                                </span>
                            </div>

                            @if ($request->offset_proof_path)
                                <div class="mt-3 flex items-center gap-2">
                                    <a href="{{ route('requests.documents.show', $request->offset_proof_path) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-1.5
                          rounded-full text-xs
                          bg-white text-sky-700 shadow-sm
                          hover:bg-sky-100 hover:underline
                          dark:bg-neutral-900 dark:text-sky-300 dark:hover:bg-sky-800/40">

                                        📎 {{ basename($request->offset_proof_path) }}
                                    </a>
                                </div>
                            @else
                                <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                    No proof document uploaded.
                                </p>
                            @endif
                        </div>
                    @endif

                </p>

                <p><strong>Start Date:</strong> {{ $request->start_date }}</p>
                <p><strong>End Date:</strong> {{ $request->end_date }}</p>
                <p><strong>Number of Days:</strong> {{ $request->number_of_days }}</p>
                <p><strong>Reason:</strong> {{ $request->reason }}</p>

                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                        'cancelled' => 'bg-neutral-300 text-neutral-800 dark:bg-neutral-600 dark:text-neutral-200',
                    ];
                @endphp

                <p>
                    <strong>Status:</strong>
                    <span
                        class="inline-block px-2 py-0.5 rounded-md text-xs font-medium
                        {{ $statusColors[strtolower($request->status)] ?? 'bg-neutral-200 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                        {{ ucfirst($request->status) }}
                    </span>
                </p>

                <p><strong>Remarks:</strong> {{ $request->remarks ?? '—' }}</p>

                @if ($request->type === 'PTO')
                    <p><strong>Remaining Leave Balance:</strong>
                        {{ optional($request->user->requestCredit)->pto ?? 'N/A' }}
                    </p>
                @elseif ($request->type === 'WFH')
                    <p><strong>Remaining WFH Credits:</strong>
                        {{ optional($request->user->requestCredit)->wfh ?? 'N/A' }}
                    </p>
                @endif
            </div>

        </div>
    </div>
</x-layouts.app>
