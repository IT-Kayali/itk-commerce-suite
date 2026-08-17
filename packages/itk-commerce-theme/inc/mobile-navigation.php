<?php
/**
 * Mobile bottom navigation.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Render the configurable mobile bottom navigation.
 * A dedicated WordPress menu wins; otherwise a neutral commerce fallback is used.
 */
function mobile_bottom_navigation() {
    /**
     * Filter whether the mobile bottom navigation is rendered.
     *
     * @param bool $enabled Whether the navigation is enabled.
     */
    if ( ! apply_filters( 'itk_commerce_mobile_bottom_enabled', true ) ) {
        return;
    }

    if ( has_nav_menu( 'mobile-bottom' ) ) {
        ?>
        <nav class="itk-mobile-bottom" aria-label="<?php esc_attr_e( 'Mobile Bottom Navigation', 'itk-commerce' ); ?>">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'mobile-bottom',
                    'container'      => false,
                    'menu_class'     => 'itk-mobile-bottom__menu',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                )
            );
            ?>
        </nav>
        <?php
        return;
    }

    $items = array(
        array(
            'label' => __( 'Home', 'itk-commerce' ),
            'url'   => home_url( '/' ),
            'icon'  => 'home',
        ),
        array(
            'label' => __( 'Shop', 'itk-commerce' ),
            'url'   => commerce_page_url( 'shop' ),
            'icon'  => 'shop',
        ),
        array(
            'label' => __( 'Cart', 'itk-commerce' ),
            'url'   => commerce_page_url( 'cart' ),
            'icon'  => 'cart',
            'badge' => true,
        ),
        array(
            'label' => __( 'Account', 'itk-commerce' ),
            'url'   => commerce_page_url( 'myaccount' ),
            'icon'  => 'user',
        ),
    );

    /**
     * Filter fallback mobile-navigation items.
     *
     * The Layouts module uses this to apply customer-profile configuration.
     * A maximum of six valid items is rendered.
     *
     * @param array<int,array<string,mixed>> $items Navigation items.
     */
    $items = apply_filters( 'itk_commerce_mobile_bottom_items', $items );
    $items = is_array( $items ) ? array_slice( $items, 0, 6 ) : array();
    $items = array_values(
        array_filter(
            $items,
            static function ( $item ) {
                return is_array( $item ) && ! empty( $item['label'] ) && ! empty( $item['url'] ) && ! empty( $item['icon'] );
            }
        )
    );

    if ( empty( $items ) ) {
        return;
    }
    ?>
    <nav class="itk-mobile-bottom" aria-label="<?php esc_attr_e( 'Mobile Bottom Navigation', 'itk-commerce' ); ?>">
        <ul class="itk-mobile-bottom__menu">
            <?php foreach ( $items as $item ) : ?>
                <li class="menu-item">
                    <a href="<?php echo esc_url( $item['url'] ); ?>">
                        <span class="itk-mobile-bottom__icon-wrap">
                            <?php icon( sanitize_key( $item['icon'] ) ); ?>
                            <?php if ( ! empty( $item['badge'] ) ) : ?>
                                <?php cart_badge(); ?>
                            <?php endif; ?>
                        </span>
                        <span class="itk-mobile-bottom__label"><?php echo esc_html( $item['label'] ); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
}
