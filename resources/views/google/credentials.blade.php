<x-layouts.app title="Upload Google Credentials">
    <div class="max-w-xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Upload Google Calendar Credentials
            </h2>

            @if (session('success'))
                <div class="mb-4 p-3 text-green-700 bg-green-100 rounded dark:bg-green-900/30 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('google.credentials.upload') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="credentials_json"
                        class="block font-medium text-sm text-gray-700 dark:text-gray-300">JSON File</label>
                    <input type="file" name="credentials_json" id="credentials_json"
                        class="mt-1 block w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-md shadow-sm"
                        required>
                </div>

                <div class="mt-6 flex justify-end">
                    <flux:button type="submit" variant="primary">Upload</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
