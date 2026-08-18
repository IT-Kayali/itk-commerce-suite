# Phase 8 – Hardening and release gates

This document is the final technical gate before a customer rollout. A package is not considered production-ready because it merely installs; the complete suite must pass the checks below together.

## Automated gates

- PHP syntax validation for every PHP file under `packages/` and `tests/`.
- JavaScript syntax validation for package and browser-test JavaScript.
- JSON validation for the Theme and every package compatibility manifest.
- Core activation, admin-hub, layout, cart, account, search/filter and multilingual smoke tests.
- Phase 6–9 package/release contract smoke test.
- Phase 6/7 static module-contract validation.
- Executable Code Manager parser/security validation for safe and rejected PHP/JS/CSS/Elementor snippets.
- Browser regression in Chromium for desktop, tablet, mobile, RTL and accessibility fixtures.
- Generic-package guard preventing reference-customer names from entering reusable packages.
- Secret-pattern guard for package and client-profile source.
- ZIP build of every `packages/itk-commerce-*` directory.

## Security review

1. No credentials, API keys, passwords, tokens, certificates or production database dumps in Git.
2. Every write action uses a nonce and capability check.
3. Customer/profile import rejects secret-like keys.
4. Translation imports are bounded, validated and draft-only.
5. XLIFF parsing rejects DTD/entity declarations and uses network-disabled parsing.
6. Code Manager uses the dedicated `itk_manage_code` capability, which Core grants only to administrators by default. New/edited snippets are always saved disabled and require a second enable action after validation.
7. PHP snippets are parsed with `token_get_all(..., TOKEN_PARSE)`, reject high-risk constructs/functions, run only through the dedicated versioned runtime, auto-disable after runtime errors and can be globally suppressed with `ITK_COMMERCE_CODE_SAFE_MODE`.
8. Code Manager retains version history, audit events and rollback; complex/business-critical functions belong in installable modules rather than snippets.
9. Public AJAX/Store API usage must not reveal private WooCommerce data.
10. Customer document downloads require authenticated order ownership or document-management capability plus a nonce.
11. Generated document email files are temporary and cleaned after the request.
12. No WordPress, WooCommerce or Elementor core patches.

## WooCommerce compatibility review

- HPOS enabled/disabled state recorded during Phase 0.
- New orders, refunds and order metadata tested with HPOS.
- Cart and Checkout Blocks tested when enabled by the customer environment.
- Classic cart/checkout fallback tested where applicable.
- Payment gateway callbacks/webhooks tested in staging.
- Shipping, tax and email behavior verified with the customer configuration.
- Stored order language remains readable for historical orders.
- Documents are generated through WooCommerce CRUD without modifying commercial order data.
- Independent invoice/correction/return number series remain stable across re-downloads.
- Customer document access, PDF email attachments, corrections/refunds and return status history are verified in staging.
- Batch packing output is reconciled against source orders before warehouse use.

## Accessibility and responsive review

- Keyboard navigation for menu, mini-cart, search, filters and drawers.
- Focus trap and Escape behavior for off-canvas components.
- Visible focus treatment.
- ARIA state synchronized for expandable controls.
- Desktop, tablet and mobile breakpoints.
- RTL visual order and directional icons.
- Reduced-motion behavior.
- No essential feature depends solely on hover.
- Elementor Theme Builder fallbacks remain usable when Elementor/Pro is inactive or no matching template exists.

## Performance review

- No unbounded product/order queries.
- Search, gift-box, reviews, product grids and batch packing result counts are bounded.
- Frontend scripts are loaded only through package-owned entry points.
- Images continue to use WordPress/WooCommerce image APIs.
- Browser cache/search behavior can be invalidated without flushing customer data.
- No remote font dependency is introduced by Commerce Core local-font management.
- Document PDF generation remains local/adapter-based; no remote renderer is mandatory.
- Query Monitor or equivalent staging profiling shows no repeated high-cost query introduced by the suite.

## Release/rollback gate

Before deployment:

- full files backup;
- database backup;
- current plugin/theme ZIPs retained;
- current active profile exported;
- maintenance/freeze window agreed when a final data delta is required;
- rollback owner and decision threshold documented;
- post-deploy order/payment/email/document smoke test prepared;
- emergency Code Manager Safe Mode procedure known (`ITK_COMMERCE_CODE_SAFE_MODE`);
- PDF renderer and legally required invoice fields verified for the customer environment.

A customer rollout may proceed only after the Phase 0 environment record and the customer rollout checklist are completed with real staging/production values.
