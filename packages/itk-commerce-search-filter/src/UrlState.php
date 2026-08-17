<?php
/**
 * Bounded shareable catalog filter URL state.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class UrlState {
    /** @var array<int,array<string,mixed>> */
    private $definitions;

    /**
     * @param array<int,array<string,mixed>> $definitions Normalized filter definitions.
     */
    public function __construct( array $definitions ) {
        $this->definitions = $definitions;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function definitions() {
        return $this->definitions;
    }

    /**
     * Parse only query keys declared by the normalized filter schema.
     *
     * @param array<string,mixed> $request Request/query values.
     * @return array<string,mixed>
     */
    public function parse( array $request ) {
        $state = array();

        foreach ( $this->definitions as $definition ) {
            if ( empty( $definition['enabled'] ) || empty( $definition['query_key'] ) ) {
                continue;
            }

            $key = $definition['query_key'];
            if ( ! array_key_exists( $key, $request ) ) {
                continue;
            }

            $value = $this->unslash( $request[ $key ] );
            $parsed = $this->parse_value( $definition, $value );

            if ( null !== $parsed && array() !== $parsed && '' !== $parsed ) {
                $state[ $definition['id'] ] = $parsed;
            }
        }

        /**
         * Filter normalized public URL state after allow-list parsing.
         *
         * @param array<string,mixed>            $state       Parsed state.
         * @param array<int,array<string,mixed>> $definitions Filter definitions.
         */
        $filtered = apply_filters( 'itk_commerce_search_filter_url_state', $state, $this->definitions );

        return is_array( $filtered ) ? $filtered : $state;
    }

    /**
     * Serialize normalized state back to stable query arguments.
     *
     * @param array<string,mixed> $state Normalized state.
     * @return array<string,string>
     */
    public function serialize( array $state ) {
        $args = array();

        foreach ( $this->definitions as $definition ) {
            $id = $definition['id'];
            if ( ! array_key_exists( $id, $state ) ) {
                continue;
            }

            $value = $this->serialize_value( $definition, $state[ $id ] );
            if ( null !== $value && '' !== $value ) {
                $args[ $definition['query_key'] ] = $value;
            }
        }

        ksort( $args );
        return $args;
    }

    /**
     * Return the number of active filter groups, not the number of selected terms.
     *
     * @param array<string,mixed> $state Normalized state.
     * @return int
     */
    public function active_count( array $state ) {
        return count( array_filter( $state, static function ( $value ) {
            return null !== $value && '' !== $value && array() !== $value;
        } ) );
    }

    /**
     * @param array<string,mixed> $definition Filter definition.
     * @param mixed               $value Raw value.
     * @return mixed
     */
    private function parse_value( array $definition, $value ) {
        switch ( $definition['type'] ) {
            case 'taxonomy':
                return $this->parse_taxonomy( $value, ! empty( $definition['multiple'] ) );
            case 'price':
                return $this->parse_price( $value );
            case 'stock':
                $stock = sanitize_key( is_scalar( $value ) ? (string) $value : '' );
                return in_array( $stock, array( 'in-stock', 'out-of-stock' ), true ) ? $stock : null;
            case 'sale':
                $sale = sanitize_key( is_scalar( $value ) ? (string) $value : '' );
                return in_array( $sale, array( '1', 'true', 'yes', 'on' ), true ) ? true : null;
            case 'rating':
                $rating = is_scalar( $value ) ? absint( $value ) : 0;
                return $rating >= 1 && $rating <= 5 ? $rating : null;
        }

        return null;
    }

    /**
     * @param mixed $value Raw taxonomy selection.
     * @param bool  $multiple Whether multiple values are supported.
     * @return string[]
     */
    private function parse_taxonomy( $value, $multiple ) {
        $values = is_array( $value ) ? $value : explode( ',', (string) $value );
        $values = array_slice( $values, 0, $multiple ? 50 : 1 );
        $values = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static function ( $term ) {
                            return sanitize_title( is_scalar( $term ) ? (string) $term : '' );
                        },
                        $values
                    )
                )
            )
        );

        return $values;
    }

    /**
     * Parse `min-max`, allowing either side to be empty.
     *
     * @param mixed $value Raw price range.
     * @return array<string,float|null>|null
     */
    private function parse_price( $value ) {
        if ( ! is_scalar( $value ) ) {
            return null;
        }

        $parts = explode( '-', (string) $value, 2 );
        if ( 2 !== count( $parts ) ) {
            return null;
        }

        $min = $this->price_number( $parts[0] );
        $max = $this->price_number( $parts[1] );

        if ( null === $min && null === $max ) {
            return null;
        }

        if ( null !== $min && null !== $max && $min > $max ) {
            $swap = $min;
            $min  = $max;
            $max  = $swap;
        }

        return array( 'min' => $min, 'max' => $max );
    }

    /**
     * @param mixed $value Raw numeric value.
     * @return float|null
     */
    private function price_number( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value || ! is_numeric( $value ) ) {
            return null;
        }

        $number = (float) $value;
        if ( $number < 0 ) {
            $number = 0.0;
        }

        return min( 1000000000000.0, $number );
    }

    /**
     * @param array<string,mixed> $definition Filter definition.
     * @param mixed               $value Normalized value.
     * @return string|null
     */
    private function serialize_value( array $definition, $value ) {
        switch ( $definition['type'] ) {
            case 'taxonomy':
                if ( ! is_array( $value ) || empty( $value ) ) {
                    return null;
                }
                return implode( ',', array_map( 'sanitize_title', array_slice( $value, 0, ! empty( $definition['multiple'] ) ? 50 : 1 ) ) );
            case 'price':
                if ( ! is_array( $value ) ) {
                    return null;
                }
                $min = isset( $value['min'] ) && null !== $value['min'] ? $this->format_number( $value['min'] ) : '';
                $max = isset( $value['max'] ) && null !== $value['max'] ? $this->format_number( $value['max'] ) : '';
                return '' === $min && '' === $max ? null : $min . '-' . $max;
            case 'stock':
                return in_array( $value, array( 'in-stock', 'out-of-stock' ), true ) ? $value : null;
            case 'sale':
                return ! empty( $value ) ? '1' : null;
            case 'rating':
                $rating = absint( $value );
                return $rating >= 1 && $rating <= 5 ? (string) $rating : null;
        }

        return null;
    }

    /**
     * @param mixed $value Number.
     * @return string
     */
    private function format_number( $value ) {
        $number = (float) $value;
        return rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' );
    }

    /**
     * @param mixed $value Request value.
     * @return mixed
     */
    private function unslash( $value ) {
        if ( function_exists( 'wp_unslash' ) ) {
            return wp_unslash( $value );
        }

        if ( is_array( $value ) ) {
            return array_map( array( $this, 'unslash' ), $value );
        }

        return is_string( $value ) ? stripslashes( $value ) : $value;
    }
}
