<?php
/**
 * Site footer.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

/** Controlled extension location after the page's primary content. */
ITK\Commerce\Theme\maybe_render_elementor_location( 'itk-after-content' );
do_action( 'itk_commerce_after_content' );

/** Controlled extension location before the rendered site footer. */
ITK\Commerce\Theme\maybe_render_elementor_location( 'itk-before-footer' );
do_action( 'itk_commerce_before_footer' );

if ( ! ITK\Commerce\Theme\maybe_render_elementor_location( 'footer' ) ) {
    ITK\Commerce\Theme\render_layout( 'footer', 'classic' );
}
ITK\Commerce\Theme\mobile_bottom_navigation();
wp_footer();
?>
</body>
</html>
