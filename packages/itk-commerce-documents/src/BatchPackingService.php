<?php
/**
 * Consolidated packing/picking output for bounded order batches.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class BatchPackingService {
    const MAX_ORDERS = 100;

    /**
     * Render a print-ready batch by order plus consolidated product picking rows.
     *
     * @param int[] $order_ids Order IDs.
     * @return string|\WP_Error
     */
    public function render( array $order_ids ) {
        $order_ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $order_ids ) ) ) ), 0, self::MAX_ORDERS );
        if ( empty( $order_ids ) ) {
            return new \WP_Error( 'itk_packing_orders', __( 'Select at least one order.', 'itk-commerce-documents' ) );
        }

        $orders = array();
        $summary = array();
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order instanceof \WC_Order ) {
                continue;
            }
            $orders[] = $order;
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                $product_id = $product ? absint( $product->get_id() ) : 0;
                $sku = $product ? (string) $product->get_sku() : '';
                $key = $product_id ? 'p:' . $product_id : 'n:' . md5( $item->get_name() );
                if ( ! isset( $summary[ $key ] ) ) {
                    $summary[ $key ] = array(
                        'product_id' => $product_id,
                        'name'       => $item->get_name(),
                        'sku'        => $sku,
                        'quantity'   => 0,
                        'location'   => $product ? sanitize_text_field( (string) $product->get_meta( '_itk_warehouse_location', true ) ) : '',
                    );
                }
                $summary[ $key ]['quantity'] += absint( $item->get_quantity() );
            }
        }

        if ( empty( $orders ) ) {
            return new \WP_Error( 'itk_packing_orders_missing', __( 'No valid WooCommerce orders were found.', 'itk-commerce-documents' ) );
        }

        uasort(
            $summary,
            static function ( $left, $right ) {
                return strnatcasecmp( (string) ( $left['sku'] ?: $left['name'] ), (string) ( $right['sku'] ?: $right['name'] ) );
            }
        );

        $pick_rows = '';
        foreach ( $summary as $row ) {
            /**
             * Filter warehouse/bin location shown on the picking list.
             *
             * @param string $location Existing location.
             * @param int    $product_id Product ID.
             * @param array  $row Summary row.
             */
            $location = (string) apply_filters( 'itk_commerce_packing_location', $row['location'], $row['product_id'], $row );
            $pick_rows .= '<tr><td>☐</td><td>' . esc_html( $location ) . '</td><td>' . esc_html( $row['sku'] ) . '</td><td>' . esc_html( $row['name'] ) . '</td><td>' . esc_html( (string) $row['quantity'] ) . '</td></tr>';
        }

        $order_sections = '';
        foreach ( $orders as $order ) {
            $address = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
            $rows = '';
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                $rows .= '<tr><td>☐</td><td>' . esc_html( $product ? $product->get_sku() : '' ) . '</td><td>' . esc_html( $item->get_name() ) . '</td><td>' . esc_html( (string) $item->get_quantity() ) . '</td></tr>';
            }
            $order_sections .= '<section class="order"><h2>' . esc_html__( 'Order', 'itk-commerce-documents' ) . ' #' . esc_html( $order->get_order_number() ) . '</h2><div class="address">' . wp_kses_post( $address ) . '</div><table><thead><tr><th>✓</th><th>' . esc_html__( 'SKU', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'Product', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'Quantity', 'itk-commerce-documents' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></section>';
        }

        $css = 'body{font-family:Arial,sans-serif;color:#222;margin:28px}h1{margin-bottom:4px}.meta{color:#666}.pick{page-break-after:always}table{width:100%;border-collapse:collapse;margin:16px 0 30px}th,td{padding:8px;border-bottom:1px solid #ddd;text-align:left}.order{page-break-inside:avoid;margin-bottom:38px}.address{margin:10px 0}@media print{body{margin:10mm}}';
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . esc_html__( 'Batch packing list', 'itk-commerce-documents' ) . '</title><style>' . $css . '</style></head><body>';
        $html .= '<section class="pick"><h1>' . esc_html__( 'Consolidated picking list', 'itk-commerce-documents' ) . '</h1><p class="meta">' . esc_html( sprintf( __( '%1$d orders · generated %2$s UTC', 'itk-commerce-documents' ), count( $orders ), gmdate( 'Y-m-d H:i' ) ) ) . '</p><table><thead><tr><th>✓</th><th>' . esc_html__( 'Location', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'SKU', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'Product', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'Total quantity', 'itk-commerce-documents' ) . '</th></tr></thead><tbody>' . $pick_rows . '</tbody></table></section>';
        $html .= $order_sections . '</body></html>';

        return (string) apply_filters( 'itk_commerce_batch_packing_html', $html, $orders, $summary );
    }
}
