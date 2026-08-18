# Phase 9 repository-side readiness

This file distinguishes what the repository can complete from what requires access to the real customer environment.

## Implemented in repository

- reusable Theme/Core/Layouts/Search/Multilingual packages;
- translation import/export and administration workflow;
- order document module;
- optional Elementor integration;
- badges, wishlist/compare and gift-box modules;
- controlled code manager;
- customer-neutral hardening/CI gates;
- Al-Lord portable profile without credentials or production data;
- Phase 0 environment audit template;
- staging, activation and rollback runbook.

## Supported rollout paths

### Fresh installation

For a new WordPress/WooCommerce installation without legacy data migration, Phase 0 is reduced to the target-environment acceptance check. No source-shop inventory, HPOS synchronization backlog, historic order reconciliation or migration rehearsal is required.

Before production sign-off, verify the real target installation:

- exact WordPress, WooCommerce and PHP versions;
- Elementor/Elementor Pro versions when Elementor is used;
- HTTPS, permalinks and WooCommerce system pages;
- HPOS state on the new installation;
- payment, shipping, tax and SMTP/email configuration;
- activation of Theme, Core and selected Commerce Suite modules;
- desktop/tablet/mobile, RTL/LTR and accessibility smoke tests;
- cart, checkout, order creation, email, refund and document generation;
- backup/restore and rollback procedure.

### Existing-shop migration

Only when an existing shop is actually being migrated, also perform the source-side work:

- fill the source/target environment inventory;
- verify HPOS authoritative storage and synchronization backlog;
- capture actual product/customer/order/refund counts;
- run a staging migration against current production data;
- reconcile the final order delta;
- preserve customer/product/order identities where required;
- perform DNS/domain/SSL cutover where applicable.

Credentials, customer data and production database dumps must never be committed to Git.

The source repository must never claim real-environment checks succeeded until evidence from the actual WordPress/WooCommerce installation exists.
