# Layout Builders

Phase 2 separates reusable presentation templates from customer-specific layout selection and editing.

## Package responsibility

### Theme

`itk-commerce-theme` owns reusable presentation models and renders them through a public layout registry.

Current Header models:

- `classic`
- `centered`
- `shop`
- `transparent`
- `dark`
- `luxury`
- `sticky`
- `vertical`

Current Footer models:

- `classic`
- `compact`
- `columns`
- `simple`
- `luxury`
- `newsletter`
- `branches`

The Theme provides these extension points:

- `itk_commerce_theme_layout_models` — register additional Theme-compatible model definitions;
- `itk_commerce_theme_layout_model` — select a model for a layout area;
- `itk_commerce_before_theme_layout` / `itk_commerce_after_theme_layout` — rendering lifecycle hooks;
- `itk_commerce_mobile_bottom_enabled` — enable/disable the bottom navigation;
- `itk_commerce_mobile_bottom_items` — configure its fallback entries.

### Layouts module

`itk-commerce-layouts` owns selection, assignment and visual editing rules. It reads and writes the active versioned customer profile through Commerce Core public services and selects Theme models through the public Theme hooks.

It does not patch Theme files and contains no customer branding. WordPress plugin activation synchronizes the module-enabled state with Core and the active profile; deactivation removes the enabled flag while preserving the saved layout configuration.

## Visual builder

The module adds **Appearance > Commerce Layouts** for users with `itk_manage_design`.

The builder currently supports:

- visual Header model cards;
- visual Footer model cards;
- Shop, Product and Checkout context overrides;
- mobile bottom-navigation visibility;
- portable Mega-menu definition keys, labels, width and column count;
- versioned save back into the active customer profile;
- automatic creation of a neutral `site-default` profile if no active profile exists.

Only layout-owned profile sections are changed. Existing branding, contacts, languages, unrelated module configuration and product-specific assignment rules remain intact.

## Authenticated live preview

The builder displays the real storefront in an iframe and can preview unsaved Header, Footer and mobile-bottom choices.

Preview requests require:

1. a logged-in user;
2. the `itk_manage_design` capability;
3. the `itk_layout_preview` flag;
4. a valid nonce for `itk_commerce_layout_preview`.

Preview pages are marked `noindex,nofollow`. The builder offers Desktop, Tablet and Mobile viewport widths without storing those temporary choices.

## Profile configuration

Example:

```json
{
  "layouts": {
    "header": {
      "default": "classic",
      "contexts": {
        "shop": "shop",
        "checkout": "classic"
      },
      "rules": {
        "single_product": {
          "123": "centered"
        },
        "product_category": {
          "specials": "shop"
        },
        "product_type": {
          "variable": "centered"
        }
      }
    },
    "footer": {
      "default": "columns",
      "contexts": {
        "checkout": "compact"
      }
    },
    "mobile_bottom": {
      "enabled": true,
      "items": [
        {"label": "Home", "target": "home", "icon": "home"},
        {"label": "Shop", "target": "shop", "icon": "shop"},
        {"label": "Cart", "target": "cart", "icon": "cart", "badge": true},
        {"label": "Account", "target": "myaccount", "icon": "user"}
      ]
    },
    "mega_menu": {
      "definitions": {
        "catalog": {
          "label": "Catalog",
          "width": "full",
          "columns": 4,
          "content_type": "menu",
          "content_key": "catalog"
        }
      }
    }
  },
  "modules": {
    "enabled": ["itk-commerce-layouts"]
  }
}
```

## Assignment priority

For commerce-aware Header/Footer assignment, the resolver uses this priority:

1. exact single product;
2. product category;
3. product type;
4. current page/commerce context;
5. area default;
6. Theme default if the configured model is invalid or missing.

Generic contexts currently include:

- `product`
- `product_category`
- `shop`
- `cart`
- `checkout`
- `account`
- `front_page`
- `page`
- `archive`
- `global`

## Mobile bottom navigation

A dedicated WordPress menu assigned to the Theme's `mobile-bottom` location remains authoritative. If no dedicated menu is assigned, the Layouts module can provide up to six fallback entries from the customer profile.

Portable targets currently include:

- `home`
- `shop`
- `cart`
- `checkout`
- `myaccount`

This keeps standard commerce destinations portable across domains.

## Mega-menu model and menu binding

Mega-menu definitions are stored in the portable customer profile under `layouts.mega_menu.definitions` and referenced by a stable key such as `catalog`.

The visual builder can create and edit up to 12 definition rows. Each definition currently controls:

- stable key;
- optional label;
- width: `aligned` or `full`;
- column count: 1–6;
- content type/key foundation.

Under **Appearance > Menus**, a top-level WordPress menu item can be connected to one of these portable definitions using the **Commerce Mega-menu definition key** field. The binding is stored as `_itk_commerce_mega_menu_key` on the local menu item, keeping the local WordPress item ID separate from the portable profile configuration.

Configured primary-menu items receive safe CSS classes and data attributes. Existing WordPress submenu items can be rendered as responsive multi-column Mega-menu content today. Rich panel rendering for products, banners, images, categories and optional Elementor content remains a later slice.

Public Mega-menu filters:

- `itk_commerce_mega_menu_definitions`
- `itk_commerce_mega_menu_definition`

## Additional model families

Several named models from the product plan intentionally reuse shared structural templates with distinct model classes rather than duplicating near-identical PHP files:

- Transparent, Dark and Sticky use the Classic Header structure with variant styling;
- Luxury uses the Centered Header structure with variant styling;
- Vertical has its own responsive structure;
- Simple uses the Compact Footer structure;
- Luxury, Newsletter and Branches build on the Columns Footer structure and expose dedicated content hooks.

This keeps future maintenance and WooCommerce/theme compatibility changes centralized.

## Safe fallback

The Theme validates every selected model against its registered model catalog. Unknown model IDs do not become arbitrary template paths; rendering falls back to a known Theme model.

## Remaining Phase 2 work

The current implementation intentionally does not yet claim completion of:

- rich Mega-menu panel content such as product cards, banners, images, categories and Elementor content;
- browser-based responsive/RTL/accessibility regression tests;
- full Shop/Product/Cart/Checkout visual template editing beyond the current Header/Footer assignment layer.

These capabilities must extend the existing public contracts rather than replacing or bypassing them.
