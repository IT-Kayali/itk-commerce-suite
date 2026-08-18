# Phases 5–9 implementation coverage

## Phase 5 – Multilingual

Implemented: language routing/switching, locale and RTL/LTR context, translation repository and revision workflow, WooCommerce entity mapping, session/order language capture, order rendering language, translated permalinks, canonical/hreflang, JSON/CSV/XLIFF transfer and Commerce Suite translation administration.

Production completion condition: browser regression green and translations reviewed/published for the target customer.

## Phase 6 – Documents

Implemented: installable Documents module, invoice, delivery note, return form and packing list HTML rendering, stored order-language direction, admin generation surface, pluggable PDF renderer and Dompdf integration when installed.

Production completion condition: customer legal/tax invoice fields and PDF renderer verified against the target jurisdiction/environment.

## Phase 7 – Elementor and developer extensions

Implemented: optional Elementor module with Commerce widget category/widgets; Theme Builder location support already exists in the Theme; Code Manager provides Head/Body/Footer HTML/CSS/JS plus explicitly enabled administrator PHP snippets. Theme local-font policy remains the default.

Production completion condition: exact Elementor/Elementor Pro versions and customer templates verified in staging.

## Phase 8 – Hardening

Implemented: static syntax validation, compatibility-manifest checks, module smoke tests, customer/generic separation tests, secret guards, Chromium responsive/RTL/accessibility regression and ZIP build for every package. Hardening/security/HPOS/performance/rollback gates are documented.

Production completion condition: real WooCommerce environment tests complete with payment/shipping/email/HPOS evidence.

## Phase 9 – Al-Lord rollout

Implemented repository-side: customer profile, environment audit template, staging/cutover/rollback runbook and final readiness boundary.

Not possible to certify from GitHub alone: live data counts, HPOS synchronization, current order delta, gateway credentials/webhooks, DNS/SSL changes or real production smoke orders. These require access to the customer source and target WordPress/WooCommerce environments.
