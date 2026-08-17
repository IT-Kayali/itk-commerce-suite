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

For normal storefront requests the module attempts `switch_to_locale()` and aligns public WordPress locale filters with the selected Commerce language. WooCommerce async requests use the separately persisted customer-session language described below instead of guessing from an unprefixed AJAX/REST URL.

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

## Translation repository and workflow

Translations are not stored in the Theme and are not packed into one growing serialized customer-profile option. The module owns versioned WordPress-prefixed entry/revision tables. Entries use one stable translation key + public language code; revisions are append-only and retain workflow status, author/reviewer IDs, timestamps and source hashes.

Customer-facing lookup reads **published revisions only**:

```text
draft -> review -> published
          |
          +-> draft
```

Published revisions are immutable. Publishing a newer reviewed revision archives the previous published revision and switches the live pointer only after the replacement is ready.

```php
$text = apply_filters(
    'itk_commerce_translate_text',
    'Pay now',
    'commerce.checkout.pay',
    ''
);
```

Lookup order is requested/current language, configured fallback language, then caller source text.

The effective lookup language can be isolated through `itk_commerce_translation_language_code`. Normal callers should not set that globally; the order-language renderer uses it only inside a bounded rendering scope so historical orders can still read translations in their frozen language.

## WooCommerce entity translation mapping

The module maps **display text onto existing WooCommerce identities** instead of creating a separate product per language.

Product/variation keys use the existing WooCommerce object ID:

```text
woocommerce.product.42.name
woocommerce.product.42.short-description
woocommerce.product.42.description
```

Product category/tag and global attribute-option terms use original taxonomy + term ID:

```text
woocommerce.term.product_cat.7.name
woocommerce.term.product_tag.11.name
woocommerce.term.pa_color.13.name
```

Global attribute labels use keys such as `woocommerce.attribute.pa_color.label`; product-local labels include the original product ID. Term objects are cloned before localized `name` / `description` mutation so shared cached objects are not polluted.

The mapper is available through:

```php
$mapper = apply_filters( 'itk_commerce_woocommerce_translation_mapper', null );
```

## WooCommerce session language

The localized storefront URL is the source of truth for ordinary page navigation. Once WooCommerce has initialized its customer session, the selected public language is stored under:

```text
itk_commerce_language
```

Rules:

1. an explicit localized route such as `/ar/...` stores `ar` in WooCommerce session state;
2. an unprefixed normal storefront route stores its configured/default request language;
3. AJAX/REST/Store API requests restore a valid persisted session language into the Commerce request context;
4. invalid, disabled or malformed session language values are ignored;
5. async WooCommerce entity translation is allowed only after that valid session language has been restored.

The current session language is exposed through:

```php
$code = apply_filters( 'itk_commerce_woocommerce_session_language', '' );
```

This keeps mini-cart/cart/checkout async traffic aligned with the language the shopper explicitly selected, without encoding commercial state into translation storage.

## Order language capture

Both classic checkout and Checkout Block/Store API capture the selected WooCommerce session language onto the existing `WC_Order` object through WooCommerce CRUD metadata.

Stored keys:

```text
_itk_commerce_language
_itk_commerce_locale
_itk_commerce_direction
```

The language code identifies the public Commerce language. Locale and direction are frozen as a historical snapshot so future removal/renaming of a configured language does not erase the language context of old orders.

Classic checkout captures the snapshot while WooCommerce is constructing the order. Store API checkout uses its public order-meta hook. No direct `wp_posts`, postmeta or HPOS table access is used.

Read the frozen order context through:

```php
$order_context = apply_filters(
    'itk_commerce_order_language_context',
    array(),
    $order
);
```

Returned values include `code`, `locale`, `direction` and whether the language is still configured. Historical stored locale/direction remain available even if the language is no longer enabled.

## WooCommerce email language scope

WooCommerce transactional order emails are rendered inside the frozen order language **before the actual email trigger executes**. This is necessary because WooCommerce customer email classes can call `setup_locale()` before assigning the current order object to the email instance.

The Multilingual module wraps WooCommerce's `..._notification` actions for order-related transactional events. WooCommerce uses those notification actions both for immediate transactional email delivery and when its deferred email queue later processes an event, so the same language contract applies in both modes.

While the explicit order scope is active:

- the Commerce language context is selected when the stored code is still enabled;
- WordPress is switched to the order's frozen locale with `switch_to_locale()`;
- `woocommerce_allow_switching_email_locale` is forced to false so `WC_Email::setup_locale()` cannot replace the order locale with the shop locale;
- `woocommerce_allow_restoring_email_locale` is forced to false so WooCommerce does not restore a locale stack it does not own;
- Commerce translation lookup is scoped to the stored order language code;
- after the notification, the previous WordPress locale and Commerce language are restored.

Non-order notifications do not enter an active scope and therefore retain normal WooCommerce locale behavior.

### Manual order-email resend

WooCommerce's admin resend flow is wrapped with its public `woocommerce_before_resend_order_emails` and `woocommerce_after_resend_order_email` hooks. Customer invoice and new-order resends therefore use the same frozen order-language contract and restore the previous locale afterwards.

Programmatic integrations that trigger email classes directly outside WooCommerce's transactional/resend flows should explicitly use the public order-language scope below.

## Document / programmatic order-language scope

The documents package is intentionally separate and may not be installed. The Multilingual module therefore exposes a reusable rendering service instead of hard-coding a dependency on `itk-commerce-documents`:

```php
$scope = apply_filters( 'itk_commerce_order_language_scope', null );

if ( $scope ) {
    $pdf = $scope->run(
        $order,
        function ( $order_context ) use ( $order ) {
            // Render invoice / delivery note / return slip / pack list here.
            return render_document( $order, $order_context );
        }
    );
}
```

`run()` returns the callback result. Restoration is implemented with `finally`, so exceptions during PDF/email rendering cannot leak the order locale into subsequent operations in the same PHP process.

Scope lifecycle actions:

```text
itk_commerce_order_language_scope_entered
itk_commerce_order_language_scope_left
```

These allow later document/font/RTL integrations to react without duplicating locale handling.

## Historical disabled languages

An old order may reference a language that is no longer enabled in the current customer profile. The order snapshot remains authoritative for historical rendering:

- its stored locale is still used for WordPress/WooCommerce strings when that locale is installed;
- its stored language code temporarily scopes Commerce translation lookup;
- its stored direction remains available to PDF/document renderers;
- the language is **not** globally re-enabled and normal storefront routing remains unchanged.

This keeps old invoices/emails reproducible without changing current storefront language configuration.

## Commercial-data boundary

The multilingual layer deliberately does **not** translate or copy:

- product/variation IDs;
- SKU;
- regular/sale/active price;
- stock quantity/status;
- tax classes/rates;
- product/category/attribute slugs in the current slice;
- cart contents/totals;
- payment state;
- WooCommerce order ownership/state.

WooCommerce remains authoritative for all of those values. Multilingual stores only translation content and language-context metadata.

## Output safety

Translation persistence does not apply one global HTML sanitizer because output contexts differ. The consuming WooCommerce/Theme/document component remains responsible for escaping/sanitizing translated content correctly for plain text, controlled HTML, attributes, JSON, email text and documents.

## Remaining Phase 5 boundaries

The current implementation does **not** yet:

- emit translated slugs/canonical/hreflang policy;
- import/export CSV/JSON/XLIFF translations;
- create the translator role/capability/admin UI boundary;
- provide final end-to-end RTL/accessibility browser coverage for translated commerce content.

## Next slice

The next workstream implements language-aware SEO/hreflang and translation import/export foundations while keeping localized route/canonical policy separate from WooCommerce commercial identities.
