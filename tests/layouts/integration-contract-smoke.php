<?php
/**
 * Dependency-light smoke test for public component integration contracts.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function add_action() {}
function apply_filters( $tag, $value ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function __( $text ) { return $text; }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/integrations.php';
require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/elementor.php';

function itk_integration_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

$slots = \ITK\Commerce\Theme\catalog_extension_slots();
itk_integration_assert( isset( $slots['search']['action'] ) && 'itk_commerce_catalog_search' === $slots['search']['action'], 'Catalog search slot is stable.' );
itk_integration_assert( isset( $slots['filters']['action'] ) && 'itk_commerce_catalog_filters' === $slots['filters']['action'], 'Catalog filter slot is stable.' );

$actions = \ITK\Commerce\Theme\product_card_action_hooks();
itk_integration_assert( in_array( 'itk_commerce_product_card_quick_view', $actions, true ), 'Quick-view action contract is registered.' );
itk_integration_assert( in_array( 'itk_commerce_product_card_wishlist', $actions, true ), 'Wishlist action contract is registered.' );
itk_integration_assert( in_array( 'itk_commerce_product_card_compare', $actions, true ), 'Compare action contract is registered.' );
itk_integration_assert( 'itk_commerce_product_card_actions' === end( $actions ), 'General product-card action remains the final compatibility slot.' );

$locations = \ITK\Commerce\Theme\elementor_theme_locations();
itk_integration_assert( array( 'header', 'footer' ) === $locations, 'Elementor defaults are limited to safe Header/Footer locations.' );

final class ITK_Elementor_Manager_Fixture {
    public $locations = array();
    public function register_location( $location ) { $this->locations[] = $location; }
}

$manager = new ITK_Elementor_Manager_Fixture();
\ITK\Commerce\Theme\register_elementor_theme_locations( $manager );
itk_integration_assert( array( 'header', 'footer' ) === $manager->locations, 'Elementor manager receives Header/Footer registrations.' );

echo "Integration contract smoke test passed.\n";
