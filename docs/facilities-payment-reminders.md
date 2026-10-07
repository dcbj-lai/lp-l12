# Facilities payment reminders

Recording the first payment or adding a new batch of payment proofs queues a Payment received email to the requester. It confirms the Paid status, event, rooms, schedule, and date paid. Saving only a date correction does not send another confirmation. The local preview is /dev/facilities/billing-email/payment_received. No additional migration is required for this confirmation.

For unpaid bookings, each SOA upload or replacement sets payment_due_at to 15 calendar days after upload in Asia/Manila. Sending the SOA preserves that date and marks the reservation Billed after successful delivery. Replacements must be sent before reminders resume. Paid reservations may replace an SOA with a required reason, preserving payment records and due date, but cannot remove the SOA or send further billing reminders.

The first scheduled task in this repository is facilities:payment-reminders, registered in routes/console.php for 08:00 Asia/Manila daily. It sends one upcoming reminder exactly seven days before the due date, only for approved unpaid billed reservations with the current SOA sent. It skips deleted reservations, missing or invalid recipients, queued billing emails, reminders already sent that day, and prior upcoming reminders for the same SOA. Queued jobs recheck payment and attachment state before sending. No automatic due-day or overdue emails are registered.

The Billed tab has Send payment reminders. Its confirmation snapshots all eligible reservations in that view, across event dates. It uses the existing upcoming/due-today/overdue wording and rechecks each booking before queueing. Cancel sends nothing. Existing individual reminders remain in the Billing dropdown.

Deployment requires the Laravel scheduler runner and an email queue worker. The scheduler should run php artisan schedule:run every minute; the application chooses the daily time. Inspect php artisan schedule:list before enabling. Nothing has been enabled or changed in production. No new database migration is required: this uses the existing payment due date and billing email history fields.

Local email delivery uses the log mailer. Automated checks were tested against isolated data using the command directly; the background scheduler runner has not been started locally. Existing due dates are retained until the SOA is reuploaded. If the daily scheduler is down on the seven-day reminder date, that automatic reminder is missed; admins can use the bulk manual action.
