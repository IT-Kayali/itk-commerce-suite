<?php
/**
 * Default loop content card.
 *
 * @package ITK_Commerce_Theme
 */

use function ITK\Commerce\Theme\entry_meta;

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'itk-content-card' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <a class="itk-content-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
            <?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
        </a>
    <?php endif; ?>
    <div class="itk-content-card__body">
        <?php entry_meta(); ?>
        <h2 class="itk-content-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="itk-content-card__excerpt"><?php the_excerpt(); ?></div>
    </div>
</article>
