<?php
/**
 * Plugin Name: IT-Kayali Commerce Search & Filter
 * Description: Modular WooCommerce catalog search and filtering for the IT-Kayali Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-search-filter
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

const VERSION        = '0.1.0-dev';
const FILE           = __FILE__;
const PATH           = __DIR__;
const MODULE_ID      = 'itk-commerce-search-filter';
const SCHEMA_VERSION = 1;

\register_activation_hook( FILE, __NAMESPACE__ . '\\activate' );
\register_deactivation_hook( FILE, __NAMESPACE__ . '\\deactivate' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\\prepare', 6 );

/**
 * Load the module only when Core and WooCommerce are available.
 *
 * @return void
 */
function prepare() {
    if ( ! interface_exists( '\\ITK\\Commerce\\Core\\Contracts\\ModuleInterface' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\\render_core_notice' );
        return;
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\\render_woocommerce_notice' );
        return;
    }

    require_once PATH . '/src/FilterSchema.php';
    require_once PATH . '/src/UrlState.php';
    require_once PATH . '/src/WooQueryAdapter.php';
    require_once PATH . '/src/FilterRenderer.php';
    require_once PATH . '/src/Admin/PostedDefinitionNormalizer.php';
    require_once PATH . '/src/Admin/FilterBuilderPage.php';
    require_once PATH . '/src/SearchFilterModule.php';

    add_action( 'admin_post_itk_commerce_save_search_filters', '\\ITK\\Commerce\\SearchFilter\\Admin\\normalize_posted_definitions', 5 );
    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\\register_module' );
}

/** @return void */
function activate() {
    set_enabled_state( true );
}

/** @return void */
function deactivate() {
    set_enabled_state( false );
}

/**
 * Synchronize the WordPress plugin state with Core and the active profile while
 * preserving module configuration for safe rollback/reactivation.
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

    $settings['modules']['enabled'] = update_enabled_list( $enabled, (bool) $enable );
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
 * Add/remove this module ID without disturbing other modules.
 *
 * @param string[] $enabled Existing enabled IDs.
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
 * Register this installable module with Commerce Core.
 *
 * @param object $registry Core module registry.
 * @return void
 */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new SearchFilterModule() );
    }
}

/** @return void */
function render_core_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__( 'IT-Kayali Commerce Search & Filter requires IT-Kayali Commerce Core.', 'itk-commerce-search-filter' );
    echo '</p></div>';
}

/** @return void */
function render_woocommerce_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__( 'IT-Kayali Commerce Search & Filter requires WooCommerce.', 'itk-commerce-search-filter' );
    echo '</p></div>';
}
