<?php
/**
 * Dependency-light WooCommerce session/order language context smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );

    $GLOBALS['itk_wc_language_actions'] = array();
    $GLOBALS['itk_wc_language_filters'] = array();
    $GLOBALS['itk_wc_language_ajax']    = false;

    function get_locale() { return 'de_DE'; }
    function home_url( $path = '' ) {
        $base = 'https://example.test';
        return '' === (string) $path ? $base : $base . '/' . ltrim( (string) $path, '/' );
    }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return (string) $value; }
    function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
        $GLOBALS['itk_wc_language_actions'][ $hook ] = array( $callback, $priority, $args );
    }
    function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
        $GLOBALS['itk_wc_language_filters'][ $hook ] = array( $callback, $priority, $args );
    }
    function do_action() {}
    function wp_doing_ajax() { return (bool) $GLOBALS['itk_wc_language_ajax']; }

    final class ITK_WC_Language_Session {
        public $data = array();
        public function get( $key, $default = null ) { return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default; }
        public function set( $key, $value ) { $this->data[ $key ] = $value; }
    }

    final class ITK_WC_Language_Order {
        public $meta = array();
        public function update_meta_data( $key, $value ) { $this->meta[ $key ] = $value; }
        public function get_meta( $key, $single = true ) { unset( $single ); return array_key_exists( $key, $this->meta ) ? $this->meta[ $key ] : ''; }
    }

    $GLOBALS['itk_wc_language_instance'] = (object) array(
        'session' => new ITK_WC_Language_Session(),
    );

    function WC() { return $GLOBALS['itk_wc_language_instance']; }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageRouter.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/WooCommerceLanguageContext.php';

    function itk_wc_language_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "WooCommerce language context failure: {$message}\n" );
            exit( 1 );
        }
    }

    $schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $config = $schema->normalize(
        array(
            'default'  => 'de',
            'fallback' => 'de',
            'languages' => array(
                array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'direction' => 'ltr', 'enabled' => true ),
                array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
                array( 'code' => 'en', 'locale' => 'en_US', 'label' => 'English', 'direction' => 'ltr', 'enabled' => true ),
            ),
        )
    );

    $_SERVER['REQUEST_URI'] = '/ar/produkt/duft/';
    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );
    $router  = new \ITK\Commerce\Multilingual\LanguageRouter( $context );
    $router->resolve_current_request();

    itk_wc_language_assert( 'ar' === $context->code(), 'Explicit /ar/ route must select Arabic before session synchronization.' );
    itk_wc_language_assert( true === $router->route_context()['explicit_prefix'], 'Route must retain explicit-prefix state for session source-of-truth behavior.' );

    $service = new \ITK\Commerce\Multilingual\WooCommerceLanguageContext( $context, $router );
    $service->register();

    foreach ( array( 'woocommerce_init', 'woocommerce_checkout_create_order', 'woocommerce_store_api_checkout_update_order_meta' ) as $hook ) {
        itk_wc_language_assert( isset( $GLOBALS['itk_wc_language_actions'][ $hook ] ), "Expected WooCommerce language action {$hook} was not registered." );
    }
    foreach ( array( 'itk_commerce_woocommerce_session_language', 'itk_commerce_order_language_context', 'itk_commerce_allow_async_translation_mapping' ) as $hook ) {
        itk_wc_language_assert( isset( $GLOBALS['itk_wc_language_filters'][ $hook ] ), "Expected public language context filter {$hook} was not registered." );
    }

    $service->synchronize_session();
    itk_wc_language_assert( 'ar' === WC()->session->get( 'itk_commerce_language' ), 'Localized storefront route must persist its language in WooCommerce session state.' );
    itk_wc_language_assert( 'ar' === $service->filter_session_language( '' ), 'Public session-language contract must expose the persisted enabled language.' );

    $context->select( 'de' );
    $GLOBALS['itk_wc_language_ajax'] = true;
    $service->synchronize_session();
    itk_wc_language_assert( 'ar' === $context->code(), 'AJAX request must restore the persisted WooCommerce session language instead of using the default context.' );
    itk_wc_language_assert( true === $service->allow_async_translation_mapping( false ), 'Async entity translation must be allowed only after a valid session language is restored.' );
    $GLOBALS['itk_wc_language_ajax'] = false;

    $classic = new ITK_WC_Language_Order();
    $service->capture_classic_order_language( $classic, array() );
    itk_wc_language_assert( 'ar' === $classic->meta['_itk_commerce_language'], 'Classic checkout must capture the session language on the WC_Order object.' );
    itk_wc_language_assert( 'ar' === $classic->meta['_itk_commerce_locale'], 'Classic checkout must freeze the configured WordPress locale.' );
    itk_wc_language_assert( 'rtl' === $classic->meta['_itk_commerce_direction'], 'Classic checkout must freeze text direction for later documents/emails.' );

    $classic_context = $service->order_language_context( $classic );
    itk_wc_language_assert( true === $classic_context['configured'] && 'ar' === $classic_context['code'], 'Configured order language must resolve through the current language catalog.' );

    $store_api = new ITK_WC_Language_Order();
    $service->capture_store_api_order_language( $store_api );
    itk_wc_language_assert( $classic->meta === $store_api->meta, 'Store API and classic checkout must persist the same language snapshot.' );

    WC()->session->set( 'itk_commerce_language', 'invalid!' );
    $context->select( 'de' );
    itk_wc_language_assert( 'de' === $service->effective_language_code(), 'Invalid session state must fall back to the valid current Commerce language context.' );
    itk_wc_language_assert( false === $service->allow_async_translation_mapping( false ), 'Invalid session language must never authorize async translation mapping.' );

    $historical = new ITK_WC_Language_Order();
    $historical->meta = array(
        '_itk_commerce_language'  => 'fr',
        '_itk_commerce_locale'    => 'fr_FR',
        '_itk_commerce_direction' => 'ltr',
    );
    $historical_context = $service->order_language_context( $historical );
    itk_wc_language_assert( false === $historical_context['configured'], 'Historical disabled/removed language must be marked as no longer configured.' );
    itk_wc_language_assert( 'fr' === $historical_context['code'] && 'fr_FR' === $historical_context['locale'], 'Historical order language/locale snapshot must survive configuration changes.' );

    $invalid_order = new \stdClass();
    itk_wc_language_assert( false === $service->capture_order_language( $invalid_order, 'test' ), 'Non-WC_Order values must not be mutated.' );

    echo "WooCommerce language context smoke test passed.\n";
}
