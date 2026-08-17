<?php
/**
 * Rich mega-menu panel rendering.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

defined( 'ABSPATH' ) || exit;

final class RichMegaMenuRenderer {
    /** @var MegaMenuConfig */
    private $config;

    /** @var array<int,array<int,object>> */
    private $items_by_parent = array();

    /**
     * @param MegaMenuConfig $config Mega-menu configuration service.
     */
    public function __construct( MegaMenuConfig $config ) {
        $this->config = $config;
    }

    /**
     * Capture the current primary menu tree so a rich panel can reuse the same
     * menu children without duplicating WordPress menu configuration.
     *
     * @param array<int,object> $items Menu items.
     * @param object            $args  Menu args.
     * @return array<int,object>
     */
    public function capture_menu_objects( $items, $args ) {
        if ( ! $this->config->is_primary_menu( $args ) || ! is_array( $items ) ) {
            return $items;
        }

        $this->items_by_parent = array();

        foreach ( $items as $item ) {
            if ( ! is_object( $item ) || empty( $item->ID ) ) {
                continue;
            }

            $parent = isset( $item->menu_item_parent ) ? absint( $item->menu_item_parent ) : 0;
            if ( ! isset( $this->items_by_parent[ $parent ] ) ) {
                $this->items_by_parent[ $parent ] = array();
            }
            $this->items_by_parent[ $parent ][] = $item;
        }

        return $items;
    }

    /**
     * Append an accessible rich panel to configured top-level menu items.
     *
     * @param string $item_output Existing link output.
     * @param object $item        WordPress menu item.
     * @param int    $depth       Menu depth.
     * @param object $args        Menu args.
     * @return string
     */
    public function render_panel( $item_output, $item, $depth, $args ) {
        if ( 0 !== (int) $depth || ! $this->config->is_primary_menu( $args ) ) {
            return $item_output;
        }

        $definition = $this->config->definition_for_menu_item( $item );
        $key        = $this->config->item_key_for_menu_item( $item );

        if ( ! $definition || ! $key || empty( $definition['blocks'] ) || ! is_array( $definition['blocks'] ) ) {
            return $item_output;
        }

        $item_id  = ! empty( $item->ID ) ? absint( $item->ID ) : 0;
        $panel_id = 'itk-mega-panel-' . $item_id;
        $label    = ! empty( $definition['label'] ) ? $definition['label'] : ( isset( $item->title ) ? $item->title : $key );
        $columns  = max( 1, min( 6, isset( $definition['columns'] ) ? absint( $definition['columns'] ) : 1 ) );

        ob_start();
        ?>
        <button class="itk-mega-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" data-itk-mega-toggle>
            <span class="screen-reader-text">
                <?php echo esc_html( sprintf( __( 'Open %s menu', 'itk-commerce-layouts' ), $label ) ); ?>
            </span>
            <span aria-hidden="true">⌄</span>
        </button>
        <div
            id="<?php echo esc_attr( $panel_id ); ?>"
            class="itk-mega-panel itk-mega-panel--<?php echo esc_attr( isset( $definition['width'] ) ? $definition['width'] : 'aligned' ); ?>"
            data-itk-mega-panel="<?php echo esc_attr( $key ); ?>"
            style="--itk-mega-columns:<?php echo esc_attr( (string) $columns ); ?>"
        >
            <div class="itk-mega-panel__inner">
                <?php foreach ( $definition['blocks'] as $block ) : ?>
                    <?php $this->render_block( $block, $item ); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return $item_output . ob_get_clean();
    }

    /**
     * Render one normalized rich-content block.
     *
     * @param array<string,mixed> $block Normalized block.
     * @param object              $item  Parent menu item.
     * @return void
     */
    private function render_block( array $block, $item ) {
        $type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : '';
        $span = max( 1, min( 6, isset( $block['span'] ) ? absint( $block['span'] ) : 1 ) );

        if ( ! $type ) {
            return;
        }

        echo '<section class="itk-mega-block itk-mega-block--' . esc_attr( $type ) . '" style="--itk-mega-span:' . esc_attr( (string) $span ) . '">';

        if ( ! empty( $block['title'] ) ) {
            echo '<h3 class="itk-mega-block__title">' . esc_html( $block['title'] ) . '</h3>';
        }

        switch ( $type ) {
            case 'menu':
                $this->render_menu_block( ! empty( $item->ID ) ? absint( $item->ID ) : 0 );
                break;
            case 'categories':
                $this->render_categories_block( $block );
                break;
            case 'products':
                $this->render_products_block( $block );
                break;
            case 'image':
                $this->render_image_block( $block );
                break;
            case 'banner':
                $this->render_banner_block( $block );
                break;
            case 'elementor':
                $this->render_elementor_block( $block );
                break;
        }

        echo '</section>';
    }

    /**
     * Reuse direct/second-level WordPress menu children in a rich panel.
     *
     * @param int $parent_id Parent menu item ID.
     * @return void
     */
    private function render_menu_block( $parent_id ) {
        $children = isset( $this->items_by_parent[ $parent_id ] ) ? $this->items_by_parent[ $parent_id ] : array();
        if ( ! $children ) {
            return;
        }

        echo '<ul class="itk-mega-links">';
        foreach ( $children as $child ) {
            $url   = isset( $child->url ) ? $child->url : '';
            $title = isset( $child->title ) ? $child->title : '';
            $id    = ! empty( $child->ID ) ? absint( $child->ID ) : 0;

            echo '<li>';
            echo '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';

            $grandchildren = isset( $this->items_by_parent[ $id ] ) ? $this->items_by_parent[ $id ] : array();
            if ( $grandchildren ) {
                echo '<ul>';
                foreach ( $grandchildren as $grandchild ) {
                    echo '<li><a href="' . esc_url( isset( $grandchild->url ) ? $grandchild->url : '' ) . '">' . esc_html( isset( $grandchild->title ) ? $grandchild->title : '' ) . '</a></li>';
                }
                echo '</ul>';
            }

            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Render WooCommerce product categories by portable slug or top-level query.
     *
     * @param array<string,mixed> $block Block.
     * @return void
     */
    private function render_categories_block( array $block ) {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return;
        }

        $limit = max( 1, min( 12, isset( $block['limit'] ) ? absint( $block['limit'] ) : 6 ) );
        $args  = array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'number'     => $limit,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );

        if ( ! empty( $block['slugs'] ) && is_array( $block['slugs'] ) ) {
            $args['slug']   = array_slice( $block['slugs'], 0, $limit );
            $args['number'] = 0;
        } else {
            $args['parent'] = 0;
        }

        $terms = get_terms( $args );
        if ( is_wp_error( $terms ) || ! $terms ) {
            return;
        }

        echo '<div class="itk-mega-categories">';
        foreach ( array_slice( $terms, 0, $limit ) as $term ) {
            $link = get_term_link( $term );
            if ( is_wp_error( $link ) ) {
                continue;
            }

            echo '<a class="itk-mega-category" href="' . esc_url( $link ) . '">';
            if ( ! empty( $block['show_images'] ) ) {
                $thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
                if ( $thumbnail_id ) {
                    echo wp_kses_post( wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, array( 'class' => 'itk-mega-category__image' ) ) );
                }
            }
            echo '<span>' . esc_html( $term->name ) . '</span>';
            echo '</a>';
        }
        echo '</div>';
    }

    /**
     * Render a bounded WooCommerce product query.
     *
     * @param array<string,mixed> $block Block.
     * @return void
     */
    private function render_products_block( array $block ) {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return;
        }

        $limit  = max( 1, min( 8, isset( $block['limit'] ) ? absint( $block['limit'] ) : 4 ) );
        $source = isset( $block['source'] ) ? sanitize_key( $block['source'] ) : 'latest';
        $value  = isset( $block['value'] ) ? sanitize_text_field( $block['value'] ) : '';
        $args   = array(
            'status'  => 'publish',
            'limit'   => $limit,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        );

        if ( 'featured' === $source ) {
            $args['featured'] = true;
        } elseif ( 'on_sale' === $source && function_exists( 'wc_get_product_ids_on_sale' ) ) {
            $args['include'] = array_slice( array_map( 'absint', wc_get_product_ids_on_sale() ), 0, 50 );
        } elseif ( 'category' === $source && $value ) {
            $args['category'] = array( sanitize_title( $value ) );
        } elseif ( 'ids' === $source && $value ) {
            $ids = array_values( array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', $value ) ) ) );
            if ( $ids ) {
                $args['include'] = array_slice( $ids, 0, $limit );
                unset( $args['orderby'], $args['order'] );
            }
        }

        $products = wc_get_products( $args );
        if ( ! $products ) {
            return;
        }

        echo '<div class="itk-mega-products">';
        foreach ( $products as $product ) {
            if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
                continue;
            }

            $product_id = $product->get_id();
            echo '<a class="itk-mega-product" href="' . esc_url( get_permalink( $product_id ) ) . '">';
            if ( method_exists( $product, 'get_image' ) ) {
                echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'itk-mega-product__image' ) ) );
            }
            echo '<span class="itk-mega-product__name">' . esc_html( method_exists( $product, 'get_name' ) ? $product->get_name() : get_the_title( $product_id ) ) . '</span>';
            if ( method_exists( $product, 'get_price_html' ) ) {
                echo '<span class="itk-mega-product__price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
            }
            echo '</a>';
        }
        echo '</div>';
    }

    /**
     * @param array<string,mixed> $block Block.
     * @return void
     */
    private function render_image_block( array $block ) {
        if ( empty( $block['image_url'] ) ) {
            return;
        }

        $image = '<img src="' . esc_url( $block['image_url'] ) . '" alt="' . esc_attr( isset( $block['alt'] ) ? $block['alt'] : '' ) . '" loading="lazy">';
        if ( ! empty( $block['link_url'] ) ) {
            echo '<a class="itk-mega-image" href="' . esc_url( $block['link_url'] ) . '">' . $image . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        echo '<div class="itk-mega-image">' . $image . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @param array<string,mixed> $block Block.
     * @return void
     */
    private function render_banner_block( array $block ) {
        $style = ! empty( $block['image_url'] )
            ? ' style="background-image:linear-gradient(90deg,rgba(17,24,39,.82),rgba(17,24,39,.28)),url(' . esc_url( $block['image_url'] ) . ')"'
            : '';

        echo '<div class="itk-mega-banner"' . $style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        if ( ! empty( $block['eyebrow'] ) ) {
            echo '<span class="itk-mega-banner__eyebrow">' . esc_html( $block['eyebrow'] ) . '</span>';
        }
        if ( ! empty( $block['title'] ) ) {
            echo '<strong class="itk-mega-banner__title">' . esc_html( $block['title'] ) . '</strong>';
        }
        if ( ! empty( $block['text'] ) ) {
            echo '<p>' . esc_html( $block['text'] ) . '</p>';
        }
        if ( ! empty( $block['link_url'] ) ) {
            $label = ! empty( $block['link_label'] ) ? $block['link_label'] : __( 'Learn more', 'itk-commerce-layouts' );
            echo '<a class="itk-mega-banner__link" href="' . esc_url( $block['link_url'] ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</div>';
    }

    /**
     * Render an optional Elementor saved template by local template ID.
     *
     * @param array<string,mixed> $block Block.
     * @return void
     */
    private function render_elementor_block( array $block ) {
        $template_id = isset( $block['template_id'] ) ? absint( $block['template_id'] ) : 0;
        if ( ! $template_id || ! class_exists( '\\Elementor\\Plugin' ) ) {
            return;
        }

        try {
            $plugin = \Elementor\Plugin::instance();
            if ( isset( $plugin->frontend ) && method_exists( $plugin->frontend, 'get_builder_content_for_display' ) ) {
                echo wp_kses_post( $plugin->frontend->get_builder_content_for_display( $template_id, true ) );
            }
        } catch ( \Throwable $error ) {
            // Optional integration failures must never break navigation rendering.
            return;
        }
    }
}
