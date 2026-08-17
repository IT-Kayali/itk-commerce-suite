<?php
/**
 * Dependency-light smoke test for stable module presentation contracts.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function add_action() {}
function apply_filters( $hook, $value ) { return $value; }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/component-contracts.php';

function itk_component_contract_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Component contract failure: {$message}\n" );
        exit( 1 );
    }
}

$contracts = \ITK\Commerce\Theme\component_contracts();
$expected  = array(
    'catalog_toolbar'          => 'itk_commerce_catalog_toolbar',
    'product_badges'           => 'itk_commerce_product_badges',
    'product_card_actions'     => 'itk_commerce_product_card_actions',
    'commerce_before_content'  => 'itk_commerce_before_content',
    'commerce_after_content'   => 'itk_commerce_after_content',
    'account_dashboard_cards'  => 'itk_commerce_account_dashboard_cards',
);

foreach ( $expected as $key => $hook ) {
    itk_component_contract_assert( isset( $contracts[ $key ] ), "Missing registry entry {$key}." );
    itk_component_contract_assert( $hook === $contracts[ $key ]['hook'], "Unexpected hook for {$key}." );
}

$theme_root    = dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/';
$product_cards = file_get_contents( $theme_root . 'inc/product-cards.php' );
$woocommerce   = file_get_contents( $theme_root . 'inc/woocommerce.php' );
$components    = file_get_contents( $theme_root . 'inc/component-contracts.php' );

itk_component_contract_assert( false !== strpos( $product_cards, "apply_filters( 'itk_commerce_product_badges'" ), 'Product badge filter must remain executable.' );
itk_component_contract_assert( false !== strpos( $product_cards, "do_action( 'itk_commerce_product_card_actions'" ), 'Product action slot must remain executable.' );
itk_component_contract_assert( false !== strpos( $components, "do_action( 'itk_commerce_catalog_toolbar'" ), 'Catalog toolbar action must remain executable.' );
itk_component_contract_assert( false !== strpos( $woocommerce, "'itk_commerce_before_content'" ), 'Before-content region must remain executable.' );
itk_component_contract_assert( false !== strpos( $woocommerce, "'itk_commerce_after_content'" ), 'After-content region must remain executable.' );

echo "Component extension contract smoke test passed.\n";
