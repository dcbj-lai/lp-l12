<x-layouts.app title="Leave Credits">
    <div class="p-4 md:p-6 space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Leave Credits</h1>
                <p class="text-sm text-gray-500">Leave and WFH balances for all employees.</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <flux:button class="w-full justify-center sm:w-auto" size="sm" variant="ghost" icon="download"
                    href="{{ route('leave-credits.csv', request()->query()) }}">
                    CSV
                </flux:button>
                <flux:button class="w-full justify-center sm:w-auto" size="sm" variant="primary" icon="download"
                    href="{{ route('leave-credits.pdf', request()->query()) }}">
                    PDF
                </flux:button>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-xs font-medium uppercase text-gray-500">Employees</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $totals['employees'] }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-xs font-medium uppercase text-gray-500">Total Leave</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ number_format($totals['pto'], 2) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-xs font-medium uppercase text-gray-500">Total WFH</p>
                <p class="mt-1 text-2xl font-semibold text-blue-700 dark:text-blue-300">{{ number_format($totals['wfh'], 2) }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('leave-credits.index') }}"
            class="flex flex-col gap-2 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search employee, department, or position"
                class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" />
            <flux:button class="w-full justify-center sm:w-auto" type="submit" variant="primary">Search</flux:button>
            @if (request('search'))
                <flux:button class="w-full justify-center sm:w-auto" href="{{ route('leave-credits.index') }}" variant="ghost">Reset</flux:button>
            @endif
        </form>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <table class="min-w-[920px] w-full text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3 text-right">Leave</th>
                        <th class="px-4 py-3 text-right">WFH</th>
                        <th class="px-4 py-3">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-zinc-100 dark:border-zinc-700">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $user->preferred_name ?: $user->name }}</div>
                                @if ($user->preferred_name)
                                    <div class="text-xs text-gray-500">{{ $user->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->department?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->position ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-700 dark:text-emerald-300">
                                {{ number_format((float) ($user->requestCredit?->pto ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-700 dark:text-blue-300">
                                {{ number_format((float) ($user->requestCredit?->wfh ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ optional($user->requestCredit?->updated_at)->format('M d, Y g:i A') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
