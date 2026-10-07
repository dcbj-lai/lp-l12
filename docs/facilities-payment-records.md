# Payment proofs and SOA corrections

Paid bookings can replace their SOA. Every replacement requires a reason and retains the previous SOA in the revision history with the admin and timestamp. For paid bookings, Paid status, payment proofs, date paid, and due date remain unchanged. Paid SOAs cannot be removed, and billing emails and reminders remain disabled after payment.

The Record payment button has the same label before and after payment. Its dialog lists previously recorded proofs and accepts up to 10 additional PDF or image files per save, each up to 10 MB. Saving appends files rather than replacing them. Each proof records its original filename, date paid, recording admin, and timestamp. Existing files have no removal control. Recording an additional batch of proofs sends the requester a payment confirmation; saving only a date correction does not resend it.

Migration 2026_10_07_000001_add_facility_payment_proofs_and_soa_revisions creates both history tables and copies legacy payment_proof_path references, including soft-deleted bookings. It does not move or delete existing attachment files. The legacy primary proof field remains available for compatibility. Apply migrations forward before using the updated views.

Verified locally through browser clicks: paid SOA replacement is enabled, an empty reason is rejected, a reason is saved, and two uploaded payment proofs appear alongside the original. Paid status and due date are preserved. The disposable booking was soft-deleted after testing. Production was not changed.
