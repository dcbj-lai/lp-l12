<div class="p-4 md:p-6 bg-white dark:bg-zinc-800 shadow-md rounded-lg max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            Create Pre-Approved Visit
        </h1>
        <a href="{{ route('visitors.mine') }}" class="inline-block px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 
                          dark:bg-neutral-700 dark:hover:bg-neutral-600 
                          text-sm font-medium text-center transition">
            ← Back to My Visitors
        </a>
    </div>

    {{-- Visit Details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Visit Date & Time</label>
            <input type="datetime-local" wire:model.defer="visit_date"
                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
            @error('visit_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Company</label>
            <input type="text" wire:model.defer="company" placeholder="Visitor's Company"
                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
            @error('company') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Purpose</label>
            <input type="text" wire:model.defer="purpose" placeholder="e.g., Meeting, Delivery"
                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
            @error('purpose') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Visitor Inputs --}}
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Visitor Details</label>

        {{-- Pills --}}
        <div
            class="flex flex-wrap gap-2 mb-2 p-2 border border-dashed border-zinc-300 dark:border-zinc-600 rounded-md min-h-[2.5rem]">
            @forelse($visitors as $index => $v)
                <div
                    class="flex items-center gap-2 border border-zinc-300 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 px-3 py-1 rounded-full text-xs">
                    <span>{{ $v['name'] }} ({{ $v['email'] }})</span>
                    <button type="button" wire:click="removeVisitor({{ $index }})" class="hover:text-amber-600">
                        <flux:icon.x class="w-4 h-4 inline" />
                    </button>
                </div>
            @empty
                <span class="text-xs text-zinc-400">Add expected visitor(s) here...</span>
            @endforelse
        </div>
        @error('visitors') <p class="text-red-500 text-sm mb-2">{{ $message }}</p> @enderror

        {{-- Input fields --}}
        <div class="flex flex-col md:flex-row gap-2">
            <input type="text" wire:model.defer="visitor_name" placeholder="Full Name"
                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white flex-1">
            <input type="email" wire:model.defer="visitor_email" placeholder="Email Address"
                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white flex-1">
            <flux:button type="button" size="sm" variant="primary" wire:click="addVisitor">Add</flux:button>
        </div>
    </div>

    {{-- Notes (optional) --}}
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            Notes <span class="text-xs text-zinc-400">(optional)</span>
        </label>
        <textarea wire:model.defer="notes" rows="3" placeholder="Any special instructions or remarks..."
            class="border px-4 py-2 rounded-md text-sm w-full transition dark:bg-zinc-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
        @error('notes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- CSV Upload + Submit --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mt-6">
        {{-- CSV Upload --}}
        <div class="flex-1 flex gap-2">
            <input type="file" wire:model="csvFile" accept=".csv"
                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white flex-1">
            <flux:button type="button" size="sm" wire:click="uploadCsv">Upload CSV</flux:button>
        </div>
        @error('csvFile') <p class="text-red-500 text-sm mt-1 w-full md:w-auto">{{ $message }}</p> @enderror

        {{-- Submit Button --}}
        <div>
            <flux:button size="sm" variant="primary" icon="send" wire:click="save">Create Pre-Approved Visit
            </flux:button>
        </div>
    </div>


    @if (session('message'))
        <div class="text-green-600 text-sm mt-3">{{ session('message') }}</div>
    @endif
</div>
