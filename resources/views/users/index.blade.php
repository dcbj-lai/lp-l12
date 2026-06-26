<x-layouts.app title="User Management">
    <div class="p-4 md:p-6 bg-white dark:bg-zinc-800 shadow-md rounded-lg">
        <h1 class="text-2xl font-semibold mb-4 text-zinc-800 dark:text-zinc-100">User Management</h1>

        <!-- Search + Tools Row -->
        <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-2">

            <!-- Search Form -->
            <form method="GET" action="{{ route('users.index') }}"
                class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users or employee number..."
                    class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-auto" />

                <select name="status"
                    class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-auto">
                    <option value="active" @selected(($status ?? request('status', 'active')) === 'active')>Active only</option>
                    <option value="all" @selected(($status ?? request('status', 'active')) === 'all')>All users</option>
                    <option value="inactive" @selected(($status ?? request('status', 'active')) === 'inactive')>Inactive only</option>
                </select>

                <flux:button type="submit" variant="primary">
                    Search
                </flux:button>

                <flux:button type="button" onclick="window.location='{{ route('users.index') }}'"
                    class="bg-gray-500 text-white hover:bg-gray-600">
                    Reset
                </flux:button>
            </form>

            <!-- ✅ Tools Button (Right Side) -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <flux:button href="{{ route('users.export.csv', request()->only(['search', 'status'])) }}" variant="outline"
                    icon="arrow-down-tray">
                    Export CSV
                </flux:button>

                <flux:button href="{{ route('users.export.pdf', request()->only(['search', 'status'])) }}" variant="outline"
                    icon="document-text">
                    Export PDF
                </flux:button>

                <flux:modal.trigger name="tools-modal">
                    <flux:button variant="outline" icon="wrench">
                        Tools
                    </flux:button>
                </flux:modal.trigger>
            </div>

        </div>

        <!-- User Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto border-collapse border border-gray-300 dark:border-zinc-700">
                <thead class="bg-gray-100 dark:bg-zinc-700">
                    <tr class="text-left text-zinc-800 dark:text-zinc-100">
                        <th class="border p-2">Employee #</th>
                        <th class="border p-2">Name</th>
                        <th class="border p-2">Email</th>
                        <th class="border p-2">Preferred Name</th>
                        <th class="border p-2 text-center">Status</th>
                        <th class="border p-2">Vcard URL</th>
                        <th class="border p-2 text-center">Payroll On</th>
                        <th class="border p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="hover:bg-zinc-100 dark:hover:bg-zinc-700 {{ $user->is_active ? 'text-zinc-700 dark:text-zinc-300' : 'bg-zinc-50 text-zinc-400 dark:bg-zinc-900/40 dark:text-zinc-500' }}">
                            <td class="border p-2">{{ $user->employee_number ?? '-' }}</td>
                            <td class="border p-2">{{ $user->name }}</td>
                            <td class="border p-2">{{ $user->email }}</td>
                            <td class="border p-2">{{ $user->preferred_name ?? 'N/A' }}</td>
                            <td class="border p-2 text-center">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <a href="{{ $user->cardUrl() }}" target="_blank" rel="noopener"
                                    class="break-all text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $user->cardUrl() }}
                                </a>
                            </td>

                            <!-- Payroll On Checkbox -->
                            <td class="border p-2 text-center">
                                <input type="checkbox" name="payroll_on" id="payroll_on"
                                    {{ $user->payroll_on ? 'checked' : '' }} disabled />
                            </td>

                            <td class="border p-2 text-center">
                                <flux:button href="{{ route('users.edit', $user) }}" size="sm" variant="primary"
                                    color="lime" icon="eye">
                                    View
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-4 text-zinc-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
    <!-- ✅ Tools Modal -->
    <flux:modal name="tools-modal" class="w-full max-w-2xl">

        <div class="space-y-6">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Tools</flux:heading>
                <flux:text class="mt-2">
                    Utilities for bulk actions and administrative tasks.
                </flux:text>
            </div>

            <!-- 🔧 Tool: Update Employee Dates -->
            <div class="border rounded-lg p-4 space-y-4 bg-zinc-50 dark:bg-zinc-900">

                <div class="flex items-center gap-2">
                    <flux:icon name="calendar-days" class="w-5 h-5 text-blue-500" />
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">
                        Update Employee Dates
                    </h3>
                </div>

                <p class="text-xs text-gray-500">
                    Upload a CSV file to update employee birthdates and hire dates.
                </p>

                <!-- ✅ Embed existing Livewire tool -->
                <livewire:users-csv-upload />

            </div>

        </div>

    </flux:modal>
</x-layouts.app>
