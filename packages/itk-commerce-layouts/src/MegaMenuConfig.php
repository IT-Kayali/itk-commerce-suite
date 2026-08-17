<?php
/**
 * Mega-menu configuration and portable rich-content normalization.
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

        $definition = $this->definition_for_menu_item( $item );
        if ( ! $definition ) {
            return $classes;
        }

        $classes[] = 'itk-menu-item--mega';
        $classes[] = 'itk-mega-menu--' . sanitize_html_class( isset( $definition['width'] ) ? $definition['width'] : 'aligned' );
        $classes[] = 'itk-mega-menu-columns-' . absint( isset( $definition['columns'] ) ? $definition['columns'] : 1 );

        if ( ! empty( $definition['blocks'] ) ) {
            $classes[] = 'itk-menu-item--mega-rich';
        }

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

        $key        = $this->item_key_for_menu_item( $item );
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
     * Resolve the configured definition for a WordPress menu item.
     *
     * @param object $item WordPress menu item.
     * @return array<string,mixed>|null
     */
    public function definition_for_menu_item( $item ) {
        $key = $this->item_key_for_menu_item( $item );
        return $key ? $this->definition( $key ) : null;
    }

    /**
     * Return the portable definition key assigned to a WordPress menu item.
     *
     * @param object $item WordPress menu item.
     * @return string
     */
    public function item_key_for_menu_item( $item ) {
        if ( ! is_object( $item ) || empty( $item->ID ) || ! function_exists( 'get_post_meta' ) ) {
            return '';
        }

        return sanitize_key( get_post_meta( (int) $item->ID, '_itk_commerce_mega_menu_key', true ) );
    }

    /**
     * Return all profile definitions keyed by portable identifier.
     *
     * Rich content is stored under the Layouts module configuration so the
     * basic layout builder can safely update definition width/column metadata
     * without discarding rich block content.
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
            $rich_content = $this->profile_rich_content( $profile );

            foreach ( $profile['layouts']['mega_menu']['definitions'] as $key => $definition ) {
                $key = sanitize_key( $key );
                if ( ! $key || ! is_array( $definition ) ) {
                    continue;
                }

                $normalized = $this->normalize_definition( $definition );

                if ( isset( $rich_content[ $key ] ) && is_array( $rich_content[ $key ] ) ) {
                    $normalized['blocks'] = $this->normalize_blocks(
                        isset( $rich_content[ $key ]['blocks'] ) && is_array( $rich_content[ $key ]['blocks'] )
                            ? $rich_content[ $key ]['blocks']
                            : array()
                    );
                }

                if ( empty( $normalized['blocks'] ) ) {
                    $normalized['blocks'] = array(
                        array(
                            'type' => 'menu',
                            'title' => '',
                            'span' => max( 1, min( 6, (int) $normalized['columns'] ) ),
                        ),
                    );
                }

                $this->definitions[ $key ] = $normalized;
            }
        }

        /**
         * Filter all available portable mega-menu definitions.
         *
         * @param array<string,array<string,mixed>> $definitions Definitions.
         */
        $filtered          = apply_filters( 'itk_commerce_mega_menu_definitions', $this->definitions );
        $this->definitions = is_array( $filtered ) ? $filtered : array();

        return $this->definitions;
    }

    /**
     * @param object $args WordPress menu args.
     * @return bool
     */
    public function is_primary_menu( $args ) {
        return is_object( $args ) && isset( $args->theme_location ) && 'primary' === $args->theme_location;
    }

    /**
     * Keep the data model portable and bounded while leaving rendering to the
     * dedicated rich-panel renderer.
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
            'blocks'       => array(),
        );
    }

    /**
     * @param array<string,mixed> $profile Active profile.
     * @return array<string,mixed>
     */
    private function profile_rich_content( array $profile ) {
        if (
            empty( $profile['modules']['configuration'][ MODULE_ID ]['mega_content'] ) ||
            ! is_array( $profile['modules']['configuration'][ MODULE_ID ]['mega_content'] )
        ) {
            return array();
        }

        return $profile['modules']['configuration'][ MODULE_ID ]['mega_content'];
    }

    /**
     * Normalize up to six portable panel blocks.
     *
     * Supported block types intentionally reference public WordPress/WooCommerce
     * data or customer-profile values only. No executable PHP/JS is accepted.
     *
     * @param array<int,array<string,mixed>> $blocks Raw blocks.
     * @return array<int,array<string,mixed>>
     */
    private function normalize_blocks( array $blocks ) {
        $normalized = array();
        $allowed    = array( 'menu', 'categories', 'products', 'image', 'banner', 'elementor' );

        foreach ( array_slice( $blocks, 0, 6 ) as $block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            $type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : '';
            if ( ! in_array( $type, $allowed, true ) ) {
                continue;
            }

            $item = array(
                'type'  => $type,
                'title' => isset( $block['title'] ) ? sanitize_text_field( $block['title'] ) : '',
                'span'  => max( 1, min( 6, isset( $block['span'] ) ? absint( $block['span'] ) : 1 ) ),
            );

            if ( 'categories' === $type ) {
                $item['slugs']       = $this->normalize_slug_list( isset( $block['slugs'] ) ? $block['slugs'] : array() );
                $item['limit']       = max( 1, min( 12, isset( $block['limit'] ) ? absint( $block['limit'] ) : 6 ) );
                $item['show_images'] = ! empty( $block['show_images'] );
            } elseif ( 'products' === $type ) {
                $source = isset( $block['source'] ) ? sanitize_key( $block['source'] ) : 'latest';
                if ( ! in_array( $source, array( 'latest', 'featured', 'on_sale', 'category', 'ids' ), true ) ) {
                    $source = 'latest';
                }
                $item['source'] = $source;
                $item['value']  = isset( $block['value'] ) ? sanitize_text_field( $block['value'] ) : '';
                $item['limit']  = max( 1, min( 8, isset( $block['limit'] ) ? absint( $block['limit'] ) : 4 ) );
            } elseif ( 'image' === $type ) {
                $item['image_url'] = isset( $block['image_url'] ) ? esc_url_raw( $block['image_url'] ) : '';
                $item['link_url']  = isset( $block['link_url'] ) ? esc_url_raw( $block['link_url'] ) : '';
                $item['alt']       = isset( $block['alt'] ) ? sanitize_text_field( $block['alt'] ) : '';
            } elseif ( 'banner' === $type ) {
                $item['eyebrow']    = isset( $block['eyebrow'] ) ? sanitize_text_field( $block['eyebrow'] ) : '';
                $item['text']       = isset( $block['text'] ) ? sanitize_textarea_field( $block['text'] ) : '';
                $item['image_url']  = isset( $block['image_url'] ) ? esc_url_raw( $block['image_url'] ) : '';
                $item['link_url']   = isset( $block['link_url'] ) ? esc_url_raw( $block['link_url'] ) : '';
                $item['link_label'] = isset( $block['link_label'] ) ? sanitize_text_field( $block['link_label'] ) : '';
            } elseif ( 'elementor' === $type ) {
                $item['template_id'] = isset( $block['template_id'] ) ? absint( $block['template_id'] ) : 0;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * @param mixed $value Raw comma-separated or array value.
     * @return string[]
     */
    private function normalize_slug_list( $value ) {
        if ( is_string( $value ) ) {
            $value = preg_split( '/\s*,\s*/', $value );
        }

        if ( ! is_array( $value ) ) {
            return array();
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map( 'sanitize_title', array_slice( $value, 0, 20 ) )
                )
            )
        );
    }
}
