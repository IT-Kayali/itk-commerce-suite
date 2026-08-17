<?php
/**
 * Reusable WooCommerce My Account presentation contracts.
 *
 * WooCommerce remains authoritative for endpoints, authentication, orders,
 * downloads, addresses and form processing. The Theme only owns presentation.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', __NAMESPACE__ . '\\account_body_classes' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_account_assets', 31 );
add_action( 'woocommerce_account_dashboard', __NAMESPACE__ . '\\render_account_dashboard_cards', 5 );

/**
 * Return bounded My Account presentation options.
 *
 * @return array<string,mixed>
 */
function account_options() {
    $defaults = array(
        'model'                => 'sidebar',
        'content_width'        => 'wide',
        'navigation_style'     => 'soft',
        'card_style'           => 'soft',
        'orders_density'       => 'comfortable',
        'show_dashboard_cards' => true,
        'dashboard_cards'      => array( 'orders', 'downloads', 'edit-address', 'edit-account' ),
    );

    /**
     * Filter My Account presentation options before final Theme validation.
     *
     * @param array<string,mixed> $defaults Current/default options.
     */
    $filtered = apply_filters( 'itk_commerce_account_options', $defaults );
    $options  = is_array( $filtered ) ? array_merge( $defaults, $filtered ) : $defaults;

    $options['model'] = in_array( $options['model'], array( 'sidebar', 'top-nav', 'compact' ), true )
        ? $options['model']
        : 'sidebar';
    $options['content_width'] = in_array( $options['content_width'], array( 'wide', 'boxed' ), true )
        ? $options['content_width']
        : 'wide';
    $options['navigation_style'] = in_array( $options['navigation_style'], array( 'soft', 'bordered', 'minimal' ), true )
        ? $options['navigation_style']
        : 'soft';
    $options['card_style'] = in_array( $options['card_style'], array( 'soft', 'bordered', 'minimal' ), true )
        ? $options['card_style']
        : 'soft';
    $options['orders_density'] = in_array( $options['orders_density'], array( 'comfortable', 'compact' ), true )
        ? $options['orders_density']
        : 'comfortable';
    $options['show_dashboard_cards'] = ! empty( $options['show_dashboard_cards'] );

    $allowed_cards = array( 'orders', 'downloads', 'edit-address', 'edit-account' );
    $cards         = is_array( $options['dashboard_cards'] ) ? $options['dashboard_cards'] : $defaults['dashboard_cards'];
    $cards         = array_values( array_unique( array_filter( array_map( 'sanitize_key', $cards ) ) ) );
    $options['dashboard_cards'] = array_values(
        array_filter(
            $cards,
            static function ( $card ) use ( $allowed_cards ) {
                return in_array( $card, $allowed_cards, true );
            }
        )
    );

    return $options;
}

/**
 * Whether the current request is a WooCommerce account page.
 *
 * @return bool
 */
function account_page_active() {
    return class_exists( 'WooCommerce' ) && function_exists( 'is_account_page' ) && is_account_page();
}

/**
 * Add stable classes used by reusable account CSS and optional modules.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function account_body_classes( $classes ) {
    $classes = is_array( $classes ) ? $classes : array();

    if ( ! account_page_active() ) {
        return $classes;
    }

    $options   = account_options();
    $classes[] = 'itk-account-enabled';
    $classes[] = 'itk-account-model-' . sanitize_html_class( $options['model'] );
    $classes[] = 'itk-account-width-' . sanitize_html_class( $options['content_width'] );
    $classes[] = 'itk-account-nav-' . sanitize_html_class( $options['navigation_style'] );
    $classes[] = 'itk-account-cards-' . sanitize_html_class( $options['card_style'] );
    $classes[] = 'itk-account-orders-' . sanitize_html_class( $options['orders_density'] );

    return array_values( array_unique( $classes ) );
}

/**
 * Load account CSS only on the WooCommerce account page.
 *
 * @return void
 */
function enqueue_account_assets() {
    if ( ! account_page_active() ) {
        return;
    }

    wp_enqueue_style(
        'itk-commerce-account',
        get_template_directory_uri() . '/assets/css/account.css',
        array( 'itk-commerce-components' ),
        asset_version( 'assets/css/account.css' )
    );
}

/**
 * Return portable dashboard-card definitions for supported WooCommerce endpoints.
 *
 * @return array<string,array<string,string>>
 */
function account_dashboard_card_definitions() {
    $definitions = array(
        'orders' => array(
            'label'       => __( 'Orders', 'itk-commerce' ),
            'description' => __( 'Review current and previous orders.', 'itk-commerce' ),
        ),
        'downloads' => array(
            'label'       => __( 'Downloads', 'itk-commerce' ),
            'description' => __( 'Access downloadable purchases when available.', 'itk-commerce' ),
        ),
        'edit-address' => array(
            'label'       => __( 'Addresses', 'itk-commerce' ),
            'description' => __( 'Manage billing and shipping addresses.', 'itk-commerce' ),
        ),
        'edit-account' => array(
            'label'       => __( 'Account details', 'itk-commerce' ),
            'description' => __( 'Update your name, email and password.', 'itk-commerce' ),
        ),
    );

    /**
     * Filter reusable account dashboard-card definitions.
     *
     * @param array<string,array<string,string>> $definitions Card definitions.
     */
    $filtered = apply_filters( 'itk_commerce_account_dashboard_card_definitions', $definitions );

    return is_array( $filtered ) ? $filtered : $definitions;
}

/**
 * Render dashboard shortcuts using supported WooCommerce account endpoint URLs.
 *
 * @return void
 */
function render_account_dashboard_cards() {
    if ( ! account_page_active() || ! function_exists( 'wc_get_account_endpoint_url' ) ) {
        return;
    }

    $options = account_options();
    if ( ! $options['show_dashboard_cards'] || empty( $options['dashboard_cards'] ) ) {
        return;
    }

    $definitions = account_dashboard_card_definitions();
    $menu_items  = function_exists( 'wc_get_account_menu_items' ) ? wc_get_account_menu_items() : array();
    $cards       = array();

    foreach ( $options['dashboard_cards'] as $endpoint ) {
        if ( ! isset( $definitions[ $endpoint ] ) ) {
            continue;
        }
        if ( is_array( $menu_items ) && $menu_items && ! array_key_exists( $endpoint, $menu_items ) ) {
            continue;
        }

        $cards[ $endpoint ]        = $definitions[ $endpoint ];
        $cards[ $endpoint ]['url'] = wc_get_account_endpoint_url( $endpoint );
    }

    /**
     * Filter normalized dashboard cards after endpoint availability is checked.
     *
     * @param array<string,array<string,string>> $cards Dashboard cards.
     */
    $cards = apply_filters( 'itk_commerce_account_dashboard_cards', $cards );

    if ( ! is_array( $cards ) || empty( $cards ) ) {
        return;
    }
    ?>
    <nav class="itk-account-dashboard-cards" aria-label="<?php esc_attr_e( 'Account shortcuts', 'itk-commerce' ); ?>">
        <?php foreach ( $cards as $endpoint => $card ) : ?>
            <?php if ( empty( $card['label'] ) || empty( $card['url'] ) ) { continue; } ?>
            <a class="itk-account-dashboard-card itk-account-dashboard-card--<?php echo esc_attr( sanitize_html_class( $endpoint ) ); ?>" href="<?php echo esc_url( $card['url'] ); ?>">
                <span class="itk-account-dashboard-card__mark" aria-hidden="true"></span>
                <span class="itk-account-dashboard-card__body">
                    <strong><?php echo esc_html( $card['label'] ); ?></strong>
                    <?php if ( ! empty( $card['description'] ) ) : ?>
                        <small><?php echo esc_html( $card['description'] ); ?></small>
                    <?php endif; ?>
                </span>
                <span class="itk-account-dashboard-card__arrow" aria-hidden="true">→</span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}
