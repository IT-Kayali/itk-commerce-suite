# IT-Kayali Commerce Suite

Modular, scalable WooCommerce commerce platform by IT-Kayali with a reusable Theme, Core plugin, installable modules and white-label customer profiles.

## Current implementation status

The reusable product contains the Phase 1–4 foundation/builders/commerce/search work, the Phase 5 multilingual stack, Phase 6 order documents, Phase 7 Elementor/developer extension modules, the optional commerce modules defined in the product plan, and Phase 8 release/hardening automation. Phase 9 repository-side rollout assets for the first reference customer are also present.

A real customer production rollout is **not** declared complete from source code alone. Phase 0 environment values and the Phase 9 staging/cutover checklist must be completed against the actual WordPress/WooCommerce installations before production sign-off.

## Product ownership

The generic architecture, source code, reusable layouts, modules, documentation and release process belong to IT-Kayali. Customer-specific branding, content, products, media and business data remain separate from the generic product core.

The first reference implementation is Al-Lord Sweets. Customer-specific configuration is isolated below `client-profiles/al-lord/` and must not be hard-coded into reusable packages.

## Architecture

1. **Customer profile** — branding, languages, layouts and enabled module configuration.
2. **IT-Kayali Theme** — design system, responsive UI, product cards and public presentation extension points.
3. **IT-Kayali Core** — settings, profiles, modules, capabilities, local-font management and lifecycle coordination.
4. **Installable modules** — independent optional commerce features with explicit dependencies.
5. **WordPress + WooCommerce** — customer/product/order data remains in supported platform APIs and storage.

## Package set

- `itk-commerce-theme` — responsive WooCommerce Theme, Elementor Theme Builder locations and reusable commerce presentation.
- `itk-commerce-core` — settings, profile schema, module registry, roles/capabilities, admin hub and self-hosted local-font management.
- `itk-commerce-layouts` — Header/Footer/Mega-menu builders and Shop/Product/Cart/Checkout layout models.
- `itk-commerce-search-filter` — filter builder, shareable URL state, asynchronous catalog navigation, mobile drawer and live search.
- `itk-commerce-multilingual` — language routing, RTL/LTR, translation revisions, WooCommerce/order language, translated routes, SEO and JSON/CSV/XLIFF transfer.
- `itk-commerce-documents` — invoices/corrections, delivery notes, returns, packing, independent number series, local barcodes, customer downloads, email attachments and batch warehouse picking.
- `itk-commerce-elementor` — optional Elementor Theme Builder integration, Commerce widgets and dynamic product/profile data.
- `itk-commerce-badges` — sale-percentage and custom product badges through Theme extension contracts.
- `itk-commerce-wishlist-compare` — browser wishlist and bounded comparison using WooCommerce Store API reads.
- `itk-commerce-gift-boxes` — validated configurable gift-box selections persisted to cart and order line metadata.
- `itk-commerce-code-manager` — versioned and conditional HTML/CSS/JS/shortcode/Elementor/PHP extension points with Safe Mode and rollback.

See [`packages/README.md`](packages/README.md) for package-level status and responsibilities.

## Multilingual workflow

The Multilingual module supports directory language routing, language switching, WordPress locale switching, RTL/LTR direction, draft/review/published revisions, WooCommerce entity translations, stored order language, historical order-language rendering, canonical/hreflang, translated entity routes and JSON/CSV/XLIFF interchange.

Translation imports are bounded and draft-only. The Commerce Suite admin surface lets translators create/import drafts and submit revisions for review; publication remains an administrative action.

## Documents

`itk-commerce-documents` renders WooCommerce order data through `WC_Order` CRUD as invoice, invoice correction/cancellation, delivery note, return form or packing list without changing commercial order state. Invoice/correction/return numbers use independent persisted number series. Generation events are recorded with content hashes; return cases retain item/quantity/reason/condition/status history.

Documents use the stored Commerce Suite order language and RTL/LTR direction. Active customer-profile branding/contact data and per-document/per-language template overrides are supported. A local Code 39 SVG barcode is generated for document numbers and can be replaced through a filter. Customer order pages can expose authorized document downloads, and selected WooCommerce customer emails can receive PDFs when a local PDF renderer is available.

HTML output is always available. PDF output uses an installed Dompdf renderer or the `itk_commerce_documents_pdf_renderer` adapter. The Packing admin surface can create a consolidated picking list plus per-order packing sections for a bounded order batch.

## Elementor and local fonts

Elementor remains optional. Commerce Suite functionality does not depend on Elementor being active. The integration supports Header, Footer, Single, Archive and additional IT-Kayali Theme Builder locations, plus widgets for products, categories, filters, product search, hero/banner, branches, reviews, contact data, mini-cart, languages and menus. Dynamic tags expose common product and customer-profile fields.

Commerce Core provides self-hosted font management through the WordPress media library. Registered font URLs must resolve to the same WordPress host and use supported local font formats; no Google Fonts/CDN dependency is required.

## Controlled custom code

`itk-commerce-code-manager` is a dedicated developer extension system rather than a normal content field. Only the `itk_manage_code` capability can manage snippets; Core grants it to administrators only by default.

Supported types are HTML, CSS, JavaScript, shortcodes, Elementor template IDs and conservatively validated PHP. Conditions can restrict snippets by language, mobile/desktop classification, user role, page type, product and product category. Every create/edit operation is saved **disabled** and requires a separate enable action after validation. The module keeps version history, an audit log, one-click rollback, fatal-error auto-disable and a global `ITK_COMMERCE_CODE_SAFE_MODE` emergency switch.

Complex or business-critical functionality should still be implemented as a real Commerce Suite module rather than PHP snippets.

## Validation and development ZIPs

GitHub Actions validates PHP/JavaScript syntax, JSON compatibility manifests, Core/Layout/Search/Multilingual contracts, document/Elementor/Code Manager release contracts, Code Manager parser safety, generic/customer separation and secret patterns. Chromium browser regression covers responsive/RTL/accessibility fixtures. After validation, every `packages/itk-commerce-*` package is built as its own ZIP artifact.

## Phase 8 and Phase 9 gates

- [`docs/HARDENING.md`](docs/HARDENING.md) defines security, accessibility, WooCommerce/HPOS, performance and rollback release gates.
- [`docs/PHASE-0-AUDIT.md`](docs/PHASE-0-AUDIT.md) records the real source/target WordPress, WooCommerce, PHP, HPOS, plugins and data-volume environment.
- [`docs/AL-LORD-ROLLOUT.md`](docs/AL-LORD-ROLLOUT.md) defines staging rehearsal, final data reconciliation, cutover and rollback for the first customer implementation.
- [`docs/PHASE-9-READINESS.md`](docs/PHASE-9-READINESS.md) separates repository readiness from checks that require the real customer environment.
- `client-profiles/al-lord/profile.json` contains the customer profile without production data or credentials.

## Engineering rules

- Namespace PHP code under `ITK\\Commerce` and use unique `itk_` identifiers where WordPress needs global names.
- Never modify WordPress, WooCommerce or Elementor core files.
- Prefer supported WordPress/WooCommerce APIs and public extension contracts.
- Keep reusable packages customer-neutral and customer configuration isolated under `client-profiles/`.
- New functionality must remain modular and must not create unintended side effects in unrelated modules.
- Database/schema changes require versioned/repeatable migrations and rollback planning.
- Inactive modules must not run frontend/background behavior.
- Never commit secrets, credentials, production customer/order data or database dumps.
- Every production deployment requires backups, staging verification and a defined rollback path.

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md)
- [`docs/ROADMAP.md`](docs/ROADMAP.md)
- [`docs/UPDATE-ROLLBACK.md`](docs/UPDATE-ROLLBACK.md)
- [`docs/MULTILINGUAL-TRANSFER.md`](docs/MULTILINGUAL-TRANSFER.md)
- [`docs/OPTIONAL-COMMERCE-MODULES.md`](docs/OPTIONAL-COMMERCE-MODULES.md)
- [`docs/HARDENING.md`](docs/HARDENING.md)
- [`docs/PHASE-0-AUDIT.md`](docs/PHASE-0-AUDIT.md)
- [`docs/AL-LORD-ROLLOUT.md`](docs/AL-LORD-ROLLOUT.md)

## Versioning

Development packages remain at `0.1.0-dev`. Stable releases use semantic versioning. Theme, Core and modules may be released independently subject to their compatibility manifests and the tested customer environment.

---

**Product owner and technical publisher:** IT-Kayali
