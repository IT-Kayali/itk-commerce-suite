# Optional Commerce modules

The following packages stay independently installable so customer projects can enable only the features they need.

## Badges

`itk-commerce-badges` extends the Theme's public `itk_commerce_product_badges` filter with calculated sale percentage and an optional per-product custom label. Sold-out/featured/new presentation remains in the Theme baseline.

## Wishlist & Compare

`itk-commerce-wishlist-compare` adds product-card/single-product buttons through public hooks. Anonymous state is browser-local and does not create customer accounts or modify WooCommerce order/cart state. Saved-list pages use `[itk_wishlist]` and `[itk_compare]` and read public product data from the WooCommerce Store API.

## Gift Boxes

`itk-commerce-gift-boxes` lets an administrator mark a WooCommerce product as a configurable gift box, choose a product-category source and set a bounded selection limit. Customer selections are validated server-side, stored in cart item data and copied to the final WooCommerce order line.

## Code Manager

`itk-commerce-code-manager` provides controlled Head, Body-open and Footer extension locations for HTML/CSS/JavaScript. PHP snippets are persisted only by administrators and remain disabled until the installation owner explicitly enables `ITK_COMMERCE_ALLOW_PHP_SNIPPETS` in `wp-config.php`.

These modules do not patch Theme, WooCommerce or WordPress core source and can be disabled without deleting unrelated Commerce Suite configuration/data.
