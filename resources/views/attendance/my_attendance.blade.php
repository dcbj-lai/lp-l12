<x-layouts.app title="My Attendance">
    <!-- Attendance Table -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-xl md:text-2xl font-bold">My Attendance</h1>
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex flex-wrap justify-between items-center">
                <!-- Buttons and Clock -->
                <div class="w-full md:w-auto flex flex-wrap items-center justify-center md:justify-start">
                    <div x-data="{
                            checkedIn: {{ $hasCheckedIn ? 'true' : 'false' }},
                            checkedOut: {{ $hasCheckedOut ? 'true' : 'false' }},
                            checkInTime: {{ $hasCheckedIn && !$hasCheckedOut ? "'" . $lastCheckIn . "'" : 'null' }},
                            elapsed: '00:00:00',
                            interval: null,
                            startTimer() {
                                if (!this.checkInTime || this.checkedOut) return;
                                let checkInTimestamp = new Date(this.checkInTime).getTime();
                                
                                this.interval = setInterval(() => {
                                    let now = new Date().getTime();
                                    let diff = now - checkInTimestamp;
                    
                                    let hours = Math.floor(diff / (1000 * 60 * 60));
                                    let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                    let seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                                    this.elapsed = 
                                        String(hours).padStart(2, '0') + ':' +
                                        String(minutes).padStart(2, '0') + ':' +
                                        String(seconds).padStart(2, '0');
                    
                                }, 1000);
                            },
                            stopTimer() {
                                if (this.interval) {
                                    clearInterval(this.interval);
                                    this.elapsed = '00:00:00';
                                }
                            }
                        }" x-init="if (checkedIn && !checkedOut) startTimer(); else stopTimer();"
                        class="flex flex-wrap items-center justify-center md:justify-start gap-4">
                        {{-- {{ dd($hasCheckedIn) }} --}}
                        <flux:button @click="$dispatch('open-modal', 'checkInModal')"
                            x-bind:disabled="checkedIn || hasCheckedIn"
                            class="w-full md:w-auto text-center disabled:cursor-not-allowed" variant="primary">
                            Check In
                        </flux:button>

                        <flux:button @click="$dispatch('open-modal', 'checkOutModal')"
                            x-bind:disabled="checkedOut || !checkedIn"
                            class="w-full md:w-auto text-center disabled:cursor-not-allowed">
                            Check Out
                        </flux:button>

                        <div x-show="checkedIn && !checkedOut"
                            class="text-md font-extralight text-slate-500 dark:text-gray-100">
                            Hours today: <span x-text="elapsed"></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Responsive Table -->
            <div x-data @check-in.window="location.reload()" @check-out.window="location.reload()">
                <div class="overflow-x-auto">
                    <table
                        class="w-full min-w-max border-collapse border border-gray-200 dark:border-gray-700 text-sm mt-4">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-200">
                                <th class="border  px-4 py-2 text-left">Date</th>
                                <th class="border  px-4 py-2 text-left">Check-In
                                </th>
                                <th class="border  px-4 py-2 text-left">Check-Out
                                </th>
                                <th class="border  px-4 py-2 text-left">Status</th>
                                <th class="border  px-4 py-2 text-left">Remarks</th>
                                <th class="border  px-4 py-2 text-left">Hours Worked
                                </th>
                                <th class="border  px-4 py-2 text-left">Created At
                                </th>
                                <th class="border  px-4 py-2 text-left">Updated At
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800 dark:text-gray-300">
                            @forelse ($attendances as $attendance)
                                @php
                                    $remarkColor = match ($attendance->remarks) {
                                        'Early check-in' => 'text-blue-500 dark:text-blue-400 font-semibold',
                                        'Late' => 'text-red-500 dark:text-red-400 font-semibold',
                                        'On Time' => 'text-green-500 dark:text-green-400 font-semibold',
                                        'Absent' => 'text-gray-500 dark:text-gray-400 font-semibold',
                                        'Undertime' => 'text-yellow-500 dark:text-yellow-400 font-semibold',
                                        default => 'text-black dark:text-white'
                                    };
                                    $officialTimeIn = new DateTime(config('app.official_time_in'));
                                    $checkInTime = new DateTime($attendance->check_in);
                                    $remarkCheckin = $attendance->check_in
                                        ? ($checkInTime->format('H:i:s') <= $officialTimeIn->format('H:i:s')
                                            ? 'text-green-500 dark:text-green-400 font-semibold'
                                            : 'text-red-500 dark:text-red-400 font-semibold')
                                        : 'text-black dark:text-white font-semibold';
                                @endphp

                                <tr class="border-b">
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->date }}
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        <span
                                            class="{{$remarkCheckin}}">{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->timezone('Asia/Manila')->format('h:i A') : '—' }}
                                        </span>
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->timezone('Asia/Manila')->format('h:i A') : '—' }}
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->status }}
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->remarks ?? '—' }}
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->hours_worked ? number_format($attendance->hours_worked, 2) . ' hrs' : '—' }}
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->created_at->timezone('Asia/Manila')->format('Y-m-d h:i A') }}
                                    </td>
                                    <td class="border  px-4 py-2 whitespace-nowrap">
                                        {{ $attendance->updated_at->timezone('Asia/Manila')->format('Y-m-d h:i A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center px-4 py-6 text-gray-500 dark:text-gray-400">
                                        No attendance records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $attendances->links() }}
            </div>
        </div>
    </div>

    <!-- Check-in Confirmation Modal -->
    <x-modal name="checkInModal">
        <div class="p-6 bg-blue-50 dark:bg-neutral-700 dark:text-gray-200 rounded-lg">
            <h2 class="text-lg font-semibold mb-4">Check-in Confirmation</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Please confirm check-in.</p>

            <div class="flex flex-col md:flex-row justify-center gap-3">
                <flux:button @click="$dispatch('close-modal', 'checkInModal')" class="w-full md:w-auto">Cancel
                </flux:button>

                <form method="POST" action="{{ route('attendance.check_in') }}">
                    @csrf
                    {{-- <x-primary-button type="submit" class="w-full md:w-auto">
                        Confirm
                    </x-primary-button> --}}
                    <flux:button type="submit" class="w-full md:w-auto" variant="primary">Confirm
                    </flux:button>
                </form>
            </div>
        </div>
    </x-modal>

    <!-- Check-out Confirmation Modal -->
    <x-modal name="checkOutModal">
        <div class="p-6 bg-blue-50 dark:bg-neutral-700 dark:text-gray-200 rounded-lg">
            <h2 class="text-lg font-semibold mb-4">Check-out Confirmation</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Please confirm check-out.</p>

            <div class="flex flex-col md:flex-row justify-center gap-3">
                <x-secondary-button @click="$dispatch('close-modal', 'checkOutModal')" class="w-full md:w-auto">
                    Cancel
                </x-secondary-button>

                <form method="POST" action="{{ route('attendance.check_out') }}" @submit="stopTimer()">
                    @csrf
                    <x-primary-button type="submit" class="w-full md:w-auto">
                        Confirm
                    </x-primary-button>
                </form>
            </div>
        </div>
    </x-modal>

</x-layouts.app>
