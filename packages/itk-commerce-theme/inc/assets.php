<?php
/**
 * Frontend assets.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );

/**
 * Return a cache-busting version for a theme asset.
 *
 * @param string $relative_path Relative path inside the theme.
 * @return string
 */
function asset_version( $relative_path ) {
    $path = DIR . '/' . ltrim( $relative_path, '/' );

    if ( is_readable( $path ) ) {
        return (string) filemtime( $path );
    }

    return VERSION;
}

/**
 * Load the small, layered theme asset set.
 */
function enqueue_assets() {
    wp_enqueue_style(
        'itk-commerce-theme',
        get_stylesheet_uri(),
        array(),
        asset_version( 'style.css' )
    );

    $styles = array(
        'base'                  => array(),
        'layout'                => array( 'itk-commerce-base' ),
        'components'            => array( 'itk-commerce-layout' ),
        'layout-models'         => array( 'itk-commerce-components' ),
        'commerce'              => array( 'itk-commerce-layout-models' ),
        'commerce-models'       => array( 'itk-commerce-commerce' ),
        'responsive'            => array( 'itk-commerce-commerce-models' ),
        'commerce-grid-contract'=> array( 'itk-commerce-responsive' ),
        'commerce-block-models' => array( 'itk-commerce-commerce-grid-contract' ),
    );

    foreach ( $styles as $name => $dependencies ) {
        $handle = 'itk-commerce-' . $name;
        $path   = 'assets/css/' . $name . '.css';

        wp_enqueue_style(
            $handle,
            get_template_directory_uri() . '/' . $path,
            $dependencies,
            asset_version( $path )
        );
    }

    wp_enqueue_script(
        'itk-commerce-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        array(),
        asset_version( 'assets/js/navigation.js' ),
        true
    );
}
