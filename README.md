# IT-Kayali Commerce Suite

Modular, scalable WooCommerce commerce platform by IT-Kayali with a reusable Theme, Core plugin, installable modules and white-label customer profiles.

## Current implementation status

The reusable product now contains the Phase 1–4 foundation/builders/commerce/search work, the Phase 5 multilingual stack, Phase 6 order documents, Phase 7 Elementor/code extension modules, the optional commerce modules defined in the product plan, and Phase 8 release/hardening automation. Phase 9 repository-side rollout assets for the first reference customer are also present.

A real customer production rollout is **not** declared complete from source code alone. Phase 0 environment values and the Phase 9 staging/cutover checklist must be completed against the actual WordPress/WooCommerce installations before production sign-off.

## Product ownership

The generic architecture, source code, reusable layouts, modules, documentation and release process belong to IT-Kayali. Customer-specific branding, content, products, media and business data remain separate from the generic product core.

The first reference implementation is Al-Lord Sweets. Customer-specific configuration is isolated below `client-profiles/al-lord/` and must not be hard-coded into reusable packages.

## Architecture

1. **Customer profile** — branding, languages, layouts and enabled module configuration.
2. **IT-Kayali Theme** — design system, responsive UI, product cards and public presentation extension points.
3. **IT-Kayali Core** — settings, profiles, modules, capabilities and lifecycle coordination.
4. **Installable modules** — independent optional commerce features with explicit dependencies.
5. **WordPress + WooCommerce** — customer/product/order data remains in supported platform APIs and storage.

## Package set

- `itk-commerce-theme` — responsive WooCommerce Theme, local-font policy and reusable commerce presentation.
- `itk-commerce-core` — settings, profile schema, module registry, roles/capabilities and admin hub.
- `itk-commerce-layouts` — Header/Footer/Mega-menu builders and Shop/Product/Cart/Checkout layout models.
- `itk-commerce-search-filter` — filter builder, shareable URL state, asynchronous catalog navigation, mobile drawer and live search.
- `itk-commerce-multilingual` — language routing, RTL/LTR, translation revisions, WooCommerce/order language, translated routes, SEO and JSON/CSV/XLIFF transfer.
- `itk-commerce-documents` — invoice, delivery note, return form and packing list with print-ready HTML plus pluggable PDF rendering.
- `itk-commerce-elementor` — optional Elementor Commerce category/widgets and extension-area integration.
- `itk-commerce-badges` — sale-percentage and custom product badges through Theme extension contracts.
- `itk-commerce-wishlist-compare` — browser wishlist and bounded comparison using WooCommerce Store API reads.
- `itk-commerce-gift-boxes` — validated configurable gift-box selections persisted to cart and order line metadata.
- `itk-commerce-code-manager` — controlled Head/Body/Footer HTML/CSS/JS and explicit opt-in PHP extension points.

See [`packages/README.md`](packages/README.md) for package-level status and responsibilities.

## Multilingual workflow

The Multilingual module supports directory language routing, language switching, WordPress locale switching, RTL/LTR direction, draft/review/published revisions, WooCommerce entity translations, stored order language, order-language rendering, canonical/hreflang, translated entity routes and JSON/CSV/XLIFF interchange.

Translation imports are bounded and draft-only. The Commerce Suite admin surface lets translators create/import drafts and submit revisions for review; publication remains an administrative action.

## Documents

`itk-commerce-documents` renders WooCommerce order data as invoice, delivery note, return form or packing list without mutating commercial order data. Stored Commerce Suite order language is reused for document direction. HTML is always available; PDF output uses an installed Dompdf renderer or the `itk_commerce_documents_pdf_renderer` adapter.

## Elementor and controlled custom code

Elementor remains optional. Commerce Suite functionality does not depend on Elementor being active. The Elementor module registers reusable Commerce widgets only when Elementor is present.

The Code Manager provides controlled extension locations instead of Theme/Core patches. PHP snippets are disabled unless `ITK_COMMERCE_ALLOW_PHP_SNIPPETS` is explicitly enabled by the installation owner.

## Validation and development ZIPs

GitHub Actions validates PHP/JavaScript syntax, JSON compatibility manifests, Core/Layout/Search/Multilingual contracts, generic/customer separation, secret patterns and the Phase 6–9 release contract. Chromium browser regression covers responsive/RTL/accessibility fixtures. After validation, every `packages/itk-commerce-*` package is built as its own ZIP artifact.

## Phase 8 and Phase 9 gates

- [`docs/HARDENING.md`](docs/HARDENING.md) defines security, accessibility, WooCommerce/HPOS, performance and rollback release gates.
- [`docs/PHASE-0-AUDIT.md`](docs/PHASE-0-AUDIT.md) records the real source/target WordPress, WooCommerce, PHP, HPOS, plugins and data-volume environment.
- [`docs/AL-LORD-ROLLOUT.md`](docs/AL-LORD-ROLLOUT.md) defines staging rehearsal, final data reconciliation, cutover and rollback for the first customer implementation.
- `client-profiles/al-lord/profile.json` contains the customer profile without production data or credentials.

## Engineering rules

- Namespace PHP code under `ITK\\Commerce` and use unique `itk_` identifiers where WordPress needs global names.
- Never modify WordPress, WooCommerce or Elementor core files.
- Prefer supported WordPress/WooCommerce APIs and public extension contracts.
- Keep reusable packages customer-neutral and customer configuration isolated under `client-profiles/`.
- New functionality must remain modular and must not create unintended side effects in unrelated modules.
- Database changes require versioned/repeatable migrations and rollback planning.
- Inactive modules must not run frontend/background behavior.
- Never commit secrets, credentials, production customer/order data or database dumps.
- Every production deployment requires backups, staging verification and a defined rollback path.

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md)
- [`docs/ROADMAP.md`](docs/ROADMAP.md)
- [`docs/UPDATE-ROLLBACK.md`](docs/UPDATE-ROLLBACK.md)
- [`docs/MULTILINGUAL-TRANSFER.md`](docs/MULTILINGUAL-TRANSFER.md)
- [`docs/HARDENING.md`](docs/HARDENING.md)
- [`docs/PHASE-0-AUDIT.md`](docs/PHASE-0-AUDIT.md)
- [`docs/AL-LORD-ROLLOUT.md`](docs/AL-LORD-ROLLOUT.md)

## Versioning

Development packages remain at `0.1.0-dev`. Stable releases use semantic versioning. Theme, Core and modules may be released independently subject to their compatibility manifests and the tested customer environment.

---

**Product owner and technical publisher:** IT-Kayali
