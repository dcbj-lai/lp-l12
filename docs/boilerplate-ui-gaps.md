# UI reuse observations

## 2026-10-28 — Optional rate lines
LP Create SOA uses existing Flux select, input and remove-button controls for optional staff rate/hour lines. This is an app-only composition; tenant boilerplate coverage is unverified because that checkout was not inspected. Source: resources/views/livewire/resources/create-soa.blade.php. Reuse candidate: accessible add/remove form rows with stable keys, inline validation, keyboard navigation and narrow-width wrapping. Billing rules remain LP-specific.

## 2026-10-28 — Nested reservation filters
LP reservation status uses a main Flux-styled tab group and contextual Approved billing / Done payment tab groups with separate accessible labels and selected state. App-only composition in resources/views/livewire/resources/reservation-index.blade.php; tenant boilerplate coverage remains unverified. Reuse candidate: contextual secondary tabs retaining independent filter state and wrapping at narrow widths. No new shared component introduced.
