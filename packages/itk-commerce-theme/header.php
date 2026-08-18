<?php
/**
 * Site header.
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
<a class="itk-skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'itk-commerce' ); ?></a>
<?php
/** Controlled extension location before the rendered site header. */
ITK\Commerce\Theme\maybe_render_elementor_location( 'itk-before-header' );
do_action( 'itk_commerce_before_header' );

if ( ! ITK\Commerce\Theme\maybe_render_layout_override( 'header' ) ) {
    ITK\Commerce\Theme\render_layout( 'header', 'classic' );
}

/** Controlled extension location after the rendered site header. */
ITK\Commerce\Theme\maybe_render_elementor_location( 'itk-after-header' );
do_action( 'itk_commerce_after_header' );

/** Controlled extension location before the page's primary content. */
ITK\Commerce\Theme\maybe_render_elementor_location( 'itk-before-content' );
do_action( 'itk_commerce_before_content' );
?>
