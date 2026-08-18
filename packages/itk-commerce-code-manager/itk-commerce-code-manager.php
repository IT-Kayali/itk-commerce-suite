<?php
/**
 * Plugin Name: IT-Kayali Commerce Code Manager
 * Description: Controlled HTML, CSS, JavaScript and opt-in PHP extension points for the Commerce Suite.
 * Version: 0.1.0-dev
 * Author: IT-Kayali
 * Text Domain: itk-commerce-code-manager
 * Requires PHP: 8.1
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

defined( 'ABSPATH' ) || exit;

const VERSION   = '0.1.0-dev';
const FILE      = __FILE__;
const MODULE_ID = 'itk-commerce-code-manager';
const OPTION    = 'itk_commerce_code_snippets';

add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare', 10 );

/** @return void */
function prepare() {
    if ( ! interface_exists( '\ITK\Commerce\Core\Contracts\ModuleInterface' ) ) {
        return;
    }
    add_action( 'itk_commerce_register_modules', __NAMESPACE__ . '\register_module' );
}

/** @param object $registry Registry. @return void */
function register_module( $registry ) {
    if ( is_object( $registry ) && method_exists( $registry, 'register' ) ) {
        $registry->register( new CodeManagerModule() );
    }
}

final class CodeManagerModule implements \ITK\Commerce\Core\Contracts\ModuleInterface {
    /** @return string */
    public function id() { return MODULE_ID; }
    /** @return string */
    public function version() { return VERSION; }
    /** @return array<string,mixed> */
    public function requirements() {
        return array( 'core' => '0.1.0-dev', 'php' => '8.1', 'wordpress' => '6.6', 'modules' => array() );
    }

    /** @return void */
    public function register() {
        add_action( 'itk_commerce_admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_post_itk_commerce_save_code_snippets', array( $this, 'save' ) );
        add_action( 'wp_head', array( $this, 'render_head' ), 99 );
        add_action( 'wp_body_open', array( $this, 'render_body_open' ), 99 );
        add_action( 'wp_footer', array( $this, 'render_footer' ), 99 );
        do_action( 'itk_commerce_code_manager_loaded', $this );
    }

    /** @param string $parent Parent slug. @return void */
    public function admin_menu( $parent ) {
        add_submenu_page( $parent, __( 'Code Manager', 'itk-commerce-code-manager' ), __( 'Code Manager', 'itk-commerce-code-manager' ), 'manage_options', 'itk-commerce-code-manager', array( $this, 'render_admin' ) );
    }

    /** @return void */
    public function render_admin() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage custom code.', 'itk-commerce-code-manager' ), 403 );
        }
        $snippets = $this->snippets();
        ?>
        <div class="wrap"><h1><?php esc_html_e( 'Commerce Code Manager', 'itk-commerce-code-manager' ); ?></h1>
        <p><?php esc_html_e( 'Use these controlled extension points instead of editing Theme or WooCommerce source files. PHP execution is disabled unless ITK_COMMERCE_ALLOW_PHP_SNIPPETS is explicitly enabled in wp-config.php.', 'itk-commerce-code-manager' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="itk_commerce_save_code_snippets">
            <?php wp_nonce_field( 'itk_commerce_save_code_snippets' ); ?>
            <?php foreach ( array( 'head' => __( 'Head', 'itk-commerce-code-manager' ), 'body_open' => __( 'Body open', 'itk-commerce-code-manager' ), 'footer' => __( 'Footer', 'itk-commerce-code-manager' ) ) as $location => $label ) : ?>
                <h2><?php echo esc_html( $label ); ?></h2>
                <?php foreach ( array( 'html' => 'HTML', 'css' => 'CSS', 'js' => 'JavaScript', 'php' => 'PHP' ) as $type => $type_label ) : ?>
                    <p><label><strong><?php echo esc_html( $type_label ); ?></strong><br><textarea class="large-text code" rows="6" name="snippets[<?php echo esc_attr( $location ); ?>][<?php echo esc_attr( $type ); ?>]" spellcheck="false"><?php echo esc_textarea( $snippets[ $location ][ $type ] ); ?></textarea></label></p>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php submit_button( __( 'Save code', 'itk-commerce-code-manager' ) ); ?>
        </form></div>
        <?php
    }

    /** @return void */
    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage custom code.', 'itk-commerce-code-manager' ), 403 );
        }
        check_admin_referer( 'itk_commerce_save_code_snippets' );
        $posted = isset( $_POST['snippets'] ) && is_array( $_POST['snippets'] ) ? wp_unslash( $_POST['snippets'] ) : array();
        $clean = $this->empty_snippets();
        foreach ( $clean as $location => $types ) {
            foreach ( $types as $type => $unused ) {
                $value = isset( $posted[ $location ][ $type ] ) ? (string) $posted[ $location ][ $type ] : '';
                if ( 'html' === $type ) {
                    $clean[ $location ][ $type ] = current_user_can( 'unfiltered_html' ) ? $value : wp_kses_post( $value );
                } else {
                    $clean[ $location ][ $type ] = $value;
                }
            }
        }
        update_option( OPTION, $clean, false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'itk-commerce-code-manager', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /** @return void */
    public function render_head() { $this->render_location( 'head' ); }
    /** @return void */
    public function render_body_open() { $this->render_location( 'body_open' ); }
    /** @return void */
    public function render_footer() { $this->render_location( 'footer' ); }

    /** @param string $location Location. @return void */
    private function render_location( $location ) {
        $snippets = $this->snippets();
        if ( ! empty( $snippets[ $location ]['html'] ) ) {
            echo $snippets[ $location ]['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- administrator-controlled extension point.
        }
        if ( ! empty( $snippets[ $location ]['css'] ) ) {
            echo '<style data-itk-commerce-code-manager>' . $snippets[ $location ]['css'] . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        if ( ! empty( $snippets[ $location ]['js'] ) ) {
            echo '<script data-itk-commerce-code-manager>' . $snippets[ $location ]['js'] . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        if ( defined( 'ITK_COMMERCE_ALLOW_PHP_SNIPPETS' ) && ITK_COMMERCE_ALLOW_PHP_SNIPPETS && ! empty( $snippets[ $location ]['php'] ) ) {
            $this->execute_php( $snippets[ $location ]['php'], $location );
        }
    }

    /** @param string $code PHP code without opening tag. @param string $location Location. @return void */
    private function execute_php( $code, $location ) {
        /**
         * Last safety gate before executing an explicitly enabled PHP snippet.
         * Returning false disables execution for the current request.
         */
        if ( ! apply_filters( 'itk_commerce_allow_php_snippet_execution', true, $location, $code ) ) {
            return;
        }
        try {
            eval( $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- explicit opt-in developer feature guarded by constant and admin-only persistence.
        } catch ( \Throwable $error ) {
            error_log( 'IT-Kayali Commerce Code Manager PHP snippet error: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    /** @return array<string,array<string,string>> */
    private function snippets() {
        $stored = get_option( OPTION, array() );
        $clean = $this->empty_snippets();
        if ( is_array( $stored ) ) {
            foreach ( $clean as $location => $types ) {
                foreach ( $types as $type => $unused ) {
                    if ( isset( $stored[ $location ][ $type ] ) && is_string( $stored[ $location ][ $type ] ) ) {
                        $clean[ $location ][ $type ] = $stored[ $location ][ $type ];
                    }
                }
            }
        }
        return $clean;
    }

    /** @return array<string,array<string,string>> */
    private function empty_snippets() {
        $types = array( 'html' => '', 'css' => '', 'js' => '', 'php' => '' );
        return array( 'head' => $types, 'body_open' => $types, 'footer' => $types );
    }
}
