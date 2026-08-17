<?php
/**
 * Dependency-light smoke test for Theme mini-cart option contracts.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

$GLOBALS['itk_test_mini_cart_filter'] = array();

function add_filter() {}
function add_action() {}
function apply_filters( $hook, $value ) {
    if ( 'itk_commerce_mini_cart_options' === $hook && ! empty( $GLOBALS['itk_test_mini_cart_filter'] ) ) {
        return array_merge( $value, $GLOBALS['itk_test_mini_cart_filter'] );
    }
    return $value;
}

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/mini-cart.php';

function itk_mini_cart_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Mini-cart contract failure: {$message}\n" );
        exit( 1 );
    }
}

$defaults = \ITK\Commerce\Theme\mini_cart_options();

itk_mini_cart_assert( true === $defaults['enabled'], 'Mini-cart is enabled by default.' );
itk_mini_cart_assert( 'end' === $defaults['position'], 'Default logical position is end.' );
itk_mini_cart_assert( 'standard' === $defaults['width'], 'Default width is standard.' );
itk_mini_cart_assert( true === $defaults['open_after_add'], 'Auto-open after add is enabled by default.' );
itk_mini_cart_assert( true === $defaults['close_on_backdrop'], 'Backdrop close is enabled by default.' );
itk_mini_cart_assert( true === $defaults['show_thumbnails'], 'Thumbnails are shown by default.' );
itk_mini_cart_assert( true === $defaults['show_subtotal'], 'Subtotal is shown by default.' );

$GLOBALS['itk_test_mini_cart_filter'] = array(
    'enabled'           => false,
    'position'          => 'invalid-side',
    'width'             => 'oversized',
    'open_after_add'    => false,
    'close_on_backdrop' => false,
    'show_thumbnails'   => false,
    'show_subtotal'     => false,
);

$bounded = \ITK\Commerce\Theme\mini_cart_options();

itk_mini_cart_assert( false === $bounded['enabled'], 'Boolean enabled override is preserved.' );
itk_mini_cart_assert( 'end' === $bounded['position'], 'Invalid position falls back safely.' );
itk_mini_cart_assert( 'standard' === $bounded['width'], 'Invalid width falls back safely.' );
itk_mini_cart_assert( false === $bounded['open_after_add'], 'Auto-open may be disabled.' );
itk_mini_cart_assert( false === $bounded['close_on_backdrop'], 'Backdrop close may be disabled.' );
itk_mini_cart_assert( false === $bounded['show_thumbnails'], 'Thumbnails may be disabled.' );
itk_mini_cart_assert( false === $bounded['show_subtotal'], 'Subtotal may be disabled.' );

echo "Mini-cart contract smoke test passed.\n";
