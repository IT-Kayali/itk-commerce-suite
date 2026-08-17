<?php
/**
 * Dependency-light order-language rendering scope smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );

    $GLOBALS['itk_scope_hooks']          = array();
    $GLOBALS['itk_scope_current_filter'] = '';
    $GLOBALS['itk_scope_locale']         = 'de_DE';
    $GLOBALS['itk_scope_locale_stack']   = array();
    $GLOBALS['itk_scope_orders']         = array();

    function get_locale() { return $GLOBALS['itk_scope_locale']; }
    function home_url( $path = '' ) { return 'https://example.test/' . ltrim( (string) $path, '/' ); }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return (string) $value; }

    function itk_scope_add_hook( $type, $hook, $callback, $priority, $args ) {
        if ( ! isset( $GLOBALS['itk_scope_hooks'][ $type ][ $hook ] ) ) {
            $GLOBALS['itk_scope_hooks'][ $type ][ $hook ] = array();
        }
        $GLOBALS['itk_scope_hooks'][ $type ][ $hook ][] = array(
            'callback' => $callback,
            'priority' => (int) $priority,
            'args'     => (int) $args,
        );
        usort( $GLOBALS['itk_scope_hooks'][ $type ][ $hook ], static function ( $a, $b ) {
            return $a['priority'] <=> $b['priority'];
        } );
    }

    function add_action( $hook, $callback, $priority = 10, $args = 1 ) { itk_scope_add_hook( 'action', $hook, $callback, $priority, $args ); }
    function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { itk_scope_add_hook( 'filter', $hook, $callback, $priority, $args ); }

    function do_action( $hook, ...$args ) {
        $previous = $GLOBALS['itk_scope_current_filter'];
        $GLOBALS['itk_scope_current_filter'] = $hook;
        foreach ( $GLOBALS['itk_scope_hooks']['action'][ $hook ] ?? array() as $entry ) {
            call_user_func_array( $entry['callback'], array_slice( $args, 0, $entry['args'] ) );
        }
        $GLOBALS['itk_scope_current_filter'] = $previous;
    }

    function apply_filters( $hook, $value, ...$args ) {
        $previous = $GLOBALS['itk_scope_current_filter'];
        $GLOBALS['itk_scope_current_filter'] = $hook;
        foreach ( $GLOBALS['itk_scope_hooks']['filter'][ $hook ] ?? array() as $entry ) {
            $call_args = array_merge( array( $value ), $args );
            $value = call_user_func_array( $entry['callback'], array_slice( $call_args, 0, $entry['args'] ) );
        }
        $GLOBALS['itk_scope_current_filter'] = $previous;
        return $value;
    }

    function current_filter() { return $GLOBALS['itk_scope_current_filter']; }

    function switch_to_locale( $locale ) {
        $locale = (string) $locale;
        if ( '' === $locale || $locale === $GLOBALS['itk_scope_locale'] ) {
            return false;
        }
        $GLOBALS['itk_scope_locale_stack'][] = $GLOBALS['itk_scope_locale'];
        $GLOBALS['itk_scope_locale'] = $locale;
        return true;
    }

    function restore_previous_locale() {
        if ( empty( $GLOBALS['itk_scope_locale_stack'] ) ) {
            return false;
        }
        $GLOBALS['itk_scope_locale'] = array_pop( $GLOBALS['itk_scope_locale_stack'] );
        return true;
    }

    function wc_get_order( $order_id ) {
        $order_id = (int) $order_id;
        return $GLOBALS['itk_scope_orders'][ $order_id ] ?? false;
    }

    final class ITK_Scope_Order {
        private $id;
        public $meta = array();
        public function __construct( $id, array $meta ) { $this->id = (int) $id; $this->meta = $meta; }
        public function get_id() { return $this->id; }
        public function get_meta( $key, $single = true ) { unset( $single ); return $this->meta[ $key ] ?? ''; }
    }

    final class ITK_Scope_Product {
        private $id;
        public function __construct( $id ) { $this->id = (int) $id; }
        public function get_id() { return $this->id; }
    }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageRouter.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/WooCommerceLanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/OrderTranslationLanguageBridge.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/OrderLanguageScope.php';

    function itk_scope_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Order language scope failure: {$message}\n" );
            exit( 1 );
        }
    }

    $language_schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $config = $language_schema->normalize(
        array(
            'default'  => 'de',
            'fallback' => 'de',
            'languages' => array(
                array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'direction' => 'ltr', 'enabled' => true ),
                array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
            ),
        )
    );

    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );
    $router  = new \ITK\Commerce\Multilingual\LanguageRouter( $context );
    $wc_context = new \ITK\Commerce\Multilingual\WooCommerceLanguageContext( $context, $router );
    $bridge = new \ITK\Commerce\Multilingual\OrderTranslationLanguageBridge();
    $bridge->register();
    $scope = new \ITK\Commerce\Multilingual\OrderLanguageScope( $context, $wc_context );
    $scope->register();

    itk_scope_assert( isset( $GLOBALS['itk_scope_hooks']['action']['woocommerce_order_status_pending_to_processing_notification'] ), 'Known transactional order notification must be wrapped.' );
    itk_scope_assert( isset( $GLOBALS['itk_scope_hooks']['action']['woocommerce_before_resend_order_emails'] ), 'Manual resend must enter the order language scope.' );
    itk_scope_assert( isset( $GLOBALS['itk_scope_hooks']['filter']['woocommerce_allow_switching_email_locale'] ), 'WooCommerce email locale switch guard must be registered.' );
    itk_scope_assert( isset( $GLOBALS['itk_scope_hooks']['filter']['itk_commerce_order_language_scope'] ), 'Programmatic document scope must be publicly discoverable.' );

    $arabic_order = new ITK_Scope_Order(
        100,
        array(
            '_itk_commerce_language'  => 'ar',
            '_itk_commerce_locale'    => 'ar',
            '_itk_commerce_direction' => 'rtl',
        )
    );
    $GLOBALS['itk_scope_orders'][100] = $arabic_order;

    $result = $scope->run( $arabic_order, static function ( $order_context ) use ( $context ) {
        itk_scope_assert( 'ar' === $context->code(), 'Configured order scope must select the order Commerce language.' );
        itk_scope_assert( 'ar' === get_locale(), 'Configured order scope must switch the WordPress locale.' );
        itk_scope_assert( 'ar' === $order_context['code'] && 'rtl' === $order_context['direction'], 'Callback must receive the frozen order context.' );
        itk_scope_assert( 'ar' === apply_filters( 'itk_commerce_translation_language_code', 'de', 'test.key', 'Source' ), 'Translation lookup must follow the active order scope.' );
        itk_scope_assert( false === apply_filters( 'woocommerce_allow_switching_email_locale', true, new \stdClass() ), 'WooCommerce must not overwrite the explicit order locale while scope is active.' );
        itk_scope_assert( false === apply_filters( 'woocommerce_allow_restoring_email_locale', true, new \stdClass() ), 'WooCommerce must not restore its own locale stack while our order scope owns it.' );
        return 'rendered-ar';
    } );

    itk_scope_assert( 'rendered-ar' === $result, 'Programmatic scope must return the renderer result.' );
    itk_scope_assert( 'de' === $context->code(), 'Commerce language must restore after rendering.' );
    itk_scope_assert( 'de_DE' === get_locale(), 'WordPress locale must restore after rendering.' );
    itk_scope_assert( 'de' === apply_filters( 'itk_commerce_translation_language_code', 'de', 'test.key', 'Source' ), 'Translation language override must be removed after scope exit.' );
    itk_scope_assert( true === apply_filters( 'woocommerce_allow_switching_email_locale', true, new \stdClass() ), 'WooCommerce locale behavior must restore outside an order scope.' );

    $historical_order = new ITK_Scope_Order(
        200,
        array(
            '_itk_commerce_language'  => 'fr',
            '_itk_commerce_locale'    => 'fr_FR',
            '_itk_commerce_direction' => 'ltr',
        )
    );
    $scope->run( $historical_order, static function ( $order_context ) use ( $context ) {
        itk_scope_assert( false === $order_context['configured'], 'Removed language must be recognized as historical.' );
        itk_scope_assert( 'fr_FR' === get_locale(), 'Historical order must still use its frozen WordPress locale.' );
        itk_scope_assert( 'fr' === apply_filters( 'itk_commerce_translation_language_code', $context->code(), 'historic.key', 'Source' ), 'Historical disabled language must still override translation lookup inside its order scope.' );
    } );
    itk_scope_assert( 'de_DE' === get_locale(), 'Historical scope must restore the previous locale.' );

    $exception_restored = false;
    try {
        $scope->run( $arabic_order, static function () {
            throw new \RuntimeException( 'expected-test-exception' );
        } );
    } catch ( \RuntimeException $exception ) {
        $exception_restored = 'expected-test-exception' === $exception->getMessage();
    }
    itk_scope_assert( $exception_restored, 'Programmatic scope must propagate renderer exceptions.' );
    itk_scope_assert( 'de_DE' === get_locale() && 'de' === $context->code(), 'Locale/context must restore through finally after renderer exceptions.' );

    $GLOBALS['itk_scope_current_filter'] = 'woocommerce_order_status_pending_to_processing_notification';
    $scope->before_transactional_notification( 100 );
    itk_scope_assert( $scope->is_active() && 'ar' === get_locale(), 'Transactional notification must resolve an order ID and enter its language scope.' );
    $scope->after_transactional_notification( 100 );
    itk_scope_assert( ! $scope->is_active() && 'de_DE' === get_locale(), 'Transactional notification after-hook must restore the scope.' );
    $GLOBALS['itk_scope_current_filter'] = '';

    $scope->before_manual_resend( $arabic_order, 'customer_invoice' );
    itk_scope_assert( $scope->is_active() && 'ar' === get_locale(), 'Manual customer-invoice resend must use frozen order language.' );
    $scope->after_manual_resend( $arabic_order, 'customer_invoice' );
    itk_scope_assert( ! $scope->is_active() && 'de_DE' === get_locale(), 'Manual resend must restore the previous locale.' );

    $scope->filter_email_actions( array( 'woocommerce_low_stock' ) );
    $GLOBALS['itk_scope_current_filter'] = 'woocommerce_low_stock_notification';
    $scope->before_transactional_notification( new ITK_Scope_Product( 100 ) );
    itk_scope_assert( ! $scope->is_active(), 'Non-order transactional email must push only an inactive balanced frame.' );
    itk_scope_assert( true === $scope->allow_woocommerce_email_locale_switch( true ), 'Non-order emails must retain normal WooCommerce locale behavior.' );
    $scope->after_transactional_notification();
    $GLOBALS['itk_scope_current_filter'] = '';

    echo "Order language scope smoke test passed.\n";
}
