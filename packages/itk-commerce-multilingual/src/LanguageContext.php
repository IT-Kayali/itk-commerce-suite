<?php
/**
 * Request language context exposed through public Commerce Suite contracts.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class LanguageContext {
    /** @var array<string,mixed> */
    private $config;

    /** @var array<string,array<string,mixed>> */
    private $languages = array();

    /** @var string */
    private $current_code = '';

    /** @param array<string,mixed> $config Normalized language config. */
    public function __construct( array $config ) {
        $this->config = $config;

        foreach ( isset( $config['languages'] ) && is_array( $config['languages'] ) ? $config['languages'] : array() as $language ) {
            if ( empty( $language['code'] ) || empty( $language['enabled'] ) ) {
                continue;
            }
            $this->languages[ $language['code'] ] = $language;
        }

        $default = isset( $config['default'] ) ? (string) $config['default'] : '';
        if ( isset( $this->languages[ $default ] ) ) {
            $this->current_code = $default;
        } elseif ( ! empty( $this->languages ) ) {
            $this->current_code = (string) array_key_first( $this->languages );
        }
    }

    /** @return void */
    public function register() {
        add_filter( 'body_class', array( $this, 'body_classes' ) );
        add_filter( 'language_attributes', array( $this, 'language_attributes' ) );
        add_filter( 'itk_commerce_language_context', array( $this, 'filter_context' ) );
        add_filter( 'itk_commerce_current_language', array( $this, 'filter_current_code' ) );
        add_filter( 'itk_commerce_text_direction', array( $this, 'filter_direction' ) );
    }

    /**
     * @param string $code Configured enabled language code.
     * @return bool
     */
    public function select( $code ) {
        $code = strtolower( trim( (string) $code ) );
        if ( ! isset( $this->languages[ $code ] ) ) {
            return false;
        }

        if ( $code === $this->current_code ) {
            return true;
        }

        $previous           = $this->current_code;
        $this->current_code = $code;

        do_action( 'itk_commerce_language_context_changed', $code, $previous, $this->current() );
        return true;
    }

    /** @return string */
    public function code() {
        return $this->current_code;
    }

    /** @return array<string,mixed> */
    public function current() {
        return isset( $this->languages[ $this->current_code ] ) ? $this->languages[ $this->current_code ] : array();
    }

    /** @return string */
    public function locale() {
        $current = $this->current();
        return isset( $current['locale'] ) ? (string) $current['locale'] : '';
    }

    /** @return string */
    public function direction() {
        $current = $this->current();
        return isset( $current['direction'] ) && 'rtl' === $current['direction'] ? 'rtl' : 'ltr';
    }

    /** @return array<int,array<string,mixed>> */
    public function enabled_languages() {
        return array_values( $this->languages );
    }

    /** @return string */
    public function default_code() {
        $default = isset( $this->config['default'] ) ? (string) $this->config['default'] : '';
        return isset( $this->languages[ $default ] ) ? $default : $this->current_code;
    }

    /** @return string */
    public function fallback_code() {
        $fallback = isset( $this->config['fallback'] ) ? (string) $this->config['fallback'] : '';
        return isset( $this->languages[ $fallback ] ) ? $fallback : $this->current_code;
    }

    /** @param string[] $classes Existing classes. @return string[] */
    public function body_classes( $classes ) {
        $classes   = is_array( $classes ) ? $classes : array();
        $classes[] = 'itk-language-' . sanitize_html_class( $this->code() );
        $classes[] = 'itk-direction-' . $this->direction();
        return array_values( array_unique( $classes ) );
    }

    /**
     * @param string $output Existing WordPress language attribute string.
     * @return string
     */
    public function language_attributes( $output ) {
        $output = is_string( $output ) ? $output : '';
        $code   = esc_attr( $this->code() );
        $dir    = esc_attr( $this->direction() );

        $output = preg_replace( '/\blang=("[^"]*"|\'[^\']*\')/i', '', $output );
        $output = preg_replace( '/\bdir=("[^"]*"|\'[^\']*\')/i', '', $output );
        $output = trim( preg_replace( '/\s+/', ' ', $output ) );

        return trim( $output . ' lang="' . $code . '" dir="' . $dir . '"' );
    }

    /** @param mixed $context Existing context. @return array<string,mixed> */
    public function filter_context( $context ) {
        unset( $context );
        return array(
            'code'      => $this->code(),
            'locale'    => $this->locale(),
            'direction' => $this->direction(),
            'default'   => $this->default_code(),
            'fallback'  => $this->fallback_code(),
            'language'  => $this->current(),
            'languages' => $this->enabled_languages(),
        );
    }

    /** @param mixed $code Existing code. @return string */
    public function filter_current_code( $code ) {
        unset( $code );
        return $this->code();
    }

    /** @param mixed $direction Existing direction. @return string */
    public function filter_direction( $direction ) {
        unset( $direction );
        return $this->direction();
    }
}
