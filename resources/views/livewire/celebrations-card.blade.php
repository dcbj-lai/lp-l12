<x-dashboard-card variant="danger" class="p-4">

    <div class="relative flex flex-col aspect-square">

        <!-- Title -->
        <div class="absolute top-4 left-4">
            <h2 class="text-xs font-medium text-gray-400 flex items-center gap-2 tracking-wide">
                <flux:icon name="calendar-days" class="w-4 h-4" />
                Celebrations ({{ now()->format('F') }})
            </h2>
        </div>

        <!-- Content -->
        <div class="flex flex-col flex-grow overflow-y-auto h-[calc(100%-2.5rem)] px-4 mt-10 pr-1">

            <!-- Birthdays -->
            <div class="space-y-2">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <flux:icon name="cake" class="w-4 h-4 text-pink-500" />
                    Birthdays
                </h3>

                @if ($birthdays->isEmpty())
                    <p class="text-xs text-gray-400 ml-6">
                        No birthdays this month.
                    </p>
                @else
                    <ul class="space-y-1 ml-6">
                        @foreach ($birthdays as $user)
                            <li class="flex justify-between items-center text-sm">
                                <span class="truncate text-gray-700 dark:text-gray-200">
                                    {{ $user->name }}
                                </span>
                                <span class="text-gray-400 text-xs">
                                    {{ $user->birthdate->format('M d') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Anniversaries -->
            <div class="mt-4 pt-4 border-t border-gray-200/60 dark:border-zinc-700/60 space-y-2">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <flux:icon name="briefcase" class="w-4 h-4 text-blue-500" />
                    Work Anniversaries
                </h3>

                @if ($anniversaries->isEmpty())
                    <p class="text-xs text-gray-400 ml-6">
                        No anniversaries this month.
                    </p>
                @else
                    <ul class="space-y-1 ml-6">
                        @foreach ($anniversaries as $user)
                            <li class="flex justify-between items-center text-sm">
                                <span class="truncate text-gray-700 dark:text-gray-200">
                                    {{ $user->name }}
                                </span>
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
