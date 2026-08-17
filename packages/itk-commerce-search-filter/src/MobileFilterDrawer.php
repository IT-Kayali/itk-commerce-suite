<?php
/**
 * Progressive mobile off-canvas enhancement for the server-rendered filter UI.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class MobileFilterDrawer {
    /** @return void */
    public function register() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 47 );
    }

    /** @return void */
    public function enqueue_assets() {
        if ( ! $this->catalog_request() ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-search-filter-drawer',
            plugins_url( 'assets/css/filter-drawer.css', \ITK\Commerce\SearchFilter\FILE ),
            array( 'itk-commerce-search-filter-ui' ),
            \ITK\Commerce\SearchFilter\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-search-filter-drawer',
            plugins_url( 'assets/js/filter-drawer.js', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION,
            true
        );

        wp_localize_script(
            'itk-commerce-search-filter-drawer',
            'ITKCommerceFilterDrawer',
            array(
                'breakpoint' => 760,
                'labels'     => array(
                    'title' => __( 'Filters', 'itk-commerce-search-filter' ),
                    'close' => __( 'Close filters', 'itk-commerce-search-filter' ),
                ),
            )
        );
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
