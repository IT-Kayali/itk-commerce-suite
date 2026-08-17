=== IT-Kayali Commerce Multilingual ===
Contributors: itk-kayali
Tags: multilingual, woocommerce, rtl, translation, commerce
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Reusable language routing, translation workflow and RTL/LTR module for the IT-Kayali Commerce Suite.

== Description ==

IT-Kayali Commerce Multilingual is an optional installable Commerce Suite module.

The current development foundation provides:

* versioned and bounded active-profile language configuration;
* default and fallback language contracts;
* enabled-language request context and RTL/LTR document state;
* directory-style storefront routes such as /de/, /ar/ and /en/;
* normal WordPress/WooCommerce route parsing after the language prefix is resolved;
* storefront locale selection through public WordPress locale APIs;
* safe same-origin language URLs and an accessible [itk_language_switcher];
* module-owned versioned translation entry/revision tables;
* append-only draft/review/published translation workflow;
* immutable published revisions while replacement drafts are edited;
* published translation lookup with fallback-language/source fallback;
* deterministic source hashes for later stale-translation detection;
* read-only WooCommerce product/variation name and description mapping;
* product category/tag and global attribute-term text mapping by existing taxonomy + term ID;
* global and product-local attribute label translation mapping without changing WooCommerce identity.

WooCommerce cart/session/order/email language context, hreflang/canonical policy, translator admin/capabilities and import/export remain separate follow-up slices.

== Architecture ==

The module depends on IT-Kayali Commerce Core and registers itself through the Commerce Suite module registry.

Customer language lists and language settings belong to the active versioned customer profile. Translation content is stored in module-owned WordPress-prefixed tables rather than Theme files or one growing serialized profile option.

The Theme remains a presentation consumer. WooCommerce continues to own product and variation IDs, prices, SKU, stock, tax, slugs, cart state and order state. The multilingual mapper changes only customer-facing textual view values on the existing WooCommerce identities.

== Language switcher ==

Use the shortcode:

[itk_language_switcher]

Optional display modes are label, code and both. Themes/builders can consume the public switcher filters and style the stable itk-language-switcher classes.

== Translation lookup ==

Reusable components can use the itk_commerce_translate_text filter with a source/default string, stable machine key and optional explicit language code. Only published revisions are returned to customer-facing output. Missing target translations fall back to the configured fallback language and finally the caller-provided source string.

Published text stays live while a newer draft or review revision exists. A replacement becomes visible only after review and explicit publish; the previous published revision is then archived as history.

== WooCommerce mapping ==

Product and variation text uses the original WooCommerce object ID, for example:

woocommerce.product.42.name
woocommerce.product.42.short-description
woocommerce.product.42.description

Product category, product tag and global attribute-option terms use the existing taxonomy and term ID, for example:

woocommerce.term.product_cat.7.name
woocommerce.term.pa_color.13.name

Global attribute labels use keys such as:

woocommerce.attribute.pa_color.label

Product-local attribute labels include the original product ID. Product/category/attribute slugs and all commercial values remain unchanged in this slice.

Admin, AJAX and REST mapping is intentionally deferred until the next slice provides an explicit persisted cart/session/order language context.

== Development status ==

Version 0.1.0-dev is a development build and not a production release.
