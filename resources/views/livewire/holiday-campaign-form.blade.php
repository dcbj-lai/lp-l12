{{-- resources/views/livewire/holiday/campaign-form.blade.php --}}

<div class="space-y-8">

    {{-- =========================
        ASSET UPLOAD
    ========================== --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:icon name="arrow-up-tray" class="w-5 h-5" />
            <h2 class="font-semibold">Upload Assets</h2>
        </div>

        <input type="file" wire:model="assets" wire:key="{{ $uploadKey }}" multiple
            class="block w-full border rounded px-3 py-2">

        @error('assets.*')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror

        @if (count($assetMap))
            <div class="border rounded p-3 space-y-2">
                @foreach ($assetMap as $original => $url)
                    <div class="text-sm">
                        <div class="font-medium">{{ $original }}</div>
                        <div class="text-gray-500 break-all">{{ $url }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    {{-- Recipients --}}
    <div class="grid grid-cols-2 gap-3 mb-4">

        <div>
            <label class="text-sm">From Email</label>
            <input type="email" wire:model="fromEmail" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="text-sm">From Name</label>
            <input type="text" wire:model="fromName" class="w-full border rounded px-3 py-2">
        </div>

    </div>
    {{-- subject --}}
    <div class="space-y-2 mb-4">

        <div class="flex items-center gap-2">
            <flux:icon name="envelope" class="w-5 h-5" />
            <h2 class="font-semibold">Email Subject</h2>
        </div>

        <input type="text" wire:model="subject" placeholder="Enter email subject"
            class="w-full border rounded px-3 py-2">

    </div>
    {{-- =========================
        HTML INPUT
    ========================== --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:icon name="code-bracket" class="w-5 h-5" />
            <h2 class="font-semibold">HTML Template</h2>
        </div>

        <textarea wire:model="html" rows="10" class="w-full border rounded px-3 py-2 font-mono text-sm"></textarea>

        <flux:button icon="sparkles" wire:click="processHtml">
            Process HTML
        </flux:button>
    </div>

    {{-- =========================
        PREVIEW
    ========================== --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:icon name="eye" class="w-5 h-5" />
            <h2 class="font-semibold">Preview</h2>
        </div>

        <div>
            @php
                $preview = html_entity_decode($processedHtml);
                $preview = preg_replace('/\{\{\s*name\s*\}\}/', 'Colleague', $preview);
            @endphp

            {!! $preview !!}
        </div>
    </div>

    {{-- =========================
        ACTION
    ========================== --}}
    <flux:modal.trigger name="recipient-modal">

        <flux:button icon="paper-airplane" variant="primary" wire:click="saveCampaign">
            Send Campaign
        </flux:button>

    </flux:modal.trigger>

    {{-- =========================
        RECIPIENT MODAL
    ========================== --}}
    <flux:modal name="recipient-modal" class="max-w-2xl">

        <div class="p-6 space-y-5">

            <div class="flex items-center gap-2">
                <flux:icon name="users" class="w-5 h-5" />
                <h2 class="text-lg font-semibold">Select Recipients</h2>
            </div>

            {{-- Users --}}
            <div class="max-h-64 overflow-y-auto border rounded p-3 space-y-2">
                @foreach ($users as $user)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="selectedUsers" value="{{ $user['id'] }}">
                        <span>
                            {{ $user['name'] }}
                            <span class="text-gray-500">
                                ({{ $user['email'] }})
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            {{-- External Emails --}}
            <div class="space-y-1">
                <label class="text-sm font-medium">
                    External Emails
                </label>

                <input type="text" wire:model="externalEmails" placeholder="example@gmail.com, test@yahoo.com"
                    class="w-full border rounded px-3 py-2 text-sm">
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-2">

                <flux:button variant="ghost" x-on:click="$flux.modal('recipient-modal').close()">
                    Cancel
                </flux:button>

                <flux:button icon="paper-airplane" variant="primary" wire:click="sendCampaign">
                    Send
                </flux:button>

            </div>

        </div>

    </flux:modal>

</div>
