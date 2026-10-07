# SOA upload status transitions

Uploading an SOA from Done does not delete the booking. A finished booking previously confirmed as requiring no payment becomes Approved / Unbilled when an SOA is added. It leaves Done until payment is recorded. The page now switches to the relevant tab and a floating banner names the event, billing status, destination tab, and whether the SOA still needs to be emailed.

| Action | Destination | Preserved / updated |
| --- | --- | --- |
| First SOA on a finished no-payment booking | Approved | Approval retained; no-payment confirmation cleared; due date set to upload + 15 days |
| First SOA on an approved upcoming booking | Approved | Record payment available immediately |
| Unpaid replacement already Billed | Billed | Reason and old SOA retained; due date reset; updated SOA awaits email |
| Paid replacement before event ends | Paid | Payment date, proofs, and due date retained |
| Paid replacement after event ends | Done | Payment records and Done classification retained |
| Record payment before event ends | Paid | New proofs appended |
| Record payment after event ends | Done | New proofs appended |
| Remove unpaid SOA | Approved | Billing dates cleared; old no-payment confirmation is not reapplied |

Browser checks used disposable booking #52: missing-file rejection, unsupported-file rejection, Done upload to Approved, payment back to Done, and paid SOA replacement staying in Done. It was soft-deleted after testing. The user's demo #53 was inspected only: it is in Approved with an uploaded SOA and Unbilled status.

Automated checks cover those transitions for individual and recurring dates, PDF/Word formats, the 10 MB limit, blank replacement reason, permissions, pending/rejected/deleted records, a status change while an upload dialog is open, storage failure, and removal. Existing billing tests cover stale queued attachments, duplicate sends, delivery failures, due date reset, and paid reminder suppression.

No new migration is required for these navigation and banner changes. The subsequent release preflight verified live S3 operations and SES delivery using temporary QA objects and two authorized dummy emails; browser click-through confirmed the delivered SOA PDF. Storage failures remain simulated locally. SOA sending remains manual. No deployment was performed.
