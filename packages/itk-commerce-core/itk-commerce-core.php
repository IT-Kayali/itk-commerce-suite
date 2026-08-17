<?php
/**
 * Plugin Name: IT-Kayali Commerce Core
 * Description: Core services, settings and module coordination for the IT-Kayali Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-core
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0-dev';
const FILE    = __FILE__;
const PATH    = __DIR__;

require_once PATH . '/src/Core.php';
require_once PATH . '/src/Contracts/ModuleInterface.php';

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

/**
 * Bootstrap the core once all plugins are loaded.
 */
function bootstrap() {
    Core::instance()->boot();
}
