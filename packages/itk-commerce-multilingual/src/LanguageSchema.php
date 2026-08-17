<?php
/**
 * Versioned bounded language configuration schema.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class LanguageSchema {
    /** @return array<string,mixed> */
    public function defaults() {
        $locale = function_exists( 'get_locale' ) ? (string) get_locale() : 'en_US';
        $locale = $this->normalize_locale( $locale );
        $code   = $this->code_from_locale( $locale );

        return array(
            'schema_version' => SCHEMA_VERSION,
            'default'        => $code,
            'fallback'       => $code,
            'languages'      => array(
                array(
                    'code'      => $code,
                    'locale'    => $locale,
                    'label'     => strtoupper( $code ),
                    'direction' => $this->default_direction( $code ),
                    'enabled'   => true,
                ),
            ),
        );
    }

    /**
     * @param mixed $config Raw configuration.
     * @return array<string,mixed>
     */
    public function normalize( $config ) {
        if ( ! is_array( $config ) ) {
            return $this->defaults();
        }

        $raw_languages = isset( $config['languages'] ) && is_array( $config['languages'] )
            ? array_slice( $config['languages'], 0, 20 )
            : array();

        $languages = array();
        $seen      = array();

        foreach ( $raw_languages as $language ) {
            $item = $this->normalize_language( $language );
            if ( null === $item || isset( $seen[ $item['code'] ] ) ) {
                continue;
            }

            $seen[ $item['code'] ] = true;
            $languages[]            = $item;
        }

        if ( empty( $languages ) ) {
            return $this->defaults();
        }

        $enabled_codes = array_values(
            array_map(
                static function ( $language ) {
                    return $language['code'];
                },
                array_filter(
                    $languages,
                    static function ( $language ) {
                        return ! empty( $language['enabled'] );
                    }
                )
            )
        );

        if ( empty( $enabled_codes ) ) {
            $languages[0]['enabled'] = true;
            $enabled_codes[]         = $languages[0]['code'];
        }

        $default  = isset( $config['default'] ) ? $this->normalize_code( $config['default'] ) : '';
        $fallback = isset( $config['fallback'] ) ? $this->normalize_code( $config['fallback'] ) : '';

        if ( ! in_array( $default, $enabled_codes, true ) ) {
            $default = $enabled_codes[0];
        }
        if ( ! in_array( $fallback, $enabled_codes, true ) ) {
            $fallback = $default;
        }

        return array(
            'schema_version' => SCHEMA_VERSION,
            'default'        => $default,
            'fallback'       => $fallback,
            'languages'      => array_values( $languages ),
        );
    }

    /**
     * @param array<string,mixed> $config Normalized config.
     * @return array<string,array<string,mixed>>
     */
    public function keyed( array $config ) {
        $keyed = array();
        foreach ( isset( $config['languages'] ) && is_array( $config['languages'] ) ? $config['languages'] : array() as $language ) {
            if ( ! empty( $language['code'] ) ) {
                $keyed[ $language['code'] ] = $language;
            }
        }
        return $keyed;
    }

    /** @param mixed $language Raw row. @return array<string,mixed>|null */
    private function normalize_language( $language ) {
        if ( ! is_array( $language ) ) {
            return null;
        }

        $code   = isset( $language['code'] ) ? $this->normalize_code( $language['code'] ) : '';
        $locale = isset( $language['locale'] ) ? $this->normalize_locale( $language['locale'] ) : '';

        if ( '' === $code || '' === $locale ) {
            return null;
        }

        $label = isset( $language['label'] ) ? sanitize_text_field( $language['label'] ) : strtoupper( $code );
        if ( '' === $label ) {
            $label = strtoupper( $code );
        }

        $direction = isset( $language['direction'] ) ? sanitize_key( $language['direction'] ) : '';
        if ( ! in_array( $direction, array( 'ltr', 'rtl' ), true ) ) {
            $direction = $this->default_direction( $code );
        }

        return array(
            'code'      => $code,
            'locale'    => $locale,
            'label'     => $label,
            'direction' => $direction,
            'enabled'   => ! isset( $language['enabled'] ) || ! empty( $language['enabled'] ),
        );
    }

    /** @param mixed $code Raw code. @return string */
    public function normalize_code( $code ) {
        $code = strtolower( trim( (string) $code ) );
        $code = str_replace( '_', '-', $code );

        if ( ! preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ) {
            return '';
        }

        return $code;
    }

    /** @param mixed $locale Raw locale. @return string */
    public function normalize_locale( $locale ) {
        $locale = trim( (string) $locale );

        if ( ! preg_match( '/^[A-Za-z]{2,3}(?:_[A-Za-z]{2,4})?(?:_[A-Za-z0-9]{2,8})?$/', $locale ) ) {
            return '';
        }

        $parts    = explode( '_', $locale );
        $parts[0] = strtolower( $parts[0] );
        if ( isset( $parts[1] ) ) {
            $parts[1] = 4 === strlen( $parts[1] )
                ? ucfirst( strtolower( $parts[1] ) )
                : strtoupper( $parts[1] );
        }
        if ( isset( $parts[2] ) ) {
            $parts[2] = strtoupper( $parts[2] );
        }

        return implode( '_', $parts );
    }

    /** @param string $locale Normalized locale. @return string */
    private function code_from_locale( $locale ) {
        $parts = explode( '_', $locale );
        $code  = $this->normalize_code( $parts[0] );
        return $code ?: 'en';
    }

    /** @param string $code Language code. @return string */
    private function default_direction( $code ) {
        $primary = explode( '-', strtolower( $code ) )[0];
        return in_array( $primary, array( 'ar', 'fa', 'he', 'ur' ), true ) ? 'rtl' : 'ltr';
    }
}
