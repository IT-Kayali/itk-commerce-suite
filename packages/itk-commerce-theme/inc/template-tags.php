<?php
/**
 * Reusable template helpers.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Render the site identity without customer-specific hard coding.
 */
function site_branding() {
    ?>
    <div class="itk-site-branding">
        <?php if ( has_custom_logo() ) : ?>
            <?php echo wp_kses_post( get_custom_logo() ); ?>
        <?php else : ?>
            <a class="itk-site-branding__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                <span class="itk-site-branding__name"><?php bloginfo( 'name' ); ?></span>
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render a simple icon using inline SVG owned by the theme.
 *
 * @param string $name Icon name.
 */
function icon( $name ) {
    $icons = array(
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'cart'    => '<path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L20.5 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
        'home'    => '<path d="m3 11 9-8 9 8"/><path d="M5 10v11h14V10M9 21v-7h6v7"/>',
        'shop'    => '<path d="M4 9h16l-1-5H5L4 9Z"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>',
        'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
    );

    if ( ! isset( $icons[ $name ] ) ) {
        return;
    }

    printf(
        '<svg class="itk-icon itk-icon--%1$s" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">%2$s</svg>',
        esc_attr( $name ),
        $icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG paths.
    );
}

/**
 * Return the WooCommerce cart quantity for header/mobile badges.
 *
 * @return int
 */
function cart_count() {
    if ( function_exists( 'WC' ) && WC()->cart ) {
        return (int) WC()->cart->get_cart_contents_count();
    }

    return 0;
}

/**
 * Render the cart badge only when there are items.
 */
function cart_badge() {
    $count = cart_count();
    ?>
    <span class="itk-cart-count<?php echo 0 === $count ? ' is-empty' : ''; ?>" data-itk-cart-count><?php echo esc_html( (string) $count ); ?></span>
    <?php
}

/**
 * Return a safe WooCommerce page URL with a home fallback.
 *
 * @param string $page WooCommerce page key.
 * @return string
 */
function commerce_page_url( $page ) {
    if ( function_exists( 'wc_get_page_permalink' ) ) {
        $url = wc_get_page_permalink( $page );
        if ( $url ) {
            return $url;
        }
    }

    return home_url( '/' );
}

/**
 * Render post metadata for standard content templates.
 */
function entry_meta() {
    if ( 'post' !== get_post_type() ) {
        return;
    }

    printf(
        '<div class="itk-entry-meta"><span>%1$s</span><span>%2$s</span></div>',
        esc_html( get_the_date() ),
        esc_html( get_the_author() )
    );
}
