<?php
/**
 * Apply normalized Search/Filter state through public WooCommerce query hooks.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class WooQueryAdapter {
    /** @var UrlState */
    private $url_state;

    /** @var array<string,mixed>|null */
    private $state = null;

    /**
     * @param UrlState $url_state URL-state service.
     */
    public function __construct( UrlState $url_state ) {
        $this->url_state = $url_state;
    }

    /** @return void */
    public function register() {
        add_filter( 'woocommerce_product_query_tax_query', array( $this, 'filter_tax_query' ), 20, 2 );
        add_filter( 'woocommerce_product_query_meta_query', array( $this, 'filter_meta_query' ), 20, 2 );
        add_action( 'woocommerce_product_query', array( $this, 'filter_product_query' ), 20, 2 );
    }

    /**
     * Add taxonomy, stock and rating visibility constraints without replacing
     * WooCommerce's existing visibility/category tax query.
     *
     * @param array<int|string,mixed> $tax_query Existing WooCommerce tax query.
     * @param object                  $query Product query object.
     * @return array<int|string,mixed>
     */
    public function filter_tax_query( $tax_query, $query ) {
        unset( $query );
        $tax_query   = is_array( $tax_query ) ? $tax_query : array();
        $state       = $this->current_state();
        $definitions = $this->definitions_by_id();

        foreach ( $state as $id => $value ) {
            if ( empty( $definitions[ $id ] ) ) {
                continue;
            }

            $definition = $definitions[ $id ];
            if ( 'taxonomy' === $definition['type'] && is_array( $value ) && $value ) {
                $tax_query[] = array(
                    'taxonomy' => $definition['taxonomy'],
                    'field'    => 'slug',
                    'terms'    => array_values( $value ),
                    'operator' => isset( $definition['match'] ) && 'all' === $definition['match'] ? 'AND' : 'IN',
                );
            }
        }

        if ( ! empty( $state['stock'] ) && function_exists( 'wc_get_product_visibility_term_ids' ) ) {
            $visibility = wc_get_product_visibility_term_ids();
            if ( ! empty( $visibility['outofstock'] ) ) {
                $tax_query[] = array(
                    'taxonomy'         => 'product_visibility',
                    'field'            => 'term_taxonomy_id',
                    'terms'            => array( absint( $visibility['outofstock'] ) ),
                    'operator'         => 'in-stock' === $state['stock'] ? 'NOT IN' : 'IN',
                    'include_children' => false,
                );
            }
        }

        if ( ! empty( $state['rating'] ) && function_exists( 'wc_get_product_visibility_term_ids' ) ) {
            $visibility = wc_get_product_visibility_term_ids();
            $rating     = absint( $state['rating'] );
            $terms      = array();

            for ( $value = $rating; $value <= 5; $value++ ) {
                $key = 'rated-' . $value;
                if ( ! empty( $visibility[ $key ] ) ) {
                    $terms[] = absint( $visibility[ $key ] );
                }
            }

            if ( $terms ) {
                $tax_query[] = array(
                    'taxonomy'         => 'product_visibility',
                    'field'            => 'term_taxonomy_id',
                    'terms'            => array_values( array_unique( $terms ) ),
                    'operator'         => 'IN',
                    'include_children' => false,
                );
            }
        }

        return $tax_query;
    }

    /**
     * Add a bounded price condition while preserving WooCommerce's existing meta
     * constraints. Lookup-table optimization is intentionally deferred to the
     * dedicated Phase 4 cache/index workstream.
     *
     * @param array<int|string,mixed> $meta_query Existing meta query.
     * @param object                  $query Product query object.
     * @return array<int|string,mixed>
     */
    public function filter_meta_query( $meta_query, $query ) {
        unset( $query );
        $meta_query = is_array( $meta_query ) ? $meta_query : array();
        $state      = $this->current_state();

        if ( empty( $state['price'] ) || ! is_array( $state['price'] ) ) {
            return $meta_query;
        }

        $min = array_key_exists( 'min', $state['price'] ) ? $state['price']['min'] : null;
        $max = array_key_exists( 'max', $state['price'] ) ? $state['price']['max'] : null;

        if ( null !== $min && null !== $max ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => array( (float) $min, (float) $max ),
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL',
            );
        } elseif ( null !== $min ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => (float) $min,
                'compare' => '>=',
                'type'    => 'DECIMAL',
            );
        } elseif ( null !== $max ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => (float) $max,
                'compare' => '<=',
                'type'    => 'DECIMAL',
            );
        }

        return $meta_query;
    }

    /**
     * Apply sale-ID intersection through WooCommerce's cached sale helper.
     *
     * @param object $query Product WP_Query.
     * @param object $wc_query WooCommerce query service.
     * @return void
     */
    public function filter_product_query( $query, $wc_query = null ) {
        unset( $wc_query );
        $state = $this->current_state();

        if ( empty( $state['sale'] ) || ! function_exists( 'wc_get_product_ids_on_sale' ) || ! is_object( $query ) || ! method_exists( $query, 'get' ) || ! method_exists( $query, 'set' ) ) {
            return;
        }

        $sale_ids = array_values( array_unique( array_filter( array_map( 'absint', wc_get_product_ids_on_sale() ) ) ) );
        if ( empty( $sale_ids ) ) {
            $query->set( 'post__in', array( 0 ) );
            return;
        }

        $existing = $query->get( 'post__in' );
        $existing = is_array( $existing ) ? array_values( array_filter( array_map( 'absint', $existing ) ) ) : array();

        if ( $existing ) {
            $intersection = array_values( array_intersect( $existing, $sale_ids ) );
            $query->set( 'post__in', $intersection ? $intersection : array( 0 ) );
            return;
        }

        $query->set( 'post__in', $sale_ids );
    }

    /**
     * Return normalized state for the current request once per module instance.
     *
     * @return array<string,mixed>
     */
    public function current_state() {
        if ( null !== $this->state ) {
            return $this->state;
        }

        $request     = isset( $_GET ) && is_array( $_GET ) ? $_GET : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public catalog filter state is read-only.
        $this->state = $this->url_state->parse( $request );

        return $this->state;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function definitions_by_id() {
        $definitions = array();
        foreach ( $this->url_state->definitions() as $definition ) {
            if ( ! empty( $definition['id'] ) ) {
                $definitions[ $definition['id'] ] = $definition;
            }
        }
        return $definitions;
    }
}
