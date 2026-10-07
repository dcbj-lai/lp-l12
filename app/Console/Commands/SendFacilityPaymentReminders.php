<?php

namespace App\Console\Commands;

use App\Models\ResourceReservation;
use App\Services\FacilityBillingEmailService;
use Illuminate\Console\Command;

class SendFacilityPaymentReminders extends Command
{
    protected $signature = 'facilities:payment-reminders';
    protected $description = 'Queue payment reminders seven days before the SOA due date';

    public function handle(FacilityBillingEmailService $billing): int
    {
        $queued = 0;
        $skipped = 0;
        ResourceReservation::where('status', 'approved')->where('billing_status', 'billed')
            ->whereNull('paid_at')->whereNotNull('soa_path')->where('soa_email_pending', false)
            ->whereDate('payment_due_at', today()->addDays(7))->chunkById(100, function ($reservations) use ($billing, &$queued, &$skipped) {
                foreach ($reservations as $reservation) {
                    try {
                        $billing->queueScheduledReminder($reservation->id);
                        $queued++;
                    } catch (\RuntimeException $e) {
                        $skipped++;
                        $this->warn("Reservation #{$reservation->id}: {$e->getMessage()}");
                    }
                }
            });
        $this->info("Queued: {$queued}. Skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
