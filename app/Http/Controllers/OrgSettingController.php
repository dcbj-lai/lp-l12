<?php

namespace App\Http\Controllers;

use App\Models\LeaveReplenishmentRun;
use App\Models\OrgSetting;
use Illuminate\Http\Request;

class OrgSettingController extends Controller
{
    public function index()
    {
        $settings = OrgSetting::first(); // single row table
        $replenishmentRuns = LeaveReplenishmentRun::with('runner')
            ->latest('run_date')
            ->latest('id')
            ->take(20)
            ->get();

        return view('org-settings.index', compact('settings', 'replenishmentRuns'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'pto_default' => 'required|numeric|min:0',
            'wfh_default' => 'required|numeric|min:0',
        ]);

        $settings = OrgSetting::first();
        $settings->update($request->only('pto_default', 'wfh_default'));

        return redirect()->back()->with('success', 'Organization settings updated.');
    }
}
