# UI reuse observations

## 2026-11-03 — Standalone facilities task pages

LP has a shared standalone task shell in resources/views/components/layouts/facilities.blade.php with scoped presentation in resources/css/facilities-portal.css. It is used for guest payment upload/cancellation. On November 4, the authenticated finance inbox/detail pages were moved to the existing x-layouts.app sidebar/account layout; their reusable card/form presentation is retained inside a facility-workspace scope with dark-mode styles. Existing LP auth-card layouts and ui/card were inspected; they provide authentication/card presentation but not the guest task shell. These patterns are shared within LP; coverage in the separate tenant boilerplate/UI Lab remains unverified.

Reuse candidate: a branded task-page header, optional authenticated account navigation, privacy context for bearer links, structured event summary, colored status badges, private-file rows, validation/empty/success states, and a one-column phone/two-column desktop layout. Form routes, roles, CSRF, file restrictions, recurrence rules and payment transitions stay in LP. Standalone pages currently use a deliberate light surface matching their prior theme; no external font or analytics resource is loaded on bearer-link pages. Future cross-framework reuse should translate the presentation rather than copy Blade.

Chrome checks covered normal upload, pending/paid states, recurring-date disclosure/selection without submission, finance inbox/empty state and validation feedback. Payment/cancellation/finance pages had no horizontal overflow at the tested ~390px width; temporary viewport settings were reset. This is an LP implementation/reuse reference, not a claim that a LifeOS boilerplate gap was resolved.

November 4 follow-up: finance inbox/detail now render with LP's standard sidebar, active Facilities navigation and existing profile/account menu, without the standalone Portal/Log out header. Main-layout light/dark and narrow-screen rendering were checked in Chrome; original System appearance and default viewport were restored. Existing finance/requester email regression suites passed: 18 tests, 129 assertions. No requester page layout or payment transition changed in this follow-up.

## 2026-10-28 — Optional rate lines
LP Create SOA uses existing Flux select, input and remove-button controls for optional staff rate/hour lines. This is an app-only composition; tenant boilerplate coverage is unverified because that checkout was not inspected. Source: resources/views/livewire/resources/create-soa.blade.php. Reuse candidate: accessible add/remove form rows with stable keys, inline validation, keyboard navigation and narrow-width wrapping. Billing rules remain LP-specific.

## 2026-10-28 — Nested reservation filters
LP reservation status uses a main Flux-styled tab group and contextual Approved billing / Done payment tab groups with separate accessible labels and selected state. App-only composition in resources/views/livewire/resources/reservation-index.blade.php; tenant boilerplate coverage remains unverified. Reuse candidate: contextual secondary tabs retaining independent filter state and wrapping at narrow widths. No new shared component introduced.
