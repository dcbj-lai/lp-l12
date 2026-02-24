<x-layouts.app>
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Import CSV</h1>
            <p class="text-sm text-white-600 mt-1">
                Upload a CSV file to import student records into the Guidance system.
            </p>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="font-medium mb-2">Please fix the following:</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-lg border bg-white shadow-sm">
            <div class="p-5 border-b">
                <h2 class="text-lg font-medium">Upload CSV</h2>
                <p class="text-sm text-gray-600">
                    Headers must be exactly:
                    <span class="font-mono">first_name,last_name,email</span>
                </p>
            </div>

            <div class="p-5">
                <form action="{{ route('guidance.import-csv.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Choose CSV file
                        </label>

                        <label
                            for="csv_file"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center hover:bg-gray-100"
                        >
                            <div class="text-sm font-medium text-gray-700">Click to upload a CSV</div>
                            <div id="csv_filename" class="mt-3 text-sm text-gray-700">No file selected</div>

                            <input
                                id="csv_file"
                                name="csv_file"
                                type="file"
                                accept=".csv,text/csv"
                                required
                                class="sr-only"
                            />
                        </label>

                        <p class="mt-2 text-xs text-gray-500">Accepted: .csv (max 5MB)</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Import
                        </button>

                        <a href="{{ route('guidance.clients.index') }}"
                           class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>

                <script>
                (function () {
                    const input = document.getElementById('csv_file');
                    const label = document.getElementById('csv_filename');
                    if (!input || !label) return;

                    input.addEventListener('change', function () {
                        label.textContent = input.files && input.files[0] ? input.files[0].name : 'No file selected';
                    });
                })();
                </script>
            </div>
        </div>
    </div>
</x-layouts.app>