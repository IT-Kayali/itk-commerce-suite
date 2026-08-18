# Phase 9 repository-side readiness

This file distinguishes what the repository can complete from what requires access to the real customer environments.

## Implemented in repository

- reusable Theme/Core/Layouts/Search/Multilingual packages;
- translation import/export and administration workflow;
- order document module;
- optional Elementor integration;
- badges, wishlist/compare and gift-box modules;
- controlled code manager;
- customer-neutral hardening/CI gates;
- Al-Lord portable profile without credentials or production data;
- Phase 0 audit template;
- staging, cutover and rollback runbook.

## Requires real WordPress/WooCommerce access before production sign-off

- fill the Phase 0 source/target environment inventory;
- verify exact WooCommerce, WordPress, PHP, Elementor and plugin versions;
- verify HPOS authoritative storage and synchronization backlog;
- capture actual product/customer/order/refund counts;
- run staging migration against current production data;
- configure payment/shipping/SMTP credentials outside Git;
- execute payment, shipping, email, refund and document tests;
- reconcile the final order delta;
- perform DNS/domain/SSL cutover where applicable;
- observe production orders after go-live.

The source repository must never claim those environment checks succeeded until evidence from the actual customer installations exists.
