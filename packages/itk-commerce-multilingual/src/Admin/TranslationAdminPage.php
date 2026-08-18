<?php
/**
 * Translation administration surface.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual\Admin;

defined( 'ABSPATH' ) || exit;

final class TranslationAdminPage {
    const PAGE_SLUG = 'itk-commerce-translations';

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_post_itk_commerce_translation_draft', array( $this, 'create_draft' ) );
        add_action( 'admin_post_itk_commerce_translation_transition', array( $this, 'transition' ) );
        add_action( 'admin_post_itk_commerce_translation_import', array( $this, 'import' ) );
        add_action( 'admin_post_itk_commerce_translation_export', array( $this, 'export' ) );
    }

    /** @param string $parent_slug Commerce Suite menu slug. @return void */
    public function register_menu( $parent_slug ) {
        add_submenu_page(
            $parent_slug,
            __( 'Translations', 'itk-commerce-multilingual' ),
            __( 'Translations', 'itk-commerce-multilingual' ),
            'itk_manage_translations',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /** @return void */
    public function render() {
        $this->require_manage();
        $notice = isset( $_GET['itk_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['itk_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Translations', 'itk-commerce-multilingual' ); ?></h1>
            <p><?php esc_html_e( 'Create draft translations, move revisions through review, and exchange translation files without bypassing the publishing workflow.', 'itk-commerce-multilingual' ); ?></p>
            <?php if ( $notice ) : ?><div class="notice notice-info"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

            <h2><?php esc_html_e( 'Create draft', 'itk-commerce-multilingual' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_translation_draft">
                <?php wp_nonce_field( 'itk_commerce_translation_draft' ); ?>
                <table class="form-table"><tbody>
                    <tr><th><label for="itk-key"><?php esc_html_e( 'Translation key', 'itk-commerce-multilingual' ); ?></label></th><td><input class="regular-text" id="itk-key" name="translation_key" required></td></tr>
                    <tr><th><label for="itk-language"><?php esc_html_e( 'Language', 'itk-commerce-multilingual' ); ?></label></th><td><input id="itk-language" name="language_code" required placeholder="de"></td></tr>
                    <tr><th><label for="itk-source"><?php esc_html_e( 'Source text', 'itk-commerce-multilingual' ); ?></label></th><td><textarea class="large-text" id="itk-source" name="source" rows="3"></textarea></td></tr>
                    <tr><th><label for="itk-value"><?php esc_html_e( 'Translation', 'itk-commerce-multilingual' ); ?></label></th><td><textarea class="large-text" id="itk-value" name="translation_value" rows="4" required></textarea></td></tr>
                </tbody></table>
                <?php submit_button( __( 'Create draft', 'itk-commerce-multilingual' ) ); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Review workflow', 'itk-commerce-multilingual' ); ?></h2>
            <p><?php esc_html_e( 'Use a revision ID from the draft/import result. Translators may submit drafts for review. Publishing requires Commerce administration rights.', 'itk-commerce-multilingual' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_translation_transition">
                <?php wp_nonce_field( 'itk_commerce_translation_transition' ); ?>
                <input type="number" min="1" name="revision_id" required placeholder="Revision ID">
                <select name="transition">
                    <option value="review"><?php esc_html_e( 'Submit for review', 'itk-commerce-multilingual' ); ?></option>
                    <option value="draft"><?php esc_html_e( 'Return to draft', 'itk-commerce-multilingual' ); ?></option>
                    <?php if ( current_user_can( 'itk_manage_commerce' ) ) : ?><option value="publish"><?php esc_html_e( 'Publish', 'itk-commerce-multilingual' ); ?></option><?php endif; ?>
                </select>
                <?php submit_button( __( 'Apply transition', 'itk-commerce-multilingual' ), 'secondary', 'submit', false ); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Import', 'itk-commerce-multilingual' ); ?></h2>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_translation_import">
                <?php wp_nonce_field( 'itk_commerce_translation_import' ); ?>
                <select name="format"><option value="json">JSON</option><option value="csv">CSV</option><option value="xliff">XLIFF 1.2</option></select>
                <input type="file" name="translation_file" required accept=".json,.csv,.xlf,.xliff,text/csv,application/json,application/xml,text/xml">
                <?php submit_button( __( 'Import as drafts', 'itk-commerce-multilingual' ), 'secondary', 'submit', false ); ?>
            </form>

            <h2><?php esc_html_e( 'Export', 'itk-commerce-multilingual' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_translation_export">
                <?php wp_nonce_field( 'itk_commerce_translation_export' ); ?>
                <select name="format"><option value="json">JSON</option><option value="csv">CSV</option><option value="xliff">XLIFF 1.2</option></select>
                <select name="scope"><option value="published"><?php esc_html_e( 'Published', 'itk-commerce-multilingual' ); ?></option><option value="current"><?php esc_html_e( 'Current revision', 'itk-commerce-multilingual' ); ?></option></select>
                <?php submit_button( __( 'Download export', 'itk-commerce-multilingual' ), 'secondary', 'submit', false ); ?>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function create_draft() {
        $this->require_manage();
        check_admin_referer( 'itk_commerce_translation_draft' );
        $workflow = apply_filters( 'itk_commerce_translation_workflow', null );
        if ( ! is_object( $workflow ) || ! method_exists( $workflow, 'create_draft' ) ) {
            $this->redirect( __( 'Translation workflow is unavailable.', 'itk-commerce-multilingual' ) );
        }
        $result = $workflow->create_draft(
            isset( $_POST['translation_key'] ) ? wp_unslash( $_POST['translation_key'] ) : '',
            isset( $_POST['language_code'] ) ? wp_unslash( $_POST['language_code'] ) : '',
            isset( $_POST['translation_value'] ) ? wp_unslash( $_POST['translation_value'] ) : '',
            isset( $_POST['source'] ) ? wp_unslash( $_POST['source'] ) : '',
            get_current_user_id()
        );
        $this->redirect( is_wp_error( $result ) ? $result->get_error_message() : sprintf( __( 'Draft revision %d created.', 'itk-commerce-multilingual' ), isset( $result['id'] ) ? (int) $result['id'] : 0 ) );
    }

    /** @return void */
    public function transition() {
        $this->require_manage();
        check_admin_referer( 'itk_commerce_translation_transition' );
        $workflow = apply_filters( 'itk_commerce_translation_workflow', null );
        $revision_id = isset( $_POST['revision_id'] ) ? absint( $_POST['revision_id'] ) : 0;
        $transition = isset( $_POST['transition'] ) ? sanitize_key( wp_unslash( $_POST['transition'] ) ) : '';
        if ( ! is_object( $workflow ) || ! $revision_id ) {
            $this->redirect( __( 'Translation workflow or revision is unavailable.', 'itk-commerce-multilingual' ) );
        }
        if ( 'publish' === $transition ) {
            if ( ! current_user_can( 'itk_manage_commerce' ) ) {
                wp_die( esc_html__( 'You are not allowed to publish translations.', 'itk-commerce-multilingual' ), 403 );
            }
            $result = $workflow->publish( $revision_id, get_current_user_id() );
        } elseif ( 'draft' === $transition ) {
            $result = $workflow->return_to_draft( $revision_id, get_current_user_id() );
        } else {
            $result = $workflow->submit_for_review( $revision_id, get_current_user_id() );
        }
        $this->redirect( is_wp_error( $result ) ? $result->get_error_message() : __( 'Translation workflow updated.', 'itk-commerce-multilingual' ) );
    }

    /** @return void */
    public function import() {
        $this->require_manage();
        check_admin_referer( 'itk_commerce_translation_import' );
        if ( empty( $_FILES['translation_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['translation_file']['tmp_name'] ) ) {
            $this->redirect( __( 'No valid import file was uploaded.', 'itk-commerce-multilingual' ) );
        }
        $transfer = apply_filters( 'itk_commerce_translation_transfer', null );
        $format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'json';
        $payload = file_get_contents( $_FILES['translation_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $result = is_object( $transfer ) && method_exists( $transfer, 'import_as_drafts' ) ? $transfer->import_as_drafts( $format, (string) $payload, get_current_user_id() ) : false;
        if ( is_wp_error( $result ) ) {
            $this->redirect( $result->get_error_message() );
        }
        $count = is_array( $result ) && isset( $result['created'] ) && is_array( $result['created'] ) ? count( $result['created'] ) : 0;
        $this->redirect( sprintf( __( '%d draft translations imported.', 'itk-commerce-multilingual' ), $count ) );
    }

    /** @return void */
    public function export() {
        $this->require_manage();
        check_admin_referer( 'itk_commerce_translation_export' );
        $transfer = apply_filters( 'itk_commerce_translation_transfer', null );
        $format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'json';
        $scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'published';
        $payload = is_object( $transfer ) && method_exists( $transfer, 'export' ) ? $transfer->export( $format, array( 'scope' => $scope ) ) : false;
        if ( is_wp_error( $payload ) || ! is_string( $payload ) ) {
            $this->redirect( is_wp_error( $payload ) ? $payload->get_error_message() : __( 'Translation export failed.', 'itk-commerce-multilingual' ) );
        }
        $mime = 'csv' === $format ? 'text/csv' : ( 'json' === $format ? 'application/json' : 'application/xml' );
        nocache_headers();
        header( 'Content-Type: ' . $mime . '; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="itk-commerce-translations.' . ( 'xliff' === $format ? 'xlf' : $format ) . '"' );
        echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated export payload.
        exit;
    }

    /** @return void */
    private function require_manage() {
        if ( ! current_user_can( 'itk_manage_translations' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage translations.', 'itk-commerce-multilingual' ), 403 );
        }
    }

    /** @param string $message Notice. @return void */
    private function redirect( $message ) {
        wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'itk_notice' => rawurlencode( $message ) ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
