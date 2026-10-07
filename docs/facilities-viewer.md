# Facilities Viewer

Assign the `facility.viewer` role to individual assistant accounts using the existing access administration page. Run `php artisan db:seed --class=ResourceRoleSeeder --force` to register the role and its `facilities.reservations.view-approved` and `facilities.reservations.view-pending` permissions in an environment.

This role restricts the entire account, even if another role is also assigned. Only For Approval and Approved reservations, their operational details and authenticated floor-plan downloads, and logout are allowed. Other web pages, Livewire updates, and authenticated API calls are denied. The viewer page uses no interactive management component and never renders SOA or payment information. Each occurrence displays separately in its status tab, sorted by event date. View recurring dates expands all active For Approval and Approved dates in the same series with their operational details; rejected and deleted dates stay hidden.

No schema migration is needed for the role: it uses the existing roles and permissions tables. The unrelated billing-email migration is a separate change.

For local review only, run `php artisan db:seed --class=FacilityViewerDemoSeeder`. Account: `facilities.viewer@example.test`, password: `ViewerLocal2026!`. The demo seeder refuses to run outside the local environment.

Attachment storage configuration remains unchanged. The portal does not disclose billing attachment URLs to viewers, but an existing publicly accessible S3 attachment URL is not made private by this role. Restricting access to already shared public URLs requires a separate storage access change.
