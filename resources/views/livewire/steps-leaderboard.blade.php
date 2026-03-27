<x-dashboard-card variant="danger" class="p-4">

    @if ($mode === 'card')

        <div class="relative flex flex-col aspect-square">

            <!-- Title -->
            <div class="absolute top-4 left-4">
                <h2 class="text-sm font-semibold text-gray-400 flex items-center gap-1">
                    <flux:icon.footprints class="w-4 h-4" />
                    Top Steppers ({{ \Carbon\Carbon::parse($startDate)->format('F') }})
                </h2>
            </div>

            <!-- Content -->
            <div class="flex flex-col justify-center items-center flex-grow px-4 text-center">

                @if ($leaders->isEmpty())
                    <p class="text-gray-600 dark:text-gray-300 text-xs">
                        No steps logged this month yet.
                    </p>
                @else
                    <ul class="space-y-3 w-full max-w-xs text-sm">

                        @foreach ($leaders as $index => $entry)
                            <li class="flex justify-between items-center">

                                <span class="text-gray-400 font-medium tracking-wide">
                                    {{ $index + 1 }}. {{ $entry->user->name }}
                                </span>

                                <span class="text-blue-600 font-semibold dark:text-blue-400">
                                    {{ number_format($entry->total_steps) }}
                                </span>

                                <span>
                                    @if ($index === 0)
                                        <flux:icon name="trophy" class="text-yellow-500 h-4 w-4" />
                                    @elseif ($index === 1)
                                        <flux:icon name="medal" class="text-gray-400 h-4 w-4" />
                                    @elseif ($index === 2)
                                        <flux:icon name="award" class="text-orange-500 h-4 w-4" />
                                    @endif
                                </span>

                            </li>
                        @endforeach

                    </ul>
                @endif

            </div>

            <!-- Footer -->
            <div class="absolute bottom-4 right-4">
                <flux:button size="xs" variant="ghost" href="{{ route('steps.index') }}">
                    See all...
                </flux:button>
            </div>

        </div>

    @endif

</x-dashboard-card>
