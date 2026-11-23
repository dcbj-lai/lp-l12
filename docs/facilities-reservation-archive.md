# Facilities reservation archive

Manual archive is independent of the existing Done classification (`is_archived` in the model).

- Records filter: Active (not manually archived), Archived, All. Combines with the existing status tabs, search, and date sorting.
- Facility admins can archive Pending, Rejected, Deleted, and Done reservations. Reservations still in Approved cannot be archived, including ended events awaiting billing/payment/completion.
- Archive preserves status, soft-delete state, billing, attachments, and history. Restore from archive clears only the manual archive marker. Restoring a Deleted reservation from archive leaves it Deleted.
- Archived recurring dates display individually. Archiving/deleting one date does not change another date. Archived dates cannot be approved, including through an already-open dialog.
- Viewers do not see manually archived reservations. Approvers cannot archive, unarchive, or permanently delete.
- Permanent deletion is available only to active facility admins for manually archived records. The dialog names the event and requires `DELETE`. Reopening resets confirmation; repeated confirmation is a no-op after success.
- Permanent deletion removes the selected reservation, its relationships, access links, billing/payment/edit history, and unshared attachment files. References from other reservations and their billing/payment/edit history protect shared files. Queued billing emails block deletion. A calendar-removal failure blocks database deletion. File-cleanup errors after deletion produce a warning and are logged.
- New additive migration: `2026_11_23_000000_add_manual_archive_to_facility_reservations.php`. No backfill or scheduler is required.

See `facilities-archive-release-nov23.md` for release verification. The release also includes the booking-field validation fix.
