<?php
/**
 * IT-Kayali Commerce theme bootstrap.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0-dev';

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );

/**
 * Register the theme capabilities that belong to the reusable theme layer.
 */
function setup() {
    load_theme_textdomain( 'itk-commerce', get_template_directory() . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'woocommerce' );

    add_theme_support(
        'html5',
        array(
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
            'navigation-widgets',
        )
    );
}

/**
 * Load only the base stylesheet. Feature/module assets are registered by their own packages.
 */
function enqueue_assets() {
    wp_enqueue_style(
        'itk-commerce-theme',
        get_stylesheet_uri(),
        array(),
        VERSION
    );
}
