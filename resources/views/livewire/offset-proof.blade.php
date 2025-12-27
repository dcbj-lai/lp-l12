<div class="space-y-2">
    @if ($request->offset_proof_path)
        <span
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                   bg-sky-100 text-sky-700 text-xs
                   dark:bg-sky-800/30 dark:text-sky-300">

            <a href="{{ route('requests.documents.show', $request->offset_proof_path) }}" target="_blank"
                class="hover:underline truncate max-w-[180px]">
                {{ basename($request->offset_proof_path) }}
            </a>


            <button wire:click="remove" type="button" class="text-rose-600 hover:text-rose-800" title="Remove file">
                ✕
            </button>
        </span>
    @endif

    <input type="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png"
        class="block w-full text-xs
                  file:mr-4 file:rounded file:border-0
                  file:bg-sky-100 file:text-sky-700
                  dark:file:bg-sky-800/30 dark:file:text-sky-300">

    @error('file')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
