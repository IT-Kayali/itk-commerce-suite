<?php
/**
 * Manual Header/Footer source editor for the active customer profile.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts\Admin;

use ITK\Commerce\Core\Core;
use ITK\Commerce\Layouts\CustomLayoutRenderer;

defined( 'ABSPATH' ) || exit;

final class CustomHeaderFooterPage {
    const PAGE_SLUG = 'itk-commerce-header-footer';

    /** @var string */
    private $page_hook = '';

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_admin_menu', array( $this, 'add_menu' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_custom_header_footer', array( $this, 'save' ) );
    }

    /** @param string $parent_slug Commerce Suite parent menu slug. @param object $hub Admin hub. @return void */
    public function add_menu( $parent_slug, $hub = null ) {
        unset( $hub );
        $this->page_hook = add_submenu_page(
            $parent_slug,
            __( 'Custom Header & Footer', 'itk-commerce-layouts' ),
            __( 'Header & Footer', 'itk-commerce-layouts' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /** @param string $hook_suffix Current admin hook. @return void */
    public function enqueue_assets( $hook_suffix ) {
        if ( $this->page_hook !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-custom-header-footer',
            plugins_url( 'assets/admin/custom-header-footer.css', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION
        );
        wp_enqueue_script(
            'itk-commerce-custom-header-footer',
            plugins_url( 'assets/admin/custom-header-footer.js', \ITK\Commerce\Layouts\FILE ),
            array(),
            \ITK\Commerce\Layouts\VERSION,
            true
        );
    }

    /** @return void */
    public function render() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce layouts.', 'itk-commerce-layouts' ) );
        }

        $profile = $this->active_profile();
        if ( ! is_array( $profile ) ) {
            ?>
            <div class="wrap itk-admin-hub itk-hf-editor">
                <div class="notice notice-error"><p><?php esc_html_e( 'Select an active customer profile before configuring Header and Footer sources.', 'itk-commerce-layouts' ); ?></p></div>
            </div>
            <?php
            return;
        }

        $saved = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) );
        $error = isset( $_GET['itk_error'] ) ? sanitize_key( wp_unslash( $_GET['itk_error'] ) ) : '';
        ?>
        <div class="wrap itk-admin-hub itk-hf-editor">
            <div class="itk-admin-hero">
                <div>
                    <span class="itk-admin-eyebrow"><?php esc_html_e( 'IT-Kayali Commerce Suite', 'itk-commerce-layouts' ); ?></span>
                    <h1><?php esc_html_e( 'Custom Header & Footer', 'itk-commerce-layouts' ); ?></h1>
                    <p><?php esc_html_e( 'Choose the rendering source independently for Header and Footer. Paste existing HTML/CSS/JavaScript, use a normal shortcode, render an Elementor saved template without Elementor Pro, keep the Theme model, or disable the area completely.', 'itk-commerce-layouts' ); ?></p>
                </div>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Header and Footer sources saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>
            <?php if ( $error ) : ?>
                <div class="notice notice-error"><p><?php esc_html_e( 'The Header/Footer configuration could not be saved.', 'itk-commerce-layouts' ); ?></p></div>
            <?php endif; ?>

            <div class="itk-hf-editor__intro">
                <div>
                    <strong><?php echo esc_html( $profile['name'] ); ?></strong>
                    <code><?php echo esc_html( $profile['profile_id'] ); ?></code>
                    <p><?php esc_html_e( 'The primary value is used on every device. Tablet and Mobile are optional overrides; when they are blank the primary value is inherited automatically.', 'itk-commerce-layouts' ); ?></p>
                </div>
                <div class="itk-hf-editor__actions">
                    <a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=itk-commerce-layout-builder' ) ); ?>"><?php esc_html_e( 'Theme Layout Builder', 'itk-commerce-layouts' ); ?></a>
                    <a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Open storefront', 'itk-commerce-layouts' ); ?></a>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_custom_header_footer">
                <?php wp_nonce_field( 'itk_commerce_save_custom_header_footer' ); ?>

                <div class="itk-hf-editor__grid">
                    <?php $this->render_area_editor( 'header', __( 'Header', 'itk-commerce-layouts' ), $this->area_assignment( $profile, 'header' ) ); ?>
                    <?php $this->render_area_editor( 'footer', __( 'Footer', 'itk-commerce-layouts' ), $this->area_assignment( $profile, 'footer' ) ); ?>
                </div>

                <div class="itk-hf-savebar">
                    <p><?php esc_html_e( 'Saving only changes the active customer profile. Products, orders, customers and WooCommerce data are untouched.', 'itk-commerce-layouts' ); ?></p>
                    <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Header & Footer', 'itk-commerce-layouts' ); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage Commerce layouts.', 'itk-commerce-layouts' ) );
        }

        check_admin_referer( 'itk_commerce_save_custom_header_footer' );

        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;
        $payload    = isset( $_POST['layout'] ) && is_array( $_POST['layout'] ) ? wp_unslash( $_POST['layout'] ) : array();

        if ( ! is_array( $profile ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&itk_error=profile' ) );
            exit;
        }

        if ( empty( $profile['layouts'] ) || ! is_array( $profile['layouts'] ) ) {
            $profile['layouts'] = array();
        }

        foreach ( array( 'header', 'footer' ) as $area ) {
            $existing = isset( $profile['layouts'][ $area ] ) && is_array( $profile['layouts'][ $area ] )
                ? $profile['layouts'][ $area ]
                : array();
            $submitted = isset( $payload[ $area ] ) && is_array( $payload[ $area ] ) ? $payload[ $area ] : array();
            $source    = isset( $submitted['source'] ) ? sanitize_key( $submitted['source'] ) : 'theme';
            if ( ! in_array( $source, CustomLayoutRenderer::sources(), true ) ) {
                $source = 'theme';
            }

            $existing['source']  = $source;
            $existing['content'] = $this->sanitize_content( $submitted );
            $profile['layouts'][ $area ] = $existing;
        }

        $profile['profile_version'] = $this->next_patch_version( isset( $profile['profile_version'] ) ? $profile['profile_version'] : '1.0.0' );
        $result = $core->profiles()->save( $profile );

        $redirect = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( add_query_arg( 'itk_error', sanitize_key( $result->get_error_code() ), $redirect ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( 'updated', '1', $redirect ) );
        exit;
    }

    /**
     * @param string              $area       Header/footer.
     * @param string              $label      Label.
     * @param array<string,mixed> $assignment Existing assignment.
     * @return void
     */
    private function render_area_editor( $area, $label, array $assignment ) {
        $source  = isset( $assignment['source'] ) ? sanitize_key( $assignment['source'] ) : 'theme';
        $content = isset( $assignment['content'] ) && is_array( $assignment['content'] ) ? $assignment['content'] : array();
        $html    = isset( $content['html'] ) && is_array( $content['html'] ) ? $content['html'] : array();
        $short   = isset( $content['shortcode'] ) && is_array( $content['shortcode'] ) ? $content['shortcode'] : array();
        $elem    = isset( $content['elementor'] ) && is_array( $content['elementor'] ) ? $content['elementor'] : array();
        ?>
        <section class="itk-hf-area" data-itk-layout-source-editor>
            <div class="itk-hf-area__head">
                <h2><?php echo esc_html( $label ); ?></h2>
                <p><?php echo esc_html( sprintf( __( 'Rendering source for the site %s.', 'itk-commerce-layouts' ), strtolower( $label ) ) ); ?></p>
            </div>
            <div class="itk-hf-area__body">
                <label class="itk-hf-field">
                    <span><?php esc_html_e( 'Source', 'itk-commerce-layouts' ); ?></span>
                    <select name="layout[<?php echo esc_attr( $area ); ?>][source]" data-itk-layout-source-select>
                        <option value="theme" <?php selected( $source, 'theme' ); ?>><?php esc_html_e( 'Theme model', 'itk-commerce-layouts' ); ?></option>
                        <option value="custom_html" <?php selected( $source, 'custom_html' ); ?>><?php esc_html_e( 'Custom HTML + CSS + JavaScript', 'itk-commerce-layouts' ); ?></option>
                        <option value="elementor" <?php selected( $source, 'elementor' ); ?>><?php esc_html_e( 'Elementor saved template', 'itk-commerce-layouts' ); ?></option>
                        <option value="shortcode" <?php selected( $source, 'shortcode' ); ?>><?php esc_html_e( 'Shortcode', 'itk-commerce-layouts' ); ?></option>
                        <option value="disabled" <?php selected( $source, 'disabled' ); ?>><?php esc_html_e( 'Disabled / no output', 'itk-commerce-layouts' ); ?></option>
                    </select>
                </label>

                <div class="itk-hf-source-panel" data-itk-source-panel="theme">
                    <h3><?php esc_html_e( 'Theme model', 'itk-commerce-layouts' ); ?></h3>
                    <p><?php esc_html_e( 'The Commerce Layout Builder controls the Classic, Centered, Shop Search, Luxury and other reusable Theme models including context overrides.', 'itk-commerce-layouts' ); ?></p>
                    <a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=itk-commerce-layout-builder' ) ); ?>"><?php esc_html_e( 'Configure Theme model', 'itk-commerce-layouts' ); ?></a>
                </div>

                <div class="itk-hf-source-panel" data-itk-source-panel="custom_html">
                    <h3><?php esc_html_e( 'Custom HTML', 'itk-commerce-layouts' ); ?></h3>
                    <p><?php esc_html_e( 'Paste your existing markup here. Put CSS and JavaScript in their dedicated fields; embedded <style> and <script> tags are removed deliberately.', 'itk-commerce-layouts' ); ?></p>
                    <div class="itk-hf-device-grid">
                        <?php $this->textarea_field( $area, 'html', 'shared', __( 'Primary HTML', 'itk-commerce-layouts' ), isset( $html['shared'] ) ? $html['shared'] : '', true, __( 'Used on desktop and inherited by tablet/mobile when their override is blank.', 'itk-commerce-layouts' ) ); ?>
                        <?php $this->textarea_field( $area, 'html', 'tablet', __( 'Tablet HTML override', 'itk-commerce-layouts' ), isset( $html['tablet'] ) ? $html['tablet'] : '', false, __( 'Optional: 768–1024 px.', 'itk-commerce-layouts' ) ); ?>
                        <?php $this->textarea_field( $area, 'html', 'mobile', __( 'Mobile HTML override', 'itk-commerce-layouts' ), isset( $html['mobile'] ) ? $html['mobile'] : '', false, __( 'Optional: up to 767 px.', 'itk-commerce-layouts' ) ); ?>
                    </div>
                    <?php $this->textarea_field( $area, 'html', 'css', __( 'Custom CSS', 'itk-commerce-layouts' ), isset( $html['css'] ) ? $html['css'] : '', false, __( 'Paste CSS only, without <style> tags.', 'itk-commerce-layouts' ) ); ?>
                    <?php $this->textarea_field( $area, 'html', 'js', __( 'Custom JavaScript', 'itk-commerce-layouts' ), isset( $html['js'] ) ? $html['js'] : '', false, __( 'Paste JavaScript only, without <script> tags.', 'itk-commerce-layouts' ) ); ?>
                    <div class="itk-hf-note"><?php esc_html_e( 'HTML is sanitized through an explicit storefront allowlist that includes navigation, forms, SVG icons, ARIA and data attributes. JavaScript and CSS are limited to users with the Commerce design capability.', 'itk-commerce-layouts' ); ?></div>
                </div>

                <div class="itk-hf-source-panel" data-itk-source-panel="elementor">
                    <h3><?php esc_html_e( 'Elementor saved template', 'itk-commerce-layouts' ); ?></h3>
                    <p><?php esc_html_e( 'This uses ordinary Elementor Saved Templates and does not require Elementor Pro Theme Builder. Enter the numeric template ID.', 'itk-commerce-layouts' ); ?></p>
                    <div class="itk-hf-device-grid">
                        <?php $this->number_field( $area, 'elementor', 'shared', __( 'Primary template ID', 'itk-commerce-layouts' ), isset( $elem['shared'] ) ? $elem['shared'] : 0 ); ?>
                        <?php $this->number_field( $area, 'elementor', 'tablet', __( 'Tablet template ID', 'itk-commerce-layouts' ), isset( $elem['tablet'] ) ? $elem['tablet'] : 0 ); ?>
                        <?php $this->number_field( $area, 'elementor', 'mobile', __( 'Mobile template ID', 'itk-commerce-layouts' ), isset( $elem['mobile'] ) ? $elem['mobile'] : 0 ); ?>
                    </div>
                    <div class="itk-hf-note"><?php esc_html_e( 'If Elementor is unavailable or the primary template ID is empty, the Theme model is used automatically instead of producing a broken page.', 'itk-commerce-layouts' ); ?></div>
                </div>

                <div class="itk-hf-source-panel" data-itk-source-panel="shortcode">
                    <h3><?php esc_html_e( 'Shortcode', 'itk-commerce-layouts' ); ?></h3>
                    <p><?php esc_html_e( 'Use any registered WordPress shortcode that renders your Header or Footer.', 'itk-commerce-layouts' ); ?></p>
                    <div class="itk-hf-device-grid">
                        <?php $this->textarea_field( $area, 'shortcode', 'shared', __( 'Primary shortcode', 'itk-commerce-layouts' ), isset( $short['shared'] ) ? $short['shared'] : '', false, '[your_header]' ); ?>
                        <?php $this->textarea_field( $area, 'shortcode', 'tablet', __( 'Tablet shortcode override', 'itk-commerce-layouts' ), isset( $short['tablet'] ) ? $short['tablet'] : '', false, __( 'Optional.', 'itk-commerce-layouts' ) ); ?>
                        <?php $this->textarea_field( $area, 'shortcode', 'mobile', __( 'Mobile shortcode override', 'itk-commerce-layouts' ), isset( $short['mobile'] ) ? $short['mobile'] : '', false, __( 'Optional.', 'itk-commerce-layouts' ) ); ?>
                    </div>
                </div>

                <div class="itk-hf-source-panel" data-itk-source-panel="disabled">
                    <h3><?php esc_html_e( 'No output', 'itk-commerce-layouts' ); ?></h3>
                    <div class="itk-hf-warning"><?php echo esc_html( sprintf( __( 'The %s will not be rendered at all. Use this only when the page design intentionally has no %s.', 'itk-commerce-layouts' ), strtolower( $label ), strtolower( $label ) ) ); ?></div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * @param string $area Area.
     * @param string $group Content group.
     * @param string $key Key.
     * @param string $label Label.
     * @param mixed  $value Value.
     * @param bool   $large Large editor.
     * @param string $hint Hint.
     * @return void
     */
    private function textarea_field( $area, $group, $key, $label, $value, $large = false, $hint = '' ) {
        ?>
        <label class="itk-hf-field">
            <span><?php echo esc_html( $label ); ?></span>
            <textarea class="<?php echo $large ? 'itk-hf-code--large' : ''; ?>" name="layout[<?php echo esc_attr( $area ); ?>][<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $key ); ?>]" spellcheck="false"><?php echo esc_textarea( (string) $value ); ?></textarea>
            <?php if ( $hint ) : ?><small><?php echo esc_html( $hint ); ?></small><?php endif; ?>
        </label>
        <?php
    }

    /** @param string $area Area. @param string $group Group. @param string $key Key. @param string $label Label. @param mixed $value Value. @return void */
    private function number_field( $area, $group, $key, $label, $value ) {
        ?>
        <label class="itk-hf-field">
            <span><?php echo esc_html( $label ); ?></span>
            <input type="number" min="0" step="1" name="layout[<?php echo esc_attr( $area ); ?>][<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) absint( $value ) ); ?>">
        </label>
        <?php
    }

    /** @param array<string,mixed> $submitted Submitted area. @return array<string,mixed> */
    private function sanitize_content( array $submitted ) {
        $html = isset( $submitted['html'] ) && is_array( $submitted['html'] ) ? $submitted['html'] : array();
        $shortcode = isset( $submitted['shortcode'] ) && is_array( $submitted['shortcode'] ) ? $submitted['shortcode'] : array();
        $elementor = isset( $submitted['elementor'] ) && is_array( $submitted['elementor'] ) ? $submitted['elementor'] : array();

        return array(
            'html' => array(
                'shared' => CustomLayoutRenderer::sanitize_html( isset( $html['shared'] ) ? $html['shared'] : '' ),
                'tablet' => CustomLayoutRenderer::sanitize_html( isset( $html['tablet'] ) ? $html['tablet'] : '' ),
                'mobile' => CustomLayoutRenderer::sanitize_html( isset( $html['mobile'] ) ? $html['mobile'] : '' ),
                'css'    => CustomLayoutRenderer::sanitize_css( isset( $html['css'] ) ? $html['css'] : '' ),
                'js'     => CustomLayoutRenderer::sanitize_js( isset( $html['js'] ) ? $html['js'] : '' ),
            ),
            'shortcode' => array(
                'shared' => CustomLayoutRenderer::sanitize_shortcode( isset( $shortcode['shared'] ) ? $shortcode['shared'] : '' ),
                'tablet' => CustomLayoutRenderer::sanitize_shortcode( isset( $shortcode['tablet'] ) ? $shortcode['tablet'] : '' ),
                'mobile' => CustomLayoutRenderer::sanitize_shortcode( isset( $shortcode['mobile'] ) ? $shortcode['mobile'] : '' ),
            ),
            'elementor' => array(
                'shared' => isset( $elementor['shared'] ) ? absint( $elementor['shared'] ) : 0,
                'tablet' => isset( $elementor['tablet'] ) ? absint( $elementor['tablet'] ) : 0,
                'mobile' => isset( $elementor['mobile'] ) ? absint( $elementor['mobile'] ) : 0,
            ),
        );
    }

    /** @param array<string,mixed> $profile Profile. @param string $area Area. @return array<string,mixed> */
    private function area_assignment( array $profile, $area ) {
        $assignment = isset( $profile['layouts'][ $area ] ) && is_array( $profile['layouts'][ $area ] )
            ? $profile['layouts'][ $area ]
            : array();
        if ( empty( $assignment['source'] ) ) {
            $assignment['source'] = 'theme';
        }
        if ( empty( $assignment['content'] ) || ! is_array( $assignment['content'] ) ) {
            $assignment['content'] = array();
        }
        return $assignment;
    }

    /** @return array<string,mixed>|null */
    private function active_profile() {
        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;
        return is_array( $profile ) ? $profile : null;
    }

    /** @param mixed $version Semantic version. @return string */
    private function next_patch_version( $version ) {
        $parts = array_map( 'absint', explode( '.', preg_replace( '/[^0-9.].*$/', '', (string) $version ) ) );
        $parts = array_pad( array_slice( $parts, 0, 3 ), 3, 0 );
        $parts[2]++;
        return implode( '.', $parts );
    }
}
