<?php
/**
 * Commerce Badges module implementation.
 *
 * Loaded lazily after Commerce Core and WooCommerce are available.
 *
 * @package ITK_Commerce_Badges
 */

namespace ITK\Commerce\Badges;

defined( 'ABSPATH' ) || exit;

final class BadgesModule implements \ITK\Commerce\Core\Contracts\ModuleInterface {
    /** @return string */
    public function id() {
        return MODULE_ID;
    }

    /** @return string */
    public function version() {
        return VERSION;
    }

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
        add_filter( 'itk_commerce_product_badges', array( $this, 'badges' ), 20, 2 );
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_fields' ) );
        add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_fields' ) );
        do_action( 'itk_commerce_badges_loaded', $this );
    }

    /**
     * @param array<string,array<string,string>> $badges Existing badges.
     * @param \WC_Product                        $product Product.
     * @return array<string,array<string,string>>
     */
    public function badges( $badges, $product ) {
        $badges = is_array( $badges ) ? $badges : array();

        if ( $product instanceof \WC_Product && $product->is_on_sale() ) {
            $regular = (float) $product->get_regular_price();
            $sale    = (float) $product->get_sale_price();

            if ( $regular > 0 && $sale >= 0 && $sale < $regular ) {
                $percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
                $badges['sale-percent'] = array(
                    'label' => sprintf( '-%d%%', $percent ),
                    'class' => 'sale-percent',
                );
            }
        }

        if ( $product instanceof \WC_Product ) {
            $custom = trim( (string) $product->get_meta( '_itk_custom_badge', true ) );
            if ( '' !== $custom ) {
                $badges['custom'] = array(
                    'label' => $custom,
                    'class' => 'custom',
                );
            }
        }

        return $badges;
    }

    /** @return void */
    public function product_fields() {
        woocommerce_wp_text_input(
            array(
                'id'          => '_itk_custom_badge',
                'label'       => __( 'IT-Kayali custom badge', 'itk-commerce-badges' ),
                'description' => __( 'Optional product-card label such as Limited, Bestseller or Exclusive.', 'itk-commerce-badges' ),
                'desc_tip'    => true,
            )
        );
    }

    /**
     * @param \WC_Product $product Product.
     * @return void
     */
    public function save_product_fields( $product ) {
        if ( ! $product instanceof \WC_Product ) {
            return;
        }

        $value = isset( $_POST['_itk_custom_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['_itk_custom_badge'] ) ) : '';
        $product->update_meta_data( '_itk_custom_badge', $value );
    }
}
