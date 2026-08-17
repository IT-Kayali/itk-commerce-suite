<?php
/**
 * Multi-column Footer model and rich content variants.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

$layout_model = isset( $args['itk_layout_model'] ) ? sanitize_key( $args['itk_layout_model'] ) : 'columns';
?>
<footer class="itk-site-footer itk-site-footer--columns itk-site-footer--<?php echo esc_attr( $layout_model ); ?>" data-itk-layout-model="<?php echo esc_attr( $layout_model ); ?>">
    <?php do_action( 'itk_commerce_before_footer' ); ?>
    <div class="itk-container">
        <div class="itk-footer-columns__intro">
            <div>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="itk-footer-brand__name">
                    <?php bloginfo( 'name' ); ?>
                </a>
                <?php if ( get_bloginfo( 'description' ) ) : ?>
                    <p><?php bloginfo( 'description' ); ?></p>
                <?php endif; ?>
            </div>
            <?php do_action( 'itk_commerce_footer_columns_intro', $layout_model ); ?>
        </div>

        <?php if ( 'newsletter' === $layout_model ) : ?>
            <div class="itk-footer-feature itk-footer-feature--newsletter">
                <?php do_action( 'itk_commerce_footer_newsletter' ); ?>
            </div>
        <?php elseif ( 'branches' === $layout_model ) : ?>
            <div class="itk-footer-feature itk-footer-feature--branches">
                <?php do_action( 'itk_commerce_footer_branches' ); ?>
            </div>
        <?php endif; ?>

        <div class="itk-footer-columns__grid">
            <?php for ( $column = 1; $column <= 4; $column++ ) : ?>
                <div class="itk-footer-column itk-footer-column--<?php echo esc_attr( (string) $column ); ?>">
                    <?php if ( is_active_sidebar( 'itk-footer-' . $column ) ) : ?>
                        <?php dynamic_sidebar( 'itk-footer-' . $column ); ?>
                    <?php endif; ?>
                </div>
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
