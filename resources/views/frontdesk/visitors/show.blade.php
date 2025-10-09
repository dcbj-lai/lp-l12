<x-layouts.app title="Visitor Details">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">
                    Visitor Details
                </h2>

                <a href="{{ route('frontdesk.visitors') }}" class="inline-block px-4 py-2 rounded bg-gray-200 hover:bg-gray-300
                          dark:bg-neutral-700 dark:hover:bg-neutral-600
                          text-sm font-medium text-center transition">
                    ← Back to Visitor Logs
                </a>
            </div>

            <!-- Visitor Info and Check-In -->
            <form action="{{ route('frontdesk.checkin', $visitor) }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-neutral-700 dark:text-neutral-300">
                    <p><span class="font-semibold">Full Name:</span> {{ $visitor->full_name }}</p>
                    <p><span class="font-semibold">Company:</span> {{ $visitor->company }}</p>
                    <p><span class="font-semibold">Email:</span> {{ $visitor->email }}</p>
                    <p><span class="font-semibold">Mobile:</span> {{ $visitor->mobile }}</p>
                    <p><span class="font-semibold">Address:</span> {{ $visitor->address }}</p>
                    <p><span class="font-semibold">Person Visited:</span>
                        {{ optional($visitor->visitedUser)->name ?? '-' }}</p>
                    <p><span class="font-semibold">Purpose:</span> {{ $visitor->purpose ?? '-' }}</p>

                    <div class="sm:col-span-2">
                        <label for="meetup_spot" class="font-semibold block mb-1">Meetup Notes:</label>
                        <input type="text" id="meetup_spot" name="meetup_spot"
                            value="{{ old('meetup_spot', $visitor->meetup_spot) }}" readonly
                            class="w-full rounded-md border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 text-sm cursor-not-allowed bg-gray-100 dark:bg-neutral-900">
                    </div>


                    <p>
                        <span class="font-semibold">Status:</span>
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                'endorsed' => 'bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-300',
                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                'declined' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                'checked_out' => 'bg-purple-100 text-purple-800 dark:bg-purple-800/20 dark:text-purple-300',
                            ];

                        @endphp
                        <span
                            class="inline-block px-2 py-0.5 rounded-md text-xs font-medium
    {{ $statusColors[strtolower($visitor->status)] ?? 'bg-neutral-200 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                            {{ ucfirst($visitor->status) }}
                        </span>

                        @php
                            $instructions = [
                                'pending' => 'Review details and endorse this visitor.',
                                'endorsed' => 'Wait for approval from the visited person.',
                                'approved' => 'Issue a guest badge or prepare for checkout.',
                                'declined' => 'Visitor declined—notify and escort them out.',
                                'checked_out' => 'Visitor already checked out—no further action needed.',
                            ];
                        @endphp

                        @if(isset($instructions[strtolower($visitor->status)]))
                            <span class="block mt-1 text-xs text-neutral-600 dark:text-neutral-400 italic">
                                {{ $instructions[strtolower($visitor->status)] }}
                            </span>
                        @endif

                    </p>

                    <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <p>
                            <span class="font-semibold">Check-In:</span>
                            {{ $visitor->check_in_at ? $visitor->check_in_at->format('M d, Y H:i') : '-' }}
                        </p>
                        <p>
                            <span class="font-semibold">Check-Out:</span>
                            {{ $visitor->check_out_at ? $visitor->check_out_at->format('M d, Y H:i') : '-' }}
                        </p>
                    </div>

                </div>

                <!-- Endorse/Cancel Buttons -->
                @if ($visitor->status === 'pending')
                    <div class="flex gap-3">
                        <button type="submit" class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Endorse
                        </button>
                        <a href="{{ route('frontdesk.visitors') }}"
                            class="text-sm px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600">
                            Cancel
                        </a>
                    </div>
                @endif
            </form>

            <!-- Checkout Button -->
            @if ($visitor->status === 'approved')
                <form action="{{ route('frontdesk.checkout', $visitor) }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="text-sm px-3 py-1 bg-emerald-600 text-white rounded hover:bg-emerald-700">
                        Check Out
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
