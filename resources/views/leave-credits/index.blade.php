<x-layouts.app title="Leave Credits">
    <div class="p-4 md:p-6 space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Leave Credits</h1>
                <p class="text-sm text-gray-500">
                    Leave balance report from {{ $periodStart->format('M d, Y') }} to {{ $asOf->format('M d, Y') }}.
                </p>
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

        <form method="GET" action="{{ route('leave-credits.index') }}"
            class="grid grid-cols-1 gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800 lg:grid-cols-[minmax(220px,1fr)_150px_160px_160px_auto_auto] lg:items-end">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium uppercase text-gray-500">Search</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Employee number, name, department, or position"
                    class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" />
            </div>

            <div>
                <label for="status" class="mb-1 block text-xs font-medium uppercase text-gray-500">Status</label>
                <select id="status" name="status"
                    class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="active" @selected(($status ?? request('status', 'active')) === 'active')>Active only</option>
                    <option value="all" @selected(($status ?? request('status', 'active')) === 'all')>All users</option>
                    <option value="inactive" @selected(($status ?? request('status', 'active')) === 'inactive')>Inactive only</option>
                </select>
            </div>

            <div>
                <label for="date_from" class="mb-1 block text-xs font-medium uppercase text-gray-500">Date From</label>
                <input id="date_from" type="date" name="date_from" value="{{ request('date_from', request('period_start', $periodStart->toDateString())) }}"
                    class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" />
            </div>

            <div>
                <label for="date_to" class="mb-1 block text-xs font-medium uppercase text-gray-500">Date To</label>
                <input id="date_to" type="date" name="date_to" value="{{ request('date_to', request('as_of', $asOf->toDateString())) }}"
                    class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" />
            </div>

            <flux:button class="w-full justify-center lg:w-auto" type="submit" variant="primary">Apply</flux:button>
            @if (request('search') || (request('status') && request('status') !== 'active') || request('date_from') || request('date_to') || request('period_start') || request('as_of'))
                <flux:button class="w-full justify-center lg:w-auto" href="{{ route('leave-credits.index') }}" variant="ghost">Reset</flux:button>
            @endif
        </form>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <table class="min-w-[1080px] w-full text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">Employee Number</th>
                        <th class="px-4 py-3">Employee Name</th>
                        <th class="px-4 py-3 text-right">Starting Leave Credits</th>
                        <th class="px-4 py-3 text-right">Total Leave Days Used To-Date</th>
                        <th class="px-4 py-3 text-right">Leave Balance To-Date</th>
                        <th class="px-4 py-3 text-right">Approved Carry Over</th>
                        <th class="px-4 py-3 text-right">Compensatory Time-Off Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-zinc-100 dark:border-zinc-700">
                            <td class="px-4 py-3 text-gray-500">{{ $user->employee_number ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2 font-medium text-zinc-800 dark:text-zinc-100">
                                    <span>{{ $user->preferred_name ?: $user->name }}</span>
                                    @if (! $user->is_active)
                                        <span class="rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                                    @endif
                                </div>
                                @if ($user->preferred_name)
                                    <div class="text-xs text-gray-500">{{ $user->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ number_format((float) ($user->requestCredit?->pto ?? 0) + (float) ($user->leave_days_used_to_date ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-amber-700 dark:text-amber-300">
                                {{ number_format((float) ($user->leave_days_used_to_date ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2 font-semibold text-emerald-700 dark:text-emerald-300">
                                    <span>{{ number_format((float) ($user->requestCredit?->pto ?? 0), 2) }}</span>

                                    @can('pnc-admin')
                                        <flux:modal.trigger name="edit-leave-balance-{{ $user->id }}">
                                            <flux:button size="xs" variant="ghost" icon="pencil" title="Edit leave balance">
                                                Edit
                                            </flux:button>
                                        </flux:modal.trigger>
                                    @endcan
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-violet-700 dark:text-violet-300">
                                {{ number_format((float) ($user->requestCredit?->approved_carry_over ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-700 dark:text-blue-300">
                                {{ number_format((float) ($user->compensatory_time_off_total ?? 0), 2) }}
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

        @can('pnc-admin')
            @foreach ($users as $user)
                <flux:modal name="edit-leave-balance-{{ $user->id }}" class="md:w-[420px]">
                    <div class="p-6 space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                Edit Leave Balance
                            </h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ $user->preferred_name ?: $user->name }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('leave-credits.balance.update', $user) }}" class="space-y-5">
                            @csrf
                            @method('PATCH')

                            @foreach (request()->only(['search', 'status', 'date_from', 'date_to', 'period_start', 'as_of']) as $key => $value)
                                @if ($value !== null && $value !== '')
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <div>
                                <label for="pto-{{ $user->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Leave Balance To-Date
                                </label>
                                <input id="pto-{{ $user->id }}" name="pto" type="number" step="0.01" min="0"
                                    value="{{ old('pto', number_format((float) ($user->requestCredit?->pto ?? 0), 2, '.', '')) }}"
                                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                            </div>

                            <div class="flex justify-end gap-3">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" icon="save">
                                    Save
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </flux:modal>
            @endforeach
        @endcan
    </div>
</x-layouts.app>
