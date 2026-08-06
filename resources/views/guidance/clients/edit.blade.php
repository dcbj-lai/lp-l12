<x-layouts.app title="Edit Student Client">
    <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Edit Student Client</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                    Update {{ $client->first_name }} {{ $client->last_name }}'s Guidance profile.
                </p>
            </div>
            <a href="{{ route('guidance.clients.show', $client) }}"
               class="inline-flex items-center justify-center rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100 dark:border-neutral-600 dark:text-neutral-200 dark:hover:bg-neutral-800">
                Back to Profile
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                <p class="font-semibold">Please correct the highlighted fields.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('guidance.clients.update', $client) }}"
              class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900 sm:p-7">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="first_name" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">First Name *</label>
                    <input id="first_name" name="first_name" type="text" required value="{{ old('first_name', $client->first_name) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div>
                    <label for="last_name" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Last Name *</label>
                    <input id="last_name" name="last_name" type="text" required value="{{ old('last_name', $client->last_name) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label for="email" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Email *</label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $client->email) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div>
                    <label for="course" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Course</label>
                    <input id="course" name="course" type="text" value="{{ old('course', $client->course) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div>
                    <label for="section" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Section</label>
                    <input id="section" name="section" type="text" value="{{ old('section', $client->section) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div class="md:col-span-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <input type="hidden" name="is_under_accessibility" value="0">
                    <label class="flex items-start justify-between gap-4">
                        <span>
                            <span class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">Under Accessibility</span>
                            <span class="mt-1 block text-xs text-neutral-500 dark:text-neutral-400">SAS will be copied on applicable consultation notifications.</span>
                        </span>
                        <input name="is_under_accessibility" type="checkbox" value="1"
                               @checked((bool) old('is_under_accessibility', $client->is_under_accessibility))
                               class="mt-1 rounded border-neutral-300 text-blue-600 focus:ring-blue-500">
                    </label>
                </div>
                <div>
                    <label for="blood_type" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Blood Type</label>
                    <input id="blood_type" name="blood_type" type="text" maxlength="10" value="{{ old('blood_type', $client->blood_type) }}" placeholder="e.g., O+"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div>
                    <label for="emergency_contact_person" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Emergency Contact Person</label>
                    <input id="emergency_contact_person" name="emergency_contact_person" type="text" value="{{ old('emergency_contact_person', $client->emergency_contact_person) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label for="emergency_contact_number" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Emergency Contact Number</label>
                    <input id="emergency_contact_number" name="emergency_contact_number" type="text" inputmode="tel" value="{{ old('emergency_contact_number', $client->emergency_contact_number) }}"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                </div>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row">
                <a href="{{ route('guidance.clients.show', $client) }}"
                   class="inline-flex items-center justify-center rounded-md border border-neutral-300 px-4 py-2 font-medium text-neutral-700 hover:bg-neutral-100 dark:border-neutral-600 dark:text-neutral-200 dark:hover:bg-neutral-800">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-2 font-semibold text-white hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</x-layouts.app>
