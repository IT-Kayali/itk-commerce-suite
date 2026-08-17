<?php
/**
 * Fallback template for the IT-Kayali Commerce theme.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<main id="primary" class="itk-site-main">
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : ?>
            <?php the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php if ( ! is_front_page() ) : ?>
                    <h1><?php the_title(); ?></h1>
                <?php endif; ?>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>

        <?php the_posts_navigation(); ?>
    <?php else : ?>
        <p><?php esc_html_e( 'No content found.', 'itk-commerce' ); ?></p>
    <?php endif; ?>
</main>

<?php wp_footer(); ?>
</body>
</html>
