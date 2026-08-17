<?php
/**
 * WooCommerce session and order language persistence.
 *
 * The public localized storefront URL remains the source of truth for normal
 * page requests. WooCommerce session state carries that explicit choice into
 * AJAX/Store API requests, and order meta freezes it for later emails/documents.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class WooCommerceLanguageContext {
    const SESSION_KEY          = 'itk_commerce_language';
    const ORDER_LANGUAGE_META  = '_itk_commerce_language';
    const ORDER_LOCALE_META    = '_itk_commerce_locale';
    const ORDER_DIRECTION_META = '_itk_commerce_direction';

    /** @var LanguageContext */
    private $context;

    /** @var LanguageRouter */
    private $router;

    /** @var string */
    private $session_code = '';

    /**
     * @param LanguageContext $context Current Commerce language context.
     * @param LanguageRouter  $router Public directory-language router.
     */
    public function __construct( LanguageContext $context, LanguageRouter $router ) {
        $this->context = $context;
        $this->router  = $router;
    }

    /** @return void */
    public function register() {
        add_action( 'woocommerce_init', array( $this, 'synchronize_session' ), 40 );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'capture_classic_order_language' ), 20, 2 );
        add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'capture_store_api_order_language' ), 20 );
        add_filter( 'itk_commerce_woocommerce_session_language', array( $this, 'filter_session_language' ) );
        add_filter( 'itk_commerce_order_language_context', array( $this, 'filter_order_language_context' ), 10, 2 );
        add_filter( 'itk_commerce_allow_async_translation_mapping', array( $this, 'allow_async_translation_mapping' ) );
    }

    /**
     * Synchronize the request language with WooCommerce session state.
     *
     * Normal page requests:
     * - explicit /{language}/ URL -> write that language to the session;
     * - unprefixed URL -> write the configured/default request language.
     *
     * Async requests:
     * - restore a previously persisted valid session language into the request
     *   context instead of guessing from the default URL context.
     *
     * @return void
     */
    public function synchronize_session() {
        $session = $this->session();
        if ( null === $session ) {
            $this->session_code = '';
            return;
        }

        $route = $this->router->route_context();

        if ( $this->is_async_request() ) {
            $stored = $this->normalize_enabled_code( $session->get( self::SESSION_KEY, '' ) );
            if ( '' !== $stored ) {
                $this->context->select( $stored );
                $this->session_code = $stored;
                do_action( 'itk_commerce_woocommerce_session_language_restored', $stored, $this->context->current() );
                return;
            }
        }

        $code = ! empty( $route['explicit_prefix'] )
            ? $this->normalize_enabled_code( isset( $route['code'] ) ? $route['code'] : '' )
            : $this->normalize_enabled_code( $this->context->code() );

        if ( '' === $code ) {
            $this->session_code = '';
            return;
        }

        $session->set( self::SESSION_KEY, $code );
        $this->session_code = $code;
        do_action( 'itk_commerce_woocommerce_session_language_persisted', $code, $this->context->current() );
    }

    /**
     * Classic shortcode/template checkout hook. WooCommerce saves the order
     * after this action, so using WC_Order CRUD meta remains HPOS-compatible.
     *
     * @param mixed $order WC_Order-like object.
     * @param mixed $data Checkout data.
     * @return void
     */
    public function capture_classic_order_language( $order, $data = array() ) {
        unset( $data );
        $this->capture_order_language( $order, 'classic' );
    }

    /**
     * Checkout Block / Store API order-meta hook.
     *
     * @param mixed $order WC_Order-like object.
     * @return void
     */
    public function capture_store_api_order_language( $order ) {
        $this->restore_session_language();
        $this->capture_order_language( $order, 'store-api' );
    }

    /**
     * Persist the selected language snapshot on the existing WooCommerce order.
     * No direct post/order-table access is used.
     *
     * @param mixed  $order WC_Order-like object.
     * @param string $source Capture source identifier.
     * @return bool
     */
    public function capture_order_language( $order, $source = 'unknown' ) {
        if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
            return false;
        }

        $code       = $this->effective_language_code();
        $definition = $this->language_definition( $code );
        if ( empty( $definition ) ) {
            return false;
        }

        $locale    = isset( $definition['locale'] ) ? (string) $definition['locale'] : '';
        $direction = isset( $definition['direction'] ) && 'rtl' === $definition['direction'] ? 'rtl' : 'ltr';

        $order->update_meta_data( self::ORDER_LANGUAGE_META, $code );
        $order->update_meta_data( self::ORDER_LOCALE_META, $locale );
        $order->update_meta_data( self::ORDER_DIRECTION_META, $direction );

        do_action(
            'itk_commerce_order_language_captured',
            $order,
            array(
                'code'      => $code,
                'locale'    => $locale,
                'direction' => $direction,
                'source'    => sanitize_key( $source ),
            )
        );

        return true;
    }

    /**
     * Read an order's frozen language snapshot. Historical order locale and
     * direction remain available even if a language is later disabled.
     *
     * @param mixed $order WC_Order-like object.
     * @return array{code:string,locale:string,direction:string,configured:bool}
     */
    public function order_language_context( $order ) {
        if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
            return array(
                'code'       => '',
                'locale'     => '',
                'direction'  => 'ltr',
                'configured' => false,
            );
        }

        $stored_code = strtolower( trim( (string) $order->get_meta( self::ORDER_LANGUAGE_META, true ) ) );
        $definition  = $this->language_definition( $stored_code );

        if ( ! empty( $definition ) ) {
            return array(
                'code'       => $stored_code,
                'locale'     => isset( $definition['locale'] ) ? (string) $definition['locale'] : '',
                'direction'  => isset( $definition['direction'] ) && 'rtl' === $definition['direction'] ? 'rtl' : 'ltr',
                'configured' => true,
            );
        }

        $stored_locale    = trim( (string) $order->get_meta( self::ORDER_LOCALE_META, true ) );
        $stored_direction = 'rtl' === $order->get_meta( self::ORDER_DIRECTION_META, true ) ? 'rtl' : 'ltr';
        $safe_code        = preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $stored_code ) ? $stored_code : '';

        return array(
            'code'       => $safe_code,
            'locale'     => $stored_locale,
            'direction'  => $stored_direction,
            'configured' => false,
        );
    }

    /**
     * Restore a valid WooCommerce session language into the current request.
     *
     * @return string Selected code or empty string.
     */
    public function restore_session_language() {
        $session = $this->session();
        if ( null === $session ) {
            return '';
        }

        $stored = $this->normalize_enabled_code( $session->get( self::SESSION_KEY, '' ) );
        if ( '' === $stored ) {
            return '';
        }

        $this->context->select( $stored );
        $this->session_code = $stored;
        return $stored;
    }

    /** @return string */
    public function effective_language_code() {
        $stored = $this->restore_session_language();
        return '' !== $stored ? $stored : $this->normalize_enabled_code( $this->context->code() );
    }

    /** @param mixed $code Existing value. @return string */
    public function filter_session_language( $code ) {
        unset( $code );
        return $this->effective_language_code();
    }

    /** @param mixed $value Existing context. @param mixed $order Order. @return array<string,mixed> */
    public function filter_order_language_context( $value, $order = null ) {
        unset( $value );
        return $this->order_language_context( $order );
    }

    /**
     * Async product/taxonomy translation is allowed only when a valid language
     * was restored from WooCommerce's customer session.
     *
     * @param mixed $allowed Existing decision.
     * @return bool
     */
    public function allow_async_translation_mapping( $allowed ) {
        if ( true === $allowed ) {
            return true;
        }

        if ( ! $this->is_async_request() ) {
            return false;
        }

        return '' !== $this->restore_session_language();
    }

    /** @return object|null */
    private function session() {
        if ( ! function_exists( 'WC' ) ) {
            return null;
        }

        $woocommerce = WC();
        if ( ! is_object( $woocommerce ) || ! isset( $woocommerce->session ) || ! is_object( $woocommerce->session ) ) {
            return null;
        }

        if ( ! method_exists( $woocommerce->session, 'get' ) || ! method_exists( $woocommerce->session, 'set' ) ) {
            return null;
        }

        return $woocommerce->session;
    }

    /** @param mixed $code Candidate code. @return string */
    private function normalize_enabled_code( $code ) {
        $code = strtolower( str_replace( '_', '-', trim( (string) $code ) ) );
        if ( ! preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ) {
            return '';
        }

        return ! empty( $this->language_definition( $code ) ) ? $code : '';
    }

    /** @param string $code Language code. @return array<string,mixed> */
    private function language_definition( $code ) {
        foreach ( $this->context->enabled_languages() as $language ) {
            if ( isset( $language['code'] ) && $code === (string) $language['code'] ) {
                return $language;
            }
        }
        return array();
    }

    /** @return bool */
    private function is_async_request() {
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return true;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        if ( function_exists( 'WC' ) ) {
            $woocommerce = WC();
            if ( is_object( $woocommerce ) && method_exists( $woocommerce, 'is_store_api_request' ) && $woocommerce->is_store_api_request() ) {
                return true;
            }
        }
        return false;
    }
}
