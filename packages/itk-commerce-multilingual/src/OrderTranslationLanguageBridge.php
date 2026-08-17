<?php
/**
 * Bridge frozen order-language scopes into translation lookup.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class OrderTranslationLanguageBridge {
    /** @var string[] */
    private $codes = array();

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_order_language_scope_entered', array( $this, 'enter' ), 10, 3 );
        add_action( 'itk_commerce_order_language_scope_left', array( $this, 'leave' ), 10, 2 );
        add_filter( 'itk_commerce_translation_language_code', array( $this, 'filter_language_code' ), 20, 3 );
    }

    /**
     * @param mixed $order Order object.
     * @param mixed $context Frozen order context.
     * @param mixed $source Scope source.
     * @return void
     */
    public function enter( $order, $context, $source = '' ) {
        unset( $order, $source );
        $code = is_array( $context ) && isset( $context['code'] )
            ? strtolower( trim( (string) $context['code'] ) )
            : '';

        $this->codes[] = preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ? $code : '';
    }

    /**
     * @param mixed $context Previous scope context.
     * @param mixed $source Scope source.
     * @return void
     */
    public function leave( $context = array(), $source = '' ) {
        unset( $context, $source );
        if ( ! empty( $this->codes ) ) {
            array_pop( $this->codes );
        }
    }

    /**
     * @param mixed  $code Current effective language code.
     * @param string $key Translation key.
     * @param string $source Source text.
     * @return string
     */
    public function filter_language_code( $code, $key = '', $source = '' ) {
        unset( $key, $source );
        if ( empty( $this->codes ) ) {
            return (string) $code;
        }

        $scoped = end( $this->codes );
        return is_string( $scoped ) && '' !== $scoped ? $scoped : (string) $code;
    }
}
