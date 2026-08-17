<?php
/**
 * Keep the Search/Filter controls available when WooCommerce finds no products.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class CatalogNoResultsToolbar {
    /** @var FilterRenderer */
    private $renderer;

    /** @param FilterRenderer $renderer Progressive filter renderer. */
    public function __construct( FilterRenderer $renderer ) {
        $this->renderer = $renderer;
    }

    /** @return void */
    public function register() {
        add_action( 'woocommerce_no_products_found', array( $this, 'render' ), 0 );
    }

    /**
     * WooCommerce normally skips `woocommerce_before_shop_loop` for an empty
     * product loop. Render the module toolbar explicitly so customers can remove
     * a restrictive filter instead of reaching a dead end.
     *
     * @return void
     */
    public function render() {
        echo '<div class="itk-catalog-toolbar itk-catalog-toolbar--no-results" data-itk-catalog-toolbar>';
        $this->renderer->render_toolbar();
        echo '</div>';
    }
}
