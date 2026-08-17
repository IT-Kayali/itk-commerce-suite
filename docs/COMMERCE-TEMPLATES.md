# Commerce Page Templates

The final Phase 2 layout slice adds profile-driven visual models for Shop, Product, Cart and Checkout pages without patching WooCommerce core or copying customer-specific templates into the reusable product.

## Ownership boundary

The implementation keeps the existing suite architecture:

- `itk-commerce-theme` owns reusable page-model catalogs, public model/option filters, WooCommerce-safe presentation hooks and responsive CSS;
- `itk-commerce-layouts` owns customer-profile selection, bounded visual options, admin editing and authenticated live preview;
- WooCommerce remains responsible for commerce data, forms, checkout validation, payments and native Cart/Checkout block internals;
- customer branding/content remains in the customer profile and WordPress/WooCommerce data.

## Admin editor

Users with `itk_manage_design` receive **Appearance > Commerce Templates**.

The editor provides four tabs:

1. Shop
2. Product
3. Cart
4. Checkout

Each tab contains visual model cards, bounded options and the same authenticated Desktop/Tablet/Mobile storefront preview contract used by the Header/Footer Layout Builder.

Only `layouts.commerce` is written when this editor is saved. Header/Footer assignments, mobile navigation, Mega-menu definitions, rich Mega-menu content, branding, contacts and unrelated module settings are preserved.

## Profile shape

```json
{
  "layouts": {
    "commerce": {
      "shop": {
        "model": "grid",
        "options": {
          "columns": 4,
          "sidebar_position": "left",
          "density": "comfortable"
        }
      },
      "product": {
        "model": "gallery-left",
        "options": {
          "gallery_width": 50,
          "sticky_summary": true,
          "tabs_layout": "tabs"
        }
      },
      "cart": {
        "model": "split",
        "options": {
          "sticky_totals": true,
          "density": "comfortable"
        }
      },
      "checkout": {
        "model": "split",
        "options": {
          "sticky_summary": true,
          "content_width": "wide",
          "field_density": "comfortable"
        }
      }
    }
  }
}
```

## Shop models

### `grid`

Balanced general-purpose product grid.

### `sidebar`

Uses the Theme-owned `itk-shop-sidebar` widget area and a product-grid content column. The sidebar can be assigned left or right. If the widget area is empty, the Theme safely renders the catalog without an empty sidebar shell.

### `editorial`

Uses a larger lead product card to create a more visual catalog rhythm while retaining WooCommerce loop output.

### `compact`

Tighter card spacing and typography for stores with large assortments.

Shop options:

- `columns`: integer 2–6;
- `sidebar_position`: `left` or `right`;
- `density`: `comfortable` or `compact`.

WooCommerce's supported `loop_shop_columns` filter remains the source for the requested catalog column count.

## Product models

### `classic`

Balanced two-column gallery/summary presentation.

### `gallery-left`

Larger gallery emphasis on the left.

### `gallery-right`

Summary first, gallery on the right at desktop widths. Responsive breakpoints restore a single natural document flow.

### `centered`

Focused single-column product presentation.

### `compact`

Tighter two-column spacing and title sizing.

Product options:

- `gallery_width`: `40`, `50` or `60`;
- `sticky_summary`: boolean;
- `tabs_layout`: `tabs` or `stacked`.

The model relies on the standard WooCommerce product gallery, summary, tabs, upsells and related-product hooks rather than replacing the single-product template.

## Cart models

### `classic`

Standard cart flow with Theme styling.

### `split`

On classic/shortcode Cart templates, cart items and totals receive a responsive two-column shell at larger widths.

### `compact`

Narrower focused cart presentation.

Cart options:

- `sticky_totals`: boolean;
- `density`: `comfortable` or `compact`.

### Cart Block compatibility

The WooCommerce Cart block is not reconstructed or restyled through its private child markup. WordPress's public `render_block` filter adds only an IT-Kayali outer wrapper around the top-level `woocommerce/cart` block. Model styling may change outer width and spacing tokens while the native block keeps responsibility for its internal responsive layout and functionality.

## Checkout models

### `classic`

Standard checkout flow with Theme styling.

### `split`

On classic WooCommerce checkout templates, customer fields and order review are arranged in a responsive two-column structure.

### `focused`

Narrower distraction-reduced checkout presentation.

Checkout options:

- `sticky_summary`: boolean;
- `content_width`: `wide` or `boxed`;
- `field_density`: `comfortable` or `compact`.

### Checkout Block compatibility

The top-level `woocommerce/checkout` block receives the same public outer-shell integration as Cart. Payment processing, validation, child-block layout and native responsive behavior remain inside WooCommerce.

## Public Theme contracts

The Theme exposes:

- `itk_commerce_template_models` — extend/modify reusable model definitions;
- `itk_commerce_template_model` — select the active model for `shop`, `product`, `cart` or `checkout`;
- `itk_commerce_template_options` — resolve bounded visual options.

The Layouts module adds profile resolution through:

- `itk_commerce_profile_template_model`;
- `itk_commerce_profile_template_options`.

Unknown model IDs are rejected by the Theme's catalog validation and fall back to the Theme default.

## Authenticated live preview

Commerce template previews reuse the existing preview authorization contract:

- logged-in user;
- `itk_manage_design` capability;
- `itk_layout_preview=1`;
- nonce action `itk_commerce_layout_preview`;
- `noindex,nofollow` robot directives.

Temporary query parameters override the selected page model/options only for the authorized iframe request and are never persisted until the user explicitly saves.

Preview destinations:

- Shop: configured WooCommerce Shop page;
- Product: latest published product, when available;
- Cart: current WooCommerce Cart page/session;
- Checkout: current WooCommerce Checkout page/session.

An empty Cart/Checkout session is allowed to show WooCommerce's native empty/redirect state rather than creating fake customer/order data.

## Responsive behavior

The generic CSS layer protects:

- configured Shop columns at desktop widths, reducing to two columns on tablet and one on mobile;
- Shop sidebar collapse to a single column;
- Product gallery/summary collapse to one column under the tablet breakpoint;
- desktop-only sticky product/cart/checkout summaries;
- classic Cart/Checkout split models collapsing to one-column flows;
- outer Cart/Checkout block shells returning to full width on tablet/mobile.

RTL-sensitive positioning uses logical properties where relevant.

## Regression coverage

Static validation includes `tests/layouts/commerce-template-contract-smoke.php` to protect the stable model catalog and option defaults.

The Playwright suite also contains a customer-neutral Commerce template fixture that checks:

- Shop 5-column desktop → 2-column tablet → 1-column mobile behavior;
- Shop sidebar right/left ordering and mobile horizontal overflow;
- Product gallery-right ordering, sticky summary and tablet collapse;
- classic Cart split/sticky behavior and public Cart block-shell width;
- classic Checkout split/sticky behavior and focused Checkout block-shell width.

The build job remains gated behind both static validation and Chromium browser regression.
