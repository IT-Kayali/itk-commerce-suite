<?php
/**
 * Search results template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="itk-site-main">
    <div class="itk-container">
        <header class="itk-archive-header">
            <h1 class="itk-page-title">
                <?php printf( esc_html__( 'Search results for: %s', 'itk-commerce' ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?>
            </h1>
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
