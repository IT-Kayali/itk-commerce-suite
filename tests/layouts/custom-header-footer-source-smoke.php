<?php
/**
 * Dependency-free contract smoke test for manual Header/Footer sources.
 */

function itk_custom_layout_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

$root       = dirname( __DIR__, 2 );
$renderer   = file_get_contents( $root . '/packages/itk-commerce-layouts/src/CustomLayoutRenderer.php' );
$admin      = file_get_contents( $root . '/packages/itk-commerce-layouts/src/Admin/CustomHeaderFooterPage.php' );
$module     = file_get_contents( $root . '/packages/itk-commerce-layouts/src/LayoutsModule.php' );
$bootstrap  = file_get_contents( $root . '/packages/itk-commerce-layouts/itk-commerce-layouts.php' );
$theme_api  = file_get_contents( $root . '/packages/itk-commerce-theme/inc/elementor.php' );
$header     = file_get_contents( $root . '/packages/itk-commerce-theme/header.php' );
$footer     = file_get_contents( $root . '/packages/itk-commerce-theme/footer.php' );

foreach ( array( $renderer, $admin, $module, $bootstrap, $theme_api, $header, $footer ) as $content ) {
    itk_custom_layout_assert( false !== $content, 'Required source file can be read.' );
}

foreach ( array( 'theme', 'custom_html', 'elementor', 'shortcode', 'disabled' ) as $source ) {
    itk_custom_layout_assert( false !== strpos( $renderer, "'{$source}'" ), "Renderer supports {$source} source." );
    itk_custom_layout_assert( false !== strpos( $admin, 'value="' . $source . '"' ), "Admin exposes {$source} source." );
}

itk_custom_layout_assert( false !== strpos( $renderer, 'has-tablet-override' ), 'Tablet override contract exists.' );
itk_custom_layout_assert( false !== strpos( $renderer, 'has-mobile-override' ), 'Mobile override contract exists.' );
itk_custom_layout_assert( false !== strpos( $renderer, 'sanitize_html' ), 'HTML has an explicit sanitizer.' );
itk_custom_layout_assert( false !== strpos( $renderer, 'aria-*' ) && false !== strpos( $renderer, 'data-*' ), 'Custom HTML allowlist covers accessible/data attributes.' );
itk_custom_layout_assert( false !== strpos( $renderer, "get_builder_content_for_display" ), 'Elementor Saved Template rendering uses the public frontend renderer.' );
itk_custom_layout_assert( false !== strpos( $renderer, 'do_shortcode' ), 'Shortcode source rendering exists.' );
itk_custom_layout_assert( false !== strpos( $bootstrap, 'itk_commerce_normalized_profile' ), 'Raw custom layout content is restored by module-owned profile normalization.' );
itk_custom_layout_assert( false !== strpos( $module, 'itk_commerce_theme_layout_override' ), 'Layouts module owns the Theme override filter.' );
itk_custom_layout_assert( false !== strpos( $theme_api, 'maybe_render_layout_override' ), 'Theme exposes a generic layout override boundary.' );
itk_custom_layout_assert( false !== strpos( $header, "maybe_render_layout_override( 'header' )" ), 'Header honors explicit source overrides.' );
itk_custom_layout_assert( false !== strpos( $footer, "maybe_render_layout_override( 'footer' )" ), 'Footer honors explicit source overrides.' );
itk_custom_layout_assert( false !== strpos( $admin, 'Primary HTML' ) && false !== strpos( $admin, 'Custom CSS' ) && false !== strpos( $admin, 'Custom JavaScript' ), 'Admin separates HTML, CSS and JavaScript inputs.' );
itk_custom_layout_assert( false !== strpos( $admin, 'does not require Elementor Pro' ), 'Admin documents Elementor Free Saved Template support.' );

echo "Custom Header/Footer source contract smoke test passed.\n";
