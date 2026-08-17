<?php
/**
 * Central WordPress admin control center for the Commerce Suite.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Admin;

use ITK\Commerce\Core\Core;
use ITK\Commerce\Core\Profiles\ProfileSchema;

defined( 'ABSPATH' ) || exit;

final class AdminHub {
    const MENU_SLUG = 'itk-commerce';

    /** @var Core */
    private $core;

    /** @param Core $core Core application. */
    public function __construct( Core $core ) {
        $this->core = $core;
    }

    /** @return void */
    public function register() {
        add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_admin_settings', array( $this, 'save_settings' ) );
        add_action( 'admin_post_itk_commerce_profile_action', array( $this, 'profile_action' ) );
        add_action( 'admin_post_itk_commerce_export_profile', array( $this, 'export_profile' ) );
    }

    /** @return void */
    public function register_menu() {
        add_menu_page(
            __( 'IT-Kayali Commerce Suite', 'itk-commerce-core' ),
            __( 'Commerce Suite', 'itk-commerce-core' ),
            'itk_manage_commerce',
            self::MENU_SLUG,
            array( $this, 'render_overview' ),
            'dashicons-store',
            56
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Commerce Suite Overview', 'itk-commerce-core' ),
            __( 'Overview', 'itk-commerce-core' ),
            'itk_manage_commerce',
            self::MENU_SLUG,
            array( $this, 'render_overview' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Commerce Suite Settings', 'itk-commerce-core' ),
            __( 'Settings', 'itk-commerce-core' ),
            'itk_manage_commerce',
            'itk-commerce-settings',
            array( $this, 'render_settings' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Commerce Modules', 'itk-commerce-core' ),
            __( 'Modules', 'itk-commerce-core' ),
            'itk_manage_modules',
            'itk-commerce-modules',
            array( $this, 'render_modules' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Customer Profiles', 'itk-commerce-core' ),
            __( 'Customer Profiles', 'itk-commerce-core' ),
            'itk_manage_profiles',
            'itk-commerce-profiles',
            array( $this, 'render_profiles' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Design & Layouts', 'itk-commerce-core' ),
            __( 'Design & Layouts', 'itk-commerce-core' ),
            'itk_manage_design',
            'itk-commerce-design',
            array( $this, 'render_design' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'System Status', 'itk-commerce-core' ),
            __( 'System Status', 'itk-commerce-core' ),
            'itk_manage_commerce',
            'itk-commerce-system',
            array( $this, 'render_system' )
        );

        /**
         * Allow optional modules to attach their own admin entries below the
         * stable Commerce Suite top-level menu without Core knowing them.
         *
         * @param string   $menu_slug Stable parent menu slug.
         * @param AdminHub $hub       Admin hub instance.
         */
        do_action( 'itk_commerce_admin_menu', self::MENU_SLUG, $this );
    }

    /** @param string $hook_suffix Current admin screen. @return void */
    public function enqueue_assets( $hook_suffix ) {
        unset( $hook_suffix );

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( 0 !== strpos( $page, 'itk-commerce' ) ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-admin-hub',
            plugins_url( 'assets/admin/admin-hub.css', \ITK\Commerce\Core\FILE ),
            array(),
            \ITK\Commerce\Core\VERSION
        );
    }

    /** @return void */
    public function render_overview() {
        $this->require_capability( 'itk_manage_commerce' );

        $profile = $this->active_profile();
        $modules = $this->core->modules()->all();
        $booted  = $this->core->modules()->booted();
        ?>
        <div class="wrap itk-admin-hub">
            <?php $this->render_header( __( 'Commerce Suite', 'itk-commerce-core' ), __( 'Central control center for the reusable IT-Kayali WooCommerce platform.', 'itk-commerce-core' ) ); ?>

            <div class="itk-admin-stat-grid">
                <?php $this->stat_card( __( 'Core version', 'itk-commerce-core' ), \ITK\Commerce\Core\VERSION, 'dashicons-admin-plugins' ); ?>
                <?php $this->stat_card( __( 'Active profile', 'itk-commerce-core' ), $profile ? $profile['name'] : __( 'Not selected', 'itk-commerce-core' ), 'dashicons-id' ); ?>
                <?php $this->stat_card( __( 'Installed modules', 'itk-commerce-core' ), (string) count( $modules ), 'dashicons-screenoptions' ); ?>
                <?php $this->stat_card( __( 'Loaded modules', 'itk-commerce-core' ), (string) count( $booted ), 'dashicons-yes-alt' ); ?>
            </div>

            <div class="itk-admin-grid">
                <?php
                $this->launch_card(
                    __( 'General settings', 'itk-commerce-core' ),
                    __( 'Choose the active customer profile and manage the global Commerce Suite state.', 'itk-commerce-core' ),
                    admin_url( 'admin.php?page=itk-commerce-settings' ),
                    __( 'Open settings', 'itk-commerce-core' ),
                    'dashicons-admin-generic'
                );

                if ( current_user_can( 'itk_manage_design' ) ) {
                    $this->launch_card(
                        __( 'Design & Layouts', 'itk-commerce-core' ),
                        __( 'Open Header/Footer, Shop, Product, Cart, Checkout, product-card and menu builders from one place.', 'itk-commerce-core' ),
                        admin_url( 'admin.php?page=itk-commerce-design' ),
                        __( 'Open design tools', 'itk-commerce-core' ),
                        'dashicons-art'
                    );
                }

                if ( current_user_can( 'itk_manage_modules' ) ) {
                    $this->launch_card(
                        __( 'Modules', 'itk-commerce-core' ),
                        __( 'See installed packages, loaded state, dependencies and profile enablement.', 'itk-commerce-core' ),
                        admin_url( 'admin.php?page=itk-commerce-modules' ),
                        __( 'Manage modules', 'itk-commerce-core' ),
                        'dashicons-screenoptions'
                    );
                }

                if ( current_user_can( 'itk_manage_profiles' ) ) {
                    $this->launch_card(
                        __( 'Customer Profiles', 'itk-commerce-core' ),
                        __( 'Create, duplicate, activate and export portable white-label customer configurations.', 'itk-commerce-core' ),
                        admin_url( 'admin.php?page=itk-commerce-profiles' ),
                        __( 'Manage profiles', 'itk-commerce-core' ),
                        'dashicons-groups'
                    );
                }

                $this->launch_card(
                    __( 'System status', 'itk-commerce-core' ),
                    __( 'Review WordPress, PHP, WooCommerce, HPOS and module compatibility information.', 'itk-commerce-core' ),
                    admin_url( 'admin.php?page=itk-commerce-system' ),
                    __( 'View system status', 'itk-commerce-core' ),
                    'dashicons-heart'
                );
                ?>
            </div>
        </div>
        <?php
    }

    /** @return void */
    public function render_settings() {
        $this->require_capability( 'itk_manage_commerce' );

        $settings  = $this->core->settings()->all();
        $profiles  = $this->core->profiles()->all();
        $updated   = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $profile_id = isset( $settings['active_profile_id'] ) ? sanitize_key( $settings['active_profile_id'] ) : '';
        ?>
        <div class="wrap itk-admin-hub">
            <?php $this->render_header( __( 'Settings', 'itk-commerce-core' ), __( 'Global Core settings stay deliberately small. Customer-facing design and module configuration live in the active customer profile.', 'itk-commerce-core' ) ); ?>
            <?php if ( $updated ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Commerce Suite settings saved.', 'itk-commerce-core' ); ?></p></div><?php endif; ?>

            <form class="itk-admin-panel" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_admin_settings">
                <?php wp_nonce_field( 'itk_commerce_save_admin_settings' ); ?>

                <div class="itk-admin-panel__heading">
                    <div><h2><?php esc_html_e( 'General', 'itk-commerce-core' ); ?></h2><p><?php esc_html_e( 'Select which white-label customer profile is currently authoritative for module and presentation configuration.', 'itk-commerce-core' ); ?></p></div>
                </div>

                <div class="itk-admin-form-row">
                    <label for="itk-active-profile"><strong><?php esc_html_e( 'Active customer profile', 'itk-commerce-core' ); ?></strong><span><?php esc_html_e( 'Changing this does not modify products, customers, orders or media.', 'itk-commerce-core' ); ?></span></label>
                    <select id="itk-active-profile" name="active_profile_id">
                        <option value=""><?php esc_html_e( 'No profile selected', 'itk-commerce-core' ); ?></option>
                        <?php foreach ( $profiles as $id => $profile ) : ?>
                            <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $profile_id, $id ); ?>><?php echo esc_html( isset( $profile['name'] ) ? $profile['name'] : $id ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="itk-admin-savebar"><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save settings', 'itk-commerce-core' ); ?></button></div>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function render_modules() {
        $this->require_capability( 'itk_manage_modules' );

        $installed = $this->core->modules()->all();
        $booted    = $this->core->modules()->booted();
        $errors    = $this->core->modules()->errors();
        $enabled   = $this->enabled_module_ids();
        $updated   = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap itk-admin-hub">
            <?php $this->render_header( __( 'Modules', 'itk-commerce-core' ), __( 'Enable only the independently installed Commerce Suite packages required by the active customer profile.', 'itk-commerce-core' ) ); ?>
            <?php if ( $updated ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Module configuration saved. Module boot changes take effect on the next request.', 'itk-commerce-core' ); ?></p></div><?php endif; ?>

            <form class="itk-admin-panel" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_admin_settings">
                <input type="hidden" name="section" value="modules">
                <?php wp_nonce_field( 'itk_commerce_save_admin_settings' ); ?>

                <div class="itk-module-list">
                    <?php if ( empty( $installed ) ) : ?>
                        <p><?php esc_html_e( 'No separately installed Commerce Suite modules were detected.', 'itk-commerce-core' ); ?></p>
                    <?php endif; ?>
                    <?php foreach ( $installed as $id => $module ) : ?>
                        <?php
                        $requirements = $module->requirements();
                        $is_enabled   = in_array( $id, $enabled, true );
                        $is_booted    = in_array( $id, $booted, true );
                        ?>
                        <article class="itk-module-card">
                            <div class="itk-module-card__main">
                                <div>
                                    <h2><?php echo esc_html( $this->module_label( $id ) ); ?></h2>
                                    <code><?php echo esc_html( $id ); ?></code>
                                </div>
                                <span class="itk-status <?php echo $is_booted ? 'is-good' : ( $is_enabled ? 'is-warning' : 'is-muted' ); ?>"><?php echo esc_html( $is_booted ? __( 'Loaded', 'itk-commerce-core' ) : ( $is_enabled ? __( 'Enabled / not loaded', 'itk-commerce-core' ) : __( 'Disabled', 'itk-commerce-core' ) ) ); ?></span>
                            </div>
                            <div class="itk-module-card__meta">
                                <span><?php echo esc_html( sprintf( __( 'Version %s', 'itk-commerce-core' ), $module->version() ) ); ?></span>
                                <?php if ( ! empty( $requirements['woocommerce'] ) ) : ?><span><?php echo esc_html( sprintf( __( 'WooCommerce ≥ %s', 'itk-commerce-core' ), $requirements['woocommerce'] ) ); ?></span><?php endif; ?>
                            </div>
                            <?php if ( ! empty( $errors[ $id ] ) ) : ?><p class="itk-module-card__error"><?php echo esc_html( implode( ', ', $errors[ $id ] ) ); ?></p><?php endif; ?>
                            <label class="itk-admin-switch"><input type="checkbox" name="enabled_modules[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( $is_enabled ); ?>><span><?php esc_html_e( 'Enabled for active profile', 'itk-commerce-core' ); ?></span></label>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="itk-admin-savebar"><a class="button" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Manage WordPress plugins', 'itk-commerce-core' ); ?></a><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save modules', 'itk-commerce-core' ); ?></button></div>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function render_profiles() {
        $this->require_capability( 'itk_manage_profiles' );

        $profiles  = $this->core->profiles()->all();
        $active_id = $this->core->settings()->active_profile_id();
        ?>
        <div class="wrap itk-admin-hub">
            <?php $this->render_header( __( 'Customer Profiles', 'itk-commerce-core' ), __( 'Portable white-label configuration belongs here; products, orders, customers and secrets do not.', 'itk-commerce-core' ) ); ?>

            <section class="itk-admin-panel">
                <div class="itk-admin-panel__heading"><div><h2><?php esc_html_e( 'Create profile', 'itk-commerce-core' ); ?></h2><p><?php esc_html_e( 'Start a clean reusable customer configuration. You can then configure layouts and modules without touching generic product code.', 'itk-commerce-core' ); ?></p></div></div>
                <form class="itk-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="itk_commerce_profile_action"><input type="hidden" name="profile_action" value="create">
                    <?php wp_nonce_field( 'itk_commerce_profile_action' ); ?>
                    <label><span><?php esc_html_e( 'Profile name', 'itk-commerce-core' ); ?></span><input type="text" name="profile_name" required placeholder="Example Customer"></label>
                    <label><span><?php esc_html_e( 'Profile ID', 'itk-commerce-core' ); ?></span><input type="text" name="profile_id" required placeholder="example-customer"></label>
                    <button class="button button-primary" type="submit"><?php esc_html_e( 'Create profile', 'itk-commerce-core' ); ?></button>
                </form>
            </section>

            <div class="itk-profile-list">
                <?php if ( empty( $profiles ) ) : ?><div class="itk-admin-empty"><p><?php esc_html_e( 'No customer profiles exist yet.', 'itk-commerce-core' ); ?></p></div><?php endif; ?>
                <?php foreach ( $profiles as $id => $profile ) : ?>
                    <article class="itk-profile-card <?php echo $id === $active_id ? 'is-active' : ''; ?>">
                        <div><h2><?php echo esc_html( isset( $profile['name'] ) ? $profile['name'] : $id ); ?></h2><code><?php echo esc_html( $id ); ?></code><p><?php echo esc_html( sprintf( __( 'Profile version %s', 'itk-commerce-core' ), isset( $profile['profile_version'] ) ? $profile['profile_version'] : '1.0.0' ) ); ?></p></div>
                        <div class="itk-profile-card__actions">
                            <?php if ( $id === $active_id ) : ?><span class="itk-status is-good"><?php esc_html_e( 'Active', 'itk-commerce-core' ); ?></span><?php else : ?><?php $this->profile_action_button( $id, 'activate', __( 'Activate', 'itk-commerce-core' ) ); ?><?php endif; ?>
                            <?php $this->profile_action_button( $id, 'duplicate', __( 'Duplicate', 'itk-commerce-core' ) ); ?>
                            <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=itk_commerce_export_profile&profile_id=' . rawurlencode( $id ) ), 'itk_commerce_export_profile_' . $id ) ); ?>"><?php esc_html_e( 'Export JSON', 'itk-commerce-core' ); ?></a>
                            <?php if ( $id !== $active_id ) : ?><?php $this->profile_action_button( $id, 'delete', __( 'Delete', 'itk-commerce-core' ), 'button-link-delete' ); ?><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /** @return void */
    public function render_design() {
        $this->require_capability( 'itk_manage_design' );
        ?>
        <div class="wrap itk-admin-hub">
            <?php $this->render_header( __( 'Design & Layouts', 'itk-commerce-core' ), __( 'One launchpad for the installed visual Commerce Suite builders.', 'itk-commerce-core' ) ); ?>
            <div class="itk-admin-grid">
                <?php
                $this->conditional_tool_card( '\\ITK\\Commerce\\Layouts\\Admin\\LayoutBuilderPage', __( 'Header / Footer / Mobile', 'itk-commerce-core' ), __( 'Configure Header models, Footer models, contextual assignments and mobile navigation.', 'itk-commerce-core' ), 'themes.php?page=itk-commerce-layout-builder' );
                $this->conditional_tool_card( '\\ITK\\Commerce\\Layouts\\Admin\\CommerceTemplatePage', __( 'Commerce Templates', 'itk-commerce-core' ), __( 'Configure Shop, Product, Cart and Checkout layout models.', 'itk-commerce-core' ), 'themes.php?page=itk-commerce-template-builder' );
                $this->conditional_tool_card( '\\ITK\\Commerce\\Layouts\\Admin\\ProductCardPage', __( 'Product Cards', 'itk-commerce-core' ), __( 'Configure product-card model, image ratio, badges, actions and hover treatment.', 'itk-commerce-core' ), 'themes.php?page=itk-commerce-product-cards' );
                $this->conditional_tool_card( '\\ITK\\Commerce\\Layouts\\Admin\\MegaMenuContentPage', __( 'Mega Menu', 'itk-commerce-core' ), __( 'Build rich menu panels with links, categories, products, images and banners.', 'itk-commerce-core' ), 'themes.php?page=itk-commerce-mega-menu-content' );
                $this->conditional_tool_card( '\\ITK\\Commerce\\SearchFilter\\Admin\\FilterBuilderPage', __( 'Search & Filter', 'itk-commerce-core' ), __( 'Configure portable catalog filters and their visual control types.', 'itk-commerce-core' ), 'admin.php?page=itk-commerce-search-filter' );
                ?>
            </div>
        </div>
        <?php
    }

    /** @return void */
    public function render_system() {
        $this->require_capability( 'itk_manage_commerce' );

        global $wp_version;
        $wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : __( 'Not active', 'itk-commerce-core' );
        $hpos       = __( 'Unavailable', 'itk-commerce-core' );

        if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
            $hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
                ? __( 'Enabled', 'itk-commerce-core' )
                : __( 'Disabled', 'itk-commerce-core' );
        }

        $theme = wp_get_theme();
        ?>
        <div class="wrap itk-admin-hub">
            <?php $this->render_header( __( 'System Status', 'itk-commerce-core' ), __( 'Read-only environment information for compatibility checks and support.', 'itk-commerce-core' ) ); ?>
            <section class="itk-admin-panel">
                <table class="widefat striped itk-system-table"><tbody>
                    <?php $this->system_row( 'PHP', PHP_VERSION ); ?>
                    <?php $this->system_row( 'WordPress', $wp_version ); ?>
                    <?php $this->system_row( 'WooCommerce', $wc_version ); ?>
                    <?php $this->system_row( 'HPOS', $hpos ); ?>
                    <?php $this->system_row( __( 'Active theme', 'itk-commerce-core' ), $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ); ?>
                    <?php $this->system_row( __( 'Core version', 'itk-commerce-core' ), \ITK\Commerce\Core\VERSION ); ?>
                    <?php $this->system_row( __( 'Active profile', 'itk-commerce-core' ), $this->core->settings()->active_profile_id() ?: __( 'None', 'itk-commerce-core' ) ); ?>
                    <?php $this->system_row( __( 'Registered modules', 'itk-commerce-core' ), (string) count( $this->core->modules()->all() ) ); ?>
                    <?php $this->system_row( __( 'Loaded modules', 'itk-commerce-core' ), implode( ', ', $this->core->modules()->booted() ) ?: __( 'None', 'itk-commerce-core' ) ); ?>
                </tbody></table>
            </section>
        </div>
        <?php
    }

    /** @return void */
    public function save_settings() {
        $this->require_capability( 'itk_manage_commerce' );
        check_admin_referer( 'itk_commerce_save_admin_settings' );

        $section  = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : 'general';
        $settings = $this->core->settings()->all();

        if ( 'modules' === $section ) {
            if ( ! current_user_can( 'itk_manage_modules' ) ) {
                wp_die( esc_html__( 'You do not have permission to manage Commerce Suite modules.', 'itk-commerce-core' ) );
            }

            $requested = isset( $_POST['enabled_modules'] ) && is_array( $_POST['enabled_modules'] ) ? wp_unslash( $_POST['enabled_modules'] ) : array();
            $allowed   = array_keys( $this->core->modules()->all() );
            $enabled   = array_values( array_intersect( array_map( 'sanitize_key', $requested ), $allowed ) );
            $settings['modules']['enabled'] = $enabled;
            $this->sync_profile_modules( $settings, $enabled );
            $redirect = admin_url( 'admin.php?page=itk-commerce-modules&updated=1' );
        } else {
            $requested_profile = isset( $_POST['active_profile_id'] ) ? sanitize_key( wp_unslash( $_POST['active_profile_id'] ) ) : '';
            $profiles          = $this->core->profiles()->all();
            $settings['active_profile_id'] = isset( $profiles[ $requested_profile ] ) ? $requested_profile : '';
            $redirect = admin_url( 'admin.php?page=itk-commerce-settings&updated=1' );
        }

        $this->core->settings()->save( $settings );
        wp_safe_redirect( $redirect );
        exit;
    }

    /** @return void */
    public function profile_action() {
        $this->require_capability( 'itk_manage_profiles' );
        check_admin_referer( 'itk_commerce_profile_action' );

        $action     = isset( $_POST['profile_action'] ) ? sanitize_key( wp_unslash( $_POST['profile_action'] ) ) : '';
        $profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
        $profiles   = $this->core->profiles()->all();

        if ( 'create' === $action ) {
            $name = isset( $_POST['profile_name'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_name'] ) ) : '';
            if ( '' !== $profile_id && ! isset( $profiles[ $profile_id ] ) ) {
                $this->core->profiles()->save( $this->blank_profile( $profile_id, $name ?: $profile_id ) );
            }
        } elseif ( 'activate' === $action && isset( $profiles[ $profile_id ] ) ) {
            $settings = $this->core->settings()->all();
            $settings['active_profile_id'] = $profile_id;
            $this->core->settings()->save( $settings );
        } elseif ( 'duplicate' === $action && isset( $profiles[ $profile_id ] ) ) {
            $copy    = $profiles[ $profile_id ];
            $new_id  = $this->unique_profile_id( $profile_id . '-copy', $profiles );
            $copy['profile_id'] = $new_id;
            $copy['name']       = ( isset( $copy['name'] ) ? $copy['name'] : $profile_id ) . ' ' . __( 'Copy', 'itk-commerce-core' );
            $copy['profile_version'] = '1.0.0';
            $this->core->profiles()->save( $copy );
        } elseif ( 'delete' === $action && isset( $profiles[ $profile_id ] ) && $profile_id !== $this->core->settings()->active_profile_id() ) {
            $this->core->profiles()->delete( $profile_id );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=itk-commerce-profiles' ) );
        exit;
    }

    /** @return void */
    public function export_profile() {
        $this->require_capability( 'itk_manage_profiles' );

        $profile_id = isset( $_GET['profile_id'] ) ? sanitize_key( wp_unslash( $_GET['profile_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        check_admin_referer( 'itk_commerce_export_profile_' . $profile_id );

        $profile = $this->core->profiles()->get( $profile_id );
        if ( ! is_array( $profile ) ) {
            wp_die( esc_html__( 'Customer profile not found.', 'itk-commerce-core' ) );
        }

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="itk-commerce-profile-' . $profile_id . '.json"' );
        echo wp_json_encode( $profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intentional JSON download.
        exit;
    }

    /** @param array<string,mixed> $settings Settings. @param string[] $enabled Enabled module IDs. @return void */
    private function sync_profile_modules( array $settings, array $enabled ) {
        $profile_id = isset( $settings['active_profile_id'] ) ? sanitize_key( $settings['active_profile_id'] ) : '';
        $profile    = $profile_id ? $this->core->profiles()->get( $profile_id ) : null;
        if ( ! is_array( $profile ) ) {
            return;
        }

        if ( empty( $profile['modules'] ) || ! is_array( $profile['modules'] ) ) {
            $profile['modules'] = array();
        }
        $profile['modules']['enabled'] = $enabled;
        $this->core->profiles()->save( $profile );
    }

    /** @return string[] */
    private function enabled_module_ids() {
        $enabled    = $this->core->settings()->enabled_modules();
        $profile_id = $this->core->settings()->active_profile_id();
        $profile    = $profile_id ? $this->core->profiles()->get( $profile_id ) : null;
        if ( is_array( $profile ) && isset( $profile['modules']['enabled'] ) && is_array( $profile['modules']['enabled'] ) ) {
            $enabled = $profile['modules']['enabled'];
        }
        return array_values( array_unique( array_filter( array_map( 'sanitize_key', $enabled ) ) ) );
    }

    /** @return array<string,mixed>|null */
    private function active_profile() {
        $id = $this->core->settings()->active_profile_id();
        return $id ? $this->core->profiles()->get( $id ) : null;
    }

    /** @param string $profile_id ID. @param string $name Name. @return array<string,mixed> */
    private function blank_profile( $profile_id, $name ) {
        return array(
            'schema_version'  => ProfileSchema::SCHEMA_VERSION,
            'profile_id'      => sanitize_key( $profile_id ),
            'profile_version' => '1.0.0',
            'name'            => sanitize_text_field( $name ),
            'branding'        => array(),
            'design'          => array(),
            'contacts'        => array(),
            'languages'       => array(),
            'layouts'         => array(),
            'modules'         => array(
                'enabled'       => $this->core->settings()->enabled_modules(),
                'configuration' => array(),
            ),
        );
    }

    /** @param string $candidate Candidate ID. @param array<string,mixed> $profiles Existing. @return string */
    private function unique_profile_id( $candidate, array $profiles ) {
        $base = sanitize_key( $candidate );
        $id   = $base;
        $i    = 2;
        while ( isset( $profiles[ $id ] ) ) {
            $id = $base . '-' . $i;
            $i++;
        }
        return $id;
    }

    /** @param string $capability Capability. @return void */
    private function require_capability( $capability ) {
        if ( ! current_user_can( $capability ) ) {
            wp_die( esc_html__( 'You do not have permission to access this Commerce Suite screen.', 'itk-commerce-core' ) );
        }
    }

    /** @param string $title Title. @param string $description Description. @return void */
    private function render_header( $title, $description ) {
        echo '<div class="itk-admin-hero"><div><span class="itk-admin-eyebrow">IT-Kayali Commerce Suite</span><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p></div><span class="itk-admin-version">v' . esc_html( \ITK\Commerce\Core\VERSION ) . '</span></div>';
    }

    /** @param string $label Label. @param string $value Value. @param string $icon Dashicon. @return void */
    private function stat_card( $label, $value, $icon ) {
        echo '<div class="itk-admin-stat"><span class="dashicons ' . esc_attr( $icon ) . '"></span><div><small>' . esc_html( $label ) . '</small><strong>' . esc_html( $value ) . '</strong></div></div>';
    }

    /** @param string $title Title. @param string $description Description. @param string $url URL. @param string $button Button. @param string $icon Icon. @return void */
    private function launch_card( $title, $description, $url, $button, $icon ) {
        echo '<article class="itk-admin-card"><span class="dashicons ' . esc_attr( $icon ) . '"></span><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $description ) . '</p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( $button ) . '</a></article>';
    }

    /** @param string $class Class. @param string $title Title. @param string $description Description. @param string $path Admin relative path. @return void */
    private function conditional_tool_card( $class, $title, $description, $path ) {
        if ( class_exists( $class ) ) {
            $this->launch_card( $title, $description, admin_url( $path ), __( 'Open builder', 'itk-commerce-core' ), 'dashicons-admin-appearance' );
        } else {
            echo '<article class="itk-admin-card is-disabled"><span class="dashicons dashicons-admin-appearance"></span><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $description ) . '</p><span class="itk-status is-muted">' . esc_html__( 'Module not active', 'itk-commerce-core' ) . '</span></article>';
        }
    }

    /** @param string $id Module ID. @return string */
    private function module_label( $id ) {
        $label = preg_replace( '/^itk-commerce-/', '', $id );
        return ucwords( str_replace( '-', ' ', (string) $label ) );
    }

    /** @param string $id Profile ID. @param string $action Action. @param string $label Label. @param string $class Class. @return void */
    private function profile_action_button( $id, $action, $label, $class = 'button' ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="itk_commerce_profile_action"><input type="hidden" name="profile_action" value="' . esc_attr( $action ) . '"><input type="hidden" name="profile_id" value="' . esc_attr( $id ) . '">';
        wp_nonce_field( 'itk_commerce_profile_action' );
        echo '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
    }

    /** @param string $label Label. @param string $value Value. @return void */
    private function system_row( $label, $value ) {
        echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><code>' . esc_html( (string) $value ) . '</code></td></tr>';
    }
}
