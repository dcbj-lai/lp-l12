<x-layouts.app title="My Requests">
    <div class="flex items-center justify-between mt-4 px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-bold text-neutral-800 dark:text-neutral-200">My Requests</h2>

        <flux:button variant="primary" type="submit" variant="outline" href="{{ route('requests.create') }}">
            + New Request
        </flux:button>
    </div>

    <div class="overflow-x-auto mt-4 px-4 sm:px-6 lg:px-8">
        <livewire:request-credits-widget />

        <table class="w-full min-w-max border-collapse border border-neutral-200 dark:border-neutral-700 text-sm mt-4">
            <thead>
                <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                    <th class="border px-4 py-2 text-left">Type</th>
                    <th class="border px-4 py-2 text-left">Date Range</th>
                    <th class="border px-4 py-2 text-left">Days</th>
                    <th class="border px-4 py-2 text-left">Reason</th>
                    <th class="border px-4 py-2 text-left">Status</th>
                    <th class="border px-4 py-2 text-left">Approver</th>
                    <th class="border px-4 py-2 text-left">Requested At</th>
                    <th class="border px-4 py-2 text-left">Updated At</th>
                    <th class="border px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="text-neutral-800 dark:text-neutral-300">
                @forelse ($requests as $request)
                    <tr class="border-b">
                        <td class="border px-4 py-2">
                            @if ($request->type === 'PTO')
                                Leave
                            @elseif ($request->type === 'WFH')
                                Work From Home
                            @else
                                {{ ucfirst($request->type) }}
                            @endif
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap text-xs">
                            {{ \Carbon\Carbon::parse($request->start_date)->format('Y-m-d') }} —
                            {{ \Carbon\Carbon::parse($request->end_date)->format('Y-m-d') }}
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            {{ number_format($request->number_of_days, 1) }}
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            {{ $request->reason }}
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                    'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                    'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                    'cancelled' => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-700/20 dark:text-neutral-300',
                                ];
                                $status = strtolower($request->status);
                            @endphp

                            <span
                                class="inline-block px-2 py-0.5 rounded-md text-xs font-medium capitalize
                                                                                                                                                                                        {{ $statusColors[$status] ?? 'bg-neutral-200 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                                {{ $status }}
                            </span>
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            {{ optional($request->approver)->name ?? '—' }}
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            {{ $request->created_at->timezone('Asia/Manila')->format('Y-m-d h:i A') }}
                        </td>
                        <td class="border px-4 py-2 whitespace-nowrap">
                            {{ $request->updated_at->timezone('Asia/Manila')->format('Y-m-d h:i A') }}
                        </td>
                        <td class="border px-4 py-2">
                            <a href="{{ route('requests.view', $request->id) }}"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center px-4 py-6 text-neutral-500 dark:text-neutral-400">
                            No requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 px-4 sm:px-6 lg:px-8">
        {{ $requests->links() }}
    </div>
</x-layouts.app>
