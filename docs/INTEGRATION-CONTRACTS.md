# Public Component Integration Contracts

Phase 3 keeps optional commerce behavior outside the reusable Theme while providing stable presentation slots for installable modules.

## Catalog Search and Filters

The Theme exposes two semantic Shop-loop actions inside a responsive extension toolbar:

- `itk_commerce_catalog_search`
- `itk_commerce_catalog_filters`

The wrapper is rendered only when at least one action has listeners. Each callback receives one normalized context array containing the current Shop page model and bounded Shop options.

```php
add_action( 'itk_commerce_catalog_filters', function ( $context ) {
    // Render integration-owned filter controls.
} );
```

Modules may extend the slot registry through `itk_commerce_catalog_extension_slots`. They remain responsible for query logic, indexing, AJAX endpoints, persistence and accessibility of their own controls.

## Product Card Quick Actions

Product-card integrations have stable semantic actions in this order:

1. `itk_commerce_product_card_quick_view`
2. `itk_commerce_product_card_wishlist`
3. `itk_commerce_product_card_compare`
4. `itk_commerce_product_card_actions` (general/backwards-compatible slot)

Each receives the current `WC_Product` and the normalized product-loop context. The Theme renders no action wrapper when none of these hooks has listeners.

The existing `itk_commerce_product_badges` filter remains the public contract for module-provided product labels.

## Elementor Theme Builder

The Theme registers Elementor Theme Builder `header` and `footer` locations through Elementor's public `elementor/theme/register_locations` integration hook. In `header.php` and `footer.php`, the Theme asks `elementor_theme_do_location()` to render a matching Elementor template and falls back to the normal IT-Kayali Header/Footer model when Elementor is unavailable or no Theme Builder template matches.

Default Elementor locations are intentionally limited to Header and Footer. Replacing WooCommerce single/archive output by default would bypass Commerce Suite page-model contracts. Advanced integrations can extend the registered location list through `itk_commerce_elementor_theme_locations` and can disable replacement per request through `itk_commerce_elementor_location_enabled`.

The mobile bottom navigation remains Theme-owned even when Elementor renders the footer.

## Architecture Boundaries

These contracts do not implement Search, filtering, Wishlist, Compare or Quick View business logic themselves. They also do not copy WooCommerce templates, patch WooCommerce core, or reach into private Cart/Checkout Block internals.

Customer-specific configuration remains in customer profiles; generic modules attach to the public contracts above.
