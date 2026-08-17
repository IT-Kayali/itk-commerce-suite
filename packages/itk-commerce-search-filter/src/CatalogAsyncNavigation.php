<?php
/**
 * Progressive same-origin catalog navigation enhancement.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class CatalogAsyncNavigation {
    /** @return void */
    public function register() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 45 );
        add_action( 'woocommerce_before_shop_loop', array( $this, 'results_open' ), 90 );
        add_action( 'woocommerce_after_shop_loop', array( $this, 'results_close' ), 90 );
        add_action( 'woocommerce_no_products_found', array( $this, 'results_open' ), 1 );
        add_action( 'woocommerce_no_products_found', array( $this, 'results_close' ), 99 );
    }

    /** @return void */
    public function enqueue_assets() {
        if ( ! $this->catalog_request() ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-search-filter-async',
            plugins_url( 'assets/css/catalog-async.css', \ITK\Commerce\SearchFilter\FILE ),
            array( 'itk-commerce-search-filter-ui' ),
            \ITK\Commerce\SearchFilter\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-search-filter-async',
            plugins_url( 'assets/js/catalog-async.js', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION,
            true
        );

        wp_localize_script(
            'itk-commerce-search-filter-async',
            'ITKCommerceCatalogAsync',
            array(
                'messages' => array(
                    'updated' => __( 'Products updated.', 'itk-commerce-search-filter' ),
                    'loading' => __( 'Updating products…', 'itk-commerce-search-filter' ),
                ),
            )
        );
    }

    /**
     * Open a stable result boundary after WooCommerce's before-loop controls.
     * The browser enhancement replaces only this module-owned wrapper content.
     *
     * @return void
     */
    public function results_open() {
        echo '<div class="itk-catalog-results" data-itk-catalog-results aria-busy="false">';
        echo '<p class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true" data-itk-catalog-live-status></p>';
    }

    /** @return void */
    public function results_close() {
        echo '</div>';
    }

    /** @return bool */
    private function catalog_request() {
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            return true;
        }
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            return true;
        }
        return false;
    }
}
