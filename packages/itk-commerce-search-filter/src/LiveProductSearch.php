<?php
/**
 * Progressive live product/category search using WooCommerce's public Store API.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class LiveProductSearch {
    const INPUT_ID   = 'itk-commerce-live-search';
    const LISTBOX_ID = 'itk-commerce-live-search-results';

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_catalog_toolbar_before', array( $this, 'render' ), 5, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 48 );
    }

    /** @return void */
    public function enqueue_assets() {
        if ( ! $this->catalog_request() ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-live-search',
            plugins_url( 'assets/css/live-search.css', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION
        );

        wp_enqueue_script(
            'itk-commerce-live-search',
            plugins_url( 'assets/js/live-search.js', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION,
            true
        );

        /**
         * Filter bounded live-search presentation/query options.
         *
         * This controls only the public WooCommerce Store API requests performed
         * by the browser; it does not grant additional product visibility.
         *
         * @param array<string,mixed> $options Default options.
         */
        $options = apply_filters(
            'itk_commerce_live_search_options',
            array(
                'min_chars'       => 2,
                'product_limit'   => 6,
                'category_limit'  => 4,
                'show_categories' => true,
                'sku_matching'    => true,
                'debounce_ms'     => 180,
            )
        );

        $options = is_array( $options ) ? $options : array();

        wp_localize_script(
            'itk-commerce-live-search',
            'ITKCommerceLiveSearch',
            array(
                'endpoints' => array(
                    'products'   => esc_url_raw( rest_url( 'wc/store/v1/products' ) ),
                    'categories' => esc_url_raw( rest_url( 'wc/store/v1/products/categories' ) ),
                ),
                'searchUrl' => esc_url_raw( home_url( '/' ) ),
                'options'   => array(
                    'minChars'       => max( 1, min( 6, isset( $options['min_chars'] ) ? absint( $options['min_chars'] ) : 2 ) ),
                    'productLimit'   => max( 1, min( 12, isset( $options['product_limit'] ) ? absint( $options['product_limit'] ) : 6 ) ),
                    'categoryLimit'  => max( 0, min( 8, isset( $options['category_limit'] ) ? absint( $options['category_limit'] ) : 4 ) ),
                    'showCategories' => ! isset( $options['show_categories'] ) || ! empty( $options['show_categories'] ),
                    'skuMatching'    => ! isset( $options['sku_matching'] ) || ! empty( $options['sku_matching'] ),
                    'debounceMs'     => max( 80, min( 800, isset( $options['debounce_ms'] ) ? absint( $options['debounce_ms'] ) : 180 ) ),
                ),
                'messages'  => array(
                    'placeholder' => __( 'Search products…', 'itk-commerce-search-filter' ),
                    'button'      => __( 'Search', 'itk-commerce-search-filter' ),
                    'products'    => __( 'Products', 'itk-commerce-search-filter' ),
                    'categories'  => __( 'Categories', 'itk-commerce-search-filter' ),
                    'allResults'  => __( 'View all results', 'itk-commerce-search-filter' ),
                    'loading'     => __( 'Searching…', 'itk-commerce-search-filter' ),
                    'empty'       => __( 'No matching products or categories found.', 'itk-commerce-search-filter' ),
                    'error'       => __( 'Live results are unavailable. Press Enter to search normally.', 'itk-commerce-search-filter' ),
                    'resultCount' => __( '%d live results available.', 'itk-commerce-search-filter' ),
                    'sku'         => __( 'SKU: %s', 'itk-commerce-search-filter' ),
                ),
            )
        );
    }

    /**
     * Render a normal product search form. JavaScript only enhances suggestions;
     * submitting the form remains a complete product-search fallback.
     *
     * @param array<string,mixed> $context Theme catalog context.
     * @return void
     */
    public function render( $context = array() ) {
        unset( $context );

        if ( ! $this->catalog_request() ) {
            return;
        }

        $value = get_search_query();
        ?>
        <div class="itk-live-search" data-itk-live-search>
            <form class="itk-live-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-itk-live-search-form>
                <label class="screen-reader-text" for="<?php echo esc_attr( self::INPUT_ID ); ?>"><?php esc_html_e( 'Search products', 'itk-commerce-search-filter' ); ?></label>
                <div class="itk-live-search__control">
                    <input
                        id="<?php echo esc_attr( self::INPUT_ID ); ?>"
                        class="itk-live-search__input"
                        type="search"
                        name="s"
                        value="<?php echo esc_attr( $value ); ?>"
                        placeholder="<?php esc_attr_e( 'Search products…', 'itk-commerce-search-filter' ); ?>"
                        autocomplete="off"
                        autocapitalize="none"
                        spellcheck="false"
                        enterkeyhint="search"
                        dir="auto"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="<?php echo esc_attr( self::LISTBOX_ID ); ?>"
                    >
                    <input type="hidden" name="post_type" value="product">
                    <button class="itk-live-search__submit" type="submit"><?php esc_html_e( 'Search', 'itk-commerce-search-filter' ); ?></button>
                </div>

                <div class="itk-live-search__panel" data-itk-live-search-panel hidden>
                    <p class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true" data-itk-live-search-status></p>
                    <div id="<?php echo esc_attr( self::LISTBOX_ID ); ?>" class="itk-live-search__results" role="listbox" data-itk-live-search-results></div>
                </div>
            </form>
        </div>
        <?php
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
