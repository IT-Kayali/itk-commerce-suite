<?php
/**
 * Plugin lifecycle installation routines.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Lifecycle;

use ITK\Commerce\Core\Profiles\ProfileRepository;
use ITK\Commerce\Core\Security\Capabilities;
use ITK\Commerce\Core\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

final class Installer {
    /**
     * Initialize versioned options and capabilities on activation.
     *
     * @return void
     */
    public static function activate() {
        ( new SettingsRepository() )->install_defaults();
        ( new ProfileRepository() )->install_defaults();
        Capabilities::install();

        update_option( 'itk_commerce_core_version', defined( 'ITK_COMMERCE_CORE_VERSION' ) ? ITK_COMMERCE_CORE_VERSION : '0.1.0-dev', false );
    }

    /**
     * Deactivation intentionally preserves customer configuration and roles.
     *
     * @return void
     */
    public static function deactivate() {
        Capabilities::deactivate();
    }
}
