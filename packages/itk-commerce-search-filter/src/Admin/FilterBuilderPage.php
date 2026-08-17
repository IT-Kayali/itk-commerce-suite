<?php
/**
 * Profile-driven Search & Filter builder foundation.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter\Admin;

use ITK\Commerce\Core\Core;
use ITK\Commerce\SearchFilter\FilterSchema;

defined( 'ABSPATH' ) || exit;

final class FilterBuilderPage {
    const PAGE_SLUG = 'itk-commerce-search-filter';

    /** @var FilterSchema */
    private $schema;

    /** @var string */
    private $page_hook = '';

    /** @param FilterSchema $schema Schema service. */
    public function __construct( FilterSchema $schema ) {
        $this->schema = $schema;
    }

    /** @return void */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_itk_commerce_save_search_filters', array( $this, 'save' ) );
    }

    /** @return void */
    public function add_menu() {
        $this->page_hook = add_submenu_page(
            'woocommerce',
            __( 'Commerce Search & Filter', 'itk-commerce-search-filter' ),
            __( 'Search & Filter', 'itk-commerce-search-filter' ),
            'itk_manage_design',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /** @param string $hook_suffix Admin hook. @return void */
    public function enqueue_assets( $hook_suffix ) {
        if ( $this->page_hook !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'itk-commerce-search-filter-builder',
            plugins_url( 'assets/admin/filter-builder.css', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION
        );
        wp_enqueue_script(
            'itk-commerce-search-filter-builder',
            plugins_url( 'assets/admin/filter-builder.js', \ITK\Commerce\SearchFilter\FILE ),
            array(),
            \ITK\Commerce\SearchFilter\VERSION,
            true
        );
    }

    /** @return void */
    public function render() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage catalog filters.', 'itk-commerce-search-filter' ) );
        }

        $profile     = $this->editor_profile();
        $definitions = $this->definitions( $profile );
        $updated     = isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap itk-filter-builder">
            <div class="itk-filter-builder__head">
                <div>
                    <span><?php esc_html_e( 'IT-Kayali Commerce Suite', 'itk-commerce-search-filter' ); ?></span>
                    <h1><?php esc_html_e( 'Search & Filter Builder', 'itk-commerce-search-filter' ); ?></h1>
                    <p><?php esc_html_e( 'Define portable WooCommerce catalog filters. The schema validates IDs, public URL keys, filter types and product taxonomies before anything is saved.', 'itk-commerce-search-filter' ); ?></p>
                </div>
                <div class="itk-filter-builder__profile">
                    <small><?php esc_html_e( 'Active profile', 'itk-commerce-search-filter' ); ?></small>
                    <strong><?php echo esc_html( $profile['name'] ); ?></strong>
                    <code><?php echo esc_html( $profile['profile_id'] ); ?></code>
                </div>
            </div>

            <?php if ( $updated ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Search & Filter configuration saved.', 'itk-commerce-search-filter' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-itk-filter-builder>
                <input type="hidden" name="action" value="itk_commerce_save_search_filters">
                <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile['profile_id'] ); ?>">
                <?php wp_nonce_field( 'itk_commerce_save_search_filters' ); ?>

                <div class="itk-filter-builder__toolbar">
                    <div>
                        <strong><?php esc_html_e( 'Catalog filters', 'itk-commerce-search-filter' ); ?></strong>
                        <span><?php esc_html_e( 'Multiple taxonomy/attribute filters are allowed. Price, availability, sale and rating are single-instance filter types.', 'itk-commerce-search-filter' ); ?></span>
                    </div>
                    <div class="itk-filter-builder__add">
                        <button type="button" class="button" data-itk-add-filter="taxonomy"><?php esc_html_e( '+ Taxonomy / Attribute', 'itk-commerce-search-filter' ); ?></button>
                        <button type="button" class="button" data-itk-add-filter="price"><?php esc_html_e( '+ Price', 'itk-commerce-search-filter' ); ?></button>
                        <button type="button" class="button" data-itk-add-filter="stock"><?php esc_html_e( '+ Availability', 'itk-commerce-search-filter' ); ?></button>
                        <button type="button" class="button" data-itk-add-filter="sale"><?php esc_html_e( '+ Sale', 'itk-commerce-search-filter' ); ?></button>
                        <button type="button" class="button" data-itk-add-filter="rating"><?php esc_html_e( '+ Rating', 'itk-commerce-search-filter' ); ?></button>
                    </div>
                </div>

                <div class="itk-filter-builder__rows" data-itk-filter-rows>
                    <?php foreach ( $definitions as $index => $definition ) : ?>
                        <?php $this->render_row( $definition, (string) $index ); ?>
                    <?php endforeach; ?>
                </div>

                <template data-itk-filter-template>
                    <?php $this->render_row( $this->blank_definition(), '__INDEX__' ); ?>
                </template>

                <div class="itk-filter-builder__savebar">
                    <p><?php esc_html_e( 'Changes affect the active profile only. Existing customer profile settings outside this module are preserved.', 'itk-commerce-search-filter' ); ?></p>
                    <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save filters', 'itk-commerce-search-filter' ); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    /** @return void */
    public function save() {
        if ( ! current_user_can( 'itk_manage_design' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage catalog filters.', 'itk-commerce-search-filter' ) );
        }

        check_admin_referer( 'itk_commerce_save_search_filters' );

        $core       = Core::instance();
        $profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
        if ( ! $profile_id ) {
            $profile_id = $core->settings()->active_profile_id();
        }
        if ( ! $profile_id ) {
            wp_safe_redirect( add_query_arg( 'itk_error', 'profile', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
            exit;
        }

        $profile = $core->profiles()->get( $profile_id );
        if ( ! is_array( $profile ) ) {
            wp_safe_redirect( add_query_arg( 'itk_error', 'profile', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
            exit;
        }

        $raw = isset( $_POST['definitions'] ) && is_array( $_POST['definitions'] ) ? wp_unslash( $_POST['definitions'] ) : array();
        $definitions = $this->schema->normalize( $raw );

        if ( empty( $profile['modules'] ) || ! is_array( $profile['modules'] ) ) {
            $profile['modules'] = array();
        }
        if ( empty( $profile['modules']['enabled'] ) || ! is_array( $profile['modules']['enabled'] ) ) {
            $profile['modules']['enabled'] = array();
        }
        if ( empty( $profile['modules']['configuration'] ) || ! is_array( $profile['modules']['configuration'] ) ) {
            $profile['modules']['configuration'] = array();
        }
        if ( empty( $profile['modules']['configuration'][ \ITK\Commerce\SearchFilter\MODULE_ID ] ) || ! is_array( $profile['modules']['configuration'][ \ITK\Commerce\SearchFilter\MODULE_ID ] ) ) {
            $profile['modules']['configuration'][ \ITK\Commerce\SearchFilter\MODULE_ID ] = array();
        }

        $profile['modules']['configuration'][ \ITK\Commerce\SearchFilter\MODULE_ID ]['filters'] = array(
            'schema_version' => \ITK\Commerce\SearchFilter\SCHEMA_VERSION,
            'definitions'    => $definitions,
        );

        if ( ! in_array( \ITK\Commerce\SearchFilter\MODULE_ID, $profile['modules']['enabled'], true ) ) {
            $profile['modules']['enabled'][] = \ITK\Commerce\SearchFilter\MODULE_ID;
        }
        $profile['modules']['enabled'] = array_values( array_unique( array_map( 'sanitize_key', $profile['modules']['enabled'] ) ) );

        $core->profiles()->save( $profile );

        wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
        exit;
    }

    /**
     * @param array<string,mixed> $definition Definition.
     * @param string              $index Row index.
     * @return void
     */
    private function render_row( array $definition, $index ) {
        $type       = isset( $definition['type'] ) ? $definition['type'] : 'taxonomy';
        $is_tax     = 'taxonomy' === $type;
        $display    = isset( $definition['display'] ) ? $definition['display'] : 'checkbox';
        $query_key  = isset( $definition['query_key'] ) ? $definition['query_key'] : '';
        ?>
        <section class="itk-filter-row" data-itk-filter-row data-filter-type="<?php echo esc_attr( $type ); ?>">
            <div class="itk-filter-row__head">
                <span class="itk-filter-row__handle" aria-hidden="true">⋮⋮</span>
                <strong data-itk-filter-row-title><?php echo esc_html( isset( $definition['label'] ) && $definition['label'] ? $definition['label'] : __( 'New filter', 'itk-commerce-search-filter' ) ); ?></strong>
                <div class="itk-filter-row__actions">
                    <button type="button" class="button-link" data-itk-move-filter="up" aria-label="<?php esc_attr_e( 'Move filter up', 'itk-commerce-search-filter' ); ?>">↑</button>
                    <button type="button" class="button-link" data-itk-move-filter="down" aria-label="<?php esc_attr_e( 'Move filter down', 'itk-commerce-search-filter' ); ?>">↓</button>
                    <button type="button" class="button-link-delete" data-itk-remove-filter><?php esc_html_e( 'Remove', 'itk-commerce-search-filter' ); ?></button>
                </div>
            </div>

            <div class="itk-filter-row__fields">
                <label><span><?php esc_html_e( 'Label', 'itk-commerce-search-filter' ); ?></span><input type="text" name="definitions[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( isset( $definition['label'] ) ? $definition['label'] : '' ); ?>" data-itk-filter-label required></label>
                <label><span><?php esc_html_e( 'ID', 'itk-commerce-search-filter' ); ?></span><input type="text" name="definitions[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( isset( $definition['id'] ) ? $definition['id'] : '' ); ?>" placeholder="brand" required></label>
                <label><span><?php esc_html_e( 'Type', 'itk-commerce-search-filter' ); ?></span><select name="definitions[<?php echo esc_attr( $index ); ?>][type]" data-itk-filter-type>
                    <?php foreach ( array( 'taxonomy', 'price', 'stock', 'sale', 'rating' ) as $candidate ) : ?>
                        <option value="<?php echo esc_attr( $candidate ); ?>" <?php selected( $type, $candidate ); ?>><?php echo esc_html( ucfirst( $candidate ) ); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label><span><?php esc_html_e( 'Public URL key', 'itk-commerce-search-filter' ); ?></span><input type="text" name="definitions[<?php echo esc_attr( $index ); ?>][query_key]" value="<?php echo esc_attr( $query_key ); ?>" placeholder="filter_brand" required></label>
                <label data-itk-taxonomy-field <?php echo $is_tax ? '' : 'hidden'; ?>><span><?php esc_html_e( 'Product taxonomy / attribute', 'itk-commerce-search-filter' ); ?></span><input type="text" name="definitions[<?php echo esc_attr( $index ); ?>][taxonomy]" value="<?php echo esc_attr( isset( $definition['taxonomy'] ) ? $definition['taxonomy'] : '' ); ?>" placeholder="product_cat or pa_brand" list="itk-product-taxonomies"></label>
                <label><span><?php esc_html_e( 'Display', 'itk-commerce-search-filter' ); ?></span><select name="definitions[<?php echo esc_attr( $index ); ?>][display]" data-itk-display>
                    <?php foreach ( array( 'checkbox', 'radio', 'select', 'chips', 'range', 'toggle' ) as $candidate ) : ?>
                        <option value="<?php echo esc_attr( $candidate ); ?>" <?php selected( $display, $candidate ); ?>><?php echo esc_html( ucfirst( $candidate ) ); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label><span><?php esc_html_e( 'Order', 'itk-commerce-search-filter' ); ?></span><input type="number" min="0" max="999" name="definitions[<?php echo esc_attr( $index ); ?>][order]" value="<?php echo esc_attr( isset( $definition['order'] ) ? (string) $definition['order'] : '100' ); ?>" data-itk-filter-order></label>
                <label class="itk-filter-row__check"><input type="checkbox" name="definitions[<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( ! isset( $definition['enabled'] ) || ! empty( $definition['enabled'] ) ); ?>><span><?php esc_html_e( 'Enabled', 'itk-commerce-search-filter' ); ?></span></label>
                <label class="itk-filter-row__check" data-itk-taxonomy-option <?php echo $is_tax ? '' : 'hidden'; ?>><input type="checkbox" name="definitions[<?php echo esc_attr( $index ); ?>][multiple]" value="1" <?php checked( ! isset( $definition['multiple'] ) || ! empty( $definition['multiple'] ) ); ?>><span><?php esc_html_e( 'Multiple selection', 'itk-commerce-search-filter' ); ?></span></label>
                <label data-itk-taxonomy-option <?php echo $is_tax ? '' : 'hidden'; ?>><span><?php esc_html_e( 'Taxonomy match', 'itk-commerce-search-filter' ); ?></span><select name="definitions[<?php echo esc_attr( $index ); ?>][match]"><option value="any" <?php selected( isset( $definition['match'] ) ? $definition['match'] : 'any', 'any' ); ?>><?php esc_html_e( 'Any selected term', 'itk-commerce-search-filter' ); ?></option><option value="all" <?php selected( isset( $definition['match'] ) ? $definition['match'] : 'any', 'all' ); ?>><?php esc_html_e( 'All selected terms', 'itk-commerce-search-filter' ); ?></option></select></label>
                <label class="itk-filter-row__check"><input type="checkbox" name="definitions[<?php echo esc_attr( $index ); ?>][show_count]" value="1" <?php checked( ! empty( $definition['show_count'] ) ); ?>><span><?php esc_html_e( 'Show counts', 'itk-commerce-search-filter' ); ?></span></label>
                <label class="itk-filter-row__check"><input type="checkbox" name="definitions[<?php echo esc_attr( $index ); ?>][collapsed]" value="1" <?php checked( ! empty( $definition['collapsed'] ) ); ?>><span><?php esc_html_e( 'Collapsed by default', 'itk-commerce-search-filter' ); ?></span></label>
            </div>
        </section>
        <?php

        if ( '__INDEX__' !== $index ) {
            $this->render_taxonomy_datalist_once();
        }
    }

    /** @return void */
    private function render_taxonomy_datalist_once() {
        static $rendered = false;
        if ( $rendered ) {
            return;
        }
        $rendered = true;
        echo '<datalist id="itk-product-taxonomies">';
        foreach ( $this->available_taxonomies() as $taxonomy => $label ) {
            echo '<option value="' . esc_attr( $taxonomy ) . '">' . esc_html( $label ) . '</option>';
        }
        echo '</datalist>';
    }

    /** @return array<string,string> */
    private function available_taxonomies() {
        $items = array(
            'product_cat' => __( 'Product categories', 'itk-commerce-search-filter' ),
            'product_tag' => __( 'Product tags', 'itk-commerce-search-filter' ),
        );

        if ( function_exists( 'wc_get_attribute_taxonomies' ) && function_exists( 'wc_attribute_taxonomy_name' ) ) {
            foreach ( wc_get_attribute_taxonomies() as $attribute ) {
                if ( empty( $attribute->attribute_name ) ) {
                    continue;
                }
                $taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
                $label    = ! empty( $attribute->attribute_label ) ? $attribute->attribute_label : $attribute->attribute_name;
                $items[ $taxonomy ] = sprintf( __( 'Attribute: %s', 'itk-commerce-search-filter' ), $label );
            }
        }

        if ( taxonomy_exists( 'product_brand' ) ) {
            $items['product_brand'] = __( 'Product brands', 'itk-commerce-search-filter' );
        }

        return $items;
    }

    /** @return array<string,mixed> */
    private function blank_definition() {
        return array(
            'id'         => '',
            'type'       => 'taxonomy',
            'label'      => '',
            'taxonomy'   => '',
            'query_key'  => '',
            'display'    => 'checkbox',
            'multiple'   => true,
            'match'      => 'any',
            'show_count' => true,
            'collapsed'  => false,
            'order'      => 100,
            'enabled'    => true,
        );
    }

    /** @return array<string,mixed> */
    private function editor_profile() {
        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        if ( ! is_array( $profile ) ) {
            return array(
                'profile_id'      => $profile_id ? $profile_id : 'site-default',
                'profile_version' => '1.0.0',
                'name'            => __( 'Site default', 'itk-commerce-search-filter' ),
                'modules'         => array(),
            );
        }

        return $profile;
    }

    /** @param array<string,mixed> $profile Profile. @return array<int,array<string,mixed>> */
    private function definitions( array $profile ) {
        $raw = isset( $profile['modules']['configuration'][ \ITK\Commerce\SearchFilter\MODULE_ID ]['filters']['definitions'] )
            ? $profile['modules']['configuration'][ \ITK\Commerce\SearchFilter\MODULE_ID ]['filters']['definitions']
            : $this->schema->defaults();

        return $this->schema->normalize( $raw );
    }
}
