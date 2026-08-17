<?php
/**
 * Dependency-light translated WooCommerce permalink routing smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );
    define( 'ARRAY_A', 'ARRAY_A' );
    define( 'OBJECT', 'OBJECT' );

    final class WP_Error {
        private $code;
        private $message;
        public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }

    $GLOBALS['itk_route_admin'] = false;
    $GLOBALS['itk_route_queried_object'] = null;
    $GLOBALS['itk_route_posts'] = array(
        42 => (object) array( 'ID' => 42, 'post_type' => 'product', 'post_name' => 'oud-royal' ),
        43 => (object) array( 'ID' => 43, 'post_type' => 'product', 'post_name' => 'another-product' ),
    );
    $GLOBALS['itk_route_terms'] = array(
        'product_cat' => array(
            7 => (object) array( 'term_id' => 7, 'taxonomy' => 'product_cat', 'slug' => 'parfum', 'parent' => 0 ),
            8 => (object) array( 'term_id' => 8, 'taxonomy' => 'product_cat', 'slug' => 'orientalisch', 'parent' => 7 ),
        ),
        'pa_color' => array(
            13 => (object) array( 'term_id' => 13, 'taxonomy' => 'pa_color', 'slug' => 'gold', 'parent' => 0 ),
        ),
    );

    function is_wp_error( $value ) { return $value instanceof WP_Error; }
    function get_locale() { return 'de_DE'; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function sanitize_title( $value ) {
        $value = strtolower( trim( (string) $value ) );
        $value = preg_replace( '/\s+/', '-', $value );
        return trim( (string) preg_replace( '/[^a-z0-9%_-]+/', '-', $value ), '-' );
    }
    function esc_attr( $value ) { return (string) $value; }
    function add_filter() {}
    function add_action() {}
    function do_action() {}
    function apply_filters( $hook, $value ) { unset( $hook ); return $value; }
    function is_admin() { return (bool) $GLOBALS['itk_route_admin']; }
    function wp_doing_ajax() { return false; }
    function wp_doing_cron() { return false; }
    function wp_installing() { return false; }
    function home_url( $path = '/' ) { return 'https://shop.test' . ( '/' === substr( (string) $path, 0, 1 ) ? (string) $path : '/' . (string) $path ); }
    function get_post( $id ) { return isset( $GLOBALS['itk_route_posts'][ (int) $id ] ) ? $GLOBALS['itk_route_posts'][ (int) $id ] : null; }
    function get_page_by_path( $slug, $output = OBJECT, $post_type = 'page' ) {
        unset( $output );
        foreach ( $GLOBALS['itk_route_posts'] as $post ) {
            if ( $post_type === $post->post_type && (string) $slug === $post->post_name ) { return $post; }
        }
        return null;
    }
    function get_permalink( $post ) {
        $post = is_object( $post ) ? $post : get_post( $post );
        return is_object( $post ) ? 'https://shop.test/product/' . $post->post_name . '/' : '';
    }
    function get_term( $id, $taxonomy = '' ) {
        return isset( $GLOBALS['itk_route_terms'][ $taxonomy ][ (int) $id ] ) ? $GLOBALS['itk_route_terms'][ $taxonomy ][ (int) $id ] : null;
    }
    function get_term_by( $field, $value, $taxonomy ) {
        if ( 'slug' !== $field || empty( $GLOBALS['itk_route_terms'][ $taxonomy ] ) ) { return false; }
        foreach ( $GLOBALS['itk_route_terms'][ $taxonomy ] as $term ) {
            if ( (string) $value === $term->slug ) { return $term; }
        }
        return false;
    }
    function get_term_link( $term ) {
        if ( ! is_object( $term ) ) { return new WP_Error( 'invalid_term', 'Invalid term' ); }
        if ( 'product_cat' === $term->taxonomy ) {
            $segments = array( $term->slug );
            $parent = isset( $term->parent ) ? (int) $term->parent : 0;
            while ( $parent > 0 ) {
                $ancestor = get_term( $parent, 'product_cat' );
                if ( ! is_object( $ancestor ) ) { break; }
                array_unshift( $segments, $ancestor->slug );
                $parent = isset( $ancestor->parent ) ? (int) $ancestor->parent : 0;
            }
            return 'https://shop.test/product-category/' . implode( '/', $segments ) . '/';
        }
        return 'https://shop.test/' . $term->taxonomy . '/' . $term->slug . '/';
    }
    function get_ancestors( $id, $taxonomy, $resource_type = '' ) {
        unset( $resource_type );
        $ancestors = array();
        $term = get_term( $id, $taxonomy );
        $parent = is_object( $term ) && isset( $term->parent ) ? (int) $term->parent : 0;
        while ( $parent > 0 ) {
            $ancestors[] = $parent;
            $term = get_term( $parent, $taxonomy );
            $parent = is_object( $term ) && isset( $term->parent ) ? (int) $term->parent : 0;
        }
        return $ancestors;
    }
    function get_the_terms( $post_id, $taxonomy ) {
        if ( 42 === (int) $post_id && 'product_cat' === $taxonomy ) {
            return array( get_term( 8, 'product_cat' ) );
        }
        return array();
    }
    function get_queried_object() { return $GLOBALS['itk_route_queried_object']; }

    final class ITK_Route_Fake_DB {
        public $prefix = 'wp_';
        public $insert_id = 0;
        public $routes = array();
        public $aliases = array();

        public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4'; }
        public function prepare( $query, ...$args ) {
            foreach ( $args as $arg ) {
                $replacement = is_int( $arg ) ? (string) $arg : "'" . str_replace( "'", "''", (string) $arg ) . "'";
                $query = preg_replace( '/%[dsf]/', $replacement, $query, 1 );
            }
            return $query;
        }
        public function query( $sql ) { unset( $sql ); return true; }
        public function insert( $table, $data, $format = null ) {
            unset( $format );
            $rows =& $this->routes;
            if ( false !== strpos( $table, 'route_aliases' ) ) { $rows =& $this->aliases; }

            foreach ( $rows as $row ) {
                if ( isset( $data['route_hash'], $row['route_hash'], $data['language_code'], $row['language_code'], $data['entity_type'], $row['entity_type'] )
                    && $data['route_hash'] === $row['route_hash']
                    && $data['language_code'] === $row['language_code']
                    && $data['entity_type'] === $row['entity_type'] ) {
                    return false;
                }
                if ( false === strpos( $table, 'route_aliases' )
                    && isset( $data['object_id'], $row['object_id'] )
                    && (int) $data['object_id'] === (int) $row['object_id']
                    && $data['language_code'] === $row['language_code']
                    && $data['entity_type'] === $row['entity_type']
                    && $data['taxonomy'] === $row['taxonomy'] ) {
                    return false;
                }
            }

            $id = count( $rows ) + 1;
            while ( isset( $rows[ $id ] ) ) { ++$id; }
            $data['id'] = $id;
            $rows[ $id ] = $data;
            $this->insert_id = $id;
            return 1;
        }
        public function update( $table, $data, $where, $format = null, $where_format = null ) {
            unset( $format, $where_format );
            $rows =& $this->routes;
            if ( false !== strpos( $table, 'route_aliases' ) ) { $rows =& $this->aliases; }
            $count = 0;
            foreach ( $rows as $id => $row ) {
                if ( ! $this->matches( $row, $where ) ) { continue; }
                $rows[ $id ] = array_merge( $row, $data );
                ++$count;
            }
            return $count;
        }
        public function delete( $table, $where, $format = null ) {
            unset( $format );
            $rows =& $this->routes;
            if ( false !== strpos( $table, 'route_aliases' ) ) { $rows =& $this->aliases; }
            foreach ( $rows as $id => $row ) {
                if ( $this->matches( $row, $where ) ) { unset( $rows[ $id ] ); return 1; }
            }
            return 0;
        }
        public function get_row( $sql, $output = ARRAY_A ) {
            unset( $output );
            $rows = false !== strpos( $sql, 'route_aliases' ) ? $this->aliases : $this->routes;
            $where = array();
            if ( preg_match_all( "/([a-z_]+) = ('([^']*)'|([0-9]+))/", $sql, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $where[ $match[1] ] = '' !== $match[3] ? str_replace( "''", "'", $match[3] ) : (int) $match[4];
                }
            }
            foreach ( $rows as $row ) {
                if ( $this->matches( $row, $where ) ) { return $row; }
            }
            return null;
        }
        private function matches( $row, $where ) {
            foreach ( $where as $key => $value ) {
                if ( ! array_key_exists( $key, $row ) || (string) $row[ $key ] !== (string) $value ) { return false; }
            }
            return true;
        }
    }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageRouter.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationInstaller.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationRepository.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationWorkflow.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslatedRouteStoreInterface.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslatedRouteRepository.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslatedPermalinkService.php';

    function itk_route_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Translated permalink failure: {$message}\n" );
            exit( 1 );
        }
    }

    $db = new ITK_Route_Fake_DB();
    $tables = \ITK\Commerce\Multilingual\TranslationInstaller::table_names( $db );
    $schema_sql = implode( "\n", \ITK\Commerce\Multilingual\TranslationInstaller::schema_sql( $db ) );
    itk_route_assert( '2' === \ITK\Commerce\Multilingual\TranslationInstaller::DB_VERSION, 'Translated route storage must bump the module DB schema version.' );
    itk_route_assert( 'wp_itk_commerce_translation_routes' === $tables['routes'], 'Current translated routes need a prefixed module-owned table.' );
    itk_route_assert( 'wp_itk_commerce_translation_route_aliases' === $tables['route_aliases'], 'Historical translated slugs need a prefixed alias table.' );
    itk_route_assert( false !== strpos( $schema_sql, 'UNIQUE KEY route_identity' ) && false !== strpos( $schema_sql, 'UNIQUE KEY alias_identity' ), 'Route and alias collisions must be protected by indexed unique identities.' );

    $language_schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $config = $language_schema->normalize( array(
        'default' => 'de',
        'fallback' => 'de',
        'languages' => array(
            array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'enabled' => true ),
            array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
            array( 'code' => 'en', 'locale' => 'en_US', 'label' => 'English', 'enabled' => true ),
        ),
    ) );
    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );
    $router  = new \ITK\Commerce\Multilingual\LanguageRouter( $context );
    $translation_schema = new \ITK\Commerce\Multilingual\TranslationSchema();
    $translations = new \ITK\Commerce\Multilingual\TranslationRepository( $translation_schema, $db );
    $routes = new \ITK\Commerce\Multilingual\TranslatedRouteRepository( $db );
    $service = new \ITK\Commerce\Multilingual\TranslatedPermalinkService( $context, $router, $translations, $routes );

    itk_route_assert( array( 'entity_type' => 'product', 'object_id' => 42, 'taxonomy' => '' ) === $service->parse_translation_key( 'woocommerce.product.42.slug' ), 'Product slug translation keys must resolve to the existing product ID.' );
    itk_route_assert( array( 'entity_type' => 'term', 'object_id' => 7, 'taxonomy' => 'product_cat' ) === $service->parse_translation_key( 'woocommerce.term.product_cat.7.slug' ), 'Term slug translation keys must preserve taxonomy + term ID.' );
    itk_route_assert( null === $service->parse_translation_key( 'woocommerce.product.42.name' ), 'Non-slug translations must not become URL routes.' );

    $de_product = $routes->publish( 'de', 'product', 42, '', 'oud-royal', 'oud-koenig', 'woocommerce.product.42.slug' );
    $ar_product = $routes->publish( 'ar', 'product', 42, '', 'oud-royal', 'oud-maliki', 'woocommerce.product.42.slug' );
    $de_cat = $routes->publish( 'de', 'term', 7, 'product_cat', 'parfum', 'duefte', 'woocommerce.term.product_cat.7.slug' );
    $de_child = $routes->publish( 'de', 'term', 8, 'product_cat', 'orientalisch', 'oriental', 'woocommerce.term.product_cat.8.slug' );
    $de_attribute = $routes->publish( 'de', 'term', 13, 'pa_color', 'gold', 'golden', 'woocommerce.term.pa_color.13.slug' );
    itk_route_assert( is_array( $de_product ) && is_array( $ar_product ) && is_array( $de_cat ) && is_array( $de_child ) && is_array( $de_attribute ), 'Current language routes must publish independently for the same technical entities.' );

    $collision = $routes->validate_slug( 'de', 'product', 43, '', 'oud-koenig' );
    itk_route_assert( is_wp_error( $collision ) && 'translated_slug_conflict' === $collision->get_error_code(), 'One language route slug cannot resolve to two different products.' );

    $updated = $routes->publish( 'de', 'product', 42, '', 'oud-royal', 'oud-premium', 'woocommerce.product.42.slug' );
    $old = $routes->resolve( 'de', 'product', '', 'oud-koenig' );
    itk_route_assert( is_array( $updated ) && 'oud-premium' === $updated['translated_slug'], 'Publishing a replacement slug must change only the translated route.' );
    itk_route_assert( is_array( $old ) && ! empty( $old['alias'] ) && 'oud-premium' === $old['current_slug'], 'Previous translated slugs must resolve as historical aliases to the same entity.' );
    itk_route_assert( 'oud-royal' === $GLOBALS['itk_route_posts'][42]->post_name, 'Translated route changes must never mutate the technical WooCommerce product slug.' );

    $context->select( 'de' );
    $product_url = $service->localized_product_permalink( $GLOBALS['itk_route_posts'][42], 'de', 'https://shop.test/parfum/orientalisch/oud-royal/' );
    itk_route_assert( 'https://shop.test/de/duefte/oriental/oud-premium/' === $product_url, 'Product links must use target-language product and category slugs while retaining the same product ID.' );

    $category_url = $service->localized_term_permalink( get_term( 8, 'product_cat' ), 'de', 'https://shop.test/product-category/parfum/orientalisch/' );
    itk_route_assert( 'https://shop.test/de/product-category/duefte/oriental/' === $category_url, 'Hierarchical category links must translate every indexed path segment.' );

    $attribute_url = $service->localized_term_permalink( get_term( 13, 'pa_color' ), 'de', 'https://shop.test/pa_color/gold/' );
    itk_route_assert( 'https://shop.test/de/pa_color/golden/' === $attribute_url, 'Global attribute term links must support language-specific slugs.' );

    $resolved_product = $service->filter_request_query_vars( array( 'product' => 'oud-premium' ) );
    itk_route_assert( 'oud-royal' === $resolved_product['product'], 'Incoming translated product slugs must resolve back to the existing technical WooCommerce slug.' );

    $resolved_category = $service->filter_request_query_vars( array( 'product_cat' => 'duefte/oriental' ) );
    itk_route_assert( 'parfum/orientalisch' === $resolved_category['product_cat'], 'Incoming translated category paths must resolve back to technical term slugs.' );

    $resolved_attribute = $service->filter_request_query_vars( array( 'pa_color' => 'golden' ) );
    itk_route_assert( 'gold' === $resolved_attribute['pa_color'], 'Incoming translated attribute slugs must resolve to the existing term slug.' );

    $GLOBALS['itk_route_queried_object'] = $GLOBALS['itk_route_posts'][42];
    $target_ar = $service->filter_language_url( 'https://shop.test/ar/product/oud-premium/', 'ar', '' );
    itk_route_assert( 'https://shop.test/ar/product/oud-maliki/' === $target_ar, 'Language switching must use the target language entity slug instead of reusing the current-language path.' );

    $service->filter_request_query_vars( array( 'product' => 'oud-koenig' ) );
    $alias_redirect = $service->filter_canonical_redirect( 'https://shop.test/product/oud-royal/', 'https://shop.test/de/product/oud-koenig/' );
    itk_route_assert( 'https://shop.test/de/product/oud-premium/' === $alias_redirect, 'Historical translated slugs must redirect to the current translated permalink, not the technical source URL.' );

    $GLOBALS['itk_route_admin'] = true;
    $admin_query = $service->filter_request_query_vars( array( 'product' => 'oud-premium' ) );
    itk_route_assert( 'oud-premium' === $admin_query['product'], 'Admin queries must not be silently rewritten through storefront translated routes.' );
    $GLOBALS['itk_route_admin'] = false;

    $en_url = $service->localized_product_permalink( $GLOBALS['itk_route_posts'][42], 'en', 'https://shop.test/product/oud-royal/' );
    itk_route_assert( 'https://shop.test/en/product/oud-royal/' === $en_url, 'A target language without its own translated slug must fall back to the technical source slug, not another language slug.' );

    echo "Translated permalink smoke test passed.\n";
}
