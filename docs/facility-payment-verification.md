# Requester payment proof and finance verification

Local implementation, November 3, 2026. No partial payments or production changes.

SOA and reminder emails include a 90-day private upload link. The link is scoped to one reservation, its current SOA path and requester email. Only its hash is stored in the payment-link table; the raw URL is not retained in billing email snapshots. Changed SOAs/recipients invalidate old links. Guest pages use no-store/no-referrer/noindex and normal CSRF protection, throttling, and PDF/image validation (10 files, 10 MB each).

Requesters supply the payment date, bank reference, files and confirmation that the full amount was paid. This creates a pending submission; it does not set Paid or add unverified evidence to the confirmed proof list. A reservation lock prevents duplicate pending submissions. Payment reminders pause during pending review. Existing admin Record payment cannot bypass a pending finance review.

Migration `2026_11_03_000000_add_facility_payment_verification` adds payment links and submissions and the `facility.payment-verifier` role. Its nullable payment_verifier_id column was applied in the initial local prototype and is retained for compatibility; it is no longer used to select or restrict reviewers. The migration does not assign roles to existing users or change old payments. Apply forward; do not roll back payment evidence after use.

Assign the role through Admin → Access → Users using the existing role editor. It grants payment review, not facilities booking approval, resource management or billing configuration. Existing roles remain effective. All active users with this role share the payment review inbox and can review/confirm any submission. New-proof notifications go to all active role holders. There is no per-reservation person selector or assignment endpoint, and previous selections are ignored. Facilities admins can view evidence but require the verifier role to confirm. All finance actions require login and an active account; confirmation retains the actual reviewer and timestamp.

Finance inbox: `/resources/payment-reviews`. Review shows the current SOA, privately served submitted proofs, date and reference. Confirmation requires a full-payment checkbox and verifies the current reservation/SOA under lock; it retains proof metadata and sets Paid once, records reviewer/time and queues the existing payment-received email. Ended paid bookings use existing Done rules; upcoming paid bookings remain Approved → Paid. A second confirmation cannot append proofs or send another receipt.

Request correction requires a note and emails a new upload link. Old evidence stays retained, the requester sees the note, and resubmission creates a new history row. Replacing/removing an SOA or changing recipient supersedes pending proofs; those proofs cannot pay the replacement. Deleted/rejected bookings, paid bookings, pending cancellation reviews, expired tokens and users without the verifier role are blocked. Files have no public URLs. Email dispatch failures are logged without undoing saved submissions or confirmed payments.

Local acceptance uses log mail only. No real requester emails or finance roles were changed in production. Before release: run forward migrations on an isolated copy, assign the verifier role to the intended users, verify private storage/queue and real delivery with separately authorized test recipients, then browser-test guest upload → correction → resubmission → finance confirmation.

## Local verification — November 3

- Complete clean release suite: 298 passed, 1,702 assertions, zero warnings/errors/failures (in-memory SQLite, fake mail, isolated Linux test snapshot).
- Vite production build and Blade compilation passed. The additive local migration applied and its repeat run was a no-op.
- Chrome acceptance on disposable reservation #99: proof upload remained Billed/pending; correction required a note; requester saw the note and resubmitted; finance confirmation required full-payment acknowledgment; confirmation changed the booking and guest page to Paid while retaining both submission histories.
- Verifier-only account received 403 for Resources and Reservations. Facilities-admin account could view the review page without receiving finance confirmation privileges. Automated coverage also checked role restrictions, foreign proof access, stale/expired links, duplicate uploads/confirmations, invalid files/dates, private SOA download, and billing-email links without raw-token retention.
- Local demo #99 and its dummy files are retained for inspection. Email remains log-only; no queue worker or scheduler was started. The existing original checkout and unrelated Docker applications were preserved.
- Ignored evidence: `storage/app/release-preflight/payment-verification-clean.log` and `.xml`. Local runtime: `lp-payment-verification-local` on loopback port 8005; stop with `docker stop lp-payment-verification-local`.

## Requester email actions — November 3

Approval, admin detail updates, SOA/updated SOA, all three reminder types, proof-correction requests, and payment receipts now share one action policy. A future active reservation offers View / Cancel reservation; started/finished reservations and pending cancellation reviews offer View reservation when its link remains valid. Rejected/deleted/cancelled events have no cancellation action. A declined cancellation can offer cancellation again if the reservation remains eligible.

Upload proof of payment is shown only for an approved, unpaid reservation with an SOA, no pending cancellation review, and no pending finance submission. Pending submissions show Payment verification pending instead; paid receipts never request another upload. Existing pending-request acknowledgments retain their cancellation link, without a payment action.

Requester links are suppressed for internal finance alerts, mismatched recipients and messages copied to other recipients. Queued messages build their actions from current reservation state. Raw action URLs are not retained in billing snapshots. Private SOA/payment proof paths are no longer converted into public-storage links in detail-update emails.

Twelve rendered-template samples were explicitly authorized and sent to paolo.ylag@life.edu.ph through connected Gmail; all returned INBOX delivery labels. These demonstrate the actual rendered templates with local magic links and dummy SOAs, not the production mail transport. Local application mail remains log-only. Samples use disposable local reservations #100–106 and remain intact for the requester to try. Browser checks opened both action buttons on sample 02 and verified they resolve to reservation #101 without cancelling or uploading anything.

Ignored evidence: email-actions-delivery-nov3.json (message IDs), email-actions-test-payloads.json (exact samples), email-actions-01.html through -12.html. Local preview: /build/email-actions-preview.html. The preview is an ignored build artifact, not a production route.

## Shared finance role — November 3

Removed person-specific selection from Create SOA and payment review, the assignment endpoint, assignment-based inbox/file restrictions, and assignment-based notifications. Previous column values are ignored without dropping the already-applied local column. No additional migration is required for this behavior change.

Chrome acceptance: second local verifier accessed and confirmed disposable booking #107 despite its old selection of the first verifier. Confirmation marked it Paid and retained reviewed_by/reviewed_at. Emailed demos #100–106 were not changed. Notifications remained fake/log-only; no real emails were sent in this pass.

Final complete isolated suite: 305 passed, 1,758 assertions, zero warnings/errors/failures. Tests cover access for multiple active role holders, exclusion of inactive/non-role users, notification to every active role holder, role revocation, and idempotent confirmation. Blade compilation passed. Screenshot: shared-finance-role-nov3.png in the original checkout's ignored release-preflight folder. Local only; nothing pushed or deployed.

## Release preparation — November 6

- Latest complete isolated suite: 306 tests, 1,770 assertions passed (62.47 seconds). Includes event-date searches by ISO date, US numeric date, named date and month/year; multi-day overlap and invalid-date cases. Admin and viewer use the same scoped search. Dates refer to event schedules, not submission dates.
- Vite production build passed. Main was fetched and the feature branch fast-forwarded to the existing production merge `bddfb57`; the merge introduced no content delta.
- Chrome click-through on disposable local reservation #105: missing correction note blocked; correction note displayed to the requester; two dummy files uploaded through the file chooser; pending state remained unpaid and removed the upload form; missing full-payment acknowledgment blocked; valid finance confirmation changed both pages to Paid and retained the earlier correction and all files. The guest page also opened signed out. Verifier-only Resources and Reservations access returned 403. The original local admin login was restored. No real emails were sent and the queue/scheduler remained stopped. User-submitted proof on #101 was not modified.
- PostgreSQL rehearsal used a separate database `lp_preflight_nov6`, restored from the local LP database. Only the isolated copy was returned to the pre-feature schema. The new migration ran forward (204.90 ms), followed by a no-op repeat. Counts remained 32 reservations (3 deleted), 5 edits, 12 confirmed proofs, 3 SOA revisions and 1 billing-email row. SHA-256 of every original reservation column was unchanged. This is not a fresh production-copy rehearsal and does not verify production's exact current schema.
- Evidence is ignored under `storage/app/release-preflight`: `payment-verification-clean.log/.xml`, `payment-migration-nov6.json`, and `finance-acceptance-nov6.png`. Do not commit database dumps, tokens, demo files or credentials.

Remaining release actions require production access/approval: verify a current recoverable backup, rehearse against a fresh isolated production export if its schema has changed, confirm `FACILITY_BILLING_DISK` resolves to durable private storage (`private_s3` uses `AWS_BUCKET`), and verify existing queue processing. Apply the additive migration forward and restart workers during the approved release. Assign `facility.payment-verifier` to the intended active users through the existing access editor; assignment is deliberately not performed by migration. No new scheduler is required for proof review. Test delivery using separately authorized recipients and smoke-test the deployed flow after release. Do not roll back the migration after evidence has been collected; prefer the compatible previous application revision while retaining additive tables/history.

Reservation card layout and removal of the cancellation disclosure are proposals only. Neither presentation change is included in this release preparation.
