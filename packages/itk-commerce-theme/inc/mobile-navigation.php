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
    ?>
    <nav class="itk-mobile-bottom" aria-label="<?php esc_attr_e( 'Mobile Bottom Navigation', 'itk-commerce' ); ?>">
        <ul class="itk-mobile-bottom__menu">
            <?php foreach ( $items as $item ) : ?>
                <li class="menu-item">
                    <a href="<?php echo esc_url( $item['url'] ); ?>">
                        <span class="itk-mobile-bottom__icon-wrap">
                            <?php icon( $item['icon'] ); ?>
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
