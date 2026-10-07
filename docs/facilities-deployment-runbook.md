# Facilities deployment preparation — October 7, 2026

## Release state

Production currently shows commit `4534485`, deployed from `main`. The release branch is `feature-facilities-reservation-form`. Cloud push-to-deploy is enabled, so merging into main starts a production deployment. Preparation does not include that merge.

## Verified locally

- Full suite: 248 tests / 1,283 assertions passed. A focused regression also checks that pending/rejected bookings with a legacy SOA have no Record payment action.
- Production Vite build and Blade template compilation passed.
- Browser acceptance: restored disposable #55, approved it with a note, replaced its unpaid SOA with a reason, sent the SOA through the local log mailer, saw Billed and October 22 due date, recorded two payment files, appended a third proof while preserving the first two, and edited notes while retaining Approved/Paid. The manual change-email button was exercised.
- Earlier browser acceptance covered booking creation/floor-plan upload, multi-room reservations, recurring overlaps, SOA removal, selected deletion, and Done → Approved → Done transitions. The current browser pass also inspected conflict blocking on individual recurring dates.
- Selected-deletion retest: disposable #44 was restored and deleted through the UI. It appeared in Deleted, no second confirmation opened, and unrelated pending bookings stayed visible. Paid disposable #55 was soft-deleted directly after acceptance because SOA protection intentionally disables UI deletion; its proof files were retained.
- Local email queue drained after the acceptance pass. One historical failed edit-mail job from October 5 predates this release check and references the previous WSL checkout's unloaded class. Current billing mail shows Sent. Production queue restart is included below to avoid stale workers after deployment.
- Isolated PostgreSQL rehearsal `lp_release_payment_oct7`: the new payment/history migration ran forward and a second run had nothing pending. Counts stayed at 53 reservations, 19 soft-deleted bookings and 14 edit-history records. Synthetic legacy payment references on one active and one deleted reservation both became payment-proof rows, with correct dates and paths. Earlier rehearsal verified the preceding three migrations. Both rehearsals use local copies, not production exports.

## Production configuration inspected without changes

- PHP 8.4; Node 22; Serverless PostgreSQL 17.
- Existing AWS public bucket and credentials are configured. No new bucket is needed. The code chooses `s3` when `AWS_PUBLIC_BUCKET` exists unless `FACILITY_UPLOAD_DISK` overrides it. The S3 driver reads `AWS_PUBLIC_BUCKET`, not `AWS_BUCKET`.
- Attachment access follows the existing implementation: admins use disk URLs; billing mail copies and attaches the current SOA; viewer floor plans use the authenticated download route. Existing public object URLs remain public if shared. This release does not change their access policy.
- Build: `composer install --no-dev`, `npm ci --audit false`, `npm run build`.
- Deploy: `php artisan migrate --force`.
- Background process: `php artisan queue:work database`, one process.
- Scheduler is disabled. The release defines `facilities:payment-reminders` at 08:00 Asia/Manila daily, with overlap prevention. Enable Cloud's scheduler during the approved release to activate it; do not add a duplicate scheduler daemon. SOA emails remain manually sent; only seven-day reminders and payment receipts are automated.
- Automatic PostgreSQL backup retention is seven days. Restore creates a new cluster. The cluster also contains LifeTrack and Portal databases; reconnect only Life Portal to an isolated restored database during recovery. Do not repoint other projects.

## Production service and database checks completed

- October 7, 06:06 UTC: a read-only export of production database `main` was restored into dedicated local PostgreSQL 17. All four release migrations ran forward successfully; a second run had nothing pending. Before/after counts remained 117 reservations, 11 soft-deleted reservations and 4 edit-history records. Hashes of every reservation's original columns were unchanged. No primary-room backfills were missing. Production had no legacy payment proofs; synthetic active/deleted proof backfills passed in the earlier local rehearsal. Viewer permissions were verified on this copy.
- October 7, 06:07 UTC: the existing production S3 identity passed upload, HeadObject, byte-for-byte GetObject, CopyObject and DeleteObject checks on temporary reservation QA objects. The existing public disk URL returned HTTP 200. Temporary objects were removed and their absence verified. This checks the shared storage operations used by facility attachments; authenticated viewer restrictions were tested locally.
- Exactly two authorized dummy emails were sent using the existing SES configuration to paolo.ylag@life.edu.ph: SOA and Payment received. Both were opened in Gmail through browser click-through. The SOA attachment opened as a one-page PDF with the expected QA content. No real requestors were emailed and no production booking records or configuration were changed.
- Cloud backup retention was verified as seven days; the earliest displayed restore point was September 30, 06:06:43 UTC. The independent production export is retained in ignored local storage. Production credentials and database copies must stay out of Git.

## Remaining deployment actions

1. Review the release branch and confirm a fresh recoverable restore point immediately before the approved deployment. Do not restore over production.
2. Merge to main only after deployment is approved, then execute and verify the deployment sequence below. The scheduler remains disabled until that release.

## Deployment sequence (prepared, not executed)

1. Record release commit and backup restore point. Pause facilities writes for the migration window if needed.
2. Merge the reviewed feature branch into main; Cloud's existing push-to-deploy runs the build and forward migrations.
3. Confirm these release migrations ran:
   - `2026_10_06_000000_add_facility_billing_emails`
   - `2026_10_06_000001_add_reservation_rooms`
   - `2026_10_07_000000_add_finished_confirmation_to_reservations`
   - `2026_10_07_000001_add_facility_payment_proofs_and_soa_revisions`
4. Run `php artisan db:seed --class=ResourceRoleSeeder --force` to register the viewer role and both viewing permissions, then `php artisan permission:cache-reset`. Do not run demo seeders in production.
5. Confirm queue processes restarted with the deployment; if they did not, use `php artisan queue:restart`. Inspect failed jobs rather than retrying all billing messages automatically.
6. Enable the Cloud scheduler; verify `php artisan schedule:list` shows the 08:00 Manila reminder. No automatic overdue/due-day emails are scheduled. Missing the exact seven-day date requires the manual reminder action.
7. Smoke-test a controlled reservation: approval, conflict block, floor plan, upload/replacement, SOA email, payment receipt, proof retention, Done transition, and viewer restrictions. Clean only the controlled test reservation. SOA-protected paid bookings cannot be deleted through the UI; arrange controlled cleanup without removing payment evidence from real bookings.

## Recovery

Prefer redeploying the previous application commit while retaining additive tables, after compatibility review. If database recovery is necessary, restore into a separate cluster/database and reconnect only Life Portal. Do not use `migrate:rollback`: the payment/history migration drops its history tables. Preserve uploaded files and revisions; a database restore does not restore deleted S3 objects. Disable reminder sending during recovery until queue and data state are reconciled.
