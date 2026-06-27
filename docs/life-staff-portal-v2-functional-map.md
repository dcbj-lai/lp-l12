# Life Staff Portal v2 Functional Map

Date: 2026-06-02

This document maps the current lp-l12 Laravel/Blade/Livewire portal into a cleaner lp-l13 direction: React web, React Native mobile, and a Laravel 13 JSON API backend.

The goal is not a one-for-one clone. The goal is to preserve the real business workflows, remove accidental complexity, and define a v2 shape that is easier to test, secure, operate, and extend. v2 will not carry over the lp-l12 visual design.

Data migration is not a primary goal because v1 data is not considered valuable for v2. The only likely exception is leave credit balances, which may need a small import or initialization path.

Language and context note: v2 should use Philippine school/workplace wording. In particular, use "Leave" and "leave credits" in the user experience.

## Source Inventory

Old portal inspected:

- `C:\Development\lp-l12\routes\web.php`
- `C:\Development\lp-l12\routes\api.php`
- `C:\Development\lp-l12\app\Http\Controllers`
- `C:\Development\lp-l12\app\Livewire`
- `C:\Development\lp-l12\app\Models`
- `C:\Development\lp-l12\app\Services`
- `C:\Development\lp-l12\database\migrations`
- `C:\Development\lp-l12\resources\views\components\layouts\app\sidebar.blade.php`

Brand guide inspected:

- `C:\Work\Pitch To College\Brand_Guidelines_LCI.pdf`

New portal scaffold inspected:

- `C:\Development\lp-l13\README.md`
- `C:\Development\lp-l13\api\routes\api.php`
- `C:\Development\lp-l13\api\app\Http\Controllers\Api`
- `C:\Development\lp-l13\api\app\Models`
- `C:\Development\lp-l13\api\database\migrations`
- `C:\Development\lp-l13\api\tests\Feature\PortalApiTest.php`
- `C:\Development\lp-l13\mobile\App.tsx`
- `C:\Development\lp-l13\mobile\src\screens`

## Current lp-l13 Coverage

lp-l13 currently has a focused API and mobile scaffold:

- Google SSO and local dev login.
- Sanctum bearer token auth.
- Mobile tab navigation for Attendance, Steps, Leave, and Profile.
- Attendance history, today, clock-in, clock-out, optional geolocation.
- Steps log, daily upsert, goal, source, weekly summary.
- Leave requests with basic applicant create/list/cancel and admin decide.
- Feature tests for auth, attendance, steps, and leave.

This is a good foundation, but it only covers a small subset of lp-l12. It also uses `is_admin`, which should be replaced before the portal grows.

## Recommended v2 Principles

Use these as the baseline when porting functionality:

- Laravel API owns all business rules. React and React Native should not calculate credits, approval rights, conflict windows, payout values, or visitor states.
- Controllers stay thin. Use Form Requests, Policies, API Resources, Actions/Services, and queued Jobs.
- Use one authorization model. Replace lp-l12's mixed `legacy_roles`, gates, and Spatie roles with Spatie roles/permissions plus Laravel policies.
- Use state machines for approval-heavy workflows. Attendance edits, leave requests, visitor visits, reservations, payroll runs, and event publication all need explicit allowed transitions.
- Treat side effects as async jobs. Emails, Google Calendar sync, PDFs, S3 cleanup, OpenAI email generation, and report exports should queue and log independently.
- Add audit logs for sensitive operations. HR, payroll, clinic, guidance, visitor, access, and manual attendance changes need actor, timestamp, before/after, and reason.
- Use signed/private files. Request proofs, event attachments, clinic photos, resource booking attachments, and payslip PDFs should never be raw public paths.
- Use Asia/Manila consistently for human-facing date/time logic, while storing UTC timestamps.
- Prefer ledgers over destructive balance mutation. Credits, payroll adjustments, and approvals should have traceable history.
- Expose versioned API contracts, ideally `/api/v1`, with OpenAPI or generated TypeScript types for React and React Native.

## Product Areas

### 1. Identity, Profile, and Access

Current lp-l12:

- Google login and password fallback.
- User management by P&C/admin.
- Department, supervisor, rank, position, payroll status, monthly rate, bank account, package.
- Profile fields: preferred name, avatar, phones, address, birthdate, hire date, emergency contact, dietary preference, medical notes.
- Legacy JSON roles plus Spatie roles/permissions.
- Access admin screens for users, roles, and permissions.
- Public business card and vCard by slug.

Current lp-l13:

- Google SSO, dev login, Sanctum tokens.
- Basic user fields: name, email, google_id, avatar, employee_no, department, `is_admin`.

v2 recommendation:

- Replace `is_admin` with roles and permissions before adding more modules.
- Split user data into `users`, `employee_profiles`, `departments`, and optional `public_profiles`.
- Keep compensation fields behind dedicated payroll/finance permissions.
- Add profile completeness rules for event registration and emergency contact fields.
- Support access administration in React web, not mobile.
- Keep business card/vCard as a public web route served by API-backed profile data.

Primary v2 clients:

- React web: full profile/admin/access management.
- React Native: self profile, avatar, emergency contact, public card link.

### 2. Staff Dashboard and Notifications

Current lp-l12:

- Dashboard cards and widgets.
- Calendar widget, events card, verse/celebrations/countdown widgets.
- Notifications nav placeholder.
- About page, knowledge base link, app launcher.
- Directory and org chart placeholders.

Current lp-l13:

- No web dashboard yet.

v2 recommendation:

- Build a real notification center as a foundation service.
- Dashboard should be role-aware and task-oriented: pending approvals, today attendance, request balances, upcoming events, visitor approvals, reservations.
- Directory and org chart can follow once employee profile and reporting lines are stable.

Primary v2 clients:

- React web: dashboard, admin summaries, operational queues.
- React Native: notifications, today summary, quick actions.

### 3. Attendance

Current lp-l12:

- Staff self check-in/check-out.
- My attendance history.
- QR check-in/check-out with expiring QR tokens.
- Acad admin QR generation and QR stop.
- Online days so virtual check-in can be allowed.
- HR/P&C attendance list with search/filter/sort.
- HR/P&C manual create/edit.
- Super admin permanent delete.
- Hours worked calculation including overnight shift handling.

Current lp-l13:

- Staff clock-in/clock-out with optional latitude/longitude and notes.
- Today's record and paginated history.
- Basic status of `present` or `late`.

v2 recommendation:

- Keep mobile attendance as a first-class workflow.
- Add attendance modes: onsite, virtual, QR, manual, imported.
- Add `attendance_sessions` or `attendance_events` if multiple clock pairs may ever be needed; otherwise keep daily record plus audit log.
- Add QR session API for admin-generated check-in/check-out sessions.
- Add online-work-day policy table for virtual attendance exceptions.
- Add admin correction workflow with reason and audit log.
- Add export/report endpoints for HR.
- Formalize late rules in configuration rather than hardcoded time checks.

Primary v2 clients:

- React Native: clock-in/out, QR scan, history.
- React web: staff history, HR admin, QR generation, online days, reports.

### 4. Requests, Leave, WFH, LWOP, and Offset

Current lp-l12:

- Request types: Leave, WFH, LWOP, offset flag.
- Weekday-only day calculation.
- Half-day support via `end_date_type`: full, half-am-off, half-pm-off.
- Weekend-only guard for short requests.
- Overlap prevention.
- Request credits for Leave and WFH.
- Pending-request outstanding balance check.
- Manager/HR approval/rejection.
- HR global view with filters and sorting.
- Staff edit/cancel while pending.
- Leave credit initialization from organization settings.
- Offset proof upload to private S3.
- Google Calendar sync on approval and deletion on rejection.
- Email notifications to supervisor, HR, and requester.

Current lp-l13:

- Basic leave request scaffold exists, but its labels should be normalized to Philippine-context Leave terminology.
- Date range, half-day boolean, reason, status.
- Admin approve/reject via `is_admin`.
- Calendar sync for approved leave.

v2 recommendation:

- Rename the module to Staff Requests or Schedule Requests, not only Leave, because lp-l12 includes WFH, LWOP, and Offset.
- Use `request_types` with flags: consumes_credit, requires_document, counts_weekdays_only, requires_calendar_sync, approver_strategy.
- Replace direct balance mutation with `request_credit_ledger`: grant, consume, restore, manual_adjustment.
- Preserve pending edits/cancel and manager direct-report visibility.
- Add HR override and audit trail.
- Add request attachments table for offset proof and future documents.
- Use a proper approval action history: submitted, updated, cancelled, approved, rejected, restored.
- Put Google Calendar sync behind a queued job and store sync status/error.

Primary v2 clients:

- React Native: submit/edit/cancel own requests, view credits, receive decisions.
- React web: staff self-service, manager approvals, HR administration, settings.

### 5. Payroll and Payslips

Current lp-l12:

- Finance payout cycles.
- Control number generation.
- Pay period and payout date setup.
- Payroll generation for users with `payroll_on`.
- Adjustments per user, cycle, mode, amount, effective date.
- Package deductions.
- Basic BIR tax calculation.
- Payslip creation with JSON adjustment snapshot.
- Staff payslip list/detail/download PDF.
- Finance payroll dashboard.

Current lp-l13:

- Not present.

v2 recommendation:

- Treat payroll as web-only for finance plus read-only mobile payslips for staff.
- Use immutable payroll runs after generation; corrections should create adjustment records or rerun drafts before lock.
- Keep a payslip line-item table instead of only JSON where possible; keep JSON snapshots for rendering history if needed.
- Move tax/package logic into tested payroll calculators.
- Add approval/lock/dispatch states.
- Add finance audit log and export endpoints.
- Keep compensation fields restricted by permissions and hidden from normal user serialization.

Primary v2 clients:

- React web: finance payroll operations.
- React Native: payslip list, detail, PDF download.

### 6. Steps and Wellness

Current lp-l12:

- Manual daily step logging.
- Edit/delete own step logs.
- Monthly total.
- Leaderboard.
- Livewire leaderboard/log components.

Current lp-l13:

- Step upsert by date.
- Goal and source fields.
- Summary with today, week total, week average, all-time total.
- Mobile has Health Connect related dependencies.

v2 recommendation:

- Keep the lp-l13 upsert behavior; it is better than rejecting a second entry.
- Add leaderboard API with date range and privacy controls.
- Add source confidence: manual, healthconnect, healthkit, googlefit.
- Consider challenges/campaigns as a separate model rather than hardcoded leaderboard logic.

Primary v2 clients:

- React Native: daily steps, sync source, progress.
- React web: leaderboard and wellness admin.

### 7. Visitors and Front Desk

Current lp-l12:

- Public visitor OTP start flow.
- Visitor details form after OTP.
- Host selection.
- Front desk list with search/filter/sort.
- Endorse visitor to host by email.
- Host approve/decline.
- Front desk check-in/check-out.
- My visitors for host.
- Pre-approved visits by host.
- Batch pre-approval with CSV upload.
- Webhook for pre-approved visitors with HMAC signature.
- Batch cancellation.
- QR validation for pre-approved visits.
- CSV export.
- Cleanup of incomplete pending records.

Current lp-l13:

- Not present.

v2 recommendation:

- Model visits as a state machine: draft_otp, submitted, endorsed, approved, declined, checked_in, checked_out, cancelled, expired.
- Separate `visitor_batches`, `visitor_visits`, and possibly `visitor_guests`.
- Use OTP expiry and resend limits.
- Use signed QR payloads or visit tokens rather than exposing raw IDs.
- Host approvals should be available in mobile notifications and web.
- Front desk web UI should be dense, searchable, and scanner-friendly.
- Keep webhook ingestion but move signature verification into middleware.

Primary v2 clients:

- Public web: visitor registration and pre-approved QR page.
- React web: front desk console and host visitor view.
- React Native: host approval notifications and visitor details.

### 8. Guidance

Current lp-l12:

- Client/student import via CSV.
- Client search/list/detail.
- Consultation check-in.
- Active consultation completion.
- Session details: current teacher, teacher emails, session type, risk assessment, issue/concern, intervention, remarks.
- Outcome: resume class or go home, with fetcher/self-approved details.
- Teacher/academic guidance emails.
- Archive and edit completed session notes.
- Auto-cleanup of stale active consultations.

Current lp-l13:

- Not present.

v2 recommendation:

- Keep guidance primarily web-only because it is sensitive and operational.
- Add explicit privacy permissions and audit logs.
- Separate check-in event, consultation notes, and outcome notifications.
- Use soft deletes/archive rather than hard delete.
- Consider shared person/student directory with clinic, but avoid merging clinical and guidance records unless policy approves it.

Primary v2 clients:

- React web: guidance console.
- React Native: likely no initial scope, except staff notifications if needed.

### 9. Clinic

Current lp-l12:

- Patient list split by student/staff.
- Patient profile edit.
- Clinic consultation check-in and completion.
- Vitals: BP, pulse, respiratory rate, temperature, O2 saturation, pain rating.
- Complaint, classification, assessment, treatment.
- Medicines and supplies JSON rows.
- Photo attachments in private S3.
- Student outcome workflow: resume class or go home.
- Teacher/academic/clinic emails.
- Consultation edit/archive.
- Clinic CSV import.

Current lp-l13:

- Not present.

v2 recommendation:

- Treat as a sensitive health module with strict role isolation.
- Add attachment metadata table rather than storing photo paths only as JSON.
- Add audit logs for view/update/delete, not only writes.
- Keep medicine/supply usage as structured line items if inventory tracking is planned.
- Use private file download endpoints with signed, short-lived URLs.

Primary v2 clients:

- React web: clinic console.
- React Native: no initial scope unless clinic staff need mobile check-in.

### 10. Facilities and Resource Booking

Current lp-l12:

- Resource catalog for rooms and equipment.
- Resource admin create/update/delete.
- Public resource booking.
- Authenticated reservations index.
- Reservation items for equipment.
- Availability conflict checks.
- Approve, reject, revoke, delete reservation.
- Approval/rejection notes.
- Attachments.
- Email notifications.
- Google Calendar event creation/deletion.

Current lp-l13:

- Not present.

v2 recommendation:

- Model resources, reservations, reservation_items, reservation_actions, and attachments.
- Add transaction-safe conflict checks with database constraints or locking where possible.
- Support public requester email and authenticated user booking.
- Add calendar sync status and retry.
- Add reservation calendar views by resource and by requester.

Primary v2 clients:

- Public web: booking form.
- React web: resource admin and reservation approver views.
- React Native: optional personal reservation view after core staff features.

### 11. Events and P&C Campaigns

Current lp-l12:

- Staff events list/detail.
- Event management with draft/published states.
- Attachments in private S3.
- Custom registration fields and instructions.
- Invite-on-publish by email.
- Staff RSVP attending/not attending.
- Event registration stores guest count, shirt size, custom answers, responded_at.
- Registrants list, CSV export, PDF export.
- Event-specific profile fields reuse normalized user profile data.

Current lp-l13:

- Not present.

v2 recommendation:

- Add event forms as a schema-driven feature: custom fields, validation, response storage.
- Keep event attachment access private and permission-aware.
- Add event invitation delivery status.
- Add optional attendance/check-in later if events need on-site tracking.
- Staff profile completeness should be prompted before RSVP.

Primary v2 clients:

- React web: event management and reports.
- React Native: event list, RSVP, profile completion.

### 12. Communications

Current lp-l12:

- Holiday campaign page.
- HTML campaign builder.
- Asset upload and processing.
- OpenAI-assisted HTML generation/enhancement.
- HTML sanitization/personalization.
- Queued campaign send.

Current lp-l13:

- Not present.

v2 recommendation:

- Keep this outside MVP unless it is operationally urgent.
- Use templates, preview, test-send, recipient segments, approval, and send logs.
- Treat OpenAI generation as an optional assistant, never as unsanitized output.

Primary v2 clients:

- React web only.

### 13. Public Cards and Directory

Current lp-l12:

- Public `/card/{slug}` and `/card/{slug}/vcard`.
- Directory/org chart nav placeholders.

Current lp-l13:

- Not present.

v2 recommendation:

- Build public profile separately from internal employee profile.
- Allow users/admins to control what fields are public.
- Generate vCard from stable public profile data.
- Directory and org chart should use normalized departments, positions, and supervisor reporting lines.

Primary v2 clients:

- Public web: staff card and vCard.
- React web/mobile: directory and org chart.

## Proposed Backend Shape

Use bounded API namespaces:

- `/api/v1/auth`
- `/api/v1/me`
- `/api/v1/users`
- `/api/v1/access`
- `/api/v1/attendance`
- `/api/v1/staff-requests`
- `/api/v1/payroll`
- `/api/v1/steps`
- `/api/v1/visitors`
- `/api/v1/guidance`
- `/api/v1/clinic`
- `/api/v1/resources`
- `/api/v1/events`
- `/api/v1/notifications`
- `/api/v1/files`

Use public web/API surfaces for:

- Visitor registration.
- Public resource booking.
- Public staff cards.
- Webhooks.

Suggested Laravel organization:

- `app/Actions/<Domain>` for business operations.
- `app/Policies` for authorization.
- `app/Http/Requests/Api/V1` for validation.
- `app/Http/Resources/Api/V1` for serialization.
- `app/Jobs` for email, calendar, PDF, file cleanup, imports, OpenAI tasks.
- `app/Events` and `app/Listeners` for domain events.
- `app/Enums` for statuses, request types, attendance modes, visitor states.
- `app/Services` only for infrastructure integrations and reusable domain services.

## Proposed Data Model Improvements

Foundation:

- `users`
- `employee_profiles`
- `departments`
- `positions` or simple profile position field
- `reporting_lines` if supervisor history matters
- Spatie `roles`, `permissions`, model role tables
- `notifications`
- `audit_logs`
- `files`

Attendance:

- `attendance_records`
- `attendance_audits` or central `audit_logs`
- `attendance_qr_sessions`
- `online_work_days`

Requests:

- `staff_requests`
- `request_types`
- `request_attachments`
- `request_approval_actions`
- `request_credit_balances`
- `request_credit_ledger`
- `calendar_syncs`

Payroll:

- `payroll_runs`
- `payslips`
- `payslip_lines`
- `adjustments`
- `adjustment_templates`

Visitors:

- `visitor_batches`
- `visitor_visits`
- `visitor_approvals`
- `visitor_tokens`

Guidance:

- `guidance_clients`
- `guidance_consultations`
- `guidance_consultation_actions`

Clinic:

- `clinic_patients`
- `clinic_consultations`
- `clinic_consultation_medicines`
- `clinic_consultation_supplies`
- `clinic_attachments`

Resources:

- `resources`
- `resource_reservations`
- `resource_reservation_items`
- `resource_reservation_actions`

Events:

- `events`
- `event_attachments`
- `event_registration_fields`
- `event_registrations`
- `event_registration_answers`

## Frontend Split

React web should own operational/admin workflows:

- P&C users, attendance admin, request admin, leave settings.
- Manager approvals.
- Finance payroll.
- Front desk visitor console.
- Guidance and clinic.
- Facilities/resource administration.
- Events management and reports.
- Access administration.
- Communications/campaigns.

React Native should own daily staff workflows:

- Login/profile.
- Attendance clock-in/out and QR scan.
- Staff requests and approvals if user is a manager.
- Steps and wellness.
- Notifications.
- Events and RSVP.
- Payslip viewing.
- Host visitor approvals.

Public web should own unauthenticated workflows:

- Visitor registration.
- Pre-approved visitor QR validation.
- Resource booking.
- Public staff card/vCard.

## Suggested Build Order

Phase 0: foundation

- Replace `is_admin` with roles/permissions and policies.
- Add `/api/v1` structure.
- Add API Resources, Form Requests, and typed client contracts.
- Add notifications and audit logs.
- Decide React web scaffold location and routing.

Phase 1: improve current lp-l13 core

- Auth/profile parity with lp-l12 important fields.
- Attendance with modes, QR, online days, admin corrections, reports.
- Staff Requests with Leave/WFH/LWOP/offset, credits, attachments, manager/HR approval, calendar jobs.
- Steps with leaderboard and health-source support.

Phase 2: operational portal

- P&C user management and leave settings.
- Events and RSVP.
- Visitors/front desk.
- Facilities/resource booking.
- Staff directory and public cards.

Phase 3: sensitive and finance modules

- Payroll and payslips.
- Guidance.
- Clinic.
- Communications campaign tool.

Phase 4: polish and migration

- Optional leave credit import or initialization from approved balances.
- Report exports.
- Mobile offline/error handling.
- Observability, retry dashboards, and admin audit review.

## Key Gaps to Resolve Before Implementation

- Is v2 meant to include all lp-l12 modules immediately, or should lp-l13 stay MVP-first with a planned module backlog?
- What exact Philippine-context request labels should v2 use for Leave, WFH, LWOP, and Offset?
- Which staff workflows must be mobile-first: attendance, requests, steps, events, visitor approvals, payslips?
- Should guidance and clinic share a person/student table, or stay separate for privacy and operational reasons?
- Should payroll be fully rebuilt in v2, or initially expose only existing payslips migrated from lp-l12?
- Which roles should be canonical in v2: P&C, Finance, Front Desk, Guidance, Clinic, Facilities Admin, Facilities Approver, Events Manager, Comms, Access Admin, Super Admin?
- Do we need React web in lp-l13 now, or is the current immediate deliverable only the Laravel API and React Native app?
