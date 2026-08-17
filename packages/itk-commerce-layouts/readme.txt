=== IT-Kayali Commerce Layouts ===
Contributors: IT-Kayali
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Profile-driven reusable layout selection and visual builders for the IT-Kayali Commerce Suite.

== Current foundation ==

* registers as an optional Commerce Core module
* maps WordPress plugin activation/deactivation to Core and active-profile module enablement while preserving layout configuration
* provides Appearance > Commerce Layouts for Header, Footer, mobile navigation and Mega-menu definitions
* provides Appearance > Commerce Templates for Shop, Product, Cart and Checkout presentation models
* previews unsaved model/options in the authenticated real storefront with desktop, tablet and mobile widths
* chooses Theme-owned Header/Footer and WooCommerce page models through public extension hooks
* supports Header/Footer contextual and product-specific assignment priority
* bridges customer-profile configuration into mobile bottom navigation
* provides portable Mega-menu definitions, WordPress menu-item binding and Appearance > Commerce Mega Menu for rich panel content
* rich Mega-menu blocks support WordPress child links, WooCommerce categories/products, images, promo banners and optional Elementor saved templates
* keeps customer branding, layout choices and rich content inside the versioned customer profile
* keeps executable templates/presentation contracts inside the Theme and profile selection/editing inside this module

== Header models ==

* classic
* centered
* shop/search-first
* transparent
* dark
* luxury
* sticky
* vertical

== Footer models ==

* classic
* compact
* columns
* simple
* luxury
* newsletter
* branches

== Shop models ==

* grid
* sidebar
* editorial
* compact

Shop options include 2-6 product columns, left/right sidebar position and comfortable/compact card density.

== Product models ==

* classic
* gallery-left
* gallery-right
* centered
* compact

Product options include 40/50/60 gallery weight, optional sticky summary and tabs/stacked product information.

== Cart models ==

* classic
* split
* compact

Classic shortcode carts can use optional sticky totals and comfortable/compact density. WooCommerce Cart blocks are wrapped only at the public WordPress block-render boundary; their native internal component structure remains untouched.

== Checkout models ==

* classic
* split
* focused

Classic checkout templates can use split/focused presentation, optional sticky order review and comfortable/compact field density. WooCommerce Checkout blocks receive only an IT-Kayali outer presentation shell and continue to own their internal layout/payment behavior.

== Rich Mega-menu blocks ==

* menu links: reuses existing WordPress child and grandchild items
* categories: optional WooCommerce product-category slugs, limits and images
* products: latest, featured, on-sale, category-based or explicit product IDs
* image: customer image URL with optional destination and alt text
* promo banner: eyebrow, title, text, image, destination and CTA label
* Elementor: optional saved-template ID; failure/inactive Elementor never breaks navigation

== Architecture ==

The module contains no hard-coded customer branding, content or production data. It reads and writes the active versioned customer profile through Commerce Core public services and selects reusable Theme-owned models.

Shop/Product/Cart/Checkout configuration is stored under `layouts.commerce` and saved independently from Header/Footer and rich Mega-menu configuration. Existing WooCommerce templates are not copied or patched for these page models.

Cart/Checkout Blocks intentionally keep their internal HTML and responsive behavior under WooCommerce ownership; the Theme uses WordPress's public `render_block` boundary for a model-specific outer shell.

Authenticated preview URLs are nonce-protected and noindex/nofollow. Static contract tests and the Chromium browser-regression gate cover responsive layout behavior before development ZIPs are built.
