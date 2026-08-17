<?php
/**
 * Visual Header/Footer layout builder for the active customer profile.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts\Admin;

use ITK\Commerce\Core\Core;
use ITK\Commerce\Layouts\LivePreview;

defined( 'ABSPATH' ) || exit;

final class LayoutBuilderPage {
    const PAGE_SLUG = 'itk-commerce-layout-builder';

    /** @var string */
    private $page_hook = '';

    /**
     * Register admin UI hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_layout_builder', array( $this, 'save' ) );
    }

    /**
     * Add the builder under Appearance.
     *
     * @return void
     */
    public function add_menu() {
        $this->page_hook = add_theme_page(
            __( 'Commerce Layout Builder', 'itk-commerce-layouts' ),
            __( 'Commerce Layouts', 'itk-commerce-layouts' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /**
     * Load isolated builder assets only on this screen.
     *
     * @param string $hook_suffix Current admin hook.
     * @return void
     */
    public function enqueue_assets( $hook_suffix ) {
        if ( $this->page_hook !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-layout-builder',
            plugins_url( 'assets/admin/layout-builder.css', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-layout-builder',
            plugins_url( 'assets/admin/layout-builder.js', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION,
            true
        );
    }

    /**
     * Render the builder with an authenticated live frontend preview.
     *
     * @return void
     */
    public function render() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce layouts.', 'itk-commerce-layouts' ) );
        }

        $profile = $this->editor_profile();
        $models  = $this->models();
        $header  = $this->area_assignment( $profile, 'header', 'classic' );
        $footer  = $this->area_assignment( $profile, 'footer', 'classic' );
        $mobile  = isset( $profile['layouts']['mobile_bottom'] ) && is_array( $profile['layouts']['mobile_bottom'] )
            ? $profile['layouts']['mobile_bottom']
            : array( 'enabled' => true, 'items' => array() );
        $mega    = isset( $profile['layouts']['mega_menu']['definitions'] ) && is_array( $profile['layouts']['mega_menu']['definitions'] )
            ? $profile['layouts']['mega_menu']['definitions']
            : array();

        $preview_url = add_query_arg(
            array(
                'itk_layout_preview' => '1',
                '_itk_preview_nonce' => wp_create_nonce( LivePreview::NONCE_ACTION ),
            ),
            home_url( '/' )
        );

        $saved = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) );
        $error = isset( $_GET['itk_error'] ) ? sanitize_key( wp_unslash( $_GET['itk_error'] ) ) : '';
        ?>
        <div class="wrap itk-layout-builder">
            <div class="itk-builder-head">
                <div>
                    <span class="itk-builder-eyebrow"><?php esc_html_e( 'IT-Kayali Commerce Suite', 'itk-commerce-layouts' ); ?></span>
                    <h1><?php esc_html_e( 'Layout Builder', 'itk-commerce-layouts' ); ?></h1>
                    <p><?php esc_html_e( 'Choose reusable Header and Footer models, apply context rules and preview the real storefront before saving.', 'itk-commerce-layouts' ); ?></p>
                </div>
                <div class="itk-builder-profile-chip">
                    <span><?php esc_html_e( 'Active profile', 'itk-commerce-layouts' ); ?></span>
                    <strong><?php echo esc_html( $profile['name'] ); ?></strong>
                    <code><?php echo esc_html( $profile['profile_id'] ); ?></code>
                </div>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Layout profile saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>
            <?php if ( $error ) : ?>
                <div class="notice notice-error"><p><?php esc_html_e( 'The layout profile could not be saved. Review the configuration and try again.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="itk-builder-form" data-itk-layout-builder data-preview-url="<?php echo esc_url( $preview_url ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_layout_builder">
                <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile['profile_id'] ); ?>">
                <?php wp_nonce_field( 'itk_commerce_save_layout_builder' ); ?>

                <div class="itk-builder-workspace">
                    <aside class="itk-builder-controls">
                        <div class="itk-builder-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Layout areas', 'itk-commerce-layouts' ); ?>">
                            <button type="button" class="is-active" data-itk-tab="header" role="tab"><?php esc_html_e( 'Header', 'itk-commerce-layouts' ); ?></button>
                            <button type="button" data-itk-tab="footer" role="tab"><?php esc_html_e( 'Footer', 'itk-commerce-layouts' ); ?></button>
                            <button type="button" data-itk-tab="mobile" role="tab"><?php esc_html_e( 'Mobile', 'itk-commerce-layouts' ); ?></button>
                            <button type="button" data-itk-tab="mega" role="tab"><?php esc_html_e( 'Mega Menu', 'itk-commerce-layouts' ); ?></button>
                        </div>

                        <section class="itk-builder-panel is-active" data-itk-panel="header">
                            <div class="itk-panel-heading">
                                <h2><?php esc_html_e( 'Header model', 'itk-commerce-layouts' ); ?></h2>
                                <p><?php esc_html_e( 'The default applies everywhere unless a context or product rule overrides it.', 'itk-commerce-layouts' ); ?></p>
                            </div>
                            <?php $this->render_model_cards( 'header_default', $models['header'], $header['default'] ); ?>
                            <?php $this->render_context_selects( 'header', $models['header'], $header ); ?>
                        </section>

                        <section class="itk-builder-panel" data-itk-panel="footer">
                            <div class="itk-panel-heading">
                                <h2><?php esc_html_e( 'Footer model', 'itk-commerce-layouts' ); ?></h2>
                                <p><?php esc_html_e( 'Use a compact Footer for focused flows such as checkout, and richer models for storefront pages.', 'itk-commerce-layouts' ); ?></p>
                            </div>
                            <?php $this->render_model_cards( 'footer_default', $models['footer'], $footer['default'] ); ?>
                            <?php $this->render_context_selects( 'footer', $models['footer'], $footer ); ?>
                        </section>

                        <section class="itk-builder-panel" data-itk-panel="mobile">
                            <div class="itk-panel-heading">
                                <h2><?php esc_html_e( 'Mobile bottom navigation', 'itk-commerce-layouts' ); ?></h2>
                                <p><?php esc_html_e( 'A dedicated WordPress mobile-bottom menu still takes priority over fallback profile items.', 'itk-commerce-layouts' ); ?></p>
                            </div>
                            <label class="itk-switch-row">
                                <span>
                                    <strong><?php esc_html_e( 'Show bottom navigation', 'itk-commerce-layouts' ); ?></strong>
                                    <small><?php esc_html_e( 'Preview this setting instantly on the mobile viewport.', 'itk-commerce-layouts' ); ?></small>
                                </span>
                                <input type="checkbox" name="mobile_bottom_enabled" value="1" <?php checked( ! empty( $mobile['enabled'] ) ); ?> data-itk-mobile-enabled>
                            </label>
                            <p class="itk-builder-hint">
                                <?php esc_html_e( 'Fallback item labels and destinations stay in the customer profile. Menu order can alternatively be managed through Appearance > Menus.', 'itk-commerce-layouts' ); ?>
                            </p>
                        </section>

                        <section class="itk-builder-panel" data-itk-panel="mega">
                            <div class="itk-panel-heading">
                                <h2><?php esc_html_e( 'Mega-menu definitions', 'itk-commerce-layouts' ); ?></h2>
                                <p><?php esc_html_e( 'Create portable definition keys here, then assign a key to a top-level item under Appearance > Menus.', 'itk-commerce-layouts' ); ?></p>
                            </div>
                            <?php $this->render_mega_rows( $mega ); ?>
                        </section>

                        <div class="itk-builder-savebar">
                            <span><?php echo esc_html( sprintf( __( 'Profile version %s', 'itk-commerce-layouts' ), $profile['profile_version'] ) ); ?></span>
                            <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save layout', 'itk-commerce-layouts' ); ?></button>
                        </div>
                    </aside>

                    <section class="itk-builder-preview" aria-label="<?php esc_attr_e( 'Storefront preview', 'itk-commerce-layouts' ); ?>">
                        <div class="itk-preview-toolbar">
                            <div>
                                <strong><?php esc_html_e( 'Live storefront preview', 'itk-commerce-layouts' ); ?></strong>
                                <span><?php esc_html_e( 'Unsaved Header, Footer and mobile visibility are previewed securely.', 'itk-commerce-layouts' ); ?></span>
                            </div>
                            <div class="itk-device-switcher" role="group" aria-label="<?php esc_attr_e( 'Preview device', 'itk-commerce-layouts' ); ?>">
                                <button type="button" class="is-active" data-itk-device="desktop" aria-label="<?php esc_attr_e( 'Desktop', 'itk-commerce-layouts' ); ?>">Desktop</button>
                                <button type="button" data-itk-device="tablet" aria-label="<?php esc_attr_e( 'Tablet', 'itk-commerce-layouts' ); ?>">Tablet</button>
                                <button type="button" data-itk-device="mobile" aria-label="<?php esc_attr_e( 'Mobile', 'itk-commerce-layouts' ); ?>">Mobile</button>
                            </div>
                        </div>
                        <div class="itk-preview-stage" data-itk-preview-stage data-device="desktop">
                            <iframe title="<?php esc_attr_e( 'Commerce layout live preview', 'itk-commerce-layouts' ); ?>" src="<?php echo esc_url( $preview_url ); ?>" data-itk-preview-frame></iframe>
                        </div>
                    </section>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Persist only the layout-owned profile sections, preserving branding and
     * unrelated module configuration.
     *
     * @return void
     */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce layouts.', 'itk-commerce-layouts' ) );
        }

        check_admin_referer( 'itk_commerce_save_layout_builder' );

        $core       = Core::instance();
        $profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
        if ( '' === $profile_id ) {
            $profile_id = 'site-default';
        }

        $profile = $core->profiles()->get( $profile_id );
        if ( ! is_array( $profile ) ) {
            $profile = $this->blank_profile( $profile_id );
        }

        $models = $this->models();
        $profile['layouts']['header'] = $this->submitted_area( 'header', $models['header'], $profile );
        $profile['layouts']['footer'] = $this->submitted_area( 'footer', $models['footer'], $profile );

        $existing_mobile = isset( $profile['layouts']['mobile_bottom'] ) && is_array( $profile['layouts']['mobile_bottom'] )
            ? $profile['layouts']['mobile_bottom']
            : array();
        $existing_mobile['enabled'] = isset( $_POST['mobile_bottom_enabled'] );
        if ( empty( $existing_mobile['items'] ) ) {
            $existing_mobile['items'] = $this->default_mobile_items();
        }
        $profile['layouts']['mobile_bottom'] = $existing_mobile;
        $profile['layouts']['mega_menu']['definitions'] = $this->submitted_mega_definitions();

        if ( empty( $profile['modules']['enabled'] ) || ! is_array( $profile['modules']['enabled'] ) ) {
            $profile['modules']['enabled'] = array();
        }
        if ( ! in_array( \ITK\Commerce\Layouts\MODULE_ID, $profile['modules']['enabled'], true ) ) {
            $profile['modules']['enabled'][] = \ITK\Commerce\Layouts\MODULE_ID;
        }

        $profile['profile_version'] = $this->next_patch_version( isset( $profile['profile_version'] ) ? $profile['profile_version'] : '1.0.0' );
        $result                     = $core->profiles()->save( $profile );

        $redirect = admin_url( 'themes.php?page=' . self::PAGE_SLUG );
        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( add_query_arg( 'itk_error', sanitize_key( $result->get_error_code() ), $redirect ) );
            exit;
        }

        $settings                      = $core->settings()->all();
        $settings['active_profile_id'] = $profile_id;
        if ( empty( $settings['modules']['enabled'] ) || ! is_array( $settings['modules']['enabled'] ) ) {
            $settings['modules']['enabled'] = array();
        }
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
            'modules'         => array(
                'enabled'       => array( \ITK\Commerce\Layouts\MODULE_ID ),
                'configuration' => array(),
            ),
        );
    }

    /**
     * @return array<string,array<string,array<string,string>>>
     */
    private function models() {
        if ( function_exists( '\\ITK\\Commerce\\Theme\\layout_models' ) ) {
            $models = \ITK\Commerce\Theme\layout_models();
            if ( is_array( $models ) && isset( $models['header'], $models['footer'] ) ) {
                return $models;
            }
        }

        return array(
            'header' => array(
                'classic'  => array( 'label' => 'Classic' ),
                'centered' => array( 'label' => 'Centered' ),
                'shop'     => array( 'label' => 'Shop Search' ),
            ),
            'footer' => array(
                'classic' => array( 'label' => 'Classic' ),
                'compact' => array( 'label' => 'Compact' ),
                'columns' => array( 'label' => 'Columns' ),
            ),
        );
    }

    /**
     * @param array<string,mixed> $profile Profile.
     * @param string              $area    Area.
     * @param string              $default Default model.
     * @return array<string,mixed>
     */
    private function area_assignment( array $profile, $area, $default ) {
        $assignment = isset( $profile['layouts'][ $area ] ) && is_array( $profile['layouts'][ $area ] )
            ? $profile['layouts'][ $area ]
            : array();

        if ( empty( $assignment['default'] ) ) {
            $assignment['default'] = $default;
        }
        if ( empty( $assignment['contexts'] ) || ! is_array( $assignment['contexts'] ) ) {
            $assignment['contexts'] = array();
        }
        if ( empty( $assignment['rules'] ) || ! is_array( $assignment['rules'] ) ) {
            $assignment['rules'] = array();
        }

        return $assignment;
    }

    /**
     * @param string                              $area     Header/footer.
     * @param array<string,array<string,string>>  $models   Available models.
     * @param array<string,mixed>                 $profile  Existing profile.
     * @return array<string,mixed>
     */
    private function submitted_area( $area, array $models, array $profile ) {
        $existing = $this->area_assignment( $profile, $area, 'classic' );
        $default  = $this->submitted_model( $area . '_default', $models, 'classic' );
        $contexts = array();

        foreach ( array( 'shop', 'product', 'checkout' ) as $context ) {
            $value = $this->submitted_model( $area . '_' . $context, $models, '' );
            if ( $value ) {
                $contexts[ $context ] = $value;
            }
        }

        return array(
            'default'  => $default,
            'contexts' => $contexts,
            'rules'    => isset( $existing['rules'] ) && is_array( $existing['rules'] ) ? $existing['rules'] : array(),
        );
    }

    /**
     * @param string                             $field    Form field.
     * @param array<string,array<string,string>> $models   Models.
     * @param string                             $fallback Fallback model.
     * @return string
     */
    private function submitted_model( $field, array $models, $fallback ) {
        $value = isset( $_POST[ $field ] ) ? sanitize_key( wp_unslash( $_POST[ $field ] ) ) : '';
        if ( $value && isset( $models[ $value ] ) ) {
            return $value;
        }

        return $fallback;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function submitted_mega_definitions() {
        $definitions = array();
        $keys        = isset( $_POST['mega_key'] ) && is_array( $_POST['mega_key'] ) ? wp_unslash( $_POST['mega_key'] ) : array();
        $labels      = isset( $_POST['mega_label'] ) && is_array( $_POST['mega_label'] ) ? wp_unslash( $_POST['mega_label'] ) : array();
        $widths      = isset( $_POST['mega_width'] ) && is_array( $_POST['mega_width'] ) ? wp_unslash( $_POST['mega_width'] ) : array();
        $columns     = isset( $_POST['mega_columns'] ) && is_array( $_POST['mega_columns'] ) ? wp_unslash( $_POST['mega_columns'] ) : array();

        foreach ( array_slice( $keys, 0, 12, true ) as $index => $raw_key ) {
            $key = sanitize_key( $raw_key );
            if ( ! $key ) {
                continue;
            }

            $width = isset( $widths[ $index ] ) ? sanitize_key( $widths[ $index ] ) : 'aligned';
            if ( ! in_array( $width, array( 'aligned', 'full' ), true ) ) {
                $width = 'aligned';
            }

            $definitions[ $key ] = array(
                'label'        => isset( $labels[ $index ] ) ? sanitize_text_field( $labels[ $index ] ) : $key,
                'width'        => $width,
                'columns'      => max( 1, min( 6, isset( $columns[ $index ] ) ? absint( $columns[ $index ] ) : 1 ) ),
                'content_type' => 'menu',
                'content_key'  => $key,
            );
        }

        return $definitions;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function default_mobile_items() {
        return array(
            array( 'label' => __( 'Home', 'itk-commerce-layouts' ), 'target' => 'home', 'icon' => 'home' ),
            array( 'label' => __( 'Shop', 'itk-commerce-layouts' ), 'target' => 'shop', 'icon' => 'shop' ),
            array( 'label' => __( 'Cart', 'itk-commerce-layouts' ), 'target' => 'cart', 'icon' => 'cart', 'badge' => true ),
            array( 'label' => __( 'Account', 'itk-commerce-layouts' ), 'target' => 'myaccount', 'icon' => 'user' ),
        );
    }

    /**
     * @param string                             $field    Form field name.
     * @param array<string,array<string,string>> $models   Model definitions.
     * @param string                             $selected Selected ID.
     * @return void
     */
    private function render_model_cards( $field, array $models, $selected ) {
        echo '<div class="itk-model-grid">';
        foreach ( $models as $model_id => $definition ) {
            $model_id = sanitize_key( $model_id );
            $label    = isset( $definition['label'] ) ? $definition['label'] : $model_id;
            ?>
            <label class="itk-model-card">
                <input type="radio" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $model_id ); ?>" <?php checked( $selected, $model_id ); ?> data-itk-model-radio>
                <span class="itk-model-card__visual" data-model="<?php echo esc_attr( $model_id ); ?>"><i></i><i></i><i></i></span>
                <span class="itk-model-card__label"><?php echo esc_html( $label ); ?></span>
            </label>
            <?php
        }
        echo '</div>';
    }

    /**
     * @param string                             $area       Header/footer.
     * @param array<string,array<string,string>> $models     Models.
     * @param array<string,mixed>                $assignment Assignment.
     * @return void
     */
    private function render_context_selects( $area, array $models, array $assignment ) {
        $contexts = isset( $assignment['contexts'] ) && is_array( $assignment['contexts'] ) ? $assignment['contexts'] : array();
        $labels   = array(
            'shop'     => __( 'Shop archive', 'itk-commerce-layouts' ),
            'product'  => __( 'Product page', 'itk-commerce-layouts' ),
            'checkout' => __( 'Checkout', 'itk-commerce-layouts' ),
        );

        echo '<div class="itk-context-rules"><h3>' . esc_html__( 'Context overrides', 'itk-commerce-layouts' ) . '</h3>';
        foreach ( $labels as $context => $label ) {
            $selected = isset( $contexts[ $context ] ) ? sanitize_key( $contexts[ $context ] ) : '';
            echo '<label><span>' . esc_html( $label ) . '</span><select name="' . esc_attr( $area . '_' . $context ) . '">';
            echo '<option value="">' . esc_html__( 'Use default', 'itk-commerce-layouts' ) . '</option>';
            foreach ( $models as $model_id => $definition ) {
                printf(
                    '<option value="%1$s"%2$s>%3$s</option>',
                    esc_attr( $model_id ),
                    selected( $selected, $model_id, false ),
                    esc_html( isset( $definition['label'] ) ? $definition['label'] : $model_id )
                );
            }
            echo '</select></label>';
        }
        echo '</div>';
    }

    /**
     * @param array<string,array<string,mixed>> $definitions Existing definitions.
     * @return void
     */
    private function render_mega_rows( array $definitions ) {
        $rows = array_values( $definitions );
        while ( count( $rows ) < 4 ) {
            $rows[] = array();
        }

        echo '<div class="itk-mega-rows">';
        foreach ( array_slice( $rows, 0, 12 ) as $index => $definition ) {
            $existing_key = '';
            if ( $definition ) {
                foreach ( $definitions as $key => $candidate ) {
                    if ( $candidate === $definition ) {
                        $existing_key = $key;
                        break;
                    }
                }
            }
            ?>
            <div class="itk-mega-row">
                <input type="text" name="mega_key[<?php echo esc_attr( (string) $index ); ?>]" value="<?php echo esc_attr( $existing_key ); ?>" placeholder="catalog" aria-label="<?php esc_attr_e( 'Definition key', 'itk-commerce-layouts' ); ?>">
                <input type="text" name="mega_label[<?php echo esc_attr( (string) $index ); ?>]" value="<?php echo esc_attr( isset( $definition['label'] ) ? $definition['label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Label', 'itk-commerce-layouts' ); ?>">
                <select name="mega_width[<?php echo esc_attr( (string) $index ); ?>]" aria-label="<?php esc_attr_e( 'Width', 'itk-commerce-layouts' ); ?>">
                    <option value="aligned" <?php selected( isset( $definition['width'] ) ? $definition['width'] : 'aligned', 'aligned' ); ?>><?php esc_html_e( 'Aligned', 'itk-commerce-layouts' ); ?></option>
                    <option value="full" <?php selected( isset( $definition['width'] ) ? $definition['width'] : '', 'full' ); ?>><?php esc_html_e( 'Full width', 'itk-commerce-layouts' ); ?></option>
                </select>
                <select name="mega_columns[<?php echo esc_attr( (string) $index ); ?>]" aria-label="<?php esc_attr_e( 'Columns', 'itk-commerce-layouts' ); ?>">
                    <?php for ( $columns = 1; $columns <= 6; $columns++ ) : ?>
                        <option value="<?php echo esc_attr( (string) $columns ); ?>" <?php selected( isset( $definition['columns'] ) ? (int) $definition['columns'] : 1, $columns ); ?>><?php echo esc_html( (string) $columns ); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php
        }
        echo '</div><p class="itk-builder-hint">' . esc_html__( 'Blank rows are ignored. Up to 12 definitions can be saved.', 'itk-commerce-layouts' ) . '</p>';
    }

    /**
     * @param string $version Semantic version.
     * @return string
     */
    private function next_patch_version( $version ) {
        $parts = array_map( 'absint', explode( '.', preg_replace( '/[^0-9.].*$/', '', (string) $version ) ) );
        $parts = array_pad( array_slice( $parts, 0, 3 ), 3, 0 );
        $parts[2]++;
        return implode( '.', $parts );
    }
}
