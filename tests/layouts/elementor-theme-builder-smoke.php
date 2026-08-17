<?php
/**
 * Dependency-light smoke test for Elementor Theme Builder compatibility.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function add_action() {}
function apply_filters( $tag, $value ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-theme/inc/elementor.php';

function itk_elementor_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

$locations = \ITK\Commerce\Theme\elementor_theme_locations();
itk_elementor_assert( array( 'header', 'footer' ) === $locations, 'Header/Footer are the safe default Elementor locations.' );

final class ITK_Elementor_Location_Manager {
    public $locations = array();
    public function register_location( $location ) { $this->locations[] = $location; }
}

$manager = new ITK_Elementor_Location_Manager();
\ITK\Commerce\Theme\register_elementor_theme_locations( $manager );
itk_elementor_assert( array( 'header', 'footer' ) === $manager->locations, 'Elementor manager receives Header/Footer registrations.' );
itk_elementor_assert( false === \ITK\Commerce\Theme\maybe_render_elementor_location( 'header' ), 'Theme fallback remains active when Elementor renderer is unavailable.' );
itk_elementor_assert( false === \ITK\Commerce\Theme\maybe_render_elementor_location( 'archive' ), 'Unregistered locations cannot replace Commerce output by default.' );

echo "Elementor Theme Builder smoke test passed.\n";
