# Facilities archive release — November 23, 2026

## Changes
- Manual archive with Active / Archived / All filter combined with status tabs and search.
- Eligible: Pending, Rejected, Deleted, Done. Active Approved reservations cannot archive. Restore keeps original status and deleted state.
- Admin-only permanent deletion of archived records with event-specific typed confirmation; preserves shared attachments and other recurring dates. Queued billing and calendar-removal failures block deletion.
- Hide unavailable soft-delete controls in individual and recurring cards. Server-side SOA protection retained.
- Corrected booking fields revalidate on blur/change, clearing stale required errors.
- Fixed archived/deleted filter overlap found during acceptance testing.

## Verification
- Full isolated suite: 329 passed / 1,968 assertions. SQLite in-memory, no network, fake mail; built Vite manifest included.
- Vite production build passed; Blade compilation passed.
- PostgreSQL rehearsal: separate local database `lp_archive_release_nov23`, restored from local data. New migration ran forward, second run had nothing pending. Counts/hashes of all original rows unchanged across 13 reservation/history tables. This is not a production-copy rehearsal.
- Browser evidence and detailed matrix are in ignored `storage/app/release-preflight/`.
- Authorized local verified-payment reservation #126 permanently deleted; submission/proofs removed, other QA records retained. No production reservations changed during preflight.

## Deployment
- New additive migration: `2026_11_23_000000_add_manual_archive_to_facility_reservations`.
- Cloud checked today: main push-to-deploy enabled, existing build commands, deploy `php artisan migrate --force`. Current pre-release production: `2a35eea`.
- Recovery checked today: PostgreSQL cluster point-in-time recovery enabled, seven days. Shared cluster also hosts other apps; no changes to those databases or connections.
- No configuration, role seeding, queue/scheduler, or email automation changes. Local session-cookie isolation remains in ignored `.env` only.
- Recovery: redeploy previous compatible application revision `2a35eea`, retaining additive archive columns and all files. Do not rollback migrations or retry old email jobs.
