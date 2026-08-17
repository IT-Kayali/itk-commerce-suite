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
    {
      "code": "de",
      "locale": "de_DE",
      "label": "Deutsch",
      "direction": "ltr",
      "enabled": true
    },
    {
      "code": "ar",
      "locale": "ar",
      "label": "العربية",
      "direction": "rtl",
      "enabled": true
    },
    {
      "code": "en",
      "locale": "en_US",
      "label": "English",
      "direction": "ltr",
      "enabled": true
    }
  ]
}
```

Generic package code contains no customer-specific language list.

## Schema boundaries

- maximum 20 configured languages;
- duplicate public language codes are discarded;
- unsafe language codes/locales are rejected;
- at least one language is always enabled;
- default/fallback must refer to enabled languages;
- public codes use bounded lowercase URL form such as `de`, `ar`, `en`, `pt-br`;
- WordPress locales remain separate values such as `de_DE`, `en_US` or `ar`;
- direction is limited to `ltr` or `rtl`.

If no Multilingual profile configuration exists, the module derives one neutral enabled language from the current WordPress locale.

## Public request context

The module exposes normalized request state through these filters:

```php
$context = apply_filters( 'itk_commerce_language_context', array() );
$code    = apply_filters( 'itk_commerce_current_language', '' );
$dir     = apply_filters( 'itk_commerce_text_direction', 'ltr' );
$route   = apply_filters( 'itk_commerce_language_route_context', array() );
```

The language context contains the current public code, WordPress locale, direction, fallback language and enabled language definitions. The route context additionally exposes the internal storefront path, whether the public URL contained an explicit language prefix and whether the WordPress locale switcher was invoked successfully.

When the selected request language changes the module fires:

```php
do_action( 'itk_commerce_language_context_changed', $code, $previous_code, $language );
```

## Directory-style routing

Configured enabled languages can be addressed as directories:

```text
/de/
/ar/
/en/
/de/shop/
/ar/produkt/beispiel/
```

The routing service resolves the language directory before WordPress performs its normal permalink matching. During `WP::parse_request()` it temporarily removes only the language prefix from `REQUEST_URI` / `PATH_INFO`, allowing the existing WordPress and WooCommerce rewrite rules to resolve the remaining route normally. The original localized request globals are restored immediately after parsing, with a shutdown safety net.

This design intentionally avoids duplicating WordPress/WooCommerce rewrite semantics and does not create language-specific copies of product price, SKU, stock, cart state or order state.

Unprefixed storefront URLs remain valid and use the configured default language context. Redirect/canonical policy for unprefixed URLs is intentionally deferred to the SEO/hreflang slice.

## WordPress locale bridge

For storefront requests, the selected Commerce language locale is applied through WordPress's public locale surfaces:

- `switch_to_locale()` is attempted before the module forces locale filters, allowing WordPress to reload installed translations when possible;
- `pre_determine_locale`, `determine_locale` and `locale` follow the selected storefront language context;
- admin, AJAX, cron, installation and already-declared REST requests are not forced into the storefront locale.

The module fires `itk_commerce_wordpress_locale_applied` with the locale, public language code and boolean switch result.

## Safe language URLs

Generate a language URL through the public filter:

```php
$url = apply_filters( 'itk_commerce_language_url', '', 'ar', '' );
```

The router preserves the same-origin storefront route and safe query state. Action/nonces such as `_wpnonce`, `add-to-cart`, `remove_item`, `wc-ajax` and generic action/security parameters are dropped to avoid repeating state-changing requests when a visitor changes language. External source URLs are never reflected into switcher destinations.

## Language switcher

A style-neutral accessible switcher is available through:

```text
[itk_language_switcher]
```

Optional shortcode values:

```text
[itk_language_switcher display="label"]
[itk_language_switcher display="code"]
[itk_language_switcher display="both" class="header-language"]
```

The output uses stable `itk-language-switcher*` classes, `hreflang`, per-link `lang` / `dir` and `aria-current="page"` for the selected language. Theme, Elementor or other presentation packages may style this markup or replace it deliberately through `itk_commerce_language_switcher_html`. The normalized item list can be filtered with `itk_commerce_language_switcher_items`.

## Theme / document direction

The request context adds stable body classes such as:

```text
itk-language-ar
itk-direction-rtl
```

It also aligns WordPress's public HTML language attributes with the Commerce context. The Theme should continue using logical CSS properties (`margin-inline`, `padding-inline`, `inset-inline`, etc.) rather than duplicated RTL stylesheets wherever possible.

## Remaining Phase 5 boundaries

The current implementation does **not** yet:

- store translated Commerce/customer strings;
- implement draft/review/published translation workflow;
- map translated product/category/attribute content;
- persist cart/session language or capture order language;
- switch WooCommerce email/document generation into stored order language;
- emit canonical/hreflang tags;
- import/export CSV/JSON/XLIFF translations;
- create the translator role/capability boundary.

These remain isolated follow-up slices so data ownership and WooCommerce lifecycle behavior can be tested independently.

## Next slice

The next workstream introduces the translation repository/schema and draft/review/published workflow foundation without changing WooCommerce commercial data ownership.
