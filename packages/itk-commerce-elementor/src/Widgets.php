<?php
/**
 * Elementor widgets for the Commerce Suite.
 *
 * @package ITK_Commerce_Elementor
 */

namespace ITK\Commerce\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

/** Internal widget helpers. */
final class WidgetSupport {
    /** @return array<string,mixed> */
    public static function profile() {
        if ( ! class_exists( '\ITK\Commerce\Core\Core' ) ) {
            return array();
        }
        $core = \ITK\Commerce\Core\Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;
        return is_array( $profile ) ? $profile : array();
    }

    /** @param int $explicit_id Optional explicit product ID. @return \WC_Product|null */
    public static function product( $explicit_id = 0 ) {
        if ( $explicit_id && function_exists( 'wc_get_product' ) ) {
            $resolved = wc_get_product( absint( $explicit_id ) );
            if ( $resolved instanceof \WC_Product ) {
                return $resolved;
            }
        }
        global $product;
        if ( $product instanceof \WC_Product ) {
            return $product;
        }
        $id = get_the_ID();
        $resolved = $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
        return $resolved instanceof \WC_Product ? $resolved : null;
    }

    /** @return string[] */
    public static function categories() {
        return array( 'itk-commerce' );
    }

    /** @param string $message Editor/frontend placeholder. @return void */
    public static function placeholder( $message ) {
        echo '<div class="itk-elementor-placeholder"><p>' . esc_html( $message ) . '</p></div>';
    }
}

final class ProductSummaryWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-product-summary'; }
    public function get_title() { return __( 'Commerce Product Summary', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-product-info'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Content', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'show_image', array( 'label' => __( 'Image', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        $this->add_control( 'show_excerpt', array( 'label' => __( 'Excerpt', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        $this->add_control( 'show_cart', array( 'label' => __( 'Add to cart', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        $this->end_controls_section();
    }

    protected function render() {
        $product = WidgetSupport::product();
        if ( ! $product ) {
            WidgetSupport::placeholder( __( 'This widget requires a WooCommerce product context.', 'itk-commerce-elementor' ) );
            return;
        }
        $settings = $this->get_settings_for_display();
        echo '<article class="itk-elementor-product-summary">';
        if ( 'yes' === ( $settings['show_image'] ?? '' ) ) {
            echo wp_kses_post( $product->get_image( 'woocommerce_single' ) );
        }
        echo '<h2>' . esc_html( $product->get_name() ) . '</h2>';
        echo '<div class="price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
        if ( 'yes' === ( $settings['show_excerpt'] ?? '' ) ) {
            echo '<div class="excerpt">' . wp_kses_post( wpautop( $product->get_short_description() ) ) . '</div>';
        }
        if ( 'yes' === ( $settings['show_cart'] ?? '' ) && $product->is_purchasable() ) {
            echo '<a class="button" href="' . esc_url( $product->add_to_cart_url() ) . '">' . esc_html( $product->add_to_cart_text() ) . '</a>';
        }
        echo '</article>';
    }
}

final class ProductsWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-products'; }
    public function get_title() { return __( 'Commerce Products', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-products'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'query', array( 'label' => __( 'Product query', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'limit', array( 'label' => __( 'Products', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 8, 'min' => 1, 'max' => 48 ) );
        $this->add_control( 'columns', array( 'label' => __( 'Columns', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 6 ) );
        $this->add_control( 'category', array( 'label' => __( 'Category slugs', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::TEXT, 'description' => __( 'Comma-separated WooCommerce category slugs. Empty shows products across categories.', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'orderby', array( 'label' => __( 'Order by', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'date', 'options' => array( 'date' => __( 'Date', 'itk-commerce-elementor' ), 'title' => __( 'Title', 'itk-commerce-elementor' ), 'price' => __( 'Price', 'itk-commerce-elementor' ), 'popularity' => __( 'Popularity', 'itk-commerce-elementor' ), 'rating' => __( 'Rating', 'itk-commerce-elementor' ), 'rand' => __( 'Random', 'itk-commerce-elementor' ) ) ) );
        $this->add_control( 'order', array( 'label' => __( 'Order', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'DESC', 'options' => array( 'ASC' => 'ASC', 'DESC' => 'DESC' ) ) );
        $this->end_controls_section();
    }

    protected function render() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            WidgetSupport::placeholder( __( 'WooCommerce is required for this widget.', 'itk-commerce-elementor' ) );
            return;
        }
        $settings = $this->get_settings_for_display();
        $limit = max( 1, min( 48, absint( $settings['limit'] ?? 8 ) ) );
        $columns = max( 1, min( 6, absint( $settings['columns'] ?? 4 ) ) );
        $orderby = sanitize_key( $settings['orderby'] ?? 'date' );
        if ( ! in_array( $orderby, array( 'date', 'title', 'price', 'popularity', 'rating', 'rand' ), true ) ) {
            $orderby = 'date';
        }
        $order = 'ASC' === strtoupper( (string) ( $settings['order'] ?? '' ) ) ? 'ASC' : 'DESC';
        $category = implode( ',', array_filter( array_map( 'sanitize_title', explode( ',', (string) ( $settings['category'] ?? '' ) ) ) ) );
        $shortcode = sprintf( '[products limit="%d" columns="%d" orderby="%s" order="%s"%s]', $limit, $columns, esc_attr( $orderby ), esc_attr( $order ), $category ? ' category="' . esc_attr( $category ) . '"' : '' );
        echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce shortcode renders product markup.
    }
}

final class ProductCategoriesWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-product-categories'; }
    public function get_title() { return __( 'Commerce Categories', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-product-categories'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'query', array( 'label' => __( 'Categories', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'number', array( 'label' => __( 'Maximum categories', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 8, 'min' => 1, 'max' => 48 ) );
        $this->add_control( 'columns', array( 'label' => __( 'Columns', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 6 ) );
        $this->add_control( 'parent', array( 'label' => __( 'Parent category ID', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'min' => 0 ) );
        $this->end_controls_section();
    }

    protected function render() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            WidgetSupport::placeholder( __( 'WooCommerce is required for this widget.', 'itk-commerce-elementor' ) );
            return;
        }
        $settings = $this->get_settings_for_display();
        $number = max( 1, min( 48, absint( $settings['number'] ?? 8 ) ) );
        $columns = max( 1, min( 6, absint( $settings['columns'] ?? 4 ) ) );
        $parent = absint( $settings['parent'] ?? 0 );
        echo do_shortcode( sprintf( '[product_categories number="%d" columns="%d" parent="%d"]', $number, $columns, $parent ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

final class ProductFilterWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-product-filter'; }
    public function get_title() { return __( 'Commerce Product Filters', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-filter'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function render() {
        if ( ! has_action( 'itk_commerce_catalog_toolbar' ) ) {
            WidgetSupport::placeholder( __( 'Activate IT-Kayali Commerce Search & Filter to render catalog filters.', 'itk-commerce-elementor' ) );
            return;
        }
        echo '<div class="itk-elementor-product-filters">';
        do_action( 'itk_commerce_catalog_toolbar', array( 'source' => 'elementor' ) );
        echo '</div>';
    }
}

final class ProductSearchWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-product-search'; }
    public function get_title() { return __( 'Commerce Product Search', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-search'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function render() {
        if ( ! function_exists( 'get_product_search_form' ) ) {
            WidgetSupport::placeholder( __( 'WooCommerce product search is unavailable.', 'itk-commerce-elementor' ) );
            return;
        }
        echo '<div class="itk-elementor-product-search">' . get_product_search_form( false ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce owns form markup.
    }
}

final class HeroBannerWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-hero'; }
    public function get_title() { return __( 'Commerce Hero / Banner', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-banner'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Hero', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'eyebrow', array( 'label' => __( 'Eyebrow', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
        $this->add_control( 'title', array( 'label' => __( 'Title', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __( 'Discover our collection', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'text', array( 'label' => __( 'Text', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::TEXTAREA ) );
        $this->add_control( 'image', array( 'label' => __( 'Image', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::MEDIA ) );
        $this->add_control( 'button_label', array( 'label' => __( 'Button label', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
        $this->add_control( 'button_url', array( 'label' => __( 'Button URL', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::URL ) );
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $image = isset( $settings['image']['url'] ) ? esc_url( $settings['image']['url'] ) : '';
        $link = isset( $settings['button_url']['url'] ) ? esc_url( $settings['button_url']['url'] ) : '';
        echo '<section class="itk-elementor-hero">';
        if ( $image ) { echo '<div class="itk-elementor-hero__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy"></div>'; }
        echo '<div class="itk-elementor-hero__content">';
        if ( ! empty( $settings['eyebrow'] ) ) { echo '<p class="itk-elementor-hero__eyebrow">' . esc_html( $settings['eyebrow'] ) . '</p>'; }
        if ( ! empty( $settings['title'] ) ) { echo '<h2>' . esc_html( $settings['title'] ) . '</h2>'; }
        if ( ! empty( $settings['text'] ) ) { echo '<div>' . wp_kses_post( wpautop( $settings['text'] ) ) . '</div>'; }
        if ( ! empty( $settings['button_label'] ) && $link ) { echo '<p><a class="button" href="' . esc_url( $link ) . '">' . esc_html( $settings['button_label'] ) . '</a></p>'; }
        echo '</div></section>';
    }
}

final class BranchesWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-branches'; }
    public function get_title() { return __( 'Commerce Branches', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-google-maps'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function render() {
        $profile = WidgetSupport::profile();
        $branches = $profile['contacts']['branches'] ?? array();
        $branches = is_array( $branches ) ? array_slice( $branches, 0, 50 ) : array();
        if ( empty( $branches ) ) {
            WidgetSupport::placeholder( __( 'No branch data is defined in the active customer profile.', 'itk-commerce-elementor' ) );
            return;
        }
        echo '<div class="itk-elementor-branches">';
        foreach ( $branches as $branch ) {
            if ( ! is_array( $branch ) ) { continue; }
            echo '<article class="itk-elementor-branch">';
            if ( ! empty( $branch['name'] ) ) { echo '<h3>' . esc_html( $branch['name'] ) . '</h3>'; }
            if ( ! empty( $branch['address'] ) ) { echo '<p>' . nl2br( esc_html( $branch['address'] ) ) . '</p>'; }
            if ( ! empty( $branch['phone'] ) ) { echo '<p>' . esc_html( $branch['phone'] ) . '</p>'; }
            if ( ! empty( $branch['hours'] ) ) { echo '<p>' . nl2br( esc_html( $branch['hours'] ) ) . '</p>'; }
            echo '</article>';
        }
        echo '</div>';
    }
}

final class ReviewsWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-reviews'; }
    public function get_title() { return __( 'Commerce Product Reviews', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-testimonial'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'query', array( 'label' => __( 'Reviews', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'product_id', array( 'label' => __( 'Product ID', 'itk-commerce-elementor' ), 'description' => __( 'Leave 0 to use the current product.', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'min' => 0 ) );
        $this->add_control( 'limit', array( 'label' => __( 'Maximum reviews', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 5, 'min' => 1, 'max' => 20 ) );
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $product = WidgetSupport::product( absint( $settings['product_id'] ?? 0 ) );
        if ( ! $product ) {
            WidgetSupport::placeholder( __( 'Choose a product or use this widget in a product context.', 'itk-commerce-elementor' ) );
            return;
        }
        $reviews = get_comments( array( 'post_id' => $product->get_id(), 'status' => 'approve', 'number' => max( 1, min( 20, absint( $settings['limit'] ?? 5 ) ) ), 'type' => 'review' ) );
        if ( empty( $reviews ) ) {
            WidgetSupport::placeholder( __( 'No approved product reviews found.', 'itk-commerce-elementor' ) );
            return;
        }
        echo '<div class="itk-elementor-reviews">';
        foreach ( $reviews as $review ) {
            $rating = absint( get_comment_meta( $review->comment_ID, 'rating', true ) );
            echo '<article class="itk-elementor-review"><header><strong>' . esc_html( get_comment_author( $review ) ) . '</strong>';
            if ( $rating && function_exists( 'wc_get_rating_html' ) ) { echo wp_kses_post( wc_get_rating_html( $rating ) ); }
            echo '</header><div>' . wp_kses_post( wpautop( $review->comment_content ) ) . '</div></article>';
        }
        echo '</div>';
    }
}

final class ContactWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-contact'; }
    public function get_title() { return __( 'Commerce Contact Data', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-call-to-action'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Contact data', 'itk-commerce-elementor' ) ) );
        foreach ( array( 'email' => __( 'Email', 'itk-commerce-elementor' ), 'phone' => __( 'Phone', 'itk-commerce-elementor' ), 'address' => __( 'Address', 'itk-commerce-elementor' ), 'hours' => __( 'Opening hours', 'itk-commerce-elementor' ) ) as $key => $label ) {
            $this->add_control( 'show_' . $key, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
        }
        $this->end_controls_section();
    }

    protected function render() {
        $profile = WidgetSupport::profile();
        $contacts = $profile['contacts'] ?? array();
        $contacts = is_array( $contacts ) ? $contacts : array();
        $settings = $this->get_settings_for_display();
        if ( empty( $contacts ) ) {
            WidgetSupport::placeholder( __( 'No contact data is defined in the active customer profile.', 'itk-commerce-elementor' ) );
            return;
        }
        echo '<address class="itk-elementor-contact">';
        if ( 'yes' === ( $settings['show_email'] ?? '' ) && ! empty( $contacts['email'] ) ) { echo '<div><a href="mailto:' . esc_attr( antispambot( $contacts['email'] ) ) . '">' . esc_html( antispambot( $contacts['email'] ) ) . '</a></div>'; }
        if ( 'yes' === ( $settings['show_phone'] ?? '' ) && ! empty( $contacts['phone'] ) ) { echo '<div>' . esc_html( $contacts['phone'] ) . '</div>'; }
        if ( 'yes' === ( $settings['show_address'] ?? '' ) && ! empty( $contacts['address'] ) ) { echo '<div>' . nl2br( esc_html( $contacts['address'] ) ) . '</div>'; }
        if ( 'yes' === ( $settings['show_hours'] ?? '' ) && ! empty( $contacts['hours'] ) ) { echo '<div>' . nl2br( esc_html( $contacts['hours'] ) ) . '</div>'; }
        echo '</address>';
    }
}

final class MiniCartWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-mini-cart'; }
    public function get_title() { return __( 'Commerce Mini Cart', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-cart'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function render() {
        if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
            WidgetSupport::placeholder( __( 'WooCommerce mini cart is unavailable.', 'itk-commerce-elementor' ) );
            return;
        }
        echo '<div class="itk-elementor-mini-cart widget_shopping_cart_content">';
        woocommerce_mini_cart();
        echo '</div>';
    }
}

final class LanguageSwitcherWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-language'; }
    public function get_title() { return __( 'Commerce Language Switcher', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-globe'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Language switcher', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'display', array( 'label' => __( 'Display', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'label', 'options' => array( 'label' => __( 'Label', 'itk-commerce-elementor' ), 'code' => __( 'Code', 'itk-commerce-elementor' ), 'both' => __( 'Label + code', 'itk-commerce-elementor' ) ) ) );
        $this->end_controls_section();
    }

    protected function render() {
        if ( ! shortcode_exists( 'itk_language_switcher' ) ) {
            WidgetSupport::placeholder( __( 'Activate IT-Kayali Commerce Multilingual for the language switcher.', 'itk-commerce-elementor' ) );
            return;
        }
        $settings = $this->get_settings_for_display();
        $display = in_array( $settings['display'] ?? '', array( 'label', 'code', 'both' ), true ) ? $settings['display'] : 'label';
        echo do_shortcode( '[itk_language_switcher display="' . esc_attr( $display ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

final class MenuWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-menu'; }
    public function get_title() { return __( 'Commerce Menu', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-nav-menu'; }
    public function get_categories() { return WidgetSupport::categories(); }

    protected function register_controls() {
        $this->start_controls_section( 'content', array( 'label' => __( 'Menu', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'menu', array( 'label' => __( 'Menu ID, slug or name', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::TEXT, 'description' => __( 'Use an existing WordPress navigation menu.', 'itk-commerce-elementor' ) ) );
        $this->add_control( 'depth', array( 'label' => __( 'Depth', 'itk-commerce-elementor' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 2, 'min' => 1, 'max' => 5 ) );
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $menu = sanitize_text_field( $settings['menu'] ?? '' );
        if ( '' === $menu ) {
            WidgetSupport::placeholder( __( 'Choose a WordPress navigation menu.', 'itk-commerce-elementor' ) );
            return;
        }
        $html = wp_nav_menu( array( 'menu' => $menu, 'container' => 'nav', 'container_class' => 'itk-elementor-menu', 'menu_class' => 'itk-elementor-menu__list', 'depth' => max( 1, min( 5, absint( $settings['depth'] ?? 2 ) ) ), 'fallback_cb' => false, 'echo' => false ) );
        echo is_string( $html ) ? $html : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress nav-menu renderer owns markup.
    }
}

final class CommerceHookWidget extends \Elementor\Widget_Base {
    public function get_name() { return 'itk-commerce-hook'; }
    public function get_title() { return __( 'Commerce Extension Area', 'itk-commerce-elementor' ); }
    public function get_icon() { return 'eicon-code'; }
    public function get_categories() { return WidgetSupport::categories(); }

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

    protected function render() {
        $settings = $this->get_settings_for_display();
        $area = isset( $settings['area'] ) ? sanitize_key( $settings['area'] ) : 'custom';
        echo '<div class="itk-elementor-extension-area itk-elementor-extension-area--' . esc_attr( $area ) . '">';
        do_action( 'itk_commerce_elementor_extension_area', $area, $this );
        echo '</div>';
    }
}
