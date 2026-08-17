<?php
/**
 * Dependency-light smoke test for Theme My Account presentation options.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

$GLOBALS['itk_test_account_filter'] = array();

function add_filter() {}
function add_action() {}
function apply_filters( $hook, $value ) {
    if ( 'itk_commerce_account_options' === $hook && ! empty( $GLOBALS['itk_test_account_filter'] ) ) {
        return array_merge( $value, $GLOBALS['itk_test_account_filter'] );
    }
    return $value;
}
function sanitize_key( $value ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/account.php';

function itk_account_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Account contract failure: {$message}\n" );
        exit( 1 );
    }
}

$defaults = \ITK\Commerce\Theme\account_options();

itk_account_assert( 'sidebar' === $defaults['model'], 'Default account model is sidebar.' );
itk_account_assert( 'wide' === $defaults['content_width'], 'Default content width is wide.' );
itk_account_assert( 'soft' === $defaults['navigation_style'], 'Default navigation style is soft.' );
itk_account_assert( 'soft' === $defaults['card_style'], 'Default dashboard-card style is soft.' );
itk_account_assert( 'comfortable' === $defaults['orders_density'], 'Default order density is comfortable.' );
itk_account_assert( true === $defaults['show_dashboard_cards'], 'Dashboard cards are enabled by default.' );
itk_account_assert( array( 'orders', 'downloads', 'edit-address', 'edit-account' ) === $defaults['dashboard_cards'], 'Default dashboard cards remain stable.' );

$GLOBALS['itk_test_account_filter'] = array(
    'model'                => 'invalid-model',
    'content_width'        => 'oversized',
    'navigation_style'     => 'unknown',
    'card_style'           => 'unknown',
    'orders_density'       => 'tiny',
    'show_dashboard_cards' => false,
    'dashboard_cards'      => array( 'orders', 'unsafe', 'orders', 'edit-account' ),
);

$bounded = \ITK\Commerce\Theme\account_options();

itk_account_assert( 'sidebar' === $bounded['model'], 'Invalid model falls back safely.' );
itk_account_assert( 'wide' === $bounded['content_width'], 'Invalid width falls back safely.' );
itk_account_assert( 'soft' === $bounded['navigation_style'], 'Invalid navigation style falls back safely.' );
itk_account_assert( 'soft' === $bounded['card_style'], 'Invalid card style falls back safely.' );
itk_account_assert( 'comfortable' === $bounded['orders_density'], 'Invalid order density falls back safely.' );
itk_account_assert( false === $bounded['show_dashboard_cards'], 'Dashboard cards may be disabled.' );
itk_account_assert( array( 'orders', 'edit-account' ) === $bounded['dashboard_cards'], 'Dashboard-card list is deduplicated and allow-listed.' );

echo "Account contract smoke test passed.\n";
