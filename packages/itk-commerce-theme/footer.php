<?php
/**
 * Site footer.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

ITK\Commerce\Theme\render_layout( 'footer', 'classic' );
ITK\Commerce\Theme\mobile_bottom_navigation();
wp_footer();
?>
</body>
</html>
