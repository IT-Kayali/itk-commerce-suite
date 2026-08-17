<?php
/**
 * Resolve profile-driven Theme layouts without patching Theme internals.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class LayoutResolver {
    /** @var array<string,mixed>|null|false */
    private $profile = false;

    /**
     * Resolve a Theme header/footer model using the active customer profile.
     *
     * Priority for commerce rules follows the product plan:
     * single product > product category > product type > contextual rule > default.
     *
     * @param string $default_model Theme-provided default model.
     * @param string $area          Layout area.
     * @return string
     */
    public function resolve_theme_model( $default_model, $area ) {
        $profile = $this->active_profile();
        $area    = sanitize_key( $area );

        if ( ! is_array( $profile ) || empty( $profile['layouts'][ $area ] ) ) {
            return sanitize_key( $default_model );
        }

        $assignment = $profile['layouts'][ $area ];

        if ( is_string( $assignment ) ) {
            return sanitize_key( $assignment );
        }

        if ( ! is_array( $assignment ) ) {
            return sanitize_key( $default_model );
        }

        $selected = isset( $assignment['model'] ) ? sanitize_key( $assignment['model'] ) : sanitize_key( $default_model );
        if ( ! empty( $assignment['default'] ) ) {
            $selected = sanitize_key( $assignment['default'] );
        }

        $rule_model = $this->resolve_commerce_rule( $assignment );
        if ( $rule_model ) {
            $selected = $rule_model;
        } else {
            $context = $this->context();
            if ( isset( $assignment['contexts'][ $context ] ) ) {
                $selected = sanitize_key( $assignment['contexts'][ $context ] );
            }
        }

        /**
         * Filter a model after the profile/layout priority resolver.
         *
         * @param string                   $selected   Selected model.
         * @param string                   $area       Layout area.
         * @param array<string,mixed>      $assignment Profile assignment.
         * @param array<string,mixed>|null $profile    Active profile.
         */
        return sanitize_key(
            apply_filters(
                'itk_commerce_layout_resolved_model',
                $selected,
                $area,
                $assignment,
                $profile
            )
        );
    }

    /**
     * Apply profile control to the mobile bottom-navigation visibility.
     *
     * @param bool $enabled Theme default.
     * @return bool
     */
    public function mobile_bottom_enabled( $enabled ) {
        $profile = $this->active_profile();

        if ( ! is_array( $profile ) || ! isset( $profile['layouts']['mobile_bottom']['enabled'] ) ) {
            return (bool) $enabled;
        }

        return (bool) $profile['layouts']['mobile_bottom']['enabled'];
    }

    /**
     * Apply up to six profile-defined fallback bottom-navigation entries.
     * A dedicated WordPress `mobile-bottom` menu still wins in the Theme.
     *
     * @param array<int,array<string,mixed>> $default_items Theme defaults.
     * @return array<int,array<string,mixed>>
     */
    public function mobile_bottom_items( $default_items ) {
        $profile = $this->active_profile();

        if ( ! is_array( $profile ) || empty( $profile['layouts']['mobile_bottom']['items'] ) || ! is_array( $profile['layouts']['mobile_bottom']['items'] ) ) {
            return $default_items;
        }

        $items = array();

        foreach ( array_slice( $profile['layouts']['mobile_bottom']['items'], 0, 6 ) as $item ) {
            if ( ! is_array( $item ) || empty( $item['label'] ) ) {
                continue;
            }

            $url = '';
            if ( ! empty( $item['target'] ) ) {
                $url = $this->target_url( sanitize_key( $item['target'] ) );
            } elseif ( ! empty( $item['url'] ) ) {
                $url = esc_url_raw( $item['url'] );
            }

            if ( ! $url ) {
                continue;
            }

            $items[] = array(
                'label' => sanitize_text_field( $item['label'] ),
                'url'   => $url,
                'icon'  => ! empty( $item['icon'] ) ? sanitize_key( $item['icon'] ) : 'home',
                'badge' => ! empty( $item['badge'] ),
            );
        }

        return $items ? $items : $default_items;
    }

    /**
     * Add diagnostic, style-safe body classes for active models.
     *
     * @param string[] $classes Existing body classes.
     * @return string[]
     */
    public function body_classes( $classes ) {
        $classes   = is_array( $classes ) ? $classes : array();
        $header    = $this->resolve_theme_model( 'classic', 'header' );
        $footer    = $this->resolve_theme_model( 'classic', 'footer' );
        $classes[] = 'itk-header-model-' . sanitize_html_class( $header );
        $classes[] = 'itk-footer-model-' . sanitize_html_class( $footer );

        return array_values( array_unique( $classes ) );
    }

    /**
     * Resolve high-priority WooCommerce assignment rules.
     *
     * Supported profile shape:
     * rules.single_product.{product_id}
     * rules.product_category.{category_slug}
     * rules.product_type.{product_type}
     *
     * @param array<string,mixed> $assignment Area assignment.
     * @return string
     */
    private function resolve_commerce_rule( array $assignment ) {
        if ( empty( $assignment['rules'] ) || ! is_array( $assignment['rules'] ) ) {
            return '';
        }

        $rules = $assignment['rules'];

        if ( function_exists( 'is_product' ) && is_product() ) {
            $product_id = (int) get_queried_object_id();

            if ( $product_id && isset( $rules['single_product'][ $product_id ] ) ) {
                return sanitize_key( $rules['single_product'][ $product_id ] );
            }

            if ( $product_id && function_exists( 'wp_get_post_terms' ) && ! empty( $rules['product_category'] ) && is_array( $rules['product_category'] ) ) {
                $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
                if ( ! is_wp_error( $terms ) ) {
                    foreach ( (array) $terms as $slug ) {
                        $slug = sanitize_key( $slug );
                        if ( isset( $rules['product_category'][ $slug ] ) ) {
                            return sanitize_key( $rules['product_category'][ $slug ] );
                        }
                    }
                }
            }

            if ( $product_id && function_exists( 'wc_get_product' ) && ! empty( $rules['product_type'] ) && is_array( $rules['product_type'] ) ) {
                $product = wc_get_product( $product_id );
                if ( $product && method_exists( $product, 'get_type' ) ) {
                    $type = sanitize_key( $product->get_type() );
                    if ( isset( $rules['product_type'][ $type ] ) ) {
                        return sanitize_key( $rules['product_type'][ $type ] );
                    }
                }
            }
        }

        if ( function_exists( 'is_product_category' ) && is_product_category() && ! empty( $rules['product_category'] ) && is_array( $rules['product_category'] ) ) {
            $object = get_queried_object();
            $slug   = is_object( $object ) && isset( $object->slug ) ? sanitize_key( $object->slug ) : '';

            if ( $slug && isset( $rules['product_category'][ $slug ] ) ) {
                return sanitize_key( $rules['product_category'][ $slug ] );
            }
        }

        return '';
    }

    /**
     * Return the current generic page/commerce context.
     *
     * @return string
     */
    private function context() {
        if ( function_exists( 'is_product' ) && is_product() ) {
            return 'product';
        }
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            return 'product_category';
        }
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            return 'shop';
        }
        if ( function_exists( 'is_cart' ) && is_cart() ) {
            return 'cart';
        }
        if ( function_exists( 'is_checkout' ) && is_checkout() ) {
            return 'checkout';
        }
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            return 'account';
        }
        if ( is_front_page() ) {
            return 'front_page';
        }
        if ( is_page() ) {
            return 'page';
        }
        if ( is_archive() ) {
            return 'archive';
        }

        return 'global';
    }

    /**
     * Resolve the active profile lazily through public Core services.
     *
     * @return array<string,mixed>|null
     */
    private function active_profile() {
        if ( false !== $this->profile ) {
            return is_array( $this->profile ) ? $this->profile : null;
        }

        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $this->profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        return is_array( $this->profile ) ? $this->profile : null;
    }

    /**
     * Resolve standard portable navigation targets.
     *
     * @param string $target Target identifier.
     * @return string
     */
    private function target_url( $target ) {
        if ( 'home' === $target ) {
            return home_url( '/' );
        }

        $wc_targets = array( 'shop', 'cart', 'checkout', 'myaccount' );
        if ( in_array( $target, $wc_targets, true ) && function_exists( 'wc_get_page_permalink' ) ) {
            $url = wc_get_page_permalink( $target );
            return $url ? $url : home_url( '/' );
        }

        return '';
    }
}
