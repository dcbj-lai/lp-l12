<x-layouts.app title="Edit User">
    <div class="p-4 md:p-6 bg-white dark:bg-zinc-800 shadow-md rounded-lg">
        <h1 class="text-2xl font-semibold mb-4 text-zinc-800 dark:text-zinc-100">User Info</h1>

        <!-- Back Button -->
        <flux:button href="{{ route('users.index') }}" size="sm" variant="outline" icon="arrow-left">
            Back to Users
        </flux:button>

        <!-- Edit Form -->
        <form method="POST" action="{{ route('users.update', $user) }}"
            class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <!-- Left Column: Basic User Info -->
            <div class="space-y-4 pr-4 border-r border-zinc-300 dark:border-zinc-700">
                <!-- Name -->
                <div>
                    <label for="name"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                </div>

                <!-- Email -->
                <div>
                    <label for="email"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                        required class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                </div>
                <!-- Birthdate -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Preferred Name -->
                    <div>
                        <label for="preferred_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Preferred Name
                        </label>
                        <input type="text" id="preferred_name" name="preferred_name"
                            value="{{ old('preferred_name', $user->preferred_name) }}"
                            class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full"
                            placeholder="Nickname / display name" />
                    </div>

                    <!-- Birthdate -->
                    <div>
                        <label for="birthdate" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Birthdate
                        </label>
                        <input type="date" id="birthdate" name="birthdate"
                            value="{{ old('birthdate', optional($user->birthdate)->format('Y-m-d')) }}"
                            class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                    </div>

                </div>

                <!-- Hire Date -->
                <div>
                    <label for="hire_date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Hire Date
                    </label>
                    <input type="date" id="hire_date" name="hire_date"
                        value="{{ old('hire_date', optional($user->hire_date)->format('Y-m-d')) }}"
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                </div>

                <!-- Supervisor Dropdown -->
                <div>
                    <label for="supervisor_id"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Supervisor</label>
                    <select id="supervisor_id" name="supervisor_id"
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
                        <option value="" {{ $user->supervisor_id ? '' : 'selected' }}>No Supervisor</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}"
                                {{ $user->supervisor_id == $supervisor->id ? 'selected' : '' }}>
                                {{ $supervisor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Department -->
                <div>
                    <label for="department_id"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Department</label>
                    <select name="department_id" id="department_id"
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
                        <option value="">Select Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}"
                                {{ $user->department_id == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Check-in Mode -->
                <div>
                    <label for="check_in_mode"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Check-in Mode</label>
                    <select name="check_in_mode" id="check_in_mode"
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
                        <option value="virtual"
                            {{ old('check_in_mode', $user->check_in_mode) === 'virtual' ? 'selected' : '' }}>Virtual
                        </option>
                        <option value="onsite"
                            {{ old('check_in_mode', $user->check_in_mode) === 'onsite' ? 'selected' : '' }}>Onsite
                        </option>
                    </select>
                </div>

            </div>

            <!-- Right Column: Roles, Payroll, and Rank -->
            <div class="space-y-4 pl-4">
                <!-- Roles -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Roles</label>

                    <!-- Existing Roles as Dismissible Banners -->
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach ($user->legacy_roles ?? [] as $role)
                            <div
                                class="bg-zinc-200 dark:bg-zinc-600 text-zinc-800 dark:text-zinc-200 px-2 py-1 flex items-center gap-2 rounded-md text-xs">
                                <span>{{ $role }}</span>
                                @if ($role !== 'user')
                                    <button type="button" onclick="removeRole(this, '{{ $role }}')">
                                        <flux:icon.x class="size-4 stroke-amber-600 hover:stroke-amber-400 inline" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Add New Role Dropdown -->
                    <div class="flex gap-2">
                        <select id="new-role"
                            class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white text-md w-full">
                            <option value="" disabled selected>Add role...</option>
                            <option value="finance.staff">Finance Staff</option>
                            <option value="finance.admin">Finance Admin</option>
                            <option value="pnc.staff">P&C Staff</option>
                            <option value="pnc.admin">P&C Admin</option>
                            <option value="sys.admin">System Admin</option>
                            <option value="frontdesk.staff">Front Desk</option>
                            <option value="acad.admin">Acad Admin</option>
                            <option value="guidance.admin">Guidance Admin</option>
                            <option value="comms.admin">Communications Admin</option>
                            <option value="clinic.admin">Clinic Admin</option>
                        </select>
                        <flux:button type="button" size="sm" variant="primary" onclick="addRole()">Add Role
                        </flux:button>
                    </div>

                    <!-- Hidden Roles Input -->
                    <input type="hidden" name="roles" id="roles"
                        value="{{ json_encode($user->legacy_roles ?? []) }}" />
                </div>

                <!-- Title -->
                <div>
                    <label for="position"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Position</label>
                    <input type="text" id="position" name="position"
                        value="{{ old('position', $user->position) }}"
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                </div>

                <!-- Rank Input -->
                <div>
                    <label for="rank"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Rank</label>
                    <select id="rank" name="rank" required
                        class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
                        <option value="employee" {{ old('rank', $user->rank) === 'employee' ? 'selected' : '' }}>
                            Employee</option>
                        <option value="manager" {{ old('rank', $user->rank) === 'manager' ? 'selected' : '' }}>Manager
                        </option>
                    </select>
                </div>
                <!-- Phone Numbers -->
                <div class="pt-4 border-t border-zinc-300 dark:border-zinc-700 space-y-4">

                    <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Contact Information
                    </div>

                    <!-- Work Phone -->
                    <div>
                        <label for="phone_work" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Work Phone
                        </label>
                        <input type="text" id="phone_work" name="phone_work"
                            value="{{ old('phone_work', $user->phone_work) }}" placeholder="+63281234567"
                            class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                    </div>

                    <!-- Mobile Phone -->
                    <div>
                        <label for="phone_mobile" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Mobile Phone
                        </label>
                        <input type="text" id="phone_mobile" name="phone_mobile"
                            value="{{ old('phone_mobile', $user->phone_mobile) }}" placeholder="+639171234567"
                            class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                    </div>
                    <!-- Address -->
                    <div class="pt-4 border-t border-zinc-300 dark:border-zinc-700">
                        <label for="address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Address
                        </label>
                        <input type="text" id="address" name="address"
                            value="{{ old('address', $user->address) }}" placeholder="Street, City, Province"
                            class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                    </div>

                </div>

                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Conditional Monthly Rate (Only for Finance Users) -->
                    @if (auth()->user()->isFinanceAdmin())
                        <div class="flex-1">
                            <label for="monthly_rate"
                                class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Monthly Rate
                            </label>
                            <input type="text" id="monthly_rate" name="monthly_rate"
                                value="{{ number_format($user->monthly_rate, 2) ?? '' }}"
                                class="w-full p-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-sm"
                                placeholder="₱0.00" />
                        </div>


                        <!-- Payroll On Checkbox -->
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="payroll_on" name="payroll_on" value="1"
                                {{ old('payroll_on', $user->payroll_on) ? 'checked' : '' }}
                                class="rounded border-zinc-300 dark:bg-zinc-700 dark:text-white" />
                            <label for="payroll_on" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Include in Payroll
                            </label>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Full Width Submit Button -->
            <div class="col-span-1 md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary" size="sm">Save Changes</flux:button>
            </div>
        </form>





        {{-- Finance only area for pay adjustments --}}
        @if (auth()->user()->isFinanceAdmin())
            <!-- Adjustments Section -->
            <div class="mt-8 p-4 border-t border-zinc-300 dark:border-zinc-600">
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-2">Pay Scheme</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Left: Add New Adjustment Form -->
                    <div class="bg-zinc-100 dark:bg-zinc-800 p-4 rounded-md shadow-md">
                        <h3 class="text-md font-semibold text-zinc-800 dark:text-zinc-100 mb-2">Add New Adjustment</h3>

                        <form method="POST" action="{{ route('adjustments.store', $user->id) }}" class="space-y-2">
                            @csrf

                            <!-- Hidden User ID -->
                            <input type="hidden" name="user_id" value="{{ $user->id }}">

                            <!-- Hidden Updated By -->
                            <input type="hidden" name="updated_by" value="{{ auth()->id() }}">

                            <!-- Mode (Add/Subtract) -->
                            <label for="mode"
                                class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Mode</label>
                            <select id="mode" name="mode" required
                                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full">
                                <option value="add">Add</option>
                                <option value="subtract">Subtract</option>
                            </select>

                            <!-- Description -->
                            <label for="description"
                                class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                            <input type="text" id="description" name="description" required
                                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />

                            <!-- Amount -->
                            <label for="amount"
                                class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Amount</label>
                            <input type="number" id="amount" name="amount" step="0.01" required
                                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />

                            <!-- Cycle -->
                            <label for="cycle"
                                class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Cycle</label>
                            <input type="number" id="cycle" name="cycle" min="1" required
                                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full" />
                            <!-- Effective Date -->
                            <label for="effective_date"
                                class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Effective Date (12-31-9999 for recurring)
                            </label>
                            <input type="date" id="effective_date" name="effective_date"
                                value="{{ old('effective_date', '9999-12-31') }}"
                                class="border px-4 py-2 rounded-md dark:bg-zinc-700 dark:text-white w-full"
                                min="{{ now()->format('Y-m-d') }}" />


                            <!-- Submit Button -->
                            <flux:button size="sm" type="submit" variant="primary" class="mt-4">
                                Add Adjustment
                            </flux:button>
                        </form>
                    </div>


                    <!-- Right: Existing Adjustments (Scrollable List) -->
                    <div class="p-4 border-zinc-300 dark:border-zinc-600">
                        <h2 class="text-md font-semibold text-zinc-800 dark:text-zinc-100 mb-2">Current Adjustments
                        </h2>

                        <!-- Add a Package Dropdown -->
                        <div class="mb-4">
                            <form action="{{ route('adjustments.package') }}" method="POST"
                                onsubmit="return confirmClearAll(this)" class="mb-4">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">

                                <label for="package"
                                    class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Add Pay Package
                                    <span
                                        class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full ml-2 
                                                                            @if ($user->package === 'L1') bg-blue-100 text-blue-600
                                                                            @elseif($user->package === 'L2') bg-green-100 text-green-600
                                                                            @elseif($user->package === 'ManCom') bg-purple-100 text-purple-600
                                                                                @else bg-gray-100 text-gray-600 @endif">
                                        {{ $user->package ?? 'N/A' }}
                                    </span>
                                </label>

                                <select id="package" name="package" required
                                    class="w-full p-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                    <option value="" disabled selected>Select a package</option>
                                    <option value="L1">L1</option>
                                    <option value="L2">L2</option>
                                    <option value="ManCom">ManCom</option>
                                    <option value="ClearAll" class="text-red-600">Clear All</option>
                                </select>

                                <flux:button size="sm" type="submit" class="mt-2" variant="primary"
                                    icon="plus">Apply</flux:button>
                            </form>
                        </div>


                        @if ($user->adjustments->isEmpty())
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No adjustments found.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($user->adjustments as $adjustment)
                                    <li
                                        class="flex justify-between items-center bg-zinc-200 dark:bg-zinc-700 px-3 py-2 rounded-md">
                                        <span class="text-zinc-800 dark:text-zinc-200 text-xs">
                                            {{ $adjustment->description }} ({{ ucfirst($adjustment->mode) }}):
                                            ₱{{ number_format($adjustment->amount, 2) }} - Cycle
                                            {{ $adjustment->cycle }}
                                        </span>

                                        <!-- Delete Button -->
                                        <form action="{{ route('adjustments.destroy', $adjustment) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this adjustment?');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1 rounded-md hover:stroke-amber-600 text-white transition-all duration-200 size-6 flex items-center justify-center">
                                                <flux:icon.x class="size-3 stroke-amber-500" />
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Leave Credits Section --}}
    <div class="mt-8 p-4 border-t border-zinc-300 dark:border-zinc-600">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Leave Credits</h2>

        <form action="{{ route('users.leave-credits.update', $user->id) }}" method="POST"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <!-- PTO Credits -->
            <div>
                <label for="pto" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">PTO
                    Credits</label>
                <input type="number" step="0.01" name="pto" id="pto"
                    value="{{ old('pto', $user->requestCredit->pto ?? 0) }}"
                    class="w-full p-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200" />
            </div>

            <!-- WFH Credits -->
            <div>
                <label for="wfh" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">WFH
                    Credits</label>
                <input type="number" step="0.01" name="wfh" id="wfh"
                    value="{{ old('wfh', $user->requestCredit->wfh ?? 0) }}"
                    class="w-full p-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200" />
            </div>

            <div class="col-span-1 md:col-span-2 flex justify-end">
                <flux:button type="submit" size="sm" variant="primary">
                    Update Leave Credits
                </flux:button>
            </div>
        </form>
    </div>


    <!-- Role Management Script -->
    <script>
        function addRole() {
            const newRole = document.getElementById('new-role').value;
            if (!newRole) return;

            let roles = JSON.parse(document.getElementById('roles').value);
            if (!roles.includes(newRole)) {
                roles.push(newRole);

                const roleContainer = document.createElement('div');
                roleContainer.className =
                    "bg-zinc-200 dark:bg-zinc-600 text-zinc-800 dark:text-zinc-200 px-2 py-1 rounded-full flex items-center gap-2 text-xs";
                roleContainer.innerHTML =
                    `<span>${newRole}</span>
                    <button type="button" onclick="removeRole(this, '${newRole}')"><flux:icon.x class="size-4 stroke-amber-600 hover:stroke-amber-400 inline" /></button>`;
                document.querySelector('form div.flex.flex-wrap').appendChild(roleContainer);

                document.getElementById('roles').value = JSON.stringify(roles);
            }
            document.getElementById('new-role').value = '';
        }

        function removeRole(button, role) {
            let roles = JSON.parse(document.getElementById('roles').value);
            if (role === 'User') return; // Protect the 'User' role
            roles = roles.filter(r => r !== role);
            button.parentElement.remove();
            document.getElementById('roles').value = JSON.stringify(roles);
        }

        function confirmClearAll(form) {
            const packageSelected = form.package.value;
            if (packageSelected === 'ClearAll') {
                return confirm('Are you sure you want to clear all adjustments for this user?');
            }
            return true;
        }
    </script>
</x-layouts.app>
