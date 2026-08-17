<?php
/**
 * Dependency-light contract test for versioned Search & Filter term caching.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['itk_options']   = array();
$GLOBALS['itk_cache']     = array();
$GLOBALS['itk_transient'] = array();
$GLOBALS['itk_actions']   = array();

function add_filter() {}
function add_action() {}
function apply_filters( $hook, $value ) { return $value; }
function do_action( $hook, ...$args ) { $GLOBALS['itk_actions'][] = array( $hook, $args ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['itk_options'] ) ? $GLOBALS['itk_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['itk_options'][ $key ] = $value; return true; }
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    unset( $force );
    $full = $group . ':' . $key;
    $found = array_key_exists( $full, $GLOBALS['itk_cache'] );
    return $found ? $GLOBALS['itk_cache'][ $full ] : false;
}
function wp_cache_set( $key, $value, $group = '', $expire = 0 ) { unset( $expire ); $GLOBALS['itk_cache'][ $group . ':' . $key ] = $value; return true; }
function get_transient( $key ) { return array_key_exists( $key, $GLOBALS['itk_transient'] ) ? $GLOBALS['itk_transient'][ $key ] : false; }
function set_transient( $key, $value, $ttl ) { unset( $ttl ); $GLOBALS['itk_transient'][ $key ] = $value; return true; }
function determine_locale() { return 'de_DE'; }
function get_locale() { return 'de_DE'; }
function is_admin() { return false; }
function is_shop() { return true; }
function is_product_taxonomy() { return false; }
function wp_is_post_revision() { return false; }
function wp_is_post_autosave() { return false; }
function get_post_type( $id ) { return 99 === (int) $id ? 'product' : 'post'; }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/src/CatalogCache.php';

function itk_sf_cache_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Search/Filter cache contract failure: {$message}\n" );
        exit( 1 );
    }
}

$definitions = array(
    array(
        'id'       => 'category',
        'type'     => 'taxonomy',
        'taxonomy' => 'product_cat',
        'enabled'  => true,
    ),
    array(
        'id'       => 'brand-disabled',
        'type'     => 'taxonomy',
        'taxonomy' => 'pa_brand',
        'enabled'  => false,
    ),
);

$cache = new \ITK\Commerce\SearchFilter\CatalogCache( $definitions );
itk_sf_cache_assert( 1 === $cache->generation(), 'Initial cache generation must default to 1.' );

$query = new stdClass();
$query->query_vars = array(
    'taxonomy'   => array( 'product_cat' ),
    'hide_empty' => true,
    'number'     => 100,
    'orderby'    => 'name',
    'order'      => 'ASC',
    'fields'     => 'all',
);

$terms = array(
    (object) array( 'term_id' => 10, 'slug' => 'fragrance', 'name' => 'Fragrance', 'count' => 12 ),
    (object) array( 'term_id' => 11, 'slug' => 'gifts', 'name' => 'Gifts', 'count' => 8 ),
);

$returned = $cache->store_term_query( $terms, array( 'product_cat' ), array(), $query );
itk_sf_cache_assert( $returned === $terms, 'Caching must not mutate the normal get_terms result.' );

$hit = $cache->pre_term_query( null, $query );
itk_sf_cache_assert( is_array( $hit ) && 2 === count( $hit ) && 'fragrance' === $hit[0]->slug, 'Canonical filter term query must hit the cross-request cache.' );

$unrelated = clone $query;
$unrelated->query_vars['number'] = 25;
itk_sf_cache_assert( null === $cache->pre_term_query( null, $unrelated ), 'Different term-query shapes must never be short-circuited.' );

$disabled_taxonomy = clone $query;
$disabled_taxonomy->query_vars['taxonomy'] = array( 'pa_brand' );
itk_sf_cache_assert( null === $cache->pre_term_query( null, $disabled_taxonomy ), 'Disabled/unconfigured taxonomies must never be intercepted.' );

$cache->invalidate();
itk_sf_cache_assert( 2 === $cache->generation(), 'Invalidation must bump the versioned generation.' );
itk_sf_cache_assert( null === $cache->pre_term_query( null, $query ), 'A generation bump must make previous cached term payloads unreachable.' );

$cache->invalidate();
itk_sf_cache_assert( 2 === $cache->generation(), 'Multiple invalidations in one request must collapse to one option write.' );

$cache2 = new \ITK\Commerce\SearchFilter\CatalogCache( $definitions );
$cache2->invalidate_for_object_terms( 99, array(), array(), 'product_cat', false, array() );
itk_sf_cache_assert( 3 === $cache2->generation(), 'Product taxonomy assignment changes must invalidate the next request generation.' );

$cache3 = new \ITK\Commerce\SearchFilter\CatalogCache( $definitions );
$cache3->invalidate_for_term_change( 10, 10, 'product_cat' );
itk_sf_cache_assert( 4 === $cache3->generation(), 'Product taxonomy edits must invalidate cached filter options.' );

$events = array_filter( $GLOBALS['itk_actions'], static function ( $event ) { return 'itk_commerce_search_filter_cache_invalidated' === $event[0]; } );
itk_sf_cache_assert( count( $events ) >= 3, 'Cache invalidation must expose a public integration event.' );

echo "Search/Filter cache contract smoke test passed.\n";
