<?php
/**
 * Reusable WooCommerce product-card models, state badges and extension slots.
 *
 * The Theme owns presentation only. Optional quick-view, wishlist, compare or
 * other business behavior plugs into the public action/filter contracts below.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', __NAMESPACE__ . '\\product_card_body_classes' );
add_action( 'woocommerce_before_shop_loop_item_title', __NAMESPACE__ . '\\render_product_state_badges', 11 );
add_action( 'woocommerce_after_shop_loop_item', __NAMESPACE__ . '\\render_product_card_actions', 15 );

/**
 * Return reusable product-card model definitions.
 *
 * @return array<string,array<string,string>>
 */
function product_card_models() {
    $models = array(
        'classic' => array(
            'label'       => __( 'Classic', 'itk-commerce' ),
            'description' => __( 'Balanced card with familiar catalog spacing and actions.', 'itk-commerce' ),
        ),
        'minimal' => array(
            'label'       => __( 'Minimal', 'itk-commerce' ),
            'description' => __( 'Borderless product card with a clean editorial presentation.', 'itk-commerce' ),
        ),
        'boxed' => array(
            'label'       => __( 'Boxed', 'itk-commerce' ),
            'description' => __( 'Defined card surface with stronger content separation.', 'itk-commerce' ),
        ),
        'overlay' => array(
            'label'       => __( 'Overlay', 'itk-commerce' ),
            'description' => __( 'Image-led card with the primary action lifted onto the media area.', 'itk-commerce' ),
        ),
    );

    /**
     * Filter reusable product-card models.
     *
     * @param array<string,array<string,string>> $models Product-card models.
     */
    $filtered = apply_filters( 'itk_commerce_product_card_models', $models );

    return is_array( $filtered ) ? $filtered : $models;
}

/**
 * Resolve a validated product-card model.
 *
 * @param string $default_model Default model.
 * @return string
 */
function product_card_model( $default_model = 'classic' ) {
    $models        = product_card_models();
    $default_model = sanitize_key( $default_model );

    if ( ! isset( $models[ $default_model ] ) ) {
        $keys          = array_keys( $models );
        $default_model = $keys ? (string) reset( $keys ) : 'classic';
    }

    /**
     * Filter the selected reusable product-card model.
     *
     * @param string $default_model Current/default model.
     */
    $selected = sanitize_key( apply_filters( 'itk_commerce_product_card_model', $default_model ) );

    return isset( $models[ $selected ] ) ? $selected : $default_model;
}

/**
 * Return bounded product-card visual options.
 *
 * @return array<string,mixed>
 */
function product_card_options() {
    $defaults = array(
        'image_ratio'      => 'portrait',
        'content_align'    => 'left',
        'price_treatment'  => 'standard',
        'action_treatment' => 'button',
        'hover_behavior'   => 'lift',
        'badge_style'      => 'pill',
        'show_state_badges'=> true,
        'new_days'         => 30,
    );

    /**
     * Filter product-card options before Theme validation.
     *
     * @param array<string,mixed> $defaults Product-card defaults/current values.
     */
    $filtered = apply_filters( 'itk_commerce_product_card_options', $defaults );
    $options  = is_array( $filtered ) ? array_merge( $defaults, $filtered ) : $defaults;

    $options['image_ratio'] = in_array( $options['image_ratio'], array( 'portrait', 'square', 'landscape' ), true )
        ? $options['image_ratio']
        : 'portrait';
    $options['content_align'] = in_array( $options['content_align'], array( 'left', 'center' ), true )
        ? $options['content_align']
        : 'left';
    $options['price_treatment'] = in_array( $options['price_treatment'], array( 'standard', 'emphasis', 'muted' ), true )
        ? $options['price_treatment']
        : 'standard';
    $options['action_treatment'] = in_array( $options['action_treatment'], array( 'button', 'outline', 'text' ), true )
        ? $options['action_treatment']
        : 'button';
    $options['hover_behavior'] = in_array( $options['hover_behavior'], array( 'none', 'lift', 'image-zoom' ), true )
        ? $options['hover_behavior']
        : 'lift';
    $options['badge_style'] = in_array( $options['badge_style'], array( 'pill', 'corner', 'minimal' ), true )
        ? $options['badge_style']
        : 'pill';
    $options['show_state_badges'] = ! empty( $options['show_state_badges'] );
    $options['new_days']          = max( 1, min( 365, absint( $options['new_days'] ) ) );

    return $options;
}

/**
 * Add stable product-card classes for catalog, related, upsell and cross-sell loops.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function product_card_body_classes( $classes ) {
    $classes = is_array( $classes ) ? $classes : array();

    if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
        return $classes;
    }

    $model   = product_card_model();
    $options = product_card_options();

    $classes[] = 'itk-card-model-' . sanitize_html_class( $model );
    $classes[] = 'itk-card-image-' . sanitize_html_class( $options['image_ratio'] );
    $classes[] = 'itk-card-align-' . sanitize_html_class( $options['content_align'] );
    $classes[] = 'itk-card-price-' . sanitize_html_class( $options['price_treatment'] );
    $classes[] = 'itk-card-action-' . sanitize_html_class( $options['action_treatment'] );
    $classes[] = 'itk-card-hover-' . sanitize_html_class( $options['hover_behavior'] );
    $classes[] = 'itk-card-badges-' . sanitize_html_class( $options['badge_style'] );

    if ( $options['show_state_badges'] ) {
        $classes[] = 'itk-card-state-badges-enabled';
    }

    return array_values( array_unique( $classes ) );
}

/**
 * Render sold-out, featured, new and module-provided loop badges. WooCommerce's
 * native sale flash remains untouched and is styled as part of the same visual
 * system, avoiding duplicated sale state markup.
 *
 * @return void
 */
function render_product_state_badges() {
    global $product;

    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        return;
    }

    $options = product_card_options();
    $badges  = array();

    if ( $options['show_state_badges'] ) {
        if ( ! $product->is_in_stock() ) {
            $badges['sold-out'] = array(
                'label' => __( 'Sold out', 'itk-commerce' ),
                'class' => 'sold-out',
            );
        }

        if ( $product->is_featured() ) {
            $badges['featured'] = array(
                'label' => __( 'Featured', 'itk-commerce' ),
                'class' => 'featured',
            );
        }

        $created = $product->get_date_created();
        if ( $created && $created->getTimestamp() >= ( time() - ( DAY_IN_SECONDS * absint( $options['new_days'] ) ) ) ) {
            $badges['new'] = array(
                'label' => __( 'New', 'itk-commerce' ),
                'class' => 'new',
            );
        }
    }

    /**
     * Filter product-card badges. Modules may add/remove portable badge entries.
     * Each entry accepts `label` and optional `class` keys.
     *
     * @param array<string,array<string,string>> $badges  Badge definitions.
     * @param \WC_Product                        $product Product object.
     * @param string                             $context Product-loop context.
     */
    $badges = apply_filters( 'itk_commerce_product_badges', $badges, $product, product_card_context() );

    if ( ! is_array( $badges ) || empty( $badges ) ) {
        return;
    }

    echo '<div class="itk-product-badges" aria-label="' . esc_attr__( 'Product labels', 'itk-commerce' ) . '">';
    foreach ( $badges as $key => $badge ) {
        if ( ! is_array( $badge ) || empty( $badge['label'] ) ) {
            continue;
        }

        $class = ! empty( $badge['class'] ) ? sanitize_html_class( $badge['class'] ) : sanitize_html_class( $key );
        echo '<span class="itk-product-badge itk-product-badge--' . esc_attr( $class ) . '">' . esc_html( $badge['label'] ) . '</span>';
    }
    echo '</div>';
}

/**
 * Render an optional action slot only when an integration actually attaches to
 * it. The Theme never forces quick view, wishlist or compare functionality.
 *
 * @return void
 */
function render_product_card_actions() {
    global $product;

    if ( ! $product || ! is_a( $product, 'WC_Product' ) || ! has_action( 'itk_commerce_product_card_actions' ) ) {
        return;
    }

    echo '<div class="itk-product-card-actions">';
    /**
     * Render optional product-card actions.
     *
     * @param \WC_Product $product Product object.
     * @param string      $context Product-loop context.
     */
    do_action( 'itk_commerce_product_card_actions', $product, product_card_context() );
    echo '</div>';
}

/**
 * Return a stable, customer-neutral loop context for integrations.
 *
 * @return string
 */
function product_card_context() {
    if ( function_exists( 'wc_get_loop_prop' ) ) {
        $name = sanitize_key( (string) wc_get_loop_prop( 'name', '' ) );
        if ( $name ) {
            return $name;
        }
    }

    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return 'shop';
    }
    if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
        return 'product-taxonomy';
    }

    return 'product-loop';
}
