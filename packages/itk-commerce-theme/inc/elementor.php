<?php
/**
 * Elementor Theme Builder compatibility contracts.
 *
 * Header and Footer are registered as safe core locations. Single/archive
 * locations are intentionally not forced because the Commerce Suite owns
 * WooCommerce page-model contracts there; integrations may opt in through the
 * public location filter when they deliberately want Elementor to replace them.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

add_action( 'elementor/theme/register_locations', __NAMESPACE__ . '\\register_elementor_theme_locations' );

/**
 * Return Elementor Theme Builder locations supported by default.
 *
 * @return string[]
 */
function elementor_theme_locations() {
    $locations = array( 'header', 'footer' );

    /**
     * Filter Elementor Theme Builder locations registered by the Theme.
     * Optional integrations may deliberately add `single` or `archive`.
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
 * Register supported Theme Builder locations using Elementor's public manager.
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
 * Ask Elementor to render a registered location and fall back safely when the
 * plugin/Pro Theme Builder is unavailable or no template matches.
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
     * Filter whether a registered Elementor location may replace the Theme
     * fallback on the current request.
     *
     * @param bool   $enabled  Default true.
     * @param string $location Location ID.
     */
    if ( ! apply_filters( 'itk_commerce_elementor_location_enabled', true, $location ) ) {
        return false;
    }

    return (bool) elementor_theme_do_location( $location );
}
