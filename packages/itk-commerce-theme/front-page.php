<?php
/**
 * Front page template.
 *
 * Keeps customer content editable and avoids hard-coded brand content.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="itk-site-main itk-front-page">
    <?php do_action( 'itk_commerce_before_front_page' ); ?>
    <div class="itk-container">
        <?php while ( have_posts() ) : ?>
            <?php
            the_post();
            get_template_part( 'template-parts/content/content', 'page' );
            ?>
        <?php endwhile; ?>
    </div>
    <?php do_action( 'itk_commerce_after_front_page' ); ?>
</main>
<?php get_footer(); ?>
