<?php
/**
 * Customer-profile persistence.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class ProfileRepository {
    const OPTION_NAME = 'itk_commerce_customer_profiles';

    /**
     * Return all persisted profiles keyed by profile identifier.
     *
     * @return array<string,array<string,mixed>>
     */
    public function all() {
        $profiles = get_option( self::OPTION_NAME, array() );
        return is_array( $profiles ) ? $profiles : array();
    }

    /**
     * @param string $profile_id Profile identifier.
     * @return array<string,mixed>|null
     */
    public function get( $profile_id ) {
        $profile_id = sanitize_key( $profile_id );
        $profiles   = $this->all();

        return isset( $profiles[ $profile_id ] ) && is_array( $profiles[ $profile_id ] )
            ? $profiles[ $profile_id ]
            : null;
    }

    /**
     * Validate and persist a profile.
     *
     * @param array<string,mixed> $profile Raw profile document.
     * @return true|\WP_Error
     */
    public function save( array $profile ) {
        $normalized = ProfileSchema::normalize( $profile );

        if ( is_wp_error( $normalized ) ) {
            return $normalized;
        }

        $profiles = $this->all();
        $profiles[ $normalized['profile_id'] ] = $normalized;

        update_option( self::OPTION_NAME, $profiles, false );
        return true;
    }

    /**
     * Delete profile configuration only. This does not touch customer content,
     * products, orders or media.
     *
     * @param string $profile_id Profile identifier.
     * @return bool
     */
    public function delete( $profile_id ) {
        $profile_id = sanitize_key( $profile_id );
        $profiles   = $this->all();

        if ( ! isset( $profiles[ $profile_id ] ) ) {
            return false;
        }

        unset( $profiles[ $profile_id ] );
        return update_option( self::OPTION_NAME, $profiles, false );
    }

    /**
     * Initialize the profile option without overwriting existing configuration.
     *
     * @return void
     */
    public function install_defaults() {
        if ( false === get_option( self::OPTION_NAME, false ) ) {
            add_option( self::OPTION_NAME, array(), '', false );
        }
    }
}
