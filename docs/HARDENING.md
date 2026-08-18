# Phase 8 – Hardening and release gates

This document is the final technical gate before a customer rollout. A package is not considered production-ready because it merely installs; the complete suite must pass the checks below together.

## Automated gates

- PHP syntax validation for every PHP file under `packages/` and `tests/`.
- JavaScript syntax validation for package and browser-test JavaScript.
- JSON validation for the Theme and every package compatibility manifest.
- Core activation, admin-hub, layout, cart, account, search/filter and multilingual smoke tests.
- Phase 6–9 package/release contract smoke test.
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
6. Code Manager is administrator-only. PHP snippets remain disabled unless `ITK_COMMERCE_ALLOW_PHP_SNIPPETS` is explicitly enabled in `wp-config.php`.
7. Public AJAX/Store API usage must not reveal private WooCommerce data.
8. No WordPress, WooCommerce or Elementor core patches.

## WooCommerce compatibility review

- HPOS enabled/disabled state recorded during Phase 0.
- New orders, refunds and order metadata tested with HPOS.
- Cart and Checkout Blocks tested when enabled by the customer environment.
- Classic cart/checkout fallback tested where applicable.
- Payment gateway callbacks/webhooks tested in staging.
- Shipping, tax and email behavior verified with the customer configuration.
- Stored order language remains readable for historical orders.
- Documents are generated without modifying commercial order data.

## Accessibility and responsive review

- Keyboard navigation for menu, mini-cart, search, filters and drawers.
- Focus trap and Escape behavior for off-canvas components.
- Visible focus treatment.
- ARIA state synchronized for expandable controls.
- Desktop, tablet and mobile breakpoints.
- RTL visual order and directional icons.
- Reduced-motion behavior.
- No essential feature depends solely on hover.

## Performance review

- No unbounded product queries.
- Search and gift-box result counts are bounded.
- Frontend scripts are loaded only through package-owned entry points.
- Images continue to use WordPress/WooCommerce image APIs.
- Browser cache/search behavior can be invalidated without flushing customer data.
- Query Monitor or equivalent staging profiling shows no repeated high-cost query introduced by the suite.

## Release/rollback gate

Before deployment:

- full files backup;
- database backup;
- current plugin/theme ZIPs retained;
- current active profile exported;
- maintenance/freeze window agreed when a final data delta is required;
- rollback owner and decision threshold documented;
- post-deploy order/payment/email smoke test prepared.

A customer rollout may proceed only after the Phase 0 environment record and the customer rollout checklist are completed with real staging/production values.
