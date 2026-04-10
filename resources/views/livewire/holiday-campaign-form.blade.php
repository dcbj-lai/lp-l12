{{-- resources/views/livewire/holiday/campaign-form.blade.php --}}

<div class="space-y-8">
    <script>
        window.addEventListener('flash', e => console.log('FLASH EVENT:', e.detail));
    </script>
    {{-- =========================
    ASSET UPLOAD
========================== --}}
    <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
        x-on:livewire-upload-finish="isUploading = false; progress = 0" x-on:livewire-upload-error="isUploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress" class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:icon name="arrow-up-tray" class="w-5 h-5" />
            <h2 class="font-semibold">Upload Assets</h2>
        </div>

        <input type="file" wire:model="assets" wire:key="{{ $uploadKey }}" multiple
            class="block w-full border rounded px-3 py-2">

        @error('assets.*')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror

        {{-- 🔥 Minimal Progress Bar --}}
        <div x-show="isUploading" class="w-full">
            <div class="h-1 w-full bg-gray-700 rounded overflow-hidden">
                <div class="h-1 bg-purple-500 transition-all duration-200" :style="`width: ${progress}%`"></div>
            </div>
            <div class="text-xs text-gray-400 mt-1" x-text="`Uploading ${progress}%`"></div>
        </div>

        {{-- Uploaded Files --}}
        @if (count($assetMap))
            <div class="border rounded p-3 space-y-2">
                @foreach ($assetMap as $original => $url)
                    <div class="text-sm flex items-start justify-between gap-2">

                        <div class="flex-1">
                            <div class="font-medium">{{ $original }}</div>
                            <div class="text-gray-500 break-all">{{ $url }}</div>
                        </div>

                        {{-- 🔥 Copy Button --}}
                        <flux:button variant="ghost" size="xs" x-data
                            x-on:click="
                            navigator.clipboard.writeText('{{ $url }}');
                            $el.innerText = 'Copied';
                            setTimeout(() => $el.innerText = 'Copy', 1200);
                        ">
                            Copy
                        </flux:button>

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

        <div x-data="highlightEditor()" x-init="init()" class="relative w-full">

            {{-- Highlight Layer --}}
            <pre x-ref="highlight"
                class="absolute inset-0 overflow-auto border rounded px-3 py-2 font-mono text-sm leading-6 whitespace-pre-wrap break-words text-white"
                x-html="highlight(value)"></pre>

            {{-- Textarea --}}
            <textarea x-model="value" rows="10" spellcheck="false" style="resize: none;"
                @scroll="$refs.highlight.scrollTop = $el.scrollTop; $refs.highlight.scrollLeft = $el.scrollLeft"
                class="relative w-full border rounded px-3 py-2 font-mono text-sm leading-6 whitespace-pre-wrap break-words bg-transparent text-transparent caret-white"></textarea>

        </div>

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
            <flux:button variant="primary" size="xs" color="fuchsia" wire:click="resetForm">
                <flux:icon name="refresh-ccw-dot" class="w-4 h-4" />
            </flux:button>
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

        <flux:button icon="paper-airplane" variant="primary" size="xs" color="blue"
            wire:click="saveCampaign">
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

                <label class="text-sm text-gray-400">Instructions (optional)</label>

                <div class="flex gap-2">

                    <select class="w-1/3 border rounded px-2 py-1 text-sm bg-zinc-900 text-white border-zinc-700"
                        wire:change="applyPreset($event.target.value)">
                        <option value="" class="bg-zinc-900 text-white">Select preset...</option>

                        @foreach ($aiPresets as $preset)
                            <option value="{{ $preset }}" class="bg-zinc-900 text-white">
                                {{ $preset }}
                            </option>
                        @endforeach
                    </select>

                    <input type="text" wire:model="aiPrompt" placeholder="Add custom instructions..."
                        class="flex-1 border rounded px-3 py-2 text-sm" />

                </div>

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
    <script>
        function highlightEditor() {
            return {
                value: '',

                init() {
                    // 🔥 Initial sync
                    this.value = this.$wire.html ?? '';

                    // 🔥 Sync: Livewire → Alpine (AI generate, reset, etc.)
                    this.$watch('$wire.html', (val) => {
                        this.value = val ?? '';
                    });

                    // 🔥 Sync: Alpine → Livewire (typing)
                    this.$watch('value', (val) => {
                        this.$wire.set('html', val);
                    });
                },

                highlight(html) {
                    if (!html) return '';

                    // Mark <img> tags
                    let marked = html.replace(/(<img[\s\S]*?>)/gi, function(match) {
                        return '__IMG_START__' + match + '__IMG_END__';
                    });

                    // Escape everything
                    let escaped = marked
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');

                    // Restore highlight safely
                    return escaped
                        .replace(/__IMG_START__/g, '<span style="color:#4ade80;">')
                        .replace(/__IMG_END__/g, '</span>');
                }
            }
        }
    </script>
</div>
