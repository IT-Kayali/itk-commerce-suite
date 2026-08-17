<?php
/**
 * Public component extension contracts for installable Commerce Suite modules.
 *
 * These hooks deliberately expose presentation slots rather than optional
 * business behavior. Search/Filter, Wishlist/Compare, Badges and Elementor may
 * attach without patching Theme or WooCommerce internals.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\\catalog_toolbar_open', 15 );
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\\catalog_toolbar_extensions', 25 );
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\\catalog_toolbar_close', 35 );

/**
 * Return the documented component contract registry.
 *
 * The registry is metadata for optional modules and developer tooling. Actual
 * execution still uses the named WordPress actions/filters listed here.
 *
 * @return array<string,array<string,string>>
 */
function component_contracts() {
    $contracts = array(
        'catalog_toolbar' => array(
            'type'        => 'action',
            'hook'        => 'itk_commerce_catalog_toolbar',
            'description' => 'Search/filter controls inside the WooCommerce catalog toolbar.',
        ),
        'catalog_toolbar_before' => array(
            'type'        => 'action',
            'hook'        => 'itk_commerce_catalog_toolbar_before',
            'description' => 'Content before native result count and catalog controls.',
        ),
        'catalog_toolbar_after' => array(
            'type'        => 'action',
            'hook'        => 'itk_commerce_catalog_toolbar_after',
            'description' => 'Content after native ordering and catalog controls.',
        ),
        'product_badges' => array(
            'type'        => 'filter',
            'hook'        => 'itk_commerce_product_badges',
            'description' => 'Portable product badge definitions supplied by optional modules.',
        ),
        'product_card_actions' => array(
            'type'        => 'action',
            'hook'        => 'itk_commerce_product_card_actions',
            'description' => 'Quick View, Wishlist, Compare or other optional product-card actions.',
        ),
        'commerce_before_content' => array(
            'type'        => 'action',
            'hook'        => 'itk_commerce_before_content',
            'description' => 'Reusable region before Theme-owned WooCommerce content.',
        ),
        'commerce_after_content' => array(
            'type'        => 'action',
            'hook'        => 'itk_commerce_after_content',
            'description' => 'Reusable region after Theme-owned WooCommerce content.',
        ),
        'account_dashboard_cards' => array(
            'type'        => 'filter',
            'hook'        => 'itk_commerce_account_dashboard_cards',
            'description' => 'Optional account dashboard shortcuts after endpoint validation.',
        ),
    );

    /**
     * Filter the discoverable component contract registry.
     *
     * Modules may advertise additional public presentation contracts here.
     *
     * @param array<string,array<string,string>> $contracts Contract metadata.
     */
    $filtered = apply_filters( 'itk_commerce_component_contracts', $contracts );

    return is_array( $filtered ) ? $filtered : $contracts;
}

/**
 * Return a stable context payload for commerce presentation extensions.
 *
 * @return array<string,mixed>
 */
function commerce_component_context() {
    $area    = function_exists( __NAMESPACE__ . '\\commerce_template_area' ) ? commerce_template_area() : '';
    $model   = $area && function_exists( __NAMESPACE__ . '\\commerce_template_model' ) ? commerce_template_model( $area ) : '';
    $options = $area && function_exists( __NAMESPACE__ . '\\commerce_template_options' ) ? commerce_template_options( $area ) : array();

    return array(
        'area'    => sanitize_key( (string) $area ),
        'model'   => sanitize_key( (string) $model ),
        'options' => is_array( $options ) ? $options : array(),
    );
}

/**
 * Whether an optional module has attached catalog-toolbar content.
 *
 * @return bool
 */
function catalog_toolbar_active() {
    return false !== has_action( 'itk_commerce_catalog_toolbar' ) ||
        false !== has_action( 'itk_commerce_catalog_toolbar_before' ) ||
        false !== has_action( 'itk_commerce_catalog_toolbar_after' );
}

/**
 * Open the toolbar wrapper before WooCommerce's native result count.
 *
 * @return void
 */
function catalog_toolbar_open() {
    if ( ! catalog_toolbar_active() ) {
        return;
    }

    $context = commerce_component_context();
    echo '<div class="itk-catalog-toolbar" data-itk-catalog-toolbar>';

    /**
     * Render optional content before native WooCommerce catalog controls.
     *
     * @param array<string,mixed> $context Commerce context.
     */
    do_action( 'itk_commerce_catalog_toolbar_before', $context );
}

/**
 * Render the primary optional catalog-toolbar slot between WooCommerce's native
 * result count and ordering controls.
 *
 * @return void
 */
function catalog_toolbar_extensions() {
    if ( ! catalog_toolbar_active() ) {
        return;
    }

    /**
     * Render Search/Filter or other optional catalog controls.
     *
     * @param array<string,mixed> $context Commerce context.
     */
    do_action( 'itk_commerce_catalog_toolbar', commerce_component_context() );
}

/**
 * Close the toolbar wrapper after WooCommerce's native ordering controls.
 *
 * @return void
 */
function catalog_toolbar_close() {
    if ( ! catalog_toolbar_active() ) {
        return;
    }

    /**
     * Render optional content after native WooCommerce catalog controls.
     *
     * @param array<string,mixed> $context Commerce context.
     */
    do_action( 'itk_commerce_catalog_toolbar_after', commerce_component_context() );
    echo '</div>';
}
