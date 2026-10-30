# Private facilities billing attachments

## Prepared locally; not deployed

SOAs and payment proofs use admin-authorized download routes. New uploads and queued billing-email copies use `FACILITY_BILLING_DISK`; the default is the existing private_s3 disk when AWS_BUCKET is configured, otherwise Laravel's private local disk. FACILITY_BILLING_DISK remains an optional override. Public floor plans, gate passes, and resource images keep their existing configuration.

Production requires a durable private disk, for example the existing `secure_s3` disk **after its bucket permissions have been verified**. Do not use the public disk or Cloud's ephemeral local disk for billing. Setting object visibility to private does not override a bucket policy granting public reads: verify anonymous denial on the actual bucket before rollout.

## Production rollout

1. Complete current release acceptance and verify access to the production project.
2. Verify the chosen private bucket permits the app to write/read/copy/delete test objects while an anonymous request returns access denied. Verify the same denial for a Facilities Viewer. Use only dummy files for probes.
3. Set `FACILITY_BILLING_DISK` to the verified private disk. Keep its name stable: paths starting with `facility-billing/` resolve through this setting. Changing the disk later requires another file migration.
4. Deploy the reviewed code. No database schema migration is required. Drain billing emails, stop queue workers, and put the application in maintenance mode before changing legacy references. Retain a current database backup and public-object inventory.
5. Run `php artisan facilities:privatize-billing` to inspect the legacy count. Run with `--apply` only after the destination and backups are verified. It includes active/deleted reservations, proof rows, SOA revisions, and retained email attachment copies. Missing source files stop the migration. It copies and hash-verifies all files before updating references in a database transaction. It saves an inventory under `facility-billing/migrations/` on the private disk and never deletes public originals. A failed preparation can leave private unreferenced copies; retain them for review.
6. Restart workers and restore access. Browser-test admin SOA/proof download, unpaid SOA replacement/removal, appending payment proofs, and a dummy billing email. Confirm viewers and logged-out users cannot download protected files; confirm a proof ID from another reservation returns 404.
7. After explicit approval, remove **only** the original public keys listed in the verified inventory. This is required to close the existing direct-URL exposure. Recheck old URLs anonymously and as a viewer: access must fail. Do not remove the public bucket, floor plans, images, or unrelated project objects.

## Recovery

Original files remain available until the approved cleanup. Before that cleanup, a database backup can restore legacy references. After public originals are removed, restore the matching database/file backups together if recovery is necessary. Do not roll back to application code that cannot read private billing paths. Queue snapshots name their storage disk explicitly.

## Current browser verification

Browser control is available. See docs/facilities-release-2026-10-30.md for current results and remaining production-access checks. Earlier plugin-install diagnostics are historical, not release blockers by themselves.
