<!DOCTYPE html>
<html>

<head>
    <style>
        /* General Styles */
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            font-size: 14px;
        }

        .payslip-container {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #4A5568;
            font-size: 22px;
            margin-bottom: 5px;
        }

        /* Logo styling */
        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo {
            width: 120px;
            height: auto;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #2D3748;
            text-align: center;
            margin-bottom: 10px;
        }

        /* Section Titles */
        .section-title {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #2B6CB0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }

        /* Table Styling */
        .details,
        .summary {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
        }

        .details th,
        .details td {
            padding: 3px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .details th {
            background-color: #f4f4f4;
        }

        .summary td {
            font-weight: bold;
            padding: 5px;
        }

        /* Net Pay styling */
        .net-pay {
            color: #38A169;
            font-size: 20px;
            text-align: center;
        }

        /* Footer styling */
        .footer {
            text-align: center;
            font-size: 10px;
            color: #718096;
            margin-top: 10px;
        }
    </style>
    <meta charset="UTF-8">
</head>

<body>
    <div class="payslip-container">

        <!-- Logo Section -->
        <div class="logo-container">
            <img src="{{ public_path('images/lai-logo.png') }}" class="logo" alt="Life Academy Logo">
        </div>

        <!-- Header -->
        <h1>Payslip</h1>
        <p class="company-name">Life Academy International</p>

        <!-- Employee Info -->
        <p><strong>Employee:</strong> {{ auth()->user()->name }}</p>
        <p><strong>Control Number:</strong> {{ $payslip->payout->control_number }}</p>
        <p><strong>Pay Period:</strong> {{ $payslip->payout->pay_period_start }} to
            {{ $payslip->payout->pay_period_end }}
        </p>
        <p><strong>Payout Date:</strong> {{ $payslip->payout->payout_date }}</p>

        <!-- Earnings Section -->
        <div class="section-title">Earnings</div>
        <table class="details">
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Basic Pay</td>
                <td>₱{{ number_format($payslip->basic_pay, 2) }}</td>
            </tr>
            @foreach($adjustments as $adjustment)
                @if($adjustment['mode'] === 'add')
                    <tr>
                        <td>{{ $adjustment['description'] }}</td>
                        <td>₱{{ number_format($adjustment['amount'], 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        <!-- Deductions Section -->
        <div class="section-title">Deductions</div>
        <table class="details">
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
            @foreach($adjustments as $adjustment)
                @if($adjustment['mode'] === 'subtract')
                    <tr>
                        <td>{{ $adjustment['description'] }}</td>
                        <td>₱{{ number_format($adjustment['amount'], 2) }}</td>
                    </tr>
                @endif
            @endforeach
            <tr>
                <td>Withholding Tax</td>
                <td>₱{{ number_format($payslip->tax_withheld, 2) }}</td>
            </tr>
        </table>

        <!-- Net Pay Section -->
        <div class="section-title">Net Pay</div>
        <table class="summary">
            <tr>
                <td class="net-pay">₱{{ number_format($payslip->net_pay, 2) }}</td>
            </tr>
        </table>

        <!-- Footer Section -->
        <div class="footer">
            <span class="text-sm italic">"Let your light shine before others, that they may see your good deeds and
                glorify your Father in heaven" - Matthew 5:16</span>
        </div>
    </div>
</body>

</html>
