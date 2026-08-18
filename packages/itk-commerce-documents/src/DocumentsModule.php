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
        $this->service = new DocumentService();
        add_filter( 'itk_commerce_documents_service', array( $this, 'service_filter' ) );
        add_action( 'itk_commerce_admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_post_itk_commerce_generate_document', array( $this, 'download' ) );
        add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ) );
        add_action( 'woocommerce_order_action_itk_generate_invoice', array( $this, 'mark_invoice_generated' ) );
        do_action( 'itk_commerce_documents_loaded', $this, $this->service );
    }

    /** @param mixed $existing Existing service. @return DocumentService */
    public function service_filter( $existing ) {
        unset( $existing );
        return $this->service;
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
        if ( ! current_user_can( 'itk_manage_documents' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage documents.', 'itk-commerce-documents' ), 403 );
        }
        ?>
        <div class="wrap"><h1><?php esc_html_e( 'Commerce Documents', 'itk-commerce-documents' ); ?></h1>
        <p><?php esc_html_e( 'Generate invoice, delivery-note, return-form or packing-list output from an existing WooCommerce order.', 'itk-commerce-documents' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="itk_commerce_generate_document">
            <?php wp_nonce_field( 'itk_commerce_generate_document' ); ?>
            <table class="form-table"><tbody>
                <tr><th><label for="itk-order-id"><?php esc_html_e( 'Order ID', 'itk-commerce-documents' ); ?></label></th><td><input id="itk-order-id" type="number" min="1" name="order_id" required></td></tr>
                <tr><th><?php esc_html_e( 'Document', 'itk-commerce-documents' ); ?></th><td><select name="document_type"><option value="invoice"><?php esc_html_e( 'Invoice', 'itk-commerce-documents' ); ?></option><option value="delivery-note"><?php esc_html_e( 'Delivery note', 'itk-commerce-documents' ); ?></option><option value="return-form"><?php esc_html_e( 'Return form', 'itk-commerce-documents' ); ?></option><option value="packing-list"><?php esc_html_e( 'Packing list', 'itk-commerce-documents' ); ?></option></select></td></tr>
                <tr><th><?php esc_html_e( 'Format', 'itk-commerce-documents' ); ?></th><td><select name="format"><option value="html"><?php esc_html_e( 'Print-ready HTML', 'itk-commerce-documents' ); ?></option><option value="pdf">PDF</option></select></td></tr>
            </tbody></table>
            <?php submit_button( __( 'Generate document', 'itk-commerce-documents' ) ); ?>
        </form></div>
        <?php
    }

    /** @return void */
    public function download() {
        if ( ! current_user_can( 'itk_manage_documents' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage documents.', 'itk-commerce-documents' ), 403 );
        }
        check_admin_referer( 'itk_commerce_generate_document' );
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $type = isset( $_POST['document_type'] ) ? sanitize_key( wp_unslash( $_POST['document_type'] ) ) : 'invoice';
        $format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'html';
        $html = $this->service->render_html( $order_id, $type );
        if ( is_wp_error( $html ) ) {
            wp_die( esc_html( $html->get_error_message() ) );
        }
        $filename = sanitize_file_name( $type . '-order-' . $order_id );
        nocache_headers();
        if ( 'pdf' === $format ) {
            $pdf = $this->service->render_pdf( $html );
            if ( is_wp_error( $pdf ) ) {
                wp_die( esc_html( $pdf->get_error_message() ) );
            }
            header( 'Content-Type: application/pdf' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '.pdf"' );
            echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF output.
            exit;
        }
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'Content-Disposition: inline; filename="' . $filename . '.html"' );
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- complete rendered document.
        exit;
    }

    /** @param array<string,string> $actions Existing order actions. @return array<string,string> */
    public function order_actions( $actions ) {
        $actions['itk_generate_invoice'] = __( 'IT-Kayali: mark invoice generated', 'itk-commerce-documents' );
        return $actions;
    }

    /** @param \WC_Order $order Order. @return void */
    public function mark_invoice_generated( $order ) {
        if ( $order instanceof \WC_Order ) {
            $order->update_meta_data( '_itk_invoice_generated_at', gmdate( 'c' ) );
            $order->save();
            $order->add_order_note( __( 'Invoice generation was recorded by IT-Kayali Commerce Documents.', 'itk-commerce-documents' ) );
        }
    }
}
