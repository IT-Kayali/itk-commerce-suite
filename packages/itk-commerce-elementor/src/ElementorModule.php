<?php
/**
 * Elementor integration module.
 *
 * @package ITK_Commerce_Elementor
 */

namespace ITK\Commerce\Elementor;

use ITK\Commerce\Core\Contracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class ElementorModule implements ModuleInterface {
    /** @return string */
    public function id() { return MODULE_ID; }

    /** @return string */
    public function version() { return VERSION; }

    /** @return array<string,mixed> */
    public function requirements() {
        return array(
            'core'      => '0.1.0-dev',
            'php'       => '8.1',
            'wordpress' => '6.6',
            'modules'   => array(),
        );
    }

    /** @return void */
    public function register() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
        add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );
        add_action( 'admin_notices', array( $this, 'elementor_notice' ) );
        add_filter( 'itk_commerce_elementor_available', array( $this, 'availability' ) );
        do_action( 'itk_commerce_elementor_loaded', $this );
    }

    /** @param mixed $value Existing availability. @return bool */
    public function availability( $value ) {
        unset( $value );
        return did_action( 'elementor/loaded' ) > 0 || class_exists( '\Elementor\Plugin' );
    }

    /** @param object $manager Elementor categories manager. @return void */
    public function register_category( $manager ) {
        if ( is_object( $manager ) && method_exists( $manager, 'add_category' ) ) {
            $manager->add_category(
                'itk-commerce',
                array(
                    'title' => __( 'IT-Kayali Commerce', 'itk-commerce-elementor' ),
                    'icon'  => 'fa fa-shopping-bag',
                )
            );
        }
    }

    /** @param object $widgets_manager Elementor widgets manager. @return void */
    public function register_widgets( $widgets_manager ) {
        if ( ! class_exists( '\Elementor\Widget_Base' ) || ! is_object( $widgets_manager ) || ! method_exists( $widgets_manager, 'register' ) ) {
            return;
        }
        require_once PATH . '/src/Widgets.php';

        $widgets = array(
            new Widgets\ProductSummaryWidget(),
            new Widgets\ProductsWidget(),
            new Widgets\ProductCategoriesWidget(),
            new Widgets\ProductFilterWidget(),
            new Widgets\ProductSearchWidget(),
            new Widgets\HeroBannerWidget(),
            new Widgets\BranchesWidget(),
            new Widgets\ReviewsWidget(),
            new Widgets\ContactWidget(),
            new Widgets\MiniCartWidget(),
            new Widgets\LanguageSwitcherWidget(),
            new Widgets\MenuWidget(),
            new Widgets\CommerceHookWidget(),
        );

        foreach ( $widgets as $widget ) {
            $widgets_manager->register( $widget );
        }
    }

    /** @param object $dynamic_tags Elementor dynamic tag manager. @return void */
    public function register_dynamic_tags( $dynamic_tags ) {
        if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) || ! is_object( $dynamic_tags ) || ! method_exists( $dynamic_tags, 'register' ) ) {
            return;
        }

        if ( method_exists( $dynamic_tags, 'register_group' ) ) {
            $dynamic_tags->register_group( 'itk-commerce', array( 'title' => __( 'IT-Kayali Commerce', 'itk-commerce-elementor' ) ) );
        }

        require_once PATH . '/src/DynamicTags.php';
        $dynamic_tags->register( new DynamicTags\CommerceTextTag() );
    }

    /** @return void */
    public function elementor_notice() {
        if ( ! current_user_can( 'activate_plugins' ) || class_exists( '\Elementor\Plugin' ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'IT-Kayali Commerce Elementor is active, but Elementor is not currently loaded. The Commerce Suite remains functional without it.', 'itk-commerce-elementor' ) . '</p></div>';
    }
}
