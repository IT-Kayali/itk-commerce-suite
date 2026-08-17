=== IT-Kayali Commerce Multilingual ===
Contributors: itk-kayali
Tags: multilingual, woocommerce, rtl, translation, commerce, hreflang
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Reusable language routing, translation workflow, SEO context and RTL/LTR module for the IT-Kayali Commerce Suite.

== Description ==

IT-Kayali Commerce Multilingual is an optional installable Commerce Suite module.

The current development foundation provides:

* versioned and bounded active-profile language configuration;
* default and fallback language contracts;
* directory-style storefront routes such as /de/, /ar/ and /en/;
* storefront locale selection through public WordPress locale APIs;
* accessible language switching and RTL/LTR document state;
* localized canonical targets, hreflang alternates and x-default;
* module-owned versioned translation entry/revision tables;
* append-only draft/review/published translation workflow with pre-publish validation;
* product/variation/category/tag/attribute display-text translation on existing WooCommerce identities;
* language-specific product, product-category, product-tag and global-attribute term slugs;
* indexed translated-route uniqueness plus historical translated-slug aliases;
* same-entity language URLs for the switcher, canonical and hreflang output;
* WooCommerce customer-session language persistence;
* classic and Store API order-language capture through WC_Order CRUD meta;
* isolated stored-language rendering for transactional order emails and future Commerce documents.

Translator admin/capabilities and translation CSV/JSON/XLIFF import/export remain follow-up slices.

== Architecture ==

The module depends on IT-Kayali Commerce Core and registers itself through the Commerce Suite module registry. Customer language lists/settings belong to the active versioned customer profile. Translation content and translated-route indexes are module-owned rather than Theme-owned.

WooCommerce remains authoritative for product/variation IDs, term IDs, technical source slugs, prices, SKU, stock, tax, cart contents, payment state and orders. A translated permalink is a language-specific URL projection of the same existing entity, not a duplicated product or taxonomy term.

== Language switcher ==

Use [itk_language_switcher]. Optional display modes are label, code and both. When the current page is a supported WooCommerce product/taxonomy entity, each switcher target uses that target language's published entity slug. If a target language has no slug translation, it uses the technical source slug rather than another language's slug.

== SEO / hreflang ==

The current localized route is canonicalized into its language directory. Every enabled language receives a same-entity hreflang alternate and x-default points to the configured default language. Entity-aware alternates use each target language's own published product/category/tag/attribute slug.

SEO targets do not copy current tracking, catalog-filter, nonce or action query parameters. Search, 404, feed, trackback, preview, admin, AJAX and REST requests do not receive default Multilingual SEO head output.

== Translation workflow ==

Customer-facing lookup reads published revisions only. Editing a published translation creates a new draft and keeps the previous live revision visible until the replacement passes review and is explicitly published.

Specialized translation consumers can validate reviewed revisions through itk_commerce_translation_validate_publish before the live pointer changes. The translated-permalink service uses this boundary to reject empty, oversized or conflicting slugs before publication.

== Translated WooCommerce permalinks ==

Supported slug translation keys use the existing entity identity:

woocommerce.product.42.slug
woocommerce.term.product_cat.7.slug
woocommerce.term.product_tag.11.slug
woocommerce.term.pa_color.13.slug

Translation DB schema version 2 adds current translated-route and historical-alias indexes. The technical WordPress/WooCommerce post_name/term slug is not overwritten.

Outgoing product and supported taxonomy links use the current language's published route. Incoming translated route query vars are mapped back to the existing technical source slug before WordPress/WooCommerce executes the normal query.

Changing a translated slug preserves the previous translated slug as a same-language alias. Requests for the old translated slug redirect to the current translated permalink instead of redirecting to the technical source URL.

Route publication rejects collisions with another translated current/alias route and with another entity's existing technical source slug in the same route scope.

== WooCommerce language context ==

The selected storefront language is persisted in the WooCommerce customer session under itk_commerce_language. Classic checkout and Checkout Block/Store API capture language, WordPress locale and text direction on the existing WC_Order object through CRUD metadata.

These values are language context only; they do not duplicate WooCommerce order ownership, items, totals, stock, taxes, payment state or HPOS data.

== Order email and document rendering ==

WooCommerce transactional order notifications and manual order-email resends run inside the frozen order-language scope. The previous WordPress locale and Commerce language are restored afterwards, including exception paths.

Future invoice, delivery-note, return-slip and pack-list renderers can obtain itk_commerce_order_language_scope and call run($order, $callback) instead of implementing a separate locale stack.

== Development status ==

Version 0.1.0-dev is a development build and not a production release.
