<x-layouts.app title="New Request">
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6">
            <livewire:request-credits-widget /> <!-- ← Request credits -->
            <div class="flex items-center justify-between mb-6 mt-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">New Request</h2>

                <flux:button variant="ghost" href="{{ route('my-requests') }}">
                    ← Back to My Requests
                </flux:button>
            </div>

            <form action="{{ route('requests.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="type"
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Type</label>
                        <select name="type" id="type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="PTO" @selected(old('type') === 'PTO')>PTO</option>
                            <option value="WFH" @selected(old('type') === 'WFH')>WFH</option>
                            <option value="LWOP" @selected(old('type') === 'LWOP')>LWOP</option>
                        </select>
                        @error('type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason"
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Reason</label>
                        <input type="text" name="reason" id="reason" value="{{ old('reason') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        @error('reason') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="start_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Start
                            Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        @error('start_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300">End
                            Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        @error('end_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="end_date_type"
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">End Date Type</label>
                        <select name="end_date_type" id="end_date_type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="full" @selected(old('end_date_type') === 'full')>Full Day</option>
                            <option value="half" @selected(old('end_date_type') === 'half')>Half Day</option>
                        </select>
                        @error('end_date_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

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
</x-layouts.app>
