# IT-Kayali Commerce Multilingual

`itk-commerce-multilingual` is an optional installable Commerce Suite module. It owns language context, translation workflow and language-specific routing contracts so the reusable Theme does not become a translation database.

## Language profile schema

Language configuration is versioned and stored under the active customer profile's module configuration for `itk-commerce-multilingual`.

```json
{
  "schema_version": 1,
  "default": "de",
  "fallback": "de",
  "languages": [
    {"code":"de","locale":"de_DE","label":"Deutsch","direction":"ltr","enabled":true},
    {"code":"ar","locale":"ar","label":"العربية","direction":"rtl","enabled":true},
    {"code":"en","locale":"en_US","label":"English","direction":"ltr","enabled":true}
  ]
}
```

Generic package code contains no customer-specific language list. Public codes are bounded lowercase URL values such as `de`, `ar`, `en` or `pt-br`; WordPress locales remain separate values such as `de_DE`, `en_US` or `ar`.

## Public request context

```php
$context = apply_filters( 'itk_commerce_language_context', array() );
$code    = apply_filters( 'itk_commerce_current_language', '' );
$dir     = apply_filters( 'itk_commerce_text_direction', 'ltr' );
$route   = apply_filters( 'itk_commerce_language_route_context', array() );
```

The context exposes the selected language, locale, direction, fallback and enabled languages. Stable body classes such as `itk-language-ar` and `itk-direction-rtl` plus bounded HTML `lang` / `dir` attributes keep the Theme presentation-aware without making it the language store.

## Directory routing and locale

Configured languages can be addressed as directories such as `/de/`, `/ar/` and `/en/`. During normal WordPress request parsing, the routing service removes only the language prefix temporarily, lets existing WordPress/WooCommerce permalink rules resolve the remaining path, then restores the public localized request URI.

Unprefixed routes remain valid in the configured default language. Canonical redirect policy is intentionally deferred to the SEO slice.

For storefront requests the module also attempts `switch_to_locale()` and aligns the public WordPress locale filters with the selected Commerce language. Admin, AJAX, cron, installation and already-declared REST requests are not forced into storefront locale state.

## Safe language URLs and switcher

```php
$url = apply_filters( 'itk_commerce_language_url', '', 'ar', '' );
```

Same-origin paths and non-action query state are preserved. Nonces and state-changing parameters such as `add-to-cart`, `remove_item`, `wc-ajax`, generic `action`/`security` values and language parameters are dropped before building another language URL.

A style-neutral accessible switcher is available through:

```text
[itk_language_switcher]
[itk_language_switcher display="code"]
[itk_language_switcher display="both" class="header-language"]
```

Theme, Elementor or other presentation packages may style the stable `itk-language-switcher*` classes or replace the output through the public switcher filters.

## Translation repository

Translations are not stored in the Theme and are not packed into one growing serialized customer-profile option. The Multilingual module owns two versioned WordPress-prefixed tables:

```text
{prefix}itk_commerce_translation_entries
{prefix}itk_commerce_translation_revisions
```

An entry represents one stable machine translation key + public language code. Each entry stores a deterministic source hash plus current/published revision pointers. Revisions are append-only and contain translated value, revision number, workflow status, author/reviewer IDs and timestamps.

## Draft / review / published workflow

Customer-facing lookup reads **published revisions only**. Editing a live translation creates a new draft without replacing the current published value.

```text
draft -> review -> published
          |
          +-> draft
```

Published revisions are immutable. Publishing a newer reviewed revision archives the previous published revision and switches the live pointer only after the replacement is ready.

## Public translation lookup

```php
$text = apply_filters(
    'itk_commerce_translate_text',
    'Pay now',
    'commerce.checkout.pay',
    ''
);
```

Lookup order is requested/current language, configured fallback language, then the caller-provided source text. Programmatic integrations can obtain the repository/workflow through `itk_commerce_translation_repository` and `itk_commerce_translation_workflow`.

## WooCommerce entity translation mapping

The module maps **display text onto existing WooCommerce identities** instead of creating a separate product per language.

### Product text

WooCommerce product/variation view getters are mapped for:

```text
name
short description
description
```

Stable keys use the existing WooCommerce object ID:

```text
woocommerce.product.42.name
woocommerce.product.42.short-description
woocommerce.product.42.description
```

The same convention works for a variation because a variation already has its own WooCommerce ID. No translated copy of the product object is created.

### Categories, tags and attribute terms

Product category/tag and global attribute-option terms use the original taxonomy + term ID:

```text
woocommerce.term.product_cat.7.name
woocommerce.term.product_cat.7.description
woocommerce.term.product_tag.11.name
woocommerce.term.pa_color.13.name
```

Term objects are cloned before the translated `name` / `description` is applied. That avoids mutating the shared WordPress term object/cache instance when a process later changes language context. Term IDs and slugs stay unchanged in this slice.

### Attribute labels

Global attribute labels use the original taxonomy name:

```text
woocommerce.attribute.pa_color.label
```

Product-local attribute labels include the existing product ID:

```text
woocommerce.product.42.attribute.bottle-size.label
```

Attribute taxonomy names, option IDs/term IDs and variation identity are not replaced.

### Public WooCommerce mapper

The active mapper is available through:

```php
$mapper = apply_filters( 'itk_commerce_woocommerce_translation_mapper', null );
```

This allows later admin/import integrations to generate exactly the same entity translation keys without coupling to Theme templates.

## Commercial-data boundary

The mapping layer deliberately does **not** translate or copy:

- product/variation IDs;
- SKU;
- regular/sale/active price;
- stock quantity/status;
- tax classes/rates;
- product/category/attribute slugs;
- cart/order/payment state.

WooCommerce remains authoritative for all of those values. The mapper runs only on customer-facing textual view surfaces.

Admin, AJAX and REST entity mapping is intentionally skipped for now. The next WooCommerce language-context slice will persist the selected language into the customer session/order so AJAX, Store API, cart and emails can use an explicit language instead of guessing from the default request context.

## Output safety

Translation persistence does not apply one global HTML sanitizer because output contexts differ. The consuming WooCommerce/Theme component remains responsible for escaping/sanitizing translated content correctly for plain text, controlled HTML, attributes, JSON, email text and documents.

## Remaining Phase 5 boundaries

The current implementation does **not** yet:

- persist cart/session language or capture order language;
- translate AJAX/Store API responses using explicit persisted customer language;
- switch WooCommerce email/document generation into stored order language;
- emit translated slugs/canonical/hreflang policy;
- import/export CSV/JSON/XLIFF translations;
- create the translator role/capability/admin UI boundary;
- provide final end-to-end RTL/accessibility browser coverage for translated commerce content.

## Next slice

The next workstream adds WooCommerce cart/session/order language persistence and an explicit email/document language context while preserving HPOS/WooCommerce CRUD ownership.
