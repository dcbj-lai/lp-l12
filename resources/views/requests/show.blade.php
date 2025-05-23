<x-layouts.app>
    <div x-data="{ open: true }">
        <div x-show="open" x-transition
            class="fixed left-64 top-0 right-0 bottom-0 z-40 flex items-center justify-center bg-transparent">

            <div
                class="bg-white dark:bg-neutral-900 shadow-xl rounded-lg p-6 w-full max-w-lg relative border border-neutral-300 dark:border-neutral-700">
                <button @click="window.location.href='{{ route('requests.manage') }}'"
                    class="absolute top-2 right-2 text-neutral-400 hover:text-neutral-200 text-2xl">
                    &times;
                </button>

                <h2 class="text-2xl font-bold mb-4">Leave Request Details</h2>
                <div class="text-neutral-800 dark:text-neutral-100 space-y-2 text-sm">
                    <p><strong>Employee:</strong> {{ $request->user->name }}</p>
                    <p><strong>Type:</strong> {{ $request->type }}</p>
                    <p><strong>Start Date:</strong> {{ $request->start_date }}</p>
                    <p><strong>End Date:</strong> {{ $request->end_date }}</p>
                    <p>
                        <strong>Status:</strong>
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                            ];
                        @endphp
                        <span
                            class="inline-block px-2 py-0.5 rounded-md text-xs font-medium
                            {{ $statusColors[strtolower($request->status)] ?? 'bg-neutral-200 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </p>
                    <p><strong>Number of Days:</strong> {{ $request->number_of_days }}</p>
                    <p><strong>Reason:</strong> {{ $request->reason }}</p>
                </div>

                <div class="mt-6">
                    <form action="{{ route('requests.process', $request->id) }}" method="POST" id="action-form">
                        @csrf

                        <textarea name="remarks" rows="2" placeholder="Remarks"
                            class="w-full text-sm border-neutral-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white rounded-md mt-4">{{ old('remarks', $request->remarks) }}</textarea>

                        <input type="hidden" name="action_type" id="action_type" value="">

                        <div class="mt-4 flex justify-between items-center">
                            <div class="flex gap-4">
                                <flux:button variant="ghost" type="submit" size="sm"
                                    onclick="document.getElementById('action_type').value='reject';">
                                    Reject
                                </flux:button>

                                <flux:button variant="primary" type="submit" size="sm"
                                    onclick="document.getElementById('action_type').value='approve';">
                                    Approve
                                </flux:button>
                            </div>
                        </div>
                    </form>
                    @if ($request->status !== 'pending')
                        <form method="POST" action="{{ route('requests.destroy', $request->id) }}"
                            onsubmit="return confirm('Are you sure you want to delete this request?');" class="ml-auto">
                            @csrf
                            @method('DELETE')
                            <div class="flex justify-end">
                                <flux:button variant="danger" type="submit" size="sm">
                                    Delete
                                </flux:button>
                            </div>
                        </form>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
