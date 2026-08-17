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
* global and product-local attribute label translation mapping without changing WooCommerce identity;
* WooCommerce customer-session language persistence for localized storefront requests;
* explicit session-language restore for AJAX and Store API requests;
* classic Checkout and Checkout Block/Store API order-language capture through WC_Order CRUD meta;
* historical order snapshots for public language code, WordPress locale and text direction;
* isolated order-language rendering scopes for WooCommerce transactional order emails;
* manual order-email resend language scoping;
* programmatic order-language run(order, callback) scope for future invoice/delivery/return document generators;
* guaranteed previous-locale/context restoration, including renderer exceptions;
* historical disabled-language translation lookup during order rendering.

Hreflang/canonical policy, translator admin/capabilities and translation import/export remain separate follow-up slices.

== Architecture ==

The module depends on IT-Kayali Commerce Core and registers itself through the Commerce Suite module registry.

Customer language lists and language settings belong to the active versioned customer profile. Translation content is stored in module-owned WordPress-prefixed tables rather than Theme files or one growing serialized profile option.

The Theme remains a presentation consumer. WooCommerce continues to own product and variation IDs, prices, SKU, stock, tax, slugs, cart contents/totals, payment state and order state. The multilingual module stores only translation content and bounded language-context metadata.

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

Normal storefront pages use the language selected by the localized URL. AJAX/REST entity mapping is allowed only when the WooCommerce language-context service restores a valid persisted customer-session language; the mapper never guesses an async language from the default route context.

== WooCommerce language context ==

The selected storefront language is persisted in the WooCommerce customer session under:

itk_commerce_language

Classic checkout and Checkout Block/Store API capture the same language snapshot on the existing WC_Order object through WooCommerce CRUD metadata:

_itk_commerce_language
_itk_commerce_locale
_itk_commerce_direction

These values are language context only. They do not duplicate or replace WooCommerce order ownership, items, totals, stock, taxes, payment state or HPOS data.

The current persisted session language is exposed through itk_commerce_woocommerce_session_language. Historical order context is exposed through itk_commerce_order_language_context and remains readable even if a language is later disabled, because locale and direction are frozen with the order.

== Order email and document rendering ==

WooCommerce transactional order notification events are wrapped in the frozen order language before the email trigger runs. While that explicit scope is active, WooCommerce's own customer-email store-locale switch/restore is disabled so it cannot overwrite the order locale. The previous WordPress locale and Commerce request language are restored after the notification.

The WooCommerce admin resend path is wrapped through its public before/after resend hooks. Non-order emails keep normal WooCommerce locale behavior.

Future invoice, delivery-note, return-slip and pack-list renderers can obtain the service through:

itk_commerce_order_language_scope

The returned service exposes run($order, $callback). The callback receives the normalized order language context and always executes inside the frozen locale/translation language. Restoration uses a finally block, so an exception does not leak the order language into later requests/renderers.

For historical orders whose language was later disabled, the stored locale still controls WordPress/WooCommerce strings and the stored public code temporarily overrides Commerce translation lookup only inside that order scope. The language is not globally re-enabled.

== Development status ==

Version 0.1.0-dev is a development build and not a production release.
