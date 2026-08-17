<?php
/**
 * Vertical header/navigation model.
 *
 * @package ITK_Commerce_Theme
 */

use function ITK\Commerce\Theme\cart_badge;
use function ITK\Commerce\Theme\commerce_page_url;
use function ITK\Commerce\Theme\icon;
use function ITK\Commerce\Theme\site_branding;

defined( 'ABSPATH' ) || exit;
?>
<header class="itk-site-header itk-site-header--vertical" data-itk-site-header data-itk-layout-model="vertical">
    <?php do_action( 'itk_commerce_before_header_main' ); ?>
    <div class="itk-vertical-header__inner">
        <div class="itk-vertical-header__top">
            <?php site_branding(); ?>
            <button class="itk-header-menu-toggle" type="button" aria-expanded="false" aria-controls="itk-primary-navigation" data-itk-menu-toggle>
                <?php icon( 'menu' ); ?>
                <span class="screen-reader-text"><?php esc_html_e( 'Open navigation', 'itk-commerce' ); ?></span>
            </button>
        </div>

        <div class="itk-vertical-header__search">
            <?php
            if ( function_exists( 'get_product_search_form' ) ) {
                get_product_search_form();
            } else {
                get_search_form();
            }
            ?>
        </div>

        <div class="itk-vertical-header__navigation" data-itk-navigation-panel>
            <nav id="itk-primary-navigation" class="itk-primary-navigation itk-primary-navigation--vertical" aria-label="<?php esc_attr_e( 'Primary Navigation', 'itk-commerce' ); ?>">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'itk-primary-navigation__menu itk-primary-navigation__menu--vertical',
                        'fallback_cb'    => false,
                    )
                );
                ?>
            </nav>
        </div>

        <div class="itk-header-actions itk-vertical-header__actions" aria-label="<?php esc_attr_e( 'Shop actions', 'itk-commerce' ); ?>">
            <a class="itk-header-action" href="<?php echo esc_url( commerce_page_url( 'myaccount' ) ); ?>">
                <?php icon( 'user' ); ?>
                <span class="itk-header-action__label"><?php esc_html_e( 'Account', 'itk-commerce' ); ?></span>
            </a>
            <a class="itk-header-action itk-header-action--cart" href="<?php echo esc_url( commerce_page_url( 'cart' ) ); ?>">
                <span class="itk-header-action__icon-wrap">
                    <?php icon( 'cart' ); ?>
                    <?php cart_badge(); ?>
                </span>
                <span class="itk-header-action__label"><?php esc_html_e( 'Cart', 'itk-commerce' ); ?></span>
            </a>
        </div>
    </div>
    <?php do_action( 'itk_commerce_after_header' ); ?>
</header>
