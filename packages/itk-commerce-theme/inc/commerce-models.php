<?php
/**
 * Reusable WooCommerce page-model registry and option contracts.
 *
 * The Theme owns presentation/model contracts. Customer/profile selection is
 * delegated to public filters implemented by optional modules.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', __NAMESPACE__ . '\\commerce_template_body_classes' );
add_filter( 'loop_shop_columns', __NAMESPACE__ . '\\commerce_loop_columns', 30 );
add_filter( 'woocommerce_output_related_products_args', __NAMESPACE__ . '\\commerce_related_product_args', 30 );
add_filter( 'render_block', __NAMESPACE__ . '\\commerce_block_shell', 20, 2 );
add_action( 'woocommerce_before_cart', __NAMESPACE__ . '\\commerce_cart_shell_start', 1 );
add_action( 'woocommerce_after_cart', __NAMESPACE__ . '\\commerce_cart_shell_end', 999 );

/**
 * Return customer-neutral page-layout model definitions.
 *
 * @return array<string,array<string,array<string,string>>>
 */
function commerce_template_models() {
    $models = array(
        'shop' => array(
            'grid' => array(
                'label'       => __( 'Grid', 'itk-commerce' ),
                'description' => __( 'Balanced product grid for general stores.', 'itk-commerce' ),
            ),
            'sidebar' => array(
                'label'       => __( 'Sidebar', 'itk-commerce' ),
                'description' => __( 'Product grid with the Theme shop widget area.', 'itk-commerce' ),
            ),
            'editorial' => array(
                'label'       => __( 'Editorial', 'itk-commerce' ),
                'description' => __( 'Larger lead product card with a more visual catalog rhythm.', 'itk-commerce' ),
            ),
            'compact' => array(
                'label'       => __( 'Compact', 'itk-commerce' ),
                'description' => __( 'Dense catalog model for larger assortments.', 'itk-commerce' ),
            ),
        ),
        'product' => array(
            'classic' => array(
                'label'       => __( 'Classic', 'itk-commerce' ),
                'description' => __( 'Balanced gallery and summary presentation.', 'itk-commerce' ),
            ),
            'gallery-left' => array(
                'label'       => __( 'Gallery Left', 'itk-commerce' ),
                'description' => __( 'Large product gallery with the summary on the right.', 'itk-commerce' ),
            ),
            'gallery-right' => array(
                'label'       => __( 'Gallery Right', 'itk-commerce' ),
                'description' => __( 'Product summary first with the gallery on the right.', 'itk-commerce' ),
            ),
            'centered' => array(
                'label'       => __( 'Centered', 'itk-commerce' ),
                'description' => __( 'Single-column product presentation for focused storytelling.', 'itk-commerce' ),
            ),
            'compact' => array(
                'label'       => __( 'Compact', 'itk-commerce' ),
                'description' => __( 'Tighter two-column product layout for shorter pages.', 'itk-commerce' ),
            ),
        ),
        'cart' => array(
            'classic' => array(
                'label'       => __( 'Classic', 'itk-commerce' ),
                'description' => __( 'Standard WooCommerce cart flow with Theme styling.', 'itk-commerce' ),
            ),
            'split' => array(
                'label'       => __( 'Split', 'itk-commerce' ),
                'description' => __( 'Cart items and totals displayed side-by-side on larger screens.', 'itk-commerce' ),
            ),
            'compact' => array(
                'label'       => __( 'Compact', 'itk-commerce' ),
                'description' => __( 'Narrow, focused cart presentation.', 'itk-commerce' ),
            ),
        ),
        'checkout' => array(
            'classic' => array(
                'label'       => __( 'Classic', 'itk-commerce' ),
                'description' => __( 'Standard checkout structure with Theme styling.', 'itk-commerce' ),
            ),
            'split' => array(
                'label'       => __( 'Split', 'itk-commerce' ),
                'description' => __( 'Customer fields and order review in two balanced columns.', 'itk-commerce' ),
            ),
            'focused' => array(
                'label'       => __( 'Focused', 'itk-commerce' ),
                'description' => __( 'Narrow checkout presentation for distraction-reduced flows.', 'itk-commerce' ),
            ),
        ),
    );

    /**
     * Filter reusable WooCommerce page-model definitions.
     *
     * @param array<string,array<string,array<string,string>>> $models Models.
     */
    $models = apply_filters( 'itk_commerce_template_models', $models );

    return is_array( $models ) ? $models : array();
}

/**
 * Return the active WooCommerce page area.
 *
 * @return string
 */
function commerce_template_area() {
    if ( function_exists( 'is_product' ) && is_product() ) {
        return 'product';
    }
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        return 'cart';
    }
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        return 'checkout';
    }
    if (
        ( function_exists( 'is_shop' ) && is_shop() ) ||
        ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
    ) {
        return 'shop';
    }

    return '';
}

/**
 * Return the Theme default for a commerce area.
 *
 * @param string $area Commerce area.
 * @return string
 */
function commerce_template_default_model( $area ) {
    $defaults = array(
        'shop'     => 'grid',
        'product'  => 'classic',
        'cart'     => 'classic',
        'checkout' => 'classic',
    );

    $area = sanitize_key( $area );
    return isset( $defaults[ $area ] ) ? $defaults[ $area ] : 'classic';
}

/**
 * Resolve a validated model for one commerce page area.
 *
 * @param string $area          Commerce area.
 * @param string $default_model Optional default model.
 * @return string
 */
function commerce_template_model( $area, $default_model = '' ) {
    $area   = sanitize_key( $area );
    $models = commerce_template_models();

    if ( empty( $models[ $area ] ) || ! is_array( $models[ $area ] ) ) {
        return sanitize_key( $default_model ?: 'classic' );
    }

    $default_model = sanitize_key( $default_model ?: commerce_template_default_model( $area ) );
    if ( ! isset( $models[ $area ][ $default_model ] ) ) {
        $keys          = array_keys( $models[ $area ] );
        $default_model = $keys ? (string) reset( $keys ) : 'classic';
    }

    /**
     * Filter the selected Shop/Product/Cart/Checkout model.
     *
     * @param string $default_model Current/default model.
     * @param string $area          Commerce page area.
     */
    $selected = sanitize_key( apply_filters( 'itk_commerce_template_model', $default_model, $area ) );

    return isset( $models[ $area ][ $selected ] ) ? $selected : $default_model;
}

/**
 * Return bounded visual options for a commerce area.
 *
 * @param string $area Commerce area.
 * @return array<string,mixed>
 */
function commerce_template_options( $area ) {
    $area = sanitize_key( $area );

    $defaults = array(
        'shop' => array(
            'columns'          => 4,
            'sidebar_position' => 'left',
            'density'          => 'comfortable',
        ),
        'product' => array(
            'gallery_width'  => 50,
            'sticky_summary' => false,
            'tabs_layout'    => 'tabs',
        ),
        'cart' => array(
            'sticky_totals' => false,
            'density'       => 'comfortable',
        ),
        'checkout' => array(
            'sticky_summary' => false,
            'content_width'  => 'wide',
            'field_density'  => 'comfortable',
        ),
    );

    $options = isset( $defaults[ $area ] ) ? $defaults[ $area ] : array();

    /**
     * Filter visual options for one commerce area.
     *
     * @param array<string,mixed> $options Defaults/current values.
     * @param string              $area    Commerce area.
     */
    $filtered = apply_filters( 'itk_commerce_template_options', $options, $area );
    if ( is_array( $filtered ) ) {
        $options = array_merge( $options, $filtered );
    }

    if ( 'shop' === $area ) {
        $options['columns']          = max( 2, min( 6, absint( $options['columns'] ) ) );
        $options['sidebar_position'] = in_array( $options['sidebar_position'], array( 'left', 'right' ), true ) ? $options['sidebar_position'] : 'left';
        $options['density']          = in_array( $options['density'], array( 'comfortable', 'compact' ), true ) ? $options['density'] : 'comfortable';
    } elseif ( 'product' === $area ) {
        $width = absint( $options['gallery_width'] );
        $options['gallery_width']  = in_array( $width, array( 40, 50, 60 ), true ) ? $width : 50;
        $options['sticky_summary'] = ! empty( $options['sticky_summary'] );
        $options['tabs_layout']    = in_array( $options['tabs_layout'], array( 'tabs', 'stacked' ), true ) ? $options['tabs_layout'] : 'tabs';
    } elseif ( 'cart' === $area ) {
        $options['sticky_totals'] = ! empty( $options['sticky_totals'] );
        $options['density']       = in_array( $options['density'], array( 'comfortable', 'compact' ), true ) ? $options['density'] : 'comfortable';
    } elseif ( 'checkout' === $area ) {
        $options['sticky_summary'] = ! empty( $options['sticky_summary'] );
        $options['content_width']  = in_array( $options['content_width'], array( 'boxed', 'wide' ), true ) ? $options['content_width'] : 'wide';
        $options['field_density']  = in_array( $options['field_density'], array( 'comfortable', 'compact' ), true ) ? $options['field_density'] : 'comfortable';
    }

    return $options;
}

/**
 * Add stable model/option classes for Theme CSS and integrations.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function commerce_template_body_classes( $classes ) {
    $classes = is_array( $classes ) ? $classes : array();
    $area    = commerce_template_area();

    if ( ! $area ) {
        return $classes;
    }

    $model   = commerce_template_model( $area );
    $options = commerce_template_options( $area );

    $classes[] = 'itk-commerce-area-' . sanitize_html_class( $area );
    $classes[] = 'itk-' . sanitize_html_class( $area ) . '-model-' . sanitize_html_class( $model );

    if ( 'shop' === $area ) {
        $classes[] = 'itk-commerce-columns-' . absint( $options['columns'] );
        $classes[] = 'itk-shop-density-' . sanitize_html_class( $options['density'] );
        $classes[] = 'itk-shop-sidebar-' . sanitize_html_class( $options['sidebar_position'] );
    } elseif ( 'product' === $area ) {
        $classes[] = 'itk-product-gallery-' . absint( $options['gallery_width'] );
        $classes[] = 'itk-product-tabs-' . sanitize_html_class( $options['tabs_layout'] );
        if ( $options['sticky_summary'] ) {
            $classes[] = 'itk-product-sticky-summary';
        }
    } elseif ( 'cart' === $area ) {
        $classes[] = 'itk-cart-density-' . sanitize_html_class( $options['density'] );
        if ( $options['sticky_totals'] ) {
            $classes[] = 'itk-cart-sticky-totals';
        }
    } elseif ( 'checkout' === $area ) {
        $classes[] = 'itk-checkout-width-' . sanitize_html_class( $options['content_width'] );
        $classes[] = 'itk-checkout-fields-' . sanitize_html_class( $options['field_density'] );
        if ( $options['sticky_summary'] ) {
            $classes[] = 'itk-checkout-sticky-summary';
        }
    }

    return array_values( array_unique( $classes ) );
}

/**
 * Apply visual shop column choice through WooCommerce's supported filter.
 *
 * @param int $columns Existing columns.
 * @return int
 */
function commerce_loop_columns( $columns ) {
    if ( 'shop' !== commerce_template_area() ) {
        return $columns;
    }

    $options = commerce_template_options( 'shop' );
    return absint( $options['columns'] );
}

/**
 * Keep related products bounded and aligned with the selected product layout.
 *
 * @param array<string,mixed> $args Related-product args.
 * @return array<string,mixed>
 */
function commerce_related_product_args( $args ) {
    if ( 'product' !== commerce_template_area() || ! is_array( $args ) ) {
        return $args;
    }

    $args['columns'] = 4;
    return $args;
}

/**
 * Whether the Theme shop sidebar should be rendered for the current request.
 *
 * @return bool
 */
function commerce_shop_sidebar_active() {
    return 'shop' === commerce_template_area()
        && 'sidebar' === commerce_template_model( 'shop' )
        && function_exists( 'is_active_sidebar' )
        && is_active_sidebar( 'itk-shop-sidebar' );
}

/**
 * Wrap current Cart/Checkout blocks at their public block boundary instead of
 * targeting WooCommerce's private internal component markup. The native block
 * remains responsible for its own internal responsive and payment behavior.
 *
 * @param string              $block_content Rendered block content.
 * @param array<string,mixed> $block         Parsed block data.
 * @return string
 */
function commerce_block_shell( $block_content, $block ) {
    if ( ! is_array( $block ) || empty( $block['blockName'] ) || ! is_string( $block_content ) || '' === $block_content ) {
        return $block_content;
    }

    $map = array(
        'woocommerce/cart'     => 'cart',
        'woocommerce/checkout' => 'checkout',
    );

    if ( ! isset( $map[ $block['blockName'] ] ) ) {
        return $block_content;
    }

    $area = $map[ $block['blockName'] ];
    if ( $area !== commerce_template_area() ) {
        return $block_content;
    }

    $model = commerce_template_model( $area );
    return '<div class="itk-commerce-block-shell itk-commerce-block-shell--' . esc_attr( $area ) . ' itk-commerce-block-shell--model-' . esc_attr( $model ) . '">' . $block_content . '</div>';
}

/**
 * Wrap classic cart form/collaterals so split/compact models can be expressed
 * without overriding WooCommerce cart templates.
 *
 * @return void
 */
function commerce_cart_shell_start() {
    if ( 'cart' !== commerce_template_area() ) {
        return;
    }

    $model = commerce_template_model( 'cart' );
    echo '<div class="itk-cart-shell itk-cart-shell--' . esc_attr( $model ) . '">';
}

/**
 * Close the classic cart model shell.
 *
 * @return void
 */
function commerce_cart_shell_end() {
    if ( 'cart' === commerce_template_area() ) {
        echo '</div>';
    }
}
