<?php
/**
 * Plugin Name: IT-Kayali Commerce Badges
 * Description: Advanced sale-percentage and custom product badges for Commerce Suite product cards.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-badges
 * Requires PHP: 8.1
 * Requires Plugins: itk-commerce-core, woocommerce
 *
 * @package ITK_Commerce_Badges
 */

namespace ITK\Commerce\Badges;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const MODULE_ID = 'itk-commerce-badges';

add_action( 'plugins_loaded', __NAMESPACE__ . '\\prepare', 10 );

/**
 * Load the module class only after all plugins have been loaded.
 *
 * The Commerce Core contract cannot safely be referenced in a class declaration
 * while WordPress is still loading active plugin files because the badges plugin
 * may be evaluated before Commerce Core. Deferring the class file prevents a
 * fatal "Interface not found" error during activation or normal plugin loading.
 *
 * @return void
 */
function prepare() {
    if ( ! interface_exists( '\\ITK\\Commerce\\Core\\Contracts\\ModuleInterface' ) || ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once __DIR__ . '/src/BadgesModule.php';

    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\\register_module' );
}

/**
 * Register the badges module with Commerce Core.
 *
 * @param object $registry Registry.
 * @return void
 */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) && class_exists( __NAMESPACE__ . '\\BadgesModule', false ) ) {
        $registry->register( new BadgesModule() );
    }
}
