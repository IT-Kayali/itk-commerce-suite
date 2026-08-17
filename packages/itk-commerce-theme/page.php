<?php
/**
 * Standard page template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="itk-site-main">
    <div class="itk-container">
        <?php while ( have_posts() ) : ?>
            <?php
            the_post();
            get_template_part( 'template-parts/content/content', 'page' );
            ?>
        <?php endwhile; ?>
    </div>
</main>
<?php get_footer(); ?>
