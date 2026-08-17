# IT-Kayali Commerce Suite

Modular, scalable WooCommerce commerce platform by IT-Kayali with a reusable theme, core plugin, installable modules and white-label customer profiles.

## Status

Foundation / Phase 1. The reusable base theme template is implemented on `foundation/initial-structure`; advanced builders and optional commerce capabilities remain separate packages.

## Product ownership

The generic product architecture, source code, reusable layouts, modules, documentation and release process belong to IT-Kayali. Customer-specific branding, content, products, media and business data remain separate from the generic product core.

The first reference implementation is Al-Lord Sweets. Al-Lord-specific decisions must stay inside its customer profile and must not be hard-coded into the reusable product core.

## Architecture

The suite is split into clear layers:

1. **Customer profile** — branding, languages, layout rules, contacts and enabled modules.
2. **IT-Kayali theme** — UI, design tokens, patterns and responsive layouts.
3. **IT-Kayali core** — settings, module management, import/export, roles and update coordination.
4. **Installable modules** — optional commerce capabilities with explicit dependencies.
5. **WordPress + WooCommerce** — customer data and commerce data remain in the supported platform APIs.

## Theme baseline (`0.1.0-dev`)

The reusable theme now provides:

- standard page, front-page, single, archive, search and 404 templates;
- WooCommerce integration and product-gallery support;
- customer-neutral header and multi-column footer;
- desktop primary navigation and mobile drawer navigation;
- configurable mobile bottom navigation with a neutral commerce fallback;
- responsive WooCommerce product-grid baseline;
- accessible skip link, focus behavior, search form and keyboard-close navigation;
- versioned `theme.json` design tokens;
- RTL-aware logical layout rules;
- a local-font-only policy by default;
- layered CSS/JS assets and public extension hooks for future modules.

## Planned packages

- `itk-commerce-theme`
- `itk-commerce-core`
- `itk-commerce-multilingual`
- `itk-commerce-elementor`
- `itk-commerce-layouts`
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
client-profiles/
  al-lord/
docs/
tests/
tools/
.github/
```

## Validation and development ZIPs

GitHub Actions validates PHP syntax and `theme.json`, then builds development artifacts:

- `itk-commerce-theme.zip`
- `itk-commerce-core.zip`

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

## Versioning

Development starts at `0.1.0-dev`. Stable releases will use semantic versioning. Theme, core and modules may be released independently while remaining subject to a documented compatibility matrix.

---

**Product owner and technical publisher:** IT-Kayali
