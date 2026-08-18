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

$expected_locations = array(
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

$locations = \ITK\Commerce\Theme\elementor_theme_locations();
itk_elementor_assert( $expected_locations === $locations, 'Header/Footer/Single/Archive and IT-Kayali extension locations are registered by default.' );

final class ITK_Elementor_Location_Manager {
    public $locations = array();
    public function register_location( $location ) { $this->locations[] = $location; }
}

$manager = new ITK_Elementor_Location_Manager();
\ITK\Commerce\Theme\register_elementor_theme_locations( $manager );
itk_elementor_assert( $expected_locations === $manager->locations, 'Elementor manager receives all Commerce Theme Builder registrations.' );
itk_elementor_assert( false === \ITK\Commerce\Theme\maybe_render_elementor_location( 'header' ), 'Theme fallback remains active when Elementor renderer is unavailable.' );
itk_elementor_assert( false === \ITK\Commerce\Theme\maybe_render_elementor_location( 'archive' ), 'Archive fallback remains active when Elementor renderer is unavailable.' );
itk_elementor_assert( false === \ITK\Commerce\Theme\maybe_render_elementor_location( 'not-registered' ), 'Unknown locations cannot replace Theme output.' );

echo "Elementor Theme Builder smoke test passed.\n";
