<?php
/**
 * Plugin Name: IT-Kayali Commerce Multilingual
 * Description: Modular language context, translation workflow and RTL/LTR foundation for the IT-Kayali Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-multilingual
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

const VERSION        = '0.1.0-dev';
const FILE           = __FILE__;
const PATH           = __DIR__;
const MODULE_ID      = 'itk-commerce-multilingual';
const SCHEMA_VERSION = 1;

require_once PATH . '/src/TranslationInstaller.php';

\register_activation_hook( FILE, __NAMESPACE__ . '\activate' );
\register_deactivation_hook( FILE, __NAMESPACE__ . '\deactivate' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare', 7 );

/**
 * Load the module only when Commerce Core is available. Translation table
 * upgrades remain module-owned and versioned independently from Commerce Core.
 *
 * @return void
 */
function prepare() {
    TranslationInstaller::maybe_install();

    if ( ! interface_exists( '\ITK\Commerce\Core\Contracts\ModuleInterface' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\render_core_notice' );
        return;
    }

    require_once PATH . '/src/LanguageSchema.php';
    require_once PATH . '/src/LanguageContext.php';
    require_once PATH . '/src/LanguageRouter.php';
    require_once PATH . '/src/LanguageSwitcher.php';
    require_once PATH . '/src/MultilingualSeo.php';
    require_once PATH . '/src/TranslationSchema.php';
    require_once PATH . '/src/TranslationRepository.php';
    require_once PATH . '/src/TranslationWorkflow.php';
    require_once PATH . '/src/WooCommerceLanguageContext.php';
    require_once PATH . '/src/OrderLanguageScope.php';
    require_once PATH . '/src/OrderTranslationLanguageBridge.php';
    require_once PATH . '/src/WooCommerceTranslationMapper.php';
    require_once PATH . '/src/MultilingualModule.php';

    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\register_module' );
}

/** @return void */
function activate() {
    TranslationInstaller::install();
    set_enabled_state( true );
}

/** @return void */
function deactivate() {
    set_enabled_state( false );
}

/**
 * Synchronize explicit plugin state with Commerce Core and the active profile.
 * Existing language/translation data is preserved for rollback/reactivation.
 *
 * @param bool $enable Desired state.
 * @return void
 */
function set_enabled_state( $enable ) {
    if ( ! class_exists( '\ITK\Commerce\Core\Core' ) ) {
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
 * Add/remove this module ID without disturbing other enabled modules.
 *
 * @param string[] $enabled Existing module IDs.
 * @param bool     $enable Desired state.
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
        $registry->register( new MultilingualModule() );
    }
}

/** @return void */
function render_core_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__( 'IT-Kayali Commerce Multilingual requires IT-Kayali Commerce Core.', 'itk-commerce-multilingual' );
    echo '</p></div>';
}
