<?php
/**
 * WooCommerce compatibility hooks owned by the theme layer.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\woocommerce_layout_setup', 20 );
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\cart_count_fragment' );
add_filter( 'body_class', __NAMESPACE__ . '\\commerce_body_classes' );

/**
 * Prefer theme-owned wrappers over WooCommerce's default container markup.
 */
function woocommerce_layout_setup() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
    remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

    add_action( 'woocommerce_before_main_content', __NAMESPACE__ . '\\commerce_wrapper_start', 10 );
    add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\\commerce_wrapper_end', 10 );
}

/**
 * Open the generic commerce content shell.
 */
function commerce_wrapper_start() {
    echo '<main id="primary" class="itk-site-main itk-commerce-main"><div class="itk-container">';
}

/**
 * Close the generic commerce content shell.
 */
function commerce_wrapper_end() {
    echo '</div></main>';
}

/**
 * Keep the cart badge current after AJAX add-to-cart requests.
 *
 * @param array $fragments Existing fragments.
 * @return array
 */
function cart_count_fragment( $fragments ) {
    ob_start();
    cart_badge();
    $fragments['span[data-itk-cart-count]'] = ob_get_clean();

    return $fragments;
}

/**
 * Add stable body classes that modules/layout rules may target.
 *
 * @param array $classes Existing body classes.
 * @return array
 */
function commerce_body_classes( $classes ) {
    $classes[] = 'itk-commerce-theme';

    if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
        $classes[] = 'itk-is-commerce';
    }

    return array_unique( $classes );
}
