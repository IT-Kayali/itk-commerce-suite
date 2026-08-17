<?php
/**
 * Versioned, bounded filter-definition schema.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class FilterSchema {
    /**
     * Return customer-neutral default catalog filters.
     *
     * @return array<int,array<string,mixed>>
     */
    public function defaults() {
        return array(
            array(
                'id'         => 'category',
                'type'       => 'taxonomy',
                'label'      => 'Category',
                'taxonomy'   => 'product_cat',
                'query_key'  => 'filter_category',
                'display'    => 'checkbox',
                'multiple'   => true,
                'match'      => 'any',
                'show_count' => true,
                'collapsed'  => false,
                'order'      => 10,
                'enabled'    => true,
            ),
            array(
                'id'         => 'price',
                'type'       => 'price',
                'label'      => 'Price',
                'query_key'  => 'filter_price',
                'display'    => 'range',
                'show_count' => false,
                'collapsed'  => false,
                'order'      => 20,
                'enabled'    => true,
            ),
            array(
                'id'         => 'stock',
                'type'       => 'stock',
                'label'      => 'Availability',
                'query_key'  => 'filter_stock',
                'display'    => 'radio',
                'show_count' => true,
                'collapsed'  => false,
                'order'      => 30,
                'enabled'    => true,
            ),
            array(
                'id'         => 'sale',
                'type'       => 'sale',
                'label'      => 'On sale',
                'query_key'  => 'filter_sale',
                'display'    => 'toggle',
                'show_count' => false,
                'collapsed'  => false,
                'order'      => 40,
                'enabled'    => true,
            ),
            array(
                'id'         => 'rating',
                'type'       => 'rating',
                'label'      => 'Rating',
                'query_key'  => 'filter_rating',
                'display'    => 'radio',
                'show_count' => false,
                'collapsed'  => true,
                'order'      => 50,
                'enabled'    => true,
            ),
        );
    }

    /**
     * Normalize a customer/profile definition list. Unknown types, duplicate IDs,
     * duplicate query keys, duplicate singleton scalar types and unsafe taxonomies
     * are discarded. Multiple taxonomy filters remain supported.
     *
     * @param mixed $definitions Raw definitions.
     * @return array<int,array<string,mixed>>
     */
    public function normalize( $definitions ) {
        if ( ! is_array( $definitions ) ) {
            $definitions = $this->defaults();
        }

        $normalized      = array();
        $ids             = array();
        $query_keys      = array();
        $singleton_types = array();

        foreach ( array_slice( $definitions, 0, 32 ) as $definition ) {
            $item = $this->normalize_definition( $definition );
            if ( null === $item ) {
                continue;
            }

            if ( isset( $ids[ $item['id'] ] ) || isset( $query_keys[ $item['query_key'] ] ) ) {
                continue;
            }

            if ( 'taxonomy' !== $item['type'] && isset( $singleton_types[ $item['type'] ] ) ) {
                continue;
            }

            $ids[ $item['id'] ]               = true;
            $query_keys[ $item['query_key'] ] = true;
            if ( 'taxonomy' !== $item['type'] ) {
                $singleton_types[ $item['type'] ] = true;
            }
            $normalized[] = $item;
        }

        usort(
            $normalized,
            static function ( $left, $right ) {
                return (int) $left['order'] <=> (int) $right['order'];
            }
        );

        return array_values( $normalized );
    }

    /**
     * Return a map keyed by filter ID.
     *
     * @param mixed $definitions Raw definitions.
     * @return array<string,array<string,mixed>>
     */
    public function keyed( $definitions ) {
        $keyed = array();
        foreach ( $this->normalize( $definitions ) as $definition ) {
            $keyed[ $definition['id'] ] = $definition;
        }
        return $keyed;
    }

    /**
     * @param mixed $definition Raw definition.
     * @return array<string,mixed>|null
     */
    private function normalize_definition( $definition ) {
        if ( ! is_array( $definition ) ) {
            return null;
        }

        $id   = isset( $definition['id'] ) ? sanitize_key( $definition['id'] ) : '';
        $type = isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : '';

        if ( '' === $id || ! in_array( $type, array( 'taxonomy', 'price', 'stock', 'sale', 'rating' ), true ) ) {
            return null;
        }

        $query_key = isset( $definition['query_key'] ) ? sanitize_key( $definition['query_key'] ) : '';
        if ( '' === $query_key ) {
            $query_key = 'filter_' . $id;
        }

        $label = isset( $definition['label'] ) ? sanitize_text_field( $definition['label'] ) : ucfirst( str_replace( '-', ' ', $id ) );
        if ( '' === $label ) {
            $label = ucfirst( str_replace( '-', ' ', $id ) );
        }

        $item = array(
            'id'         => $id,
            'type'       => $type,
            'label'      => $label,
            'query_key'  => $query_key,
            'display'    => $this->normalize_display( isset( $definition['display'] ) ? $definition['display'] : '', $type ),
            'show_count' => ! empty( $definition['show_count'] ),
            'collapsed'  => ! empty( $definition['collapsed'] ),
            'order'      => max( 0, min( 999, isset( $definition['order'] ) ? absint( $definition['order'] ) : 100 ) ),
            'enabled'    => ! isset( $definition['enabled'] ) || ! empty( $definition['enabled'] ),
        );

        if ( 'taxonomy' === $type ) {
            $taxonomy = isset( $definition['taxonomy'] ) ? sanitize_key( $definition['taxonomy'] ) : '';
            if ( ! $this->taxonomy_allowed( $taxonomy ) ) {
                return null;
            }

            $item['taxonomy'] = $taxonomy;
            $item['multiple'] = ! isset( $definition['multiple'] ) || ! empty( $definition['multiple'] );
            $item['match']    = isset( $definition['match'] ) && 'all' === sanitize_key( $definition['match'] ) ? 'all' : 'any';
        }

        return $item;
    }

    /**
     * @param mixed  $display Requested display.
     * @param string $type Filter type.
     * @return string
     */
    private function normalize_display( $display, $type ) {
        $display = sanitize_key( $display );
        $allowed = array(
            'taxonomy' => array( 'checkbox', 'select', 'radio', 'chips' ),
            'price'    => array( 'range' ),
            'stock'    => array( 'radio', 'select', 'chips' ),
            'sale'     => array( 'toggle', 'checkbox' ),
            'rating'   => array( 'radio', 'select', 'chips' ),
        );

        $fallback = $allowed[ $type ][0];
        return in_array( $display, $allowed[ $type ], true ) ? $display : $fallback;
    }

    /**
     * Restrict taxonomy filtering to product taxonomies by default. Product
     * attributes use the standard `pa_` prefix. Additional safe product
     * taxonomies can be explicitly allow-listed by integrations.
     *
     * @param string $taxonomy Taxonomy key.
     * @return bool
     */
    private function taxonomy_allowed( $taxonomy ) {
        if ( '' === $taxonomy ) {
            return false;
        }

        $allowed = in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_brand' ), true ) || 0 === strpos( $taxonomy, 'pa_' );

        /**
         * Filter whether a taxonomy may be exposed as a public catalog filter.
         *
         * @param bool   $allowed  Current decision.
         * @param string $taxonomy Taxonomy key.
         */
        return (bool) apply_filters( 'itk_commerce_search_filter_taxonomy_allowed', $allowed, $taxonomy );
    }
}
