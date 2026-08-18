<?php
/**
 * Dedicated administrator UI for controlled snippets.
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

defined( 'ABSPATH' ) || exit;

final class AdminPage {
    const PAGE_SLUG = 'itk-commerce-code-manager';

    /** @var SnippetRepository */
    private $repository;

    /** @param SnippetRepository $repository Repository. */
    public function __construct( SnippetRepository $repository ) {
        $this->repository = $repository;
    }

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_itk_commerce_save_snippet', array( $this, 'save' ) );
        add_action( 'admin_post_itk_commerce_toggle_snippet', array( $this, 'toggle' ) );
        add_action( 'admin_post_itk_commerce_rollback_snippet', array( $this, 'rollback' ) );
        add_action( 'admin_post_itk_commerce_delete_snippet', array( $this, 'delete' ) );
    }

    /** @param string $parent Parent menu. @return void */
    public function menu( $parent ) {
        add_submenu_page(
            $parent,
            __( 'Code Manager', 'itk-commerce-code-manager' ),
            __( 'Code Manager', 'itk-commerce-code-manager' ),
            'itk_manage_code',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /** @return void */
    public function render() {
        $this->require_capability();
        $edit_id = isset( $_GET['snippet'] ) ? sanitize_key( wp_unslash( $_GET['snippet'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $editing = $edit_id ? $this->repository->get( $edit_id ) : null;
        $notice = isset( $_GET['itk_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['itk_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $conditions = is_array( $editing ) && isset( $editing['conditions'] ) && is_array( $editing['conditions'] ) ? $editing['conditions'] : array();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Commerce Code Manager', 'itk-commerce-code-manager' ); ?></h1>
            <p><?php esc_html_e( 'Dedicated developer extension points with syntax validation, conditions, disabled-on-save behavior, version history, audit events, Safe Mode and automatic PHP error deactivation. Complex features should still be implemented as modules.', 'itk-commerce-code-manager' ); ?></p>
            <?php if ( defined( 'ITK_COMMERCE_CODE_SAFE_MODE' ) && ITK_COMMERCE_CODE_SAFE_MODE ) : ?><div class="notice notice-warning"><p><?php esc_html_e( 'Safe Mode is active. No Code Manager snippets execute on the storefront.', 'itk-commerce-code-manager' ); ?></p></div><?php endif; ?>
            <?php if ( $notice ) : ?><div class="notice notice-info is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

            <h2><?php echo esc_html( $editing ? __( 'Edit snippet', 'itk-commerce-code-manager' ) : __( 'New snippet', 'itk-commerce-code-manager' ) ); ?></h2>
            <p><strong><?php esc_html_e( 'Safety rule:', 'itk-commerce-code-manager' ); ?></strong> <?php esc_html_e( 'Saving a new or edited snippet always leaves it disabled. Review the saved version, then enable it explicitly below.', 'itk-commerce-code-manager' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_snippet">
                <input type="hidden" name="snippet_id" value="<?php echo esc_attr( $editing['id'] ?? '' ); ?>">
                <?php wp_nonce_field( 'itk_commerce_save_snippet' ); ?>
                <table class="form-table"><tbody>
                    <tr><th><label for="itk-snippet-title"><?php esc_html_e( 'Title', 'itk-commerce-code-manager' ); ?></label></th><td><input id="itk-snippet-title" class="regular-text" name="title" required value="<?php echo esc_attr( $editing['title'] ?? '' ); ?>"></td></tr>
                    <tr><th><label for="itk-snippet-type"><?php esc_html_e( 'Type', 'itk-commerce-code-manager' ); ?></label></th><td><select id="itk-snippet-type" name="type"><?php foreach ( $this->repository->types() as $type ) : ?><option value="<?php echo esc_attr( $type ); ?>" <?php selected( $editing['type'] ?? 'html', $type ); ?>><?php echo esc_html( strtoupper( $type ) ); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th><label for="itk-snippet-location"><?php esc_html_e( 'Location', 'itk-commerce-code-manager' ); ?></label></th><td><select id="itk-snippet-location" name="location"><?php foreach ( $this->repository->locations() as $location ) : ?><option value="<?php echo esc_attr( $location ); ?>" <?php selected( $editing['location'] ?? 'footer', $location ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $location ) ) ); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th><label for="itk-snippet-code"><?php esc_html_e( 'Code / template ID', 'itk-commerce-code-manager' ); ?></label></th><td><textarea id="itk-snippet-code" class="large-text code" rows="14" name="code" spellcheck="false"><?php echo esc_textarea( $editing['code'] ?? '' ); ?></textarea><p class="description"><?php esc_html_e( 'PHP must omit <?php tags. Elementor type expects a numeric template ID. JavaScript and CSS receive their wrapper automatically.', 'itk-commerce-code-manager' ); ?></p></td></tr>
                    <?php
                    $this->condition_row( 'languages', __( 'Languages', 'itk-commerce-code-manager' ), __( 'Comma-separated codes, e.g. de,ar,en. Empty = all.', 'itk-commerce-code-manager' ), $conditions );
                    $this->condition_row( 'devices', __( 'Devices', 'itk-commerce-code-manager' ), __( 'all, mobile or desktop. Server-side mobile detection includes tablets classified as mobile by WordPress.', 'itk-commerce-code-manager' ), $conditions );
                    $this->condition_row( 'roles', __( 'Roles', 'itk-commerce-code-manager' ), __( 'Comma-separated WordPress role slugs. Empty = all visitors.', 'itk-commerce-code-manager' ), $conditions );
                    $this->condition_row( 'page_types', __( 'Page types', 'itk-commerce-code-manager' ), __( 'front, blog, shop, product, product-category, cart, checkout, account, singular, archive, search, 404.', 'itk-commerce-code-manager' ), $conditions );
                    $this->condition_row( 'product_ids', __( 'Product IDs', 'itk-commerce-code-manager' ), __( 'Comma-separated product IDs. Empty = all products/pages.', 'itk-commerce-code-manager' ), $conditions );
                    $this->condition_row( 'categories', __( 'Product categories', 'itk-commerce-code-manager' ), __( 'Comma-separated product category slugs.', 'itk-commerce-code-manager' ), $conditions );
                    ?>
                </tbody></table>
                <?php submit_button( $editing ? __( 'Save as disabled revision', 'itk-commerce-code-manager' ) : __( 'Create disabled snippet', 'itk-commerce-code-manager' ) ); ?>
                <?php if ( $editing ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>"><?php esc_html_e( 'New snippet', 'itk-commerce-code-manager' ); ?></a><?php endif; ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Snippets', 'itk-commerce-code-manager' ); ?></h2>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Snippet', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'Type / location', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'State', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'Versions', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'Actions', 'itk-commerce-code-manager' ); ?></th></tr></thead><tbody>
            <?php if ( empty( $this->repository->all() ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No snippets created yet.', 'itk-commerce-code-manager' ); ?></td></tr><?php endif; ?>
            <?php foreach ( $this->repository->all() as $snippet ) : ?>
                <?php if ( ! is_array( $snippet ) ) { continue; } ?>
                <tr>
                    <td><strong><?php echo esc_html( $snippet['title'] ?? $snippet['id'] ); ?></strong><br><code><?php echo esc_html( $snippet['id'] ?? '' ); ?></code><?php if ( ! empty( $snippet['last_error'] ) ) : ?><p style="color:#b32d2e"><?php echo esc_html( $snippet['last_error'] ); ?></p><?php endif; ?></td>
                    <td><?php echo esc_html( strtoupper( (string) ( $snippet['type'] ?? '' ) ) ); ?><br><?php echo esc_html( (string) ( $snippet['location'] ?? '' ) ); ?></td>
                    <td><?php echo ! empty( $snippet['enabled'] ) ? '<strong>' . esc_html__( 'Enabled', 'itk-commerce-code-manager' ) . '</strong>' : esc_html__( 'Disabled', 'itk-commerce-code-manager' ); ?></td>
                    <td><?php echo esc_html( (string) count( isset( $snippet['versions'] ) && is_array( $snippet['versions'] ) ? $snippet['versions'] : array() ) ); ?></td>
                    <td>
                        <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'snippet' => $snippet['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'itk-commerce-code-manager' ); ?></a>
                        <?php $this->action_form( 'itk_commerce_toggle_snippet', $snippet['id'], ! empty( $snippet['enabled'] ) ? 'disable' : 'enable', ! empty( $snippet['enabled'] ) ? __( 'Disable', 'itk-commerce-code-manager' ) : __( 'Enable', 'itk-commerce-code-manager' ) ); ?>
                        <?php if ( ! empty( $snippet['versions'] ) ) : ?><?php $this->action_form( 'itk_commerce_rollback_snippet', $snippet['id'], 'rollback', __( 'Rollback', 'itk-commerce-code-manager' ) ); ?><?php endif; ?>
                        <?php $this->action_form( 'itk_commerce_delete_snippet', $snippet['id'], 'delete', __( 'Delete', 'itk-commerce-code-manager' ), 'button-link-delete' ); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>

            <h2><?php esc_html_e( 'Audit log', 'itk-commerce-code-manager' ); ?></h2>
            <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'Action', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'Snippet', 'itk-commerce-code-manager' ); ?></th><th><?php esc_html_e( 'User', 'itk-commerce-code-manager' ); ?></th></tr></thead><tbody>
            <?php foreach ( array_reverse( array_slice( $this->repository->audit_log(), -50 ) ) as $row ) : ?><tr><td><?php echo esc_html( $row['created_at'] ?? '' ); ?></td><td><?php echo esc_html( $row['action'] ?? '' ); ?></td><td><code><?php echo esc_html( $row['snippet_id'] ?? '' ); ?></code></td><td><?php echo esc_html( (string) ( $row['user_id'] ?? 0 ) ); ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
    }

    /** @return void */
    public function save() {
        $this->require_capability();
        check_admin_referer( 'itk_commerce_save_snippet' );
        $result = $this->repository->save(
            array(
                'id'         => isset( $_POST['snippet_id'] ) ? wp_unslash( $_POST['snippet_id'] ) : '',
                'title'      => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
                'type'       => isset( $_POST['type'] ) ? wp_unslash( $_POST['type'] ) : '',
                'location'   => isset( $_POST['location'] ) ? wp_unslash( $_POST['location'] ) : '',
                'code'       => isset( $_POST['code'] ) ? wp_unslash( $_POST['code'] ) : '',
                'conditions' => $this->posted_conditions(),
            ),
            get_current_user_id()
        );
        if ( is_wp_error( $result ) ) {
            $this->redirect( $result->get_error_message() );
        }
        $this->redirect( __( 'Snippet saved and disabled. Review it, then enable it explicitly.', 'itk-commerce-code-manager' ), $result['id'] ?? '' );
    }

    /** @return void */
    public function toggle() {
        $this->require_capability();
        check_admin_referer( 'itk_commerce_toggle_snippet' );
        $id = isset( $_POST['snippet_id'] ) ? sanitize_key( wp_unslash( $_POST['snippet_id'] ) ) : '';
        $enabled = isset( $_POST['operation'] ) && 'enable' === sanitize_key( wp_unslash( $_POST['operation'] ) );
        $result = $this->repository->set_enabled( $id, $enabled, get_current_user_id() );
        $this->redirect( is_wp_error( $result ) ? $result->get_error_message() : ( $enabled ? __( 'Snippet enabled.', 'itk-commerce-code-manager' ) : __( 'Snippet disabled.', 'itk-commerce-code-manager' ) ) );
    }

    /** @return void */
    public function rollback() {
        $this->require_capability();
        check_admin_referer( 'itk_commerce_rollback_snippet' );
        $id = isset( $_POST['snippet_id'] ) ? sanitize_key( wp_unslash( $_POST['snippet_id'] ) ) : '';
        $result = $this->repository->rollback( $id, get_current_user_id() );
        $this->redirect( is_wp_error( $result ) ? $result->get_error_message() : __( 'Previous version restored and left disabled for review.', 'itk-commerce-code-manager' ), $id );
    }

    /** @return void */
    public function delete() {
        $this->require_capability();
        check_admin_referer( 'itk_commerce_delete_snippet' );
        $id = isset( $_POST['snippet_id'] ) ? sanitize_key( wp_unslash( $_POST['snippet_id'] ) ) : '';
        $this->repository->delete( $id, get_current_user_id() );
        $this->redirect( __( 'Snippet deleted.', 'itk-commerce-code-manager' ) );
    }

    /** @return array<string,mixed> */
    private function posted_conditions() {
        $conditions = array();
        foreach ( array( 'languages', 'devices', 'roles', 'page_types', 'product_ids', 'categories' ) as $key ) {
            $raw = isset( $_POST[ 'condition_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'condition_' . $key ] ) ) : '';
            $conditions[ $key ] = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
        }
        return $conditions;
    }

    /** @param string $key Key. @param string $label Label. @param string $description Description. @param array<string,mixed> $conditions Conditions. @return void */
    private function condition_row( $key, $label, $description, array $conditions ) {
        $values = isset( $conditions[ $key ] ) && is_array( $conditions[ $key ] ) ? implode( ',', $conditions[ $key ] ) : '';
        echo '<tr><th><label for="itk-condition-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input id="itk-condition-' . esc_attr( $key ) . '" class="regular-text" name="condition_' . esc_attr( $key ) . '" value="' . esc_attr( $values ) . '"><p class="description">' . esc_html( $description ) . '</p></td></tr>';
    }

    /** @param string $action Action. @param string $id ID. @param string $operation Operation. @param string $label Label. @param string $class Class. @return void */
    private function action_form( $action, $id, $operation, $label, $class = 'button' ) {
        echo '<form style="display:inline" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="' . esc_attr( $action ) . '"><input type="hidden" name="snippet_id" value="' . esc_attr( $id ) . '"><input type="hidden" name="operation" value="' . esc_attr( $operation ) . '">';
        wp_nonce_field( $action );
        echo '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
    }

    /** @return void */
    private function require_capability() {
        if ( ! current_user_can( 'itk_manage_code' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage Commerce Suite code snippets.', 'itk-commerce-code-manager' ), '', array( 'response' => 403 ) );
        }
    }

    /** @param string $message Message. @param string $snippet_id Optional ID. @return void */
    private function redirect( $message, $snippet_id = '' ) {
        $args = array( 'page' => self::PAGE_SLUG, 'itk_notice' => $message );
        if ( $snippet_id ) {
            $args['snippet'] = sanitize_key( $snippet_id );
        }
        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }
}
