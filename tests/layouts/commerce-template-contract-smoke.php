<?php
/**
 * Dependency-light smoke test for Theme Commerce page/component contracts.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function add_filter() {}
function add_action() {}
function __( $text ) { return $text; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_html_class( $value ) { return sanitize_key( $value ); }
function absint( $value ) { return abs( (int) $value ); }
function apply_filters( $tag, $value ) { return $value; }
function is_product() { return false; }
function is_cart() { return false; }
function is_checkout() { return false; }
function is_shop() { return false; }
function is_product_taxonomy() { return false; }
function is_active_sidebar() { return false; }
function is_woocommerce() { return false; }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/commerce-models.php';
require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/product-cards.php';

function itk_commerce_template_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

$models = \ITK\Commerce\Theme\commerce_template_models();

itk_commerce_template_assert( isset( $models['shop']['grid'], $models['shop']['sidebar'], $models['shop']['editorial'], $models['shop']['compact'] ), 'Shop model catalog is complete.' );
itk_commerce_template_assert( isset( $models['product']['classic'], $models['product']['gallery-left'], $models['product']['gallery-right'], $models['product']['centered'], $models['product']['compact'] ), 'Product model catalog is complete.' );
itk_commerce_template_assert( isset( $models['cart']['classic'], $models['cart']['split'], $models['cart']['compact'] ), 'Cart model catalog is complete.' );
itk_commerce_template_assert( isset( $models['checkout']['classic'], $models['checkout']['split'], $models['checkout']['focused'] ), 'Checkout model catalog is complete.' );
itk_commerce_template_assert( 'grid' === \ITK\Commerce\Theme\commerce_template_default_model( 'shop' ), 'Shop default is stable.' );
itk_commerce_template_assert( 'classic' === \ITK\Commerce\Theme\commerce_template_default_model( 'product' ), 'Product default is stable.' );
itk_commerce_template_assert( 'classic' === \ITK\Commerce\Theme\commerce_template_default_model( 'cart' ), 'Cart default is stable.' );
itk_commerce_template_assert( 'classic' === \ITK\Commerce\Theme\commerce_template_default_model( 'checkout' ), 'Checkout default is stable.' );

$shop = \ITK\Commerce\Theme\commerce_template_options( 'shop' );
$product = \ITK\Commerce\Theme\commerce_template_options( 'product' );
$cart = \ITK\Commerce\Theme\commerce_template_options( 'cart' );
$checkout = \ITK\Commerce\Theme\commerce_template_options( 'checkout' );

itk_commerce_template_assert( 4 === $shop['columns'], 'Shop default columns are stable.' );
itk_commerce_template_assert( 'left' === $shop['sidebar_position'], 'Shop default sidebar position is stable.' );
itk_commerce_template_assert( 50 === $product['gallery_width'], 'Product default gallery width is stable.' );
itk_commerce_template_assert( false === $product['sticky_summary'], 'Product sticky summary defaults off.' );
itk_commerce_template_assert( false === $cart['sticky_totals'], 'Cart sticky totals default off.' );
itk_commerce_template_assert( 'wide' === $checkout['content_width'], 'Checkout default width is stable.' );

$card_models  = \ITK\Commerce\Theme\product_card_models();
$card_options = \ITK\Commerce\Theme\product_card_options();

itk_commerce_template_assert( isset( $card_models['classic'], $card_models['minimal'], $card_models['boxed'], $card_models['overlay'] ), 'Product-card model catalog is complete.' );
itk_commerce_template_assert( 'classic' === \ITK\Commerce\Theme\product_card_model(), 'Product-card default model is stable.' );
itk_commerce_template_assert( 'portrait' === $card_options['image_ratio'], 'Product-card image ratio default is stable.' );
itk_commerce_template_assert( 'lift' === $card_options['hover_behavior'], 'Product-card hover default is stable.' );
itk_commerce_template_assert( true === $card_options['show_state_badges'], 'Product-card state badges default on.' );
itk_commerce_template_assert( 30 === $card_options['new_days'], 'Product-card new badge window is stable.' );

echo "Commerce template contract smoke test passed.\n";
