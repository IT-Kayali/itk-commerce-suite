<?php
/**
 * Plugin Name: IT-Kayali Commerce Code Manager
 * Description: Versioned, conditional and safe-mode HTML/CSS/JS/shortcode/Elementor/PHP extension points for the Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-code-manager
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const FILE      = __FILE__;
const PATH      = __DIR__;
const MODULE_ID = 'itk-commerce-code-manager';

add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare', 10 );

/** @return void */
function prepare() {
    if ( ! interface_exists( '\ITK\Commerce\Core\Contracts\ModuleInterface' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\core_notice' );
        return;
    }

    require_once PATH . '/src/SnippetRepository.php';
    require_once PATH . '/src/ConditionMatcher.php';
    require_once PATH . '/src/SnippetRuntime.php';
    require_once PATH . '/src/AdminPage.php';
    require_once PATH . '/src/CodeManagerModule.php';
    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\register_module' );
}

/** @param object $registry Registry. @return void */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new CodeManagerModule() );
    }
}

/** @return void */
function core_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'IT-Kayali Commerce Code Manager requires Commerce Core.', 'itk-commerce-code-manager' ) . '</p></div>';
    }
}
