<x-layouts.app title="Visitor Details">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg p-6 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-semibold text-neutral-800 dark:text-neutral-100">
                    Visitor Details
                </h2>

                <a href="{{ route('visitors.mine') }}" class="inline-block px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 
                          dark:bg-neutral-700 dark:hover:bg-neutral-600 
                          text-sm font-medium text-center transition">
                    ← Back to My Visitors
                </a>
            </div>

            <!-- Status Pill -->
            <div>
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
                    class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$visitor->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($visitor->status) }}
                </span>
            </div>

            <!-- Visitor Form -->
            <form action="{{ route('visitor.approve', $visitor->id) }}" method="POST" class="space-y-5">
                @csrf

                <div class="space-y-2 text-sm text-neutral-700 dark:text-neutral-300">
                    <p><span class="font-semibold">Full Name:</span> {{ $visitor->full_name }}</p>
                    <p><span class="font-semibold">Company:</span> {{ $visitor->company }}</p>
                    <p><span class="font-semibold">Email:</span> {{ $visitor->email }}</p>
                    <p><span class="font-semibold">Mobile:</span> {{ $visitor->mobile }}</p>
                    <p><span class="font-semibold">Address:</span> {{ $visitor->address }}</p>
                    <p><span class="font-semibold">Purpose:</span> {{ $visitor->purpose ?? '-' }}</p>
                    <p><span class="font-semibold">Visit Date:</span>
                        {{ \Carbon\Carbon::parse($visitor->visit_date)->format('F j, Y') }}
                    </p>
                </div>

                <!-- Meetup Instructions -->
                <div>
                    <label for="meetup_spot"
                        class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        Meetup Notes
                    </label>

                    @php
                        $isEditable = false;
                        if ($visitor->batch_id === NULL) {
                            if ($visitor->status === 'endorsed') {
                                $isEditable = true;
                            }
                        }
                    @endphp


                    <input type="text" name="meetup_spot" id="meetup_spot"
                        value="{{ old('meetup_spot', $visitor->meetup_spot) }}" class="w-full rounded-md border border-neutral-300 dark:border-neutral-700 
                    px-3 py-2 text-sm transition
                    {{ $isEditable
    ? 'bg-neutral-50 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
    : 'bg-neutral-100 dark:bg-neutral-900 text-neutral-500 dark:text-neutral-500 cursor-not-allowed opacity-70' }}" {{ $isEditable ? '' : 'readonly' }}>
                </div>


                @if($visitor->batch_id === NULL)
                    <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
                        <button type="submit"
                            class="px-3 py-1 rounded bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            onclick="document.getElementById('action_type').value='decline';"
                            @disabled(in_array($visitor->status, ['approved', 'declined', 'checked_out', 'pending']))>
                            Decline Visit
                        </button>

                        <button type="submit"
                            class="px-3 py-1 rounded bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            onclick="document.getElementById('action_type').value='approve';"
                            @disabled(in_array($visitor->status, ['approved', 'declined', 'checked_out', 'pending']))>
                            Approve Visit
                        </button>
                    </div>
                @endif

                <input type="hidden" name="action_type" id="action_type" value="">
            </form>

            <!-- Cancel Visit Button (Separate Form) -->
            @if ($visitor->batch_id != NULL)
                <form action="{{ route('visitor.cancel-batch', $visitor->batch_id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to cancel this pre-approved visit? This will delete all visitors in this batch.');"
                    class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-3 py-1 rounded text-white text-sm font-medium 
                                {{ $visitor->status === 'checked_out' ? 'bg-gray-400 cursor-not-allowed' : 'bg-amber-600 hover:bg-amber-700' }}"
                        {{ $visitor->status === 'checked_out' ? 'disabled' : '' }}>
                        Cancel Visit
                    </button>
                </form>
            @endif


        </div>
    </div>
</x-layouts.app>
