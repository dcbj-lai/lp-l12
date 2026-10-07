# Local release checks — October 7, 2026

No deployment or merge to main was performed.

## Latest preparation pass

The current deployment checklist is [facilities-deployment-runbook.md](facilities-deployment-runbook.md). The full suite now passes 248 tests / 1,283 assertions. The new payment-proof/SOA-history migration passed a forward PostgreSQL rehearsal on an isolated local copy, including active and deleted legacy proof backfills. Paid SOA replacement is now enabled with a required reason; paid removal and further billing emails remain blocked. Proof files append without replacing earlier records. Local browser booking #55 completed replacement, SOA send, Billed, payment with two files, a third proof, approved edit, and manual change email; it was soft-deleted afterward with attachments retained. These findings supersede the older paid-lock description below.

## Verified

- Full automated suite: 239 tests, 1,151 assertions passed. After adding the payment confirmation, its two focused tests passed with 35 assertions.
- Vite production build passed.
- Three pending migrations ran forward successfully on an isolated copy of the local PostgreSQL database, lp_release_rehearsal_oct7. Existing counts stayed at 53 reservations, 19 soft-deleted reservations, and 14 edit-history records. Room backfill left no missing primary rooms. A second migration run had nothing pending. The application database was not changed by this rehearsal.
- Browser-created booking #54 was submitted with a floor plan and approved. An SOA was uploaded, emailed through the local log mailer, and the Billed view showed October 22 as its due date, 15 days after upload. Bulk reminders identified one eligible booking and queued its reminder. Payment proof and October 7 payment date were saved through the browser. The booking moved to Paid and its Payment received email was logged. Replace and Remove SOA were disabled after payment, and deletion remained disabled with the SOA tooltip.
- Disposable booking #54 was soft-deleted directly after testing because the expected paid SOA protection prevents UI deletion. Its attachments were retained. Existing bookings were not deleted.
- Production configuration was inspected read-only: Asia/Manila timezone, SES mailer, configured S3 bucket, and php artisan queue:work database background worker. The production scheduler is disabled. No settings were saved and no production commands were run.

## Remaining verification before deployment

- Subsequently completed: a current read-only production export was restored to isolated PostgreSQL 17 and all four pending migrations passed forward, preserving 117 reservations, 11 soft-deleted records, 4 edit-history records and all original reservation column values. Room backfills were complete; the second migration run had nothing pending.
- Subsequently completed: live S3 upload/head/read/copy/delete and public URL access passed with temporary QA files, which were removed afterward. The authorized dummy SOA and payment receipt reached paolo.ylag@life.edu.ph through production SES; browser click-through confirmed both messages and the PDF attachment preview. Production booking data and configuration were not changed.
- Duplicate reminder/payment behavior has automated coverage. Browser booking #55 verified SOA replacement uses a new file, resets a seeded October 20 due date to October 22, and displays that updated date in the email confirmation dialog. Its unpaid SOA was removed, re-enabling deletion, and only this booking was deleted through the UI. No second deletion prompt appeared.
- Enable the production scheduler when deploying the daily seven-day payment reminder feature. It has not been enabled as part of this local work.

The Payment received preview is available locally at /dev/facilities/billing-email/payment_received. It sends on the first successful payment recording; edits to an existing payment do not send duplicates.
