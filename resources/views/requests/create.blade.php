<x-layouts.app title="New Request">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-900 shadow-xl sm:rounded-lg p-6">

            {{-- Form --}}
            <form action="{{ route('requests.store') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Credits + Offset --}}
                <div class="flex items-start justify-between gap-4">
                    <livewire:request-credits-widget />

                    <div class="flex items-center gap-2 mt-1">
                        <input type="checkbox" name="is_offset" id="is_offset" value="1" @checked(old('is_offset'))
                            class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                        <label for="is_offset" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            Offset (no credit deduction)
                        </label>
                    </div>
                </div>

                {{-- Header --}}
                <div class="flex items-center justify-between mb-6 mt-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        New Request
                    </h2>

                    <flux:button variant="ghost" href="{{ route('my-requests') }}">
                        ← Back to My Requests
                    </flux:button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Type --}}
                    <div>
                        <label for="type" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            Type
                        </label>
                        <select name="type" id="type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="PTO" @selected(old('type') === 'PTO')>Leave</option>
                            <option value="WFH" @selected(old('type') === 'WFH')>Work from Home</option>
                            <option value="LWOP" @selected(old('type') === 'LWOP')>Leave w/o Pay</option>
                        </select>
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label for="reason" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            Reason/Notes
                        </label>
                        <input type="text" name="reason" id="reason" value="{{ old('reason') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <label for="start_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            Start Date
                        </label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label for="end_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            End Date
                        </label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    {{-- End Date Type --}}
                    <div>
                        <label for="end_date_type" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
                            End Date Type
                        </label>
                        <select name="end_date_type" id="end_date_type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="full" @selected(old('end_date_type') === 'full')>
                                Full Day
                            </option>
                            <option value="half-am-off" @selected(old('end_date_type') === 'half-am-off')>
                                Half Day: Morning Off
                            </option>
                            <option value="half-pm-off" @selected(old('end_date_type') === 'half-pm-off')>
                                Half Day: Afternoon Off
                            </option>
                        </select>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex justify-end gap-4">
                    <flux:button variant="ghost" href="{{ route('my-requests') }}">
                        Cancel
                    </flux:button>

                    <flux:button variant="primary" type="submit">
                        Submit Request
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="text-red-600 text-xs mt-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-layouts.app>
