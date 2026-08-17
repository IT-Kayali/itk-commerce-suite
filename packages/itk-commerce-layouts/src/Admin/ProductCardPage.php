<?php
/**
 * Visual reusable product-card editor.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts\Admin;

use ITK\Commerce\Core\Core;
use ITK\Commerce\Layouts\LivePreview;
defined( 'ABSPATH' ) || exit;

final class ProductCardPage {
    const PAGE_SLUG = 'itk-commerce-product-cards';

    /** @var string */
    private $page_hook = '';

    /** @return void */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_product_cards', array( $this, 'save' ) );
    }

    /** @return void */
    public function add_menu() {
        $this->page_hook = add_theme_page(
            __( 'Commerce Product Cards', 'itk-commerce-layouts' ),
            __( 'Product Cards', 'itk-commerce-layouts' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /**
     * @param string $hook_suffix Admin hook suffix.
     * @return void
     */
    public function enqueue_assets( $hook_suffix ) {
        if ( $this->page_hook !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-product-card-builder',
            plugins_url( 'assets/admin/commerce-template-builder.css', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-product-card-builder',
            plugins_url( 'assets/admin/product-card-builder.js', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION,
            true
        );
    }

    /** @return void */
    public function render() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage product cards.', 'itk-commerce-layouts' ) );
        }

        $profile = $this->editor_profile();
        $models  = $this->models();
        $config  = $this->config( $profile );
        $target  = $this->preview_target();
        $nonce   = wp_create_nonce( LivePreview::NONCE_ACTION );
        $saved   = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) );
        $error   = isset( $_GET['itk_error'] ) ? sanitize_key( wp_unslash( $_GET['itk_error'] ) ) : '';
        ?>
        <div class="wrap itk-commerce-template-builder">
            <div class="itk-template-head">
                <div>
                    <span class="itk-template-eyebrow"><?php esc_html_e( 'IT-Kayali Commerce Suite', 'itk-commerce-layouts' ); ?></span>
                    <h1><?php esc_html_e( 'Product Cards', 'itk-commerce-layouts' ); ?></h1>
                    <p><?php esc_html_e( 'Configure reusable catalog cards, state labels and interaction styling without overriding WooCommerce product templates. Wishlist, compare and quick-view features attach later through public extension points.', 'itk-commerce-layouts' ); ?></p>
                </div>
                <div class="itk-template-profile">
                    <span><?php esc_html_e( 'Active profile', 'itk-commerce-layouts' ); ?></span>
                    <strong><?php echo esc_html( $profile['name'] ); ?></strong>
                    <code><?php echo esc_html( $profile['profile_id'] ); ?></code>
                </div>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Product-card configuration saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>
            <?php if ( $error ) : ?>
                <div class="notice notice-error"><p><?php esc_html_e( 'The product-card configuration could not be saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                data-itk-product-card-builder
                data-preview-url="<?php echo esc_url( $target ); ?>"
                data-preview-nonce="<?php echo esc_attr( $nonce ); ?>"
            >
                <input type="hidden" name="action" value="itk_commerce_save_product_cards">
                <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile['profile_id'] ); ?>">
                <?php wp_nonce_field( 'itk_commerce_save_product_cards' ); ?>

                <div class="itk-template-workspace">
                    <aside class="itk-template-controls">
                        <section class="itk-template-panel is-active">
                            <div class="itk-template-panel__heading">
                                <h2><?php esc_html_e( 'Card model', 'itk-commerce-layouts' ); ?></h2>
                                <p><?php esc_html_e( 'Choose a reusable visual base, then fine-tune image, price, action, hover and label treatment.', 'itk-commerce-layouts' ); ?></p>
                            </div>

                            <?php $this->render_model_cards( $models, $config['model'] ); ?>

                            <div class="itk-template-options">
                                <?php $options = $config['options']; ?>
                                <label><span><?php esc_html_e( 'Image ratio', 'itk-commerce-layouts' ); ?></span><select name="product_card[image_ratio]" data-itk-card-option="image_ratio">
                                    <option value="portrait" <?php selected( $options['image_ratio'], 'portrait' ); ?>><?php esc_html_e( 'Portrait 4:5', 'itk-commerce-layouts' ); ?></option>
                                    <option value="square" <?php selected( $options['image_ratio'], 'square' ); ?>><?php esc_html_e( 'Square 1:1', 'itk-commerce-layouts' ); ?></option>
                                    <option value="landscape" <?php selected( $options['image_ratio'], 'landscape' ); ?>><?php esc_html_e( 'Landscape 4:3', 'itk-commerce-layouts' ); ?></option>
                                </select></label>
                                <label><span><?php esc_html_e( 'Content alignment', 'itk-commerce-layouts' ); ?></span><select name="product_card[content_align]" data-itk-card-option="content_align">
                                    <option value="left" <?php selected( $options['content_align'], 'left' ); ?>><?php esc_html_e( 'Left', 'itk-commerce-layouts' ); ?></option>
                                    <option value="center" <?php selected( $options['content_align'], 'center' ); ?>><?php esc_html_e( 'Centered', 'itk-commerce-layouts' ); ?></option>
                                </select></label>
                                <label><span><?php esc_html_e( 'Price treatment', 'itk-commerce-layouts' ); ?></span><select name="product_card[price_treatment]" data-itk-card-option="price_treatment">
                                    <option value="standard" <?php selected( $options['price_treatment'], 'standard' ); ?>><?php esc_html_e( 'Standard', 'itk-commerce-layouts' ); ?></option>
                                    <option value="emphasis" <?php selected( $options['price_treatment'], 'emphasis' ); ?>><?php esc_html_e( 'Emphasis', 'itk-commerce-layouts' ); ?></option>
                                    <option value="muted" <?php selected( $options['price_treatment'], 'muted' ); ?>><?php esc_html_e( 'Muted', 'itk-commerce-layouts' ); ?></option>
                                </select></label>
                                <label><span><?php esc_html_e( 'Action treatment', 'itk-commerce-layouts' ); ?></span><select name="product_card[action_treatment]" data-itk-card-option="action_treatment">
                                    <option value="button" <?php selected( $options['action_treatment'], 'button' ); ?>><?php esc_html_e( 'Button', 'itk-commerce-layouts' ); ?></option>
                                    <option value="outline" <?php selected( $options['action_treatment'], 'outline' ); ?>><?php esc_html_e( 'Outline', 'itk-commerce-layouts' ); ?></option>
                                    <option value="text" <?php selected( $options['action_treatment'], 'text' ); ?>><?php esc_html_e( 'Text link', 'itk-commerce-layouts' ); ?></option>
                                </select></label>
                                <label><span><?php esc_html_e( 'Hover behavior', 'itk-commerce-layouts' ); ?></span><select name="product_card[hover_behavior]" data-itk-card-option="hover_behavior">
                                    <option value="lift" <?php selected( $options['hover_behavior'], 'lift' ); ?>><?php esc_html_e( 'Lift card', 'itk-commerce-layouts' ); ?></option>
                                    <option value="image-zoom" <?php selected( $options['hover_behavior'], 'image-zoom' ); ?>><?php esc_html_e( 'Image zoom', 'itk-commerce-layouts' ); ?></option>
                                    <option value="none" <?php selected( $options['hover_behavior'], 'none' ); ?>><?php esc_html_e( 'None', 'itk-commerce-layouts' ); ?></option>
                                </select></label>
                                <label><span><?php esc_html_e( 'Badge style', 'itk-commerce-layouts' ); ?></span><select name="product_card[badge_style]" data-itk-card-option="badge_style">
                                    <option value="pill" <?php selected( $options['badge_style'], 'pill' ); ?>><?php esc_html_e( 'Pill', 'itk-commerce-layouts' ); ?></option>
                                    <option value="corner" <?php selected( $options['badge_style'], 'corner' ); ?>><?php esc_html_e( 'Corner', 'itk-commerce-layouts' ); ?></option>
                                    <option value="minimal" <?php selected( $options['badge_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'itk-commerce-layouts' ); ?></option>
                                </select></label>
                                <label class="itk-template-switch">
                                    <span><?php esc_html_e( 'State badges', 'itk-commerce-layouts' ); ?></span>
                                    <input type="checkbox" name="product_card[show_state_badges]" value="1" <?php checked( ! empty( $options['show_state_badges'] ) ); ?> data-itk-card-option="show_state_badges">
                                </label>
                                <label><span><?php esc_html_e( 'New badge window', 'itk-commerce-layouts' ); ?></span><select name="product_card[new_days]" data-itk-card-option="new_days">
                                    <?php foreach ( array( 7, 14, 30, 60, 90 ) as $days ) : ?>
                                        <option value="<?php echo esc_attr( (string) $days ); ?>" <?php selected( (int) $options['new_days'], $days ); ?>><?php echo esc_html( sprintf( __( '%d days', 'itk-commerce-layouts' ), $days ) ); ?></option>
                                    <?php endforeach; ?>
                                </select></label>
                            </div>

                            <p class="itk-template-hint"><?php esc_html_e( 'Sale keeps WooCommerce’s native sale flash. Sold-out, Featured and New use the Theme badge surface. Modules can add their own labels with the public itk_commerce_product_badges filter.', 'itk-commerce-layouts' ); ?></p>
                        </section>

                        <div class="itk-template-savebar">
                            <span><?php echo esc_html( sprintf( __( 'Profile version %s', 'itk-commerce-layouts' ), $profile['profile_version'] ) ); ?></span>
                            <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save product cards', 'itk-commerce-layouts' ); ?></button>
                        </div>
                    </aside>

                    <section class="itk-template-preview" aria-label="<?php esc_attr_e( 'Product-card live preview', 'itk-commerce-layouts' ); ?>">
                        <div class="itk-template-preview__toolbar">
                            <div>
                                <strong><?php esc_html_e( 'Shop card preview', 'itk-commerce-layouts' ); ?></strong>
                                <span data-itk-card-preview-status><?php esc_html_e( 'Unsaved card changes are previewed securely on the real storefront.', 'itk-commerce-layouts' ); ?></span>
                            </div>
                            <div class="itk-template-devices" role="group" aria-label="<?php esc_attr_e( 'Preview device', 'itk-commerce-layouts' ); ?>">
                                <button type="button" class="is-active" data-itk-card-device="desktop"><?php esc_html_e( 'Desktop', 'itk-commerce-layouts' ); ?></button>
                                <button type="button" data-itk-card-device="tablet"><?php esc_html_e( 'Tablet', 'itk-commerce-layouts' ); ?></button>
                                <button type="button" data-itk-card-device="mobile"><?php esc_html_e( 'Mobile', 'itk-commerce-layouts' ); ?></button>
                            </div>
                        </div>
                        <div class="itk-template-preview__stage" data-itk-card-stage data-device="desktop">
                            <iframe title="<?php esc_attr_e( 'Product-card preview', 'itk-commerce-layouts' ); ?>" src="<?php echo esc_url( $target ); ?>" data-itk-card-preview></iframe>
                        </div>
                    </section>
                </div>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage product cards.', 'itk-commerce-layouts' ) );
        }

        check_admin_referer( 'itk_commerce_save_product_cards' );

        $core       = Core::instance();
        $profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
        if ( ! $profile_id ) {
            $profile_id = 'site-default';
        }

        $profile = $core->profiles()->get( $profile_id );
        if ( ! is_array( $profile ) ) {
            $profile = $this->blank_profile( $profile_id );
        }

        $raw = isset( $_POST['product_card'] ) && is_array( $_POST['product_card'] ) ? wp_unslash( $_POST['product_card'] ) : array();

        if ( empty( $profile['modules'] ) || ! is_array( $profile['modules'] ) ) {
            $profile['modules'] = array();
        }
        if ( empty( $profile['modules']['enabled'] ) || ! is_array( $profile['modules']['enabled'] ) ) {
            $profile['modules']['enabled'] = array();
        }
        if ( empty( $profile['modules']['configuration'] ) || ! is_array( $profile['modules']['configuration'] ) ) {
            $profile['modules']['configuration'] = array();
        }
        if ( empty( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ] ) || ! is_array( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ] ) ) {
            $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ] = array();
        }

        $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['product_card'] = $this->sanitize_config( $raw, $this->models() );

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

        $settings                       = $core->settings()->all();
        $settings['active_profile_id']  = $profile_id;
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

    /** @return array<string,mixed> */
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

    /** @return array<string,array<string,string>> */
    private function models() {
        if ( function_exists( '\\ITK\\Commerce\\Theme\\product_card_models' ) ) {
            $models = \ITK\Commerce\Theme\product_card_models();
            if ( is_array( $models ) && $models ) {
                return $models;
            }
        }

        return array(
            'classic' => array( 'label' => 'Classic', 'description' => 'Balanced catalog card.' ),
            'minimal' => array( 'label' => 'Minimal', 'description' => 'Clean borderless card.' ),
            'boxed'   => array( 'label' => 'Boxed', 'description' => 'Defined card surface.' ),
            'overlay' => array( 'label' => 'Overlay', 'description' => 'Image-led action card.' ),
        );
    }

    /**
     * @param array<string,mixed> $profile Profile.
     * @return array<string,mixed>
     */
    private function config( array $profile ) {
        $defaults = array(
            'model'   => 'classic',
            'options' => array(
                'image_ratio'       => 'portrait',
                'content_align'     => 'left',
                'price_treatment'   => 'standard',
                'action_treatment'  => 'button',
                'hover_behavior'    => 'lift',
                'badge_style'       => 'pill',
                'show_state_badges' => true,
                'new_days'          => 30,
            ),
        );

        $stored = isset( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['product_card'] ) && is_array( $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['product_card'] )
            ? $profile['modules']['configuration'][ \ITK\Commerce\Layouts\MODULE_ID ]['product_card']
            : array();

        if ( ! empty( $stored['model'] ) ) {
            $defaults['model'] = sanitize_key( $stored['model'] );
        }
        if ( ! empty( $stored['options'] ) && is_array( $stored['options'] ) ) {
            $defaults['options'] = array_merge( $defaults['options'], $stored['options'] );
        }

        return $defaults;
    }

    /**
     * @param array<string,mixed>                $raw    Raw values.
     * @param array<string,array<string,string>> $models Allowed models.
     * @return array<string,mixed>
     */
    private function sanitize_config( array $raw, array $models ) {
        $model = isset( $raw['model'] ) ? sanitize_key( $raw['model'] ) : 'classic';
        if ( ! isset( $models[ $model ] ) ) {
            $model = 'classic';
        }

        $new_days = isset( $raw['new_days'] ) ? absint( $raw['new_days'] ) : 30;

        return array(
            'model'   => $model,
            'options' => array(
                'image_ratio'       => $this->choice( $raw, 'image_ratio', array( 'portrait', 'square', 'landscape' ), 'portrait' ),
                'content_align'     => $this->choice( $raw, 'content_align', array( 'left', 'center' ), 'left' ),
                'price_treatment'   => $this->choice( $raw, 'price_treatment', array( 'standard', 'emphasis', 'muted' ), 'standard' ),
                'action_treatment'  => $this->choice( $raw, 'action_treatment', array( 'button', 'outline', 'text' ), 'button' ),
                'hover_behavior'    => $this->choice( $raw, 'hover_behavior', array( 'none', 'lift', 'image-zoom' ), 'lift' ),
                'badge_style'       => $this->choice( $raw, 'badge_style', array( 'pill', 'corner', 'minimal' ), 'pill' ),
                'show_state_badges' => ! empty( $raw['show_state_badges'] ),
                'new_days'          => max( 1, min( 365, $new_days ) ),
            ),
        );
    }

    /**
     * @param array<string,mixed> $raw      Raw data.
     * @param string              $key      Key.
     * @param string[]            $allowed  Allowed values.
     * @param string              $fallback Fallback.
     * @return string
     */
    private function choice( array $raw, $key, array $allowed, $fallback ) {
        $value = isset( $raw[ $key ] ) ? sanitize_key( $raw[ $key ] ) : '';
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /** @return string */
    private function preview_target() {
        $fallback = home_url( '/' );
        $target   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $fallback;
        return $target ? $target : $fallback;
    }

    /**
     * @param array<string,array<string,string>> $models Models.
     * @param string                             $selected Selected model.
     * @return void
     */
    private function render_model_cards( array $models, $selected ) {
        echo '<div class="itk-commerce-model-grid">';
        foreach ( $models as $model_id => $definition ) {
            $model_id    = sanitize_key( $model_id );
            $label       = isset( $definition['label'] ) ? $definition['label'] : $model_id;
            $description = isset( $definition['description'] ) ? $definition['description'] : '';
            ?>
            <label class="itk-commerce-model-card">
                <input type="radio" name="product_card[model]" value="<?php echo esc_attr( $model_id ); ?>" <?php checked( $selected, $model_id ); ?> data-itk-card-model>
                <span class="itk-commerce-model-card__visual" data-area="product-card" data-model="<?php echo esc_attr( $model_id ); ?>"><i></i><i></i><i></i><i></i></span>
                <strong><?php echo esc_html( $label ); ?></strong>
                <?php if ( $description ) : ?><small><?php echo esc_html( $description ); ?></small><?php endif; ?>
            </label>
            <?php
        }
        echo '</div>';
    }

    /** @param string $version @return string */
    private function next_patch_version( $version ) {
        $parts = array_map( 'absint', explode( '.', preg_replace( '/[^0-9.].*$/', '', (string) $version ) ) );
        $parts = array_pad( array_slice( $parts, 0, 3 ), 3, 0 );
        $parts[2]++;
        return implode( '.', $parts );
    }
}
