=== IT-Kayali Commerce Multilingual ===
Contributors: itk-kayali
Tags: multilingual, woocommerce, rtl, translation, commerce
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Reusable language context, translation workflow and RTL/LTR module for the IT-Kayali Commerce Suite.

== Description ==

IT-Kayali Commerce Multilingual is an optional installable Commerce Suite module.

The current development foundation provides:

* versioned and bounded active-profile language configuration;
* default and fallback language contracts;
* neutral fallback from the current WordPress locale;
* enabled-language request context;
* public current-language / locale / direction contracts;
* stable body language/direction classes;
* HTML lang/dir alignment through the normal WordPress language-attributes filter;
* directory-style storefront routes such as /de/, /ar/ and /en/;
* normal WordPress/WooCommerce route parsing after the language prefix is resolved;
* storefront locale selection through public WordPress locale APIs;
* safe same-origin language URLs that preserve non-action storefront query state;
* accessible, style-neutral [itk_language_switcher] output and public switcher filters;
* RTL/LTR direction foundation without customer-specific Theme code.

Translation storage/workflow, WooCommerce order/email language context, hreflang/canonical policy and import/export remain separate follow-up slices.

== Architecture ==

The module depends on IT-Kayali Commerce Core and registers itself through the Commerce Suite module registry.

Customer language lists and language settings belong to the active versioned customer profile. Generic package code contains no customer-specific language configuration.

The Theme remains a presentation consumer. Translation data and language routing do not become Theme-owned state. WooCommerce continues to own products, prices, SKU, stock, cart state and order state.

== Language switcher ==

Use the shortcode:

[itk_language_switcher]

Optional display modes are label, code and both. Themes/builders can also consume the public itk_commerce_language_switcher_html / itk_commerce_language_switcher_items filters and style the stable itk-language-switcher classes.

== Development status ==

Version 0.1.0-dev is a development build and not a production release.
