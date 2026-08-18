<?php
/**
 * Customer-profile document template administration.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class DocumentTemplateAdmin {
    const PAGE_SLUG = 'itk-commerce-document-templates';

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_itk_commerce_save_document_templates', array( $this, 'save' ) );
    }

    /** @param string $parent Parent menu slug. @return void */
    public function menu( $parent ) {
        add_submenu_page(
            $parent,
            __( 'Document Templates', 'itk-commerce-documents' ),
            __( 'Document Templates', 'itk-commerce-documents' ),
            'itk_manage_documents',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /** @return void */
    public function render() {
        $this->require_capability();
        $profile = $this->active_profile();
        if ( ! $profile ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Document Templates', 'itk-commerce-documents' ) . '</h1><div class="notice notice-warning"><p>' . esc_html__( 'Select an active Commerce Suite customer profile first.', 'itk-commerce-documents' ) . '</p></div></div>';
            return;
        }

        $config = $this->config( $profile );
        $languages = $this->languages( $profile );
        $types = $this->types();
        $saved = isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Document Templates', 'itk-commerce-documents' ); ?></h1>
            <p><?php echo esc_html( sprintf( __( 'Profile: %s. Configure customer/language-specific labels and visual accents without modifying the reusable Documents module.', 'itk-commerce-documents' ), $profile['name'] ?? $profile['profile_id'] ) ); ?></p>
            <?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Document templates saved.', 'itk-commerce-documents' ); ?></p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_document_templates">
                <?php wp_nonce_field( 'itk_commerce_save_document_templates' ); ?>

                <table class="form-table"><tbody><tr><th><label for="itk-doc-global-footer"><?php esc_html_e( 'Global document footer', 'itk-commerce-documents' ); ?></label></th><td><textarea id="itk-doc-global-footer" class="large-text" rows="3" name="global_footer"><?php echo esc_textarea( $config['footer'] ?? '' ); ?></textarea><p class="description"><?php esc_html_e( 'Used when a document/language-specific footer is empty.', 'itk-commerce-documents' ); ?></p></td></tr></tbody></table>

                <?php foreach ( $types as $type => $type_label ) : ?>
                    <h2><?php echo esc_html( $type_label ); ?></h2>
                    <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Language', 'itk-commerce-documents' ); ?></th><th><?php esc_html_e( 'Title', 'itk-commerce-documents' ); ?></th><th><?php esc_html_e( 'Accent', 'itk-commerce-documents' ); ?></th><th><?php esc_html_e( 'Footer override', 'itk-commerce-documents' ); ?></th></tr></thead><tbody>
                    <?php foreach ( $languages as $language => $language_label ) : ?>
                        <?php $row = $config['templates'][ $type ][ $language ] ?? array(); $row = is_array( $row ) ? $row : array(); ?>
                        <tr>
                            <td><strong><?php echo esc_html( $language_label ); ?></strong><br><code><?php echo esc_html( $language ); ?></code></td>
                            <td><input class="regular-text" name="templates[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $language ); ?>][title]" value="<?php echo esc_attr( $row['title'] ?? '' ); ?>"></td>
                            <td><input type="color" name="templates[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $language ); ?>][accent]" value="<?php echo esc_attr( sanitize_hex_color( $row['accent'] ?? '' ) ?: '#222222' ); ?>"></td>
                            <td><textarea rows="2" name="templates[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $language ); ?>][footer]" class="large-text"><?php echo esc_textarea( $row['footer'] ?? '' ); ?></textarea></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table>
                <?php endforeach; ?>
                <?php submit_button( __( 'Save document templates', 'itk-commerce-documents' ) ); ?>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function save() {
        $this->require_capability();
        check_admin_referer( 'itk_commerce_save_document_templates' );
        $profile = $this->active_profile();
        if ( ! $profile ) {
            wp_die( esc_html__( 'No active Commerce Suite profile is available.', 'itk-commerce-documents' ) );
        }

        $posted = isset( $_POST['templates'] ) && is_array( $_POST['templates'] ) ? wp_unslash( $_POST['templates'] ) : array();
        $languages = array_keys( $this->languages( $profile ) );
        $types = array_keys( $this->types() );
        $templates = array();

        foreach ( $types as $type ) {
            foreach ( $languages as $language ) {
                $row = isset( $posted[ $type ][ $language ] ) && is_array( $posted[ $type ][ $language ] ) ? $posted[ $type ][ $language ] : array();
                $title = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
                $accent = isset( $row['accent'] ) ? sanitize_hex_color( $row['accent'] ) : '';
                $footer = isset( $row['footer'] ) ? sanitize_textarea_field( $row['footer'] ) : '';
                if ( '' === $title && '' === $accent && '' === $footer ) {
                    continue;
                }
                $templates[ $type ][ $language ] = array(
                    'title'  => $title,
                    'accent' => $accent ?: '#222222',
                    'footer' => $footer,
                );
            }
        }

        if ( empty( $profile['modules'] ) || ! is_array( $profile['modules'] ) ) {
            $profile['modules'] = array( 'enabled' => array(), 'configuration' => array() );
        }
        if ( empty( $profile['modules']['configuration'] ) || ! is_array( $profile['modules']['configuration'] ) ) {
            $profile['modules']['configuration'] = array();
        }
        $existing = isset( $profile['modules']['configuration'][ MODULE_ID ] ) && is_array( $profile['modules']['configuration'][ MODULE_ID ] ) ? $profile['modules']['configuration'][ MODULE_ID ] : array();
        $existing['footer'] = isset( $_POST['global_footer'] ) ? sanitize_textarea_field( wp_unslash( $_POST['global_footer'] ) ) : '';
        $existing['templates'] = $templates;
        $profile['modules']['configuration'][ MODULE_ID ] = $existing;

        $core = \ITK\Commerce\Core\Core::instance();
        $result = $core->profiles()->save( $profile );
        if ( is_wp_error( $result ) ) {
            wp_die( esc_html( $result->get_error_message() ) );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'saved' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /** @return array<string,mixed>|null */
    private function active_profile() {
        if ( ! class_exists( '\ITK\Commerce\Core\Core' ) ) {
            return null;
        }
        $core = \ITK\Commerce\Core\Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;
        return is_array( $profile ) ? $profile : null;
    }

    /** @param array<string,mixed> $profile Profile. @return array<string,mixed> */
    private function config( array $profile ) {
        $config = $profile['modules']['configuration'][ MODULE_ID ] ?? array();
        return is_array( $config ) ? $config : array();
    }

    /** @param array<string,mixed> $profile Profile. @return array<string,string> */
    private function languages( array $profile ) {
        $result = array( 'default' => __( 'Default / fallback', 'itk-commerce-documents' ) );
        $languages = isset( $profile['languages'] ) && is_array( $profile['languages'] ) ? $profile['languages'] : array();
        foreach ( $languages as $language ) {
            if ( ! is_array( $language ) || empty( $language['code'] ) ) {
                continue;
            }
            $code = sanitize_key( $language['code'] );
            if ( $code ) {
                $result[ $code ] = isset( $language['label'] ) ? sanitize_text_field( $language['label'] ) : strtoupper( $code );
            }
        }
        return $result;
    }

    /** @return array<string,string> */
    private function types() {
        return array(
            'invoice'            => __( 'Invoice', 'itk-commerce-documents' ),
            'invoice-correction' => __( 'Invoice correction / cancellation', 'itk-commerce-documents' ),
            'delivery-note'      => __( 'Delivery note', 'itk-commerce-documents' ),
            'return-form'        => __( 'Return form', 'itk-commerce-documents' ),
            'packing-list'       => __( 'Packing list', 'itk-commerce-documents' ),
        );
    }

    /** @return void */
    private function require_capability() {
        if ( ! current_user_can( 'itk_manage_documents' ) ) {
            wp_die( esc_html__( 'You are not allowed to configure document templates.', 'itk-commerce-documents' ), '', array( 'response' => 403 ) );
        }
    }
}
