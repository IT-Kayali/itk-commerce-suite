<?php
/**
 * Request language context exposed to Theme/modules through public contracts.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class LanguageContext {
    /** @var array<string,mixed> */
    private $config;

    /** @var array<string,array<string,mixed>> */
    private $languages;

    /** @var string */
    private $current_code;

    /**
     * @param array<string,mixed> $config Normalized language config.
     */
    public function __construct( array $config ) {
        $this->config    = $config;
        $this->languages = array();

        foreach ( isset( $config['languages'] ) && is_array( $config['languages'] ) ? $config['languages'] : array() as $language ) {
            if ( empty( $language['code'] ) || empty( $language['enabled'] ) ) {
                continue;
            }
            $this->languages[ $language['code'] ] = $language;
        }

        $default = isset( $config['default'] ) ? (string) $config['default'] : '';
        $this->current_code = isset( $this->languages[ $default ] ) ? $default : (string) array_key_first( $this->languages );
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
     * Explicitly select one configured language for the current request.
     * Directory/query routing will call this service in a later Phase 5 slice.
     *
     * @param string $code Configured public language code.
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

        /**
         * Fires when the request language context changes.
         *
         * @param string $code New language code.
         * @param string $previous Previous language code.
         * @param array<string,mixed> $language New language definition.
         */
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
    public function fallback_code() {
        $fallback = isset( $this->config['fallback'] ) ? (string) $this->config['fallback'] : '';
        return isset( $this->languages[ $fallback ] ) ? $fallback : $this->current_code;
    }

    /**
     * Add stable direction/language classes without forcing Theme internals.
     *
     * @param string[] $classes Existing body classes.
     * @return string[]
     */
    public function body_classes( $classes ) {
        $classes   = is_array( $classes ) ? $classes : array();
        $classes[] = 'itk-language-' . sanitize_html_class( $this->code() );
        $classes[] = 'itk-direction-' . $this->direction();
        return array_values( array_unique( $classes ) );
    }

    /**
     * Align the document's public lang/dir attributes with the selected Commerce
     * language context. WordPress still owns the attribute string/filter chain.
     *
     * @param string $output Existing language attributes.
     * @return string
     */
    public function language_attributes( $output ) {
        $output = is_string( $output ) ? $output : '';
        $code   = esc_attr( $this->code() );
        $dir    = esc_attr( $this->direction() );

        if ( preg_match( '/\blang=(?:"[^"]*"|\'[^\']*\')/i', $output ) ) {
            $output = preg_replace( '/\blang=(?:"[^"]*"|\'[^\']*\')/i', 'lang="' . $code . '"', $output, 1 );
        } else {
            $output = trim( $output . ' lang="' . $code . '"' );
        }

        if ( preg_match( '/\bdir=(?:"[^"]*"|\'[^\']*\')/i', $output ) ) {
            $output = preg_replace( '/\bdir=(?:"[^"]*"|\'[^\']*\')/i', 'dir="' . $dir . '"', $output, 1 );
        } else {
            $output = trim( $output . ' dir="' . $dir . '"' );
        }

        return $output;
    }

    /**
     * @param mixed $context Existing public context.
     * @return array<string,mixed>
     */
    public function filter_context( $context ) {
        unset( $context );
        return array(
            'code'      => $this->code(),
            'locale'    => $this->locale(),
            'direction' => $this->direction(),
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
