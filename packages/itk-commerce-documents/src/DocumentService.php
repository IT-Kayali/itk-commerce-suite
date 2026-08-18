<?php
/**
 * WooCommerce order document renderer.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class DocumentService {
    /** @var DocumentNumberService */
    private $numbers;

    /** @var ReturnCaseService */
    private $returns;

    /**
     * @param DocumentNumberService $numbers Number service.
     * @param ReturnCaseService     $returns Return-case service.
     */
    public function __construct( DocumentNumberService $numbers, ReturnCaseService $returns ) {
        $this->numbers = $numbers;
        $this->returns = $returns;
    }

    /** @return string[] */
    public function types() {
        return array( 'invoice', 'invoice-correction', 'delivery-note', 'return-form', 'packing-list' );
    }

    /**
     * Render a complete standalone HTML document.
     *
     * @param int                 $order_id Order ID.
     * @param string              $type Document type.
     * @param array<string,mixed> $options Rendering options.
     * @return string|\WP_Error
     */
    public function render_html( $order_id, $type, array $options = array() ) {
        $type = sanitize_key( $type );
        if ( ! in_array( $type, $this->types(), true ) ) {
            return new \WP_Error( 'itk_document_type', __( 'Unsupported document type.', 'itk-commerce-documents' ) );
        }

        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order instanceof \WC_Order ) {
            return new \WP_Error( 'itk_document_order', __( 'Order was not found.', 'itk-commerce-documents' ) );
        }

        $number = $this->numbers->number_for( $order, $type );
        if ( is_wp_error( $number ) ) {
            return $number;
        }

        $language = sanitize_key( (string) $order->get_meta( '_itk_commerce_language', true ) );
        if ( '' === $language ) {
            $language = 'en';
        }
        $stored_direction = sanitize_key( (string) $order->get_meta( '_itk_commerce_direction', true ) );
        $direction = in_array( $stored_direction, array( 'ltr', 'rtl' ), true ) ? $stored_direction : ( in_array( $language, array( 'ar', 'fa', 'he', 'ur' ), true ) ? 'rtl' : 'ltr' );

        $profile = $this->profile();
        $document_config = $this->document_config( $profile );
        $template = $this->template_config( $document_config, $type, $language );
        $labels = $this->labels();
        $title = ! empty( $template['title'] ) ? sanitize_text_field( $template['title'] ) : $labels[ $type ];
        $accent = ! empty( $template['accent'] ) && sanitize_hex_color( $template['accent'] ) ? sanitize_hex_color( $template['accent'] ) : '#222222';
        $include_prices = 'invoice' === $type || 'invoice-correction' === $type || ( 'delivery-note' === $type && ! empty( $options['include_prices'] ) );

        $branding = isset( $profile['branding'] ) && is_array( $profile['branding'] ) ? $profile['branding'] : array();
        $contacts = isset( $profile['contacts'] ) && is_array( $profile['contacts'] ) ? $profile['contacts'] : array();
        $company_name = ! empty( $branding['company_name'] ) ? sanitize_text_field( $branding['company_name'] ) : ( ! empty( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : get_bloginfo( 'name' ) );
        $logo = ! empty( $branding['logo_url'] ) ? esc_url( $branding['logo_url'] ) : '';
        $company_address = ! empty( $contacts['address'] ) ? sanitize_textarea_field( $contacts['address'] ) : '';
        $company_email = ! empty( $contacts['email'] ) ? sanitize_email( $contacts['email'] ) : '';
        $company_phone = ! empty( $contacts['phone'] ) ? sanitize_text_field( $contacts['phone'] ) : '';
        $tax_id = ! empty( $contacts['tax_id'] ) ? sanitize_text_field( $contacts['tax_id'] ) : '';
        $vat_id = ! empty( $contacts['vat_id'] ) ? sanitize_text_field( $contacts['vat_id'] ) : '';
        $footer = ! empty( $template['footer'] ) ? sanitize_textarea_field( $template['footer'] ) : ( ! empty( $document_config['footer'] ) ? sanitize_textarea_field( $document_config['footer'] ) : '' );

        $customer_address = $this->customer_address( $order, $type, $options );
        $rows = $this->item_rows( $order, $type, $include_prices );
        $totals = $include_prices ? $this->totals_markup( $order, 'invoice-correction' === $type ) : '';
        $return = 'return-form' === $type ? $this->return_markup( $order ) : '';
        $correction = 'invoice-correction' === $type ? $this->correction_markup( $order ) : '';
        $code = $this->machine_code_markup( $order, $type, $number );
        $date = $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '';

        $logo_markup = $logo ? '<img class="logo" src="' . esc_url( $logo ) . '" alt="' . esc_attr( $company_name ) . '">' : '';
        $company_markup = '<strong>' . esc_html( $company_name ) . '</strong>';
        if ( $company_address ) {
            $company_markup .= '<br>' . nl2br( esc_html( $company_address ) );
        }
        if ( $company_email ) {
            $company_markup .= '<br>' . esc_html( $company_email );
        }
        if ( $company_phone ) {
            $company_markup .= '<br>' . esc_html( $company_phone );
        }
        if ( $tax_id ) {
            $company_markup .= '<br>' . esc_html__( 'Tax ID', 'itk-commerce-documents' ) . ': ' . esc_html( $tax_id );
        }
        if ( $vat_id ) {
            $company_markup .= '<br>' . esc_html__( 'VAT ID', 'itk-commerce-documents' ) . ': ' . esc_html( $vat_id );
        }

        $price_heading = $include_prices ? '<th>' . esc_html__( 'Amount', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'Tax', 'itk-commerce-documents' ) . '</th>' : '';
        $check_heading = 'packing-list' === $type ? '<th class="check">✓</th>' : '';

        $css = 'html{direction:' . esc_attr( $direction ) . '}body{font-family:Arial,sans-serif;color:#222;margin:32px;line-height:1.45}.head{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid ' . esc_attr( $accent ) . ';padding-bottom:16px}.brand{display:flex;gap:16px;align-items:flex-start}.logo{max-width:160px;max-height:70px;object-fit:contain}.doc-meta{text-align:end}.meta{margin:22px 0;display:grid;grid-template-columns:1fr 1fr;gap:24px}.panel{border:1px solid #ddd;padding:14px;border-radius:6px}table{width:100%;border-collapse:collapse;margin-top:24px}th,td{padding:9px;border-bottom:1px solid #ddd;text-align:start;vertical-align:top}th{background:#f7f7f7}.money{white-space:nowrap}.totals{margin-inline-start:auto;margin-top:20px;max-width:420px}.totals table{margin-top:0}.totals th{background:transparent}.return-fields,.correction{margin-top:28px;padding:16px;border:1px solid #ddd}.history{font-size:12px}.machine-code{font-family:monospace;letter-spacing:.06em;margin-top:16px}.footer{margin-top:48px;padding-top:12px;border-top:1px solid #ddd;font-size:12px;color:#555}.check{width:40px}@media(max-width:640px){body{margin:16px}.head,.meta{display:block}.brand,.doc-meta,.panel{margin-bottom:16px}table{font-size:12px}}@media print{body{margin:12mm}.no-print{display:none}}';

        /**
         * Filter printable document CSS after profile/template resolution.
         *
         * @param string    $css CSS.
         * @param \WC_Order $order Order.
         * @param string    $type Type.
         */
        $css = (string) apply_filters( 'itk_commerce_document_css', $css, $order, $type );

        $html = '<!doctype html><html lang="' . esc_attr( $language ) . '" dir="' . esc_attr( $direction ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $title . ' ' . $number ) . '</title><style>' . $css . '</style></head><body>';
        $html .= '<header class="head"><div class="brand">' . $logo_markup . '<div>' . $company_markup . '</div></div><div class="doc-meta"><h1>' . esc_html( $title ) . '</h1><strong>' . esc_html( $number ) . '</strong><br>' . esc_html__( 'Order', 'itk-commerce-documents' ) . ' #' . esc_html( $order->get_order_number() ) . ( $date ? '<br>' . esc_html( $date ) : '' ) . '</div></header>';
        $html .= '<section class="meta"><div class="panel"><strong>' . esc_html__( 'Customer / address', 'itk-commerce-documents' ) . '</strong><br>' . wp_kses_post( $customer_address ) . '</div><div class="panel"><strong>' . esc_html__( 'Order status', 'itk-commerce-documents' ) . '</strong><br>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '<br><strong>' . esc_html__( 'Language', 'itk-commerce-documents' ) . ':</strong> ' . esc_html( $language ) . '</div></section>';
        $html .= '<table><thead><tr>' . $check_heading . '<th>' . esc_html__( 'Product', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'SKU', 'itk-commerce-documents' ) . '</th><th>' . esc_html__( 'Quantity', 'itk-commerce-documents' ) . '</th>' . $price_heading . '</tr></thead><tbody>' . $rows . '</tbody></table>';
        $html .= $totals . $correction . $return . $code;
        $html .= '<footer class="footer">' . ( $footer ? nl2br( esc_html( $footer ) ) : esc_html__( 'Generated by IT-Kayali Commerce Documents. Document content and tax/legal wording must be approved by the shop operator.', 'itk-commerce-documents' ) ) . '</footer></body></html>';

        /**
         * Filter final document HTML. This enables customer/template packages to
         * replace presentation without modifying the module renderer.
         *
         * @param string              $html Document HTML.
         * @param \WC_Order           $order Order.
         * @param string              $type Document type.
         * @param array<string,mixed> $options Rendering options.
         */
        return (string) apply_filters( 'itk_commerce_document_html', $html, $order, $type, $options );
    }

    /**
     * Return PDF bytes when a renderer is available. Dompdf is supported when
     * installed by the host; a custom renderer can be supplied by filter.
     *
     * @param string $html HTML.
     * @return string|\WP_Error
     */
    public function render_pdf( $html ) {
        $custom = apply_filters( 'itk_commerce_documents_pdf_renderer', null, $html );
        if ( is_callable( $custom ) ) {
            $bytes = call_user_func( $custom, $html );
            return is_string( $bytes ) && '' !== $bytes ? $bytes : new \WP_Error( 'itk_pdf_renderer', __( 'Custom PDF renderer returned invalid data.', 'itk-commerce-documents' ) );
        }

        if ( class_exists( '\Dompdf\Dompdf' ) ) {
            $dompdf = new \Dompdf\Dompdf( array( 'isRemoteEnabled' => false, 'isPhpEnabled' => false ) );
            $dompdf->loadHtml( $html, 'UTF-8' );
            $dompdf->setPaper( 'A4' );
            $dompdf->render();
            return (string) $dompdf->output();
        }

        return new \WP_Error( 'itk_pdf_unavailable', __( 'No PDF renderer is installed. Install Dompdf or provide the itk_commerce_documents_pdf_renderer adapter.', 'itk-commerce-documents' ) );
    }

    /** @param int $order_id Order ID. @param string $type Type. @return string|\WP_Error */
    public function number( $order_id, $type ) {
        $order = wc_get_order( absint( $order_id ) );
        return $order instanceof \WC_Order ? $this->numbers->number_for( $order, sanitize_key( $type ) ) : new \WP_Error( 'itk_document_order', __( 'Order was not found.', 'itk-commerce-documents' ) );
    }

    /** @param \WC_Order $order Order. @param string $type Type. @param array<string,mixed> $options Options. @return string */
    private function customer_address( $order, $type, array $options ) {
        $billing = $order->get_formatted_billing_address();
        $shipping = $order->get_formatted_shipping_address();
        $use_billing = ! empty( $options['billing_address'] );
        if ( in_array( $type, array( 'delivery-note', 'packing-list' ), true ) && ! $use_billing && $shipping ) {
            return $shipping;
        }
        return $billing ?: $shipping;
    }

    /** @param \WC_Order $order Order. @param string $type Type. @param bool $include_prices Include prices. @return string */
    private function item_rows( $order, $type, $include_prices ) {
        $rows = '';
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            $sku = $product ? $product->get_sku() : '';
            $check = 'packing-list' === $type ? '<td class="check">☐</td>' : '';
            $rows .= '<tr>' . $check . '<td>' . esc_html( $item->get_name() ) . $this->item_meta( $item ) . '</td><td>' . esc_html( $sku ) . '</td><td>' . esc_html( (string) $item->get_quantity() ) . '</td>';
            if ( $include_prices ) {
                $amount = (float) $item->get_total();
                $tax = (float) $item->get_total_tax();
                if ( 'invoice-correction' === $type ) {
                    $amount *= -1;
                    $tax *= -1;
                }
                $rows .= '<td class="money">' . wp_kses_post( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ) . '</td><td class="money">' . wp_kses_post( wc_price( $tax, array( 'currency' => $order->get_currency() ) ) ) . '</td>';
            }
            $rows .= '</tr>';
        }
        return $rows;
    }

    /** @param \WC_Order_Item_Product $item Item. @return string */
    private function item_meta( $item ) {
        if ( ! function_exists( 'wc_display_item_meta' ) ) {
            return '';
        }
        return (string) wc_display_item_meta( $item, array( 'echo' => false, 'separator' => ', ', 'label_before' => '<small>', 'label_after' => ':</small> ', 'autop' => false ) );
    }

    /** @param \WC_Order $order Order. @param bool $correction Correction. @return string */
    private function totals_markup( $order, $correction ) {
        $totals = $order->get_order_item_totals();
        if ( ! is_array( $totals ) ) {
            return '';
        }
        $rows = '';
        foreach ( $totals as $key => $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $label = isset( $row['label'] ) ? wp_strip_all_tags( $row['label'] ) : sanitize_text_field( (string) $key );
            $value = isset( $row['value'] ) ? wp_kses_post( $row['value'] ) : '';
            $rows .= '<tr><th>' . esc_html( $label ) . '</th><td>' . ( $correction ? '− ' : '' ) . $value . '</td></tr>';
        }
        return '<div class="totals"><table><tbody>' . $rows . '</tbody></table></div>';
    }

    /** @param \WC_Order $order Order. @return string */
    private function correction_markup( $order ) {
        $refunds = $order->get_refunds();
        if ( empty( $refunds ) ) {
            return '<section class="correction"><strong>' . esc_html__( 'Correction / cancellation', 'itk-commerce-documents' ) . '</strong><p>' . esc_html__( 'No WooCommerce refund is currently recorded for this order. Verify the accounting basis before using this document.', 'itk-commerce-documents' ) . '</p></section>';
        }
        $rows = array();
        foreach ( $refunds as $refund ) {
            if ( ! $refund instanceof \WC_Order_Refund ) {
                continue;
            }
            $rows[] = esc_html( sprintf( '#%s – %s – %s', $refund->get_id(), $refund->get_reason() ?: __( 'Refund', 'itk-commerce-documents' ), wp_strip_all_tags( wc_price( abs( (float) $refund->get_amount() ), array( 'currency' => $order->get_currency() ) ) ) ) );
        }
        return '<section class="correction"><strong>' . esc_html__( 'Recorded refunds / correction basis', 'itk-commerce-documents' ) . '</strong><p>' . implode( '<br>', $rows ) . '</p></section>';
    }

    /** @param \WC_Order $order Order. @return string */
    private function return_markup( $order ) {
        $case = $this->returns->get( $order );
        if ( empty( $case ) ) {
            return '<section class="return-fields"><strong>' . esc_html__( 'Return details', 'itk-commerce-documents' ) . '</strong><p>' . esc_html__( 'Reason', 'itk-commerce-documents' ) . ': ______________________________</p><p>' . esc_html__( 'Condition', 'itk-commerce-documents' ) . ': ____________________________</p><p>' . esc_html__( 'Status', 'itk-commerce-documents' ) . ': ______________________________</p><p>' . esc_html__( 'Customer signature', 'itk-commerce-documents' ) . ': ____________________</p></section>';
        }

        $items = '';
        foreach ( $case['items'] ?? array() as $item ) {
            if ( is_array( $item ) ) {
                $items .= '<li>' . esc_html( (string) ( $item['quantity'] ?? 0 ) ) . ' × ' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</li>';
            }
        }
        $history = '';
        foreach ( $case['history'] ?? array() as $event ) {
            if ( is_array( $event ) ) {
                $history .= '<li>' . esc_html( (string) ( $event['created_at'] ?? '' ) ) . ': ' . esc_html( (string) ( $event['from'] ?? '' ) ) . ' → ' . esc_html( (string) ( $event['to'] ?? '' ) ) . '</li>';
            }
        }
        return '<section class="return-fields"><strong>' . esc_html__( 'Return case', 'itk-commerce-documents' ) . ' ' . esc_html( (string) ( $case['number'] ?? '' ) ) . '</strong><ul>' . $items . '</ul><p><strong>' . esc_html__( 'Reason', 'itk-commerce-documents' ) . ':</strong> ' . esc_html( (string) ( $case['reason'] ?? '' ) ) . '</p><p><strong>' . esc_html__( 'Condition', 'itk-commerce-documents' ) . ':</strong> ' . esc_html( (string) ( $case['condition'] ?? '' ) ) . '</p><p><strong>' . esc_html__( 'Status', 'itk-commerce-documents' ) . ':</strong> ' . esc_html( (string) ( $case['status'] ?? '' ) ) . '</p><div class="history"><strong>' . esc_html__( 'Processing history', 'itk-commerce-documents' ) . '</strong><ul>' . $history . '</ul></div></section>';
    }

    /** @param \WC_Order $order Order. @param string $type Type. @param string $number Number. @return string */
    private function machine_code_markup( $order, $type, $number ) {
        /**
         * Provide an optional locally rendered Barcode/QR implementation.
         * Returning markup allows a shipping/customer module to use its chosen
         * licensed encoder without remote requests from the generic product.
         *
         * @param string    $markup Existing markup.
         * @param string    $number Document number.
         * @param \WC_Order $order Order.
         * @param string    $type Type.
         */
        $markup = apply_filters( 'itk_commerce_document_code_markup', '', $number, $order, $type );
        if ( is_string( $markup ) && '' !== trim( $markup ) ) {
            return '<div class="machine-code">' . wp_kses_post( $markup ) . '</div>';
        }
        return '<div class="machine-code" data-document-code="' . esc_attr( $number ) . '">' . esc_html__( 'Document code', 'itk-commerce-documents' ) . ': ' . esc_html( $number ) . '</div>';
    }

    /** @return array<string,mixed> */
    private function profile() {
        if ( ! class_exists( '\ITK\Commerce\Core\Core' ) ) {
            return array();
        }
        $core = \ITK\Commerce\Core\Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;
        return is_array( $profile ) ? $profile : array();
    }

    /** @param array<string,mixed> $profile Profile. @return array<string,mixed> */
    private function document_config( array $profile ) {
        $config = $profile['modules']['configuration'][ MODULE_ID ] ?? array();
        return is_array( $config ) ? $config : array();
    }

    /** @param array<string,mixed> $config Config. @param string $type Type. @param string $language Language. @return array<string,mixed> */
    private function template_config( array $config, $type, $language ) {
        $templates = isset( $config['templates'] ) && is_array( $config['templates'] ) ? $config['templates'] : array();
        $type_config = isset( $templates[ $type ] ) && is_array( $templates[ $type ] ) ? $templates[ $type ] : array();
        if ( isset( $type_config[ $language ] ) && is_array( $type_config[ $language ] ) ) {
            return $type_config[ $language ];
        }
        return isset( $type_config['default'] ) && is_array( $type_config['default'] ) ? $type_config['default'] : $type_config;
    }

    /** @return array<string,string> */
    private function labels() {
        return array(
            'invoice'            => __( 'Invoice', 'itk-commerce-documents' ),
            'invoice-correction' => __( 'Invoice correction / cancellation', 'itk-commerce-documents' ),
            'delivery-note'      => __( 'Delivery note', 'itk-commerce-documents' ),
            'return-form'        => __( 'Return form', 'itk-commerce-documents' ),
            'packing-list'       => __( 'Packing list', 'itk-commerce-documents' ),
        );
    }
}
