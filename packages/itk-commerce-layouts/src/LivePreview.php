<?php
/**
 * Authenticated frontend preview overrides for the layout builder.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

defined( 'ABSPATH' ) || exit;

final class LivePreview {
    const NONCE_ACTION = 'itk_commerce_layout_preview';

    /**
     * Override a resolved layout model only for an authorized preview request.
     *
     * @param string $model Current model.
     * @param string $area  Layout area.
     * @return string
     */
    public function layout_model( $model, $area ) {
        if ( ! $this->is_authorized() ) {
            return $model;
        }

        $parameter = 'header' === $area ? 'itk_header_model' : ( 'footer' === $area ? 'itk_footer_model' : '' );
        if ( ! $parameter || ! isset( $_GET[ $parameter ] ) ) {
            return $model;
        }

        return sanitize_key( wp_unslash( $_GET[ $parameter ] ) );
    }

    /**
     * Preview the mobile-bottom visibility without saving the profile.
     *
     * @param bool $enabled Current state.
     * @return bool
     */
    public function mobile_bottom_enabled( $enabled ) {
        if ( ! $this->is_authorized() || ! isset( $_GET['itk_mobile_bottom'] ) ) {
            return $enabled;
        }

        return '1' === sanitize_text_field( wp_unslash( $_GET['itk_mobile_bottom'] ) );
    }

    /**
     * Prevent authenticated preview URLs from being treated as indexable pages.
     *
     * @param array<string,bool> $robots Robot directives.
     * @return array<string,bool>
     */
    public function robots( $robots ) {
        if ( $this->is_authorized() ) {
            $robots['noindex']  = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    }

    /**
     * @return bool
     */
    private function is_authorized() {
        if ( empty( $_GET['itk_layout_preview'] ) || ! is_user_logged_in() || ! current_user_can( 'itk_manage_design' ) ) {
            return false;
        }

        if ( empty( $_GET['_itk_preview_nonce'] ) ) {
            return false;
        }

        $nonce = sanitize_text_field( wp_unslash( $_GET['_itk_preview_nonce'] ) );
        return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
    }
}
