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

A Layouts customer profile can store the same values under `modules.configuration.itk-commerce-layouts.mini_cart.options`.

## Progressive enhancement

Header Cart links and WordPress menu links that point to the WooCommerce Cart remain real links. JavaScript intercepts only a normal unmodified same-window click when the Theme mini-cart is rendered. Download links, modified clicks and links targeting another browsing context keep their native browser behavior. Without JavaScript, the links continue to the normal Cart page.

The component supports Escape close, focus trapping, focus restoration, dialog ARIA state, optional backdrop closing, reduced-motion preferences, logical LTR/RTL positioning and full-width mobile behavior.

## WooCommerce fragments and Blocks compatibility

While the drawer is enabled, the Theme explicitly enqueues WooCommerce's registered `wc-cart-fragments` runtime. It adds only two Theme-owned selectors to `woocommerce_add_to_cart_fragments`:

- `div[data-itk-mini-cart-content]` for the WooCommerce-rendered drawer contents;
- `span[data-itk-cart-count]` for the stable Header/mobile cart-count surface, including zero-to-nonzero transitions.

The drawer contents are always produced by `woocommerce_mini_cart()`, so WooCommerce remains authoritative for products, quantities, prices, removal links, subtotal and the underlying cart session.

Classic AJAX add-to-cart keeps using WooCommerce's `added_to_cart` event and fragment response. For block-era interactions, the Theme also listens for the public `wc-blocks_added_to_cart` and `wc-blocks_removed_from_cart` browser events. Those events refresh the Theme surfaces through WooCommerce's supported `get_refreshed_fragments` AJAX endpoint. Adds may open the drawer when `open_after_add` is enabled; removals refresh state without forcing the drawer open.

This bridge does not read or mutate private WooCommerce Blocks stores or component trees.

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
- access private Cart/Checkout Block component trees or data stores;
- implement payment, coupon or shipping business logic;
- persist cart data outside WooCommerce.
