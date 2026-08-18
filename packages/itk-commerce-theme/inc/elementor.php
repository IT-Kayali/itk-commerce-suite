<?php
/**
 * Elementor Theme Builder compatibility contracts.
 *
 * Elementor remains optional. When present, Header/Footer/Single/Archive and
 * explicit IT-Kayali extension locations can be fulfilled by Theme Builder;
 * every location keeps a Theme/WooCommerce fallback when no Elementor template
 * matches the current request.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_action( 'elementor/theme/register_locations', __NAMESPACE__ . '\register_elementor_theme_locations' );

/**
 * Return Elementor Theme Builder locations supported by default.
 *
 * @return string[]
 */
function elementor_theme_locations() {
    $locations = array(
        'header',
        'footer',
        'single',
        'archive',
        'itk-before-header',
        'itk-after-header',
        'itk-before-content',
        'itk-after-content',
        'itk-before-footer',
    );

    /**
     * Filter Elementor Theme Builder locations registered by the Theme.
     *
     * @param string[] $locations Location IDs.
     */
    $filtered = apply_filters( 'itk_commerce_elementor_theme_locations', $locations );

    if ( ! is_array( $filtered ) ) {
        return $locations;
    }

    return array_values( array_unique( array_filter( array_map( 'sanitize_key', $filtered ) ) ) );
}

/**
 * Register supported Theme Builder locations through Elementor's public API.
 *
 * @param object $manager Elementor Theme Manager.
 * @return void
 */
function register_elementor_theme_locations( $manager ) {
    if ( ! is_object( $manager ) || ! method_exists( $manager, 'register_location' ) ) {
        return;
    }

    foreach ( elementor_theme_locations() as $location ) {
        $manager->register_location( $location );
    }
}

/**
 * Let Elementor render a registered location and fall back safely when the
 * plugin/Theme Builder is unavailable or no template matches.
 *
 * @param string $location Location ID.
 * @return bool True when Elementor rendered the location.
 */
function maybe_render_elementor_location( $location ) {
    $location = sanitize_key( $location );

    if ( ! in_array( $location, elementor_theme_locations(), true ) || ! function_exists( 'elementor_theme_do_location' ) ) {
        return false;
    }

    /**
     * Filter whether Elementor may replace this Theme location on the current
     * request.
     *
     * @param bool   $enabled  Default true.
     * @param string $location Location ID.
     */
    if ( ! apply_filters( 'itk_commerce_elementor_location_enabled', true, $location ) ) {
        return false;
    }

    return (bool) elementor_theme_do_location( $location );
}

/**
 * Give optional modules first control over Header/Footer rendering. A null
 * result means no module selected an explicit source, so legacy Elementor Theme
 * Builder behavior is preserved. False means render the Theme model; true means
 * the module already rendered the area or intentionally disabled it.
 *
 * @param string $location Header/footer location.
 * @return bool True when no Theme model should be rendered.
 */
function maybe_render_layout_override( $location ) {
    $location = sanitize_key( $location );

    /**
     * Filter the rendering source for a Theme layout area.
     *
     * @param bool|null $handled  Null when no explicit source owns the area.
     * @param string    $location Layout location.
     */
    $handled = apply_filters( 'itk_commerce_theme_layout_override', null, $location );

    if ( null !== $handled ) {
        return (bool) $handled;
    }

    return maybe_render_elementor_location( $location );
}
