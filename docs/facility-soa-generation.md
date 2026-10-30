# SOA generation — local implementation

Facilities admins can use **Billing → Create SOA** on an approved reservation. Enter the required Client Name, optional Business Address, Ministry/Non-Ministry classification, staff quantities and hours, and consumable quantities. Event Name, Event Date, Venue and Event Time come from the reservation; the supplied template's letterhead, notes and bank details are retained.

## Calculation

- Venue: Ministry PHP 1,200 or Non-Ministry PHP 2,400 × number of rooms × started four-hour blocks. Five hours uses two blocks per room. Exactly four hours uses one block.
- Staff: quantity × editable billable hours × hourly rate, calculated in integer cents. Default hours come from the reservation duration.
- Consumables: quantity × unit rate.
- Facilities → Billing configuration manages Security, Logistics, Housekeeping, Plates, Spoons, Forks, Coffee Set and Cups. Logistics and Housekeeping start at PHP 200/hour because those amounts appear in the source template. Other prices start unconfigured; selected quantities cannot be billed until a rate is entered. A configured zero explicitly means free.

## Issue dates and storage

Create SOA saves a draft private PDF and moves the event into Unbilled. Preview PDF downloads a draft without saving or emailing it. Use the existing Send SOA action to issue it.

On the actual email-job execution day, generated SOAs are finalized with that Manila issue date and a due date 15 weekdays later. Saturdays and Sundays are excluded; Philippine holidays are not currently excluded. A reminder retains the original issue/due dates. Uploaded external SOAs retain their existing date workflow.

Each generated document stores an event/pricing snapshot, so subsequent configuration changes do not alter existing charges. Replacing a current SOA requires a reason, retains the previous file and creates an SOA revision. Generating a new SOA is blocked after payment; the existing manual Replace SOA action remains available for its established audit workflow. No refunds or payment changes are made by generation.

SOA filenames use the Manila event start date and event name: `mmddyy - Event Name.pdf`. Previews, saved downloads and billing email attachments use this format. A generated SOA stores its filename alongside its event snapshot; later booking edits do not rename that document. New generated files live in separate UUID folders to avoid overwriting documents with matching event names/dates. Uploaded SOAs retain their PDF/Word extension when downloaded or emailed. Unsafe filename characters are replaced and long names shortened safely.

New migration: `2026_10_27_000002_add_facility_soa_generation.php`. This work depends on the locally prepared private billing storage and revised billing-tab workflow. Those changes require their own production readiness checks; generation has not been deployed.

## Verification

The generated sample was rendered to PNG and visually reviewed as a one-page letter-size PDF. Automated checks cover multi-room/full-block pricing, exact four-hour boundaries, staff/consumables, missing rates, required client name, immutable snapshots, replacements, paid protection, admin configuration permissions, and delayed sending/reminder dates. Direct browser click-through remains pending the Chrome/browser-control connection; desktop control is not used as a substitute.

Source: user-supplied `SOA Template.docx`, Google Drive file `1tELJam2CeHFSZOXJzQL2DY5cCaa75mZM`, inspected October 27, 2026. The source file was not edited.

## Holiday and overtime configuration

Migration `2026_10_27_000003_add_facility_holiday_rates.php` adds 33 nullable rate entries, preserving the existing regular fees. The configuration page groups rates by Security, Logistics and Housekeeping, with separate first-eight-hour/over-eight-hour periods for Sunday, special holidays and legal holidays, including holidays on Sunday and Monday–Saturday overtime. Values are explicit PHP hourly amounts, not multipliers; zero is free and blank is unconfigured. Negative amounts and more than two decimal places are rejected.

Reference: `LIFE ACADEMY - OT RATES.pdf`, Drive file `1hMOXkeQd4L8P9ys4HTrKQy6k-sBSMNne`, inspected October 27, 2026. User confirmed the PHP 755/day schedule for Housekeeping/Logistics and separate Security rates: PHP 115.94/hour day shift, PHP 121.99/hour night shift. Migration `2026_10_27_000004_configure_facility_ot_reference_rates.php` populates previously blank holiday entries from that schedule and adds Security night shift. Existing configured prices are preserved. Special/legal holiday first-eight-hour amounts are copied as printed, including premium-only amounts; they are not inferred multipliers or replacements for the existing PHP 200 regular client staff fee. Security OT amounts remain unconfigured pending user-provided rates. Holiday/OT and night-shift values are editable and can now be selected as additional staff rate/hour lines in Create SOA. Regular SOA pricing uses the existing regular rates (with Security now defaulting to PHP 115.94 if previously unconfigured).

## Selected staff rate hours (October 28, 2026)

Create/Edit SOA now supports additional per-staff rate lines for configured holiday and overtime periods, plus Security night shift. Enter staff quantity once and allocate hours between the regular hours and additional lines; each is charged separately (quantity × hours × its configured hourly amount). Set regular hours to zero for a shift billed entirely under an additional rate. Do not enter the same hours twice unless an explicit additional premium is intended. Period applicability and hour allocation are selected by the admin, not inferred from dates, holidays or shift length. Amounts from the supplied rate sheet are used literally; no multiplier or base-rate addition is assumed.

Missing prices block billing of positive hours; zero remains an explicit free rate. Saved SOAs retain period labels, hours and amounts in the pricing snapshot. Edit restores those selections and uses current configured prices for the replacement, retaining the original document in history. This uses the existing JSON snapshot and rate table, so no additional migration is required.


## Reservation navigation (October 28, 2026)

Main tabs: All, For Approval, Approved, Done, Rejected, Deleted. Approved includes every approved occurrence that is not Done; secondary billing filters are All, Unbilled, Billed and Paid. Unbilled includes approved events without a sent SOA, including events not yet finished. Replaced unpaid SOAs awaiting sending remain Unbilled. Paid future events remain Approved → Paid.

Done is computed on load/actions without polling or database writes: the scheduled end has passed and either billing is paid or completion was confirmed with no payment required (unbilled with no SOA). An unsent/replacement SOA or requester cancellation awaiting review prevents Done. Finished unpaid events remain Approved for SOA/payment follow-up. Done retains All/Paid/Complimentary filters. Recurring occurrences display independently by their own event dates in Approved and Done, with a recurring-series label and View recurring events action.
