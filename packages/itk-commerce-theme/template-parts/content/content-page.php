<?php
/**
 * Page content template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'itk-page' ); ?>>
    <?php if ( ! is_front_page() ) : ?>
        <header class="itk-page-header">
            <h1 class="itk-page-title"><?php the_title(); ?></h1>
        </header>
    <?php endif; ?>
    <div class="itk-entry-content">
        <?php the_content(); ?>
        <?php wp_link_pages(); ?>
    </div>
</article>
