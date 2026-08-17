# IT-Kayali Commerce Suite

Modular, scalable WooCommerce commerce platform by IT-Kayali with a reusable theme, core plugin, installable modules and white-label customer profiles.

## Status

Initial foundation. Current development target: Phase 0/1 (audit, specification and technical foundation).

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
