<x-layouts.app title="My Payslips">
    <div class="p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">My Payslips</h1>

        <table class="w-full min-w-max border-collapse border border-gray-200 dark:border-gray-700 text-sm mt-4">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="border px-4 py-2 text-left dark:text-gray-300">Control Number</th>
                    <th class="border px-4 py-2 text-left dark:text-gray-300">Pay Period</th>
                    <th class="border px-4 py-2 text-left dark:text-gray-300">Paydate</th>
                    <th class="border px-4 py-2 text-left dark:text-gray-300">Net Pay</th>
                    <th class="border px-4 py-2 text-center dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payslips as $payslip)
                                @php
                                    $payslip->payout_date = \Carbon\Carbon::parse($payslip->payout->payout_date)->format('F d, Y');
                                @endphp
                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border-b">
                                    <td class="border px-4 py-2 dark:text-gray-300">{{ $payslip->payout->control_number }}</td>
                                    <td class="border px-4 py-2 dark:text-gray-300">{{ $payslip->payout->pay_period_start }} to
                                        {{ $payslip->payout->pay_period_end }}
                                    </td>
                                    <td class="border px-4 py-2 dark:text-gray-300">{{$payslip->payout_date}}</td>
                                    <td class="border px-4 py-2 dark:text-gray-300">₱{{ number_format($payslip->net_pay, 2) }}</td>
                                    <td class="border px-4 py-2 text-center">
                                        <flux:button href="{{ route('payslips.show', $payslip->id) }}" variant="primary" size="sm">
                                            View
                                        </flux:button>
                                    </td>
                                </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-center text-gray-500 dark:text-gray-400">No payslips
                            available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
