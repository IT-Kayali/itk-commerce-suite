# IT-Kayali Commerce Multilingual

`itk-commerce-multilingual` is an optional installable Commerce Suite module. It owns language context, translation workflow and language-specific routing contracts so the reusable Theme does not become a translation database.

## Phase 5 foundation

The first slice establishes a bounded, versioned language model before URL rewriting or translation storage is introduced.

Example profile configuration:

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

The configuration belongs under the active customer profile's module configuration for `itk-commerce-multilingual`. Generic package code contains no customer-specific language list.

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
```

The context contains:

- current public code;
- WordPress locale;
- text direction;
- fallback code;
- current language definition;
- enabled language definitions.

When the selected request language changes the module fires:

```php
do_action( 'itk_commerce_language_context_changed', $code, $previous_code, $language );
```

Later routing/session/order/email slices build on this contract instead of inventing separate language state.

## Theme / document direction

The foundation adds stable body classes:

```text
itk-language-ar
itk-direction-rtl
```

It also aligns WordPress's public HTML language attributes with the selected Commerce context, producing bounded `lang` and `dir` values.

The Theme should continue using logical CSS properties (`margin-inline`, `padding-inline`, `inset-inline`, etc.) rather than maintaining separate duplicated RTL stylesheets wherever possible.

## Deliberate Phase 5 boundaries

This foundation does **not** yet:

- add `/de/`, `/ar/`, `/en/` rewrite rules;
- switch the global WordPress locale based on a URL;
- store translations;
- duplicate products per language;
- alter WooCommerce stock, SKU or price ownership;
- add hreflang tags;
- store order language;
- translate WooCommerce emails/documents;
- import/export XLIFF.

Those are isolated follow-up slices so routing, data model and WooCommerce lifecycle behavior can be tested independently.

## Planned next slice

The next Multilingual workstream introduces directory-style routing and a language switcher while preserving the current storefront path/query state safely. That routing service will select one of the already-normalized enabled languages through `LanguageContext`.
