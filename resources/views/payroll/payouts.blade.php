<x-layouts.app title="Payouts">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-4">
        <h1 class="text-2xl font-semibold mb-4 dark:text-gray-200">Payouts</h1>
        <!-- Generate Payout Button -->
        <div class="mb-4 flex justify-center sm:justify-start">
            <flux:modal.trigger name="generate-payout">
                <flux:button class="w-full sm:w-auto text-center">+ Generate Payout</flux:button>
            </flux:modal.trigger>
        </div>

        <div class="overflow-x-auto shadow border">
            <table class="w-full table-auto border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-700 text-xs md:text-sm">
                    <tr>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-gray-600 dark:text-gray-300">Cycle</th>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-gray-600 dark:text-gray-300">Pay Period</th>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-gray-600 dark:text-gray-300">Control #</th>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-gray-600 dark:text-gray-300">Payout Date</th>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-gray-600 dark:text-gray-300">Total Amount</th>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-gray-600 dark:text-gray-300">Status</th>
                        <th class="border px-2 py-2 md:px-4 md:py-2 text-center text-gray-600 dark:text-gray-300">
                            Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($payouts as $payout)
                        <tr>
                            <td class="border px-4 py-2 dark:text-gray-300">{{ $payout->cycle }}</td>
                            <td class="border px-4 py-2 dark:text-gray-300">{{ $payout->pay_period_start }} to
                                {{ $payout->pay_period_end }}
                            </td>
                            <td class="border px-4 py-2 dark:text-gray-300">{{ $payout->control_number }}</td>
                            <td class="border px-4 py-2 dark:text-gray-300">{{ $payout->payout_date }}</td>
                            <td class="border px-4 py-2 dark:text-gray-300">₱{{ number_format($payout->total_amount, 2) }}
                            </td>
                            <td class="border px-4 py-2 dark:text-gray-300">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded
                                                                        @if($payout->status === 'pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200
                                                                        @elseif($payout->status === 'dispatched') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200
                                                                        @else bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 @endif">
                                    {{ ucfirst($payout->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <flux:dropdown>
                                    <flux:button size="sm" icon-trailing="chevron-down">Actions</flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil"
                                            href="{{ route('payouts.edit', $payout->control_number) }}"
                                            :disabled="$payout->status === 'dispatched'">
                                            Edit
                                        </flux:menu.item>
                                        <!-- Generate Payroll Button wrapped in form -->
                                        <form action="{{ route('payroll.generate', $payout->control_number) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to generate payroll for this payout?');">
                                            @csrf
                                            <flux:menu.item icon="currency-dollar" type="submit" :disabled="$payout->status !== 'pending'">
                                                Generate Payroll
                                            </flux:menu.item>
                                        </form>

                                        <flux:menu.item icon="document-text"
                                            href="{{ route('payslips.index', $payout->control_number) }}">
                                            View All Payslips
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>

                    @empty
                        <!-- No Payouts Message -->
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-500 dark:text-gray-400 italic">
                                No payouts records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>
    {{-- modals --}}
    <flux:modal name="generate-payout" :show="false" maxWidth="lg" variant="flyout">
        <div class="p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-semibold mb-4 dark:text-gray-200">Generate New Payout</h2>

            <!-- Form inside Modal -->
            <form action="{{ route('payouts.store') }}" method="POST">
                @csrf

                <!-- Cycle Dropdown -->
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200">Cycle</label>
                    <select name="cycle" x-data @change="setCycleDefaults($event.target.value)"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200">
                        <option value="1" selected>Cycle 1 (21st prev month - 5th current)</option>
                        <option value="2">Cycle 2 (6th - 20th current month)</option>
                    </select>
                </div>

                <!-- Pay Period -->
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200">Pay Period</label>
                    <input type="text" name="pay_period" readonly
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200">
                    <input type="hidden" name="pay_period_start">
                    <input type="hidden" name="pay_period_end">
                </div>

                <!-- Payout Date -->
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200">Payout Date</label>
                    <input type="date" name="payout_date" readonly
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200">
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-2 space-y-2 sm:space-y-0 mt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto">
                        Generate
                    </flux:button>
                </div>

            </form>
        </div>
    </flux:modal>

    <script>
        function setCycleDefaults(cycle) {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');

            let start, end, payout;

            if (cycle == 1) {
                const prevMonth = today.getMonth() === 0 ? '12' : String(today.getMonth()).padStart(2, '0');
                const prevYear = today.getMonth() === 0 ? year - 1 : year;

                start = `${prevYear}-${prevMonth}-21`;
                end = `${year}-${month}-05`;
                payout = `${year}-${month}-10`;
            } else {
                start = `${year}-${month}-06`;
                end = `${year}-${month}-20`;
                payout = `${year}-${month}-25`;
            }

            document.querySelector('[name="pay_period"]').value = `${start} to ${end}`;
            document.querySelector('[name="pay_period_start"]').value = start;
            document.querySelector('[name="pay_period_end"]').value = end;
            document.querySelector('[name="payout_date"]').value = payout;
        }

        // Ensure Cycle 1 is loaded by default
        document.addEventListener('DOMContentLoaded', () => setCycleDefaults(1));
    </script>

</x-layouts.app>
