<?php
/**
 * Elementor dynamic data tags for WooCommerce/profile context.
 *
 * @package ITK_Commerce_Elementor
 */

namespace ITK\Commerce\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

final class CommerceTextTag extends \Elementor\Core\DynamicTags\Tag {
    /** @return string */
    public function get_name() { return 'itk-commerce-dynamic-text'; }

    /** @return string */
    public function get_title() { return __( 'IT-Kayali Commerce Field', 'itk-commerce-elementor' ); }

    /** @return string */
    public function get_group() { return 'itk-commerce'; }

    /** @return string[] */
    public function get_categories() { return array( 'text' ); }

    /** @return void */
    protected function register_controls() {
        $this->add_control(
            'field',
            array(
                'label'   => __( 'Field', 'itk-commerce-elementor' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'product-name',
                'options' => array(
                    'product-name'       => __( 'Product name', 'itk-commerce-elementor' ),
                    'product-sku'        => __( 'Product SKU', 'itk-commerce-elementor' ),
                    'product-price'      => __( 'Product price', 'itk-commerce-elementor' ),
                    'product-stock'      => __( 'Product stock status', 'itk-commerce-elementor' ),
                    'product-short-desc' => __( 'Product short description', 'itk-commerce-elementor' ),
                    'current-language'   => __( 'Current Commerce language', 'itk-commerce-elementor' ),
                    'contact-email'      => __( 'Profile contact email', 'itk-commerce-elementor' ),
                    'contact-phone'      => __( 'Profile contact phone', 'itk-commerce-elementor' ),
                    'contact-address'    => __( 'Profile contact address', 'itk-commerce-elementor' ),
                ),
            )
        );
    }

    /** @return void */
    public function render() {
        $field = sanitize_key( (string) $this->get_settings( 'field' ) );
        $value = $this->resolve( $field );

        /**
         * Filter resolved dynamic value for custom profile/product fields.
         *
         * @param string $value Resolved value.
         * @param string $field Field ID.
         * @param object $tag Dynamic tag instance.
         */
        $value = (string) apply_filters( 'itk_commerce_elementor_dynamic_value', $value, $field, $this );
        echo esc_html( $value );
    }

    /** @param string $field Field. @return string */
    private function resolve( $field ) {
        if ( 0 === strpos( $field, 'product-' ) ) {
            $product = $this->product();
            if ( ! $product ) {
                return '';
            }
            if ( 'product-name' === $field ) { return $product->get_name(); }
            if ( 'product-sku' === $field ) { return (string) $product->get_sku(); }
            if ( 'product-price' === $field ) { return wp_strip_all_tags( $product->get_price_html() ); }
            if ( 'product-stock' === $field ) { return wp_strip_all_tags( wc_get_stock_html( $product ) ); }
            if ( 'product-short-desc' === $field ) { return wp_strip_all_tags( $product->get_short_description() ); }
        }

        if ( 'current-language' === $field ) {
            return sanitize_key( (string) apply_filters( 'itk_commerce_current_language', '' ) );
        }

        $profile = $this->profile();
        $contacts = isset( $profile['contacts'] ) && is_array( $profile['contacts'] ) ? $profile['contacts'] : array();
        if ( 'contact-email' === $field ) { return isset( $contacts['email'] ) ? (string) $contacts['email'] : ''; }
        if ( 'contact-phone' === $field ) { return isset( $contacts['phone'] ) ? (string) $contacts['phone'] : ''; }
        if ( 'contact-address' === $field ) { return isset( $contacts['address'] ) ? (string) $contacts['address'] : ''; }
        return '';
    }

    /** @return \WC_Product|null */
    private function product() {
        global $product;
        if ( $product instanceof \WC_Product ) {
            return $product;
        }
        $id = get_the_ID();
        $resolved = $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
        return $resolved instanceof \WC_Product ? $resolved : null;
    }

    /** @return array<string,mixed> */
    private function profile() {
        if ( ! class_exists( '\ITK\Commerce\Core\Core' ) ) {
            return array();
        }
        $core = \ITK\Commerce\Core\Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;
        return is_array( $profile ) ? $profile : array();
    }
}
