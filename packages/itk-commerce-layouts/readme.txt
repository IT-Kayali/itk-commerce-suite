=== IT-Kayali Commerce Layouts ===
Contributors: IT-Kayali
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Profile-driven reusable layout selection for the IT-Kayali Commerce Suite.

== Current foundation ==

* registers as an optional Commerce Core module
* maps WordPress plugin activation/deactivation to Core module enablement while preserving profile configuration
* chooses Theme-owned Header and Footer models through public extension hooks
* supports global and contextual profile assignments
* supports commerce rule priority: single product, product category, product type, then global/context fallback
* bridges customer-profile configuration into the mobile bottom navigation
* provides portable mega-menu definitions with responsive column metadata
* keeps executable templates inside the Theme and selection logic inside this module

== Header models ==

* classic
* centered
* shop/search-first

== Footer models ==

* classic
* compact
* columns

== Architecture ==

The module contains no customer-specific branding, content or production data. It reads the active versioned customer profile through Commerce Core public services and only selects Theme-owned reusable models.

Rich mega-menu panels, visual builder screens and additional model families remain later Phase 2 work.
