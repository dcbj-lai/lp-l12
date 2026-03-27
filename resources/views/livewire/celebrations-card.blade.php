<x-dashboard-card class="p-4">

    <div class="relative flex flex-col aspect-square">

        <!-- Title -->
        <div class="absolute top-4 left-4">
            <h2 class="text-sm font-semibold text-gray-400 flex items-center gap-1">
                <flux:icon name="calendar-days" class="w-4 h-4" />
                Celebrations ({{ now()->format('F') }})
            </h2>
        </div>

        <!-- Scrollable Content -->
        <div class="flex flex-col flex-grow overflow-y-auto h-[calc(100%-2.5rem)] px-4 text-sm space-y-4 mt-10 pr-1">

            <!-- Birthdays -->
            <div>
                <h3
                    class="font-semibold text-gray-600 dark:text-gray-300 mb-2 flex items-center gap-1 sticky top-0 bg-inherit z-10">
                    <flux:icon name="cake" class="w-4 h-4 text-pink-500" />
                    Birthdays
                </h3>

                @if ($birthdays->isEmpty())
                    <p class="text-xs text-gray-500">No birthdays this month.</p>
                @else
                    <ul class="space-y-1">
                        @foreach ($birthdays as $user)
                            <li class="flex justify-between items-center">
                                <span class="truncate">{{ $user->name }}</span>
                                <span class="text-gray-400 text-xs">
                                    {{ $user->birthdate->format('M d') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Anniversaries -->
            <div>
                <h3
                    class="font-semibold text-gray-600 dark:text-gray-300 mb-2 flex items-center gap-1 sticky top-0 bg-inherit z-10">
                    <flux:icon name="briefcase" class="w-4 h-4 text-blue-500" />
                    Work Anniversaries
                </h3>

                @if ($anniversaries->isEmpty())
                    <p class="text-xs text-gray-500">No anniversaries this month.</p>
                @else
                    <ul class="space-y-1">
                        @foreach ($anniversaries as $user)
                            <li class="flex justify-between items-center">
                                <span class="truncate">{{ $user->name }}</span>
                                <span class="text-gray-400 text-xs">
                                    {{ $user->hire_date->format('M d') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>

    </div>

</x-dashboard-card>
