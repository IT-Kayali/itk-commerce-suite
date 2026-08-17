<?php
/**
 * Dependency-light multilingual SEO/hreflang smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );

    $GLOBALS['itk_seo_filters']       = array();
    $GLOBALS['itk_seo_actions']       = array();
    $GLOBALS['itk_seo_singular']      = false;
    $GLOBALS['itk_seo_search']        = false;
    $GLOBALS['itk_seo_404']           = false;
    $GLOBALS['itk_seo_preview']       = false;
    $GLOBALS['itk_seo_queried_id']    = 42;

    function get_locale() { return 'de_DE'; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function esc_url( $value ) { return (string) $value; }
    function home_url( $path = '' ) {
        $base = 'https://example.test/store';
        if ( '' === (string) $path ) {
            return $base;
        }
        return $base . '/' . ltrim( (string) $path, '/' );
    }
    function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
        $GLOBALS['itk_seo_filters'][ $hook ] = array( $callback, $priority, $args );
    }
    function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
        $GLOBALS['itk_seo_actions'][ $hook ] = array( $callback, $priority, $args );
    }
    function apply_filters( $hook, $value ) { return $value; }
    function do_action() {}
    function is_admin() { return false; }
    function wp_doing_ajax() { return false; }
    function is_404() { return (bool) $GLOBALS['itk_seo_404']; }
    function is_search() { return (bool) $GLOBALS['itk_seo_search']; }
    function is_feed() { return false; }
    function is_trackback() { return false; }
    function is_preview() { return (bool) $GLOBALS['itk_seo_preview']; }
    function is_singular() { return (bool) $GLOBALS['itk_seo_singular']; }
    function get_queried_object_id() { return (int) $GLOBALS['itk_seo_queried_id']; }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageRouter.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/MultilingualSeo.php';

    function itk_seo_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Multilingual SEO failure: {$message}\n" );
            exit( 1 );
        }
    }

    $schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $config = $schema->normalize(
        array(
            'default'  => 'de',
            'fallback' => 'en',
            'languages' => array(
                array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'direction' => 'ltr', 'enabled' => true ),
                array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
                array( 'code' => 'en', 'locale' => 'en_US', 'label' => 'English', 'direction' => 'ltr', 'enabled' => true ),
                array( 'code' => 'pt-br', 'locale' => 'pt_BR', 'label' => 'Português', 'direction' => 'ltr', 'enabled' => true ),
            ),
        )
    );

    $_SERVER['REQUEST_URI'] = '/store/ar/produkt/duft/?utm_source=test&filter_note=rose#details';
    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );
    $router  = new \ITK\Commerce\Multilingual\LanguageRouter( $context );
    $router->resolve_current_request();
    $seo = new \ITK\Commerce\Multilingual\MultilingualSeo( $context, $router );
    $seo->register();

    itk_seo_assert( 'ar' === $context->code(), 'Arabic directory must establish the SEO request language.' );
    itk_seo_assert( 'de' === $context->default_code(), 'Context must expose the configured default independently from fallback.' );
    itk_seo_assert( 'en' === $context->fallback_code(), 'Fallback language must remain independent from x-default selection.' );
    itk_seo_assert( isset( $GLOBALS['itk_seo_filters']['get_canonical_url'] ), 'WordPress singular canonical filter must be registered.' );
    itk_seo_assert( isset( $GLOBALS['itk_seo_actions']['wp_head'] ), 'Frontend head renderer must be registered.' );
    itk_seo_assert( isset( $GLOBALS['itk_seo_filters']['itk_commerce_multilingual_canonical_url'] ), 'Public canonical contract must be registered.' );
    itk_seo_assert( isset( $GLOBALS['itk_seo_filters']['itk_commerce_multilingual_alternate_urls'] ), 'Public alternate-URL contract must be registered.' );

    $canonical = $seo->canonical_url();
    itk_seo_assert(
        'https://example.test/store/ar/produkt/duft/' === $canonical,
        'Canonical must retain the localized route while dropping all query/fragment state.'
    );
    itk_seo_assert( false === strpos( $canonical, 'utm_' ) && false === strpos( $canonical, 'filter_note' ), 'Canonical must never preserve tracking/filter query variants.' );

    $alternates = $seo->alternate_urls();
    itk_seo_assert( 5 === count( $alternates ), 'Four enabled languages plus x-default must be emitted.' );

    $by_hreflang = array();
    foreach ( $alternates as $item ) {
        $by_hreflang[ $item['hreflang'] ] = $item;
    }

    itk_seo_assert( isset( $by_hreflang['de'], $by_hreflang['ar'], $by_hreflang['en'], $by_hreflang['pt-BR'], $by_hreflang['x-default'] ), 'All language alternates and x-default must be present with normalized hreflang codes.' );
    itk_seo_assert( 'https://example.test/store/de/produkt/duft/' === $by_hreflang['x-default']['url'], 'x-default must point to the configured default language, not fallback.' );
    itk_seo_assert( 'https://example.test/store/pt-br/produkt/duft/' === $by_hreflang['pt-BR']['url'], 'Regional hreflang label may use pt-BR while the public directory keeps normalized pt-br.' );

    ob_start();
    $seo->render_head_links();
    $head = ob_get_clean();
    itk_seo_assert( 1 === substr_count( $head, 'rel="canonical"' ), 'Non-singular indexable views must receive one Multilingual canonical.' );
    itk_seo_assert( 5 === substr_count( $head, 'rel="alternate"' ), 'Head must render one alternate per enabled language plus x-default.' );
    itk_seo_assert( false === strpos( $head, 'utm_source' ) && false === strpos( $head, 'filter_note' ), 'Head SEO links must not contain current query state.' );

    $GLOBALS['itk_seo_singular'] = true;
    $post = (object) array( 'ID' => 42 );
    $singular = $seo->filter_singular_canonical( 'https://example.test/store/produkt/duft/', $post );
    itk_seo_assert( 'https://example.test/store/ar/produkt/duft/' === $singular, 'Core singular canonical must be localized through get_canonical_url.' );

    $other_post = (object) array( 'ID' => 99 );
    itk_seo_assert( 'https://example.test/original' === $seo->filter_singular_canonical( 'https://example.test/original', $other_post ), 'Canonical filter must not rewrite URLs requested for another post object.' );

    ob_start();
    $seo->render_head_links();
    $singular_head = ob_get_clean();
    itk_seo_assert( 0 === substr_count( $singular_head, 'rel="canonical"' ), 'Singular head renderer must leave canonical output to WordPress Core.' );
    itk_seo_assert( 5 === substr_count( $singular_head, 'rel="alternate"' ), 'Singular pages must still receive hreflang alternates.' );

    $GLOBALS['itk_seo_singular'] = false;
    $context->select( 'de' );
    $_SERVER['REQUEST_URI'] = '/store/produkt/duft/?utm_campaign=x';
    $router->resolve_current_request();
    itk_seo_assert(
        'https://example.test/store/de/produkt/duft/' === $seo->canonical_url(),
        'Unprefixed default-language duplicates must canonicalize into the configured language directory.'
    );

    $GLOBALS['itk_seo_search'] = true;
    itk_seo_assert( '' === $seo->canonical_url() && array() === $seo->alternate_urls(), 'Search results must not receive Multilingual canonical/hreflang links.' );
    ob_start();
    $seo->render_head_links();
    itk_seo_assert( '' === ob_get_clean(), 'Search result head must remain untouched.' );
    $GLOBALS['itk_seo_search'] = false;

    $GLOBALS['itk_seo_404'] = true;
    itk_seo_assert( ! $seo->is_indexable_request(), '404 views must be excluded from language SEO links.' );
    $GLOBALS['itk_seo_404'] = false;

    $GLOBALS['itk_seo_preview'] = true;
    itk_seo_assert( ! $seo->is_indexable_request(), 'Preview views must be excluded from language SEO links.' );

    echo "Multilingual SEO/hreflang smoke test passed.\n";
}
