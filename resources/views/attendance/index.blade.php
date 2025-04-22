<x-layouts.app title="Admin Attendance">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-xl md:text-2xl font-bold">Manage Attendance</h1>
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6" x-data="attendanceData()">
            <div class="flex flex-wrap items-center gap-4 mb-4">
                <div class="flex flex-wrap gap-4 flex-grow">
                    <input type="date"
                        class="border rounded p-2 bg-white dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                        x-model="filterDate">

                    <select
                        class="border rounded p-2 bg-white dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                        x-model="filterUser">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                        @endforeach
                    </select>

                    <select
                        class="border rounded p-2 bg-white dark:bg-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600"
                        x-model="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="On Time">On Time</option>
                        <option value="Late">Late</option>
                        <option value="Absent">Absent</option>
                        <option value="Present">Present</option>
                    </select>

                    <flux:button @click="resetFilters" variant="filled" class="uppercase">Reset</flux:button>
                </div>

                <!-- Download icon pushed to the far right -->
                <div class="ml-auto">
                    <flux:icon name="download" class="w-6 h-6 cursor-pointer text-gray-700 dark:text-gray-300"
                        @click="downloadCSV" />
                </div>
            </div>


            <!-- Attendance Table -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-max border-collapse border border-gray-200 dark:border-gray-700 text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-200">
                            <th class="border px-4 py-2 text-left">Employee</th>
                            <th class="border px-4 py-2 text-left">Date</th>
                            <th class="border px-4 py-2 text-left">Check-In</th>
                            <th class="border px-4 py-2 text-left">Check-Out</th>
                            <th class="border px-4 py-2 text-left">Status</th>
                            <th class="border px-4 py-2 text-left">Remarks</th>
                            <th class="border px-4 py-2 text-left">Hours Worked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="attendance in paginatedAttendances()" :key="attendance.id">
                            <tr class="border-b">
                                <td class="border px-4 py-2" x-text="attendance.user.name"></td>
                                <td class="border px-4 py-2" x-text="attendance.date"></td>
                                <td class="border px-4 py-2" x-text="attendance.check_in"></td>
                                <td class="border px-4 py-2" x-text="attendance.check_out"></td>
                    
                                <!-- Status with color coding -->
                                <td class="border px-4 py-2 text-center"
                                    :class="{
                                        'text-green-600 font-semibold': attendance.status === 'On Time',
                                        'text-yellow-500 font-semibold': attendance.status === 'Late',
                                        'text-red-600 font-semibold': attendance.status === 'Absent',
                                        'text-blue-600 font-semibold': attendance.status === 'Present'
                                    }" 
                                    x-text="attendance.status">
                                </td>
                    
                                <!-- Remarks with color coding -->
                                <td class="border px-4 py-2"
                                    :class="{
                                        'text-gray-600 italic': attendance.remarks === 'No Remarks',
                                        'text-blue-500 font-medium': attendance.remarks.includes('Approved'),
                                        'text-red-500 font-medium': attendance.remarks.includes('Pending')
                                    }" 
                                    x-text="attendance.remarks">
                                </td>
                    
                                <td class="border px-4 py-2" x-text="attendance.hours_worked"></td>
                            </tr>
                        </template>
                        <template x-if="filteredAttendances().length === 0">
                            <tr>
                                <td colspan="7" class="text-center text-gray-500 py-4 italic">
                                    No attendance records found.
                                </td>
                            </tr>
                        </template>
                        
                    </tbody>
                    
                </table>
            </div>

            <!-- Pagination Controls -->
            <!-- Pagination Controls -->
            <!-- Pagination Controls -->
            <div class="flex justify-between items-center mt-4 text-sm">
                <flux:button @click="prevPage" x-bind:disabled="page === 1">
                    Previous
                </flux:button>

                <span class="text-gray-700 dark:text-gray-300">
                    Page <span x-text="page"></span> of <span x-text="totalPages()"></span>
                </span>

                <flux:button @click="nextPage" x-bind:disabled="page >= totalPages()">
                    Next
                </flux:button>
            </div>


        </div>
    </div>

    <script>
        function attendanceData() {
            return {
                filterDate: '',
                filterUser: '',
                filterStatus: '',
                users: @json($users),
                attendances: @json($attendances),
                page: 1,
                perPage: 10,

                init() {
                    this.$watch('filterDate', () => this.page = 1);
                    this.$watch('filterUser', () => this.page = 1);
                    this.$watch('filterStatus', () => this.page = 1);
                },

                filteredAttendances() {
                    return this.attendances.filter(attendance => {
                        return (!this.filterDate || attendance.date === this.filterDate) &&
                            (!this.filterUser || attendance.user.id == this.filterUser) &&
                            (!this.filterStatus || attendance.status === this.filterStatus);
                    });
                },

                paginatedAttendances() {
                    let start = (this.page - 1) * this.perPage;
                    return this.filteredAttendances().slice(start, start + this.perPage);
                },

                totalPages() {
                    return Math.ceil(this.filteredAttendances().length / this.perPage);
                },

                nextPage() {
                    if (this.page < this.totalPages()) {
                        this.page++;
                    }
                },

                prevPage() {
                    if (this.page > 1) {
                        this.page--;
                    }
                },

                resetFilters() {
                    this.filterDate = '';
                    this.filterUser = '';
                    this.filterStatus = '';
                    this.page = 1;
                },

                downloadCSV() {
                    let csvContent = "data:text/csv;charset=utf-8,";
                    let headers = ["Employee", "Date", "Check-In", "Check-Out", "Status", "Remarks", "Hours Worked"];
                    csvContent += headers.join(",") + "\n";

                    this.filteredAttendances().forEach(attendance => {
                        let row = [
                            `"${attendance.user.name}"`,
                            `"${attendance.date}"`,
                            `"${attendance.check_in}"`,
                            `"${attendance.check_out}"`,
                            `"${attendance.status}"`,
                            `"${attendance.remarks}"`,
                            `"${attendance.hours_worked}"`
                        ];
                        csvContent += row.join(",") + "\n";
                    });

                    let encodedUri = encodeURI(csvContent);
                    let link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "attendance_report.csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            };
        }


    </script>
</x-layouts.app>
