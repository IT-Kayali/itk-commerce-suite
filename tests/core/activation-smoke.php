<?php
/**
 * Minimal dependency-free activation/deactivation smoke test.
 *
 * This does not replace WordPress integration tests. It verifies that the Core
 * bootstrap can be loaded and its lifecycle routines initialize expected
 * versioned options and capabilities without fatal errors.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );

$GLOBALS['itk_test_options'] = array();
$GLOBALS['itk_test_roles']   = array(
    'administrator' => null,
    'shop_manager'  => null,
);
$GLOBALS['itk_activation_hook']   = null;
$GLOBALS['itk_deactivation_hook'] = null;

class WP_Error {
    public function __construct( $code = '', $message = '' ) {
        $this->code    = $code;
        $this->message = $message;
    }
}

class ITK_Test_Role {
    public $caps = array();

    public function __construct( array $caps = array() ) {
        $this->caps = $caps;
    }

    public function add_cap( $capability ) {
        $this->caps[ $capability ] = true;
    }
}

$GLOBALS['itk_test_roles']['administrator'] = new ITK_Test_Role( array( 'read' => true ) );
$GLOBALS['itk_test_roles']['shop_manager']  = new ITK_Test_Role( array( 'read' => true ) );

function __( $text ) { return $text; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function get_bloginfo( $key ) { return '6.6'; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function apply_filters( $hook, $value ) { return $value; }
function do_action() {}
function add_action() {}
function current_user_can() { return true; }
function esc_html__( $text ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }

function get_option( $name, $default = false ) {
    return array_key_exists( $name, $GLOBALS['itk_test_options'] ) ? $GLOBALS['itk_test_options'][ $name ] : $default;
}

function add_option( $name, $value ) {
    if ( array_key_exists( $name, $GLOBALS['itk_test_options'] ) ) {
        return false;
    }
    $GLOBALS['itk_test_options'][ $name ] = $value;
    return true;
}

function update_option( $name, $value ) {
    $GLOBALS['itk_test_options'][ $name ] = $value;
    return true;
}

function add_role( $role, $display_name, array $capabilities = array() ) {
    if ( ! isset( $GLOBALS['itk_test_roles'][ $role ] ) ) {
        $GLOBALS['itk_test_roles'][ $role ] = new ITK_Test_Role( $capabilities );
    }
    return $GLOBALS['itk_test_roles'][ $role ];
}

function get_role( $role ) {
    return isset( $GLOBALS['itk_test_roles'][ $role ] ) ? $GLOBALS['itk_test_roles'][ $role ] : null;
}

function register_activation_hook( $file, $callback ) {
    $GLOBALS['itk_activation_hook'] = $callback;
}

function register_deactivation_hook( $file, $callback ) {
    $GLOBALS['itk_deactivation_hook'] = $callback;
}

function itk_test_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-core/itk-commerce-core.php';

itk_test_assert( is_callable( $GLOBALS['itk_activation_hook'] ), 'Activation hook is registered.' );
itk_test_assert( is_callable( $GLOBALS['itk_deactivation_hook'] ), 'Deactivation hook is registered.' );

call_user_func( $GLOBALS['itk_activation_hook'] );

itk_test_assert( isset( $GLOBALS['itk_test_options']['itk_commerce_settings'] ), 'Core settings are initialized.' );
itk_test_assert( isset( $GLOBALS['itk_test_options']['itk_commerce_customer_profiles'] ), 'Profile storage is initialized.' );
itk_test_assert( '0.1.0-dev' === $GLOBALS['itk_test_options']['itk_commerce_core_version'], 'Core version is persisted.' );
itk_test_assert( isset( $GLOBALS['itk_test_roles']['itk_designer'] ), 'Designer role is installed.' );
itk_test_assert( isset( $GLOBALS['itk_test_roles']['itk_translator'] ), 'Translator role is installed.' );
itk_test_assert( isset( $GLOBALS['itk_test_roles']['itk_document_manager'] ), 'Document manager role is installed.' );
itk_test_assert( ! empty( $GLOBALS['itk_test_roles']['administrator']->caps['itk_manage_modules'] ), 'Administrator receives module capability.' );
itk_test_assert( ! empty( $GLOBALS['itk_test_roles']['shop_manager']->caps['itk_manage_documents'] ), 'Shop manager receives operational document capability.' );

call_user_func( $GLOBALS['itk_deactivation_hook'] );

echo "Core activation/deactivation smoke test passed.\n";
