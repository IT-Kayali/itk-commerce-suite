# Public Commerce Component Extension Contracts

The Theme exposes stable presentation contracts so optional Commerce Suite modules can integrate without modifying Theme files or WooCommerce internals.

## Contract discovery

`ITK\Commerce\Theme\component_contracts()` returns the built-in registry. The registry may be extended through:

```php
itk_commerce_component_contracts
```

The registry is metadata only. Modules still attach to the named WordPress actions and filters.

## Search and Filter

The catalog toolbar is activated only when a module attaches to at least one of these actions:

```text
itk_commerce_catalog_toolbar_before
itk_commerce_catalog_toolbar
itk_commerce_catalog_toolbar_after
```

When active, the Theme opens its toolbar before WooCommerce's native result count and closes it after WooCommerce's native ordering control. This keeps the standard WooCommerce controls intact while allowing the future Search/Filter module to add filter buttons, active-filter summaries, mobile off-canvas triggers or other controls.

Each action receives a context array with the current Commerce area, model and validated page-model options.

## Wishlist, Compare and Quick View

Optional product actions attach to:

```text
itk_commerce_product_card_actions
```

The Theme renders the action wrapper only when at least one integration is attached. Wishlist, Compare and Quick View therefore remain installable functionality instead of becoming hard dependencies of the Theme.

The action receives the current `WC_Product` and product-loop context.

## Badges

Portable product labels use:

```text
itk_commerce_product_badges
```

The filter receives the Theme-generated badge collection, current `WC_Product` and product-loop context. Modules may add or remove labels while the Theme retains the presentation layer.

WooCommerce's native Sale flash is not duplicated by this contract.

## Elementor and reusable presentation regions

The Theme exposes two generic WooCommerce content regions:

```text
itk_commerce_before_content
itk_commerce_after_content
```

Both receive the current Commerce component context. A future Elementor module may render Theme Location output into these regions without overriding WooCommerce wrappers or customer templates.

Header/Footer models continue to use the Layouts module's public Theme layout model contracts. Phase 7 may add Elementor-specific adapters, but the Theme regions defined here remain provider-neutral.

## My Account extensions

Optional modules may extend the normalized account dashboard through:

```text
itk_commerce_account_dashboard_card_definitions
itk_commerce_account_dashboard_cards
```

Modules remain responsible for their own endpoints and authorization. The Theme does not create business endpoints on their behalf.

## Compatibility rules

Modules using these contracts must not:

- patch Theme or WooCommerce core files;
- depend on private Cart/Checkout Block component internals;
- store customer-specific branding in generic package code;
- replace WooCommerce order, cart, account or product data ownership;
- assume another optional module is installed unless declared as a dependency.

New contracts should be additive and backwards-compatible. Existing hook names are part of the public Commerce Suite integration surface and should only change through a versioned deprecation/migration process.
