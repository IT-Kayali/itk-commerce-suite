=== IT-Kayali Commerce Search & Filter ===
Contributors: it-kayali
Tags: woocommerce, search, filter, catalog
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Reusable catalog search/filter module for the IT-Kayali Commerce Suite.

== Description ==

This installable module owns optional WooCommerce catalog discovery functionality. The Theme only exposes presentation slots; Search/Filter owns filter definitions, public URL state, product-query adaptation and later AJAX/search behavior.

The 0.1.0-dev foundation includes:

* versioned bounded filter definitions;
* product category and product taxonomy/attribute filter contracts;
* price, stock, on-sale and rating state contracts;
* shareable URL parsing/serialization based only on allow-listed filter keys;
* profile-driven filter definition loading;
* WooCommerce product query adaptation through supported tax/meta/query hooks;
* independent Core module registration and activation state.

Server-rendered filter UI, AJAX catalog replacement, mobile off-canvas filters, product autocomplete and cache/index optimization are Phase 4 follow-up slices.

== Architecture ==

The module requires IT-Kayali Commerce Core and WooCommerce. It does not patch WooCommerce core or the IT-Kayali Theme. Customer-specific filter choices belong in the active customer profile under the module configuration namespace.

== URL State ==

Every exposed request key must originate from a normalized filter definition. Unknown request keys are ignored by the module. Taxonomy values are normalized to slugs, price ranges are bounded numeric ranges, stock/sale/rating values use fixed allow-lists.

== Compatibility ==

WooCommerce version ranges are intentionally not declared as production-supported until the Phase 0 environment audit and compatibility matrix are complete. See compatibility.json and docs/SEARCH-FILTER.md in the Commerce Suite repository.
