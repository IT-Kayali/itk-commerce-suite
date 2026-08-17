# Search & Filter Cache / Index Hardening

Phase 4 deliberately hardens the existing WooCommerce/WordPress query path instead of introducing a second catalog database, custom product table or speculative SQL index.

## What is cached

The module caches only the canonical taxonomy-option query used by the Search & Filter frontend to render choices such as categories and WooCommerce attributes.

A query is eligible only when all of the following are true:

- the request is a public Shop or product-taxonomy catalog request;
- exactly one taxonomy is requested;
- that taxonomy belongs to an enabled Search & Filter definition;
- `hide_empty` is enabled;
- at most the canonical 100 option terms are requested;
- ordering is `name ASC`;
- full term objects are requested;
- no include/exclude/slug/name/search/object/meta restrictions are present.

Other `get_terms()` calls from WordPress, WooCommerce or third-party plugins are not short-circuited.

## Cache storage

The cache uses the normal WordPress cache stack:

1. WordPress Object Cache group `itk_commerce_search_filter` when a persistent object-cache backend is available;
2. WordPress Transients as the cross-request fallback.

The logical cache key contains:

- cache generation;
- current locale;
- product taxonomy.

This prevents one language/locale from reusing another locale's rendered term names.

The default lifetime is 10 minutes and is bounded between one minute and one hour:

```php
add_filter( 'itk_commerce_search_filter_cache_ttl', function ( $ttl ) {
    return 10 * MINUTE_IN_SECONDS;
} );
```

## Versioned invalidation

Instead of scanning persistent cache backends and deleting an unknown number of physical keys, the module keeps a generation number in:

```text
itk_commerce_search_filter_cache_generation
```

All generated term-cache keys include that generation. Invalidation increments it, making all previous keys unreachable immediately.

Repeated relevant mutations inside the same PHP request collapse into one generation increment so a WooCommerce product save does not cause many option writes.

After invalidation the module fires:

```php
do_action( 'itk_commerce_search_filter_cache_invalidated', $generation );
```

## Automatic invalidation

The generation is invalidated for changes that can affect available filter terms/counts:

- product save;
- product variation save;
- product/variation deletion;
- assigning/removing relevant product taxonomy terms;
- creating/editing/deleting relevant product taxonomy terms;
- product or variation stock-status changes;
- WooCommerce global attribute add/update/delete.

Relevant taxonomies include `product_cat`, `product_tag`, `product_brand`, `product_visibility` and WooCommerce attribute taxonomies using `pa_*`.

Integrations may extend the invalidation taxonomy decision:

```php
add_filter( 'itk_commerce_search_filter_cache_taxonomy', function ( $invalidate, $taxonomy ) {
    if ( 'my_product_taxonomy' === $taxonomy ) {
        return true;
    }
    return $invalidate;
}, 10, 2 );
```

## Why there is no custom product index yet

The current Search & Filter module intentionally keeps WooCommerce authoritative:

- taxonomy filtering uses WordPress taxonomy queries;
- stock/rating visibility uses WooCommerce product-visibility terms;
- sale products use WooCommerce helpers;
- live discovery uses WooCommerce Store API;
- product/catalog visibility remains WooCommerce-owned.

A custom product-index table or custom SQL index would add migration, synchronization, multilingual, HPOS-adjacent operational and rollback complexity. Phase 4 therefore optimizes repeated metadata work first and keeps the core WooCommerce query/index paths intact.

A custom index should only be introduced after real profiling on a representative customer catalog shows a measured bottleneck that WooCommerce lookup tables, normal database indexes, object cache and the current bounded metadata cache cannot solve.

## Measurement guidance

Before adding a future custom index, record at minimum:

- product/variation count;
- active filter count and taxonomy cardinality;
- uncached vs cached catalog response time;
- database query count and slow-query samples;
- object-cache hit rate when a persistent cache is present;
- p50/p95 filter response time under representative concurrency.

Any future index change must remain versioned, migratable, rollback-safe and isolated inside the optional Search & Filter module.

## Regression contract

The Phase 4 cache contract test verifies that:

- canonical filter taxonomy queries can be cached and reused;
- unrelated term-query shapes are not intercepted;
- disabled/unconfigured taxonomies are not intercepted;
- generation invalidation makes older payloads unreachable;
- repeated invalidations within one request collapse to one generation write;
- product taxonomy assignments and taxonomy edits invalidate the next generation;
- invalidation exposes the public integration event.
