<?php
/**
 * Search-first shop header model.
 *
 * @package ITK_Commerce_Theme
 */

use function ITK\Commerce\Theme\cart_badge;
use function ITK\Commerce\Theme\commerce_page_url;
use function ITK\Commerce\Theme\icon;
use function ITK\Commerce\Theme\site_branding;

defined( 'ABSPATH' ) || exit;
?>
<header class="itk-site-header itk-site-header--shop" data-itk-site-header data-itk-layout-model="shop">
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

    <div class="itk-shop-header-main">
        <div class="itk-container itk-shop-header-main__inner">
            <button class="itk-header-menu-toggle" type="button" aria-expanded="false" aria-controls="itk-primary-navigation" data-itk-menu-toggle>
                <?php icon( 'menu' ); ?>
                <span class="screen-reader-text"><?php esc_html_e( 'Open navigation', 'itk-commerce' ); ?></span>
            </button>

            <?php site_branding(); ?>

            <div class="itk-shop-header-search" id="itk-header-search">
                <?php
                if ( function_exists( 'get_product_search_form' ) ) {
                    get_product_search_form();
                } else {
                    get_search_form();
                }
                ?>
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

    <div class="itk-primary-navigation-wrap itk-primary-navigation-wrap--shop" data-itk-navigation-panel>
        <div class="itk-container itk-shop-navigation-row">
            <?php if ( has_nav_menu( 'mobile' ) ) : ?>
                <nav class="itk-shop-categories" aria-label="<?php esc_attr_e( 'Shop Categories', 'itk-commerce' ); ?>">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'mobile',
                            'container'      => false,
                            'menu_class'     => 'itk-shop-categories__menu',
                            'depth'          => 1,
                            'fallback_cb'    => false,
                        )
                    );
                    ?>
                </nav>
            <?php endif; ?>

            <nav id="itk-primary-navigation" class="itk-primary-navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'itk-commerce' ); ?>">
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

    <?php do_action( 'itk_commerce_after_header' ); ?>
</header>
