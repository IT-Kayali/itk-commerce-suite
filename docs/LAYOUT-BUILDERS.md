# Layout Builders

Phase 2 separates reusable presentation models from customer-specific layout selection and editing.

## Package responsibility

### Theme

`itk-commerce-theme` owns reusable presentation/model contracts and responsive rendering.

Header models:

- `classic`
- `centered`
- `shop`
- `transparent`
- `dark`
- `luxury`
- `sticky`
- `vertical`

Footer models:

- `classic`
- `compact`
- `columns`
- `simple`
- `luxury`
- `newsletter`
- `branches`

WooCommerce page models:

- Shop: `grid`, `sidebar`, `editorial`, `compact`
- Product: `classic`, `gallery-left`, `gallery-right`, `centered`, `compact`
- Cart: `classic`, `split`, `compact`
- Checkout: `classic`, `split`, `focused`

### Layouts module

`itk-commerce-layouts` owns customer-profile selection, assignment rules, visual editors and authenticated previews. It reads/writes the active versioned customer profile through Commerce Core public services and never patches Theme or WooCommerce source files.

WordPress plugin activation synchronizes the module-enabled state with Core and the active profile. Deactivation removes the enabled flag while preserving saved layout configuration for safe reactivation/rollback.

## Appearance > Commerce Layouts

The main visual builder supports:

- visual Header model cards;
- visual Footer model cards;
- Shop/Product/Checkout Header/Footer context overrides;
- product/category/product-type assignment priority;
- mobile bottom-navigation visibility;
- portable Mega-menu definition keys, labels, width and columns;
- versioned save into the active customer profile;
- neutral `site-default` profile creation when no active profile exists.

Header/Footer assignment priority:

1. exact single product;
2. product category;
3. product type;
4. current page/commerce context;
5. area default;
6. Theme fallback.

## Appearance > Commerce Mega Menu

Mega-menu definitions are portable profile data under `layouts.mega_menu.definitions`. A local WordPress menu item binds to a portable key through menu-item meta `_itk_commerce_mega_menu_key`.

Rich content is stored separately under:

`modules.configuration.itk-commerce-layouts.mega_content.{definition_key}.blocks`

Supported blocks:

- `menu` — existing child/grandchild WordPress menu items;
- `categories` — WooCommerce product categories;
- `products` — latest, featured, on-sale, category or explicit product IDs;
- `image` — customer image/link/alt content;
- `banner` — promotional content and CTA;
- `elementor` — optional saved Elementor template ID.

Rich panels use a dedicated toggle separate from the top-level destination link, `aria-expanded`, `aria-controls`, keyboard focus behavior, Escape closing/focus return, click-outside closing and responsive/RTL-aware layout.

Existing basic Mega-menu definitions keep their normal submenu behavior until explicit rich blocks are saved.

## Appearance > Commerce Templates

The final Phase 2 editor manages Shop/Product/Cart/Checkout page-model selection and bounded visual options under `layouts.commerce`.

It includes an authenticated Desktop/Tablet/Mobile live preview and saves only the commerce page-model section, preserving Header/Footer, navigation, Mega-menu and customer branding configuration.

Detailed contracts, model descriptions, block compatibility and profile shape are documented in [`COMMERCE-TEMPLATES.md`](COMMERCE-TEMPLATES.md).

## Authenticated live preview

All layout editors reuse the same preview contract:

1. logged-in user;
2. `itk_manage_design` capability;
3. `itk_layout_preview=1`;
4. valid nonce for `itk_commerce_layout_preview`.

Preview pages are `noindex,nofollow`. Temporary model/options are sent only to the authorized iframe and are not persisted until Save is submitted.

## Mobile bottom navigation

A WordPress menu assigned to the Theme's `mobile-bottom` location remains authoritative. Otherwise the profile can provide up to six fallback entries.

Portable standard targets:

- `home`
- `shop`
- `cart`
- `checkout`
- `myaccount`

## Public extension points

Header/Footer Theme hooks:

- `itk_commerce_theme_layout_models`
- `itk_commerce_theme_layout_model`
- `itk_commerce_before_theme_layout`
- `itk_commerce_after_theme_layout`
- `itk_commerce_mobile_bottom_enabled`
- `itk_commerce_mobile_bottom_items`

Mega-menu filters:

- `itk_commerce_mega_menu_definitions`
- `itk_commerce_mega_menu_definition`

Commerce page-model filters:

- `itk_commerce_template_models`
- `itk_commerce_template_model`
- `itk_commerce_template_options`
- `itk_commerce_profile_template_model`
- `itk_commerce_profile_template_options`

## Safe fallback and compatibility rules

The Theme validates selected model IDs against registered model catalogs. Unknown model IDs never become arbitrary template paths and fall back to a known Theme model.

Optional WooCommerce/Elementor Mega-menu content fails closed rather than breaking navigation.

Cart/Checkout block internals are intentionally not targeted. The Theme wraps only the public top-level `woocommerce/cart` and `woocommerce/checkout` render boundary and leaves native component markup, validation, payment behavior and responsive internals to WooCommerce.

## Browser regression

Phase 2 is protected by customer-neutral Chromium tests covering:

- Header/Footer responsive layouts;
- mobile navigation;
- Mega-menu accessibility, RTL and responsive behavior;
- Shop column/sidebar models;
- Product gallery/sticky models;
- classic Cart/Checkout split models;
- public Cart/Checkout block-shell widths;
- mobile horizontal-overflow guards.

See [`BROWSER-REGRESSION.md`](BROWSER-REGRESSION.md).

## Phase 2 status

The approved Phase 2 layout scope is implemented:

- reusable Header/Footer model registry;
- visual Header/Footer builder;
- profile/context assignments;
- mobile navigation bridge;
- Mega-menu definitions and rich panels;
- expanded Header/Footer model families;
- responsive/RTL/accessibility browser regression gate;
- visual Shop/Product/Cart/Checkout model editor and live preview.

New capabilities should extend these public contracts rather than replacing them with customer-specific copies or direct core/template patches.
