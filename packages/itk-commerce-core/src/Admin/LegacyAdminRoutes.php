<?php
/**
 * Backward-compatible redirects for renamed Commerce Suite admin screens.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Admin;

defined( 'ABSPATH' ) || exit;

final class LegacyAdminRoutes {
    /** @return void */
    public function register() {
        add_action( 'admin_init', array( $this, 'redirect_legacy_routes' ), 1 );
    }

    /**
     * Redirect stale internal/bookmarked admin page slugs to their canonical
     * current screen. No state is changed by these GET redirects.
     *
     * @return void
     */
    public function redirect_legacy_routes() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            return;
        }

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin route selection.

        $routes = array(
            'itk-commerce-mega-menu-content' => 'itk-commerce-mega-content',
        );

        if ( ! isset( $routes[ $page ] ) ) {
            return;
        }

        wp_safe_redirect( admin_url( 'themes.php?page=' . $routes[ $page ] ) );
        exit;
    }
}
