<x-layouts.app>
    <div class="px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-100">
            Request Settings
        </h1>

        {{-- Update Default Credits --}}
        <form action="{{ route('org-settings.update') }}" method="POST"
            class="space-y-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            @csrf

            <div>
                <label for="pto_default" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Default PTO Credits
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

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                <flux:button type="submit" class="w-full sm:w-auto" variant="primary">
                    Save Settings
                </flux:button>
            </div>
        </form>

        {{-- Divider --}}
        <div class="my-10 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Initialize Leave Credits --}}
        <div class="space-y-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Initialize Leave Requests
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This will reset and assign PTO and WFH credits for
                <strong>all users</strong> based on the default values above.
            </p>

            <form action="{{ route('org-settings.initiate-leave') }}" method="POST"
                onsubmit="return confirm('Are you sure you want to initialize leave credits for all users? This will overwrite current balances.');">
                @csrf
                <flux:button type="submit" variant="danger" class="w-full sm:w-auto">
                    Initialize All Leaves
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts.app>
