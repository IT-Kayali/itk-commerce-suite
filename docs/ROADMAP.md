# Development Roadmap

This roadmap mirrors the approved IT-Kayali Commerce Suite product plan. Each phase must produce installable, testable and documented results. Repository implementation and real-customer production verification are tracked separately so source code never claims an environment test that has not actually been executed.

## Phase 0 — Audit and specification

**Repository status:** audit template and rollout acceptance fields implemented in `docs/PHASE-0-AUDIT.md`.

**Environment status:** open until the real source and target WordPress/WooCommerce installations are audited.

Deliverables:

- system inventory;
- data model and data-volume inventory;
- UX/technical acceptance criteria;
- supported-environment matrix;
- migration risks/dependencies;
- backup/restore and rollback evidence.

## Phase 1 — Foundation

**Status:** implemented.

Deliverables include repository/package structure, build/release foundation, Core plugin, Theme, design tokens, roles/capabilities and module-management contracts.

## Phase 2 — Builders

**Status:** implemented.

Header/Footer builders, rich Mega Menu, mobile bottom navigation, preview and commerce layout management are present.

## Phase 3 — Commerce UI

**Status:** implemented.

Shop/catalog, product, product-card, badge slots, cart, checkout, mini-cart and customer-account presentation contracts are present.

## Phase 4 — Search and filter

**Status:** implemented.

Filter builder, asynchronous catalog navigation, mobile/off-canvas filters, shareable URL state and live product/category/SKU search are present.

## Phase 5 — Multilingual

**Repository status:** implementation complete on the Phase 5–9 integration branch/PR.

Implemented:

- language configuration/routing/switching;
- RTL/LTR and WordPress locale context;
- translation repository with draft/review/published workflow;
- product/category/attribute mapping;
- session/order language capture and historical order-language rendering;
- canonical/hreflang and translated routes;
- JSON/CSV/XLIFF transfer;
- translator/admin workflow surface;
- dedicated package and browser regression gates.

**Production gate:** customer translations and real environment behavior still require staging acceptance.

## Phase 6 — Documents

**Repository status:** implemented.

The installable Documents module provides invoice, delivery note, return form and packing list generation, order-language direction, print-ready HTML and a PDF-renderer contract with Dompdf support when available.

**Production gate:** legal/tax content, customer branding and PDF renderer must be verified in the target environment.

## Phase 7 — Elementor and developer code

**Repository status:** implemented.

- existing Theme Builder locations;
- optional Elementor module and Commerce widgets;
- local-font-first Theme policy;
- controlled Code Manager for Head/Body/Footer HTML/CSS/JS and explicitly enabled administrator PHP snippets;
- optional advanced badges, wishlist/compare and gift-box packages.

**Production gate:** exact Elementor/Elementor Pro and customer-template compatibility requires staging verification.

## Phase 8 — Hardening

**Repository status:** automated hardening implemented.

- syntax/manifest/package validation;
- security/customer-boundary guards;
- responsive/RTL/accessibility Chromium regression;
- package release-contract tests;
- all-package ZIP builds;
- documented security, performance, HPOS, Blocks and rollback gates.

**Environment status:** HPOS, Cart/Checkout Blocks, gateways, shipping, email and performance must still be verified against the audited customer stack before production sign-off.

## Phase 9 — Al-Lord rollout

**Repository-side status:** ready for execution.

Implemented in Git:

- Al-Lord customer profile without secrets or production commerce data;
- environment audit template;
- migration rehearsal/cutover/rollback runbook;
- post-launch monitoring checklist.

**Live status:** not executed from GitHub. Current production-data migration, final order reconciliation, HPOS verification, gateway/SMTP secrets, DNS/SSL cutover and real production smoke orders require access to the actual source/target WordPress installations.

## Release gate

A production release requires no open critical/high defects, complete backups and tested rollback, completed HPOS synchronization where applicable, successful checkout/payment/shipping/email coverage, data-count reconciliation and explicit IT-Kayali/customer approval. Repository CI is necessary but cannot replace these real-environment checks.
