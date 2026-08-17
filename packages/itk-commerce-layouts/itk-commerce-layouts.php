<?php
/**
 * Plugin Name: IT-Kayali Commerce Layouts
 * Description: Profile-driven reusable layout selection for the IT-Kayali Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-layouts
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const FILE      = __FILE__;
const PATH      = __DIR__;
const MODULE_ID = 'itk-commerce-layouts';

\register_activation_hook( FILE, __NAMESPACE__ . '\\activate' );
\register_deactivation_hook( FILE, __NAMESPACE__ . '\\deactivate' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\\prepare', 5 );

/**
 * Load the module only when the Commerce Core module contract is available.
 *
 * @return void
 */
function prepare() {
    if ( ! interface_exists( '\\ITK\\Commerce\\Core\\Contracts\\ModuleInterface' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\\render_core_notice' );
        return;
    }

    require_once PATH . '/src/LayoutResolver.php';
    require_once PATH . '/src/MegaMenuConfig.php';
    require_once PATH . '/src/LivePreview.php';

    if ( is_admin() ) {
        require_once PATH . '/src/Admin/LayoutBuilderPage.php';
        require_once PATH . '/src/Admin/MegaMenuFields.php';
    }

    require_once PATH . '/src/LayoutsModule.php';

    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\\register_module' );
}

/**
 * Enable the module in Core settings on WordPress activation when Core is available.
 * Customer-profile module selections are not rewritten here.
 *
 * @return void
 */
function activate() {
    if ( ! class_exists( '\\ITK\\Commerce\\Core\\Core' ) ) {
        return;
    }

    $settings_repository = \ITK\Commerce\Core\Core::instance()->settings();
    $settings            = $settings_repository->all();
    $enabled             = isset( $settings['modules']['enabled'] ) && is_array( $settings['modules']['enabled'] )
        ? $settings['modules']['enabled']
        : array();

    if ( ! in_array( MODULE_ID, $enabled, true ) ) {
        $enabled[] = MODULE_ID;
    }

    $settings['modules']['enabled'] = array_values( array_unique( $enabled ) );
    $settings_repository->save( $settings );
}

/**
 * Disable the global Core setting on WordPress deactivation while preserving
 * customer-profile configuration for safe reactivation/rollback.
 *
 * @return void
 */
function deactivate() {
    if ( ! class_exists( '\\ITK\\Commerce\\Core\\Core' ) ) {
        return;
    }

    $settings_repository = \ITK\Commerce\Core\Core::instance()->settings();
    $settings            = $settings_repository->all();
    $enabled             = isset( $settings['modules']['enabled'] ) && is_array( $settings['modules']['enabled'] )
        ? $settings['modules']['enabled']
        : array();

    $settings['modules']['enabled'] = array_values(
        array_filter(
            $enabled,
            static function ( $module_id ) {
                return MODULE_ID !== $module_id;
            }
        )
    );

    $settings_repository->save( $settings );
}

/**
 * Register the installable module with Commerce Core.
 *
 * @param object $registry Commerce Core module registry.
 * @return void
 */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new LayoutsModule() );
    }
}

/**
 * Explain the missing Core dependency without causing a fatal error.
 *
 * @return void
 */
function render_core_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__( 'IT-Kayali Commerce Layouts requires IT-Kayali Commerce Core.', 'itk-commerce-layouts' );
    echo '</p></div>';
}
