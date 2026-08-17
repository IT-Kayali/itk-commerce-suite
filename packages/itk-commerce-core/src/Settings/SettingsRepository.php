<?php
/**
 * Versioned Commerce Suite settings repository.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Settings;

defined( 'ABSPATH' ) || exit;

final class SettingsRepository {
    const OPTION_NAME    = 'itk_commerce_settings';
    const SCHEMA_VERSION = 1;

    /**
     * Return normalized settings with defaults applied.
     *
     * @return array<string,mixed>
     */
    public function all() {
        $stored = get_option( self::OPTION_NAME, array() );
        $stored = is_array( $stored ) ? $stored : array();

        return array_replace_recursive( $this->defaults(), $this->sanitize( $stored ) );
    }

    /**
     * Return enabled module identifiers.
     *
     * @return string[]
     */
    public function enabled_modules() {
        $settings = $this->all();
        $enabled  = isset( $settings['modules']['enabled'] ) && is_array( $settings['modules']['enabled'] )
            ? $settings['modules']['enabled']
            : array();

        return array_values( array_unique( array_filter( array_map( 'sanitize_key', $enabled ) ) ) );
    }

    /**
     * Return the configured active customer profile identifier.
     *
     * @return string
     */
    public function active_profile_id() {
        $settings = $this->all();
        return isset( $settings['active_profile_id'] ) ? sanitize_key( $settings['active_profile_id'] ) : '';
    }

    /**
     * Persist a complete normalized settings document.
     *
     * @param array<string,mixed> $settings Settings document.
     * @return bool
     */
    public function save( array $settings ) {
        $normalized                   = array_replace_recursive( $this->defaults(), $this->sanitize( $settings ) );
        $normalized['schema_version'] = self::SCHEMA_VERSION;

        return update_option( self::OPTION_NAME, $normalized, false );
    }

    /**
     * Initialize the option without overwriting existing settings.
     *
     * @return void
     */
    public function install_defaults() {
        if ( false === get_option( self::OPTION_NAME, false ) ) {
            add_option( self::OPTION_NAME, $this->defaults(), '', false );
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function defaults() {
        return array(
            'schema_version'    => self::SCHEMA_VERSION,
            'active_profile_id' => '',
            'modules'           => array(
                'enabled' => array(),
            ),
        );
    }

    /**
     * Keep the core option deliberately small. Module-specific settings belong
     * to their own versioned namespaces and customer-visible design values live
     * in the customer profile.
     *
     * @param array<string,mixed> $settings Raw settings.
     * @return array<string,mixed>
     */
    private function sanitize( array $settings ) {
        $clean = array();

        $clean['schema_version'] = isset( $settings['schema_version'] ) ? absint( $settings['schema_version'] ) : self::SCHEMA_VERSION;
        $clean['active_profile_id'] = isset( $settings['active_profile_id'] ) ? sanitize_key( $settings['active_profile_id'] ) : '';

        $enabled = isset( $settings['modules']['enabled'] ) && is_array( $settings['modules']['enabled'] )
            ? $settings['modules']['enabled']
            : array();

        $clean['modules'] = array(
            'enabled' => array_values( array_unique( array_filter( array_map( 'sanitize_key', $enabled ) ) ) ),
        );

        return $clean;
    }
}
