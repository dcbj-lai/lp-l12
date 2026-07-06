<x-layouts.app>
    <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <h1 class="max-w-3xl mx-auto text-2xl font-bold mb-6 text-gray-800 dark:text-gray-100">
            Request Settings
        </h1>

        {{-- Update Default Credits --}}
        <form action="{{ route('org-settings.update') }}" method="POST"
            class="max-w-3xl mx-auto space-y-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            @csrf

            <div>
                <label for="pto_default" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Default Leave Credits
                </label>
                <input type="number" step="0.01" name="pto_default" id="pto_default"
                    value="{{ old('pto_default', $settings->pto_default) }}"
                    class="mt-1 block w-full rounded-md bg-neutral-100 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring focus:ring-blue-500/30 p-2">
            </div>

            <div>
                <label for="wfh_default" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Default WFH Credits
                </label>
                <input type="number" step="0.01" name="wfh_default" id="wfh_default"
                    value="{{ old('wfh_default', $settings->wfh_default) }}"
                    class="mt-1 block w-full rounded-md bg-neutral-100 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring focus:ring-blue-500/30 p-2">
            </div>

            <div>
                <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Last Leave Replenishment Date
                </p>
                <p class="mt-1 rounded-md bg-neutral-100 p-2 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    {{ $settings?->last_leave_replenished_on?->format('Y-m-d') ?? now()->toDateString() }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    If no run has been recorded yet, leave-credit reports use today as the default reference date unless filters are applied.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                <flux:button type="submit" class="w-full sm:w-auto" variant="primary">
                    Save Settings
                </flux:button>
            </div>
        </form>

        {{-- Divider --}}
        <div class="my-10 max-w-3xl mx-auto border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Initialize Leave Credits --}}
        <div class="max-w-3xl mx-auto space-y-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Initialize Leave Requests
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This will replenish Leave and WFH credits for
                <strong>all users</strong>. Leave credits will be set to the default value plus each user's approved carry over.
                The run date will be stored for leave-credit report defaults.
            </p>

            <form action="{{ route('org-settings.initiate-leave') }}" method="POST"
                onsubmit="return confirm('Are you sure you want to replenish leave credits for all users? Approved carry over will be applied and then cleared for the next run.');">
                @csrf
                <flux:button type="submit" variant="danger" class="w-full sm:w-auto">
                    Initialize All Leaves
                </flux:button>
            </form>
        </div>

        {{-- Divider --}}
        <div class="my-10 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Replenishment Run History --}}
        <div class="space-y-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Replenishment Run History
            </h2>

            <div class="max-w-full overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="bg-gray-100 text-left text-xs font-semibold uppercase text-gray-600 dark:bg-gray-900 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Run Date</th>
                            <th class="px-4 py-3 text-right">Default Leave</th>
                            <th class="px-4 py-3 text-right">Default WFH</th>
                            <th class="px-4 py-3 text-right">Users</th>
                            <th class="px-4 py-3 text-right">Carry Over Applied</th>
                            <th class="px-4 py-3">Run By</th>
                            <th class="px-4 py-3">Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($replenishmentRuns as $run)
                            <tr class="border-t border-gray-100 dark:border-gray-700 align-top">
                                <td class="px-4 py-3">{{ $run->run_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $run->pto_default, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $run->wfh_default, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($run->users_count) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $run->total_approved_carry_over, 2) }}</td>
                                <td class="px-4 py-3">{{ $run->runner?->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $run->created_at?->timezone('Asia/Manila')->format('Y-m-d h:i A') }}</td>
                            </tr>
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td colspan="7" class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40">
                                    <details>
                                        <summary class="cursor-pointer text-sm font-medium text-blue-700 dark:text-blue-300">
                                            Employee snapshots ({{ number_format($run->items->count()) }})
                                        </summary>

                                        <div class="mt-3 max-w-full overflow-x-auto">
                                            <table class="w-full min-w-[1120px] text-xs">
                                                <thead class="bg-white text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left">Employee #</th>
                                                        <th class="px-3 py-2 text-left">Employee</th>
                                                        <th class="px-3 py-2 text-right">Previous PTO</th>
                                                        <th class="px-3 py-2 text-right">PTO Default</th>
                                                        <th class="px-3 py-2 text-right">Carry Over Applied</th>
                                                        <th class="px-3 py-2 text-right">Initialized PTO</th>
                                                        <th class="px-3 py-2 text-right">Previous WFH</th>
                                                        <th class="px-3 py-2 text-right">Initialized WFH</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($run->items as $item)
                                                        <tr class="border-t border-gray-100 dark:border-gray-700">
                                                            <td class="px-3 py-2">{{ $item->employee_number ?? '—' }}</td>
                                                            <td class="px-3 py-2">
                                                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $item->employee_name }}</div>
                                                                <div class="text-gray-500">{{ $item->employee_email ?? '—' }}</div>
                                                            </td>
                                                            <td class="px-3 py-2 text-right">{{ number_format((float) $item->previous_pto, 2) }}</td>
                                                            <td class="px-3 py-2 text-right">{{ number_format((float) $item->pto_default, 2) }}</td>
                                                            <td class="px-3 py-2 text-right">{{ number_format((float) $item->approved_carry_over_applied, 2) }}</td>
                                                            <td class="px-3 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format((float) $item->initialized_pto, 2) }}</td>
                                                            <td class="px-3 py-2 text-right">{{ number_format((float) $item->previous_wfh, 2) }}</td>
                                                            <td class="px-3 py-2 text-right">{{ number_format((float) $item->initialized_wfh, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="8" class="px-3 py-5 text-center text-gray-500">
                                                                No employee snapshots were recorded for this run.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No replenishment runs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
