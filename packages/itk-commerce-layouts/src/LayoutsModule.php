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

    /**
     * @return string
     */
    public function id() {
        return 'itk-commerce-layouts';
    }

    /**
     * @return string
     */
    public function version() {
        return VERSION;
    }

    /**
     * @return array<string,mixed>
     */
    public function requirements() {
        return array(
            'core'      => '0.1.0-dev',
            'php'       => '8.1',
            'wordpress' => '6.6',
            'modules'   => array(),
        );
    }

    /**
     * Register profile-driven layout extension points.
     *
     * @return void
     */
    public function register() {
        if ( null !== $this->resolver ) {
            return;
        }

        $this->resolver = new LayoutResolver();

        add_filter( 'itk_commerce_theme_layout_model', array( $this->resolver, 'resolve_theme_model' ), 10, 2 );
        add_filter( 'itk_commerce_mobile_bottom_enabled', array( $this->resolver, 'mobile_bottom_enabled' ) );
        add_filter( 'itk_commerce_mobile_bottom_items', array( $this->resolver, 'mobile_bottom_items' ) );
        add_filter( 'body_class', array( $this->resolver, 'body_classes' ) );

        /**
         * Fires after the Layouts module has attached its public extension points.
         *
         * @param LayoutResolver $resolver Active resolver.
         */
        do_action( 'itk_commerce_layouts_loaded', $this->resolver );
    }
}
