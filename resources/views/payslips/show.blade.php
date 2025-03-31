<x-layouts.app title="Payslip">
    <div class="p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Payslip Details</h1>
        <div class="bg-neutral-100 dark:bg-neutral-800 p-4 rounded-lg shadow-sm">
            <h2 class="text-md text-gray-700 dark:text-gray-300 mb-2">Pay Period:
                {{ $payslip->payout->pay_period_start }} to {{ $payslip->payout->pay_period_end }}
            </h2>
            <p class="text-gray-600 dark:text-gray-400">Paydate: {{ $payslip->payout->payout_date }}</p>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300">Earnings</h3>
                <ul class="list-disc pl-4 text-gray-600 dark:text-gray-400">
                    <li>Basic Pay: ₱{{ number_format($payslip->basic_pay, 2) }}</li>
                    @foreach($adjustments as $adjustment)
                        @if($adjustment['mode'] === 'add')
                            <li>{{ $adjustment['description'] }}: ₱{{ number_format($adjustment['amount'], 2) }}</li>
                        @endif
                    @endforeach
                </ul>

                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mt-4">Deductions</h3>
                <ul class="list-disc pl-4 text-gray-600 dark:text-gray-400">
                    @foreach($adjustments as $adjustment)
                        @if($adjustment['mode'] === 'subtract')
                            <li>{{ $adjustment['description'] }}: ₱{{ number_format($adjustment['amount'], 2) }}</li>
                        @endif
                    @endforeach
                    <li>Withholding Tax: ₱{{ number_format($payslip->tax_withheld, 2) }}</li>
                </ul>

                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mt-4">Net Pay</h3>
                <p class="text-xl font-bold text-green-600 dark:text-green-400">
                    ₱{{ number_format($payslip->net_pay, 2) }}</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-2">
            <flux:button href="{{ route('payslips.index') }}" variant="filled" size="sm" class="w-full md:w-auto">
                Back to Payslips
            </flux:button>
            <flux:button href="{{ route('payslips.download', $payslip->id) }}" variant="primary" size="sm"
                class="w-full md:w-auto mt-2 md:mt-0">
                Download PDF
                <flux:icon.download size="sm" />
            </flux:button>
        </div>

    </div>
</x-layouts.app>
