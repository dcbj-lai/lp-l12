<x-layouts.app title="Online Days">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-neutral-100">Online Days</h1>
        <a href="{{ route('onlinedays.create') }}"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow transition-all duration-150">
            <flux:icon.circle-plus class="w-4 h-4" />
            Add New
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Date</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Declared By
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Remarks</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Active</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($onlineDays as $day)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                            {{ $day->date->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                            {{ $day->declared_by }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $day->remarks }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($day->is_active)
                                <span class="inline-flex items-center text-green-600 dark:text-green-400 font-semibold">
                                    <flux:icon.circle-check class="w-4 h-4 mr-1" /> Active
                                </span>
                            @else
                                <span class="inline-flex items-center text-gray-500 dark:text-gray-400">
                                    <flux:icon.ban class="w-4 h-4 mr-1" /> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 flex gap-2">
                            <a href="{{ route('onlinedays.edit', $day) }}"
                                class="inline-flex items-center gap-1 bg-slate-600 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium shadow-sm transition">
                                <flux:icon.pencil class="w-3.5 h-3.5" /> Edit
                            </a>

                            <form action="{{ route('onlinedays.destroy', $day) }}" method="POST"
                                onsubmit="return confirm('Delete this record?')" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium shadow-sm transition">
                                    <flux:icon.circle-x class="w-3.5 h-3.5" /> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                            No online days declared yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $onlineDays->links() }}
    </div>
</x-layouts.app>
