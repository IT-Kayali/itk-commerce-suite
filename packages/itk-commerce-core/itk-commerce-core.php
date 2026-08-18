<?php
/**
 * Plugin Name: IT-Kayali Commerce Core
 * Description: Core services, settings and module coordination for the IT-Kayali Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-core
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0-dev';
const FILE    = __FILE__;
const PATH    = __DIR__;

if ( ! defined( 'ITK_COMMERCE_CORE_VERSION' ) ) {
    define( 'ITK_COMMERCE_CORE_VERSION', VERSION );
}

require_once PATH . '/src/Contracts/ModuleInterface.php';
require_once PATH . '/src/Settings/SettingsRepository.php';
require_once PATH . '/src/Profiles/ProfileSchema.php';
require_once PATH . '/src/Profiles/ProfileRepository.php';
require_once PATH . '/src/Security/Capabilities.php';
require_once PATH . '/src/Lifecycle/Installer.php';
require_once PATH . '/src/Modules/ModuleRegistry.php';
require_once PATH . '/src/Core.php';
require_once PATH . '/src/Admin/AdminHub.php';
require_once PATH . '/src/Admin/LegacyAdminRoutes.php';
require_once PATH . '/src/Design/LocalFonts.php';

\register_activation_hook( FILE, array( Lifecycle\Installer::class, 'activate' ) );
\register_deactivation_hook( FILE, array( Lifecycle\Installer::class, 'deactivate' ) );

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap', 20 );

/**
 * Bootstrap the Core and register the central admin/design services.
 *
 * @return void
 */
function bootstrap() {
    Lifecycle\Installer::maybe_upgrade();

    $core = Core::instance();
    $core->boot();

    $local_fonts = new Design\LocalFonts( $core );
    $local_fonts->register();

    if ( is_admin() ) {
        $routes = new Admin\LegacyAdminRoutes();
        $routes->register();

        $admin = new Admin\AdminHub( $core );
        $admin->register();
    }
}
