{{-- resources/views/livewire/holiday/campaign-form.blade.php --}}

<div class="space-y-8">
    <script>
        window.addEventListener('flash', e => console.log('FLASH EVENT:', e.detail));
    </script>
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
    <div class="space-y-2">
        <label class="text-sm font-medium">Background Color</label>

        <input type="color" wire:model="backgroundColor" class="w-16 h-10 border rounded cursor-pointer">

        <p class="text-xs text-gray-500">
            Controls the outer email background
        </p>
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

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="sparkles" size="xs" color="amber" wire:click="processHtml">
                Process HTML
            </flux:button>

            <flux:modal.trigger name="ai-generate-modal">
                <flux:button variant="primary" icon="cpu-chip" size="xs" color="purple">
                    AI Generate
                </flux:button>
            </flux:modal.trigger>

            <flux:modal.trigger name="ai-enhance-modal">
                <flux:button variant="primary" icon="wand" size="xs" color="teal">
                    Enhance
                </flux:button>
            </flux:modal.trigger>
        </div>
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

                $doc = "<!DOCTYPE html><html><head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head><body>$preview</body></html>";

                $base64 = base64_encode($doc);
            @endphp

            <iframe class="w-full border rounded bg-white" style="min-height: 600px;" sandbox="allow-same-origin"
                src="data:text/html;base64,{{ $base64 }}"></iframe>
        </div>
    </div>

    {{-- =========================
        ACTION
    ========================== --}}
    <flux:modal.trigger name="recipient-modal">

        <flux:button icon="paper-airplane" variant="primary" size="xs" color="blue" wire:click="saveCampaign">
            Send Campaign
        </flux:button>

    </flux:modal.trigger>

    {{-- =========================
        RECIPIENT MODAL
    ========================== --}}
    <flux:modal name="recipient-modal" class="max-w-2xl">

        <div class="p-6 space-y-5">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="users" class="w-5 h-5" />
                    <h2 class="text-lg font-semibold">Select Recipients</h2>
                </div>

                <flux:button size="xs" variant="ghost" wire:click="toggleSelectAll">
                    {{ count($selectedUsers) === count($users) ? 'Uncheck All' : 'Check All' }}
                </flux:button>
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

                <flux:button variant="ghost" size="xs" x-on:click="$flux.modal('recipient-modal').close()">
                    Cancel
                </flux:button>

                <flux:button icon="paper-airplane" variant="primary" color="teal" size="xs"
                    wire:click="sendCampaign">
                    Send
                </flux:button>

            </div>

        </div>

    </flux:modal>
    {{-- AI Generate Modal --}}
    <flux:modal name="ai-generate-modal" class="max-w-2xl">
        <div class="p-6 space-y-5">

            <div class="flex items-center gap-2">
                <flux:icon name="cpu-chip" class="w-5 h-5" />
                <h2 class="text-lg font-semibold">AI Generate Email</h2>
            </div>

            {{-- MODE SELECTION --}}
            <div class="space-y-2">
                <label class="text-sm font-medium">Mode</label>

                <select wire:model="aiMode"
                    class="w-full border rounded px-3 py-2 text-sm bg-neutral-900 text-white border-neutral-700">
                    <option value="format_only" class="bg-neutral-900 text-white">
                        Format Only (convert text → HTML email)
                    </option>
                    <option value="enhance" class="bg-neutral-900 text-white">
                        Enhance (improve + format)
                    </option>
                    <option value="prompt" class="bg-neutral-900 text-white">
                        Prompt (generate from scratch)
                    </option>
                </select>

                <p class="text-xs text-gray-500">
                    Choose how AI should handle your input.
                </p>
            </div>

            {{-- INPUT TEXT --}}
            <div class="space-y-2">
                <label class="text-sm font-medium">Input Text</label>

                <textarea wire:model="aiInput" rows="5"
                    placeholder="Paste your raw message or leave empty if using prompt mode..."
                    class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            {{-- EXTRA PROMPT --}}
            <div class="space-y-2">
                <label class="text-sm font-medium">Extra Instructions (optional)</label>

                <input type="text" wire:model="aiPrompt"
                    placeholder="e.g. Make it festive, Christmas theme, warm tone..."
                    class="w-full border rounded px-3 py-2 text-sm">
            </div>

            {{-- ACTIONS --}}
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" size="xs" x-on:click="$flux.modal('ai-generate-modal').close()">
                    Cancel
                </flux:button>

                <flux:button variant="primary" color="purple" size="xs" wire:click="generateAiHtml">
                    Generate & Insert
                </flux:button>
            </div>

        </div>
    </flux:modal>
    {{-- AI Enhance Modal --}}
    <flux:modal name="ai-enhance-modal" class="max-w-xl">
        <div class="p-6 space-y-5">

            <div class="flex items-center gap-2">
                <flux:icon name="wand" class="w-5 h-5" />
                <h2 class="text-lg font-semibold">Enhance Email</h2>
            </div>

            {{-- Instruction only --}}
            <div class="space-y-2">
                <label class="text-sm font-medium">Instructions</label>

                <input type="text" wire:model="enhancePrompt"
                    placeholder="e.g. Make tone warmer, shorten content, add emphasis..."
                    class="w-full border rounded px-3 py-2 text-sm bg-neutral-900 text-white border-neutral-700">
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-2">

                <flux:button variant="ghost" size="xs" x-on:click="$flux.modal('ai-enhance-modal').close()">
                    Cancel
                </flux:button>

                <flux:button variant="primary" color="teal" size="xs" wire:click="enhanceHtml">
                    Enhance & Apply
                </flux:button>

            </div>

        </div>
    </flux:modal>

</div>
