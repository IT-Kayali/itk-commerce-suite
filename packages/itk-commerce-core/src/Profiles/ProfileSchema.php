<?php
/**
 * Versioned customer-profile schema.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Profiles;

defined( 'ABSPATH' ) || exit;

final class ProfileSchema {
    const SCHEMA_VERSION = 1;

    /**
     * Validate and normalize a customer profile.
     *
     * @param array<string,mixed> $profile Raw profile data.
     * @return array<string,mixed>|\WP_Error
     */
    public static function normalize( array $profile ) {
        if ( self::contains_secret_keys( $profile ) ) {
            return new \WP_Error(
                'itk_profile_contains_secrets',
                __( 'Customer profiles must not contain passwords, private keys, tokens or API secrets.', 'itk-commerce-core' )
            );
        }

        $schema_version = isset( $profile['schema_version'] ) ? absint( $profile['schema_version'] ) : self::SCHEMA_VERSION;

        if ( self::SCHEMA_VERSION !== $schema_version ) {
            return new \WP_Error(
                'itk_profile_schema_unsupported',
                sprintf(
                    /* translators: 1: supplied schema version, 2: supported schema version. */
                    __( 'Customer profile schema %1$d is not supported. This Core supports schema %2$d.', 'itk-commerce-core' ),
                    $schema_version,
                    self::SCHEMA_VERSION
                )
            );
        }

        $profile_id = isset( $profile['profile_id'] ) ? sanitize_key( $profile['profile_id'] ) : '';

        if ( '' === $profile_id ) {
            return new \WP_Error(
                'itk_profile_id_missing',
                __( 'A customer profile requires a stable profile_id.', 'itk-commerce-core' )
            );
        }

        $enabled_modules = isset( $profile['modules']['enabled'] ) && is_array( $profile['modules']['enabled'] )
            ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $profile['modules']['enabled'] ) ) ) )
            : array();

        $normalized = array(
            'schema_version'  => self::SCHEMA_VERSION,
            'profile_id'      => $profile_id,
            'profile_version' => isset( $profile['profile_version'] ) ? sanitize_text_field( $profile['profile_version'] ) : '1.0.0',
            'name'            => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : $profile_id,
            'branding'        => self::sanitize_section( isset( $profile['branding'] ) ? $profile['branding'] : array() ),
            'design'          => self::sanitize_section( isset( $profile['design'] ) ? $profile['design'] : array() ),
            'contacts'        => self::sanitize_section( isset( $profile['contacts'] ) ? $profile['contacts'] : array() ),
            'languages'       => self::sanitize_languages( isset( $profile['languages'] ) ? $profile['languages'] : array() ),
            'layouts'         => self::sanitize_section( isset( $profile['layouts'] ) ? $profile['layouts'] : array() ),
            'modules'         => array(
                'enabled'       => $enabled_modules,
                'configuration' => self::sanitize_section(
                    isset( $profile['modules']['configuration'] ) ? $profile['modules']['configuration'] : array()
                ),
            ),
        );

        /**
         * Filter a normalized profile before it is persisted.
         *
         * Module packages may validate their own configuration namespaces here.
         *
         * @param array<string,mixed> $normalized Normalized profile.
         * @param array<string,mixed> $profile    Original profile.
         */
        return apply_filters( 'itk_commerce_normalized_profile', $normalized, $profile );
    }

    /**
     * @param mixed $section Arbitrary profile section.
     * @return array<string|int,mixed>
     */
    private static function sanitize_section( $section ) {
        if ( ! is_array( $section ) ) {
            return array();
        }

        $clean = array();

        foreach ( $section as $key => $value ) {
            $clean_key = is_int( $key ) ? $key : sanitize_key( $key );

            if ( is_array( $value ) ) {
                $clean[ $clean_key ] = self::sanitize_section( $value );
            } elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
                $clean[ $clean_key ] = $value;
            } elseif ( is_string( $value ) ) {
                $clean[ $clean_key ] = sanitize_text_field( $value );
            }
        }

        return $clean;
    }

    /**
     * @param mixed $languages Language definitions.
     * @return array<int,array<string,string>>
     */
    private static function sanitize_languages( $languages ) {
        if ( ! is_array( $languages ) ) {
            return array();
        }

        $clean = array();

        foreach ( $languages as $language ) {
            if ( ! is_array( $language ) || empty( $language['code'] ) ) {
                continue;
            }

            $code      = sanitize_key( $language['code'] );
            $direction = isset( $language['direction'] ) && 'rtl' === strtolower( (string) $language['direction'] ) ? 'rtl' : 'ltr';

            $clean[] = array(
                'code'      => $code,
                'label'     => isset( $language['label'] ) ? sanitize_text_field( $language['label'] ) : strtoupper( $code ),
                'direction' => $direction,
            );
        }

        return $clean;
    }

    /**
     * Reject secret-like fields from portable profile configuration.
     *
     * @param array<string|int,mixed> $values Values to inspect.
     * @return bool
     */
    private static function contains_secret_keys( array $values ) {
        $forbidden = array(
            'password',
            'passwd',
            'secret',
            'api_key',
            'api_secret',
            'private_key',
            'access_token',
            'refresh_token',
            'client_secret',
        );

        foreach ( $values as $key => $value ) {
            if ( is_string( $key ) && in_array( sanitize_key( $key ), $forbidden, true ) ) {
                return true;
            }

            if ( is_array( $value ) && self::contains_secret_keys( $value ) ) {
                return true;
            }
        }

        return false;
    }
}
