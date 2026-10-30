<?php

namespace App\Livewire\Resources;

use App\Services\FacilitySoaGenerator;
use App\Support\FacilityStaffRates;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BillingConfiguration extends Component
{
    public array $rates = [];
    public bool $saved = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        foreach (DB::table('facility_charge_rates')->get() as $rate) $this->rates[$rate->code] = $rate->amount_cents === null ? '' : number_format($rate->amount_cents / 100, 2, '.', '');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $this->validate(['rates' => 'array', 'rates.*' => 'nullable|numeric|min:0|max:1000000|decimal:0,2']);
        DB::transaction(function () {
            foreach (FacilitySoaGenerator::LABELS + FacilityStaffRates::labels() as $code => $label) {
                $value = $this->rates[$code] ?? '';
                DB::table('facility_charge_rates')->where('code', $code)->update(['amount_cents' => $value === '' || $value === null ? null : (int) round((float) $value * 100),
                    'updated_by' => auth()->id(), 'updated_at' => now()]);
            }
        });
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.resources.billing-configuration', ['labels' => FacilitySoaGenerator::LABELS,
            'periods' => FacilityStaffRates::PERIODS]);
    }
}
