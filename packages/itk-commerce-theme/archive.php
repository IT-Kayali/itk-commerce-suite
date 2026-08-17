<?php
/**
 * Archive template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="itk-site-main">
    <div class="itk-container">
        <header class="itk-archive-header">
            <?php the_archive_title( '<h1 class="itk-page-title">', '</h1>' ); ?>
            <?php the_archive_description( '<div class="itk-archive-description">', '</div>' ); ?>
        </header>
        <?php if ( have_posts() ) : ?>
            <div class="itk-content-list">
                <?php while ( have_posts() ) : ?>
                    <?php the_post(); get_template_part( 'template-parts/content/content', get_post_type() ); ?>
                <?php endwhile; ?>
            </div>
            <?php the_posts_navigation(); ?>
        <?php else : ?>
            <?php get_template_part( 'template-parts/content/content', 'none' ); ?>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
