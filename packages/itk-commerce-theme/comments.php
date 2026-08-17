<?php
/**
 * Comments template.
 *
 * @package ITK_Commerce_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
    return;
}
?>
<section id="comments" class="itk-comments">
    <?php if ( have_comments() ) : ?>
        <h2 class="itk-comments__title">
            <?php
            printf(
                esc_html( _nx( '%1$s comment', '%1$s comments', get_comments_number(), 'comments title', 'itk-commerce' ) ),
                esc_html( number_format_i18n( get_comments_number() ) )
            );
            ?>
        </h2>
        <ol class="itk-comment-list">
            <?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() ) : ?>
        <p><?php esc_html_e( 'Comments are closed.', 'itk-commerce' ); ?></p>
    <?php endif; ?>

    <?php comment_form(); ?>
</section>
