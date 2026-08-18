<?php
/**
 * Static contract smoke tests for Phase 6/7 installable modules.
 */

$root = dirname( __DIR__, 2 );

$expectations = array(
    'packages/itk-commerce-documents/src/DocumentService.php' => array(
        "'invoice'",
        "'delivery-note'",
        "'return-form'",
        "'packing-list'",
        'itk_commerce_documents_pdf_renderer',
        '_itk_commerce_language',
    ),
    'packages/itk-commerce-documents/src/DocumentsModule.php' => array(
        'itk_manage_documents',
        'admin_post_itk_commerce_generate_document',
    ),
    'packages/itk-commerce-elementor/src/ElementorModule.php' => array(
        'elementor/widgets/register',
        'ProductSummaryWidget',
        'CommerceHookWidget',
    ),
    'packages/itk-commerce-core/src/Design/LocalFonts.php' => array(
        'itk_commerce_local_fonts',
        'upload_mimes',
        'wp_enqueue_media',
        '@font-face',
        'font-display:swap',
        'itk_manage_design',
    ),
    'packages/itk-commerce-code-manager/itk-commerce-code-manager.php' => array(
        'ITK_COMMERCE_ALLOW_PHP_SNIPPETS',
        'wp_head',
        'wp_body_open',
        'wp_footer',
        'manage_options',
    ),
    'packages/itk-commerce-badges/itk-commerce-badges.php' => array(
        'itk_commerce_product_badges',
        '_itk_custom_badge',
    ),
    'packages/itk-commerce-wishlist-compare/itk-commerce-wishlist-compare.php' => array(
        'itk_wishlist',
        'itk_compare',
        'itk_commerce_product_card_actions',
    ),
    'packages/itk-commerce-gift-boxes/itk-commerce-gift-boxes.php' => array(
        'woocommerce_add_to_cart_validation',
        'woocommerce_add_cart_item_data',
        'woocommerce_checkout_create_order_line_item',
    ),
);

foreach ( $expectations as $relative => $needles ) {
    $file = $root . '/' . $relative;
    if ( ! is_file( $file ) ) {
        throw new RuntimeException( 'Missing contract file: ' . $relative );
    }
    $content = file_get_contents( $file );
    foreach ( $needles as $needle ) {
        if ( false === strpos( $content, $needle ) ) {
            throw new RuntimeException( sprintf( 'Missing contract "%s" in %s', $needle, $relative ) );
        }
    }
}

fwrite( STDOUT, "Phase 6-7 module contract smoke test passed.\n" );
