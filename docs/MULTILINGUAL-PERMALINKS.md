# Multilingual translated permalink routing

The `itk-commerce-multilingual` package treats a translated slug as a language-specific URL projection of an existing WordPress/WooCommerce entity. It never creates a second WooCommerce product, variation, category, tag or attribute option in order to translate a URL.

## Supported route identities

The first permalink-routing slice supports:

```text
woocommerce.product.{PRODUCT_ID}.slug
woocommerce.term.product_cat.{TERM_ID}.slug
woocommerce.term.product_tag.{TERM_ID}.slug
woocommerce.term.pa_{attribute}.{TERM_ID}.slug
```

The numeric product/term ID remains authoritative. Product SKU, price, stock, tax data and order state are not copied into multilingual routing storage.

## Storage schema v2

Translation database schema version 2 adds two WordPress-prefixed tables:

```text
{prefix}itk_commerce_translation_routes
{prefix}itk_commerce_translation_route_aliases
```

`translation_routes` stores one current translated slug for one language + entity identity. `translation_route_aliases` stores previous translated slugs after a language-specific slug changes.

The current-route index enforces uniqueness by language, entity type and route hash. Term route hashes include the taxonomy, allowing the same visible slug in separate taxonomy route spaces while preventing ambiguity inside one taxonomy.

## Publication workflow

Slug translations use the same draft -> review -> published workflow as other translations, with an additional pre-publish validation contract:

```text
itk_commerce_translation_validate_publish
```

Before the reviewed revision is published, the permalink service validates:

- the translation key belongs to a supported existing WooCommerce entity;
- `sanitize_title()` produces a non-empty slug;
- the normalized slug remains within the supported WordPress slug length;
- the slug does not collide with another current translated route;
- the slug does not collide with a historical alias owned by another entity;
- the slug does not collide with another product/term technical source slug in the same route scope.

Only after validation succeeds does the translation workflow publish the revision. The post-publish projection then updates the route index. If the translated slug changed, the previous translated slug becomes an alias.

## Outgoing permalinks

WordPress/WooCommerce continue to build the technical permalink. The Multilingual module then changes only the route boundary:

```text
technical product
/product/oud-royal/

German projection
/de/product/oud-koenig/

Arabic projection
/ar/product/oud-maliki/
```

Hierarchical product-category segments that are present in a product permalink are localized where indexed. Supported taxonomy links localize their own hierarchy as well.

A target language without a published slug translation falls back to the technical source slug:

```text
/en/product/oud-royal/
```

It does **not** borrow the German or Arabic translated slug.

## Incoming route resolution

Directory routing first establishes the public language context. WordPress then parses its normal rewrite rules. On the public `request` query-var boundary, the Multilingual module resolves supported translated route segments back to technical source slugs before the normal query executes.

Example:

```text
/de/product/oud-koenig/
             |
             +-> request product=oud-royal
                 -> existing WooCommerce product ID 42
```

The database product `post_name` stays `oud-royal`; the translated URL never becomes the commercial identity.

The same pattern applies to product categories, product tags and global attribute taxonomies such as `pa_color`.

## Historical slug redirects

When a translated slug changes:

```text
/de/product/oud-koenig/
          -> /de/product/oud-premium/
```

The old translated slug remains indexed as an alias for the same product/language. WordPress canonical redirect handling is refined so the alias redirects to the **current translated same-language permalink**, not to the technical source URL.

This provides clean redirect behavior for changed translated slugs without changing WordPress/WooCommerce source identity.

## Language switcher, canonical and hreflang

`itk_commerce_language_url` is the shared URL contract. The base language router first creates a safe directory-style same-route URL. The translated permalink service then replaces it with the target language's entity permalink when the current queried object is a supported product/taxonomy entity.

The language switcher and Multilingual SEO layer both consume this shared contract. Therefore a product can emit:

```text
hreflang=de -> /de/product/oud-koenig/
hreflang=ar -> /ar/product/oud-maliki/
hreflang=en -> /en/product/oud-royal/
```

`x-default` still points to the configured default language.

## Request boundaries

Normal storefront route resolution is disabled for admin, cron, AJAX and REST requests. Async link localization remains possible only through the already-existing explicit WooCommerce session-language contract; the module never guesses an AJAX/REST language from an unprefixed endpoint.

## Public services

Integrations can retrieve the routing layer through:

```php
$service = apply_filters( 'itk_commerce_translated_permalink_service', null );
$routes  = apply_filters( 'itk_commerce_translated_route_repository', null );
```

The internal technical source slug must not be overwritten merely to achieve a translated public permalink.

## Current boundary

This slice covers WooCommerce product and product-taxonomy entity slugs. Generic WordPress page/post slugs, translated route-base labels (for example a localized `product-category` base), sitemap integration and admin translation surfaces remain separate concerns and must be added through compatible follow-up slices rather than by changing WordPress/WooCommerce core files.
