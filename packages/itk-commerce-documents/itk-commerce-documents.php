<?php
/**
 * Plugin Name: IT-Kayali Commerce Documents
 * Description: Invoice, correction, delivery-note, return-form and packing-list documents for WooCommerce orders.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-documents
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const FILE      = __FILE__;
const PATH      = __DIR__;
const MODULE_ID = 'itk-commerce-documents';

add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare', 8 );

/** @return void */
function prepare() {
    if ( ! interface_exists( '\ITK\Commerce\Core\Contracts\ModuleInterface' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\core_notice' );
        return;
    }
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\woocommerce_notice' );
        return;
    }

    require_once PATH . '/src/DocumentNumberService.php';
    require_once PATH . '/src/DocumentHistoryService.php';
    require_once PATH . '/src/ReturnCaseService.php';
    require_once PATH . '/src/DocumentService.php';
    require_once PATH . '/src/DocumentsModule.php';
    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\register_module' );
}

/** @param object $registry Module registry. @return void */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new DocumentsModule() );
    }
}

/** @return void */
function core_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'IT-Kayali Commerce Documents requires Commerce Core.', 'itk-commerce-documents' ) . '</p></div>';
    }
}

/** @return void */
function woocommerce_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'IT-Kayali Commerce Documents requires WooCommerce.', 'itk-commerce-documents' ) . '</p></div>';
    }
}
