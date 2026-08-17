<?php
/**
 * Accessible WooCommerce mini-cart / off-canvas presentation.
 *
 * The Theme owns presentation and interaction only. Cart state, line items,
 * totals and AJAX fragment data remain WooCommerce-owned.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', __NAMESPACE__ . '\\mini_cart_body_classes' );
add_filter( 'nav_menu_link_attributes', __NAMESPACE__ . '\\mini_cart_menu_link_attributes', 20, 4 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_mini_cart_assets', 30 );
add_action( 'wp_footer', __NAMESPACE__ . '\\render_mini_cart', 20 );
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\mini_cart_fragment' );

function mini_cart_options() {
    $defaults = array(
        'enabled'           => true,
        'position'          => 'end',
        'width'             => 'standard',
        'open_after_add'    => true,
        'close_on_backdrop' => true,
        'show_thumbnails'   => true,
        'show_subtotal'     => true,
    );
    $filtered = apply_filters( 'itk_commerce_mini_cart_options', $defaults );
    $options  = is_array( $filtered ) ? array_merge( $defaults, $filtered ) : $defaults;
    $options['enabled'] = ! empty( $options['enabled'] );
    $options['position'] = in_array( $options['position'], array( 'start', 'end' ), true ) ? $options['position'] : 'end';
    $options['width'] = in_array( $options['width'], array( 'compact', 'standard', 'wide' ), true ) ? $options['width'] : 'standard';
    $options['open_after_add']    = ! empty( $options['open_after_add'] );
    $options['close_on_backdrop'] = ! empty( $options['close_on_backdrop'] );
    $options['show_thumbnails']   = ! empty( $options['show_thumbnails'] );
    $options['show_subtotal']     = ! empty( $options['show_subtotal'] );
    return $options;
}

function mini_cart_enabled() {
    $options = mini_cart_options();
    return $options['enabled'] && class_exists( 'WooCommerce' ) && function_exists( 'woocommerce_mini_cart' );
}

function mini_cart_body_classes( $classes ) {
    $classes = is_array( $classes ) ? $classes : array();
    if ( ! mini_cart_enabled() ) {
        return $classes;
    }
    $options   = mini_cart_options();
    $classes[] = 'itk-mini-cart-enabled';
    $classes[] = 'itk-mini-cart-position-' . sanitize_html_class( $options['position'] );
    $classes[] = 'itk-mini-cart-width-' . sanitize_html_class( $options['width'] );
    if ( ! $options['show_thumbnails'] ) {
        $classes[] = 'itk-mini-cart-hide-thumbnails';
    }
    if ( ! $options['show_subtotal'] ) {
        $classes[] = 'itk-mini-cart-hide-subtotal';
    }
    return array_values( array_unique( $classes ) );
}

function mini_cart_menu_link_attributes( $atts, $menu_item, $args, $depth ) {
    unset( $menu_item, $args, $depth );
    if ( ! mini_cart_enabled() || empty( $atts['href'] ) ) {
        return $atts;
    }
    $cart_url = commerce_page_url( 'cart' );
    if ( untrailingslashit( (string) $atts['href'] ) !== untrailingslashit( $cart_url ) ) {
        return $atts;
    }
    $atts['data-itk-mini-cart-trigger'] = '1';
    $atts['aria-controls']              = 'itk-mini-cart';
    $atts['aria-haspopup']              = 'dialog';
    $atts['aria-expanded']              = 'false';
    return $atts;
}

/**
 * Load assets and WooCommerce's supported classic fragment runtime while the
 * Theme drawer is active. The localized refresh endpoint is also used to bridge
 * public WooCommerce Blocks cart events back to the Woo-owned mini-cart markup.
 */
function enqueue_mini_cart_assets() {
    if ( ! mini_cart_enabled() ) {
        return;
    }
    wp_enqueue_style(
        'itk-commerce-mini-cart',
        get_template_directory_uri() . '/assets/css/mini-cart.css',
        array( 'itk-commerce-components' ),
        asset_version( 'assets/css/mini-cart.css' )
    );
    wp_enqueue_script( 'wc-cart-fragments' );
    wp_enqueue_script(
        'itk-commerce-mini-cart',
        get_template_directory_uri() . '/assets/js/mini-cart.js',
        array(),
        asset_version( 'assets/js/mini-cart.js' ),
        true
    );
    $options = mini_cart_options();
    $refresh_url = class_exists( 'WC_AJAX' )
        ? \WC_AJAX::get_endpoint( 'get_refreshed_fragments' )
        : add_query_arg( 'wc-ajax', 'get_refreshed_fragments', home_url( '/' ) );
    wp_localize_script(
        'itk-commerce-mini-cart',
        'ITKCommerceMiniCart',
        array(
            'openAfterAdd'    => (bool) $options['open_after_add'],
            'closeOnBackdrop' => (bool) $options['close_on_backdrop'],
            'refreshUrl'      => esc_url_raw( $refresh_url ),
        )
    );
}

function render_mini_cart() {
    if ( ! mini_cart_enabled() ) {
        return;
    }
    $options = mini_cart_options();
    $classes = array(
        'itk-mini-cart',
        'itk-mini-cart--position-' . sanitize_html_class( $options['position'] ),
        'itk-mini-cart--width-' . sanitize_html_class( $options['width'] ),
    );
    ?>
    <div id="itk-mini-cart" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-itk-mini-cart aria-hidden="true">
        <div class="itk-mini-cart__backdrop" data-itk-mini-cart-backdrop aria-hidden="true"></div>
        <section class="itk-mini-cart__panel" role="dialog" aria-modal="true" aria-labelledby="itk-mini-cart-title" tabindex="-1" data-itk-mini-cart-panel>
            <header class="itk-mini-cart__header">
                <div>
                    <span class="itk-mini-cart__eyebrow"><?php esc_html_e( 'Shopping cart', 'itk-commerce' ); ?></span>
                    <h2 id="itk-mini-cart-title"><?php esc_html_e( 'Your cart', 'itk-commerce' ); ?></h2>
                </div>
                <button class="itk-mini-cart__close" type="button" data-itk-mini-cart-close aria-label="<?php esc_attr_e( 'Close cart', 'itk-commerce' ); ?>">
                    <?php icon( 'close' ); ?>
                </button>
            </header>
            <?php mini_cart_content(); ?>
        </section>
    </div>
    <?php
}

function mini_cart_content() {
    ?>
    <div class="itk-mini-cart__content" data-itk-mini-cart-content aria-live="polite">
        <?php woocommerce_mini_cart(); ?>
    </div>
    <?php
}

/**
 * Synchronize both the WooCommerce-owned drawer content and every stable Theme
 * cart-count badge through WooCommerce's public fragment contract.
 */
function mini_cart_fragment( $fragments ) {
    if ( ! mini_cart_enabled() ) {
        return $fragments;
    }
    ob_start();
    mini_cart_content();
    $fragments['div[data-itk-mini-cart-content]'] = ob_get_clean();
    ob_start();
    cart_badge();
    $fragments['span[data-itk-cart-count]'] = trim( (string) ob_get_clean() );
    return $fragments;
}
