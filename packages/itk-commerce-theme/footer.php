<?php
/**
 * Site footer.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! ITK\Commerce\Theme\maybe_render_elementor_location( 'footer' ) ) {
    ITK\Commerce\Theme\render_layout( 'footer', 'classic' );
}
ITK\Commerce\Theme\mobile_bottom_navigation();
wp_footer();
?>
</body>
</html>
