<?php
/**
 * Static contract smoke tests for Phase 6/7 installable modules.
 */

$root = dirname( __DIR__, 2 );

$expectations = array(
    'packages/itk-commerce-documents/src/DocumentService.php' => array(
        "'invoice'",
        "'invoice-correction'",
        "'delivery-note'",
        "'return-form'",
        "'packing-list'",
        'itk_commerce_documents_pdf_renderer',
        'itk_commerce_document_code_markup',
        '_itk_commerce_language',
        '_itk_commerce_direction',
    ),
    'packages/itk-commerce-documents/src/DocumentNumberService.php' => array(
        'INV-',
        'CRN-',
        'RET-',
        'add_option',
        '_itk_document_number_',
    ),
    'packages/itk-commerce-documents/src/DocumentHistoryService.php' => array(
        'content_hash',
        'previous_hash',
        'entry_hash',
    ),
    'packages/itk-commerce-documents/src/ReturnCaseService.php' => array(
        'requested',
        'received',
        'approved',
        'rejected',
        'refunded',
        'closed',
    ),
    'packages/itk-commerce-documents/src/DocumentsModule.php' => array(
        'itk_manage_documents',
        'admin_post_itk_commerce_generate_document',
        'woocommerce_email_attachments',
        'woocommerce_order_details_after_order_table',
        'itk_commerce_order_language_scope',
    ),
    'packages/itk-commerce-elementor/src/ElementorModule.php' => array(
        'elementor/widgets/register',
        'elementor/dynamic_tags/register',
        'ProductsWidget',
        'ProductCategoriesWidget',
        'ProductFilterWidget',
        'ProductSearchWidget',
        'HeroBannerWidget',
        'BranchesWidget',
        'ReviewsWidget',
        'ContactWidget',
        'MiniCartWidget',
        'LanguageSwitcherWidget',
        'MenuWidget',
    ),
    'packages/itk-commerce-elementor/src/DynamicTags.php' => array(
        'product-sku',
        'product-price',
        'product-stock',
        'current-language',
        'contact-email',
    ),
    'packages/itk-commerce-theme/inc/elementor.php' => array(
        "'single'",
        "'archive'",
        "'itk-before-header'",
        "'itk-before-content'",
        "'itk-before-footer'",
    ),
    'packages/itk-commerce-core/src/Design/LocalFonts.php' => array(
        'itk_commerce_local_fonts',
        'upload_mimes',
        'wp_enqueue_media',
        '@font-face',
        'font-display:swap',
        'itk_manage_design',
    ),
    'packages/itk-commerce-code-manager/src/SnippetRepository.php' => array(
        'TOKEN_PARSE',
        'version',
        'audit',
        'set_enabled',
        'rollback',
        'disabled',
    ),
    'packages/itk-commerce-code-manager/src/ConditionMatcher.php' => array(
        'itk_commerce_current_language',
        'wp_is_mobile',
        'is_product',
        'is_product_category',
        'roles',
    ),
    'packages/itk-commerce-code-manager/src/SnippetRuntime.php' => array(
        'ITK_COMMERCE_CODE_SAFE_MODE',
        'shutdown_guard',
        'disable_after_error',
        'itk_commerce_before_header',
        'itk_commerce_before_content',
        'eval',
    ),
    'packages/itk-commerce-code-manager/src/AdminPage.php' => array(
        'itk_manage_code',
        'Create disabled snippet',
        'Rollback',
        'Audit log',
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
