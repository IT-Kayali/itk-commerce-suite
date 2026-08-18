<?php
/**
 * WooCommerce CRUD based return-case state for return documents.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class ReturnCaseService {
    const META_KEY = '_itk_commerce_return_case';

    /** @var DocumentNumberService */
    private $numbers;

    /** @param DocumentNumberService $numbers Number service. */
    public function __construct( DocumentNumberService $numbers ) {
        $this->numbers = $numbers;
    }

    /**
     * Create or replace the editable return request data while retaining a
     * timestamped status history. Product/quantity values are validated against
     * the order itself rather than arbitrary product IDs.
     *
     * @param \WC_Order $order Order.
     * @param array<int,int> $quantities Order item ID => requested quantity.
     * @param string $reason Reason.
     * @param string $condition Condition.
     * @param string $status Status.
     * @return array<string,mixed>|\WP_Error
     */
    public function save( $order, array $quantities, $reason, $condition, $status ) {
        if ( ! $order instanceof \WC_Order ) {
            return new \WP_Error( 'itk_return_order', __( 'A valid WooCommerce order is required.', 'itk-commerce-documents' ) );
        }

        $number = $this->numbers->number_for( $order, 'return-form' );
        if ( is_wp_error( $number ) ) {
            return $number;
        }

        $allowed_statuses = array( 'requested', 'received', 'approved', 'rejected', 'refunded', 'closed' );
        $status = sanitize_key( $status );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            $status = 'requested';
        }

        $items = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $requested = isset( $quantities[ $item_id ] ) ? absint( $quantities[ $item_id ] ) : 0;
            $maximum = absint( $item->get_quantity() );
            if ( $requested < 1 ) {
                continue;
            }
            $requested = min( $maximum, $requested );
            $product = $item->get_product();
            $items[] = array(
                'order_item_id' => absint( $item_id ),
                'product_id'    => $product ? absint( $product->get_id() ) : 0,
                'name'          => sanitize_text_field( $item->get_name() ),
                'quantity'      => $requested,
            );
        }

        if ( empty( $items ) ) {
            return new \WP_Error( 'itk_return_items', __( 'Select at least one valid order item for the return.', 'itk-commerce-documents' ) );
        }

        $existing = $this->get( $order );
        $history = isset( $existing['history'] ) && is_array( $existing['history'] ) ? $existing['history'] : array();
        $previous_status = isset( $existing['status'] ) ? sanitize_key( $existing['status'] ) : '';
        $history[] = array(
            'from'       => $previous_status,
            'to'         => $status,
            'created_at' => gmdate( 'c' ),
            'actor_id'   => function_exists( 'get_current_user_id' ) ? max( 0, (int) get_current_user_id() ) : 0,
        );

        $case = array(
            'number'     => (string) $number,
            'items'      => $items,
            'reason'     => sanitize_textarea_field( $reason ),
            'condition'  => sanitize_textarea_field( $condition ),
            'status'     => $status,
            'updated_at' => gmdate( 'c' ),
            'history'    => array_slice( $history, -100 ),
        );

        $order->update_meta_data( self::META_KEY, $case );
        $order->save();
        do_action( 'itk_commerce_return_case_saved', $order, $case );
        return $case;
    }

    /** @param \WC_Order $order Order. @return array<string,mixed> */
    public function get( $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return array();
        }
        $case = $order->get_meta( self::META_KEY, true );
        return is_array( $case ) ? $case : array();
    }
}
