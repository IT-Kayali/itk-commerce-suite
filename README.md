# IT-Kayali Commerce Suite

Modular, scalable WooCommerce commerce platform by IT-Kayali with a reusable theme, core plugin, installable modules and white-label customer profiles.

## Status

Phase 1 foundation is complete. The Phase 2 layout scope now includes the visual Header/Footer Layout Builder, profile-driven responsive navigation, rich Mega-menu panels, visual Shop/Product/Cart/Checkout page models and automated Chromium responsive/RTL/accessibility regression coverage.

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
- reusable Shop models: Grid, Sidebar, Editorial and Compact;
- reusable Product models: Classic, Gallery Left, Gallery Right, Centered and Compact;
- reusable Cart models: Classic, Split and Compact;
- reusable Checkout models: Classic, Split and Focused;
- desktop primary navigation and mobile drawer navigation;
- configurable mobile bottom navigation with a neutral commerce fallback;
- responsive Mega-menu presentation;
- Shop sidebar support through the Theme widget area rather than customer-specific markup;
- WooCommerce-safe page-model hooks without patching WooCommerce core/templates;
- public `render_block` outer shells for Cart/Checkout blocks while native block internals remain under WooCommerce ownership;
- accessible skip link, focus behavior, search form and keyboard navigation;
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

- **Appearance > Commerce Layouts** for Header/Footer, context rules, mobile navigation and portable Mega-menu definitions;
- authenticated live storefront preview before saving with Desktop, Tablet and Mobile widths;
- profile-driven Header/Footer selection and product/category/context priority rules;
- configurable mobile bottom-navigation visibility and fallback items;
- **Appearance > Commerce Mega Menu** for rich WordPress/WooCommerce/image/banner/optional Elementor panels;
- accessible rich-panel toggles with ARIA state, focus behavior, Escape and click-outside closing;
- **Appearance > Commerce Templates** for Shop, Product, Cart and Checkout page models;
- Shop options for 2–6 columns, sidebar position and card density;
- Product options for gallery weight, sticky summary and tabs/stacked details;
- Cart options for split/compact presentation, density and classic-template sticky totals;
- Checkout options for split/focused presentation, width, density and classic-template sticky order review;
- customer-profile persistence under `layouts.commerce` without overwriting Header/Footer/Mega-menu configuration;
- safe Theme fallback when an unknown model is configured.

Existing basic Mega-menu definitions keep their previous submenu behavior until rich blocks are explicitly saved. Rich blocks do not accept executable PHP or JavaScript. Optional WooCommerce/Elementor content fails closed instead of breaking navigation.

Cart and Checkout block models intentionally style only an IT-Kayali wrapper at WordPress's public block-render boundary. WooCommerce continues to own the block components, payment/validation behavior and their internal responsive markup.

Preview URLs require a logged-in user with the Commerce design capability and a valid nonce, and are marked `noindex,nofollow`.

## Browser regression gate

Customer-neutral Playwright fixtures exercise the real reusable Theme/Layouts CSS and JavaScript in Chromium. The gate covers:

- Desktop/Tablet/Mobile Header/Footer and Mega-menu behavior;
- mobile horizontal overflow and RTL logical positioning;
- Mega-menu ARIA state, keyboard open/Escape, focus restoration and click-outside closing;
- Shop column/sidebar responsive behavior;
- Product gallery ordering/sticky-summary collapse;
- classic Cart/Checkout split-model collapse;
- public Cart/Checkout block-shell widths;
- skip-link/ID/`aria-controls` and accessible-name contracts.

The installable ZIP build depends on both static validation and browser regression. Failure diagnostics retain Playwright traces, screenshots and an HTML report.

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

GitHub Actions validates PHP and JavaScript syntax, `theme.json`, all package compatibility manifests, Core lifecycle smoke tests, Layouts contracts, rich Mega-menu normalization, Commerce template contracts, the generic/customer separation rule and the Chromium browser suite. Development artifacts are built only after both validation layers pass:

- `itk-commerce-theme.zip`
- `itk-commerce-core.zip`
- `itk-commerce-layouts.zip`

## Engineering rules

- Namespace PHP code under `ITK\\Commerce` and use unique `itk_` prefixes where WordPress requires global identifiers.
- Never modify WordPress or WooCommerce core files.
- Prefer supported WooCommerce and WordPress APIs/hooks; use template overrides only when justified by layout requirements.
- Do not couple generic packages to one reference customer.
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
- [`docs/COMMERCE-TEMPLATES.md`](docs/COMMERCE-TEMPLATES.md)
- [`docs/BROWSER-REGRESSION.md`](docs/BROWSER-REGRESSION.md)

## Versioning

Development starts at `0.1.0-dev`. Stable releases will use semantic versioning. Theme, Core and modules may be released independently while remaining subject to a documented compatibility matrix.

---

**Product owner and technical publisher:** IT-Kayali
