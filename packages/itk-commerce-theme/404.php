<?php
/**
 * 404 template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="itk-site-main">
    <div class="itk-container itk-reading-width">
        <section class="itk-empty-state itk-empty-state--404">
            <p class="itk-empty-state__code">404</p>
            <h1><?php esc_html_e( 'Page not found', 'itk-commerce' ); ?></h1>
            <p><?php esc_html_e( 'The requested page does not exist or may have moved.', 'itk-commerce' ); ?></p>
            <?php get_search_form(); ?>
            <p><a class="itk-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to homepage', 'itk-commerce' ); ?></a></p>
        </section>
    </div>
</main>
<?php get_footer(); ?>
