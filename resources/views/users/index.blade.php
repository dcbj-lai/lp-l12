<x-layouts.app title="User Management">
    <div class="p-4 md:p-6 bg-white dark:bg-zinc-800 shadow-md rounded-lg">
        <h1 class="text-2xl font-semibold mb-4 text-zinc-800 dark:text-zinc-100">User Management</h1>

        <!-- Search + Tools Row -->
        <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-2">

            <!-- Search Form -->
            <form method="GET" action="{{ route('users.index') }}"
                class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..."
                    class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full md:w-auto" />

                <flux:button type="submit" variant="primary">
                    Search
                </flux:button>

                <flux:button type="button" onclick="window.location='{{ route('users.index') }}'"
                    class="bg-gray-500 text-white hover:bg-gray-600">
                    Reset
                </flux:button>
            </form>

            <!-- ✅ Tools Button (Right Side) -->
            <flux:modal.trigger name="tools-modal">
                <flux:button variant="outline" icon="wrench">
                    Tools
                </flux:button>
            </flux:modal.trigger>

        </div>

        <!-- User Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto border-collapse border border-gray-300 dark:border-zinc-700">
                <thead class="bg-gray-100 dark:bg-zinc-700">
                    <tr class="text-left text-zinc-800 dark:text-zinc-100">
                        <th class="border p-2">Name</th>
                        <th class="border p-2">Email</th>
                        <th class="border p-2">Roles</th>
                        <th class="border p-2 text-center">Payroll On</th>
                        <th class="border p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                            <td class="border p-2">{{ $user->name }}</td>
                            <td class="border p-2">{{ $user->email }}</td>
                            <td class="border p-2">{{ implode(', ', $user->legacy_roles ?? []) }}</td>

                            <!-- Payroll On Checkbox -->
                            <td class="border p-2 text-center">
                                <input type="checkbox" name="payroll_on" id="payroll_on"
                                    {{ $user->payroll_on ? 'checked' : '' }} disabled />
                            </td>

                            <td class="border p-2 text-center">
                                <flux:button href="{{ route('users.edit', $user) }}" size="sm" variant="filled">
                                    Edit
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-4 text-zinc-500">No users found.</td>
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
