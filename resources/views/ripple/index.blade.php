<x-layouts.app Title="Ripple Feed">
    <h1 class="text-xl md:text-2xl font-bold">Life Ripples</h1>
    <subheader>LAIC Team Feed</subheader>
    <livewire:ripple-feed />
    <style>
        .pinned-ripple {
            position: relative;
            padding: 1.5rem;
            color: #1a202c;
            border-radius: 0.75rem;
            background: linear-gradient(45deg, rgba(237, 141, 39, 0.6) 0%, rgba(160, 220, 237, 0.6) 86.52%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid #cbd5e1;
            min-height: 150px;
            transition: all 0.3s ease-in-out;
            overflow: hidden;
        }

        /* Hover effect
        .pinned-ripple:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
            filter: brightness(1.05);
        } */
    </style>
</x-layouts.app>
