<div class="space-y-4 p-4 max-w-md mx-auto sm:max-w-2xl md:max-w-4xl">
    @php
        $user = Auth::user();
        $isPNC = in_array('PNC', $user->roles ?? []);
    @endphp

    <!-- Notification -->
    <x-alert :type="session('notify.type')" :message="session('notify.message')" />
    <!-- Post Form -->
    <!-- New Ripple Form -->
    <form wire:submit.prevent="postRipple" class="p-4 bg-neutral-100 dark:bg-neutral-700 shadow rounded-lg">
        <textarea wire:model="newContent"
            class="w-full border-gray-300 dark:border-gray-700 bg-neutral-50 dark:bg-neutral-600 dark:text-white rounded-lg resize-none"
            placeholder="What's rippling?"></textarea>

        <input type="file" wire:model="file" class="mt-2 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4
            file:rounded-lg file:border-0 file:text-sm file:font-semibold
            file:bg-blue-50 dark:file:bg-gray-700 file:text-blue-700 dark:file:text-blue-400
            hover:file:bg-blue-100 dark:hover:file:bg-gray-600" />

        {{-- <x-button type="submit" class="mt-2">Post Ripple</x-button> --}}
        <flux:button size="sm" variant="primary" type="submit" class="mt-2">Post Ripple</flux:button>
    </form>


    <!-- Ripple Feed -->
    <div class="space-y-4">
        @foreach($ripples->sortByDesc('pinned') as $ripple)
            <div class="p-4 rounded-lg shadow flex items-start gap-2 
                                                                                                                                                                                                                {{ $ripple->pinned ? 'bg-yellow-50 dark:bg-yellow-200' : 'bg-neutral-100 dark:bg-neutral-700' }}"
                style="{{ $ripple->pinned ? 'box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); border: 1px solid #cbd5e1;' : '' }}">


                <!-- Ripple content or editing view -->
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon.ripple class="w-5 h-5 inline stroke-orange-400" />
                            <strong class="text-gray-800 dark:text-white">{{ $ripple->user->name }}</strong>
                        </div>

                        <span class="text-sm text-gray-400 dark:text-gray-400">
                            {{ $ripple->created_at->diffForHumans() }}
                        </span>
                    </div>


                    <!-- Editing Mode -->
                    @if($editingRippleId === $ripple->id)
                        <textarea wire:model="editingContent"
                            class="mt-2 w-full border-gray-300 dark:border-gray-700 bg-neutral-50 dark:bg-yellow-50 dark:text-gray-500 rounded-lg resize-none"></textarea>
                        <!-- File input -->
                        <input type="file" wire:model="editingFile"
                            class="mt-2 w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4
                                                                                                                                                                                                                                                                                                                                                                        file:rounded-lg file:border-0 file:text-sm file:font-semibold
                                                                                                                                                                                                                                                                                                                                                                        file:bg-blue-50 dark:file:bg-gray-700 file:text-blue-700 dark:file:text-blue-400
                                                                                                                                                                                                                                                                                                                                                                        hover:file:bg-blue-100 dark:hover:file:bg-gray-600" />

                        <!-- Save & Cancel buttons -->
                        <div class="flex gap-2 mt-2">
                            <flux:button wire:click="saveRipple" variant="primary" size="sm">
                                <flux:icon.save class="w-4 h-4 inline" /> Save
                            </flux:button>

                            <flux:button wire:click="cancelEdit" size="sm">
                                <flux:icon.x class="w-4 h-4 inline" /> Cancel
                            </flux:button>
                        </div>

                    @else
                        <!-- Regular ripple display -->
                        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $ripple->content }}</p>

                        @if($ripple->file_path)
                            <div class="flex items-center gap-2 mt-1">
                                <a href="{{ Storage::url($ripple->file_path) }}" target="_blank"
                                    class="text-blue-500 dark:text-blue-400 hover:underline flex items-center text-sm">
                                    <flux:icon.paperclip class="w-4 h-4 inline mr-1" />
                                    <span class="truncate max-w-[150px] md:max-w-[300px]"
                                        title="{{ basename($ripple->file_path) }}">
                                        {{ basename($ripple->file_path) }}
                                    </span>
                                </a>
                                @if($ripple->user_id === auth()->id())
                                    <button wire:click="deleteAttachment({{ $ripple->id }})"
                                        class="text-red-500 hover:text-red-600 text-sm font-semibold ml-2">
                                        <flux:icon.circle-x class="w-5 h-5 inline" /> Delete
                                    </button>
                                @endif
                            </div>
                        @endif
                    @endif

                    <!-- Like, Pin, Menu Buttons -->
                    <div class="flex space-x-4 mt-2 text-sm">
                        <button wire:click="toggleLike({{ $ripple->id }})"
                            class="{{ $ripple->isLikedByUser(auth()->id()) ? 'text-blue-500' : 'text-blue-400 hover:text-blue-500' }} transform hover:scale-110 transition-transform duration-150 ease-out">
                            <flux:icon.heart class="w-5 h-5 inline" /> {{ $ripple->likes()->count() }}
                        </button>

                        <!-- Replies Toggle -->
                        <!-- Toggle Reply Button -->
                        <button wire:click="toggleReply({{ $ripple->id }})"
                            class="text-blue-400 hover:text-blue-500 transform hover:scale-110 transition-transform duration-150 ease-out">
                            <flux:icon.reply class="w-4 h-4 inline" /> Reply
                        </button>

                        <!-- Reply Form (Only Shows for Active Ripple) -->
                        @if($replyingTo === $ripple->id)
                            <div class="mt-2 p-2 bg-neutral-200 dark:bg-neutral-800 rounded-lg">
                                <textarea wire:model="replyContent"
                                    class="w-full border-gray-300 dark:border-gray-700 bg-neutral-50 dark:bg-neutral-600 dark:text-white rounded-lg resize-none"
                                    placeholder="Write your reply..."></textarea>

                                <flux:button wire:click="submitReply({{ $ripple->id }})" variant="primary" class="mt-2"
                                    size="sm">
                                    <flux:icon.send class="w-4 h-4 inline mr-2" /> Reply
                                </flux:button>
                            </div>
                        @endif
                        @if($isPNC)
                            <button wire:click="togglePin({{ $ripple->id }})"
                                class="text-blue-400 hover:text-blue-500 transform hover:scale-110 transition-transform duration-150 ease-out">
                                @if($ripple->pinned)
                                    <flux:icon.pin-off class="w-5 h-5 inline" /> Unpin
                                @else
                                    <flux:icon.pin class="w-5 h-5 inline" /> Pin
                                @endif
                            </button>
                        @endif
                        @if($ripple->replies()->count() > 0)
                            <button wire:click="toggleReplies({{ $ripple->id }})"
                                class="text-blue-400 hover:text-blue-500 transform hover:scale-110 transition-transform duration-150 ease-out">
                                {{ $openReplies[$ripple->id] ?? false ? 'Hide' : 'Show' }} Replies
                                ({{ $ripple->replies()->count() }})
                            </button>
                        @endif



                        <!-- Ellipsis Menu -->
                        @if($ripple->user_id === auth()->id())
                            <div x-data="{ open: false }" class="relative ml-auto">
                                <button @click="open = !open" class="text-gray-500 dark:text-gray-400">
                                    <flux:icon.ellipsis-vertical class="w-5 h-5" />
                                </button>

                                <div x-show="open" @click.away="open = false"
                                    class="absolute right-0 mt-2 w-28 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-lg z-50"
                                    x-transition.origin.top.right.duration.200ms x-on:menu-closed.window="open = false">

                                    <button wire:click="editRipple({{ $ripple->id }})"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 w-full text-left">
                                        <flux:icon.pencil class="w-4 h-4 inline mr-2" /> Edit
                                    </button>

                                    <button wire:click="deleteRipple({{ $ripple->id }})"
                                        class="block px-4 py-2 text-sm text-red-500 hover:bg-gray-100 dark:hover:bg-gray-600 w-full text-left">
                                        <flux:icon.circle-x class="w-4 h-4 inline mr-2" /> Delete
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Replies Section -->
            @if($openReplies[$ripple->id] ?? false)
                <div class="mt-2 space-y-2 pl-6 border-l border-gray-300 dark:border-gray-600 text-xs">
                    @foreach($ripple->replies as $reply)
                        <div wire:key="reply-{{ $reply->id }}" class="p-2 bg-neutral-200 dark:bg-neutral-700 rounded-lg relative">

                            <!-- 🧨 Delete Button (Top-Right Corner) -->
                            @if($reply->user_id === auth()->id())
                                <button wire:click="deleteRipple({{ $reply->id }})"
                                    class="absolute top-0 right-0 text-orange-500 hover:text-orange-600 p-1">
                                    <flux:icon.x class="w-3 h-3" />
                                </button>
                            @endif

                            <!-- Reply Content -->
                            <div class="flex items-center justify-between">
                                <strong class="text-gray-700 dark:text-white">{{ $reply->user->name }}</strong>
                                <span
                                    class="mr-2 text-xs text-gray-400 dark:text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $reply->content }}</p>
                            <!-- Like Button for Replies -->
                            <button wire:click="toggleLike({{ $reply->id }})"
                                class="{{ $reply->isLikedByUser(auth()->id()) ? 'text-blue-500' : 'text-blue-400 hover:text-blue-500' }}">
                                <flux:icon.heart class="w-4 h-4 inline" /> {{ $reply->likes()->count() }}
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</div>
