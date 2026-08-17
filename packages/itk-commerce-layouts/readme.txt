=== IT-Kayali Commerce Layouts ===
Contributors: IT-Kayali
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Profile-driven reusable layout selection for the IT-Kayali Commerce Suite.

== Current foundation ==

* registers as an optional Commerce Core module
* chooses Theme-owned Header and Footer models through public extension hooks
* supports global and contextual profile assignments
* supports commerce rule priority: single product, product category, product type, then global/context fallback
* bridges customer-profile configuration into the mobile bottom navigation
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

Mega-menu configuration and additional model families remain later Phase 2 work.
