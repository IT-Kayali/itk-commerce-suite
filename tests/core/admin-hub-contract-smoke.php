<?php
/**
 * Dependency-light contract smoke test for the Commerce Suite admin hub.
 */

$root      = dirname( __DIR__, 2 );
$hub       = file_get_contents( $root . '/packages/itk-commerce-core/src/Admin/AdminHub.php' );
$routes    = file_get_contents( $root . '/packages/itk-commerce-core/src/Admin/LegacyAdminRoutes.php' );
$bootstrap = file_get_contents( $root . '/packages/itk-commerce-core/itk-commerce-core.php' );
$css       = file_get_contents( $root . '/packages/itk-commerce-core/assets/admin/admin-hub.css' );

function itk_admin_hub_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Admin hub contract failure: {$message}\n" );
        exit( 1 );
    }
}

itk_admin_hub_assert( false !== strpos( $bootstrap, 'src/Admin/AdminHub.php' ), 'Core bootstrap must load the central AdminHub class.' );
itk_admin_hub_assert( false !== strpos( $bootstrap, 'new Admin\\AdminHub' ), 'Core bootstrap must register AdminHub in wp-admin.' );
itk_admin_hub_assert( false !== strpos( $bootstrap, 'src/Admin/LegacyAdminRoutes.php' ), 'Core bootstrap must load backward-compatible admin routes.' );
itk_admin_hub_assert( false !== strpos( $bootstrap, 'new Admin\\LegacyAdminRoutes' ), 'Core bootstrap must register backward-compatible admin routes.' );
itk_admin_hub_assert( false !== strpos( $routes, "'itk-commerce-mega-menu-content' => 'itk-commerce-mega-content'" ), 'Old Mega Menu admin links must redirect to the canonical builder slug.' );
itk_admin_hub_assert( false !== strpos( $routes, "current_user_can( 'itk_manage_design' )" ), 'Legacy design-route redirects must retain the design capability boundary.' );
itk_admin_hub_assert( false !== strpos( $routes, 'wp_safe_redirect' ), 'Legacy admin routes must use safe WordPress redirects.' );
itk_admin_hub_assert( false !== strpos( $hub, 'add_menu_page(' ), 'AdminHub must expose a top-level WordPress sidebar menu.' );
itk_admin_hub_assert( false !== strpos( $hub, "'itk-commerce-settings'" ), 'Settings submenu must exist.' );
itk_admin_hub_assert( false !== strpos( $hub, "'itk-commerce-modules'" ), 'Modules submenu must exist.' );
itk_admin_hub_assert( false !== strpos( $hub, "'itk-commerce-profiles'" ), 'Customer Profiles submenu must exist.' );
itk_admin_hub_assert( false !== strpos( $hub, "'itk-commerce-design'" ), 'Design & Layouts submenu must exist.' );
itk_admin_hub_assert( false !== strpos( $hub, "'itk-commerce-system'" ), 'System Status submenu must exist.' );
itk_admin_hub_assert( false !== strpos( $hub, "check_admin_referer( 'itk_commerce_save_admin_settings'" ), 'Settings writes must be nonce protected.' );
itk_admin_hub_assert( false !== strpos( $hub, "check_admin_referer( 'itk_commerce_profile_action'" ), 'Profile mutations must be nonce protected.' );
itk_admin_hub_assert( false !== strpos( $hub, "array_intersect( array_map( 'sanitize_key', \$requested ), \$allowed )" ), 'Module enablement must be restricted to registered module IDs.' );
itk_admin_hub_assert( false !== strpos( $hub, 'custom_orders_table_usage_is_enabled' ), 'System Status must expose HPOS state through WooCommerce public utilities when available.' );
itk_admin_hub_assert( false !== strpos( $hub, 'application/json' ), 'Profile JSON export must be implemented.' );
itk_admin_hub_assert( false !== strpos( $hub, "do_action( 'itk_commerce_admin_menu'" ), 'Optional modules need a public admin-menu extension contract.' );
itk_admin_hub_assert( false !== strpos( $css, '.itk-admin-grid' ) && false !== strpos( $css, '@media (max-width: 782px)' ), 'Admin UI must have isolated responsive styling.' );

echo "Commerce Suite admin hub contract smoke test passed.\n";
