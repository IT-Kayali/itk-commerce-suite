<?php
/**
 * Commerce Core application object.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core;

use ITK\Commerce\Core\Modules\ModuleRegistry;
use ITK\Commerce\Core\Profiles\ProfileRepository;
use ITK\Commerce\Core\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

final class Core {
    /** @var Core|null */
    private static $instance = null;

    /** @var bool */
    private $booted = false;

    /** @var SettingsRepository */
    private $settings;

    /** @var ProfileRepository */
    private $profiles;

    /** @var ModuleRegistry */
    private $modules;

    /**
     * @return Core
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Start the reusable product core and then boot enabled modules in a
     * dependency-safe order.
     *
     * @return void
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
         * Allow separately installed packages to register module instances.
         *
         * @param ModuleRegistry $registry Module registry.
         */
        do_action( 'itk_commerce_register_modules', $this->modules );

        $this->modules->boot( $this->enabled_module_ids() );

        if ( $this->modules->errors() ) {
            add_action( 'admin_notices', array( $this, 'render_module_notices' ) );
        }

        /**
         * Fires after the IT-Kayali Commerce Core has completed its bootstrap.
         *
         * @param Core $core Core application instance.
         */
        do_action( 'itk_commerce_core_loaded', $this );
    }

    /**
     * @return SettingsRepository
     */
    public function settings() {
        return $this->settings;
    }

    /**
     * @return ProfileRepository
     */
    public function profiles() {
        return $this->profiles;
    }

    /**
     * @return ModuleRegistry
     */
    public function modules() {
        return $this->modules;
    }

    /**
     * Explain the missing platform dependency without causing a fatal error.
     *
     * @return void
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
     * Surface module dependency/environment failures to authorized users.
     *
     * @return void
     */
    public function render_module_notices() {
        if ( ! current_user_can( 'itk_manage_modules' ) && ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        foreach ( $this->modules->errors() as $module_id => $errors ) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html(
                    sprintf(
                        /* translators: 1: module identifier, 2: machine-readable error list. */
                        __( 'Commerce module %1$s was not loaded: %2$s', 'itk-commerce-core' ),
                        $module_id,
                        implode( ', ', $errors )
                    )
                )
            );
        }
    }

    /**
     * Resolve the enabled module list. If an active customer profile defines a
     * module list it becomes authoritative; otherwise the core settings list is
     * used. This keeps customer-specific choices out of generic package code.
     *
     * @return string[]
     */
    private function enabled_module_ids() {
        $enabled    = $this->settings->enabled_modules();
        $profile_id = $this->settings->active_profile_id();
        $profile    = $profile_id ? $this->profiles->get( $profile_id ) : null;

        if ( is_array( $profile ) && isset( $profile['modules']['enabled'] ) && is_array( $profile['modules']['enabled'] ) ) {
            $enabled = $profile['modules']['enabled'];
        }

        /**
         * Filter enabled module identifiers before dependency resolution.
         *
         * @param string[]                $enabled Enabled module identifiers.
         * @param array<string,mixed>|null $profile Active customer profile.
         */
        $enabled = apply_filters( 'itk_commerce_enabled_modules', $enabled, $profile );

        return is_array( $enabled )
            ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $enabled ) ) ) )
            : array();
    }

    /**
     * Prevent direct construction outside the singleton accessor.
     */
    private function __construct() {
        $this->settings = new SettingsRepository();
        $this->profiles = new ProfileRepository();
        $this->modules  = new ModuleRegistry( VERSION );
    }
}
