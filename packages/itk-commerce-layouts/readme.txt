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

== Architecture ==

The module contains no customer-specific branding, content or production data. It reads and writes the active versioned customer profile through Commerce Core public services and only selects Theme-owned reusable models.

Authenticated preview URLs are nonce-protected and noindex/nofollow. Rich Mega-menu product/banner panels and browser-based regression testing remain later Phase 2 work.
