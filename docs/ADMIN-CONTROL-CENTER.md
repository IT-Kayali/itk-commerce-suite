# Commerce Suite Admin Control Center

The IT-Kayali Commerce Core owns a stable top-level WordPress admin entry named **Commerce Suite**. Optional modules remain independently installable and may attach their own screens through the public admin-menu contract.

## Sidebar structure

The Core provides these stable screens:

- **Overview** — suite status and shortcuts.
- **Settings** — global Core settings, currently the active customer profile.
- **Modules** — installed module registry, loaded/error state and enablement for the active profile.
- **Customer Profiles** — create, duplicate, activate and export portable white-label profiles.
- **Design & Layouts** — launchpad to visual builders supplied by active modules.
- **System Status** — read-only PHP, WordPress, WooCommerce, HPOS, Theme, Core and module information.

The menu is deliberately not a second copy of WooCommerce settings. WooCommerce remains authoritative for products, orders, taxes, payments, shipping and customer data.

## Customer profile boundary

Changing the active profile or its enabled Commerce Suite modules must never modify:

- WooCommerce products or variations;
- customers or WordPress users;
- orders or HPOS records;
- coupons, reviews, media or uploads;
- payment/shipping configuration;
- secrets or API credentials.

Profile JSON export contains only the normalized portable customer profile. The existing `ProfileSchema` secret-key guard remains authoritative before profile persistence.

## Module enablement

The Modules screen lists only modules that registered through `itk_commerce_register_modules`. Submitted module IDs are intersected with the registered module list before persistence. If an active customer profile exists, its `modules.enabled` list is synchronized with the Core fallback list so the UI does not show a different state from the runtime resolver.

Activating or deactivating the WordPress plugin package itself remains a separate operation on the normal Plugins screen.

## Design launchpad

Core does not copy module settings UIs. It detects active builder classes and links to their real screens. This preserves package boundaries and avoids duplicate configuration storage.

Current launch targets include:

- Header / Footer / Mobile layout builder;
- Shop / Product / Cart / Checkout template builder;
- Product Card builder;
- Mega Menu content builder;
- Search & Filter builder when the optional module exposes it.

## Extension contract

Optional modules may add entries below the central sidebar menu using:

```php
add_action(
    'itk_commerce_admin_menu',
    function ( $parent_slug, $hub ) {
        // add_submenu_page( $parent_slug, ... );
    },
    10,
    2
);
```

`$parent_slug` is currently `itk-commerce` and is treated as a stable public integration surface.

## Security

- Core admin screens use suite capabilities (`itk_manage_commerce`, `itk_manage_modules`, `itk_manage_profiles`, `itk_manage_design`).
- All state-changing forms use WordPress nonces.
- Module IDs and profile IDs are sanitized and allow-listed where applicable.
- Profile deletion is refused for the currently active profile.
- System Status is read-only.
- JSON export uses the persisted normalized profile and does not query WooCommerce customer/order data.
