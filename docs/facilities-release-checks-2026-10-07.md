# Local release checks — October 7, 2026

No deployment or merge to main was performed.

## Verified

- Full automated suite: 239 tests, 1,151 assertions passed. After adding the payment confirmation, its two focused tests passed with 35 assertions.
- Vite production build passed.
- Three pending migrations ran forward successfully on an isolated copy of the local PostgreSQL database, lp_release_rehearsal_oct7. Existing counts stayed at 53 reservations, 19 soft-deleted reservations, and 14 edit-history records. Room backfill left no missing primary rooms. A second migration run had nothing pending. The application database was not changed by this rehearsal.
- Browser-created booking #54 was submitted with a floor plan and approved. An SOA was uploaded, emailed through the local log mailer, and the Billed view showed October 22 as its due date, 15 days after upload. Bulk reminders identified one eligible booking and queued its reminder. Payment proof and October 7 payment date were saved through the browser. The booking moved to Paid and its Payment received email was logged. Replace and Remove SOA were disabled after payment, and deletion remained disabled with the SOA tooltip.
- Disposable booking #54 was soft-deleted directly after testing because the expected paid SOA protection prevents UI deletion. Its attachments were retained. Existing bookings were not deleted.
- Production configuration was inspected read-only: Asia/Manila timezone, SES mailer, configured S3 bucket, and php artisan queue:work database background worker. The production scheduler is disabled. No settings were saved and no production commands were run.

## Remaining verification before deployment

- This was a local database copy, not a current production backup. Forward migration rehearsal against an isolated production copy remains required to verify its exact starting state.
- Production SES inbox delivery was not tested. Local log output confirms rendering and queue execution, not external delivery.
- Duplicate reminder/payment behavior has automated coverage. Browser booking #55 verified SOA replacement uses a new file, resets a seeded October 20 due date to October 22, and displays that updated date in the email confirmation dialog. Its unpaid SOA was removed, re-enabling deletion, and only this booking was deleted through the UI. No second deletion prompt appeared.
- Enable the production scheduler when deploying the daily seven-day payment reminder feature. It has not been enabled as part of this local work.

The Payment received preview is available locally at /dev/facilities/billing-email/payment_received. It sends on the first successful payment recording; edits to an existing payment do not send duplicates.
