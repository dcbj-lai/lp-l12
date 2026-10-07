# Reservation billing tabs and Done

The admin tabs are All, For Approval, Approved, Billed, Paid, Done, Rejected, and Deleted. Billed and Paid show approved, non-archived occurrences with that billing status. Approved includes non-archived approved occurrences. Done shows individual dates, including recurring bookings, sorted by event date.

An approved event automatically appears in Done once its end time has passed and billing status is Paid. Billed but unpaid events remain active so outstanding payments remain visible. This is calculated when the page is rendered and refreshed every minute; it requires no scheduler and preserves approval and billing status.

For no-payment events, an admin can use Confirm finished after the end time. Confirmation requires an approved, unbilled booking with no SOA, and records the admin and confirmation time. Adding an SOA or billing later removes the no-payment event from Done until paid. Restoring a deleted booking clears the manual finish confirmation and returns it to For Approval.

Migration `2026_10_07_000000_add_finished_confirmation_to_reservations` adds nullable confirmation time and user fields. Run forward during deployment. No existing approval or payment data is changed by the migration.

All reservation delete controls remain disabled while an SOA exists, with the tooltip “SOA is uploaded.” Backend and API guards also apply regardless of approval status. Approval emails display an optional Approval note, including multiline text.
