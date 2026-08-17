<?php
/**
 * Default reusable footer model.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

$layout_model = isset( $args['itk_layout_model'] ) ? sanitize_key( $args['itk_layout_model'] ) : 'classic';
?>
<footer class="itk-site-footer itk-site-footer--<?php echo esc_attr( $layout_model ); ?>" data-itk-layout-model="<?php echo esc_attr( $layout_model ); ?>">
    <?php do_action( 'itk_commerce_before_footer' ); ?>
    <div class="itk-container">
        <div class="itk-footer-grid">
            <div class="itk-footer-brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="itk-footer-brand__name">
                    <?php bloginfo( 'name' ); ?>
                </a>
                <?php if ( get_bloginfo( 'description' ) ) : ?>
                    <p><?php bloginfo( 'description' ); ?></p>
                <?php endif; ?>
            </div>

            <?php for ( $column = 1; $column <= 4; $column++ ) : ?>
                <?php if ( is_active_sidebar( 'itk-footer-' . $column ) ) : ?>
                    <div class="itk-footer-column">
                        <?php dynamic_sidebar( 'itk-footer-' . $column ); ?>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <div class="itk-footer-bottom">
            <p class="itk-footer-copyright">
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
            </p>
            <?php if ( has_nav_menu( 'footer-legal' ) ) : ?>
                <?php
                wp_nav_menu(
                    array(
                        'theme_location'       => 'footer-legal',
                        'container'            => 'nav',
                        'container_aria_label' => __( 'Legal Navigation', 'itk-commerce' ),
                        'menu_class'           => 'itk-footer-legal',
                        'depth'                => 1,
                        'fallback_cb'          => false,
                    )
                );
                ?>
            <?php endif; ?>
        </div>
    </div>
    <?php do_action( 'itk_commerce_after_footer' ); ?>
</footer>
