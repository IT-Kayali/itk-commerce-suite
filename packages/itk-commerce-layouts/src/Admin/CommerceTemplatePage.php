<?php
/**
 * Visual Shop/Product/Cart/Checkout template editor.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts\Admin;

use ITK\Commerce\Core\Core;
use ITK\Commerce\Layouts\LivePreview;
defined( 'ABSPATH' ) || exit;

final class CommerceTemplatePage {
    const PAGE_SLUG = 'itk-commerce-template-builder';

    /** @var string */
    private $page_hook = '';

    /**
     * @return void
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_template_builder', array( $this, 'save' ) );
    }

    /**
     * @return void
     */
    public function add_menu() {
        $this->page_hook = add_theme_page(
            __( 'Commerce Page Templates', 'itk-commerce-layouts' ),
            __( 'Commerce Templates', 'itk-commerce-layouts' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /**
     * @param string $hook_suffix Admin hook.
     * @return void
     */
    public function enqueue_assets( $hook_suffix ) {
        if ( $this->page_hook !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-template-builder',
            plugins_url( 'assets/admin/commerce-template-builder.css', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-template-builder',
            plugins_url( 'assets/admin/commerce-template-builder.js', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION,
            true
        );
    }

    /**
     * @return void
     */
    public function render() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce templates.', 'itk-commerce-layouts' ) );
        }

        $profile = $this->editor_profile();
        $models  = $this->models();
        $config  = $this->config( $profile );
        $targets = $this->preview_targets();
        $nonce   = wp_create_nonce( LivePreview::NONCE_ACTION );
        $saved   = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) );
        $error   = isset( $_GET['itk_error'] ) ? sanitize_key( wp_unslash( $_GET['itk_error'] ) ) : '';
        ?>
        <div class="wrap itk-commerce-template-builder">
            <div class="itk-template-head">
                <div>
                    <span class="itk-template-eyebrow"><?php esc_html_e( 'IT-Kayali Commerce Suite', 'itk-commerce-layouts' ); ?></span>
                    <h1><?php esc_html_e( 'Commerce Page Templates', 'itk-commerce-layouts' ); ?></h1>
                    <p><?php esc_html_e( 'Design Shop, Product, Cart and Checkout presentation through reusable WooCommerce-safe models instead of copying customer-specific templates.', 'itk-commerce-layouts' ); ?></p>
                </div>
                <div class="itk-template-profile">
                    <span><?php esc_html_e( 'Active profile', 'itk-commerce-layouts' ); ?></span>
                    <strong><?php echo esc_html( $profile['name'] ); ?></strong>
                    <code><?php echo esc_html( $profile['profile_id'] ); ?></code>
                </div>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Commerce page templates saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>
            <?php if ( $error ) : ?>
                <div class="notice notice-error"><p><?php esc_html_e( 'The Commerce template configuration could not be saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                data-itk-commerce-template-builder
                data-preview-shop="<?php echo esc_url( $targets['shop'] ); ?>"
                data-preview-product="<?php echo esc_url( $targets['product'] ); ?>"
                data-preview-cart="<?php echo esc_url( $targets['cart'] ); ?>"
                data-preview-checkout="<?php echo esc_url( $targets['checkout'] ); ?>"
                data-preview-nonce="<?php echo esc_attr( $nonce ); ?>"
            >
                <input type="hidden" name="action" value="itk_commerce_save_template_builder">
                <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile['profile_id'] ); ?>">
                <?php wp_nonce_field( 'itk_commerce_save_template_builder' ); ?>

                <div class="itk-template-workspace">
                    <aside class="itk-template-controls">
                        <div class="itk-template-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Commerce page templates', 'itk-commerce-layouts' ); ?>">
                            <?php foreach ( array( 'shop', 'product', 'cart', 'checkout' ) as $index => $area ) : ?>
                                <button type="button" role="tab" data-itk-commerce-tab="<?php echo esc_attr( $area ); ?>" class="<?php echo 0 === $index ? 'is-active' : ''; ?>">
                                    <?php echo esc_html( $this->area_label( $area ) ); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php $this->render_shop_panel( $models['shop'], $config['shop'] ); ?>
                        <?php $this->render_product_panel( $models['product'], $config['product'] ); ?>
                        <?php $this->render_cart_panel( $models['cart'], $config['cart'] ); ?>
                        <?php $this->render_checkout_panel( $models['checkout'], $config['checkout'] ); ?>

                        <div class="itk-template-savebar">
                            <span><?php echo esc_html( sprintf( __( 'Profile version %s', 'itk-commerce-layouts' ), $profile['profile_version'] ) ); ?></span>
                            <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save templates', 'itk-commerce-layouts' ); ?></button>
                        </div>
                    </aside>

                    <section class="itk-template-preview" aria-label="<?php esc_attr_e( 'Commerce template live preview', 'itk-commerce-layouts' ); ?>">
                        <div class="itk-template-preview__toolbar">
                            <div>
                                <strong data-itk-preview-title><?php esc_html_e( 'Shop preview', 'itk-commerce-layouts' ); ?></strong>
                                <span data-itk-preview-status><?php esc_html_e( 'Unsaved model and option changes are previewed securely.', 'itk-commerce-layouts' ); ?></span>
                            </div>
                            <div class="itk-template-devices" role="group" aria-label="<?php esc_attr_e( 'Preview device', 'itk-commerce-layouts' ); ?>">
                                <button type="button" class="is-active" data-itk-template-device="desktop"><?php esc_html_e( 'Desktop', 'itk-commerce-layouts' ); ?></button>
                                <button type="button" data-itk-template-device="tablet"><?php esc_html_e( 'Tablet', 'itk-commerce-layouts' ); ?></button>
                                <button type="button" data-itk-template-device="mobile"><?php esc_html_e( 'Mobile', 'itk-commerce-layouts' ); ?></button>
                            </div>
                        </div>
                        <div class="itk-template-preview__stage" data-itk-template-stage data-device="desktop">
                            <iframe title="<?php esc_attr_e( 'Commerce page template preview', 'itk-commerce-layouts' ); ?>" src="<?php echo esc_url( $targets['shop'] ); ?>" data-itk-commerce-preview></iframe>
                        </div>
                    </section>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * @param array<string,array<string,string>> $models Shop models.
     * @param array<string,mixed>                $config Shop config.
     * @return void
     */
    private function render_shop_panel( array $models, array $config ) {
        $options = $config['options'];
        ?>
        <section class="itk-template-panel is-active" data-itk-commerce-panel="shop">
            <?php $this->panel_heading( __( 'Shop / catalog', 'itk-commerce-layouts' ), __( 'Choose the catalog rhythm, product density and optional filter sidebar.', 'itk-commerce-layouts' ) ); ?>
            <?php $this->render_model_cards( 'shop', $models, $config['model'] ); ?>
            <div class="itk-template-options">
                <label><span><?php esc_html_e( 'Product columns', 'itk-commerce-layouts' ); ?></span><select name="commerce[shop][columns]" data-itk-preview-option="columns">
                    <?php for ( $columns = 2; $columns <= 6; $columns++ ) : ?><option value="<?php echo esc_attr( (string) $columns ); ?>" <?php selected( (int) $options['columns'], $columns ); ?>><?php echo esc_html( (string) $columns ); ?></option><?php endfor; ?>
                </select></label>
                <label><span><?php esc_html_e( 'Sidebar position', 'itk-commerce-layouts' ); ?></span><select name="commerce[shop][sidebar_position]" data-itk-preview-option="sidebar_position">
                    <option value="left" <?php selected( $options['sidebar_position'], 'left' ); ?>><?php esc_html_e( 'Left', 'itk-commerce-layouts' ); ?></option>
                    <option value="right" <?php selected( $options['sidebar_position'], 'right' ); ?>><?php esc_html_e( 'Right', 'itk-commerce-layouts' ); ?></option>
                </select></label>
                <label><span><?php esc_html_e( 'Card density', 'itk-commerce-layouts' ); ?></span><select name="commerce[shop][density]" data-itk-preview-option="density">
                    <option value="comfortable" <?php selected( $options['density'], 'comfortable' ); ?>><?php esc_html_e( 'Comfortable', 'itk-commerce-layouts' ); ?></option>
                    <option value="compact" <?php selected( $options['density'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'itk-commerce-layouts' ); ?></option>
                </select></label>
            </div>
            <p class="itk-template-hint"><?php esc_html_e( 'The Sidebar model uses the existing Shop Sidebar widget area. If it is empty, the catalog safely renders without an empty sidebar.', 'itk-commerce-layouts' ); ?></p>
        </section>
        <?php
    }

    /**
     * @param array<string,array<string,string>> $models Product models.
     * @param array<string,mixed>                $config Product config.
     * @return void
     */
    private function render_product_panel( array $models, array $config ) {
        $options = $config['options'];
        ?>
        <section class="itk-template-panel" data-itk-commerce-panel="product">
            <?php $this->panel_heading( __( 'Single product', 'itk-commerce-layouts' ), __( 'Control gallery direction, gallery weight and the summary/tabs behavior without replacing WooCommerce product templates.', 'itk-commerce-layouts' ) ); ?>
            <?php $this->render_model_cards( 'product', $models, $config['model'] ); ?>
            <div class="itk-template-options">
                <label><span><?php esc_html_e( 'Gallery width', 'itk-commerce-layouts' ); ?></span><select name="commerce[product][gallery_width]" data-itk-preview-option="gallery_width">
                    <?php foreach ( array( 40, 50, 60 ) as $width ) : ?><option value="<?php echo esc_attr( (string) $width ); ?>" <?php selected( (int) $options['gallery_width'], $width ); ?>><?php echo esc_html( $width . '%' ); ?></option><?php endforeach; ?>
                </select></label>
                <label><span><?php esc_html_e( 'Tabs layout', 'itk-commerce-layouts' ); ?></span><select name="commerce[product][tabs_layout]" data-itk-preview-option="tabs_layout">
                    <option value="tabs" <?php selected( $options['tabs_layout'], 'tabs' ); ?>><?php esc_html_e( 'Tabs', 'itk-commerce-layouts' ); ?></option>
                    <option value="stacked" <?php selected( $options['tabs_layout'], 'stacked' ); ?>><?php esc_html_e( 'Stacked sections', 'itk-commerce-layouts' ); ?></option>
                </select></label>
                <?php $this->render_switch( 'commerce[product][sticky_summary]', 'sticky_summary', __( 'Sticky product summary', 'itk-commerce-layouts' ), ! empty( $options['sticky_summary'] ) ); ?>
            </div>
        </section>
        <?php
    }

    /**
     * @param array<string,array<string,string>> $models Cart models.
     * @param array<string,mixed>                $config Cart config.
     * @return void
     */
    private function render_cart_panel( array $models, array $config ) {
        $options = $config['options'];
        ?>
        <section class="itk-template-panel" data-itk-commerce-panel="cart">
            <?php $this->panel_heading( __( 'Cart', 'itk-commerce-layouts' ), __( 'Choose a classic, split or compact cart while retaining WooCommerce form and totals hooks.', 'itk-commerce-layouts' ) ); ?>
            <?php $this->render_model_cards( 'cart', $models, $config['model'] ); ?>
            <div class="itk-template-options">
                <label><span><?php esc_html_e( 'Table density', 'itk-commerce-layouts' ); ?></span><select name="commerce[cart][density]" data-itk-preview-option="density">
                    <option value="comfortable" <?php selected( $options['density'], 'comfortable' ); ?>><?php esc_html_e( 'Comfortable', 'itk-commerce-layouts' ); ?></option>
                    <option value="compact" <?php selected( $options['density'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'itk-commerce-layouts' ); ?></option>
                </select></label>
                <?php $this->render_switch( 'commerce[cart][sticky_totals]', 'sticky_totals', __( 'Sticky cart totals', 'itk-commerce-layouts' ), ! empty( $options['sticky_totals'] ) ); ?>
            </div>
            <p class="itk-template-hint"><?php esc_html_e( 'Cart preview depends on the current browser cart session. An empty cart will naturally show WooCommerce’s empty-cart state.', 'itk-commerce-layouts' ); ?></p>
        </section>
        <?php
    }

    /**
     * @param array<string,array<string,string>> $models Checkout models.
     * @param array<string,mixed>                $config Checkout config.
     * @return void
     */
    private function render_checkout_panel( array $models, array $config ) {
        $options = $config['options'];
        ?>
        <section class="itk-template-panel" data-itk-commerce-panel="checkout">
            <?php $this->panel_heading( __( 'Checkout', 'itk-commerce-layouts' ), __( 'Configure the checkout width, field density and order-review behavior while preserving WooCommerce payment and validation flows.', 'itk-commerce-layouts' ) ); ?>
            <?php $this->render_model_cards( 'checkout', $models, $config['model'] ); ?>
            <div class="itk-template-options">
                <label><span><?php esc_html_e( 'Content width', 'itk-commerce-layouts' ); ?></span><select name="commerce[checkout][content_width]" data-itk-preview-option="content_width">
                    <option value="wide" <?php selected( $options['content_width'], 'wide' ); ?>><?php esc_html_e( 'Wide', 'itk-commerce-layouts' ); ?></option>
                    <option value="boxed" <?php selected( $options['content_width'], 'boxed' ); ?>><?php esc_html_e( 'Boxed', 'itk-commerce-layouts' ); ?></option>
                </select></label>
                <label><span><?php esc_html_e( 'Field density', 'itk-commerce-layouts' ); ?></span><select name="commerce[checkout][field_density]" data-itk-preview-option="field_density">
                    <option value="comfortable" <?php selected( $options['field_density'], 'comfortable' ); ?>><?php esc_html_e( 'Comfortable', 'itk-commerce-layouts' ); ?></option>
                    <option value="compact" <?php selected( $options['field_density'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'itk-commerce-layouts' ); ?></option>
                </select></label>
                <?php $this->render_switch( 'commerce[checkout][sticky_summary]', 'sticky_summary', __( 'Sticky order review', 'itk-commerce-layouts' ), ! empty( $options['sticky_summary'] ) ); ?>
            </div>
            <p class="itk-template-hint"><?php esc_html_e( 'WooCommerce may redirect an empty checkout to the cart. Add a product to the current session for a complete checkout preview.', 'itk-commerce-layouts' ); ?></p>
        </section>
        <?php
    }

    /**
     * Save only `layouts.commerce`, preserving Header/Footer, rich Mega-menu,
     * branding and unrelated module configuration.
     *
     * @return void
     */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce templates.', 'itk-commerce-layouts' ) );
        }

        check_admin_referer( 'itk_commerce_save_template_builder' );

        $core       = Core::instance();
        $profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
        if ( ! $profile_id ) {
            $profile_id = 'site-default';
        }

        $profile = $core->profiles()->get( $profile_id );
        if ( ! is_array( $profile ) ) {
            $profile = $this->blank_profile( $profile_id );
        }

        $models = $this->models();
        $raw    = isset( $_POST['commerce'] ) && is_array( $_POST['commerce'] ) ? wp_unslash( $_POST['commerce'] ) : array();

        $profile['layouts']['commerce'] = array(
            'shop'     => $this->sanitize_shop( isset( $raw['shop'] ) && is_array( $raw['shop'] ) ? $raw['shop'] : array(), $models['shop'] ),
            'product'  => $this->sanitize_product( isset( $raw['product'] ) && is_array( $raw['product'] ) ? $raw['product'] : array(), $models['product'] ),
            'cart'     => $this->sanitize_cart( isset( $raw['cart'] ) && is_array( $raw['cart'] ) ? $raw['cart'] : array(), $models['cart'] ),
            'checkout' => $this->sanitize_checkout( isset( $raw['checkout'] ) && is_array( $raw['checkout'] ) ? $raw['checkout'] : array(), $models['checkout'] ),
        );

        if ( empty( $profile['modules']['enabled'] ) || ! is_array( $profile['modules']['enabled'] ) ) {
            $profile['modules']['enabled'] = array();
        }
        if ( ! in_array( \ITK\Commerce\Layouts\MODULE_ID, $profile['modules']['enabled'], true ) ) {
            $profile['modules']['enabled'][] = \ITK\Commerce\Layouts\MODULE_ID;
        }

        $profile['profile_version'] = $this->next_patch_version( isset( $profile['profile_version'] ) ? $profile['profile_version'] : '1.0.0' );
        $result                     = $core->profiles()->save( $profile );
        $redirect                   = admin_url( 'themes.php?page=' . self::PAGE_SLUG );

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( add_query_arg( 'itk_error', sanitize_key( $result->get_error_code() ), $redirect ) );
            exit;
        }

        $settings                      = $core->settings()->all();
        $settings['active_profile_id'] = $profile_id;
        $settings['modules']['enabled'] = isset( $settings['modules']['enabled'] ) && is_array( $settings['modules']['enabled'] )
            ? $settings['modules']['enabled']
            : array();
        if ( ! in_array( \ITK\Commerce\Layouts\MODULE_ID, $settings['modules']['enabled'], true ) ) {
            $settings['modules']['enabled'][] = \ITK\Commerce\Layouts\MODULE_ID;
        }
        $core->settings()->save( $settings );

        wp_safe_redirect( add_query_arg( 'updated', '1', $redirect ) );
        exit;
    }

    /**
     * @return array<string,mixed>
     */
    private function editor_profile() {
        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        return is_array( $profile ) ? $profile : $this->blank_profile( 'site-default' );
    }

    /**
     * @param string $profile_id Profile ID.
     * @return array<string,mixed>
     */
    private function blank_profile( $profile_id ) {
        return array(
            'schema_version'  => 1,
            'profile_id'      => sanitize_key( $profile_id ),
            'profile_version' => '1.0.0',
            'name'            => sprintf( __( '%s Site Profile', 'itk-commerce-layouts' ), get_bloginfo( 'name' ) ),
            'branding'        => array(),
            'design'          => array(),
            'contacts'        => array(),
            'languages'       => array(),
            'layouts'         => array(),
            'modules'         => array( 'enabled' => array( \ITK\Commerce\Layouts\MODULE_ID ), 'configuration' => array() ),
        );
    }

    /**
     * @return array<string,array<string,array<string,string>>>
     */
    private function models() {
        if ( function_exists( '\\ITK\\Commerce\\Theme\\commerce_template_models' ) ) {
            $models = \ITK\Commerce\Theme\commerce_template_models();
            if ( is_array( $models ) && isset( $models['shop'], $models['product'], $models['cart'], $models['checkout'] ) ) {
                return $models;
            }
        }

        return array(
            'shop' => array( 'grid' => array( 'label' => 'Grid' ), 'sidebar' => array( 'label' => 'Sidebar' ), 'editorial' => array( 'label' => 'Editorial' ), 'compact' => array( 'label' => 'Compact' ) ),
            'product' => array( 'classic' => array( 'label' => 'Classic' ), 'gallery-left' => array( 'label' => 'Gallery Left' ), 'gallery-right' => array( 'label' => 'Gallery Right' ), 'centered' => array( 'label' => 'Centered' ), 'compact' => array( 'label' => 'Compact' ) ),
            'cart' => array( 'classic' => array( 'label' => 'Classic' ), 'split' => array( 'label' => 'Split' ), 'compact' => array( 'label' => 'Compact' ) ),
            'checkout' => array( 'classic' => array( 'label' => 'Classic' ), 'split' => array( 'label' => 'Split' ), 'focused' => array( 'label' => 'Focused' ) ),
        );
    }

    /**
     * @param array<string,mixed> $profile Profile.
     * @return array<string,array<string,mixed>>
     */
    private function config( array $profile ) {
        $stored   = isset( $profile['layouts']['commerce'] ) && is_array( $profile['layouts']['commerce'] ) ? $profile['layouts']['commerce'] : array();
        $defaults = array(
            'shop' => array( 'model' => 'grid', 'options' => array( 'columns' => 4, 'sidebar_position' => 'left', 'density' => 'comfortable' ) ),
            'product' => array( 'model' => 'classic', 'options' => array( 'gallery_width' => 50, 'sticky_summary' => false, 'tabs_layout' => 'tabs' ) ),
            'cart' => array( 'model' => 'classic', 'options' => array( 'sticky_totals' => false, 'density' => 'comfortable' ) ),
            'checkout' => array( 'model' => 'classic', 'options' => array( 'sticky_summary' => false, 'content_width' => 'wide', 'field_density' => 'comfortable' ) ),
        );

        foreach ( $defaults as $area => $default ) {
            if ( isset( $stored[ $area ] ) && is_array( $stored[ $area ] ) ) {
                $defaults[ $area ]['model'] = ! empty( $stored[ $area ]['model'] ) ? sanitize_key( $stored[ $area ]['model'] ) : $default['model'];
                if ( ! empty( $stored[ $area ]['options'] ) && is_array( $stored[ $area ]['options'] ) ) {
                    $defaults[ $area ]['options'] = array_merge( $default['options'], $stored[ $area ]['options'] );
                }
            }
        }

        return $defaults;
    }

    /**
     * @return array<string,string>
     */
    private function preview_targets() {
        $fallback = home_url( '/' );
        $targets  = array(
            'shop'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $fallback,
            'cart'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'cart' ) : $fallback,
            'checkout' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'checkout' ) : $fallback,
            'product'  => '',
        );

        if ( function_exists( 'wc_get_products' ) ) {
            $ids = wc_get_products( array( 'status' => 'publish', 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids' ) );
            if ( $ids ) {
                $targets['product'] = get_permalink( absint( reset( $ids ) ) );
            }
        }

        foreach ( array( 'shop', 'cart', 'checkout' ) as $area ) {
            if ( ! $targets[ $area ] ) {
                $targets[ $area ] = $fallback;
            }
        }

        return $targets;
    }

    /**
     * @param array<string,mixed>                $raw    Raw area config.
     * @param array<string,array<string,string>> $models Allowed models.
     * @return array<string,mixed>
     */
    private function sanitize_shop( array $raw, array $models ) {
        return array(
            'model' => $this->model_value( $raw, $models, 'grid' ),
            'options' => array(
                'columns'          => max( 2, min( 6, isset( $raw['columns'] ) ? absint( $raw['columns'] ) : 4 ) ),
                'sidebar_position' => isset( $raw['sidebar_position'] ) && in_array( $raw['sidebar_position'], array( 'left', 'right' ), true ) ? $raw['sidebar_position'] : 'left',
                'density'          => isset( $raw['density'] ) && in_array( $raw['density'], array( 'comfortable', 'compact' ), true ) ? $raw['density'] : 'comfortable',
            ),
        );
    }

    /** @param array<string,mixed> $raw @param array<string,array<string,string>> $models @return array<string,mixed> */
    private function sanitize_product( array $raw, array $models ) {
        $width = isset( $raw['gallery_width'] ) ? absint( $raw['gallery_width'] ) : 50;
        return array(
            'model' => $this->model_value( $raw, $models, 'classic' ),
            'options' => array(
                'gallery_width'  => in_array( $width, array( 40, 50, 60 ), true ) ? $width : 50,
                'sticky_summary' => ! empty( $raw['sticky_summary'] ),
                'tabs_layout'    => isset( $raw['tabs_layout'] ) && in_array( $raw['tabs_layout'], array( 'tabs', 'stacked' ), true ) ? $raw['tabs_layout'] : 'tabs',
            ),
        );
    }

    /** @param array<string,mixed> $raw @param array<string,array<string,string>> $models @return array<string,mixed> */
    private function sanitize_cart( array $raw, array $models ) {
        return array(
            'model' => $this->model_value( $raw, $models, 'classic' ),
            'options' => array(
                'sticky_totals' => ! empty( $raw['sticky_totals'] ),
                'density'       => isset( $raw['density'] ) && in_array( $raw['density'], array( 'comfortable', 'compact' ), true ) ? $raw['density'] : 'comfortable',
            ),
        );
    }

    /** @param array<string,mixed> $raw @param array<string,array<string,string>> $models @return array<string,mixed> */
    private function sanitize_checkout( array $raw, array $models ) {
        return array(
            'model' => $this->model_value( $raw, $models, 'classic' ),
            'options' => array(
                'sticky_summary' => ! empty( $raw['sticky_summary'] ),
                'content_width'  => isset( $raw['content_width'] ) && in_array( $raw['content_width'], array( 'boxed', 'wide' ), true ) ? $raw['content_width'] : 'wide',
                'field_density'  => isset( $raw['field_density'] ) && in_array( $raw['field_density'], array( 'comfortable', 'compact' ), true ) ? $raw['field_density'] : 'comfortable',
            ),
        );
    }

    /**
     * @param array<string,mixed>                $raw      Raw config.
     * @param array<string,array<string,string>> $models   Models.
     * @param string                             $fallback Fallback.
     * @return string
     */
    private function model_value( array $raw, array $models, $fallback ) {
        $value = isset( $raw['model'] ) ? sanitize_key( $raw['model'] ) : '';
        return $value && isset( $models[ $value ] ) ? $value : $fallback;
    }

    /**
     * @param string                             $area     Area.
     * @param array<string,array<string,string>> $models   Models.
     * @param string                             $selected Selected model.
     * @return void
     */
    private function render_model_cards( $area, array $models, $selected ) {
        echo '<div class="itk-commerce-model-grid">';
        foreach ( $models as $model_id => $definition ) {
            $model_id    = sanitize_key( $model_id );
            $label       = isset( $definition['label'] ) ? $definition['label'] : $model_id;
            $description = isset( $definition['description'] ) ? $definition['description'] : '';
            ?>
            <label class="itk-commerce-model-card">
                <input type="radio" name="commerce[<?php echo esc_attr( $area ); ?>][model]" value="<?php echo esc_attr( $model_id ); ?>" <?php checked( $selected, $model_id ); ?> data-itk-commerce-model>
                <span class="itk-commerce-model-card__visual" data-area="<?php echo esc_attr( $area ); ?>" data-model="<?php echo esc_attr( $model_id ); ?>"><i></i><i></i><i></i><i></i></span>
                <strong><?php echo esc_html( $label ); ?></strong>
                <?php if ( $description ) : ?><small><?php echo esc_html( $description ); ?></small><?php endif; ?>
            </label>
            <?php
        }
        echo '</div>';
    }

    /** @param string $title @param string $description @return void */
    private function panel_heading( $title, $description ) {
        echo '<div class="itk-template-panel__heading"><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $description ) . '</p></div>';
    }

    /**
     * @param string $name    Input name.
     * @param string $option  Preview option key.
     * @param string $label   Label.
     * @param bool   $checked Checked.
     * @return void
     */
    private function render_switch( $name, $option, $label, $checked ) {
        ?>
        <label class="itk-template-switch">
            <span><?php echo esc_html( $label ); ?></span>
            <input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?> data-itk-preview-option="<?php echo esc_attr( $option ); ?>">
        </label>
        <?php
    }

    /** @param string $area @return string */
    private function area_label( $area ) {
        $labels = array(
            'shop'     => __( 'Shop', 'itk-commerce-layouts' ),
            'product'  => __( 'Product', 'itk-commerce-layouts' ),
            'cart'     => __( 'Cart', 'itk-commerce-layouts' ),
            'checkout' => __( 'Checkout', 'itk-commerce-layouts' ),
        );
        return isset( $labels[ $area ] ) ? $labels[ $area ] : $area;
    }

    /** @param string $version @return string */
    private function next_patch_version( $version ) {
        $parts = array_map( 'absint', explode( '.', preg_replace( '/[^0-9.].*$/', '', (string) $version ) ) );
        $parts = array_pad( array_slice( $parts, 0, 3 ), 3, 0 );
        $parts[2]++;
        return implode( '.', $parts );
    }
}
