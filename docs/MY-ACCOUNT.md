# WooCommerce My Account Experience

Phase 3 enhances WooCommerce My Account without replacing its endpoint, authentication, order, address, download or form-processing logic.

## Ownership

- WooCommerce owns account endpoints, authentication and customer data.
- The Theme owns layout models, responsive presentation, reusable dashboard shortcut cards and public extension filters.
- The Layouts module may resolve portable visual choices from the active customer profile.
- Optional business functionality remains in separate installable modules.

## Models and bounded options

`itk_commerce_account_options` receives the current/default options before final Theme validation.

Supported values:

- `model`: `sidebar`, `top-nav`, `compact`
- `content_width`: `wide`, `boxed`
- `navigation_style`: `soft`, `bordered`, `minimal`
- `card_style`: `soft`, `bordered`, `minimal`
- `orders_density`: `comfortable`, `compact`
- `show_dashboard_cards`: boolean
- `dashboard_cards`: ordered subset of `orders`, `downloads`, `edit-address`, `edit-account`

The Layouts module reads these values from:

```text
modules.configuration.itk-commerce-layouts.account.options
```

## Dashboard shortcut cards

The Theme attaches to WooCommerce's public `woocommerce_account_dashboard` action and renders endpoint shortcuts only when WooCommerce exposes the corresponding account menu item.

The default definitions can be filtered through:

```php
itk_commerce_account_dashboard_card_definitions
```

The final normalized cards can be filtered through:

```php
itk_commerce_account_dashboard_cards
```

Modules may extend the card collection, but they remain responsible for their own endpoint/business behavior.

## Orders, downloads, addresses and account forms

The implementation styles the existing WooCommerce markup and preserves the native data flow. It does not copy My Account templates or query/order data independently.

Presentation coverage includes:

- account navigation;
- dashboard shortcuts;
- orders and downloads tables;
- billing/shipping address cards;
- account details and address forms;
- responsive tablet/mobile navigation;
- logical LTR/RTL behavior through CSS logical properties.

## Compatibility boundary

The Theme does not:

- patch WooCommerce core;
- override WooCommerce My Account templates;
- replace authentication or password handling;
- replace order, address or download queries;
- duplicate customer data;
- alter endpoint routing.

This keeps customer-account behavior update-safe while still allowing different presentation models per customer profile.
