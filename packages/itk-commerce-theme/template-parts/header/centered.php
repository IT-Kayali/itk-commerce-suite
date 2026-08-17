<?php
/**
 * Centered header model and compatible centered visual variants.
 *
 * @package ITK_Commerce_Theme
 */

use function ITK\Commerce\Theme\cart_badge;
use function ITK\Commerce\Theme\commerce_page_url;
use function ITK\Commerce\Theme\icon;
use function ITK\Commerce\Theme\site_branding;

defined( 'ABSPATH' ) || exit;

$layout_model = isset( $args['itk_layout_model'] ) ? sanitize_key( $args['itk_layout_model'] ) : 'centered';
?>
<header class="itk-site-header itk-site-header--centered itk-site-header--<?php echo esc_attr( $layout_model ); ?>" data-itk-site-header data-itk-layout-model="<?php echo esc_attr( $layout_model ); ?>">
    <?php if ( has_nav_menu( 'secondary' ) ) : ?>
        <div class="itk-topbar">
            <div class="itk-container">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'secondary',
                        'container'      => false,
                        'menu_class'     => 'itk-topbar__menu',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    )
                );
                ?>
            </div>
        </div>
    <?php endif; ?>

    <?php do_action( 'itk_commerce_before_header_main' ); ?>

    <div class="itk-header-centered">
        <div class="itk-container itk-header-centered__inner">
            <div class="itk-header-centered__start">
                <button class="itk-header-menu-toggle" type="button" aria-expanded="false" aria-controls="itk-primary-navigation" data-itk-menu-toggle>
                    <?php icon( 'menu' ); ?>
                    <span class="screen-reader-text"><?php esc_html_e( 'Open navigation', 'itk-commerce' ); ?></span>
                </button>
                <a class="itk-header-action" href="#itk-header-search" aria-label="<?php esc_attr_e( 'Search', 'itk-commerce' ); ?>">
                    <?php icon( 'search' ); ?>
                    <span class="itk-header-action__label"><?php esc_html_e( 'Search', 'itk-commerce' ); ?></span>
                </a>
            </div>

            <div class="itk-header-centered__brand">
                <?php site_branding(); ?>
            </div>

            <div class="itk-header-actions" aria-label="<?php esc_attr_e( 'Shop actions', 'itk-commerce' ); ?>">
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
    </div>

    <div class="itk-primary-navigation-wrap" data-itk-navigation-panel>
        <div class="itk-container">
            <nav id="itk-primary-navigation" class="itk-primary-navigation itk-primary-navigation--centered" aria-label="<?php esc_attr_e( 'Primary Navigation', 'itk-commerce' ); ?>">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'itk-primary-navigation__menu',
                        'fallback_cb'    => false,
                    )
                );
                ?>
            </nav>
        </div>
    </div>

    <div class="itk-header-search itk-header-search--centered" id="itk-header-search">
        <div class="itk-container">
            <?php
            if ( function_exists( 'get_product_search_form' ) ) {
                get_product_search_form();
            } else {
                get_search_form();
            }
            ?>
        </div>
    </div>

    <?php do_action( 'itk_commerce_after_header' ); ?>
</header>
