<?php
/**
 * Commerce Core application object.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core;

defined( 'ABSPATH' ) || exit;

final class Core {
    /**
     * Singleton instance.
     *
     * @var Core|null
     */
    private static $instance = null;

    /**
     * Whether the core has already booted.
     *
     * @var bool
     */
    private $booted = false;

    /**
     * Return the shared core instance.
     *
     * @return Core
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Start the reusable product core.
     *
     * Module discovery, settings, roles and migrations will be attached here as
     * Phase 1 is implemented. Keeping this bootstrap intentionally small makes
     * package boundaries explicit from the beginning.
     */
    public function boot() {
        if ( $this->booted ) {
            return;
        }

        $this->booted = true;

        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'render_woocommerce_notice' ) );
        }

        /**
         * Fires after the IT-Kayali Commerce Core has completed its base bootstrap.
         *
         * @param Core $core Core application instance.
         */
        do_action( 'itk_commerce_core_loaded', $this );
    }

    /**
     * Explain the missing platform dependency without causing a fatal error.
     */
    public function render_woocommerce_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__( 'IT-Kayali Commerce Core requires WooCommerce for commerce features.', 'itk-commerce-core' );
        echo '</p></div>';
    }

    /**
     * Prevent direct construction outside the singleton accessor.
     */
    private function __construct() {}
}
