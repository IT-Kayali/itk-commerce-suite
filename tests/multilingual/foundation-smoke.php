<?php
/**
 * Dependency-light Multilingual foundation smoke test.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );
define( 'ITK_MULTILINGUAL_TEST', true );

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
}

namespace {
    $GLOBALS['itk_multilingual_actions'] = array();

    function get_locale() { return 'de_DE'; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function add_filter() {}
    function do_action( $hook, ...$args ) { $GLOBALS['itk_multilingual_actions'][] = array( $hook, $args ); }

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';

    function itk_multilingual_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Multilingual foundation failure: {$message}\n" );
            exit( 1 );
        }
    }

    $schema   = new \ITK\Commerce\Multilingual\LanguageSchema();
    $defaults = $schema->defaults();

    itk_multilingual_assert( 1 === $defaults['schema_version'], 'Default schema version must be explicit.' );
    itk_multilingual_assert( 'de' === $defaults['default'] && 'de' === $defaults['fallback'], 'WordPress de_DE locale should produce neutral de language defaults.' );
    itk_multilingual_assert( 'de_DE' === $defaults['languages'][0]['locale'], 'Default locale normalization must preserve de_DE.' );
    itk_multilingual_assert( 'ltr' === $defaults['languages'][0]['direction'], 'German default direction should be LTR.' );

    $config = $schema->normalize(
        array(
            'default'  => 'ar',
            'fallback' => 'de',
            'languages' => array(
                array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'direction' => 'ltr', 'enabled' => true ),
                array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
                array( 'code' => 'en', 'locale' => 'en_US', 'label' => 'English', 'direction' => 'ltr', 'enabled' => false ),
                array( 'code' => 'AR', 'locale' => 'ar_SA', 'label' => 'Duplicate', 'direction' => 'rtl', 'enabled' => true ),
                array( 'code' => '../bad', 'locale' => 'xx_BAD!', 'label' => 'Unsafe', 'enabled' => true ),
            ),
        )
    );

    itk_multilingual_assert( 3 === count( $config['languages'] ), 'Duplicate and unsafe language entries must be discarded.' );
    itk_multilingual_assert( 'ar' === $config['default'], 'Configured enabled default language should be preserved.' );
    itk_multilingual_assert( 'de' === $config['fallback'], 'Configured enabled fallback language should be preserved.' );

    $keyed = $schema->keyed( $config );
    itk_multilingual_assert( 'rtl' === $keyed['ar']['direction'], 'Arabic profile language direction must normalize to RTL.' );
    itk_multilingual_assert( false === $keyed['en']['enabled'], 'Disabled language must remain in profile configuration without becoming enabled.' );
    itk_multilingual_assert( 'pt-br' === $schema->normalize_code( 'PT_BR' ), 'Public language codes should normalize to bounded lowercase URL form.' );
    itk_multilingual_assert( '' === $schema->normalize_code( 'bad/code' ), 'Unsafe public language codes must be rejected.' );
    itk_multilingual_assert( 'pt_BR' === $schema->normalize_locale( 'pt_br' ), 'WordPress locale normalization should produce pt_BR.' );
    itk_multilingual_assert( '' === $schema->normalize_locale( 'de_DE<script>' ), 'Unsafe locale strings must be rejected.' );

    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );
    itk_multilingual_assert( 'ar' === $context->code(), 'Context should start with the configured default language.' );
    itk_multilingual_assert( 'ar' === $context->locale(), 'Arabic locale should be exposed by context.' );
    itk_multilingual_assert( 'rtl' === $context->direction(), 'Arabic context must expose RTL direction.' );
    itk_multilingual_assert( 2 === count( $context->enabled_languages() ), 'Disabled languages must not enter the public enabled-language context.' );

    $classes = $context->body_classes( array( 'woocommerce' ) );
    itk_multilingual_assert( in_array( 'itk-language-ar', $classes, true ), 'Body classes must expose the current language.' );
    itk_multilingual_assert( in_array( 'itk-direction-rtl', $classes, true ), 'Body classes must expose RTL direction.' );

    $attributes = $context->language_attributes( 'lang="de-DE" dir="ltr"' );
    itk_multilingual_assert( false !== strpos( $attributes, 'lang="ar"' ), 'HTML language attribute must follow Commerce language context.' );
    itk_multilingual_assert( false !== strpos( $attributes, 'dir="rtl"' ), 'HTML direction attribute must follow Commerce direction context.' );

    itk_multilingual_assert( true === $context->select( 'de' ), 'Selecting another configured enabled language should succeed.' );
    itk_multilingual_assert( 'de' === $context->code() && 'ltr' === $context->direction(), 'Selected language context must update code/direction.' );
    itk_multilingual_assert( false === $context->select( 'en' ), 'Disabled language must not be selectable for the current request.' );
    itk_multilingual_assert( false === $context->select( 'xx' ), 'Unknown language must not be selectable.' );

    $changes = array_filter(
        $GLOBALS['itk_multilingual_actions'],
        static function ( $event ) {
            return 'itk_commerce_language_context_changed' === $event[0];
        }
    );
    itk_multilingual_assert( 1 === count( $changes ), 'Language context changes must emit one public event.' );

    $public = $context->filter_context( array() );
    itk_multilingual_assert( 'de' === $public['code'] && 'de_DE' === $public['locale'], 'Public language context must expose selected code and locale.' );
    itk_multilingual_assert( 'de' === $public['fallback'], 'Public language context must expose configured fallback.' );

    echo "Multilingual foundation smoke test passed.\n";
}
