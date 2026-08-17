<?php
/**
 * WooCommerce fallback template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
get_header();

if ( function_exists( 'woocommerce_content' ) ) {
    woocommerce_content();
} else {
    ?>
    <main id="primary" class="itk-site-main">
        <div class="itk-container">
            <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
        </div>
    </main>
    <?php
}

get_footer();
