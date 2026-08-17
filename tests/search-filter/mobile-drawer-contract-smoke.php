<?php
/**
 * Dependency-light contract smoke test for the progressive mobile filter drawer.
 */

$root      = dirname( __DIR__, 2 ) . '/packages/itk-commerce-search-filter/';
$bootstrap = file_get_contents( $root . 'itk-commerce-search-filter.php' );
$module    = file_get_contents( $root . 'src/SearchFilterModule.php' );
$service   = file_get_contents( $root . 'src/MobileFilterDrawer.php' );
$script    = file_get_contents( $root . 'assets/js/filter-drawer.js' );
$styles    = file_get_contents( $root . 'assets/css/filter-drawer.css' );

function itk_sf_drawer_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Search/Filter mobile drawer contract failure: {$message}\n" );
        exit( 1 );
    }
}

itk_sf_drawer_assert( false !== strpos( $bootstrap, 'src/MobileFilterDrawer.php' ), 'Plugin bootstrap must load the mobile drawer service.' );
itk_sf_drawer_assert( false !== strpos( $module, 'new MobileFilterDrawer()' ), 'Search Filter module must instantiate the mobile drawer.' );
itk_sf_drawer_assert( false !== strpos( $service, 'wp_localize_script(' ), 'Drawer labels and breakpoint must remain localization/config driven.' );
itk_sf_drawer_assert( false !== strpos( $service, "'breakpoint' => 760" ), 'Mobile drawer breakpoint must be explicit and synchronized with the CSS contract.' );
itk_sf_drawer_assert( false !== strpos( $script, "window.matchMedia('(max-width: ' + breakpoint + 'px)')" ), 'Drawer activation must use a bounded responsive media query.' );
itk_sf_drawer_assert( false !== strpos( $script, "panel.setAttribute('role', 'dialog')" ), 'Open mobile drawer must expose dialog semantics.' );
itk_sf_drawer_assert( false !== strpos( $script, "panel.setAttribute('aria-modal', 'true')" ), 'Open mobile drawer must expose aria-modal.' );
itk_sf_drawer_assert( false !== strpos( $script, "event.key === 'Escape'" ), 'Escape must close the drawer.' );
itk_sf_drawer_assert( false !== strpos( $script, "event.key !== 'Tab'" ), 'Drawer must implement keyboard focus trapping.' );
itk_sf_drawer_assert( false !== strpos( $script, "document.body.classList.add('itk-filter-drawer-open')" ), 'Open drawer must lock background scrolling.' );
itk_sf_drawer_assert( false !== strpos( $script, "document.addEventListener('itk:catalog-updated', enhanceAll)" ), 'Async catalog replacements must be re-enhanced automatically.' );
itk_sf_drawer_assert( false !== strpos( $script, "panel.addEventListener('submit'" ), 'Submitting filters must release the drawer before async toolbar replacement.' );
itk_sf_drawer_assert( false !== strpos( $styles, 'inset-inline-end: 0' ), 'Drawer positioning must use logical CSS properties.' );
itk_sf_drawer_assert( false !== strpos( $styles, 'html[dir="rtl"]' ), 'RTL drawer motion must be explicitly covered.' );
itk_sf_drawer_assert( false !== strpos( $styles, '@media (prefers-reduced-motion: reduce)' ), 'Drawer animation must honor reduced motion.' );

echo "Search/Filter mobile drawer contract smoke test passed.\n";
