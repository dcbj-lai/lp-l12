@props([
    'type' => 'success',
    'message' => null,
])

@php
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️',
    ];

    $borderColors = [
        'success' => 'border-green-500 text-green-700 dark:border-green-400 dark:text-green-300',
        'error' => 'border-red-500 text-red-700 dark:border-red-400 dark:text-red-300',
        'warning' => 'border-yellow-500 text-yellow-700 dark:border-yellow-400 dark:text-yellow-300',
        'info' => 'border-blue-500 text-blue-700 dark:border-blue-400 dark:text-blue-300',
        'default' => 'border-gray-500 text-gray-700 dark:border-gray-400 dark:text-gray-300',
    ];

    $bgColors = [
        'success' => 'bg-green-100 dark:bg-green-900',
        'error' => 'bg-red-100 dark:bg-red-900',
        'warning' => 'bg-yellow-100 dark:bg-yellow-900',
        'info' => 'bg-blue-100 dark:bg-blue-900',
        'default' => 'bg-gray-100 dark:bg-gray-900',
    ];
@endphp

<div x-data="{
    show: false,
    message: '',
    type: 'success',
    icons: @js($icons),
    borderColors: @js($borderColors),
    bgColors: @js($bgColors),

    get styles() {
        return (this.borderColors[this.type] ?? this.borderColors.default) + ' ' +
            (this.bgColors[this.type] ?? this.bgColors.default);
    },

    trigger(msg, typ = 'success') {
        this.message = msg;
        this.type = typ;
        this.show = true;

        setTimeout(() => this.show = false, 5000);
    }
}" {{-- ✅ Session flash (on page load) --}} x-init="const initialMessage = @js($message);
const initialType = @js($type);

if (initialMessage) {
    trigger(initialMessage, initialType);
}" {{-- ✅ Livewire events --}}
    x-on:flash.window="
        trigger($event.detail.message, $event.detail.type);
    " x-show="show"
    x-transition:enter="transform transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-10"
    x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transform transition ease-in duration-500"
    x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-10"
    class="fixed top-5 right-5 z-50 flex items-center space-x-3 px-5 py-3 rounded-lg border-l-4 shadow-lg"
    :class="styles">
    <span x-text="icons[type] ?? 'ℹ️'"></span>

    <span class="text-sm flex-1" x-text="message"></span>

    <button @click="show = false"
        class="text-gray-500 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 text-lg font-bold">
        &times;
    </button>
</div>
