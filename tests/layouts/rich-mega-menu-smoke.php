<?php
/**
 * Dependency-light smoke test for rich mega-menu block normalization.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

function sanitize_key( $value ) {
    return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) );
}

function sanitize_text_field( $value ) {
    return trim( strip_tags( (string) $value ) );
}

function sanitize_textarea_field( $value ) {
    return trim( strip_tags( (string) $value ) );
}

function esc_url_raw( $value ) {
    return filter_var( (string) $value, FILTER_SANITIZE_URL );
}

function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    return trim( preg_replace( '/[^a-z0-9]+/', '-', $value ), '-' );
}

function absint( $value ) {
    return abs( (int) $value );
}

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-layouts/src/MegaMenuConfig.php';

function itk_rich_mega_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

$config = new \ITK\Commerce\Layouts\MegaMenuConfig();
$method = new ReflectionMethod( $config, 'normalize_blocks' );
$method->setAccessible( true );

$blocks = $method->invoke(
    $config,
    array(
        array(
            'type'        => 'categories',
            'title'       => '<b>Collections</b>',
            'span'        => 99,
            'slugs'       => 'Men, Women, Gift Sets',
            'limit'       => 99,
            'show_images' => true,
        ),
        array(
            'type'   => 'products',
            'source' => 'not-valid',
            'value'  => 'summer',
            'limit'  => 99,
        ),
        array(
            'type'        => 'elementor',
            'template_id' => '-42',
        ),
        array(
            'type' => 'php',
        ),
    )
);

itk_rich_mega_assert( 3 === count( $blocks ), 'Unsupported executable block types are rejected.' );
itk_rich_mega_assert( 'categories' === $blocks[0]['type'], 'Category block type is retained.' );
itk_rich_mega_assert( 'Collections' === $blocks[0]['title'], 'Category title is sanitized.' );
itk_rich_mega_assert( 6 === $blocks[0]['span'], 'Column span is bounded to six.' );
itk_rich_mega_assert( array( 'men', 'women', 'gift-sets' ) === $blocks[0]['slugs'], 'Category slugs are normalized.' );
itk_rich_mega_assert( 12 === $blocks[0]['limit'], 'Category limit is bounded to twelve.' );
itk_rich_mega_assert( true === $blocks[0]['show_images'], 'Category image flag is preserved.' );
itk_rich_mega_assert( 'latest' === $blocks[1]['source'], 'Invalid product source falls back safely.' );
itk_rich_mega_assert( 8 === $blocks[1]['limit'], 'Product limit is bounded to eight.' );
itk_rich_mega_assert( 42 === $blocks[2]['template_id'], 'Elementor template ID is normalized to a positive integer.' );

echo "Rich mega-menu normalization smoke test passed.\n";
