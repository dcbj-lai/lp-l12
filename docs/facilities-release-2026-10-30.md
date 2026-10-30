# Facilities release checks — October 30, 2026

## Scope

Release checkout: codex/facilities-release-oct30, based on main 4df5931. Includes requester cancellation links/date selection, generated SOAs and configurable staff/OT rates, private billing download/storage support, and Approved billing subfilters / Done classification. Excludes the separate clinic inventory and authentication changes in the original working checkout. Five additive facilities migrations dated October 27 are included. Demo seeders and local data/credentials are excluded.

## Verification completed

- Original working checkout: full 316-test run completed; 313 passed and three failed because assertions still expected earliest-first sorting or the removed Approval dashboard heading. All three corrected tests passed on rerun (13 assertions). No non-facilities failures were reported in that run.
- Isolated release frontend build passed. An early release-only test run was invalidated by absent local built assets; those assets have since been generated. The clean packaged release-only run passed all 287 tests / 1,629 assertions in 64.181 seconds; Blade template compilation passed. Native Linux execution avoided the Windows dependency-mount bottleneck.
- Browser: required-field floating errors; three-date booking with floor plan/equipment; schedule check; bulk approval; approved edit retains status; closing edit does not open approval; finished unpaid remains Approved/Unbilled; generated SOA with OT rates; one-page Crimson Pro preview and correct PHP 1,674.92 total; replacement reason required and revision retained; local log-only SOA job changes status to Billed; multiple payment proofs append; finished paid moves to Done; SOA/deletion protection; protected admin proof download; selected date deletion and individual restore without affecting adjacent date.
- Viewer: current/future-only Approved and For Approval lists, no admin controls, upload badges without financial links; direct SOA and Resources access return 403. Viewer disclosure activation was unreliable through browser automation; experimental UI changes were reverted. Guest cancellation selection was checked without cancelling the existing emailed demo.
- No real requester email was sent. Local mail is log, the standard SOA job was processed only for the disposable local QA booking.
- PostgreSQL 17 forward rehearsal: restored the retained October 7 production export into an isolated local container/database. All pending migrations passed; second run said Nothing to migrate. Counts and hashes of all original reservation fields were unchanged: 117 reservations, 11 soft-delete markers, four edit-history records. This is NOT a fresh production export; current production state still needs checking.

## Deployment gates still open

- Current Laravel Cloud session opens a personal organization onboarding screen, not the LIFE production project. Correct organization/account access is needed.
- Verify a fresh recoverable production backup and current migration state; use only forward migrations. Never migrate:rollback for recovery because history down methods drop tables/columns.
- Verify the durable private S3 disk and set FACILITY_BILLING_DISK accordingly before deployment. The production upload guard rejects local/unverified disks. Existing floor plans/gate passes keep their current disk. Do not delete legacy public originals or change bucket policies without the separate rollout review.
- Confirm queue process restart, PHP/GD/font rendering and APP_URL=https://lp.life.edu.ph for email cancellation links. No new completion polling/scheduler is introduced.
- Exact release regression is complete. Production configuration verification and post-deployment smoke checks remain required before marking this deployed.

## Local QA data

Created #96–#98, [Release QA Oct30] Three-date acceptance. #96 is a paid past occurrence with retained dummy SOA revisions and three proofs, intentionally protected from deletion. #97 is approved/upcoming; #98 was deleted and restored to For Approval during testing. No production test bookings were created.

## Recovery

Record the prior deployed commit and backup point. Prefer a compatible prior application revision retaining additive schema and billing files. After private billing paths are used, the application revision must support those paths. Restore only Life Portal to a separate recovered database if necessary; other projects sharing infrastructure must remain unchanged. Never rerun all failed billing jobs automatically.

