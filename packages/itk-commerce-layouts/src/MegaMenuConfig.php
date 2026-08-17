<?php
/**
 * Mega-menu configuration foundation.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class MegaMenuConfig {
    /** @var array<string,mixed>|null|false */
    private $definitions = false;

    /**
     * Mark configured top-level menu items without replacing WordPress menu data.
     *
     * A menu item opts into a portable profile definition through its
     * `_itk_commerce_mega_menu_key` meta value.
     *
     * @param string[] $classes Menu item classes.
     * @param object   $item    WordPress menu item.
     * @param object   $args    Menu arguments.
     * @param int      $depth   Menu depth.
     * @return string[]
     */
    public function menu_item_classes( $classes, $item, $args, $depth ) {
        $classes = is_array( $classes ) ? $classes : array();

        if ( 0 !== (int) $depth || ! $this->is_primary_menu( $args ) ) {
            return $classes;
        }

        $definition = $this->definition_for_item( $item );
        if ( ! $definition ) {
            return $classes;
        }

        $classes[] = 'itk-menu-item--mega';
        $classes[] = 'itk-mega-menu--' . sanitize_html_class( isset( $definition['width'] ) ? $definition['width'] : 'aligned' );
        $classes[] = 'itk-mega-menu-columns-' . absint( isset( $definition['columns'] ) ? $definition['columns'] : 1 );

        return array_values( array_unique( $classes ) );
    }

    /**
     * Add accessible/data attributes used by the interactive mega-menu layer.
     *
     * @param array<string,string> $atts  Link attributes.
     * @param object               $item  WordPress menu item.
     * @param object               $args  Menu arguments.
     * @param int                  $depth Menu depth.
     * @return array<string,string>
     */
    public function menu_link_attributes( $atts, $item, $args, $depth ) {
        $atts = is_array( $atts ) ? $atts : array();

        if ( 0 !== (int) $depth || ! $this->is_primary_menu( $args ) ) {
            return $atts;
        }

        $key        = $this->item_key( $item );
        $definition = $key ? $this->definition( $key ) : null;

        if ( ! $definition ) {
            return $atts;
        }

        $atts['data-itk-mega-menu']         = $key;
        $atts['data-itk-mega-menu-width']   = $definition['width'];
        $atts['data-itk-mega-menu-columns'] = (string) $definition['columns'];
        $atts['aria-haspopup']               = 'true';

        return $atts;
    }

    /**
     * Return a sanitized portable mega-menu definition from the active profile.
     *
     * @param string $key Definition key.
     * @return array<string,mixed>|null
     */
    public function definition( $key ) {
        $key         = sanitize_key( $key );
        $definitions = $this->definitions();

        if ( ! $key || ! isset( $definitions[ $key ] ) || ! is_array( $definitions[ $key ] ) ) {
            return null;
        }

        $definition = $definitions[ $key ];

        /**
         * Filter a resolved portable mega-menu definition.
         *
         * @param array<string,mixed> $definition Definition values.
         * @param string              $key        Definition key.
         */
        $definition = apply_filters( 'itk_commerce_mega_menu_definition', $definition, $key );

        return is_array( $definition ) ? $definition : null;
    }

    /**
     * Return all profile definitions keyed by portable identifier.
     *
     * @return array<string,array<string,mixed>>
     */
    public function definitions() {
        if ( false !== $this->definitions ) {
            return is_array( $this->definitions ) ? $this->definitions : array();
        }

        $this->definitions = array();
        $core              = Core::instance();
        $profile_id        = $core->settings()->active_profile_id();
        $profile           = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        if ( is_array( $profile ) && ! empty( $profile['layouts']['mega_menu']['definitions'] ) && is_array( $profile['layouts']['mega_menu']['definitions'] ) ) {
            foreach ( $profile['layouts']['mega_menu']['definitions'] as $key => $definition ) {
                $key = sanitize_key( $key );
                if ( $key && is_array( $definition ) ) {
                    $this->definitions[ $key ] = $this->normalize_definition( $definition );
                }
            }
        }

        /**
         * Filter all available portable mega-menu definitions.
         *
         * @param array<string,array<string,mixed>> $definitions Definitions.
         */
        $filtered = apply_filters( 'itk_commerce_mega_menu_definitions', $this->definitions );
        $this->definitions = is_array( $filtered ) ? $filtered : array();

        return $this->definitions;
    }

    /**
     * @param object $item WordPress menu item.
     * @return array<string,mixed>|null
     */
    private function definition_for_item( $item ) {
        $key = $this->item_key( $item );
        return $key ? $this->definition( $key ) : null;
    }

    /**
     * @param object $item WordPress menu item.
     * @return string
     */
    private function item_key( $item ) {
        if ( ! is_object( $item ) || empty( $item->ID ) || ! function_exists( 'get_post_meta' ) ) {
            return '';
        }

        return sanitize_key( get_post_meta( (int) $item->ID, '_itk_commerce_mega_menu_key', true ) );
    }

    /**
     * @param object $args WordPress menu args.
     * @return bool
     */
    private function is_primary_menu( $args ) {
        return is_object( $args ) && isset( $args->theme_location ) && 'primary' === $args->theme_location;
    }

    /**
     * Keep the data model portable and bounded while leaving rich content
     * rendering to later Theme/module slices.
     *
     * @param array<string,mixed> $definition Raw definition.
     * @return array<string,mixed>
     */
    private function normalize_definition( array $definition ) {
        $width = isset( $definition['width'] ) ? sanitize_key( $definition['width'] ) : 'aligned';
        if ( ! in_array( $width, array( 'aligned', 'full' ), true ) ) {
            $width = 'aligned';
        }

        $columns = isset( $definition['columns'] ) ? absint( $definition['columns'] ) : 1;
        $columns = max( 1, min( 6, $columns ) );

        return array(
            'label'        => isset( $definition['label'] ) ? sanitize_text_field( $definition['label'] ) : '',
            'width'        => $width,
            'columns'      => $columns,
            'content_type' => isset( $definition['content_type'] ) ? sanitize_key( $definition['content_type'] ) : 'menu',
            'content_key'  => isset( $definition['content_key'] ) ? sanitize_key( $definition['content_key'] ) : '',
        );
    }
}
