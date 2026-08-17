<?php
/**
 * Dependency-light WooCommerce entity translation mapping smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );

    $GLOBALS['itk_mapper_filters'] = array();
    $GLOBALS['itk_mapper_admin']   = false;
    $GLOBALS['itk_mapper_ajax']    = false;
    $GLOBALS['itk_mapper_calls']   = array();

    function get_locale() { return 'ar'; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return (string) $value; }
    function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
        $GLOBALS['itk_mapper_filters'][ $hook ] = array( $callback, $priority, $args );
    }
    function is_admin() { return (bool) $GLOBALS['itk_mapper_admin']; }
    function wp_doing_ajax() { return (bool) $GLOBALS['itk_mapper_ajax']; }
    function wp_doing_cron() { return false; }
    function wp_installing() { return false; }

    final class ITK_Mapper_Product {
        private $id;
        public function __construct( $id ) { $this->id = (int) $id; }
        public function get_id() { return $this->id; }
    }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/WooCommerceTranslationMapper.php';

    function itk_mapper_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "WooCommerce mapping failure: {$message}\n" );
            exit( 1 );
        }
    }

    $language_schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $context = new \ITK\Commerce\Multilingual\LanguageContext(
        $language_schema->normalize(
            array(
                'default' => 'ar',
                'fallback' => 'de',
                'languages' => array(
                    array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
                    array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'enabled' => true ),
                ),
            )
        )
    );

    $translator = static function ( $key, $source, $language_code ) {
        $GLOBALS['itk_mapper_calls'][] = array( $key, $source, $language_code );
        return '[' . $language_code . ':' . $key . ']' . $source;
    };

    $mapper = new \ITK\Commerce\Multilingual\WooCommerceTranslationMapper( $translator, $context );
    $mapper->register();

    foreach ( array(
        'woocommerce_product_get_name',
        'woocommerce_product_get_description',
        'woocommerce_product_get_short_description',
        'woocommerce_product_variation_get_name',
        'get_term',
        'woocommerce_attribute_label',
    ) as $hook ) {
        itk_mapper_assert( isset( $GLOBALS['itk_mapper_filters'][ $hook ] ), "Expected public mapping hook {$hook} was not registered." );
    }

    $product = new ITK_Mapper_Product( 42 );
    $name = $mapper->product_name( 'Original Product', $product );
    itk_mapper_assert( '[ar:woocommerce.product.42.name]Original Product' === $name, 'Product name must map by existing WooCommerce product ID.' );

    $description = $mapper->product_description( '<p>Original description</p>', $product );
    itk_mapper_assert( '[ar:woocommerce.product.42.description]<p>Original description</p>' === $description, 'Product description must use a stable field key without changing product identity.' );

    $short = $mapper->product_short_description( 'Short', $product );
    itk_mapper_assert( '[ar:woocommerce.product.42.short-description]Short' === $short, 'Product short description must use a separate translation key.' );

    itk_mapper_assert( 'woocommerce.product.42.name' === $mapper->product_key( 42, 'Name' ), 'Product key helper must be deterministic.' );
    itk_mapper_assert( 'woocommerce.term.product_cat.7.name' === $mapper->term_key( 'product_cat', 7, 'name' ), 'Term key helper must retain taxonomy + term identity.' );

    $category = (object) array(
        'term_id'     => 7,
        'name'        => 'Perfume',
        'description' => 'Category text',
        'slug'        => 'perfume',
    );
    $translated_category = $mapper->term( $category, 'product_cat' );
    itk_mapper_assert( $translated_category !== $category, 'Translatable term objects must be cloned before localized display mutation.' );
    itk_mapper_assert( 'Perfume' === $category->name && 'perfume' === $category->slug, 'Original cached term identity must remain unchanged.' );
    itk_mapper_assert( '[ar:woocommerce.term.product_cat.7.name]Perfume' === $translated_category->name, 'Product category name must map through published translation key.' );
    itk_mapper_assert( '[ar:woocommerce.term.product_cat.7.description]Category text' === $translated_category->description, 'Product category description must map separately.' );
    itk_mapper_assert( 'perfume' === $translated_category->slug, 'Category slug must stay WooCommerce/WordPress-owned in this slice.' );

    $attribute_term = (object) array(
        'term_id'     => 13,
        'name'        => 'Red',
        'description' => '',
        'slug'        => 'red',
    );
    $translated_attribute_term = $mapper->term( $attribute_term, 'pa_color' );
    itk_mapper_assert( '[ar:woocommerce.term.pa_color.13.name]Red' === $translated_attribute_term->name, 'Global attribute option terms must be translatable by taxonomy + term ID.' );

    $ordinary_term = (object) array( 'term_id' => 9, 'name' => 'Blog', 'description' => '', 'slug' => 'blog' );
    itk_mapper_assert( $ordinary_term === $mapper->term( $ordinary_term, 'category' ), 'Non-WooCommerce taxonomies must not be changed.' );

    $global_label = $mapper->attribute_label( 'Color', 'pa_color', null );
    itk_mapper_assert( '[ar:woocommerce.attribute.pa_color.label]Color' === $global_label, 'Global attribute labels must translate without changing taxonomy identity.' );

    $local_label = $mapper->attribute_label( 'Bottle size', 'Bottle Size', $product );
    itk_mapper_assert( '[ar:woocommerce.product.42.attribute.bottle-size.label]Bottle size' === $local_label, 'Product-local attribute labels must be scoped to the original product ID.' );

    $GLOBALS['itk_mapper_admin'] = true;
    itk_mapper_assert( 'Original Product' === $mapper->product_name( 'Original Product', $product ), 'Admin product values must not be overwritten by storefront translation filters.' );
    $GLOBALS['itk_mapper_admin'] = false;

    $GLOBALS['itk_mapper_ajax'] = true;
    itk_mapper_assert( 'Original Product' === $mapper->product_name( 'Original Product', $product ), 'AJAX must wait for explicit session-language persistence instead of guessing a language.' );
    $GLOBALS['itk_mapper_ajax'] = false;

    $before = count( $GLOBALS['itk_mapper_calls'] );
    $invalid = new ITK_Mapper_Product( 0 );
    itk_mapper_assert( 'Draft' === $mapper->product_name( 'Draft', $invalid ), 'Unsaved products without a WooCommerce ID must not receive persistent translation keys.' );
    itk_mapper_assert( $before === count( $GLOBALS['itk_mapper_calls'] ), 'Invalid product identity must not query translation storage.' );

    echo "WooCommerce entity mapping smoke test passed.\n";
}
