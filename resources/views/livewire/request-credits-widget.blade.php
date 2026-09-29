<div class="space-y-3">
    <div class="inline-block rounded-xl border border-gray-200 p-3 dark:border-gray-700">
        <div class="flex gap-2">
            <div
                class="flex h-16 w-16 flex-col items-center justify-center rounded-lg bg-white text-center shadow dark:bg-gray-800">
                <div class="mb-0.5 text-[10px] text-gray-500 dark:text-gray-400">LEAVE</div>
                <div class="text-sm font-bold leading-tight text-blue-600 dark:text-blue-400">
                    {{ number_format($pto, 1) }}
                </div>
            </div>
            <div
                class="flex h-16 w-16 flex-col items-center justify-center rounded-lg bg-white text-center shadow dark:bg-gray-800">
                <div class="mb-0.5 text-[10px] text-gray-500 dark:text-gray-400">WFH</div>
                <div class="text-sm font-bold leading-tight text-teal-600 dark:text-teal-400">
                    {{ number_format($wfh, 1) }}
                </div>
            </div>
        </div>
    </div>

    <div class="w-full rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-800/60 dark:bg-amber-950/30 sm:w-fit">
        <p class="flex items-start gap-2 text-xs font-bold leading-relaxed text-amber-900 dark:text-white sm:items-center sm:whitespace-nowrap">
            <flux:icon name="information-circle" class="mt-0.5 h-4 w-4 shrink-0 sm:mt-0" />
            Displayed leave credits are based on a full-year tenure (July 1, 2026–June 30, 2027).
        </p>
    </div>
</div>
