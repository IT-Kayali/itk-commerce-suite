# Layout Builders Foundation

Phase 2 separates presentation templates from customer-specific layout selection.

## Package responsibility

### Theme

`itk-commerce-theme` owns reusable presentation models and renders them through a small public layout registry.

Current Header models:

- `classic`
- `centered`
- `shop`

Current Footer models:

- `classic`
- `compact`
- `columns`

The Theme provides these extension points:

- `itk_commerce_theme_layout_models` — register additional Theme-compatible model definitions;
- `itk_commerce_theme_layout_model` — select a model for a layout area;
- `itk_commerce_before_theme_layout` / `itk_commerce_after_theme_layout` — rendering lifecycle hooks;
- `itk_commerce_mobile_bottom_enabled` — enable/disable the bottom navigation;
- `itk_commerce_mobile_bottom_items` — configure its fallback entries.

### Layouts module

`itk-commerce-layouts` owns selection and assignment rules. It reads the active versioned customer profile through Commerce Core public services and selects Theme models through the public hooks above.

It must not patch Theme files and contains no customer branding. WordPress plugin activation enables the module in Core settings when Core is available; deactivation removes only the global enabled flag and preserves customer-profile configuration.

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

This keeps profile exports independent from a specific domain URL for the standard commerce destinations.

## Mega-menu data model

Mega-menu definitions are stored in the portable customer profile under `layouts.mega_menu.definitions` and are referenced by a stable key such as `catalog`.

A WordPress menu item opts into a definition through menu-item meta `_itk_commerce_mega_menu_key`. This deliberately separates the local WordPress menu-item identity from the portable profile definition.

The foundation currently normalizes:

- width: `aligned` or `full`;
- column count: 1–6;
- content type;
- content key;
- optional label.

Configured top-level primary-menu items receive safe CSS classes and data attributes. Existing WordPress submenu items can already be displayed in responsive multi-column layouts using this metadata. Rich panel contents such as product cards, banners, images, icons and Elementor content remain later work on top of the same definition key.

Public mega-menu filters:

- `itk_commerce_mega_menu_definitions`
- `itk_commerce_mega_menu_definition`

## Safe fallback

The Theme validates the selected model against its registered models. Unknown model IDs do not produce arbitrary template paths; rendering falls back to a known Theme model.

## Remaining Phase 2 work

The current foundation intentionally does not yet claim completion of:

- rich Mega-menu panel content such as products, banners, images and Elementor content;
- the full visual builder/editor UI;
- browser-based responsive/RTL/accessibility regression tests;
- additional Header/Footer model families such as transparent, dark, Luxury, vertical, newsletter and branch layouts;
- Shop/Product/Cart/Checkout template model editing beyond the current Header/Footer selection layer.

Those capabilities build on the same public model and assignment contracts rather than replacing them.
