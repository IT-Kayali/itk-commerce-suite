<?php
/**
 * Versioned cross-request cache for reusable Search & Filter catalog metadata.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class CatalogCache {
    const OPTION_KEY = 'itk_commerce_search_filter_cache_generation';
    const GROUP      = 'itk_commerce_search_filter';

    /** @var bool */
    private $invalidated_this_request = false;

    /** @return void */
    public function register() {
        add_action( 'save_post_product', array( $this, 'invalidate_for_product_save' ), 20, 3 );
        add_action( 'save_post_product_variation', array( $this, 'invalidate_for_product_save' ), 20, 3 );
        add_action( 'before_delete_post', array( $this, 'invalidate_for_deleted_post' ), 20, 2 );
        add_action( 'set_object_terms', array( $this, 'invalidate_for_object_terms' ), 20, 6 );
        add_action( 'created_term', array( $this, 'invalidate_for_term_change' ), 20, 3 );
        add_action( 'edited_term', array( $this, 'invalidate_for_term_change' ), 20, 3 );
        add_action( 'delete_term', array( $this, 'invalidate_for_deleted_term' ), 20, 5 );
        add_action( 'woocommerce_product_set_stock_status', array( $this, 'invalidate' ), 20 );
        add_action( 'woocommerce_variation_set_stock_status', array( $this, 'invalidate' ), 20 );
        add_action( 'woocommerce_attribute_added', array( $this, 'invalidate' ), 20 );
        add_action( 'woocommerce_attribute_updated', array( $this, 'invalidate' ), 20 );
        add_action( 'woocommerce_attribute_deleted', array( $this, 'invalidate' ), 20 );
    }

    /**
     * Return a cached normalized term list for a configured product taxonomy.
     *
     * The payload contains only the fields used by the frontend renderer so it is
     * stable to serialize across requests and does not retain arbitrary WP_Term
     * state from third-party code.
     *
     * @param string $taxonomy Product taxonomy key.
     * @return array<int,object>
     */
    public function terms( $taxonomy ) {
        $taxonomy = sanitize_key( $taxonomy );
        if ( '' === $taxonomy ) {
            return array();
        }

        $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        $key    = 'terms:' . $this->generation() . ':' . $locale . ':' . $taxonomy;
        $cached = $this->get( $key );

        if ( is_array( $cached ) ) {
            return $this->objects_from_payload( $cached );
        }

        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
                'number'     => 100,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return array();
        }

        $payload = array();
        foreach ( $terms as $term ) {
            if ( ! is_object( $term ) || empty( $term->slug ) ) {
                continue;
            }

            $payload[] = array(
                'slug'  => sanitize_title( $term->slug ),
                'name'  => isset( $term->name ) ? sanitize_text_field( $term->name ) : sanitize_title( $term->slug ),
                'count' => isset( $term->count ) ? absint( $term->count ) : 0,
            );
        }

        $this->set( $key, $payload );
        return $this->objects_from_payload( $payload );
    }

    /**
     * Current cache generation. Incrementing it invalidates every generated key
     * without scanning/deleting persistent cache backends.
     *
     * @return int
     */
    public function generation() {
        $generation = absint( get_option( self::OPTION_KEY, 1 ) );
        return max( 1, $generation );
    }

    /**
     * Public invalidation entrypoint for product/term changes and integrations.
     * Multiple relevant mutations during one PHP request result in one generation
     * bump, avoiding repeated option writes during a WooCommerce product save.
     *
     * @return void
     */
    public function invalidate() {
        if ( $this->invalidated_this_request ) {
            return;
        }

        $this->invalidated_this_request = true;
        $next = $this->generation() + 1;
        update_option( self::OPTION_KEY, $next, false );
        wp_cache_set( 'generation', $next, self::GROUP );

        /**
         * Fires after Search & Filter catalog metadata cache invalidation.
         *
         * @param int $generation New generation number.
         */
        do_action( 'itk_commerce_search_filter_cache_invalidated', $next );
    }

    /**
     * @param int     $post_id Product/variation ID.
     * @param object  $post Post object.
     * @param bool    $update Update flag.
     * @return void
     */
    public function invalidate_for_product_save( $post_id, $post, $update ) {
        unset( $update );

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( is_object( $post ) && in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
            $this->invalidate();
        }
    }

    /**
     * @param int         $post_id Post ID.
     * @param object|null $post Post object.
     * @return void
     */
    public function invalidate_for_deleted_post( $post_id, $post = null ) {
        if ( ! $post && function_exists( 'get_post' ) ) {
            $post = get_post( $post_id );
        }

        if ( is_object( $post ) && in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
            $this->invalidate();
        }
    }

    /**
     * @param int    $object_id Object ID.
     * @param array  $terms Terms.
     * @param array  $tt_ids Term taxonomy IDs.
     * @param string $taxonomy Taxonomy.
     * @param bool   $append Append flag.
     * @param array  $old_tt_ids Previous IDs.
     * @return void
     */
    public function invalidate_for_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        unset( $terms, $tt_ids, $append, $old_tt_ids );

        if ( ! $this->product_taxonomy( $taxonomy ) ) {
            return;
        }

        $post_type = function_exists( 'get_post_type' ) ? get_post_type( $object_id ) : '';
        if ( in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
            $this->invalidate();
        }
    }

    /**
     * @param int    $term_id Term ID.
     * @param int    $tt_id Term taxonomy ID.
     * @param string $taxonomy Taxonomy.
     * @return void
     */
    public function invalidate_for_term_change( $term_id, $tt_id, $taxonomy ) {
        unset( $term_id, $tt_id );
        if ( $this->product_taxonomy( $taxonomy ) ) {
            $this->invalidate();
        }
    }

    /**
     * @param int         $term Term ID.
     * @param int         $tt_id Term taxonomy ID.
     * @param string      $taxonomy Taxonomy.
     * @param mixed       $deleted_term Deleted term data.
     * @param array<int>  $object_ids Object IDs.
     * @return void
     */
    public function invalidate_for_deleted_term( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
        unset( $term, $tt_id, $deleted_term, $object_ids );
        if ( $this->product_taxonomy( $taxonomy ) ) {
            $this->invalidate();
        }
    }

    /**
     * @param string $taxonomy Taxonomy key.
     * @return bool
     */
    private function product_taxonomy( $taxonomy ) {
        $taxonomy = sanitize_key( $taxonomy );
        $allowed  = in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_brand', 'product_visibility' ), true ) || 0 === strpos( $taxonomy, 'pa_' );

        /**
         * Filter whether a taxonomy mutation invalidates catalog option caches.
         *
         * @param bool   $allowed Current decision.
         * @param string $taxonomy Taxonomy key.
         */
        return (bool) apply_filters( 'itk_commerce_search_filter_cache_taxonomy', $allowed, $taxonomy );
    }

    /**
     * @param string $key Logical cache key.
     * @return mixed
     */
    private function get( $key ) {
        $hash  = md5( $key );
        $found = false;
        $value = wp_cache_get( $hash, self::GROUP, false, $found );

        if ( $found ) {
            return $value;
        }

        $value = get_transient( 'itk_sf_' . $hash );
        if ( false !== $value ) {
            wp_cache_set( $hash, $value, self::GROUP, $this->ttl() );
        }

        return $value;
    }

    /**
     * @param string $key Logical cache key.
     * @param mixed  $value Cache payload.
     * @return void
     */
    private function set( $key, $value ) {
        $hash = md5( $key );
        $ttl  = $this->ttl();

        wp_cache_set( $hash, $value, self::GROUP, $ttl );
        set_transient( 'itk_sf_' . $hash, $value, $ttl );
    }

    /** @return int */
    private function ttl() {
        /**
         * Filter Search & Filter catalog metadata cache lifetime in seconds.
         *
         * @param int $ttl Default 10 minutes.
         */
        $ttl = (int) apply_filters( 'itk_commerce_search_filter_cache_ttl', 10 * MINUTE_IN_SECONDS );
        return max( MINUTE_IN_SECONDS, min( HOUR_IN_SECONDS, $ttl ) );
    }

    /**
     * @param array<int,array<string,mixed>> $payload Cached payload.
     * @return array<int,object>
     */
    private function objects_from_payload( array $payload ) {
        $terms = array();
        foreach ( $payload as $item ) {
            if ( ! is_array( $item ) || empty( $item['slug'] ) ) {
                continue;
            }

            $term        = new \stdClass();
            $term->slug  = (string) $item['slug'];
            $term->name  = isset( $item['name'] ) ? (string) $item['name'] : (string) $item['slug'];
            $term->count = isset( $item['count'] ) ? absint( $item['count'] ) : 0;
            $terms[]     = $term;
        }

        return $terms;
    }
}
