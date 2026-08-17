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

Translations are **not** stored in the Theme and are not packed into one growing serialized customer-profile option. The Multilingual module owns two versioned WordPress-prefixed tables:

```text
{prefix}itk_commerce_translation_entries
{prefix}itk_commerce_translation_revisions
```

An entry represents one stable machine translation key + public language code. Example keys:

```text
commerce.checkout.pay
commerce.header.welcome
customer.footer.tagline
```

Each entry stores a deterministic hash of its current source/default string plus pointers to its current and published revisions. Revisions are append-only and contain the translated value, revision number, workflow status, author/reviewer IDs and timestamps.

The storage schema is installed with an independent database schema version. Plugin updates check that version again because WordPress plugin activation hooks are not an update migration mechanism.

## Draft / review / published workflow

Customer-facing lookup reads **published revisions only**. Editing a live translation therefore creates a new draft revision without replacing the existing published value.

Supported workflow:

```text
draft -> review -> published
          |
          +-> draft
```

Published revisions are immutable. When a newer reviewed revision is published, the previous published revision becomes archived history and the entry pointer switches to the newly published revision.

This means:

- an unfinished draft never appears in the storefront;
- a review revision never appears in the storefront;
- rejecting/returning a review does not alter the current live translation;
- revision history remains available instead of overwriting previous text;
- the previous live revision is archived only when the replacement successfully publishes.

## Public translation lookup

Reusable components can request a published translation without depending on repository internals:

```php
$text = apply_filters(
    'itk_commerce_translate_text',
    'Pay now',
    'commerce.checkout.pay',
    ''
);
```

An empty language argument uses the current Commerce request language. Lookup order is:

1. published translation for the requested/current language;
2. published translation for the configured fallback language;
3. caller-provided source/default string.

Programmatic integrations can obtain the repository/workflow through `itk_commerce_translation_repository` and `itk_commerce_translation_workflow`. Workflow events are emitted when drafts are created, submitted for review, returned to draft and published.

Persistence deliberately does not apply display-context escaping to translation values because the consuming component owns its output context. Consumers must escape/sanitize according to whether the value is plain text, controlled HTML, an attribute, JSON, email text, etc.

## Source-change tracking

Each translation entry stores a SHA-256 `source_hash`. This does not silently invalidate or remove a published translation. It provides a stable foundation for a later translation-management UI to flag translations as potentially stale when the source string changes.

## Data ownership boundary

This translation repository is currently for Commerce Suite strings and customer-owned textual values. Product/category/attribute translation mapping is the next isolated slice.

The current repository does **not** duplicate or take ownership of WooCommerce commercial data. Price, SKU, stock, tax, cart and order state remain WooCommerce-owned.

## Remaining Phase 5 boundaries

The current implementation does **not** yet:

- map product/category/attribute translation fields;
- persist cart/session language or capture order language;
- switch WooCommerce email/document generation into stored order language;
- emit canonical/hreflang tags;
- import/export CSV/JSON/XLIFF translations;
- create the translator role/capability/admin UI boundary;
- provide final end-to-end RTL/accessibility browser coverage for translated commerce content.

## Next slice

The next workstream adds product/category/attribute translation mapping while preserving one shared WooCommerce commercial identity for stock, SKU and price.
