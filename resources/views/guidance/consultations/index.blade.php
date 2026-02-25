<x-layouts.app>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold">Consultations</h1>
        </div>

        {{-- Filters --}}
        <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-sm text-gray-600">Search (Name or Email)</label>
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? request('q') }}"
                    class="border rounded px-3 py-2 w-72"
                    placeholder="e.g., Juan or juan@email.com"
                />
            </div>

            <div>
                <label class="block text-sm text-gray-600">Date From</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom ?? request('date_from') }}"
                    class="border rounded px-3 py-2"
                />
            </div>

            <div>
                <label class="block text-sm text-gray-600">Date To</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo ?? request('date_to') }}"
                    class="border rounded px-3 py-2"
                />
            </div>

            <div class="flex gap-2">
                <button class="bg-blue-600 text-white rounded px-4 py-2">
                    Filter
                </button>

                <a href="{{ route('guidance.consultations.index') }}"
                   class="bg-gray-200 text-gray-800 rounded px-4 py-2">
                    Reset
                </a>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Student</th>
                        <th class="text-left px-4 py-3">Email</th>
                        <th class="text-left px-4 py-3">Current Teacher</th>
                        <th class="text-left px-4 py-3">Time In</th>
                        <th class="text-left px-4 py-3">Time Out</th>
                        <th class="text-left px-4 py-3">After Consultation</th>
                        <th class="text-left px-4 py-3">Remarks</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($consultations as $consultation)
                        <tr class="border-t">
                            <td class="px-4 py-3">
                                {{ optional($consultation->client)->first_name }}
                                {{ optional($consultation->client)->last_name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ optional($consultation->client)->email ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $consultation->current_teacher ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ optional($consultation->time_in)->format('Y-m-d h:i A') ?? \Carbon\Carbon::parse($consultation->time_in)->format('Y-m-d h:i A') }}
                            </td>

                            <td class="px-4 py-3">
                                @if($consultation->time_out)
                                    {{ \Carbon\Carbon::parse($consultation->time_out)->format('Y-m-d h:i A') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $consultation->after_consultation
                                    ? \Illuminate\Support\Str::of($consultation->after_consultation)->replace('_', ' ')->title()
                                    : '—'
                                }}
                            </td>

                            <td class="px-4 py-3">
                                {{ \Illuminate\Support\Str::limit($consultation->remarks ?? '', 60) ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('guidance.consultations.show', ['consultation' => $consultation->getKey()]) }}"
                                    class="inline-flex items-center px-3 py-1.5 rounded bg-gray-800 text-white hover:bg-gray-700">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                No consultations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $consultations->links() }}
        </div>
    </div>
</x-layouts.app>