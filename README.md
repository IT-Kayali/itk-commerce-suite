# IT-Kayali Commerce Suite

Modular, scalable WooCommerce commerce platform by IT-Kayali with a reusable theme, core plugin, installable modules and white-label customer profiles.

## Status

Phase 1 foundation is complete. Phase 2 now includes the visual Layout Builder, profile-driven Header/Footer assignments, responsive navigation controls, rich Mega-menu panels and the installable `itk-commerce-layouts` module.

## Product ownership

The generic product architecture, source code, reusable layouts, modules, documentation and release process belong to IT-Kayali. Customer-specific branding, content, products, media and business data remain separate from the generic product core.

The first reference implementation is Al-Lord Sweets. Al-Lord-specific decisions must stay inside its customer profile and must not be hard-coded into the reusable product core.

## Architecture

The suite is split into clear layers:

1. **Customer profile** — branding, languages, layout rules, contacts and enabled modules.
2. **IT-Kayali theme** — UI, design tokens, patterns and responsive layouts.
3. **IT-Kayali core** — settings, module management, import/export, roles and update coordination.
4. **Installable modules** — optional commerce capabilities with explicit dependencies.
5. **WordPress + WooCommerce** — customer data and commerce data remain in supported platform APIs.

## Theme baseline (`0.1.0-dev`)

The reusable theme provides:

- standard page, front-page, single, archive, search and 404 templates;
- WooCommerce integration and product-gallery support;
- customer-neutral Header and Footer rendering;
- reusable Header models: Classic, Centered, Shop/Search-first, Transparent, Dark, Luxury, Sticky and Vertical;
- reusable Footer models: Classic, Compact, Columns, Simple, Luxury, Newsletter and Branches;
- desktop primary navigation and mobile drawer navigation;
- configurable mobile bottom navigation with a neutral commerce fallback;
- responsive Mega-menu column presentation foundation;
- responsive WooCommerce product-grid baseline;
- accessible skip link, focus behavior, search form and keyboard-close navigation;
- versioned `theme.json` design tokens;
- RTL-aware logical layout rules;
- a local-font-only policy by default;
- layered CSS/JS assets and public extension hooks for modules.

## Core baseline (`0.1.0-dev`)

The Core provides:

- versioned suite settings;
- versioned customer-profile schema and persistence;
- portable-profile secret rejection;
- module registry with dependency and environment validation;
- deterministic dependency-ordered module boot;
- role/capability foundation;
- activation/deactivation lifecycle handling;
- CI lifecycle smoke testing;
- compatibility manifests;
- documented update/migration/rollback rules.

## Layouts module (`0.1.0-dev`)

The optional Layouts module provides:

- **Appearance > Commerce Layouts** visual builder;
- visual Header/Footer model cards;
- context overrides for Shop, Product and Checkout;
- authenticated live storefront preview without saving first;
- Desktop, Tablet and Mobile preview widths;
- profile-driven Header/Footer model selection;
- single-product, category, product-type and contextual assignment priority;
- WordPress activation/deactivation synchronization with Core and the active profile;
- configurable mobile bottom-navigation visibility and fallback items;
- portable Mega-menu definition keys, width and 1–6 column settings;
- a WordPress menu-item field that binds local menu items to portable Mega-menu definitions;
- **Appearance > Commerce Mega Menu** rich-content editor;
- rich Mega-menu blocks for WordPress child links, WooCommerce categories, WooCommerce products, images, promo banners and optional Elementor saved templates;
- responsive rich-panel rendering with a dedicated toggle, `aria-expanded`, keyboard focus support, Escape handling and click-outside closing;
- customer-profile storage that keeps rich content separate from width/assignment metadata so normal Layout Builder saves do not delete rich panels;
- safe Theme fallback when an unknown model is configured.

Existing basic Mega-menu definitions keep their previous submenu behavior until rich blocks are explicitly saved. Rich blocks do not accept executable PHP or JavaScript. Optional WooCommerce/Elementor content fails closed instead of breaking the site header.

Preview URLs require a logged-in user with the Commerce design capability and a valid nonce, and are marked `noindex,nofollow`.

## Packages

Implemented:

- `itk-commerce-theme`
- `itk-commerce-core`
- `itk-commerce-layouts`

Planned:

- `itk-commerce-multilingual`
- `itk-commerce-elementor`
- `itk-commerce-search-filter`
- `itk-commerce-documents`
- `itk-commerce-badges`
- `itk-commerce-wishlist-compare`
- `itk-commerce-gift-boxes`
- `itk-commerce-code-manager`

Packages are created when implementation starts; empty placeholder packages are intentionally avoided.

## Repository layout

```text
packages/
  itk-commerce-theme/
  itk-commerce-core/
  itk-commerce-layouts/
client-profiles/
  al-lord/
docs/
tests/
tools/
.github/
```

## Validation and development ZIPs

GitHub Actions validates PHP and JavaScript syntax, `theme.json`, all package compatibility manifests, Core lifecycle smoke tests, the Layouts module contract, rich Mega-menu normalization and the generic/customer separation rule. It then builds development artifacts:

- `itk-commerce-theme.zip`
- `itk-commerce-core.zip`
- `itk-commerce-layouts.zip`

## Engineering rules

- Namespace PHP code under `ITK\\Commerce` and use unique `itk_` prefixes where WordPress requires global identifiers.
- Never modify WordPress or WooCommerce core files.
- Prefer supported WooCommerce APIs and hooks; use template overrides only when justified by layout requirements.
- New features must be modular, backwards-compatible and isolated from unrelated modules.
- Database changes require versioned, repeatable migrations and an explicit rollback strategy where data changes are involved.
- Inactive modules must not enqueue frontend assets or run background jobs.
- Secrets, passwords, API keys and customer personal data must never be committed or exported by default.
- Every release must be installable, testable, documented and recoverable.

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md)
- [`docs/ROADMAP.md`](docs/ROADMAP.md)
- [`docs/CORE-FOUNDATION.md`](docs/CORE-FOUNDATION.md)
- [`docs/UPDATE-ROLLBACK.md`](docs/UPDATE-ROLLBACK.md)
- [`docs/LAYOUT-BUILDERS.md`](docs/LAYOUT-BUILDERS.md)

## Versioning

Development starts at `0.1.0-dev`. Stable releases will use semantic versioning. Theme, Core and modules may be released independently while remaining subject to a documented compatibility matrix.

---

**Product owner and technical publisher:** IT-Kayali
