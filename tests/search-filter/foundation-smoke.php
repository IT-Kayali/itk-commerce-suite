<?php
/**
 * Dependency-light Search & Filter foundation smoke test.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function add_filter() {}
function add_action() {}
function apply_filters( $hook, $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    $value = preg_replace( '/[^a-z0-9\-_ ]+/', '', $value );
    return trim( preg_replace( '/[ _]+/', '-', $value ), '-' );
}
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) {
    if ( is_array( $value ) ) {
        return array_map( 'wp_unslash', $value );
    }
    return is_string( $value ) ? stripslashes( $value ) : $value;
}
function wc_get_product_visibility_term_ids() {
    return array(
        'outofstock' => 91,
        'rated-1'    => 101,
        'rated-2'    => 102,
        'rated-3'    => 103,
        'rated-4'    => 104,
        'rated-5'    => 105,
    );
}
function wc_get_product_ids_on_sale() { return array( 2, 3, 3, 4 ); }

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/src/FilterSchema.php';
require dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/src/UrlState.php';
require dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/src/WooQueryAdapter.php';

function itk_sf_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Search/Filter foundation failure: {$message}\n" );
        exit( 1 );
    }
}

$schema   = new \ITK\Commerce\SearchFilter\FilterSchema();
$defaults = $schema->normalize( $schema->defaults() );

itk_sf_assert( 5 === count( $defaults ), 'Neutral defaults should expose five filter groups.' );
itk_sf_assert( 'category' === $defaults[0]['id'], 'Category remains the first default filter.' );
itk_sf_assert( 'price' === $defaults[1]['id'], 'Price follows Category by stable order.' );

$custom = $schema->normalize(
    array(
        array(
            'id'        => 'brand',
            'type'      => 'taxonomy',
            'taxonomy'  => 'pa_brand',
            'query_key' => 'filter_brand',
            'label'     => 'Brand',
            'order'     => 20,
        ),
        array(
            'id'        => 'unsafe',
            'type'      => 'taxonomy',
            'taxonomy'  => 'post_tag',
            'query_key' => 'filter_unsafe',
            'order'     => 5,
        ),
        array(
            'id'        => 'brand',
            'type'      => 'sale',
            'query_key' => 'filter_sale_duplicate_id',
            'order'     => 30,
        ),
        array(
            'id'        => 'sale-copy',
            'type'      => 'sale',
            'query_key' => 'filter_brand',
            'order'     => 40,
        ),
        array(
            'id'        => 'rating',
            'type'      => 'rating',
            'query_key' => 'filter_rating',
            'order'     => 10,
        ),
        array(
            'id'        => 'stars-copy',
            'type'      => 'rating',
            'query_key' => 'filter_stars_copy',
            'order'     => 50,
        ),
    )
);

itk_sf_assert( 2 === count( $custom ), 'Unsafe, duplicate and duplicate singleton-type definitions should be discarded.' );
itk_sf_assert( 'rating' === $custom[0]['id'], 'Definitions should be sorted by bounded order.' );
itk_sf_assert( 'brand' === $custom[1]['id'] && 'pa_brand' === $custom[1]['taxonomy'], 'Product attribute taxonomy should be accepted.' );

$url_state = new \ITK\Commerce\SearchFilter\UrlState( $defaults );
$state     = $url_state->parse(
    array(
        'filter_category' => 'Fragrance,gifts,fragrance',
        'filter_price'    => '150-20',
        'filter_stock'    => 'in-stock',
        'filter_sale'     => 'yes',
        'filter_rating'   => '4',
        'arbitrary_meta'  => 'must-not-enter-state',
    )
);

itk_sf_assert( array( 'fragrance', 'gifts' ) === $state['category'], 'Taxonomy values should normalize and deduplicate.' );
itk_sf_assert( 20.0 === $state['price']['min'] && 150.0 === $state['price']['max'], 'Reversed price bounds should normalize safely.' );
itk_sf_assert( 'in-stock' === $state['stock'], 'Stock allow-list should be preserved.' );
itk_sf_assert( true === $state['sale'], 'Sale truthy value should normalize to boolean true.' );
itk_sf_assert( 4 === $state['rating'], 'Rating should normalize to a bounded integer.' );
itk_sf_assert( ! isset( $state['arbitrary_meta'] ), 'Unknown request keys must never enter filter state.' );
itk_sf_assert( 5 === $url_state->active_count( $state ), 'Active count should count filter groups.' );

$args = $url_state->serialize( $state );
itk_sf_assert( 'fragrance,gifts' === $args['filter_category'], 'Taxonomy state should serialize as stable comma-separated slugs.' );
itk_sf_assert( '20-150' === $args['filter_price'], 'Price state should serialize canonically.' );
itk_sf_assert( '1' === $args['filter_sale'], 'Sale state should serialize canonically.' );

final class ITKSFFakeQuery {
    private $values = array();

    public function __construct( array $values = array() ) { $this->values = $values; }
    public function get( $key ) { return isset( $this->values[ $key ] ) ? $this->values[ $key ] : null; }
    public function set( $key, $value ) { $this->values[ $key ] = $value; }
    public function value( $key ) { return isset( $this->values[ $key ] ) ? $this->values[ $key ] : null; }
}

$_GET = array(
    'filter_category' => 'fragrance,gifts',
    'filter_price'    => '20-150',
    'filter_stock'    => 'in-stock',
    'filter_sale'     => '1',
    'filter_rating'   => '4',
);
$adapter = new \ITK\Commerce\SearchFilter\WooQueryAdapter( $url_state );

$tax_query = $adapter->filter_tax_query( array(), null );
itk_sf_assert( 3 === count( $tax_query ), 'Tax query should contain category, stock and rating constraints.' );
itk_sf_assert( 'product_cat' === $tax_query[0]['taxonomy'], 'Category taxonomy constraint should use product_cat.' );
itk_sf_assert( 'NOT IN' === $tax_query[1]['operator'], 'In-stock filter should exclude WooCommerce out-of-stock visibility term.' );
itk_sf_assert( array( 104, 105 ) === $tax_query[2]['terms'], 'Rating 4 should include WooCommerce rated-4 and rated-5 visibility terms.' );

$meta_query = $adapter->filter_meta_query( array(), null );
itk_sf_assert( 1 === count( $meta_query ), 'Price should append one bounded meta-query condition.' );
itk_sf_assert( 'BETWEEN' === $meta_query[0]['compare'] && array( 20.0, 150.0 ) === $meta_query[0]['value'], 'Price query should use normalized range.' );

$query = new ITKSFFakeQuery( array( 'post__in' => array( 1, 2, 3 ) ) );
$adapter->filter_product_query( $query );
itk_sf_assert( array( 2, 3 ) === $query->value( 'post__in' ), 'Sale filter should intersect an existing post__in constraint.' );

$custom_scalar_definitions = $schema->normalize(
    array(
        array( 'id' => 'cost', 'type' => 'price', 'query_key' => 'catalog_cost', 'label' => 'Cost', 'order' => 10 ),
        array( 'id' => 'availability', 'type' => 'stock', 'query_key' => 'availability', 'label' => 'Availability', 'order' => 20 ),
        array( 'id' => 'promotion', 'type' => 'sale', 'query_key' => 'promotion', 'label' => 'Promotion', 'order' => 30 ),
        array( 'id' => 'stars', 'type' => 'rating', 'query_key' => 'stars', 'label' => 'Stars', 'order' => 40 ),
    )
);
$custom_scalar_state = new \ITK\Commerce\SearchFilter\UrlState( $custom_scalar_definitions );
$_GET = array(
    'catalog_cost' => '10-30',
    'availability' => 'out-of-stock',
    'promotion'    => '1',
    'stars'        => '5',
);
$custom_adapter = new \ITK\Commerce\SearchFilter\WooQueryAdapter( $custom_scalar_state );

$custom_tax_query = $custom_adapter->filter_tax_query( array(), null );
itk_sf_assert( 'IN' === $custom_tax_query[0]['operator'], 'Custom-ID stock filter should still resolve by schema type.' );
itk_sf_assert( array( 105 ) === $custom_tax_query[1]['terms'], 'Custom-ID rating filter should still resolve by schema type.' );

$custom_meta_query = $custom_adapter->filter_meta_query( array(), null );
itk_sf_assert( array( 10.0, 30.0 ) === $custom_meta_query[0]['value'], 'Custom-ID price filter should still resolve by schema type.' );

$custom_query = new ITKSFFakeQuery();
$custom_adapter->filter_product_query( $custom_query );
itk_sf_assert( array( 2, 3, 4 ) === $custom_query->value( 'post__in' ), 'Custom-ID sale filter should still resolve by schema type.' );

$_GET = array( 'filter_stock' => 'invalid', 'filter_rating' => '99', 'filter_price' => 'not-a-range' );
$invalid_adapter = new \ITK\Commerce\SearchFilter\WooQueryAdapter( $url_state );
itk_sf_assert( array() === $invalid_adapter->current_state(), 'Invalid bounded URL values should be ignored.' );

echo "Search/Filter foundation smoke test passed.\n";
