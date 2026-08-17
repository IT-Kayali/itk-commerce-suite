<?php
/**
 * Dependency-light smoke coverage for progressive filter UI contracts.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function apply_filters( $hook, $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return trim( preg_replace( '/[^a-z0-9]+/', '-', $value ), '-' );
}
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return $value; }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/src/UrlState.php';

function itk_sf_ui_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Search/Filter UI contract failure: {$message}\n" );
        exit( 1 );
    }
}

$definitions = array(
    array(
        'id'        => 'cost',
        'type'      => 'price',
        'query_key' => 'catalog_cost',
        'enabled'   => true,
        'multiple'  => false,
    ),
);
$state_service = new \ITK\Commerce\SearchFilter\UrlState( $definitions );
$state = $state_service->parse(
    array(
        'catalog_cost' => array(
            'min' => '19.95',
            'max' => '125',
        ),
    )
);

itk_sf_ui_assert( 19.95 === $state['cost']['min'], 'Progressive GET price minimum should parse from array input.' );
itk_sf_ui_assert( 125.0 === $state['cost']['max'], 'Progressive GET price maximum should parse from array input.' );
itk_sf_ui_assert( '19.95-125' === $state_service->serialize( $state )['catalog_cost'], 'Progressive price input should serialize back to canonical scalar URL state.' );

$root     = dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/';
$renderer = file_get_contents( $root . 'src/FilterRenderer.php' );
$builder  = file_get_contents( $root . 'src/Admin/FilterBuilderPage.php' );
$module   = file_get_contents( $root . 'src/SearchFilterModule.php' );

itk_sf_ui_assert( false !== strpos( $renderer, "add_action( 'itk_commerce_catalog_toolbar'" ), 'Filter UI must attach through the Phase 3 public catalog toolbar contract.' );
itk_sf_ui_assert( false !== strpos( $renderer, 'method="get"' ), 'Catalog filtering must retain a normal GET fallback.' );
itk_sf_ui_assert( false !== strpos( $renderer, 'render_active_chips' ), 'Active-filter chip rendering must remain available.' );
itk_sf_ui_assert( false !== strpos( $renderer, "'chips' === \$definition['display']" ), 'Configured chip display mode must be rendered.' );
itk_sf_ui_assert( false !== strpos( $builder, "check_admin_referer( 'itk_commerce_save_search_filters'" ), 'Builder save must remain nonce protected.' );
itk_sf_ui_assert( false !== strpos( $builder, '$this->schema->normalize( $raw )' ), 'Builder saves must pass through the bounded filter schema.' );
itk_sf_ui_assert( false !== strpos( $module, 'new Admin\\FilterBuilderPage' ), 'Search Filter module must register the profile-driven builder.' );
itk_sf_ui_assert( false !== strpos( $module, "array_key_exists( 'definitions', \$filters )" ), 'Explicitly empty customer filter definitions must remain distinguishable from never-configured defaults.' );

echo "Search/Filter UI contract smoke test passed.\n";
