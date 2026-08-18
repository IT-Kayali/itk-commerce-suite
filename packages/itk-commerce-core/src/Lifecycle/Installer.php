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
    const SCHEMA_VERSION = 2;
    const SCHEMA_OPTION  = 'itk_commerce_core_schema_version';

    /**
     * Initialize versioned options and capabilities on activation.
     *
     * @return void
     */
    public static function activate() {
        self::install_current_schema();
    }

    /**
     * Apply idempotent Core migrations after an update. This keeps new
     * capabilities/settings available without requiring plugin deactivation.
     *
     * @return void
     */
    public static function maybe_upgrade() {
        $installed = absint( get_option( self::SCHEMA_OPTION, 0 ) );
        if ( $installed >= self::SCHEMA_VERSION ) {
            return;
        }
        self::install_current_schema();
    }

    /**
     * Deactivation intentionally preserves customer configuration and roles.
     *
     * @return void
     */
    public static function deactivate() {
        Capabilities::deactivate();
    }

    /** @return void */
    private static function install_current_schema() {
        ( new SettingsRepository() )->install_defaults();
        ( new ProfileRepository() )->install_defaults();
        Capabilities::install();

        update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
        update_option( 'itk_commerce_core_version', defined( 'ITK_COMMERCE_CORE_VERSION' ) ? ITK_COMMERCE_CORE_VERSION : '0.1.0-dev', false );
    }
}
