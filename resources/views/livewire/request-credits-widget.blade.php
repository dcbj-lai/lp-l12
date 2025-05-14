<div class="inline-block border border-gray-200 dark:border-gray-700 rounded-xl p-3">
    <div class="flex gap-2">
        <div
            class="w-16 h-16 bg-white dark:bg-gray-800 rounded-lg shadow text-center flex flex-col items-center justify-center">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">PTO</div>
            <div class="text-sm font-bold text-blue-600 dark:text-blue-400 leading-tight">{{ number_format($pto, 1) }}
            </div>
        </div>
        <div
            class="w-16 h-16 bg-white dark:bg-gray-800 rounded-lg shadow text-center flex flex-col items-center justify-center">
            <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">WFH</div>
            <div class="text-sm font-bold text-teal-600 dark:text-teal-400 leading-tight">{{ number_format($wfh, 1) }}
            </div>
        </div>
    </div>
</div>
