=== IT-Kayali Commerce ===
Contributors: IT-Kayali
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0-dev

Reusable WooCommerce theme foundation for the IT-Kayali Commerce Suite.

== Current foundation ==

* responsive desktop/tablet/mobile shell
* WooCommerce support and product gallery features
* configurable WordPress menu locations
* mobile bottom navigation with commerce fallback
* local-font policy (no external Google Fonts by default)
* RTL-aware layout primitives
* theme.json design tokens
* accessible header/footer/content/search templates
* archive, single, page, front-page, 404 and WooCommerce templates
* hook points for builders and optional modules
* layered assets so future modules can load only what they need

== Architecture ==

Customer-specific branding and behavior must not be hard-coded into this package. Persistent product functionality belongs in the Core or optional modules. This theme owns presentation, layouts and reusable UI primitives.

== Planned extension packages ==

The approved Commerce Suite plan keeps advanced layout models, multilingual data, filters/search, documents, Elementor widgets, badges, wishlist/compare, gift boxes and the code manager in separate packages. Those packages may extend this theme without replacing its reusable base templates.
