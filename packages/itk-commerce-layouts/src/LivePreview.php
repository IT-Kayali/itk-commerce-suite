<?php
/**
 * Authenticated frontend preview overrides for Commerce layout builders.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;
defined( 'ABSPATH' ) || exit;

final class LivePreview {
    const NONCE_ACTION = 'itk_commerce_layout_preview';

    /**
     * Override a resolved Header/Footer layout model for an authorized preview.
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
     * Override Shop/Product/Cart/Checkout model during an authorized preview.
     *
     * @param string $model Current model.
     * @param string $area  Commerce area.
     * @return string
     */
    public function commerce_template_model( $model, $area ) {
        if ( ! $this->is_authorized() || ! $this->matches_template_area( $area ) || empty( $_GET['itk_template_model'] ) ) {
            return $model;
        }

        return sanitize_key( wp_unslash( $_GET['itk_template_model'] ) );
    }

    /**
     * Preview bounded commerce visual options without writing the profile.
     * Final validation remains in the Theme's public option contract.
     *
     * @param array<string,mixed> $options Current options.
     * @param string              $area    Commerce area.
     * @return array<string,mixed>
     */
    public function commerce_template_options( $options, $area ) {
        $options = is_array( $options ) ? $options : array();

        if ( ! $this->is_authorized() || ! $this->matches_template_area( $area ) ) {
            return $options;
        }

        if ( 'shop' === $area ) {
            if ( isset( $_GET['itk_shop_columns'] ) ) {
                $options['columns'] = absint( wp_unslash( $_GET['itk_shop_columns'] ) );
            }
            if ( isset( $_GET['itk_shop_sidebar_position'] ) ) {
                $options['sidebar_position'] = sanitize_key( wp_unslash( $_GET['itk_shop_sidebar_position'] ) );
            }
            if ( isset( $_GET['itk_shop_density'] ) ) {
                $options['density'] = sanitize_key( wp_unslash( $_GET['itk_shop_density'] ) );
            }
        } elseif ( 'product' === $area ) {
            if ( isset( $_GET['itk_product_gallery_width'] ) ) {
                $options['gallery_width'] = absint( wp_unslash( $_GET['itk_product_gallery_width'] ) );
            }
            if ( isset( $_GET['itk_product_sticky_summary'] ) ) {
                $options['sticky_summary'] = '1' === sanitize_text_field( wp_unslash( $_GET['itk_product_sticky_summary'] ) );
            }
            if ( isset( $_GET['itk_product_tabs_layout'] ) ) {
                $options['tabs_layout'] = sanitize_key( wp_unslash( $_GET['itk_product_tabs_layout'] ) );
            }
        } elseif ( 'cart' === $area ) {
            if ( isset( $_GET['itk_cart_sticky_totals'] ) ) {
                $options['sticky_totals'] = '1' === sanitize_text_field( wp_unslash( $_GET['itk_cart_sticky_totals'] ) );
            }
            if ( isset( $_GET['itk_cart_density'] ) ) {
                $options['density'] = sanitize_key( wp_unslash( $_GET['itk_cart_density'] ) );
            }
        } elseif ( 'checkout' === $area ) {
            if ( isset( $_GET['itk_checkout_sticky_summary'] ) ) {
                $options['sticky_summary'] = '1' === sanitize_text_field( wp_unslash( $_GET['itk_checkout_sticky_summary'] ) );
            }
            if ( isset( $_GET['itk_checkout_content_width'] ) ) {
                $options['content_width'] = sanitize_key( wp_unslash( $_GET['itk_checkout_content_width'] ) );
            }
            if ( isset( $_GET['itk_checkout_field_density'] ) ) {
                $options['field_density'] = sanitize_key( wp_unslash( $_GET['itk_checkout_field_density'] ) );
            }
        }

        return $options;
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
     * @param string $area Requested commerce area.
     * @return bool
     */
    private function matches_template_area( $area ) {
        if ( empty( $_GET['itk_template_area'] ) ) {
            return false;
        }

        return sanitize_key( wp_unslash( $_GET['itk_template_area'] ) ) === sanitize_key( $area );
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
