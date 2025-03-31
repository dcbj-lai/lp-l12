<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Adjustment;
use Illuminate\Http\Request;
use Log;

class AdjustmentController extends Controller
{
    public function store(Request $request)
{
    /**You cannot edit your own pay package!!! */
    // if (request()->user()->id === auth()->user()->id) {
    //     return abort(403);
    // }
    
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'mode' => 'required|in:add,subtract',
        'description' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0.01',
        'cycle' => 'required|integer|min:1',
        'updated_by' => 'required|exists:users,id',
    ]);

    Adjustment::create($validated);
    // dd('Adjustment created', $validated);

    return redirect()->back()->with('success', 'Adjustment added successfully!');
}

    public function destroy(Adjustment $adjustment)
{
    $adjustment->delete();

    return redirect()->back()->with('success', 'Adjustment deleted successfully!');
}

public function applyPackage(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'package' => 'required|in:L1,L2,ManCom,ClearAll',
    ]);

    $userId = $request->user_id;
    $package = $request->package;

    $deMinimisPackages = [
        'L1' => [
            ['description' => 'Rice 1', 'amount' => 150, 'mode' => 'add'],
            ['description' => 'Rice 2', 'amount' => 150, 'mode' => 'add'],
            ['description' => 'Laundry', 'amount' => 300, 'mode' => 'add'],
            ['description' => 'Medicine', 'amount' => 500, 'mode' => 'add'],
            ['description' => 'Meal', 'amount' => 400, 'mode' => 'add'],
            ['description' => 'Other Allowances', 'amount' => 3000, 'mode' => 'add'],
        ],
        'L2' => [
            ['description' => 'Rice 1', 'amount' => 250, 'mode' => 'add'],
            ['description' => 'Rice 2', 'amount' => 250, 'mode' => 'add'],
            ['description' => 'Laundry', 'amount' => 300, 'mode' => 'add'],
            ['description' => 'Medicine', 'amount' => 800, 'mode' => 'add'],
            ['description' => 'Meal', 'amount' => 900, 'mode' => 'add'],
            ['description' => 'Other Allowances', 'amount' => 5000, 'mode' => 'add'],
        ],
        'ManCom' => [
            ['description' => 'Rice 1', 'amount' => 1000, 'mode' => 'add'],
            ['description' => 'Rice 2', 'amount' => 1000, 'mode' => 'add'],
            ['description' => 'Laundry', 'amount' => 600, 'mode' => 'add'],
            ['description' => 'Medicine', 'amount' => 1600, 'mode' => 'add'],
            ['description' => 'Meal', 'amount' => 1800, 'mode' => 'add'],
            ['description' => 'Other Allowances', 'amount' => 5000, 'mode' => 'add'],
        ],
    ];
    // Define the 4 "subtract" adjustments
    $user = User::findOrFail($userId);
    $monthlyRate = $user->monthly_rate;

    $mandatoryDeductions = [
        ['description' => 'HDMF-EE', 'amount' => 200, 'mode' => 'subtract'], // Fixed
        ['description' => 'PHIC-EE', 'amount' => min($monthlyRate * 0.05 / 2, 2500), 'mode' => 'subtract'], // 5% of monthly / 2, capped at 2500
        ['description' => 'MPF-EE', 'amount' => $this->getMpfAmount($monthlyRate), 'mode' => 'subtract'], // Lookup from MPF table
        ['description' => 'SSS-EE', 'amount' => $this->getSssAmount($monthlyRate), 'mode' => 'subtract'], // Lookup from SSS table
    ];

    //Clear all 'add' adjustments when ClearAll is selected
    if ($package === 'ClearAll') {
        Adjustment::where('user_id', $userId)
            ->delete();
        $user->update(['package' => null]);
        return redirect()->back()->with('success', 'All "add" adjustments cleared!');
    }

    // Clean up any existing package adjustments (both add/subtract)
    Adjustment::where('user_id', $userId)->delete();

    // Apply the new package adjustments
    if (isset($deMinimisPackages[$package])) {
        foreach ($deMinimisPackages[$package] as $adjustment) {
            Adjustment::create([
                'user_id' => $userId,
                'description' => $adjustment['description'],
                'mode' => $adjustment['mode'], // Support both add/subtract
                'amount' => $adjustment['amount'],
                'cycle' => 2,
                'effective_date' => '9999-12-31',
                'updated_by' => auth()->id(),
            ]);
        }
    }
    // Apply mandatory "subtract" adjustments
    foreach ($mandatoryDeductions as $deduction) {
        Adjustment::create([
            'user_id' => $userId,
            'description' => $deduction['description'],
            'mode' => $deduction['mode'],
            'amount' => $deduction['amount'],
            'cycle' => 1,
            'effective_date' => '9999-12-31',
            'updated_by' => auth()->id(),
        ]);
    }
    $user->update(['package' => $request->package]);
    return redirect()->back()->with('success', "Package {$package} applied successfully!");
}

// ✅ Helper function to get MPF-EE from the updated table
private function getMpfAmount($monthlyRate)
{
    $mpfTable = [
        ['min' => 0, 'max' => 5249.99, 'amount' => 0],
        ['min' => 5250.00, 'max' => 5749.99, 'amount' => 0],
        ['min' => 5750.00, 'max' => 6249.99, 'amount' => 0],
        ['min' => 6250.00, 'max' => 6749.99, 'amount' => 0],
        ['min' => 6750.00, 'max' => 7249.99, 'amount' => 0],
        ['min' => 7250.00, 'max' => 7749.99, 'amount' => 0],
        ['min' => 7750.00, 'max' => 8249.99, 'amount' => 0],
        ['min' => 8250.00, 'max' => 8749.99, 'amount' => 0],
        ['min' => 8750.00, 'max' => 9249.99, 'amount' => 0],
        ['min' => 9250.00, 'max' => 9749.99, 'amount' => 0],
        ['min' => 9750.00, 'max' => 10249.99, 'amount' => 0],
        ['min' => 10250.00, 'max' => 10749.99, 'amount' => 0],
        ['min' => 10750.00, 'max' => 11249.99, 'amount' => 0],
        ['min' => 11250.00, 'max' => 11749.99, 'amount' => 0],
        ['min' => 11750.00, 'max' => 12249.99, 'amount' => 0],
        ['min' => 12250.00, 'max' => 12749.99, 'amount' => 0],
        ['min' => 12750.00, 'max' => 13249.99, 'amount' => 0],
        ['min' => 13250.00, 'max' => 13749.99, 'amount' => 0],
        ['min' => 13750.00, 'max' => 14249.99, 'amount' => 0],
        ['min' => 14250.00, 'max' => 14749.99, 'amount' => 0],
        ['min' => 14750.00, 'max' => 15249.99, 'amount' => 0],
        ['min' => 15250.00, 'max' => 15749.99, 'amount' => 0],
        ['min' => 15750.00, 'max' => 16249.99, 'amount' => 0],
        ['min' => 16250.00, 'max' => 16749.99, 'amount' => 0],
        ['min' => 16750.00, 'max' => 17249.99, 'amount' => 0],
        ['min' => 17250.00, 'max' => 17749.99, 'amount' => 0],
        ['min' => 17750.00, 'max' => 18249.99, 'amount' => 0],
        ['min' => 18250.00, 'max' => 18749.99, 'amount' => 0],
        ['min' => 18750.00, 'max' => 19249.99, 'amount' => 0],
        ['min' => 19250.00, 'max' => 19749.99, 'amount' => 0],
        ['min' => 19750.00, 'max' => 20249.99, 'amount' => 0],
        ['min' => 20250.00, 'max' => 20749.99, 'amount' => 25.00],
        ['min' => 20750.00, 'max' => 21249.99, 'amount' => 50.00],
        ['min' => 21250.00, 'max' => 21749.99, 'amount' => 75.00],
        ['min' => 21750.00, 'max' => 22249.99, 'amount' => 100.00],
        ['min' => 22250.00, 'max' => 22749.99, 'amount' => 125.00],
        ['min' => 22750.00, 'max' => 23249.99, 'amount' => 150.00],
        ['min' => 23250.00, 'max' => 23749.99, 'amount' => 175.00],
        ['min' => 23750.00, 'max' => 24249.99, 'amount' => 200.00],
        ['min' => 24250.00, 'max' => 24749.99, 'amount' => 225.00],
        ['min' => 24750.00, 'max' => 25249.99, 'amount' => 250.00],
        ['min' => 25250.00, 'max' => 25749.99, 'amount' => 275.00],
        ['min' => 25750.00, 'max' => 26249.99, 'amount' => 300.00],
        ['min' => 26250.00, 'max' => 26749.99, 'amount' => 325.00],
        ['min' => 26750.00, 'max' => 27249.99, 'amount' => 350.00],
        ['min' => 27250.00, 'max' => 27749.99, 'amount' => 375.00],
        ['min' => 27750.00, 'max' => 28249.99, 'amount' => 400.00],
        ['min' => 28250.00, 'max' => 28749.99, 'amount' => 425.00],
        ['min' => 28750.00, 'max' => 29249.99, 'amount' => 450.00],
        ['min' => 29250.00, 'max' => 29749.99, 'amount' => 475.00],
        ['min' => 29750.00, 'max' => 30249.99, 'amount' => 500.00],
        ['min' => 30250.00, 'max' => 30749.99, 'amount' => 525.00],
        ['min' => 30750.00, 'max' => 31249.99, 'amount' => 550.00],
        ['min' => 31250.00, 'max' => 31749.99, 'amount' => 575.00],
        ['min' => 31750.00, 'max' => 32249.99, 'amount' => 600.00],
        ['min' => 32250.00, 'max' => 32749.99, 'amount' => 625.00],
        ['min' => 32750.00, 'max' => 33249.99, 'amount' => 650.00],
        ['min' => 33250.00, 'max' => 33749.99, 'amount' => 675.00],
        ['min' => 33750.00, 'max' => 34249.99, 'amount' => 700.00],
        ['min' => 34250.00, 'max' => 34749.99, 'amount' => 725.00],
        ['min' => 34750.00, 'max' => PHP_INT_MAX, 'amount' => 750.00],
    ];
    

    foreach ($mpfTable as $range) {
        if ($monthlyRate >= $range['min'] && $monthlyRate <= $range['max']) {
            return $range['amount'];
        }
    }

    return 0;
}

// ✅ Helper function to get SSS-EE from the updated table
private function getSssAmount($monthlyRate)
{
    $sssTable = [
        ['min' => 0, 'max' => 5249.99, 'amount' => 250],
        ['min' => 5250.00, 'max' => 5749.99, 'amount' => 275],
        ['min' => 5750.00, 'max' => 6249.99, 'amount' => 300],
        ['min' => 6250.00, 'max' => 6749.99, 'amount' => 325],
        ['min' => 6750.00, 'max' => 7249.99, 'amount' => 350],
        ['min' => 7250.00, 'max' => 7749.99, 'amount' => 375],
        ['min' => 7750.00, 'max' => 8249.99, 'amount' => 400],
        ['min' => 8250.00, 'max' => 8749.99, 'amount' => 425],
        ['min' => 8750.00, 'max' => 9249.99, 'amount' => 450],
        ['min' => 9250.00, 'max' => 9749.99, 'amount' => 475],
        ['min' => 9750.00, 'max' => 10249.99, 'amount' => 500],
        ['min' => 10250.00, 'max' => 10749.99, 'amount' => 525],
        ['min' => 10750.00, 'max' => 11249.99, 'amount' => 550],
        ['min' => 11250.00, 'max' => 11749.99, 'amount' => 575],
        ['min' => 11750.00, 'max' => 12249.99, 'amount' => 600],
        ['min' => 12250.00, 'max' => 12749.99, 'amount' => 625],
        ['min' => 12750.00, 'max' => 13249.99, 'amount' => 650],
        ['min' => 13250.00, 'max' => 13749.99, 'amount' => 675],
        ['min' => 13750.00, 'max' => 14249.99, 'amount' => 700],
        ['min' => 14250.00, 'max' => 14749.99, 'amount' => 725],
        ['min' => 14750.00, 'max' => 15249.99, 'amount' => 750],
        ['min' => 15250.00, 'max' => 15749.99, 'amount' => 775],
        ['min' => 15750.00, 'max' => 16249.99, 'amount' => 800],
        ['min' => 16250.00, 'max' => 16749.99, 'amount' => 825],
        ['min' => 16750.00, 'max' => 17249.99, 'amount' => 850],
        ['min' => 17250.00, 'max' => 17749.99, 'amount' => 875],
        ['min' => 17750.00, 'max' => 18249.99, 'amount' => 900],
        ['min' => 18250.00, 'max' => 18749.99, 'amount' => 925],
        ['min' => 18750.00, 'max' => 19249.99, 'amount' => 950],
        ['min' => 19250.00, 'max' => 19749.99, 'amount' => 975],
        ['min' => 19750.00, 'max' => 20249.99, 'amount' => 1000],
        ['min' => 20250.00, 'max' => 20749.99, 'amount' => 1000],
        ['min' => 20750.00, 'max' => 21249.99, 'amount' => 1000],
        ['min' => 21250.00, 'max' => 21749.99, 'amount' => 1000],
        ['min' => 21750.00, 'max' => 22249.99, 'amount' => 1000],
        ['min' => 22250.00, 'max' => 22749.99, 'amount' => 1000],
        ['min' => 22750.00, 'max' => 23249.99, 'amount' => 1000],
        ['min' => 23250.00, 'max' => 23749.99, 'amount' => 1000],
        ['min' => 23750.00, 'max' => 24249.99, 'amount' => 1000],
        ['min' => 24250.00, 'max' => 24749.99, 'amount' => 1000],
        ['min' => 24750.00, 'max' => 25249.99, 'amount' => 1000],
        ['min' => 25250.00, 'max' => 25749.99, 'amount' => 1000],
        ['min' => 25750.00, 'max' => 26249.99, 'amount' => 1000],
        ['min' => 26250.00, 'max' => 26749.99, 'amount' => 1000],
        ['min' => 26750.00, 'max' => 27249.99, 'amount' => 1000],
        ['min' => 27250.00, 'max' => 27749.99, 'amount' => 1000],
        ['min' => 27750.00, 'max' => 28249.99, 'amount' => 1000],
        ['min' => 28250.00, 'max' => 28749.99, 'amount' => 1000],
        ['min' => 28750.00, 'max' => 29249.99, 'amount' => 1000],
        ['min' => 29250.00, 'max' => 29749.99, 'amount' => 1000],
        ['min' => 29750.00, 'max' => 30249.99, 'amount' => 1000],
        ['min' => 30250.00, 'max' => 30749.99, 'amount' => 1000],
        ['min' => 30750.00, 'max' => 31249.99, 'amount' => 1000],
        ['min' => 31250.00, 'max' => 31749.99, 'amount' => 1000],
        ['min' => 31750.00, 'max' => 32249.99, 'amount' => 1000],
        ['min' => 32250.00, 'max' => 32749.99, 'amount' => 1000],
        ['min' => 32750.00, 'max' => 33249.99, 'amount' => 1000],
        ['min' => 33250.00, 'max' => 33749.99, 'amount' => 1000],
        ['min' => 33750.00, 'max' => 34249.99, 'amount' => 1000],
        ['min' => 34250.00, 'max' => PHP_INT_MAX, 'amount' => 1000],
    ];
    

    foreach ($sssTable as $range) {
        if ($monthlyRate >= $range['min'] && $monthlyRate <= $range['max']) {
            return $range['amount'];
        }
    }

    return 0;
}


}
