<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payout;
use App\Models\Payslip;
use App\Models\Adjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    public function index()
    {
        $payouts = Payout::orderBy('payout_date', 'desc')->get();

        return view('payroll.payouts', compact('payouts'));
    }

    public function edit(Payout $payout)
    {
        return view('payroll.edit-payout', compact('payout'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cycle' => 'required|in:1,2',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date',
            'payout_date' => 'required|date',
        ]);
    
        // Generate control number (YYYY-nnn)
        $year = date('Y');
        $latestPayout = Payout::latest('id')->first();
        $nextNumber = $latestPayout ? (intval(substr($latestPayout->control_number, -3)) + 1) : 1;
        $controlNumber = sprintf('%s-%03d', $year, $nextNumber);
    
        try {
            // Check for existing payout with the same cycle and dates
            $existingPayout = Payout::where('cycle', $request->cycle)
                ->where('pay_period_start', $request->pay_period_start)
                ->where('pay_period_end', $request->pay_period_end)
                ->where('payout_date', $request->payout_date)
                ->exists();
    
            if ($existingPayout) {
                return back()->with('error', 'Payout for this period and cycle already exists!');
            }
            
            // dd ($request);
            // Create the new payout if no duplicate found
            Payout::create([
                'control_number' => $controlNumber,
                'cycle' => (int) $request->cycle,
                'pay_period_start' => $request->pay_period_start,
                'pay_period_end' => $request->pay_period_end,
                'payout_date' => $request->payout_date,
                'total_amount' => 0,
                'status' => 'pending',
            ]);
    
            return redirect()->route('payouts.index')->with('success', 'Payout created successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create payout: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Payout $payout)
    {
        $validated = $request->validate([
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date',
            'payout_date' => 'required|date',
            'status' => 'required|in:pending,dispatched,canceled',
        ]);

        $payout->update($validated);

        return redirect()->route('payouts.index')->with('success', 'Payout updated successfully!');
    }

   

    public function generate($control_number)
    {
        $payout = Payout::where('control_number', $control_number)->firstOrFail();
    
        // Prevent regeneration if payslips already exist
        if (Payslip::where('payout_id', $payout->id)->exists()) {
            return abort(409);
        }
    
        $users = User::where('payroll_on', true)->get();
        $total_payout_amount = 0; // Initialize total amount tracker
    
        foreach ($users as $user) {
            $adjustments = Adjustment::where('user_id', $user->id)
                ->where('cycle', $payout->cycle)
                ->where(function ($query) use ($payout) {
                    $query->where('effective_date', '9999-12-31')
                        ->orWhereBetween('effective_date', [$payout->pay_period_start, $payout->pay_period_end]);
                })
                ->get();
    
            $total_additions = $adjustments->where('mode', 'add')->sum('amount');
            $total_deductions = $adjustments->where('mode', 'subtract')->sum('amount');
    
            $gross_salary = $user->monthly_rate / 2;
    
            $withholding_tax = $payout->cycle == 2 ? $this->calculateTax($user->monthly_rate) : 0;
    
            $net_pay = $gross_salary + $total_additions - $total_deductions - $withholding_tax;
    
            Payslip::create([
                'user_id' => $user->id,
                'payout_id' => $payout->id,
                'cycle' => $payout->cycle,
                'basic_pay' => $gross_salary,
                'total_additions' => $total_additions,
                'total_deductions' => $total_deductions,
                'tax_withheld' => $withholding_tax,
                'net_pay' => $net_pay,
                'adjustments' => json_encode($adjustments),
            ]);
    
            // Add this user's net pay to the total payout amount
            $total_payout_amount += $net_pay;
        }
    
        // Update the payout record with total amount and status
        $payout->update([
            'status' => 'dispatched',
            'total_amount' => $total_payout_amount,
        ]);
    
        return redirect()->route('payouts.index')->with('success', 'Payroll generated successfully!');
    }
    

public function userPayslips()
{
    $payslips = Payslip::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get();
    // dd($payslips);
    return view('payslips.index', compact('payslips'));
}

public function showPayslip(Payslip $payslip)
{
    if ($payslip->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access');
    }

    $adjustments = json_decode($payslip->adjustments, true);

    return view('payslips.show', compact('payslip', 'adjustments'));
}

public function downloadPayslip(Payslip $payslip)
{
    if ($payslip->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access');
    }
    $employeeName = auth()->user()->name;
    $adjustments = json_decode($payslip->adjustments, true);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payslips.pdf', compact('payslip', 'adjustments'));

    $filename = "Payslip_{$payslip->payout->payout_date}_{$employeeName}.pdf";

    return $pdf->download($filename);
}

/**
 * BIR Tax Computation
 * @param mixed $monthlyRate
 * @return float|int
 */
private function calculateTax($monthlyRate)
{
    if ($monthlyRate <= 20833) {
        return 0;
    } elseif ($monthlyRate <= 33332) {
        return ($monthlyRate - 20833) * 0.15;
    } elseif ($monthlyRate <= 66666) {
        return 1875 + ($monthlyRate - 33333) * 0.20;
    } elseif ($monthlyRate <= 166666) {
        return 8541.80 + ($monthlyRate - 66667) * 0.25;
    } elseif ($monthlyRate <= 666666) {
        return 33541.80 + ($monthlyRate - 166667) * 0.30;
    } else {
        return 183541.80 + ($monthlyRate - 666667) * 0.35;
    }
}

}
