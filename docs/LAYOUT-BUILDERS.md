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

`itk-commerce-layouts` owns selection, assignment, visual editing and optional rich navigation rules. It reads and writes the active versioned customer profile through Commerce Core public services and selects Theme models through the public Theme hooks.

It does not patch Theme files and contains no hard-coded customer branding. WordPress plugin activation synchronizes the module-enabled state with Core and the active profile; deactivation removes the enabled flag while preserving the saved layout configuration.

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
    "enabled": ["itk-commerce-layouts"],
    "configuration": {
      "itk-commerce-layouts": {
        "mega_content": {
          "catalog": {
            "blocks": [
              {"type": "menu", "title": "Shop", "span": 1},
              {"type": "categories", "title": "Categories", "span": 1, "slugs": "", "limit": 6, "show_images": true},
              {"type": "products", "title": "New arrivals", "span": 1, "source": "latest", "value": "", "limit": 4},
              {"type": "banner", "title": "Collection", "span": 1, "eyebrow": "Featured", "text": "", "image_url": "", "link_url": "", "link_label": "Shop now"}
            ]
          }
        }
      }
    }
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

The visual builder can create and edit up to 12 definition rows. Each definition controls:

- stable key;
- optional label;
- width: `aligned` or `full`;
- column count: 1–6;
- content type/key compatibility fields.

Under **Appearance > Menus**, a top-level WordPress menu item can be connected to one of these portable definitions using the **Commerce Mega-menu definition key** field. The binding is stored as `_itk_commerce_mega_menu_key` on the local menu item, keeping the local WordPress item ID separate from the portable profile configuration.

Basic definitions keep the previous responsive WordPress submenu behavior until explicit rich content is saved. This preserves backward compatibility for existing customer profiles.

Public Mega-menu filters:

- `itk_commerce_mega_menu_definitions`
- `itk_commerce_mega_menu_definition`

## Rich Mega-menu content

The module adds **Appearance > Commerce Mega Menu** for advanced panel content. Rich content is stored separately from the width/column definition under:

`modules.configuration.itk-commerce-layouts.mega_content.{definition_key}.blocks`

This separation is intentional: saving the basic Layout Builder can update Mega-menu width or assignment metadata without deleting rich panel content.

Supported blocks:

- `menu` — reuses direct and second-level WordPress child menu items;
- `categories` — WooCommerce product categories by portable slug, or top-level categories when no slug is supplied;
- `products` — latest, featured, on-sale, category-based or explicit product IDs;
- `image` — customer image URL, optional target and alt text;
- `banner` — eyebrow, title, text, background image, destination and CTA label;
- `elementor` — optional Elementor saved-template ID.

Each block can span 1–6 configured grid columns and every panel is bounded to six blocks. Product and category query limits are also bounded.

Rich blocks never accept executable PHP or JavaScript. Optional Elementor rendering is isolated: if Elementor is inactive, the template is missing or rendering throws an error, that block produces no output and navigation continues normally.

## Rich Mega-menu accessibility and responsive behavior

Configured rich top-level items receive a dedicated toggle button separate from the destination link. This means the top-level link remains navigable while the panel can still be opened without relying only on hover.

The frontend behavior includes:

- `aria-expanded` and `aria-controls` on the toggle;
- keyboard focus support through `:focus-within`;
- Escape closes open panels and returns focus to the toggle;
- click outside closes open panels;
- one open rich panel at a time;
- logical CSS properties for RTL-friendly positioning;
- desktop hover/focus behavior;
- compact toggle-driven panels inside mobile/tablet navigation.

Frontend CSS/JS is enqueued only when at least one definition has explicit rich blocks configured.

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

Basic Mega-menu definitions also remain functional without rich content, and optional WooCommerce/Elementor block rendering fails closed rather than breaking the site header.

## Remaining Phase 2 work

The current implementation intentionally does not yet claim completion of:

- browser-based responsive/RTL/accessibility regression tests;
- full Shop/Product/Cart/Checkout visual template editing beyond the current Header/Footer assignment layer.

These capabilities must extend the existing public contracts rather than replacing or bypassing them.
