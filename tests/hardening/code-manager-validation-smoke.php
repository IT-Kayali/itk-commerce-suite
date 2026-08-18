<?php
/**
 * Executable validation smoke test for the Code Manager parser/safety gates.
 */

define( 'ABSPATH', __DIR__ );

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;
        public function __construct( $code = '', $message = '' ) { $this->code = $code; $this->message = $message; }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }
}

if ( ! function_exists( '__' ) ) { function __( $text, $domain = '' ) { unset( $domain ); return $text; } }
if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); } }
if ( ! function_exists( 'sanitize_title' ) ) { function sanitize_title( $value ) { return sanitize_key( str_replace( ' ', '-', (string) $value ) ); } }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $value ) { return trim( (string) $value ); } }
if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $value ) { return $value instanceof WP_Error; } }

require_once dirname( __DIR__, 2 ) . '/packages/itk-commerce-code-manager/src/SnippetRepository.php';

$repository = new \ITK\Commerce\CodeManager\SnippetRepository();

$valid_php = $repository->validate_code( 'php', "add_action( 'init', function () { return; } );" );
if ( true !== $valid_php ) {
    $detail = is_wp_error( $valid_php ) ? $valid_php->get_error_code() . ': ' . $valid_php->get_error_message() : gettype( $valid_php );
    throw new RuntimeException( 'Safe PHP snippet did not pass validation: ' . $detail );
}

$cases = array(
    array( 'php', 'eval( "$x = 1;" );', 'eval must be rejected' ),
    array( 'php', "system( 'id' );", 'system must be rejected' ),
    array( 'php', "include 'file.php';", 'include must be rejected' ),
    array( 'php', 'echo `id`;', 'backticks must be rejected' ),
    array( 'php', 'if (', 'syntax errors must be rejected' ),
    array( 'php', "<?php echo 'x';", 'PHP tags must be rejected' ),
    array( 'js', "console.log('x');</script><script>alert(1)</script>", 'script breakout must be rejected' ),
    array( 'css', "body{color:red}</style><script>alert(1)</script>", 'style breakout must be rejected' ),
    array( 'elementor', '12abc', 'Elementor template must be numeric' ),
);

foreach ( $cases as $case ) {
    $result = $repository->validate_code( $case[0], $case[1] );
    if ( ! is_wp_error( $result ) ) {
        throw new RuntimeException( $case[2] );
    }
}

if ( true !== $repository->validate_code( 'elementor', '123' ) ) {
    throw new RuntimeException( 'Valid Elementor template ID did not pass.' );
}
if ( true !== $repository->validate_code( 'js', "console.log('safe');" ) ) {
    throw new RuntimeException( 'Safe JavaScript did not pass.' );
}
if ( true !== $repository->validate_code( 'css', '.shop{display:grid}' ) ) {
    throw new RuntimeException( 'Safe CSS did not pass.' );
}

fwrite( STDOUT, "Code Manager validation smoke test passed.\n" );
