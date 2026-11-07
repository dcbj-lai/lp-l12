# Facilities release — November 7, 2026

Branch: `codex/facilities-payment-verification`, based on production `bddfb57`. The user authorized deployment after acceptance checks pass.

## Behavior

- Requester magic links accept up to ten private payment proofs. Submission remains unpaid until an active `facility.payment-verifier` confirms full payment. All verifiers share the inbox; correction/resubmission history is retained.
- Requester email actions follow current eligibility. Payment upload requires a current SOA and unpaid reservation; cancellation remains scoped to the associated booking/eligible series dates.
- Admin/viewer reservation cards are compact disclosures. Search accepts event dates. SOAs show filenames, email-state pills and separate due dates; floor plans have authenticated previews with download/error fallback.
- Paid/Done SOAs can be revised with a reason while retaining prior files and paid state. SOA generation/upload does not send mail; unpaid issuance uses the explicit email action. Paid corrections do not send an SOA email.
- Facilities admins can explicitly override pending or confirmed payment evidence with new proofs and a required reason. Earlier evidence and actor/time/reason remain in audit history. Finance cannot subsequently confirm superseded submissions.

## Validation

Current complete clean Linux suite: 311 tests, 1,821 assertions passed. Vite production build and all Blade templates compile. Tests use isolated SQLite and fake mail.

Chrome acceptance includes required booking fields, a three-date public submission with a real dummy floor-plan upload, bulk approval, approved note editing, manual SOA generation/issuance, two-file requester upload, acknowledgment validation, Finance confirmation, paid guest state, viewer upload badges and Finance access denial, recurring cancellation selection, conflict preview/blocking and boundary resolution. Earlier November 7 checks cover paid Done SOA corrections, required override reasons, retained confirmed proofs, pending-review override and superseded Finance evidence; November 6 covers correction/resubmission and verifier restrictions. Disposable test records are cleaned up with recoverable deletion; evidence is retained. Local mail uses the log transport only.

The fresh browser pass found a deleted recurring group displaying an Approved summary badge. Its collapsed badge now says Deleted. Selected deletion closed its confirmation without opening an unrelated confirmation; restoring the selected booking returned it to For Approval.

## Database

Apply forward only:

- `2026_11_03_000000_add_facility_payment_verification`
- `2026_11_07_000000_add_facility_payment_override_history`

A new isolated PostgreSQL 17 database was restored from the schema-only production export and brought to the current baseline. Columns, indexes and foreign keys of reservations, payment proofs, users and roles matched metadata read from production on November 7. Both new migrations and a second no-op run passed; hashes of original synthetic active/deleted reservations and payment proofs were unchanged. Earlier local-data rehearsals also retained 32 reservations and 12 proofs. This is schema verification plus synthetic/local evidence, not a fresh copy of production record data.

Ignored reports: `storage/app/release-preflight/current-schema-migration-nov7.json`, `current-production-schema-nov7.json`, `payment-verification-clean.log/.xml`. Never commit tokens, credentials, exports or uploaded proofs.

## Live configuration checked before release

Laravel Cloud still deploys `main` automatically. PHP 8.4, Node 22, build commands `composer install --no-dev`, `npm ci --audit false`, `npm run build`; deploy command `php artisan migrate --force`.

Billing resolves to `private_s3`; the private bucket is configured and existing SOA/proof objects are readable. Existing public floor-plan configuration is retained. Queue is `database`, one `php artisan queue:work database` background process; zero pending default jobs and zero failed jobs dated November 7 at preflight. Scheduler is already enabled, with one daily 08:00 payment-reminder task. No additional scheduler is introduced or enabled by this release.

Cloud database cluster has seven-day point-in-time recovery enabled. It also contains other application databases; recovery must isolate LP and never repoint the other applications.

## Release and recovery

After checks pass, commit/push the feature branch and merge its reviewed PR to main. Verify Cloud build, forward migrations and deployed commit. Restart queue workers if necessary and verify migration status, attachment access and controlled production smoke flows. Reset the permission cache after migration. Do not retry historical failed mail jobs automatically.

The migration registers the verifier role but does not assign it. Assign intended active Finance users through Admin → Access → Users; do not create production QA accounts or grant financial access by inference.

Recovery should redeploy a compatible prior application revision while retaining additive schema and evidence. Do not run migration rollback: down methods remove payment/audit tables. If database recovery is required, restore a separate cluster/database and reconnect LP alone; retain S3 files and reconcile queue state before resuming reminder delivery.
