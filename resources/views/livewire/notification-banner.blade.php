<div>
    @if ($visible)
        <div 
            x-data 
            x-init="setTimeout(() => { $wire.closeNotification() }, 3000)" {{-- Auto-hide in 3 seconds --}}
            class="fixed top-4 right-4 max-w-sm w-full p-3 rounded-md shadow-lg border-l-4 flex items-center justify-between"
            @class([
                'border-green-500 text-green-700 bg-green-100' => $type === 'success',
                'border-red-500 text-red-700 bg-red-100' => $type === 'error',
                'border-yellow-500 text-yellow-700 bg-yellow-100' => $type === 'warning',
                'border-blue-500 text-blue-700 bg-blue-100' => $type === 'info',
            ])
        >
            <div class="flex items-center gap-2">
                <span class="font-bold">
                    @if ($type === 'success') ✅ 
                    @elseif ($type === 'error') ❌ 
                    @elseif ($type === 'warning') ⚠️ 
                    @else ℹ️ @endif
                </span>
                <span>{{ $message }}</span>
            </div>

            {{-- Close Button --}}
            <button wire:click="closeNotification" class="ml-4 text-gray-600 hover:text-black">
                ✖
            </button>
        </div>
    @endif
</div>
