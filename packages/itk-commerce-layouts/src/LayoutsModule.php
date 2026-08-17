<?php
/**
 * Commerce Layouts module definition.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

use ITK\Commerce\Core\Contracts\ModuleInterface;
defined( 'ABSPATH' ) || exit;

final class LayoutsModule implements ModuleInterface {
    /** @var LayoutResolver|null */
    private $resolver = null;

    /** @var CommerceTemplateResolver|null */
    private $commerce_resolver = null;

    /** @var MegaMenuConfig|null */
    private $mega_menu = null;

    /** @var RichMegaMenuRenderer|null */
    private $mega_renderer = null;

    /** @var LivePreview|null */
    private $preview = null;

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
            'core'      => '0.1.0-dev',
            'php'       => '8.1',
            'wordpress' => '6.6',
            'modules'   => array(),
        );
    }

    /**
     * Register profile-driven layout extension points and isolated admin tools.
     *
     * @return void
     */
    public function register() {
        if ( null !== $this->resolver ) {
            return;
        }

        $this->resolver          = new LayoutResolver();
        $this->commerce_resolver = new CommerceTemplateResolver();
        $this->mega_menu         = new MegaMenuConfig();
        $this->mega_renderer     = new RichMegaMenuRenderer( $this->mega_menu );
        $this->preview           = new LivePreview();

        add_filter( 'itk_commerce_theme_layout_model', array( $this->resolver, 'resolve_theme_model' ), 10, 2 );
        add_filter( 'itk_commerce_mobile_bottom_enabled', array( $this->resolver, 'mobile_bottom_enabled' ) );
        add_filter( 'itk_commerce_mobile_bottom_items', array( $this->resolver, 'mobile_bottom_items' ) );
        add_filter( 'body_class', array( $this->resolver, 'body_classes' ) );

        add_filter( 'itk_commerce_template_model', array( $this->commerce_resolver, 'resolve_model' ), 10, 2 );
        add_filter( 'itk_commerce_template_options', array( $this->commerce_resolver, 'resolve_options' ), 10, 2 );
        add_filter( 'itk_commerce_product_card_model', array( $this->commerce_resolver, 'resolve_product_card_model' ), 10 );
        add_filter( 'itk_commerce_product_card_options', array( $this->commerce_resolver, 'resolve_product_card_options' ), 10 );
        add_filter( 'itk_commerce_mini_cart_options', array( $this->commerce_resolver, 'resolve_mini_cart_options' ), 10 );
        add_filter( 'itk_commerce_account_options', array( $this->commerce_resolver, 'resolve_account_options' ), 10 );

        add_filter( 'nav_menu_css_class', array( $this->mega_menu, 'menu_item_classes' ), 10, 4 );
        add_filter( 'nav_menu_link_attributes', array( $this->mega_menu, 'menu_link_attributes' ), 10, 4 );
        add_filter( 'wp_nav_menu_objects', array( $this->mega_renderer, 'capture_menu_objects' ), 10, 2 );
        add_filter( 'walker_nav_menu_start_el', array( $this->mega_renderer, 'render_panel' ), 20, 4 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        add_filter( 'itk_commerce_theme_layout_model', array( $this->preview, 'layout_model' ), 999, 2 );
        add_filter( 'itk_commerce_template_model', array( $this->preview, 'commerce_template_model' ), 999, 2 );
        add_filter( 'itk_commerce_template_options', array( $this->preview, 'commerce_template_options' ), 999, 2 );
        add_filter( 'itk_commerce_product_card_model', array( $this->preview, 'product_card_model' ), 999 );
        add_filter( 'itk_commerce_product_card_options', array( $this->preview, 'product_card_options' ), 999 );
        add_filter( 'itk_commerce_mobile_bottom_enabled', array( $this->preview, 'mobile_bottom_enabled' ), 999 );
        add_filter( 'wp_robots', array( $this->preview, 'robots' ) );

        if ( is_admin() ) {
            ( new Admin\LayoutBuilderPage() )->register();
            ( new Admin\CommerceTemplatePage() )->register();
            ( new Admin\ProductCardPage() )->register();
            ( new Admin\MegaMenuFields() )->register();
            ( new Admin\MegaMenuContentPage() )->register();
        }

        /**
         * Fires after the Layouts module has attached its public extension points.
         *
         * @param LayoutResolver           $resolver          Header/Footer resolver.
         * @param MegaMenuConfig           $mega_menu         Mega-menu configuration service.
         * @param LivePreview              $preview           Authenticated preview service.
         * @param RichMegaMenuRenderer     $mega_renderer     Rich panel renderer.
         * @param CommerceTemplateResolver $commerce_resolver Commerce page/component resolver.
         */
        do_action( 'itk_commerce_layouts_loaded', $this->resolver, $this->mega_menu, $this->preview, $this->mega_renderer, $this->commerce_resolver );
    }

    /**
     * Load rich panel assets only when at least one mega-menu definition has
     * explicit rich blocks configured.
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        if ( ! $this->has_rich_mega_menu() ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-rich-mega-menu',
            plugins_url( 'assets/css/mega-menu.css', FILE ),
            array(),
            VERSION
        );

        wp_enqueue_script(
            'itk-commerce-rich-mega-menu',
            plugins_url( 'assets/js/mega-menu.js', FILE ),
            array(),
            VERSION,
            true
        );
    }

    /** @return bool */
    private function has_rich_mega_menu() {
        if ( null === $this->mega_menu ) {
            return false;
        }

        foreach ( $this->mega_menu->definitions() as $definition ) {
            if ( is_array( $definition ) && ! empty( $definition['blocks'] ) ) {
                return true;
            }
        }

        return false;
    }
}
