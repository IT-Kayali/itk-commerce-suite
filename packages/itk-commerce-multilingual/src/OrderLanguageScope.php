<?php
/**
 * Isolated order-language rendering scopes for WooCommerce emails/documents.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class OrderLanguageScope {
    /** @var LanguageContext */
    private $context;

    /** @var WooCommerceLanguageContext */
    private $woocommerce_context;

    /** @var array<int,array<string,mixed>> */
    private $stack = array();

    /** @var array<string,bool> */
    private $registered_notification_hooks = array();

    /**
     * @param LanguageContext             $context Current language context.
     * @param WooCommerceLanguageContext  $woocommerce_context Order-language snapshot service.
     */
    public function __construct( LanguageContext $context, WooCommerceLanguageContext $woocommerce_context ) {
        $this->context             = $context;
        $this->woocommerce_context = $woocommerce_context;
    }

    /** @return void */
    public function register() {
        add_filter( 'woocommerce_email_actions', array( $this, 'filter_email_actions' ), 1 );
        add_filter( 'woocommerce_allow_switching_email_locale', array( $this, 'allow_woocommerce_email_locale_switch' ), 1, 2 );
        add_filter( 'woocommerce_allow_restoring_email_locale', array( $this, 'allow_woocommerce_email_locale_restore' ), 1, 2 );
        add_action( 'woocommerce_before_resend_order_emails', array( $this, 'before_manual_resend' ), 1, 2 );
        add_action( 'woocommerce_after_resend_order_email', array( $this, 'after_manual_resend' ), 9999, 2 );
        add_action( 'shutdown', array( $this, 'restore_all' ), PHP_INT_MAX );
        add_filter( 'itk_commerce_order_language_scope', array( $this, 'filter_scope' ) );

        // Register current WooCommerce order notification actions immediately.
        // The woocommerce_email_actions filter below also discovers extension
        // actions before WooCommerce attaches its direct/deferred dispatcher.
        $this->register_notification_actions( $this->default_order_email_actions() );
    }

    /**
     * Register notification wrappers for the current WooCommerce email-action
     * list. Only actions from which an order can be resolved become active
     * language scopes, so stock/customer-account notifications remain untouched.
     *
     * @param mixed $actions Transactional email parent actions.
     * @return mixed
     */
    public function filter_email_actions( $actions ) {
        if ( is_array( $actions ) ) {
            $this->register_notification_actions( $actions );
        }
        return $actions;
    }

    /**
     * Run arbitrary rendering logic in the frozen order language and always
     * restore the previous WordPress locale + Commerce language context.
     * Intended for invoices, delivery notes, return slips and integrations.
     *
     * @param mixed    $order WC_Order-like object.
     * @param callable $callback Rendering callback. Receives normalized context.
     * @return mixed
     */
    public function run( $order, callable $callback ) {
        $scope = $this->enter( $order, 'programmatic' );
        try {
            return $callback( $scope['order_context'] );
        } finally {
            $this->leave();
        }
    }

    /**
     * Begin a rendering scope. An inactive frame is still pushed so nested
     * before/after notification hooks remain perfectly balanced.
     *
     * @param mixed  $order WC_Order-like object.
     * @param string $source Scope source.
     * @return array<string,mixed>
     */
    public function enter( $order, $source = 'unknown' ) {
        $order_context = $this->woocommerce_context->order_language_context( $order );
        $previous_code = $this->context->code();
        $locale        = isset( $order_context['locale'] ) ? trim( (string) $order_context['locale'] ) : '';
        $code          = isset( $order_context['code'] ) ? strtolower( trim( (string) $order_context['code'] ) ) : '';
        $active        = '' !== $code || '' !== $locale;
        $locale_switched = false;

        if ( $active && '' !== $code ) {
            $this->context->select( $code );
        }

        if ( $active && '' !== $locale && function_exists( 'switch_to_locale' ) ) {
            $locale_switched = (bool) switch_to_locale( $locale );
        }

        $frame = array(
            'active'          => $active,
            'source'          => sanitize_key( $source ),
            'previous_code'   => $previous_code,
            'locale_switched' => $locale_switched,
            'order_context'   => $order_context,
        );
        $this->stack[] = $frame;

        if ( $active ) {
            do_action( 'itk_commerce_order_language_scope_entered', $order, $order_context, $frame['source'] );
        }

        return $frame;
    }

    /**
     * Restore one scope frame.
     *
     * @return void
     */
    public function leave() {
        if ( empty( $this->stack ) ) {
            return;
        }

        $frame = array_pop( $this->stack );
        if ( empty( $frame['active'] ) ) {
            return;
        }

        if ( ! empty( $frame['locale_switched'] ) && function_exists( 'restore_previous_locale' ) ) {
            restore_previous_locale();
        }

        if ( isset( $frame['previous_code'] ) && '' !== (string) $frame['previous_code'] ) {
            $this->context->select( (string) $frame['previous_code'] );
        }

        do_action(
            'itk_commerce_order_language_scope_left',
            isset( $frame['order_context'] ) ? $frame['order_context'] : array(),
            isset( $frame['source'] ) ? $frame['source'] : 'unknown'
        );
    }

    /** @return void */
    public function restore_all() {
        while ( ! empty( $this->stack ) ) {
            $this->leave();
        }
    }

    /** @return bool */
    public function is_active() {
        if ( empty( $this->stack ) ) {
            return false;
        }

        $frame = end( $this->stack );
        return is_array( $frame ) && ! empty( $frame['active'] );
    }

    /**
     * WooCommerce customer emails normally switch to the store locale. Inside
     * our explicit frozen-order scope that would overwrite the selected order
     * locale, so leave WooCommerce's own switch disabled until our scope ends.
     *
     * @param mixed $allow Existing decision.
     * @param mixed $email WC_Email instance.
     * @return bool
     */
    public function allow_woocommerce_email_locale_switch( $allow, $email = null ) {
        unset( $email );
        return $this->is_active() ? false : (bool) $allow;
    }

    /**
     * Matching restore guard for WC_Email::restore_locale(). Our own scope owns
     * the locale stack while active and restores it after the notification.
     *
     * @param mixed $allow Existing decision.
     * @param mixed $email WC_Email instance.
     * @return bool
     */
    public function allow_woocommerce_email_locale_restore( $allow, $email = null ) {
        unset( $email );
        return $this->is_active() ? false : (bool) $allow;
    }

    /**
     * Notification hook callback registered with accepted_args=10.
     *
     * @return void
     */
    public function before_transactional_notification() {
        $args  = func_get_args();
        $hook  = function_exists( 'current_filter' ) ? (string) current_filter() : '';
        $order = $this->find_order( $args, $hook );
        $this->enter( $order, 'woocommerce-email' );
    }

    /** @return void */
    public function after_transactional_notification() {
        $this->leave();
    }

    /**
     * Manual admin resend wrapper. WooCommerce fires this before calling
     * customer_invoice()/new_order trigger directly.
     *
     * @param mixed  $order WC_Order-like object.
     * @param string $email_type Email ID.
     * @return void
     */
    public function before_manual_resend( $order, $email_type = '' ) {
        $this->enter( $order, 'manual-' . sanitize_key( $email_type ) );
    }

    /**
     * @param mixed  $order WC_Order-like object.
     * @param string $email_type Email ID.
     * @return void
     */
    public function after_manual_resend( $order = null, $email_type = '' ) {
        unset( $order, $email_type );
        $this->leave();
    }

    /** @param mixed $scope Existing value. @return OrderLanguageScope */
    public function filter_scope( $scope ) {
        unset( $scope );
        return $this;
    }

    /**
     * @param string[] $actions Parent transactional email actions.
     * @return void
     */
    private function register_notification_actions( array $actions ) {
        foreach ( $actions as $action ) {
            $action = sanitize_key( (string) $action );
            if ( '' === $action ) {
                continue;
            }

            $notification_hook = $action . '_notification';
            if ( isset( $this->registered_notification_hooks[ $notification_hook ] ) ) {
                continue;
            }

            add_action( $notification_hook, array( $this, 'before_transactional_notification' ), 1, 10 );
            add_action( $notification_hook, array( $this, 'after_transactional_notification' ), 9999, 10 );
            $this->registered_notification_hooks[ $notification_hook ] = true;
        }
    }

    /**
     * Order-related parent actions from WooCommerce's current transactional
     * email list. Extension actions are discovered through woocommerce_email_actions.
     *
     * @return string[]
     */
    private function default_order_email_actions() {
        return array(
            'woocommerce_order_status_pending_to_processing',
            'woocommerce_order_status_pending_to_completed',
            'woocommerce_order_status_processing_to_cancelled',
            'woocommerce_order_status_pending_to_failed',
            'woocommerce_order_status_pending_to_on-hold',
            'woocommerce_order_status_failed_to_processing',
            'woocommerce_order_status_failed_to_completed',
            'woocommerce_order_status_failed_to_on-hold',
            'woocommerce_order_status_cancelled_to_processing',
            'woocommerce_order_status_cancelled_to_completed',
            'woocommerce_order_status_cancelled_to_on-hold',
            'woocommerce_order_status_on-hold_to_processing',
            'woocommerce_order_status_on-hold_to_cancelled',
            'woocommerce_order_status_on-hold_to_failed',
            'woocommerce_order_status_completed',
            'woocommerce_order_status_failed',
            'woocommerce_order_fully_refunded',
            'woocommerce_order_partially_refunded',
            'woocommerce_send_review_request',
            'woocommerce_new_customer_note',
        );
    }

    /**
     * Resolve an order object from notification arguments without interpreting
     * arbitrary stock/customer IDs as order IDs.
     *
     * @param array<int,mixed> $args Notification args.
     * @param string           $hook Current notification hook.
     * @return mixed|null
     */
    private function find_order( array $args, $hook ) {
        foreach ( $args as $arg ) {
            if ( $this->looks_like_order( $arg ) ) {
                return $arg;
            }
            if ( is_array( $arg ) ) {
                if ( isset( $arg['order'] ) && $this->looks_like_order( $arg['order'] ) ) {
                    return $arg['order'];
                }
                if ( isset( $arg['order_id'] ) ) {
                    $order = $this->load_order( $arg['order_id'] );
                    if ( null !== $order ) {
                        return $order;
                    }
                }
            }
        }

        if ( ! $this->hook_can_use_order_id( $hook ) ) {
            return null;
        }

        foreach ( $args as $arg ) {
            if ( is_numeric( $arg ) && (int) $arg > 0 ) {
                $order = $this->load_order( $arg );
                if ( null !== $order ) {
                    return $order;
                }
            }
        }

        return null;
    }

    /** @param mixed $value Candidate order. @return bool */
    private function looks_like_order( $value ) {
        return is_object( $value ) && method_exists( $value, 'get_meta' ) && method_exists( $value, 'get_id' );
    }

    /** @param mixed $order_id Candidate order ID. @return mixed|null */
    private function load_order( $order_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return null;
        }

        $order = wc_get_order( max( 0, (int) $order_id ) );
        return $this->looks_like_order( $order ) ? $order : null;
    }

    /** @param string $hook Notification hook. @return bool */
    private function hook_can_use_order_id( $hook ) {
        return 0 === strpos( $hook, 'woocommerce_order_' )
            || 'woocommerce_send_review_request_notification' === $hook
            || 'woocommerce_new_customer_note_notification' === $hook;
    }
}
