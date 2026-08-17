<?php
/**
 * Empty results template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="itk-empty-state">
    <h1><?php esc_html_e( 'Nothing found', 'itk-commerce' ); ?></h1>
    <p><?php esc_html_e( 'No content matched this request. Try another search or return to the homepage.', 'itk-commerce' ); ?></p>
    <?php get_search_form(); ?>
</section>
