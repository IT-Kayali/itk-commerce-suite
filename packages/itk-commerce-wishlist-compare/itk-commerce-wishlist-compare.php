<?php
/**
 * Plugin Name: IT-Kayali Commerce Wishlist & Compare
 * Description: Lightweight wishlist and product comparison using the Commerce Theme product-card extension contract.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-wishlist-compare
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Wishlist_Compare
 */

namespace ITK\Commerce\WishlistCompare;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const FILE      = __FILE__;
const PATH      = __DIR__;
const MODULE_ID = 'itk-commerce-wishlist-compare';

add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare', 10 );

/** @return void */
function prepare() {
    if ( ! interface_exists( '\ITK\Commerce\Core\Contracts\ModuleInterface' ) || ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\register_module' );
}

/** @param object $registry Registry. @return void */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new WishlistCompareModule() );
    }
}

final class WishlistCompareModule implements \ITK\Commerce\Core\Contracts\ModuleInterface {
    /** @return string */ public function id() { return MODULE_ID; }
    /** @return string */ public function version() { return VERSION; }
    /** @return array<string,mixed> */ public function requirements() { return array( 'core' => '0.1.0-dev', 'php' => '8.1', 'wordpress' => '6.6', 'woocommerce' => null, 'modules' => array() ); }

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_product_card_actions', array( $this, 'buttons' ), 20, 1 );
        add_action( 'woocommerce_single_product_summary', array( $this, 'single_buttons' ), 35 );
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
        add_shortcode( 'itk_wishlist', array( $this, 'wishlist_shortcode' ) );
        add_shortcode( 'itk_compare', array( $this, 'compare_shortcode' ) );
        do_action( 'itk_commerce_wishlist_compare_loaded', $this );
    }

    /** @return void */
    public function assets() {
        wp_enqueue_script( 'itk-commerce-wishlist-compare', plugins_url( 'assets/wishlist-compare.js', FILE ), array(), VERSION, true );
        wp_localize_script(
            'itk-commerce-wishlist-compare',
            'ITKWishlistCompare',
            array(
                'endpoint' => esc_url_raw( rest_url( 'wc/store/v1/products' ) ),
                'labels'   => array(
                    'wishlist' => __( 'Wishlist', 'itk-commerce-wishlist-compare' ),
                    'compare'  => __( 'Compare', 'itk-commerce-wishlist-compare' ),
                    'empty'    => __( 'No products saved yet.', 'itk-commerce-wishlist-compare' ),
                    'remove'   => __( 'Remove', 'itk-commerce-wishlist-compare' ),
                ),
            )
        );
    }

    /** @param \WC_Product $product Product. @return void */
    public function buttons( $product ) {
        if ( ! $product instanceof \WC_Product ) { return; }
        $this->render_buttons( $product->get_id() );
    }

    /** @return void */
    public function single_buttons() {
        global $product;
        if ( $product instanceof \WC_Product ) { $this->render_buttons( $product->get_id() ); }
    }

    /** @param int $product_id Product ID. @return void */
    private function render_buttons( $product_id ) {
        echo '<div class="itk-wishlist-compare-actions" data-product-id="' . esc_attr( (string) $product_id ) . '">';
        echo '<button type="button" class="button" data-itk-wishlist-toggle="' . esc_attr( (string) $product_id ) . '" aria-pressed="false">' . esc_html__( 'Wishlist', 'itk-commerce-wishlist-compare' ) . '</button> ';
        echo '<button type="button" class="button" data-itk-compare-toggle="' . esc_attr( (string) $product_id ) . '" aria-pressed="false">' . esc_html__( 'Compare', 'itk-commerce-wishlist-compare' ) . '</button>';
        echo '</div>';
    }

    /** @return string */
    public function wishlist_shortcode() {
        return '<div class="itk-saved-products" data-itk-saved-products="wishlist" aria-live="polite"></div>';
    }

    /** @return string */
    public function compare_shortcode() {
        return '<div class="itk-saved-products itk-compare-table" data-itk-saved-products="compare" aria-live="polite"></div>';
    }
}
