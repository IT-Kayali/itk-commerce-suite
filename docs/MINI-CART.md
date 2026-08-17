# Mini Cart / Off-Canvas Cart

The Phase 3 mini-cart is a Theme-owned presentation component around WooCommerce's supported mini-cart APIs. It does not replace WooCommerce cart state, line-item calculations, totals, checkout behavior or Cart/Checkout Block internals.

## Architecture

- WooCommerce owns cart/session data and the native `woocommerce_mini_cart()` output.
- The Theme owns the off-canvas shell, responsive CSS, focus management and progressive-enhancement triggers.
- The Layouts module may resolve portable customer-profile presentation options through `itk_commerce_mini_cart_options`.
- Optional business features remain separate modules.

## Supported options

The Theme exposes `itk_commerce_mini_cart_options`. Values are bounded after filters run:

- `enabled`: boolean
- `position`: `start` or `end`
- `width`: `compact`, `standard` or `wide`
- `open_after_add`: boolean
- `close_on_backdrop`: boolean
- `show_thumbnails`: boolean
- `show_subtotal`: boolean

A Layouts customer profile can store the same values under:

```json
{
  "modules": {
    "configuration": {
      "itk-commerce-layouts": {
        "mini_cart": {
          "options": {
            "enabled": true,
            "position": "end",
            "width": "standard",
            "open_after_add": true,
            "close_on_backdrop": true,
            "show_thumbnails": true,
            "show_subtotal": true
          }
        }
      }
    }
  }
}
```

## Progressive enhancement

Header Cart links and WordPress menu links that point to the WooCommerce Cart remain real links. JavaScript intercepts a normal unmodified click only when the Theme mini-cart is rendered. If JavaScript is unavailable, the links continue to the normal Cart page.

The component supports:

- Escape to close;
- focus trapping while open;
- focus restoration to the opening control;
- `aria-expanded`, `aria-controls`, `aria-haspopup="dialog"` and modal-dialog semantics;
- optional backdrop closing;
- reduced-motion preferences;
- logical start/end positioning for LTR and RTL;
- full-width mobile behavior.

## WooCommerce AJAX compatibility

The Theme adds only its replaceable `div[data-itk-mini-cart-content]` shell to `woocommerce_add_to_cart_fragments`. The contents are rendered by `woocommerce_mini_cart()` so WooCommerce remains authoritative for products, quantities, prices, removal links, subtotal and native fragment refresh behavior.

When WooCommerce's `added_to_cart` event fires, the drawer can open automatically. This can be disabled through `open_after_add`.

## Public integration contract

Use the Theme option filter for reusable presentation defaults:

```php
add_filter( 'itk_commerce_mini_cart_options', function ( $options ) {
    $options['width'] = 'wide';
    return $options;
} );
```

Customer-specific values should normally live in the active customer profile instead of generic package code.

## Compatibility boundaries

The implementation intentionally does not:

- patch WooCommerce core;
- copy or override WooCommerce mini-cart templates;
- access private Cart/Checkout Block component trees;
- implement payment, coupon or shipping business logic;
- persist cart data outside WooCommerce.
