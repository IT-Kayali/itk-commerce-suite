# Search & Filter Module

`itk-commerce-search-filter` is the optional catalog discovery module. It uses the Phase 3 public Theme contracts but owns its business logic independently.

## Foundation services

The initial Phase 4 slice contains three reusable services:

- `FilterSchema` validates and bounds filter definitions;
- `UrlState` parses and serializes shareable public filter state;
- `WooQueryAdapter` converts normalized state into supported WooCommerce/WordPress product-query constraints.

`SearchFilterModule` loads the active-profile configuration, validates it and wires those services together.

## Filter definition schema

Schema version: `1`.

Supported filter types:

- `taxonomy`
- `price`
- `stock`
- `sale`
- `rating`

Taxonomy filters support product categories, product tags, product brands and `pa_*` WooCommerce product attributes by default. Additional product taxonomies require an explicit allow-list decision through:

```text
itk_commerce_search_filter_taxonomy_allowed
```

A normalized definition includes stable `id`, `type`, `label`, `query_key`, display metadata, order and enabled state. Taxonomy definitions may also contain `taxonomy`, `multiple` and `match` (`any`/`all`).

At most 32 definitions are accepted by the schema. Duplicate IDs and duplicate public query keys are discarded.

## Profile configuration

Customer-specific definitions live under:

```text
modules.configuration.itk-commerce-search-filter.filters.definitions
```

If no customer-specific definitions exist, neutral defaults are used for Category, Price, Availability, On sale and Rating.

Generic package code must never include production customer terms, categories, brands or attribute values.

## Public URL state

Only query keys declared in the normalized filter schema are parsed. Unknown request parameters are ignored by Search/Filter.

Example neutral URL state:

```text
?filter_category=fragrance,gifts&filter_price=20-150&filter_stock=in-stock&filter_rating=4
```

State rules:

- taxonomy selections are slug-normalized and capped;
- price uses `min-max`, permits an empty minimum or maximum and bounds numeric values;
- stock accepts `in-stock` or `out-of-stock`;
- sale accepts a normalized true value and serializes as `1`;
- rating accepts integers 1–5;
- serialization sorts query keys for stable/shareable URLs.

The normalized state can be filtered through:

```text
itk_commerce_search_filter_url_state
```

## Query integration

The foundation uses public WooCommerce product-query hooks. Existing WooCommerce visibility/category restrictions are preserved and the module appends only its normalized constraints.

Current adapters:

- taxonomy terms → `woocommerce_product_query_tax_query`
- stock → WooCommerce `product_visibility` out-of-stock term
- rating → WooCommerce `product_visibility` rated terms
- price → bounded `_price` meta-query foundation
- sale → intersection with WooCommerce's cached `wc_get_product_ids_on_sale()` result

The price implementation is intentionally a correctness-first foundation. Phase 4 cache/index work will replace expensive paths with a measured WooCommerce lookup/index strategy after the real catalog/environment audit.

## Future Phase 4 slices

Not yet implemented in this foundation:

- visual filter builder;
- server-rendered filter form;
- active-filter chips;
- AJAX catalog result replacement;
- browser history/state synchronization;
- mobile off-canvas filter UI;
- product search/autocomplete;
- cache/index optimization and invalidation.

These build on the bounded schema and URL/query contracts rather than replacing them.

## Security and compatibility boundary

The module does not:

- convert arbitrary request keys into tax/meta queries;
- execute raw SQL from request values;
- patch WooCommerce or Theme files;
- copy customer-specific production filter data into generic packages;
- replace WooCommerce product visibility rules;
- assume AJAX is required for filter correctness.

The completed filter UI must retain a normal URL/form fallback so catalog filtering remains usable when JavaScript is unavailable.
