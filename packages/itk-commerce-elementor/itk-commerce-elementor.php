<?php
/**
 * Plugin Name: IT-Kayali Commerce Elementor
 * Description: Elementor integration, widgets and dynamic WooCommerce presentation bridges for the Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-elementor
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Elementor
 */

namespace ITK\Commerce\Elementor;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const FILE      = __FILE__;
const PATH      = __DIR__;
const MODULE_ID = 'itk-commerce-elementor';

add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare', 9 );

/** @return void */
function prepare() {
    if ( ! interface_exists( '\ITK\Commerce\Core\Contracts\ModuleInterface' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\core_notice' );
        return;
    }
    require_once PATH . '/src/ElementorModule.php';
    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\register_module' );
}

/** @param object $registry Registry. @return void */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new ElementorModule() );
    }
}

/** @return void */
function core_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'IT-Kayali Commerce Elementor requires Commerce Core.', 'itk-commerce-elementor' ) . '</p></div>';
    }
}
