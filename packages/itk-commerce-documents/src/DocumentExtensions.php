<?php
/**
 * Secondary document integrations registered only after the Documents module boots.
 *
 * @package ITK_Commerce_Documents
 */

namespace ITK\Commerce\Documents;

defined( 'ABSPATH' ) || exit;

final class DocumentExtensions {
    /** @var BarcodeService */
    private $barcode;

    /** @var BatchPackingService */
    private $batch;

    public function __construct() {
        $this->barcode = new BarcodeService();
        $this->batch = new BatchPackingService();
    }

    /** @return void */
    public function register() {
        add_filter( 'itk_commerce_document_code_markup', array( $this, 'barcode_markup' ), 10, 4 );
        add_action( 'itk_commerce_admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_post_itk_commerce_batch_packing', array( $this, 'download_batch' ) );
    }

    /** @param string $markup Existing markup. @param string $number Number. @param \WC_Order $order Order. @param string $type Type. @return string */
    public function barcode_markup( $markup, $number, $order, $type ) {
        unset( $order, $type );
        if ( is_string( $markup ) && '' !== trim( $markup ) ) {
            return $markup;
        }
        return $this->barcode->render( $number );
    }

    /** @param string $parent Parent menu slug. @return void */
    public function admin_menu( $parent ) {
        add_submenu_page(
            $parent,
            __( 'Packing', 'itk-commerce-documents' ),
            __( 'Packing', 'itk-commerce-documents' ),
            'itk_manage_documents',
            'itk-commerce-packing',
            array( $this, 'render_admin' )
        );
    }

    /** @return void */
    public function render_admin() {
        $this->require_capability();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Batch Packing & Picking', 'itk-commerce-documents' ); ?></h1>
            <p><?php esc_html_e( 'Create one consolidated warehouse picking list plus individual packing sections for up to 100 WooCommerce orders.', 'itk-commerce-documents' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itk_commerce_batch_packing">
                <?php wp_nonce_field( 'itk_commerce_batch_packing' ); ?>
                <table class="form-table"><tbody>
                    <tr><th><label for="itk-packing-orders"><?php esc_html_e( 'Order IDs', 'itk-commerce-documents' ); ?></label></th><td><textarea id="itk-packing-orders" class="large-text" rows="5" name="order_ids" required placeholder="1024, 1025, 1026"></textarea><p class="description"><?php esc_html_e( 'Comma, whitespace or line-break separated. Invalid/missing orders are ignored; maximum 100 valid orders per batch.', 'itk-commerce-documents' ); ?></p></td></tr>
                </tbody></table>
                <?php submit_button( __( 'Open print-ready packing batch', 'itk-commerce-documents' ) ); ?>
            </form>
            <p><?php esc_html_e( 'Warehouse/bin locations can be stored on products as _itk_warehouse_location or supplied dynamically through the itk_commerce_packing_location filter.', 'itk-commerce-documents' ); ?></p>
        </div>
        <?php
    }

    /** @return void */
    public function download_batch() {
        $this->require_capability();
        check_admin_referer( 'itk_commerce_batch_packing' );
        $raw = isset( $_POST['order_ids'] ) ? (string) wp_unslash( $_POST['order_ids'] ) : '';
        $ids = preg_split( '/[^0-9]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
        $html = $this->batch->render( is_array( $ids ) ? array_map( 'absint', $ids ) : array() );
        if ( is_wp_error( $html ) ) {
            wp_die( esc_html( $html->get_error_message() ) );
        }
        nocache_headers();
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'Content-Disposition: inline; filename="packing-batch-' . esc_attr( gmdate( 'Ymd-His' ) ) . '.html"' );
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated standalone warehouse document.
        exit;
    }

    /** @return void */
    private function require_capability() {
        if ( ! current_user_can( 'itk_manage_documents' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage packing documents.', 'itk-commerce-documents' ), '', array( 'response' => 403 ) );
        }
    }
}
