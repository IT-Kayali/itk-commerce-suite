<?php
/**
 * Single content template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();

if ( ! ITK\Commerce\Theme\maybe_render_elementor_location( 'single' ) ) :
?>
<main id="primary" class="itk-site-main">
    <div class="itk-container itk-reading-width">
        <?php while ( have_posts() ) : ?>
            <?php the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'itk-single' ); ?>>
                <header class="itk-page-header">
                    <?php ITK\Commerce\Theme\entry_meta(); ?>
                    <h1 class="itk-page-title"><?php the_title(); ?></h1>
                </header>
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="itk-single__media"><?php the_post_thumbnail( 'full' ); ?></div>
                <?php endif; ?>
                <div class="itk-entry-content"><?php the_content(); ?></div>
            </article>
            <?php the_post_navigation(); ?>
            <?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>
        <?php endwhile; ?>
    </div>
</main>
<?php
endif;
get_footer();
