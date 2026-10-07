<?php

namespace App\Http\Controllers;

class FacilityBillingPreviewController extends Controller
{
    public function __invoke(string $scenario = 'all')
    {
        abort_unless(app()->environment('local'), 404);
        $scenarios = ['soa' => 'Initial SOA', 'updated_soa' => 'Updated SOA', 'upcoming' => 'Upcoming payment reminder', 'due_today' => 'Payment due today', 'overdue' => 'Overdue payment reminder', 'payment_received' => 'Payment received'];
        if ($scenario === 'all') {
            return view('emails.resource-booking.billing-previews', compact('scenarios'));
        }
        abort_unless(isset($scenarios[$scenario]), 404);
        $kind = $scenario;
        $details = [
            'title' => 'Faculty Workshop', 'reservation_id' => 101,
            'event_date' => 'Nov 10, 2026 9:00 AM–Nov 10, 2026 12:00 PM',
            'room' => 'Meeting Room · 14th Floor', 'paid' => false,
            'due_date' => ($scenario === 'overdue' ? today()->subDays(3) : ($scenario === 'due_today' ? today() : today()->addDays(15)))->format('M j, Y'),
            'paid_date' => today()->format('M j, Y'),
        ];
        if ($scenario === 'payment_received') { return view('emails.resource-booking.payment-received', compact('details')); }
        return view('emails.resource-booking.billing', compact('details', 'kind'));
    }
}
