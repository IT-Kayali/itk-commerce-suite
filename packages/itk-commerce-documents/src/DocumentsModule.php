<?php
/**
 * Documents module definition.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

use ITK\Commerce\Core\Contracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class DocumentsModule implements ModuleInterface {
    /** @var DocumentService */
    private $service;

    /** @var DocumentNumberService */
    private $numbers;

    /** @var DocumentHistoryService */
    private $history;

    /** @var ReturnCaseService */
    private $returns;

    /** @var string[] */
    private $temporary_files = array();

    /** @return string */
    public function id() { return MODULE_ID; }

    /** @return string */
    public function version() { return VERSION; }

    /** @return array<string,mixed> */
    public function requirements() {
        return array(
            'core'        => '0.1.0-dev',
            'php'         => '8.1',
            'wordpress'   => '6.6',
            'woocommerce' => null,
            'modules'     => array(),
        );
    }

    /** @return void */
    public function register() {
        $this->numbers = new DocumentNumberService();
        $this->history = new DocumentHistoryService();
        $this->returns = new ReturnCaseService( $this->numbers );
        $this->service = new DocumentService( $this->numbers, $this->returns );

        add_filter( 'itk_commerce_documents_service', array( $this, 'service_filter' ) );
        add_filter( 'itk_commerce_return_case_service', array( $this, 'return_service_filter' ) );
        add_action( 'itk_commerce_admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_post_itk_commerce_generate_document', array( $this, 'download' ) );
        add_action( 'admin_post_itk_commerce_save_return_case', array( $this, 'save_return_case' ) );
        add_action( 'admin_post_itk_commerce_customer_document', array( $this, 'customer_download' ) );
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'customer_document_links' ), 25, 1 );
        add_filter( 'woocommerce_email_attachments', array( $this, 'email_attachments' ), 20, 4 );
        add_action( 'shutdown', array( $this, 'cleanup_temporary_files' ), PHP_INT_MAX );

        do_action( 'itk_commerce_documents_loaded', $this, $this->service, $this->returns, $this->history );
    }

    /** @param mixed $existing Existing service. @return DocumentService */
    public function service_filter( $existing ) {
        unset( $existing );
        return $this->service;
    }

    /** @param mixed $existing Existing service. @return ReturnCaseService */
    public function return_service_filter( $existing ) {
        unset( $existing );
        return $this->returns;
    }

    /** @param string $parent Parent menu slug. @return void */
    public function admin_menu( $parent ) {
        add_submenu_page(
            $parent,
            __( 'Documents', 'itk-commerce-documents' ),
            __( 'Documents', 'itk-commerce-documents' ),
            'itk_manage_documents',
            'itk-commerce-documents',
            array( $this, 'render_admin' )
        );
    }

    /** @return void */
    public function render_admin() {
        $this->require_manage_documents();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Commerce Documents', 'itk-commerce-documents' ); ?></h1>
            <p><?php esc_html_e( 'Generate and re-download order documents. Numbers are assigned once per document series and generation events are recorded on the WooCommerce order.', 'itk-commerce-documents' ); ?></p>

            <h2><?php esc_html_e( 'Generate document', 'itk-commerce-documents' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_generate_document">
                <?php wp_nonce_field( 'itk_commerce_generate_document' ); ?>
                <table class="form-table"><tbody>
                    <tr><th><label for="itk-order-id"><?php esc_html_e( 'Order ID', 'itk-commerce-documents' ); ?></label></th><td><input id="itk-order-id" type="number" min="1" name="order_id" required></td></tr>
                    <tr><th><?php esc_html_e( 'Document', 'itk-commerce-documents' ); ?></th><td><select name="document_type"><option value="invoice"><?php esc_html_e( 'Invoice', 'itk-commerce-documents' ); ?></option><option value="invoice-correction"><?php esc_html_e( 'Invoice correction / cancellation', 'itk-commerce-documents' ); ?></option><option value="delivery-note"><?php esc_html_e( 'Delivery note', 'itk-commerce-documents' ); ?></option><option value="return-form"><?php esc_html_e( 'Return form', 'itk-commerce-documents' ); ?></option><option value="packing-list"><?php esc_html_e( 'Packing list', 'itk-commerce-documents' ); ?></option></select></td></tr>
                    <tr><th><?php esc_html_e( 'Format', 'itk-commerce-documents' ); ?></th><td><select name="format"><option value="html"><?php esc_html_e( 'Print-ready HTML', 'itk-commerce-documents' ); ?></option><option value="pdf">PDF</option></select></td></tr>
                    <tr><th><?php esc_html_e( 'Delivery options', 'itk-commerce-documents' ); ?></th><td><label><input type="checkbox" name="include_prices" value="1"> <?php esc_html_e( 'Include prices on delivery note', 'itk-commerce-documents' ); ?></label><br><label><input type="checkbox" name="billing_address" value="1"> <?php esc_html_e( 'Use billing address instead of shipping address', 'itk-commerce-documents' ); ?></label></td></tr>
                </tbody></table>
                <?php submit_button( __( 'Generate document', 'itk-commerce-documents' ) ); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Create or update return case', 'itk-commerce-documents' ); ?></h2>
            <p><?php esc_html_e( 'This operational return record is stored through WooCommerce order metadata. By default all order-line quantities are included; extensions may provide a more detailed item-selection UI through the return service.', 'itk-commerce-documents' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_save_return_case">
                <?php wp_nonce_field( 'itk_commerce_save_return_case' ); ?>
                <table class="form-table"><tbody>
                    <tr><th><label for="itk-return-order-id"><?php esc_html_e( 'Order ID', 'itk-commerce-documents' ); ?></label></th><td><input id="itk-return-order-id" type="number" min="1" name="order_id" required></td></tr>
                    <tr><th><label for="itk-return-reason"><?php esc_html_e( 'Reason', 'itk-commerce-documents' ); ?></label></th><td><textarea id="itk-return-reason" class="large-text" name="reason" rows="3"></textarea></td></tr>
                    <tr><th><label for="itk-return-condition"><?php esc_html_e( 'Condition', 'itk-commerce-documents' ); ?></label></th><td><textarea id="itk-return-condition" class="large-text" name="condition" rows="3"></textarea></td></tr>
                    <tr><th><?php esc_html_e( 'Status', 'itk-commerce-documents' ); ?></th><td><select name="status"><option value="requested"><?php esc_html_e( 'Requested', 'itk-commerce-documents' ); ?></option><option value="received"><?php esc_html_e( 'Received', 'itk-commerce-documents' ); ?></option><option value="approved"><?php esc_html_e( 'Approved', 'itk-commerce-documents' ); ?></option><option value="rejected"><?php esc_html_e( 'Rejected', 'itk-commerce-documents' ); ?></option><option value="refunded"><?php esc_html_e( 'Refunded', 'itk-commerce-documents' ); ?></option><option value="closed"><?php esc_html_e( 'Closed', 'itk-commerce-documents' ); ?></option></select></td></tr>
                </tbody></table>
                <?php submit_button( __( 'Save return case', 'itk-commerce-documents' ), 'secondary' ); ?>
            </form>

            <p><em><?php esc_html_e( 'PDF output requires a local PDF renderer such as Dompdf or a renderer connected through itk_commerce_documents_pdf_renderer. No remote PDF service is required by the module.', 'itk-commerce-documents' ); ?></em></p>
        </div>
        <?php
    }

    /** @return void */
    public function download() {
        $this->require_manage_documents();
        check_admin_referer( 'itk_commerce_generate_document' );

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $type = isset( $_POST['document_type'] ) ? sanitize_key( wp_unslash( $_POST['document_type'] ) ) : 'invoice';
        $format = isset( $_POST['format'] ) && 'pdf' === sanitize_key( wp_unslash( $_POST['format'] ) ) ? 'pdf' : 'html';
        $options = array(
            'include_prices'  => ! empty( $_POST['include_prices'] ),
            'billing_address' => ! empty( $_POST['billing_address'] ),
        );
        $this->stream_document( $order_id, $type, $format, $options, array( 'event' => 'admin_download' ) );
    }

    /** @return void */
    public function save_return_case() {
        $this->require_manage_documents();
        check_admin_referer( 'itk_commerce_save_return_case' );

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof \WC_Order ) {
            wp_die( esc_html__( 'Order was not found.', 'itk-commerce-documents' ) );
        }

        $quantities = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $quantities[ absint( $item_id ) ] = absint( $item->get_quantity() );
        }
        $reason = isset( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : '';
        $condition = isset( $_POST['condition'] ) ? wp_unslash( $_POST['condition'] ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'requested';
        $case = $this->returns->save( $order, $quantities, $reason, $condition, $status );
        if ( is_wp_error( $case ) ) {
            wp_die( esc_html( $case->get_error_message() ) );
        }
        $order->add_order_note( sprintf( __( 'IT-Kayali return case %1$s updated to status %2$s.', 'itk-commerce-documents' ), $case['number'], $case['status'] ) );
        wp_safe_redirect( add_query_arg( array( 'page' => 'itk-commerce-documents', 'return_saved' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /** @param \WC_Order $order Order. @return void */
    public function customer_document_links( $order ) {
        if ( ! is_user_logged_in() || ! $order instanceof \WC_Order || (int) $order->get_user_id() !== (int) get_current_user_id() ) {
            return;
        }

        echo '<section class="woocommerce-order-details itk-customer-documents"><h2>' . esc_html__( 'Documents', 'itk-commerce-documents' ) . '</h2><p>';
        foreach ( array( 'invoice' => __( 'Invoice', 'itk-commerce-documents' ), 'delivery-note' => __( 'Delivery note', 'itk-commerce-documents' ), 'return-form' => __( 'Return form', 'itk-commerce-documents' ) ) as $type => $label ) {
            $url = $this->customer_document_url( $order, $type, 'html' );
            echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
        }
        echo '</p></section>';
    }

    /** @return void */
    public function customer_download() {
        if ( ! is_user_logged_in() ) {
            auth_redirect();
        }
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $type = isset( $_GET['document_type'] ) ? sanitize_key( wp_unslash( $_GET['document_type'] ) ) : '';
        $format = isset( $_GET['format'] ) && 'pdf' === sanitize_key( wp_unslash( $_GET['format'] ) ) ? 'pdf' : 'html';
        check_admin_referer( 'itk_commerce_customer_document_' . $order_id . '_' . $type . '_' . $format );

        $order = wc_get_order( $order_id );
        $authorized = $order instanceof \WC_Order && ( (int) $order->get_user_id() === (int) get_current_user_id() || current_user_can( 'itk_manage_documents' ) );
        if ( ! $authorized || ! in_array( $type, array( 'invoice', 'delivery-note', 'return-form' ), true ) ) {
            wp_die( esc_html__( 'You are not allowed to access this document.', 'itk-commerce-documents' ), '', array( 'response' => 403 ) );
        }
        $this->stream_document( $order_id, $type, $format, array(), array( 'event' => 'customer_download' ) );
    }

    /**
     * Attach configured order PDFs to selected WooCommerce customer emails. PDF
     * failure never blocks the transactional email; it is surfaced through a
     * WooCommerce order note for authorized follow-up.
     *
     * @param string[] $attachments Existing attachments.
     * @param string   $email_id Email identifier.
     * @param mixed    $object Email object/order.
     * @param mixed    $email Email instance.
     * @return string[]
     */
    public function email_attachments( $attachments, $email_id, $object, $email ) {
        unset( $email );
        $attachments = is_array( $attachments ) ? $attachments : array();
        $order = $object instanceof \WC_Order ? $object : null;
        if ( ! $order ) {
            return $attachments;
        }

        $default_types = in_array( $email_id, array( 'customer_processing_order', 'customer_completed_order' ), true ) ? array( 'invoice' ) : array();
        /**
         * Filter which Commerce documents should be automatically attached to a
         * WooCommerce email. Return an empty list to disable automatic attachments.
         *
         * @param string[]  $default_types Document types.
         * @param string    $email_id WooCommerce email ID.
         * @param \WC_Order $order Order.
         */
        $types = apply_filters( 'itk_commerce_document_email_types', $default_types, $email_id, $order );
        $types = is_array( $types ) ? array_values( array_intersect( $this->service->types(), array_map( 'sanitize_key', $types ) ) ) : array();

        foreach ( $types as $type ) {
            $html = $this->render_in_order_language( $order, $type, array() );
            if ( is_wp_error( $html ) ) {
                continue;
            }
            $pdf = $this->service->render_pdf( $html );
            if ( is_wp_error( $pdf ) ) {
                do_action( 'itk_commerce_document_email_attachment_error', $pdf, $order, $type, $email_id );
                continue;
            }
            $number = $this->service->number( $order->get_id(), $type );
            if ( is_wp_error( $number ) ) {
                continue;
            }
            $path = wp_tempnam( sanitize_file_name( $type . '-' . $number . '.pdf' ) );
            if ( ! $path || false === file_put_contents( $path, $pdf ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                continue;
            }
            $this->temporary_files[] = $path;
            $attachments[] = $path;
            $this->history->append( $order, $type, $number, 'pdf', $pdf, array( 'event' => 'email_attachment', 'email_id' => $email_id ) );
        }

        return array_values( array_unique( $attachments ) );
    }

    /** @return void */
    public function cleanup_temporary_files() {
        foreach ( array_unique( $this->temporary_files ) as $path ) {
            if ( is_string( $path ) && is_file( $path ) ) {
                @unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- best-effort temporary-file cleanup.
            }
        }
        $this->temporary_files = array();
    }

    /**
     * @param int                 $order_id Order ID.
     * @param string              $type Type.
     * @param string              $format Format.
     * @param array<string,mixed> $options Render options.
     * @param array<string,mixed> $history_context History context.
     * @return void
     */
    private function stream_document( $order_id, $type, $format, array $options, array $history_context ) {
        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order instanceof \WC_Order || ! in_array( $type, $this->service->types(), true ) ) {
            wp_die( esc_html__( 'Document or order was not found.', 'itk-commerce-documents' ) );
        }

        $html = $this->render_in_order_language( $order, $type, $options );
        if ( is_wp_error( $html ) ) {
            wp_die( esc_html( $html->get_error_message() ) );
        }

        $number = $this->service->number( $order_id, $type );
        if ( is_wp_error( $number ) ) {
            wp_die( esc_html( $number->get_error_message() ) );
        }

        $content = $html;
        $mime = 'text/html; charset=UTF-8';
        $extension = 'html';
        $disposition = 'inline';
        if ( 'pdf' === $format ) {
            $content = $this->service->render_pdf( $html );
            if ( is_wp_error( $content ) ) {
                wp_die( esc_html( $content->get_error_message() ) );
            }
            $mime = 'application/pdf';
            $extension = 'pdf';
            $disposition = 'attachment';
        }

        $this->history->append( $order, $type, $number, $format, $content, $history_context );
        nocache_headers();
        header( 'Content-Type: ' . $mime );
        header( 'Content-Disposition: ' . $disposition . '; filename="' . sanitize_file_name( $type . '-' . $number . '.' . $extension ) . '"' );
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- complete generated document or PDF bytes.
        exit;
    }

    /** @param \WC_Order $order Order. @param string $type Type. @param array<string,mixed> $options Options. @return string|\WP_Error */
    private function render_in_order_language( $order, $type, array $options ) {
        $scope = apply_filters( 'itk_commerce_order_language_scope', null );
        if ( is_object( $scope ) && method_exists( $scope, 'run' ) ) {
            return $scope->run(
                $order,
                function () use ( $order, $type, $options ) {
                    return $this->service->render_html( $order->get_id(), $type, $options );
                }
            );
        }
        return $this->service->render_html( $order->get_id(), $type, $options );
    }

    /** @param \WC_Order $order Order. @param string $type Type. @param string $format Format. @return string */
    private function customer_document_url( $order, $type, $format ) {
        $url = add_query_arg(
            array(
                'action'        => 'itk_commerce_customer_document',
                'order_id'      => $order->get_id(),
                'document_type' => $type,
                'format'        => $format,
            ),
            admin_url( 'admin-post.php' )
        );
        return wp_nonce_url( $url, 'itk_commerce_customer_document_' . $order->get_id() . '_' . $type . '_' . $format );
    }

    /** @return void */
    private function require_manage_documents() {
        if ( ! current_user_can( 'itk_manage_documents' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage documents.', 'itk-commerce-documents' ), '', array( 'response' => 403 ) );
        }
    }
}
