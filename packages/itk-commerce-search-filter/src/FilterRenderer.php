<?php
/**
 * Progressive server-rendered catalog filter UI and active-filter chips.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

defined( 'ABSPATH' ) || exit;

final class FilterRenderer {
    /** @var array<int,array<string,mixed>> */
    private $definitions;

    /** @var UrlState */
    private $url_state;

    /**
     * @param array<int,array<string,mixed>> $definitions Normalized definitions.
     * @param UrlState                       $url_state URL-state service.
     */
    public function __construct( array $definitions, UrlState $url_state ) {
        $this->definitions = $definitions;
        $this->url_state   = $url_state;
    }

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_catalog_toolbar', array( $this, 'render_toolbar' ), 10, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 40 );
    }

    /** @return void */
    public function enqueue_assets() {
        if ( ! $this->catalog_request() ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-search-filter-ui',
            plugins_url( 'assets/css/filter-ui.css', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION
        );
    }

    /**
     * Render the filter control inside the Theme Phase 3 catalog toolbar.
     * A normal GET form provides full filtering without JavaScript.
     *
     * @param array<string,mixed> $context Theme commerce context.
     * @return void
     */
    public function render_toolbar( $context = array() ) {
        unset( $context );

        if ( ! $this->catalog_request() || empty( $this->definitions ) ) {
            return;
        }

        $state  = $this->current_state();
        $count  = $this->url_state->active_count( $state );
        $action = $this->catalog_url();
        ?>
        <div class="itk-filter-ui" data-itk-filter-ui>
            <details class="itk-filter-popover" <?php echo $count ? 'open' : ''; ?>>
                <summary class="itk-filter-trigger">
                    <span><?php esc_html_e( 'Filters', 'itk-commerce-search-filter' ); ?></span>
                    <?php if ( $count ) : ?>
                        <span class="itk-filter-trigger__count" aria-label="<?php echo esc_attr( sprintf( _n( '%d active filter', '%d active filters', $count, 'itk-commerce-search-filter' ), $count ) ); ?>"><?php echo esc_html( (string) $count ); ?></span>
                    <?php endif; ?>
                </summary>

                <div class="itk-filter-popover__panel">
                    <form class="itk-filter-form" method="get" action="<?php echo esc_url( $action ); ?>">
                        <?php $this->render_preserved_query_inputs(); ?>
                        <div class="itk-filter-groups">
                            <?php foreach ( $this->definitions as $definition ) : ?>
                                <?php if ( ! empty( $definition['enabled'] ) ) { $this->render_group( $definition, $state ); } ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="itk-filter-form__actions">
                            <button type="submit" class="button itk-filter-apply"><?php esc_html_e( 'Apply filters', 'itk-commerce-search-filter' ); ?></button>
                            <?php if ( $count ) : ?>
                                <a class="itk-filter-clear" href="<?php echo esc_url( $this->clear_all_url() ); ?>"><?php esc_html_e( 'Clear all', 'itk-commerce-search-filter' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </details>

            <?php $this->render_active_chips( $state ); ?>
        </div>
        <?php
    }

    /**
     * @param array<string,mixed> $definition Definition.
     * @param array<string,mixed> $state Current state.
     * @return void
     */
    private function render_group( array $definition, array $state ) {
        $id        = $definition['id'];
        $active    = array_key_exists( $id, $state ) ? $state[ $id ] : null;
        $collapsed = ! empty( $definition['collapsed'] ) && null === $active;
        ?>
        <fieldset class="itk-filter-group itk-filter-group--<?php echo esc_attr( sanitize_html_class( $definition['type'] ) ); ?>">
            <legend><?php echo esc_html( $definition['label'] ); ?></legend>
            <div class="itk-filter-group__content<?php echo $collapsed ? ' is-collapsed-default' : ''; ?>">
                <?php
                switch ( $definition['type'] ) {
                    case 'taxonomy':
                        $this->render_taxonomy( $definition, $active );
                        break;
                    case 'price':
                        $this->render_price( $definition, $active );
                        break;
                    case 'stock':
                        $this->render_stock( $definition, $active );
                        break;
                    case 'sale':
                        $this->render_sale( $definition, $active );
                        break;
                    case 'rating':
                        $this->render_rating( $definition, $active );
                        break;
                }
                ?>
            </div>
        </fieldset>
        <?php
    }

    /**
     * @param array<string,mixed> $definition Definition.
     * @param mixed               $active Current value.
     * @return void
     */
    private function render_taxonomy( array $definition, $active ) {
        if ( ! function_exists( 'get_terms' ) ) {
            return;
        }

        $terms = get_terms(
            array(
                'taxonomy'   => $definition['taxonomy'],
                'hide_empty' => true,
                'number'     => 100,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            echo '<p class="itk-filter-empty">' . esc_html__( 'No filter options available.', 'itk-commerce-search-filter' ) . '</p>';
            return;
        }

        $active = is_array( $active ) ? $active : array();
        $name   = $definition['query_key'] . ( ! empty( $definition['multiple'] ) ? '[]' : '' );
        $type   = ! empty( $definition['multiple'] ) ? 'checkbox' : 'radio';

        echo '<div class="itk-filter-options">';
        foreach ( $terms as $term ) {
            $slug = isset( $term->slug ) ? sanitize_title( $term->slug ) : '';
            if ( '' === $slug ) {
                continue;
            }
            ?>
            <label class="itk-filter-option">
                <input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $active, true ) ); ?>>
                <span><?php echo esc_html( isset( $term->name ) ? $term->name : $slug ); ?></span>
                <?php if ( ! empty( $definition['show_count'] ) && isset( $term->count ) ) : ?>
                    <small><?php echo esc_html( (string) absint( $term->count ) ); ?></small>
                <?php endif; ?>
            </label>
            <?php
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $definition Definition. @param mixed $active Value. @return void */
    private function render_price( array $definition, $active ) {
        $active = is_array( $active ) ? $active : array();
        $min    = isset( $active['min'] ) && null !== $active['min'] ? $active['min'] : '';
        $max    = isset( $active['max'] ) && null !== $active['max'] ? $active['max'] : '';
        $key    = $definition['query_key'];
        ?>
        <div class="itk-filter-price">
            <label><span><?php esc_html_e( 'Min', 'itk-commerce-search-filter' ); ?></span><input type="number" min="0" step="0.01" inputmode="decimal" name="<?php echo esc_attr( $key ); ?>[min]" value="<?php echo esc_attr( (string) $min ); ?>"></label>
            <span aria-hidden="true">–</span>
            <label><span><?php esc_html_e( 'Max', 'itk-commerce-search-filter' ); ?></span><input type="number" min="0" step="0.01" inputmode="decimal" name="<?php echo esc_attr( $key ); ?>[max]" value="<?php echo esc_attr( (string) $max ); ?>"></label>
        </div>
        <?php
    }

    /** @param array<string,mixed> $definition Definition. @param mixed $active Value. @return void */
    private function render_stock( array $definition, $active ) {
        $key = $definition['query_key'];
        $items = array(
            ''             => __( 'Any availability', 'itk-commerce-search-filter' ),
            'in-stock'     => __( 'In stock', 'itk-commerce-search-filter' ),
            'out-of-stock' => __( 'Out of stock', 'itk-commerce-search-filter' ),
        );

        echo '<div class="itk-filter-options">';
        foreach ( $items as $value => $label ) {
            ?>
            <label class="itk-filter-option"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( (string) $active, $value ); ?>><span><?php echo esc_html( $label ); ?></span></label>
            <?php
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $definition Definition. @param mixed $active Value. @return void */
    private function render_sale( array $definition, $active ) {
        ?>
        <label class="itk-filter-option itk-filter-option--toggle"><input type="checkbox" name="<?php echo esc_attr( $definition['query_key'] ); ?>" value="1" <?php checked( true === $active ); ?>><span><?php esc_html_e( 'Only products on sale', 'itk-commerce-search-filter' ); ?></span></label>
        <?php
    }

    /** @param array<string,mixed> $definition Definition. @param mixed $active Value. @return void */
    private function render_rating( array $definition, $active ) {
        $key = $definition['query_key'];
        echo '<div class="itk-filter-options">';
        ?>
        <label class="itk-filter-option"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="" <?php checked( empty( $active ) ); ?>><span><?php esc_html_e( 'Any rating', 'itk-commerce-search-filter' ); ?></span></label>
        <?php
        for ( $rating = 5; $rating >= 1; $rating-- ) {
            ?>
            <label class="itk-filter-option"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $rating ); ?>" <?php checked( (int) $active, $rating ); ?>><span><?php echo esc_html( sprintf( __( '%d stars & up', 'itk-commerce-search-filter' ), $rating ) ); ?></span></label>
            <?php
        }
        echo '</div>';
    }

    /**
     * @param array<string,mixed> $state State.
     * @return void
     */
    private function render_active_chips( array $state ) {
        if ( empty( $state ) ) {
            return;
        }

        $by_id = array();
        foreach ( $this->definitions as $definition ) {
            $by_id[ $definition['id'] ] = $definition;
        }

        echo '<div class="itk-active-filters" aria-label="' . esc_attr__( 'Active filters', 'itk-commerce-search-filter' ) . '">';
        foreach ( $state as $id => $value ) {
            if ( empty( $by_id[ $id ] ) ) {
                continue;
            }
            $label = $this->active_label( $by_id[ $id ], $value );
            if ( '' === $label ) {
                continue;
            }
            $next = $state;
            unset( $next[ $id ] );
            ?>
            <a class="itk-active-filter" href="<?php echo esc_url( $this->state_url( $next ) ); ?>"><span><?php echo esc_html( $label ); ?></span><span aria-hidden="true">×</span><span class="screen-reader-text"><?php esc_html_e( 'Remove filter', 'itk-commerce-search-filter' ); ?></span></a>
            <?php
        }
        echo '<a class="itk-active-filter itk-active-filter--clear" href="' . esc_url( $this->clear_all_url() ) . '">' . esc_html__( 'Clear all', 'itk-commerce-search-filter' ) . '</a>';
        echo '</div>';
    }

    /**
     * @param array<string,mixed> $definition Definition.
     * @param mixed               $value Value.
     * @return string
     */
    private function active_label( array $definition, $value ) {
        if ( 'taxonomy' === $definition['type'] && is_array( $value ) ) {
            $labels = array();
            foreach ( array_slice( $value, 0, 4 ) as $slug ) {
                $name = $slug;
                if ( function_exists( 'get_term_by' ) ) {
                    $term = get_term_by( 'slug', $slug, $definition['taxonomy'] );
                    if ( $term && ! is_wp_error( $term ) && ! empty( $term->name ) ) {
                        $name = $term->name;
                    }
                }
                $labels[] = $name;
            }
            $suffix = count( $value ) > 4 ? ' +' . ( count( $value ) - 4 ) : '';
            return $definition['label'] . ': ' . implode( ', ', $labels ) . $suffix;
        }

        if ( 'price' === $definition['type'] && is_array( $value ) ) {
            $min = isset( $value['min'] ) && null !== $value['min'] ? $value['min'] : null;
            $max = isset( $value['max'] ) && null !== $value['max'] ? $value['max'] : null;
            if ( null !== $min && null !== $max ) {
                return sprintf( __( '%1$s: %2$s–%3$s', 'itk-commerce-search-filter' ), $definition['label'], $this->price_label( $min ), $this->price_label( $max ) );
            }
            if ( null !== $min ) {
                return sprintf( __( '%1$s: from %2$s', 'itk-commerce-search-filter' ), $definition['label'], $this->price_label( $min ) );
            }
            if ( null !== $max ) {
                return sprintf( __( '%1$s: up to %2$s', 'itk-commerce-search-filter' ), $definition['label'], $this->price_label( $max ) );
            }
        }

        if ( 'stock' === $definition['type'] ) {
            return $definition['label'] . ': ' . ( 'in-stock' === $value ? __( 'In stock', 'itk-commerce-search-filter' ) : __( 'Out of stock', 'itk-commerce-search-filter' ) );
        }
        if ( 'sale' === $definition['type'] ) {
            return $definition['label'];
        }
        if ( 'rating' === $definition['type'] ) {
            return sprintf( __( '%1$s: %2$d+ stars', 'itk-commerce-search-filter' ), $definition['label'], absint( $value ) );
        }

        return '';
    }

    /** @param mixed $value Price. @return string */
    private function price_label( $value ) {
        if ( function_exists( 'wc_price' ) ) {
            return wp_strip_all_tags( wc_price( (float) $value ) );
        }
        return (string) $value;
    }

    /** @return array<string,mixed> */
    private function current_state() {
        $request = isset( $_GET ) && is_array( $_GET ) ? $_GET : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only public catalog state.
        return $this->url_state->parse( $request );
    }

    /** @return void */
    private function render_preserved_query_inputs() {
        $safe = array( 'orderby' );
        foreach ( $safe as $key ) {
            if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) . '">'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        }
    }

    /** @param array<string,mixed> $state State. @return string */
    private function state_url( array $state ) {
        $args = $this->preserved_query_args();
        $args = array_merge( $args, $this->url_state->serialize( $state ) );
        return $args ? add_query_arg( $args, $this->catalog_url() ) : $this->catalog_url();
    }

    /** @return string */
    private function clear_all_url() {
        $args = $this->preserved_query_args();
        return $args ? add_query_arg( $args, $this->catalog_url() ) : $this->catalog_url();
    }

    /** @return array<string,string> */
    private function preserved_query_args() {
        $args = array();
        if ( isset( $_GET['orderby'] ) && is_scalar( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $args['orderby'] = sanitize_key( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        return $args;
    }

    /** @return string */
    private function catalog_url() {
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() && function_exists( 'get_queried_object' ) && function_exists( 'get_term_link' ) ) {
            $term = get_queried_object();
            if ( $term ) {
                $url = get_term_link( $term );
                if ( ! is_wp_error( $url ) ) {
                    return $url;
                }
            }
        }

        if ( function_exists( 'wc_get_page_permalink' ) ) {
            $url = wc_get_page_permalink( 'shop' );
            if ( $url ) {
                return $url;
            }
        }

        return home_url( '/' );
    }

    /** @return bool */
    private function catalog_request() {
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            return true;
        }
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            return true;
        }
        return false;
    }
}
