<?php
/**
 * Public component integration contracts for optional Commerce Suite modules.
 *
 * The Theme owns only stable presentation slots. Search/filter engines,
 * quick-view, wishlist, compare and other business behavior remain installable
 * modules that attach to these contracts when enabled.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\\render_catalog_extension_slots', 40 );

/**
 * Return stable catalog extension slot definitions.
 *
 * @return array<string,array<string,string>>
 */
function catalog_extension_slots() {
    $slots = array(
        'search' => array(
            'action' => 'itk_commerce_catalog_search',
            'label'  => __( 'Catalog search', 'itk-commerce' ),
        ),
        'filters' => array(
            'action' => 'itk_commerce_catalog_filters',
            'label'  => __( 'Catalog filters', 'itk-commerce' ),
        ),
    );

    /**
     * Filter catalog extension slots. Modules may add portable slot definitions,
     * but each entry must expose a valid WordPress action name and label.
     *
     * @param array<string,array<string,string>> $slots Slot definitions.
     */
    $filtered = apply_filters( 'itk_commerce_catalog_extension_slots', $slots );

    return is_array( $filtered ) ? $filtered : $slots;
}

/**
 * Return the normalized catalog context passed to optional integrations.
 *
 * @return array<string,mixed>
 */
function catalog_extension_context() {
    return array(
        'area'    => 'shop',
        'model'   => function_exists( __NAMESPACE__ . '\\commerce_template_model' ) ? commerce_template_model( 'shop' ) : 'grid',
        'options' => function_exists( __NAMESPACE__ . '\\commerce_template_options' ) ? commerce_template_options( 'shop' ) : array(),
    );
}

/**
 * Render catalog extension slots only when a module has attached to them.
 * Empty wrappers are intentionally avoided.
 *
 * @return void
 */
function render_catalog_extension_slots() {
    if ( function_exists( __NAMESPACE__ . '\\commerce_template_area' ) && 'shop' !== commerce_template_area() ) {
        return;
    }

    $active  = array();
    $context = catalog_extension_context();

    foreach ( catalog_extension_slots() as $slot_id => $definition ) {
        if ( ! is_array( $definition ) || empty( $definition['action'] ) ) {
            continue;
        }

        $action = sanitize_key( $definition['action'] );
        if ( $action && has_action( $action ) ) {
            $active[ sanitize_key( $slot_id ) ] = array(
                'action' => $action,
                'label'  => ! empty( $definition['label'] ) ? sanitize_text_field( $definition['label'] ) : sanitize_key( $slot_id ),
            );
        }
    }

    if ( ! $active ) {
        return;
    }

    echo '<div class="itk-catalog-extensions" data-itk-catalog-extensions>';
    foreach ( $active as $slot_id => $definition ) {
        echo '<div class="itk-catalog-extension-slot itk-catalog-extension-slot--' . esc_attr( sanitize_html_class( $slot_id ) ) . '" data-itk-catalog-slot="' . esc_attr( $slot_id ) . '" aria-label="' . esc_attr( $definition['label'] ) . '">';
        do_action( $definition['action'], $context );
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Return ordered product-card action contracts. Optional modules may attach to
 * one semantic action without needing to coordinate callback priorities with
 * unrelated integrations. The legacy/general action remains last.
 *
 * @return string[]
 */
function product_card_action_hooks() {
    $hooks = array(
        'itk_commerce_product_card_quick_view',
        'itk_commerce_product_card_wishlist',
        'itk_commerce_product_card_compare',
        'itk_commerce_product_card_actions',
    );

    /**
     * Filter the ordered product-card action hook contract.
     *
     * @param string[] $hooks Action hook names.
     */
    $filtered = apply_filters( 'itk_commerce_product_card_action_hooks', $hooks );

    if ( ! is_array( $filtered ) ) {
        return $hooks;
    }

    return array_values( array_unique( array_filter( array_map( 'sanitize_key', $filtered ) ) ) );
}

/**
 * Whether any optional product-card integration is active.
 *
 * @return bool
 */
function product_card_has_actions() {
    foreach ( product_card_action_hooks() as $hook ) {
        if ( has_action( $hook ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Render all active semantic product-card action hooks in the stable order.
 *
 * @param \WC_Product $product Product object.
 * @param string      $context Product-loop context.
 * @return void
 */
function render_product_card_action_hooks( $product, $context ) {
    foreach ( product_card_action_hooks() as $hook ) {
        if ( has_action( $hook ) ) {
            do_action( $hook, $product, $context );
        }
    }
}
