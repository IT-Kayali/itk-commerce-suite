<?php
/**
 * Dependency-light Multilingual routing / switcher smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );

    $GLOBALS['itk_multilingual_actions']       = array();
    $GLOBALS['itk_multilingual_switched_to']   = array();
    $GLOBALS['itk_multilingual_shortcodes']    = array();

    function get_locale() { return 'de_DE'; }
    function home_url( $path = '' ) {
        $base = 'https://example.test/shop';
        if ( '' === (string) $path ) {
            return $base;
        }
        return $base . '/' . ltrim( (string) $path, '/' );
    }
    function is_admin() { return false; }
    function wp_doing_ajax() { return false; }
    function wp_doing_cron() { return false; }
    function wp_installing() { return false; }
    function switch_to_locale( $locale ) {
        $GLOBALS['itk_multilingual_switched_to'][] = (string) $locale;
        return true;
    }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function esc_url( $value ) { return (string) $value; }
    function esc_attr__( $value ) { return (string) $value; }
    function add_filter() {}
    function add_action() {}
    function add_shortcode( $tag, $callback ) { $GLOBALS['itk_multilingual_shortcodes'][ $tag ] = $callback; }
    function apply_filters( $hook, $value ) { return $value; }
    function do_action( $hook, ...$args ) { $GLOBALS['itk_multilingual_actions'][] = array( $hook, $args ); }
    function shortcode_atts( $pairs, $atts ) { return array_merge( $pairs, is_array( $atts ) ? $atts : array() ); }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageRouter.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSwitcher.php';

    function itk_multilingual_routing_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Multilingual routing failure: {$message}\n" );
            exit( 1 );
        }
    }

    $schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $config = $schema->normalize(
        array(
            'default'  => 'de',
            'fallback' => 'de',
            'languages' => array(
                array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'direction' => 'ltr', 'enabled' => true ),
                array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
                array( 'code' => 'en', 'locale' => 'en_US', 'label' => 'English', 'direction' => 'ltr', 'enabled' => true ),
            ),
        )
    );

    $_SERVER['REQUEST_URI'] = '/shop/ar/produkt/duft/?orderby=price&_wpnonce=bad&add-to-cart=55&filter_note=rose';
    $_SERVER['PATH_INFO']   = '/shop/ar/produkt/duft/';

    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );
    $router  = new \ITK\Commerce\Multilingual\LanguageRouter( $context );
    $router->register();

    $route = $router->route_context();
    itk_multilingual_routing_assert( 'ar' === $context->code(), 'The /ar/ directory must select the configured Arabic context.' );
    itk_multilingual_routing_assert( 'ar' === $route['code'] && true === $route['explicit_prefix'], 'Route context must expose the explicit language prefix.' );
    itk_multilingual_routing_assert( 'produkt/duft' === $route['path'], 'Route context must retain the internal storefront path without the language directory.' );
    itk_multilingual_routing_assert( array( 'ar' ) === $GLOBALS['itk_multilingual_switched_to'], 'The public WordPress locale switcher must receive the selected locale.' );
    itk_multilingual_routing_assert( 'ar' === $router->filter_pre_locale( null ), 'pre_determine_locale must follow the selected storefront locale.' );
    itk_multilingual_routing_assert( 'ar' === $router->filter_locale( 'de_DE' ), 'locale/determine_locale must follow the selected storefront locale.' );

    $before_uri  = $_SERVER['REQUEST_URI'];
    $before_path = $_SERVER['PATH_INFO'];
    itk_multilingual_routing_assert( true === $router->prepare_wordpress_request( true, new \stdClass(), array() ), 'Prefixed storefront requests must remain parseable by WordPress.' );
    itk_multilingual_routing_assert(
        '/shop/produkt/duft?orderby=price&_wpnonce=bad&add-to-cart=55&filter_note=rose' === $_SERVER['REQUEST_URI'],
        'Only the language directory may be removed before normal WordPress route parsing.'
    );
    itk_multilingual_routing_assert( '/shop/produkt/duft' === $_SERVER['PATH_INFO'], 'PATH_INFO must follow the same internal route during parsing.' );

    $router->restore_request_globals();
    itk_multilingual_routing_assert( $before_uri === $_SERVER['REQUEST_URI'], 'The public localized REQUEST_URI must be restored after parsing.' );
    itk_multilingual_routing_assert( $before_path === $_SERVER['PATH_INFO'], 'The public localized PATH_INFO must be restored after parsing.' );

    $localized = $router->url_for(
        'de',
        'https://example.test/shop/ar/produkt/duft/?orderby=price&_wpnonce=bad&add-to-cart=55&filter_note=rose#details'
    );
    itk_multilingual_routing_assert(
        'https://example.test/shop/de/produkt/duft/?orderby=price&filter_note=rose#details' === $localized,
        'Language URLs must preserve safe storefront state while dropping nonce/action parameters.'
    );

    $external = $router->url_for( 'en', 'https://evil.example/steal?x=1' );
    itk_multilingual_routing_assert(
        'https://example.test/shop/en/' === $external,
        'External source URLs must not be reflected into the language switcher.'
    );

    $unprefixed = $router->route_parts_from_uri( '/shop/kategorie/parfuem/' );
    itk_multilingual_routing_assert( false === $unprefixed['explicit'] && 'kategorie/parfuem' === $unprefixed['path'], 'Unprefixed storefront URLs must remain valid default-language routes.' );

    $switcher = new \ITK\Commerce\Multilingual\LanguageSwitcher( $context, $router );
    $switcher->register();
    $items = $switcher->items( 'https://example.test/shop/ar/produkt/duft/' );

    itk_multilingual_routing_assert( 3 === count( $items ), 'Switcher must expose all configured enabled languages.' );
    itk_multilingual_routing_assert( true === $items[1]['current'] && 'ar' === $items[1]['code'], 'Switcher must mark the current Arabic language.' );
    itk_multilingual_routing_assert( isset( $GLOBALS['itk_multilingual_shortcodes']['itk_language_switcher'] ), 'Language switcher shortcode must be registered.' );

    $html = $switcher->render( array( 'display' => 'both', 'class' => 'header-language' ) );
    itk_multilingual_routing_assert( false !== strpos( $html, 'data-itk-language-switcher' ), 'Switcher markup must expose a stable Theme/Builder hook.' );
    itk_multilingual_routing_assert( false !== strpos( $html, 'hreflang="de"' ), 'Switcher links must expose bounded hreflang values.' );
    itk_multilingual_routing_assert( false !== strpos( $html, 'aria-current="page"' ), 'Current language link must be announced accessibly.' );
    itk_multilingual_routing_assert( false !== strpos( $html, 'header-language' ), 'Consumer-supplied safe class must be preserved.' );

    echo "Multilingual routing smoke test passed.\n";
}
