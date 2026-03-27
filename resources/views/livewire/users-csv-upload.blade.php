<div class="space-y-4">

    <!-- Header -->
    <div>
        <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">
            Update Employee Dates
        </h3>
        <p class="text-xs text-gray-500">
            Upload a CSV file (email, birthdate, hire_date — mm/dd/yyyy)
        </p>
    </div>

    <!-- File Input -->
    <div>
        <input type="file" wire:model.live="file" accept=".csv,text/csv"
            class="w-full border rounded-md p-2 dark:bg-zinc-700 dark:text-white">

        <!-- Uploading indicator -->
        <div wire:loading wire:target="file" class="text-xs text-gray-400 mt-1">
            Uploading file...
        </div>

        <!-- ✅ Laravel validation errors ONLY -->
        @if ($errors instanceof \Illuminate\Support\MessageBag && $errors->has('file'))
            <p class="text-red-500 text-xs mt-1">
                {{ $errors->first('file') }}
            </p>
        @endif
    </div>

    <!-- Actions -->
    <div class="flex items-center">
        <flux:spacer />

        <flux:button type="button" wire:click="processUpload" wire:loading.attr="disabled" variant="primary"
            size="sm">
            <span wire:loading.remove wire:target="processUpload">
                Upload CSV
            </span>
            <span wire:loading wire:target="processUpload">
                Processing...
            </span>
        </flux:button>
    </div>

    <!-- Results -->
    @if (($successCount ?? 0) > 0 || !empty($csvErrors))
        <div class="pt-4 border-t space-y-2 max-h-60 overflow-y-auto">

            <!-- Success -->
            @if (($successCount ?? 0) > 0)
                <p class="text-sm font-semibold text-green-600">
                    Updated: {{ $successCount }}
                </p>
            @endif

            <!-- ✅ CSV Errors (your custom errors) -->
            @if (!empty($csvErrors))
                <div class="text-xs text-red-500 space-y-1">
                    @foreach ($csvErrors as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

        </div>
    @endif

</div>
