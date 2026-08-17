=== IT-Kayali Commerce Multilingual ===
Contributors: itk-kayali
Tags: multilingual, woocommerce, rtl, translation, commerce
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Reusable language context, translation workflow and RTL/LTR module for the IT-Kayali Commerce Suite.

== Description ==

IT-Kayali Commerce Multilingual is an optional installable Commerce Suite module.

The first development slice provides:

* versioned and bounded active-profile language configuration;
* default and fallback language contracts;
* neutral fallback from the current WordPress locale;
* enabled-language request context;
* public current-language / locale / direction contracts;
* stable body language/direction classes;
* HTML lang/dir alignment through the normal WordPress language-attributes filter;
* RTL/LTR direction foundation without customer-specific Theme code.

Directory routing, translation storage/workflow, WooCommerce order/email language context, hreflang and import/export are separate follow-up slices.

== Architecture ==

The module depends on IT-Kayali Commerce Core and registers itself through the Commerce Suite module registry.

Customer language lists and language settings belong to the active versioned customer profile. Generic package code contains no customer-specific language configuration.

The Theme remains a presentation consumer. Translation data and language routing do not become Theme-owned state.

== Development status ==

Version 0.1.0-dev is a development build and not a production release.
