<?php
/**
 * Dependency-light contract smoke test for WooCommerce Store API live search.
 */

$root      = dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/';
$bootstrap = file_get_contents( $root . 'itk-commerce-search-filter.php' );
$module    = file_get_contents( $root . 'src/SearchFilterModule.php' );
$service   = file_get_contents( $root . 'src/LiveProductSearch.php' );
$script    = file_get_contents( $root . 'assets/js/live-search.js' );
$styles    = file_get_contents( $root . 'assets/css/live-search.css' );

function itk_sf_live_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Search/Filter live-search contract failure: {$message}\n" );
        exit( 1 );
    }
}

itk_sf_live_assert( false !== strpos( $bootstrap, 'src/LiveProductSearch.php' ), 'Plugin bootstrap must load the live-search service.' );
itk_sf_live_assert( false !== strpos( $module, 'new LiveProductSearch()' ), 'Search Filter module must instantiate live search.' );
itk_sf_live_assert( false !== strpos( $service, "add_action( 'itk_commerce_catalog_toolbar_before'" ), 'Live search must attach through the public catalog-toolbar contract.' );
itk_sf_live_assert( false !== strpos( $service, "rest_url( 'wc/store/v1/products' )" ), 'Product suggestions must use WooCommerce Store API.' );
itk_sf_live_assert( false !== strpos( $service, "rest_url( 'wc/store/v1/products/categories' )" ), 'Category suggestions must use WooCommerce Store API.' );
itk_sf_live_assert( false !== strpos( $service, 'method="get"' ) && false !== strpos( $service, 'name="post_type" value="product"' ), 'The live-search form must retain a normal product GET-search fallback.' );
itk_sf_live_assert( false !== strpos( $service, 'role="combobox"' ) && false !== strpos( $service, 'aria-autocomplete="list"' ), 'Search input must expose combobox semantics.' );
itk_sf_live_assert( false !== strpos( $service, "'sku_matching'" ) && false !== strpos( $service, "'show_categories'" ), 'SKU/category scopes must be independently configurable.' );

itk_sf_live_assert( false !== strpos( $script, "credentials: 'same-origin'" ), 'Store API requests must remain same-origin credentialed requests.' );
itk_sf_live_assert( false !== strpos( $script, 'new AbortController()' ), 'Stale live-search requests must be cancellable.' );
itk_sf_live_assert( false !== strpos( $script, 'Promise.allSettled' ), 'Name/SKU/category scopes must degrade independently when one public endpoint fails.' );
itk_sf_live_assert( false !== strpos( $script, "{ search: normalized" ), 'Product-name scope must use the Store API search parameter.' );
itk_sf_live_assert( false !== strpos( $script, "{ sku: normalized" ), 'Optional SKU scope must use the Store API SKU parameter.' );
itk_sf_live_assert( false !== strpos( $script, "categoriesEndpoint" ) && false !== strpos( $script, "{ search: normalized, per_page: categoryLimit" ), 'Category scope must use the Store API category search endpoint.' );
itk_sf_live_assert( false !== strpos( $script, "event.key === 'ArrowDown'" ) && false !== strpos( $script, "event.key === 'ArrowUp'" ), 'Keyboard result navigation must support arrow keys.' );
itk_sf_live_assert( false !== strpos( $script, "event.key === 'Escape'" ) && false !== strpos( $script, 'aria-activedescendant' ), 'Combobox Escape and active-descendant behavior must be implemented.' );
itk_sf_live_assert( false !== strpos( $script, 'cache = new Map()' ), 'Repeated terms must use a bounded client-side suggestion cache.' );
itk_sf_live_assert( false !== strpos( $script, "document.addEventListener('itk:catalog-updated', enhanceAll)" ), 'Live search must re-enhance after async catalog toolbar replacement.' );
itk_sf_live_assert( false !== strpos( $script, 'textContent = product.name' ) && false === strpos( $script, 'product.name +'), 'Remote product names must render as text rather than executable HTML.' );
itk_sf_live_assert( false === strpos( $script, 'admin-ajax.php' ) && false === strpos( $service, 'register_rest_route' ), 'Live search must not duplicate WooCommerce Store API with a custom product endpoint.' );
itk_sf_live_assert( false !== strpos( $styles, '.itk-live-search__panel[hidden]' ) && false !== strpos( $styles, '@media (max-width: 480px)' ), 'Live results must have hidden-state and mobile responsive styling.' );

echo "Search/Filter live search contract smoke test passed.\n";
