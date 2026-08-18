<?php
/**
 * Runtime condition matching for controlled snippets.
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

defined( 'ABSPATH' ) || exit;

final class ConditionMatcher {
    /** @param array<string,mixed> $conditions Conditions. @return bool */
    public function matches( array $conditions ) {
        if ( ! $this->matches_language( $conditions['languages'] ?? array() ) ) {
            return false;
        }
        if ( ! $this->matches_roles( $conditions['roles'] ?? array() ) ) {
            return false;
        }
        if ( ! $this->matches_devices( $conditions['devices'] ?? array() ) ) {
            return false;
        }
        if ( ! $this->matches_page_types( $conditions['page_types'] ?? array() ) ) {
            return false;
        }
        if ( ! $this->matches_products( $conditions['product_ids'] ?? array() ) ) {
            return false;
        }
        if ( ! $this->matches_categories( $conditions['categories'] ?? array() ) ) {
            return false;
        }
        return true;
    }

    /** @param mixed $values Values. @return bool */
    private function matches_language( $values ) {
        $values = $this->keys( $values );
        if ( empty( $values ) ) {
            return true;
        }
        $current = sanitize_key( (string) apply_filters( 'itk_commerce_current_language', '' ) );
        if ( '' === $current ) {
            $current = sanitize_key( substr( determine_locale(), 0, 2 ) );
        }
        return in_array( $current, $values, true );
    }

    /** @param mixed $values Values. @return bool */
    private function matches_roles( $values ) {
        $values = $this->keys( $values );
        if ( empty( $values ) ) {
            return true;
        }
        $user = wp_get_current_user();
        $roles = is_array( $user->roles ) ? array_map( 'sanitize_key', $user->roles ) : array();
        return (bool) array_intersect( $values, $roles );
    }

    /** @param mixed $values Values. @return bool */
    private function matches_devices( $values ) {
        $values = $this->keys( $values );
        if ( empty( $values ) || in_array( 'all', $values, true ) ) {
            return true;
        }
        // WordPress exposes a conservative mobile detector server-side. Tablet
        // precision belongs in CSS/JS, therefore `mobile` includes tablets that
        // WordPress classifies as mobile while `desktop` means non-mobile.
        $current = wp_is_mobile() ? 'mobile' : 'desktop';
        return in_array( $current, $values, true );
    }

    /** @param mixed $values Values. @return bool */
    private function matches_page_types( $values ) {
        $values = $this->keys( $values );
        if ( empty( $values ) || in_array( 'all', $values, true ) ) {
            return true;
        }
        $current = array();
        if ( is_front_page() ) { $current[] = 'front'; }
        if ( is_home() ) { $current[] = 'blog'; }
        if ( function_exists( 'is_shop' ) && is_shop() ) { $current[] = 'shop'; }
        if ( function_exists( 'is_product' ) && is_product() ) { $current[] = 'product'; }
        if ( function_exists( 'is_product_category' ) && is_product_category() ) { $current[] = 'product-category'; }
        if ( function_exists( 'is_cart' ) && is_cart() ) { $current[] = 'cart'; }
        if ( function_exists( 'is_checkout' ) && is_checkout() ) { $current[] = 'checkout'; }
        if ( function_exists( 'is_account_page' ) && is_account_page() ) { $current[] = 'account'; }
        if ( is_singular() ) { $current[] = 'singular'; }
        if ( is_archive() ) { $current[] = 'archive'; }
        if ( is_search() ) { $current[] = 'search'; }
        if ( is_404() ) { $current[] = '404'; }
        return (bool) array_intersect( $values, $current );
    }

    /** @param mixed $values Values. @return bool */
    private function matches_products( $values ) {
        $values = is_array( $values ) ? array_values( array_unique( array_filter( array_map( 'absint', $values ) ) ) ) : array();
        if ( empty( $values ) ) {
            return true;
        }
        $product_id = function_exists( 'is_product' ) && is_product() ? absint( get_queried_object_id() ) : 0;
        return $product_id > 0 && in_array( $product_id, $values, true );
    }

    /** @param mixed $values Values. @return bool */
    private function matches_categories( $values ) {
        $values = $this->keys( $values );
        if ( empty( $values ) ) {
            return true;
        }
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            $term = get_queried_object();
            return $term instanceof \WP_Term && in_array( sanitize_key( $term->slug ), $values, true );
        }
        if ( function_exists( 'is_product' ) && is_product() ) {
            $slugs = wp_get_post_terms( get_queried_object_id(), 'product_cat', array( 'fields' => 'slugs' ) );
            $slugs = is_wp_error( $slugs ) ? array() : array_map( 'sanitize_key', $slugs );
            return (bool) array_intersect( $values, $slugs );
        }
        return false;
    }

    /** @param mixed $values Values. @return string[] */
    private function keys( $values ) {
        return is_array( $values ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $values ) ) ) ) : array();
    }
}
