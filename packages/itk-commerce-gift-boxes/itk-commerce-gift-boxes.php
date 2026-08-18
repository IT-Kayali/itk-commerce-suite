<?php
/**
 * Plugin Name: IT-Kayali Commerce Gift Boxes
 * Description: Configurable WooCommerce gift boxes with bounded selectable products and order-line persistence.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-gift-boxes
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Gift_Boxes
 */

namespace ITK\Commerce\GiftBoxes;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const MODULE_ID = 'itk-commerce-gift-boxes';

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
        $registry->register( new GiftBoxesModule() );
    }
}

final class GiftBoxesModule implements \ITK\Commerce\Core\Contracts\ModuleInterface {
    /** @return string */ public function id() { return MODULE_ID; }
    /** @return string */ public function version() { return VERSION; }
    /** @return array<string,mixed> */ public function requirements() { return array( 'core' => '0.1.0-dev', 'php' => '8.1', 'wordpress' => '6.6', 'woocommerce' => null, 'modules' => array() ); }

    /** @return void */
    public function register() {
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'fields' ) );
        add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'selector' ) );
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate' ), 20, 3 );
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'cart_data' ), 20, 3 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_data' ), 20, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'order_meta' ), 20, 4 );
        do_action( 'itk_commerce_gift_boxes_loaded', $this );
    }

    /** @return void */
    public function fields() {
        woocommerce_wp_checkbox( array( 'id' => '_itk_gift_box', 'label' => __( 'Configurable gift box', 'itk-commerce-gift-boxes' ) ) );
        woocommerce_wp_text_input( array( 'id' => '_itk_gift_box_category', 'label' => __( 'Selectable category slug', 'itk-commerce-gift-boxes' ), 'description' => __( 'Products from this category can be selected inside the box.', 'itk-commerce-gift-boxes' ), 'desc_tip' => true ) );
        woocommerce_wp_text_input( array( 'id' => '_itk_gift_box_limit', 'label' => __( 'Maximum selections', 'itk-commerce-gift-boxes' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '1', 'max' => '24' ), 'value' => '6' ) );
    }

    /** @param \WC_Product $product Product. @return void */
    public function save_fields( $product ) {
        if ( ! $product instanceof \WC_Product ) { return; }
        $product->update_meta_data( '_itk_gift_box', isset( $_POST['_itk_gift_box'] ) ? 'yes' : 'no' );
        $product->update_meta_data( '_itk_gift_box_category', isset( $_POST['_itk_gift_box_category'] ) ? sanitize_title( wp_unslash( $_POST['_itk_gift_box_category'] ) ) : '' );
        $limit = isset( $_POST['_itk_gift_box_limit'] ) ? absint( $_POST['_itk_gift_box_limit'] ) : 6;
        $product->update_meta_data( '_itk_gift_box_limit', max( 1, min( 24, $limit ) ) );
    }

    /** @return void */
    public function selector() {
        global $product;
        if ( ! $product instanceof \WC_Product || 'yes' !== $product->get_meta( '_itk_gift_box', true ) ) { return; }
        $category = sanitize_title( (string) $product->get_meta( '_itk_gift_box_category', true ) );
        $limit = max( 1, min( 24, absint( $product->get_meta( '_itk_gift_box_limit', true ) ?: 6 ) ) );
        if ( '' === $category ) { return; }
        $choices = wc_get_products( array( 'status' => 'publish', 'limit' => 48, 'category' => array( $category ), 'orderby' => 'name', 'order' => 'ASC' ) );
        if ( empty( $choices ) ) { return; }
        echo '<fieldset class="itk-gift-box"><legend>' . esc_html( sprintf( __( 'Choose up to %d items', 'itk-commerce-gift-boxes' ), $limit ) ) . '</legend>';
        foreach ( $choices as $choice ) {
            if ( ! $choice instanceof \WC_Product || $choice->get_id() === $product->get_id() ) { continue; }
            echo '<label style="display:block"><input type="checkbox" name="itk_gift_box_items[]" value="' . esc_attr( (string) $choice->get_id() ) . '"> ' . esc_html( $choice->get_name() ) . '</label>';
        }
        echo '</fieldset>';
    }

    /** @param bool $passed Valid. @param int $product_id Product ID. @param int $quantity Quantity. @return bool */
    public function validate( $passed, $product_id, $quantity ) {
        unset( $quantity );
        $product = wc_get_product( $product_id );
        if ( ! $product || 'yes' !== $product->get_meta( '_itk_gift_box', true ) ) { return $passed; }
        $selected = $this->selected_ids();
        $limit = max( 1, min( 24, absint( $product->get_meta( '_itk_gift_box_limit', true ) ?: 6 ) ) );
        if ( empty( $selected ) || count( $selected ) > $limit || ! $this->selection_allowed( $product, $selected ) ) {
            wc_add_notice( sprintf( __( 'Please select between 1 and %d valid gift-box items.', 'itk-commerce-gift-boxes' ), $limit ), 'error' );
            return false;
        }
        return $passed;
    }

    /** @param array<string,mixed> $data Cart data. @param int $product_id Product ID. @param int $variation_id Variation ID. @return array<string,mixed> */
    public function cart_data( $data, $product_id, $variation_id ) {
        unset( $variation_id );
        $product = wc_get_product( $product_id );
        if ( $product && 'yes' === $product->get_meta( '_itk_gift_box', true ) ) {
            $selected = $this->selected_ids();
            if ( $this->selection_allowed( $product, $selected ) ) {
                $data['itk_gift_box_items'] = $selected;
                $data['itk_gift_box_key'] = wp_generate_uuid4();
            }
        }
        return $data;
    }

    /** @param array<int,array<string,mixed>> $item_data Display data. @param array<string,mixed> $cart_item Cart item. @return array<int,array<string,mixed>> */
    public function display_cart_data( $item_data, $cart_item ) {
        if ( empty( $cart_item['itk_gift_box_items'] ) || ! is_array( $cart_item['itk_gift_box_items'] ) ) { return $item_data; }
        $names = array();
        foreach ( $cart_item['itk_gift_box_items'] as $id ) { $p = wc_get_product( $id ); if ( $p ) { $names[] = $p->get_name(); } }
        if ( $names ) { $item_data[] = array( 'key' => __( 'Gift box contents', 'itk-commerce-gift-boxes' ), 'value' => implode( ', ', $names ) ); }
        return $item_data;
    }

    /** @param \WC_Order_Item_Product $item Order item. @param string $cart_item_key Key. @param array<string,mixed> $values Cart values. @param \WC_Order $order Order. @return void */
    public function order_meta( $item, $cart_item_key, $values, $order ) {
        unset( $cart_item_key, $order );
        if ( empty( $values['itk_gift_box_items'] ) || ! is_array( $values['itk_gift_box_items'] ) ) { return; }
        $names = array();
        foreach ( $values['itk_gift_box_items'] as $id ) { $p = wc_get_product( $id ); if ( $p ) { $names[] = $p->get_name(); } }
        if ( $names ) { $item->add_meta_data( __( 'Gift box contents', 'itk-commerce-gift-boxes' ), implode( ', ', $names ), true ); }
    }

    /** @return int[] */
    private function selected_ids() {
        $raw = isset( $_POST['itk_gift_box_items'] ) && is_array( $_POST['itk_gift_box_items'] ) ? wp_unslash( $_POST['itk_gift_box_items'] ) : array();
        return array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
    }

    /** @param \WC_Product $box Box product. @param int[] $ids Selected IDs. @return bool */
    private function selection_allowed( $box, array $ids ) {
        if ( empty( $ids ) ) { return false; }
        $category = sanitize_title( (string) $box->get_meta( '_itk_gift_box_category', true ) );
        if ( '' === $category ) { return false; }
        foreach ( $ids as $id ) {
            if ( $id === $box->get_id() || ! has_term( $category, 'product_cat', $id ) || ! wc_get_product( $id ) ) { return false; }
        }
        return true;
    }
}
