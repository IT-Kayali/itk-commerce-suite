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
 * Open the generic commerce content shell and, for the Sidebar Shop model,
 * render the Theme-owned shop sidebar without overriding WooCommerce templates.
 */
function commerce_wrapper_start() {
    $area  = function_exists( __NAMESPACE__ . '\\commerce_template_area' ) ? commerce_template_area() : '';
    $model = $area && function_exists( __NAMESPACE__ . '\\commerce_template_model' ) ? commerce_template_model( $area ) : '';

    $classes = array( 'itk-site-main', 'itk-commerce-main' );
    if ( $area ) {
        $classes[] = 'itk-commerce-main--' . sanitize_html_class( $area );
    }
    if ( $model ) {
        $classes[] = 'itk-commerce-main--model-' . sanitize_html_class( $model );
    }

    echo '<main id="primary" class="' . esc_attr( implode( ' ', $classes ) ) . '"><div class="itk-container">';

    if ( function_exists( __NAMESPACE__ . '\\commerce_shop_sidebar_active' ) && commerce_shop_sidebar_active() ) {
        $options  = commerce_template_options( 'shop' );
        $position = isset( $options['sidebar_position'] ) ? sanitize_html_class( $options['sidebar_position'] ) : 'left';

        echo '<div class="itk-shop-shell itk-shop-shell--sidebar itk-shop-shell--sidebar-' . esc_attr( $position ) . '">';
        echo '<aside class="itk-shop-sidebar" aria-label="' . esc_attr__( 'Shop filters', 'itk-commerce' ) . '">';
        dynamic_sidebar( 'itk-shop-sidebar' );
        echo '</aside><div class="itk-shop-shell__content">';
    }
}

/**
 * Close the generic commerce content shell.
 */
function commerce_wrapper_end() {
    if ( function_exists( __NAMESPACE__ . '\\commerce_shop_sidebar_active' ) && commerce_shop_sidebar_active() ) {
        echo '</div></div>';
    }

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
