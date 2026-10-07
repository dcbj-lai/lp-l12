# Multiple rooms per event

The booking form adds rooms from a floor-grouped selector and shows removable selected-room chips. Every recurring occurrence receives the selected room assignments. Schedule, setup, attachments, billing, and equipment belong to the occurrence; equipment is not multiplied by the number of rooms.

Migration `2026_10_06_000001_add_reservation_rooms` creates `resource_reservation_rooms` and backfills every existing non-null primary room, including soft-deleted reservations. It retains `resource_id` for compatibility. Deploy forward with `php artisan migrate --force`; do not roll back to recover a release, because dropping the pivot loses additional room assignments.

Creation accepts overlaps for review. Approval checks all rooms and equipment within the existing transaction/resource-lock flow. Admin edits to approved bookings remain approved and must pass the same conflict check. Resource availability counts additional rooms. Soft deletion releases all rooms, and restoration preserves room assignments while returning the occurrence to pending.

Admin cards, recurring dialogs, viewer cards, reservation emails, billing emails, and Google Calendar location/description show all rooms. Manual edit emails list before/after room names. Existing older edit-history snapshots are normalized from their primary room before formatting the email.

API clients may send `room_ids` (a nonempty array of distinct room IDs) or the legacy single `resource_id`. Responses retain `resource` and add `rooms`. Partial edits without either field preserve the current rooms; a legacy `resource_id` edit explicitly changes the booking to one room.

Local browser acceptance created and approved a two-date, two-room series, changed the rooms on one approved occurrence, submitted a secondary-room-only overlap, checked the blocking admin dialog, and verified only approved occurrences appeared for the viewer. Test reservations are cleaned up through the admin UI.
