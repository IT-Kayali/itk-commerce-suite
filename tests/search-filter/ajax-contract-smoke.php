<?php
/**
 * Dependency-light contract smoke test for progressive catalog Fetch/History.
 */

$root      = dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/';
$bootstrap = file_get_contents( $root . 'itk-commerce-search-filter.php' );
$module    = file_get_contents( $root . 'src/SearchFilterModule.php' );
$service   = file_get_contents( $root . 'src/CatalogAsyncNavigation.php' );
$script    = file_get_contents( $root . 'assets/js/catalog-async.js' );
$styles    = file_get_contents( $root . 'assets/css/catalog-async.css' );

function itk_sf_ajax_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Search/Filter async contract failure: {$message}\n" );
        exit( 1 );
    }
}

itk_sf_ajax_assert( false !== strpos( $bootstrap, 'src/CatalogAsyncNavigation.php' ), 'Plugin bootstrap must load the async navigation service.' );
itk_sf_ajax_assert( false !== strpos( $module, 'new CatalogAsyncNavigation()' ), 'Search Filter module must instantiate async navigation.' );
itk_sf_ajax_assert( false !== strpos( $service, "add_action( 'woocommerce_before_shop_loop'" ), 'Product result boundary must use public WooCommerce before-loop hooks.' );
itk_sf_ajax_assert( false !== strpos( $service, "add_action( 'woocommerce_after_shop_loop'" ), 'Product result boundary must use public WooCommerce after-loop hooks.' );
itk_sf_ajax_assert( false !== strpos( $service, "add_action( 'woocommerce_no_products_found'" ), 'No-results responses must still expose a replaceable result boundary.' );
itk_sf_ajax_assert( false !== strpos( $service, 'data-itk-catalog-results' ), 'Async replacement boundary must have a stable module-owned selector.' );
itk_sf_ajax_assert( false !== strpos( $service, 'aria-live="polite"' ), 'Async product updates must expose a polite live region.' );
itk_sf_ajax_assert( false !== strpos( $service, 'wp_localize_script' ), 'Browser status messages must remain translation-ready.' );

itk_sf_ajax_assert( false !== strpos( $script, "credentials: 'same-origin'" ), 'Catalog Fetch must send same-origin credentials only.' );
itk_sf_ajax_assert( false !== strpos( $script, 'new AbortController()' ), 'Stale catalog requests must be cancellable.' );
itk_sf_ajax_assert( false !== strpos( $script, 'window.history.pushState' ), 'Successful filter navigation must update browser history.' );
itk_sf_ajax_assert( false !== strpos( $script, "window.addEventListener('popstate'" ), 'Back/forward navigation must restore catalog state asynchronously.' );
itk_sf_ajax_assert( false !== strpos( $script, "rawKey.match(/^(.*)\\[\\]$/" ), 'Multiple checkbox values must canonicalize to the bounded scalar URL contract.' );
itk_sf_ajax_assert( false !== strpos( $script, "rawKey.match(/^(.*)\\[(min|max)\\]$/" ), 'Progressive min/max inputs must canonicalize to the bounded price URL contract.' );
itk_sf_ajax_assert( false !== strpos( $script, "event.target.closest('.itk-filter-form')" ), 'GET filter forms must be progressively intercepted.' );
itk_sf_ajax_assert( false !== strpos( $script, '.woocommerce-pagination a' ), 'Filtered pagination links must preserve async navigation and history.' );
itk_sf_ajax_assert( false !== strpos( $script, 'window.location.assign' ), 'Any async failure must degrade to normal full-page GET navigation.' );
itk_sf_ajax_assert( false !== strpos( $script, "document.dispatchEvent(new CustomEvent('itk:catalog-updated'" ), 'Successful replacement must expose a public catalog-updated browser event.' );
itk_sf_ajax_assert( false === strpos( $script, 'admin-ajax.php' ) && false === strpos( $service, 'wp_ajax_' ), 'Read-only catalog navigation must not duplicate server query logic in a custom admin-ajax endpoint.' );
itk_sf_ajax_assert( false !== strpos( $styles, "[aria-busy='true']" ), 'Async loading state must be visibly represented without layout replacement hacks.' );

echo "Search/Filter async catalog contract smoke test passed.\n";
