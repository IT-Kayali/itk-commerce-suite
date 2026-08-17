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
    require_once PATH . '/src/CommerceTemplateResolver.php';
    require_once PATH . '/src/MegaMenuConfig.php';
    require_once PATH . '/src/RichMegaMenuRenderer.php';
    require_once PATH . '/src/LivePreview.php';

    if ( is_admin() ) {
        require_once PATH . '/src/Admin/LayoutBuilderPage.php';
        require_once PATH . '/src/Admin/CommerceTemplatePage.php';
        require_once PATH . '/src/Admin/ProductCardPage.php';
        require_once PATH . '/src/Admin/MegaMenuFields.php';
        require_once PATH . '/src/Admin/MegaMenuContentPage.php';
    }

    require_once PATH . '/src/LayoutsModule.php';

    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\\register_module' );
}

/**
 * Enable the module in global Core settings and the active customer profile.
 * Existing profile configuration is preserved.
 *
 * @return void
 */
function activate() {
    set_enabled_state( true );
}

/**
 * Disable the module in global Core settings and the active customer profile.
 * Layout configuration remains stored for safe reactivation/rollback.
 *
 * @return void
 */
function deactivate() {
    set_enabled_state( false );
}

/**
 * Synchronize the explicit WordPress plugin state with Core/profile module flags.
 *
 * @param bool $enable Desired module state.
 * @return void
 */
function set_enabled_state( $enable ) {
    if ( ! class_exists( '\\ITK\\Commerce\\Core\\Core' ) ) {
        return;
    }

    $core                = \ITK\Commerce\Core\Core::instance();
    $settings_repository = $core->settings();
    $settings            = $settings_repository->all();
    $enabled             = isset( $settings['modules']['enabled'] ) && is_array( $settings['modules']['enabled'] )
        ? $settings['modules']['enabled']
        : array();

    $enabled = update_enabled_list( $enabled, (bool) $enable );
    $settings['modules']['enabled'] = $enabled;
    $settings_repository->save( $settings );

    $profile_id = isset( $settings['active_profile_id'] ) ? sanitize_key( $settings['active_profile_id'] ) : '';
    $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;

    if ( ! is_array( $profile ) ) {
        return;
    }

    if ( empty( $profile['modules'] ) || ! is_array( $profile['modules'] ) ) {
        $profile['modules'] = array();
    }
    $profile_enabled = isset( $profile['modules']['enabled'] ) && is_array( $profile['modules']['enabled'] )
        ? $profile['modules']['enabled']
        : array();

    $profile['modules']['enabled'] = update_enabled_list( $profile_enabled, (bool) $enable );
    $core->profiles()->save( $profile );
}

/**
 * Add or remove this module identifier without disturbing other modules.
 *
 * @param string[] $enabled Existing enabled module IDs.
 * @param bool     $enable  Desired state.
 * @return string[]
 */
function update_enabled_list( array $enabled, $enable ) {
    $enabled = array_values( array_unique( array_filter( array_map( 'sanitize_key', $enabled ) ) ) );

    if ( $enable ) {
        if ( ! in_array( MODULE_ID, $enabled, true ) ) {
            $enabled[] = MODULE_ID;
        }
        return $enabled;
    }

    return array_values(
        array_filter(
            $enabled,
            static function ( $module_id ) {
                return MODULE_ID !== $module_id;
            }
        )
    );
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
