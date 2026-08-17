=== IT-Kayali Commerce Layouts ===
Contributors: IT-Kayali
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Profile-driven reusable layout selection and visual builder for the IT-Kayali Commerce Suite.

== Current foundation ==

* registers as an optional Commerce Core module
* maps WordPress plugin activation/deactivation to Core and active-profile module enablement while preserving layout configuration
* provides an Appearance > Commerce Layouts visual builder
* previews unsaved Header, Footer and mobile-bottom choices in the authenticated real storefront
* supports desktop, tablet and mobile preview widths
* chooses Theme-owned Header and Footer models through public extension hooks
* supports global and contextual profile assignments
* supports commerce rule priority: single product, product category, product type, then global/context fallback
* bridges customer-profile configuration into the mobile bottom navigation
* provides portable Mega-menu definitions with responsive column metadata
* adds a WordPress menu-item field to bind local menu items to portable Mega-menu definition keys
* provides Appearance > Commerce Mega Menu for rich panel content
* rich Mega-menu blocks support WordPress child links, WooCommerce categories, WooCommerce product queries, images, promo banners and optional Elementor saved templates
* rich panels include a separate accessible toggle, Escape handling, click-outside close behavior and responsive mobile rendering
* rich content is stored under the Layouts module namespace inside the versioned active customer profile so width/assignment edits do not delete panel content
* no executable PHP or JavaScript can be stored as a rich Mega-menu block
* keeps executable templates inside the Theme and selection logic inside this module

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

== Rich Mega-menu blocks ==

* menu links: reuses existing WordPress child and grandchild items
* categories: optional WooCommerce product-category slugs, limits and images
* products: latest, featured, on-sale, category-based or explicit product IDs
* image: customer image URL with optional destination and alt text
* promo banner: eyebrow, title, text, image, destination and CTA label
* Elementor: optional saved-template ID; failure/inactive Elementor never breaks navigation

== Architecture ==

The module contains no hard-coded customer branding, content or production data. It reads and writes the active versioned customer profile through Commerce Core public services and only selects Theme-owned reusable models or renders profile-configured navigation content.

Existing basic Mega-menu definitions keep their previous submenu behavior until rich blocks are explicitly saved. Authenticated preview URLs are nonce-protected and noindex/nofollow.

Browser-based responsive/RTL/accessibility regression testing and full Shop/Product/Cart/Checkout visual template editing remain later Phase 2 work.
