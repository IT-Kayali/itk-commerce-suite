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
* accessible Header/Footer/content/search templates
* archive, single, page, front-page and 404 templates
* reusable Header/Footer model registry
* reusable Shop/Product/Cart/Checkout page-model registry and bounded visual-option contracts
* Shop models: Grid, Sidebar, Editorial and Compact
* Product models: Classic, Gallery Left, Gallery Right, Centered and Compact
* Cart models: Classic, Split and Compact
* Checkout models: Classic, Split and Focused
* Shop Sidebar model uses the Theme-owned widget area and WooCommerce loop hooks
* classic Cart/Checkout presentation uses supported WooCommerce hooks and native forms
* Cart/Checkout blocks are wrapped only at WordPress's public render_block boundary; private block internals remain untouched
* hook/filter points for builders and optional modules
* layered assets so future modules can load only what they need

== Architecture ==

Customer-specific branding and behavior must not be hard-coded into this package. Persistent product functionality belongs in the Core or optional modules. This Theme owns reusable presentation, model catalogs, safe WooCommerce presentation hooks and UI primitives.

The optional Layouts module selects these models through public filters and stores customer choices in the versioned customer profile. Unknown model IDs fall back to Theme-owned defaults.

== Planned extension packages ==

The approved Commerce Suite plan keeps multilingual data, advanced search/filtering, documents, dedicated Elementor widgets, badges, wishlist/compare, gift boxes and the code manager in separate packages. Those packages may extend this Theme without replacing its reusable base contracts.
