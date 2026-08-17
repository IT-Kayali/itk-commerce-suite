<?php
/**
 * Theme setup and widget areas.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );
add_action( 'widgets_init', __NAMESPACE__ . '\\register_widget_areas' );

/**
 * Register reusable theme capabilities and navigation locations.
 */
function setup() {
    load_theme_textdomain( 'itk-commerce', DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'customize-selective-refresh-widgets' );

    add_theme_support(
        'custom-logo',
        array(
            'height'      => 120,
            'width'       => 360,
            'flex-height' => true,
            'flex-width'  => true,
        )
    );

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
            'search-form',
        )
    );

    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    register_nav_menus(
        array(
            'primary'        => __( 'Primary Navigation', 'itk-commerce' ),
            'secondary'      => __( 'Secondary Navigation', 'itk-commerce' ),
            'mobile'         => __( 'Mobile Navigation', 'itk-commerce' ),
            'mobile-bottom'  => __( 'Mobile Bottom Navigation', 'itk-commerce' ),
            'footer-primary' => __( 'Footer Primary', 'itk-commerce' ),
            'footer-legal'   => __( 'Footer Legal', 'itk-commerce' ),
        )
    );

    add_image_size( 'itk-product-card', 720, 900, false );
}

/**
 * Register theme-owned widget areas. Modules may register additional areas.
 */
function register_widget_areas() {
    register_sidebar(
        array(
            'name'          => __( 'Shop Sidebar', 'itk-commerce' ),
            'id'            => 'itk-shop-sidebar',
            'description'   => __( 'Shown by layouts that use a WooCommerce sidebar.', 'itk-commerce' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );

    for ( $column = 1; $column <= 4; $column++ ) {
        register_sidebar(
            array(
                'name'          => sprintf( __( 'Footer Column %d', 'itk-commerce' ), $column ),
                'id'            => 'itk-footer-' . $column,
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title">',
                'after_title'   => '</h2>',
            )
        );
    }
}
