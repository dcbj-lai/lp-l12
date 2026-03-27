<div class="space-y-4">

    <!-- Instructions -->
    <div class="text-xs text-gray-500 space-y-1">
        <p>1. Select a CSV file with columns: email, birthdate, hire_date (mm/dd/yyyy).</p>
        <p>2. Wait for the file to finish uploading.</p>
        <p>3. Click "Apply Updates from CSV" to process the data.</p>
    </div>

    <!-- File Input -->
    <div>
        <input type="file" wire:model.live="file" accept=".csv,text/csv"
            class="w-full border rounded-md p-2 dark:bg-zinc-700 dark:text-white">

        <!-- Uploading indicator -->
        <div wire:loading wire:target="file" class="text-xs text-gray-400 mt-1">
            Uploading file...
        </div>

        <!-- Validation errors -->
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
                Apply Updates from CSV
            </span>
            <span wire:loading wire:target="processUpload">
                Processing...
            </span>
        </flux:button>
    </div>

    <!-- Results -->
    @if (($successCount ?? 0) > 0 || !empty($csvErrors))
        <div class="pt-4 border-t space-y-2 max-h-60 overflow-y-auto">

            @if (($successCount ?? 0) > 0)
                <p class="text-sm font-semibold text-green-600">
                    Updated: {{ $successCount }}
                </p>
            @endif

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
