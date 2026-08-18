<?php
/**
 * Elementor widgets for the Commerce Suite.
 *
 * @package ITK_Commerce_Elementor
 */

namespace ITK\Commerce\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

final class ProductSummaryWidget extends \Elementor\Widget_Base {
    /** @return string */
    public function get_name() { return 'itk-commerce-product-summary'; }
    /** @return string */
    public function get_title() { return __( 'Commerce Product Summary', 'itk-commerce-elementor' ); }
    /** @return string */
    public function get_icon() { return 'eicon-product-info'; }
    /** @return string[] */
    public function get_categories() { return array( 'itk-commerce' ); }

    /** @return void */
    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Content', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'show_image', array( 'label' => __( 'Image', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        $this->add_control( 'show_excerpt', array( 'label' => __( 'Excerpt', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        $this->add_control( 'show_cart', array( 'label' => __( 'Add to cart', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        $this->end_controls_section();
    }

    /** @return void */
    protected function render() {
        global $product;
        if ( ! $product instanceof \WC_Product ) {
            $post_id = get_the_ID();
            $product = $post_id ? wc_get_product( $post_id ) : null;
        }
        if ( ! $product instanceof \WC_Product ) {
            echo '<p>' . esc_html__( 'This widget requires a WooCommerce product context.', 'itk-commerce-elementor' ) . '</p>';
            return;
        }
        $settings = $this->get_settings_for_display();
        echo '<article class="itk-elementor-product-summary">';
        if ( 'yes' === $settings['show_image'] ) {
            echo wp_kses_post( $product->get_image( 'woocommerce_single' ) );
        }
        echo '<h2>' . esc_html( $product->get_name() ) . '</h2>';
        echo '<div class="price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
        if ( 'yes' === $settings['show_excerpt'] ) {
            echo '<div class="excerpt">' . wp_kses_post( wpautop( $product->get_short_description() ) ) . '</div>';
        }
        if ( 'yes' === $settings['show_cart'] && $product->is_purchasable() ) {
            echo '<a class="button" href="' . esc_url( $product->add_to_cart_url() ) . '">' . esc_html( $product->add_to_cart_text() ) . '</a>';
        }
        echo '</article>';
    }
}

final class CommerceHookWidget extends \Elementor\Widget_Base {
    /** @return string */
    public function get_name() { return 'itk-commerce-hook'; }
    /** @return string */
    public function get_title() { return __( 'Commerce Extension Area', 'itk-commerce-elementor' ); }
    /** @return string */
    public function get_icon() { return 'eicon-code'; }
    /** @return string[] */
    public function get_categories() { return array( 'itk-commerce' ); }

    /** @return void */
    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Extension area', 'itk-commerce-elementor' ) ) );
        $this->add_control(
            'area',
            array(
                'label'   => __( 'Area', 'itk-commerce-elementor' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'product-card-actions',
                'options' => array(
                    'product-card-actions' => __( 'Product card actions', 'itk-commerce-elementor' ),
                    'catalog-toolbar'      => __( 'Catalog toolbar', 'itk-commerce-elementor' ),
                    'custom'               => __( 'Custom extension area', 'itk-commerce-elementor' ),
                ),
            )
        );
        $this->end_controls_section();
    }

    /** @return void */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $area = isset( $settings['area'] ) ? sanitize_key( $settings['area'] ) : 'custom';
        echo '<div class="itk-elementor-extension-area itk-elementor-extension-area--' . esc_attr( $area ) . '">';
        do_action( 'itk_commerce_elementor_extension_area', $area, $this );
        echo '</div>';
    }
}
