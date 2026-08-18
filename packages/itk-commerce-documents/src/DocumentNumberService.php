<?php
/**
 * Independent, persisted document number series.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class DocumentNumberService {
    const SERIES_OPTION = 'itk_commerce_document_number_series';
    const LOCK_TTL      = 30;

    /**
     * Return a stable document number for an order/document type. Once assigned,
     * the number is stored through WC_Order CRUD and never regenerated.
     *
     * @param \WC_Order $order Order.
     * @param string    $type Document type.
     * @return string|\WP_Error
     */
    public function number_for( $order, $type ) {
        if ( ! $order instanceof \WC_Order ) {
            return new \WP_Error( 'itk_document_number_order', __( 'A valid WooCommerce order is required.', 'itk-commerce-documents' ) );
        }

        $type = sanitize_key( $type );
        $series = $this->series_for_type( $type );
        if ( '' === $series ) {
            return (string) $order->get_order_number();
        }

        $meta_key = '_itk_document_number_' . $series;
        $existing = trim( (string) $order->get_meta( $meta_key, true ) );
        if ( '' !== $existing ) {
            return $existing;
        }

        $lock = $this->acquire_lock( $series );
        if ( is_wp_error( $lock ) ) {
            return $lock;
        }

        try {
            // Re-read after acquiring the series lock to avoid assigning a second
            // number when two requests target the same order concurrently.
            $fresh = wc_get_order( $order->get_id() );
            if ( $fresh instanceof \WC_Order ) {
                $existing = trim( (string) $fresh->get_meta( $meta_key, true ) );
                if ( '' !== $existing ) {
                    return $existing;
                }
                $order = $fresh;
            }

            $settings = get_option( self::SERIES_OPTION, array() );
            $settings = is_array( $settings ) ? $settings : array();
            $year = gmdate( 'Y' );
            $state = isset( $settings[ $series ] ) && is_array( $settings[ $series ] ) ? $settings[ $series ] : array();
            $next = isset( $state['year'] ) && (string) $state['year'] === $year ? max( 1, absint( $state['next'] ?? 1 ) ) : 1;

            $prefixes = array(
                'invoice'    => 'INV',
                'correction' => 'CRN',
                'return'     => 'RET',
            );
            $prefix = isset( $prefixes[ $series ] ) ? $prefixes[ $series ] : strtoupper( substr( $series, 0, 3 ) );

            /**
             * Filter a generated document number before it is persisted.
             * Implementations must preserve uniqueness within their own series.
             *
             * @param string    $number Generated number.
             * @param string    $series Series identifier.
             * @param int       $next Sequence integer.
             * @param string    $year UTC year.
             * @param \WC_Order $order Order.
             */
            $number = sprintf( '%s-%s-%06d', $prefix, $year, $next );
            $number = (string) apply_filters( 'itk_commerce_document_number', $number, $series, $next, $year, $order );
            $number = trim( sanitize_text_field( $number ) );
            if ( '' === $number ) {
                return new \WP_Error( 'itk_document_number_empty', __( 'The document number format returned an empty number.', 'itk-commerce-documents' ) );
            }

            $settings[ $series ] = array(
                'year' => $year,
                'next' => $next + 1,
            );
            update_option( self::SERIES_OPTION, $settings, false );

            $order->update_meta_data( $meta_key, $number );
            $order->save();
            return $number;
        } finally {
            $this->release_lock( $series );
        }
    }

    /** @param string $type Type. @return string */
    private function series_for_type( $type ) {
        if ( 'invoice' === $type ) {
            return 'invoice';
        }
        if ( in_array( $type, array( 'invoice-correction', 'cancellation-note' ), true ) ) {
            return 'correction';
        }
        if ( 'return-form' === $type ) {
            return 'return';
        }
        return '';
    }

    /** @param string $series Series. @return true|\WP_Error */
    private function acquire_lock( $series ) {
        $key = '_itk_document_sequence_lock_' . sanitize_key( $series );
        for ( $attempt = 0; $attempt < 40; $attempt++ ) {
            if ( add_option( $key, time(), '', false ) ) {
                return true;
            }

            $created = (int) get_option( $key, 0 );
            if ( $created > 0 && ( time() - $created ) > self::LOCK_TTL ) {
                delete_option( $key );
                continue;
            }
            usleep( 25000 );
        }

        return new \WP_Error( 'itk_document_number_locked', __( 'The document number series is busy. Please try again.', 'itk-commerce-documents' ) );
    }

    /** @param string $series Series. @return void */
    private function release_lock( $series ) {
        delete_option( '_itk_document_sequence_lock_' . sanitize_key( $series ) );
    }
}
