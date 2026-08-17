<?php
/**
 * Default shop sidebar.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'itk-shop-sidebar' ) ) {
    return;
}
?>
<aside class="itk-shop-sidebar" aria-label="<?php esc_attr_e( 'Shop filters and sidebar', 'itk-commerce' ); ?>">
    <?php dynamic_sidebar( 'itk-shop-sidebar' ); ?>
</aside>
