<?php
/**
 * Controlled frontend snippet runtime.
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

defined( 'ABSPATH' ) || exit;

final class SnippetRuntime {
    /** @var SnippetRepository */
    private $repository;

    /** @var ConditionMatcher */
    private $matcher;

    /** @var string */
    private $active_php_snippet = '';

    /** @param SnippetRepository $repository Repository. @param ConditionMatcher $matcher Matcher. */
    public function __construct( SnippetRepository $repository, ConditionMatcher $matcher ) {
        $this->repository = $repository;
        $this->matcher = $matcher;
    }

    /** @return void */
    public function register() {
        if ( is_admin() || $this->safe_mode() ) {
            return;
        }

        register_shutdown_function( array( $this, 'shutdown_guard' ) );
        $hook_map = array(
            'head_start'     => array( 'wp_head', 1 ),
            'head_end'       => array( 'wp_head', 999 ),
            'body_open'      => array( 'wp_body_open', 50 ),
            'before_header'  => array( 'itk_commerce_before_header', 10 ),
            'after_header'   => array( 'itk_commerce_after_header', 10 ),
            'before_content' => array( 'itk_commerce_before_content', 10 ),
            'after_content'  => array( 'itk_commerce_after_content', 10 ),
            'before_footer'  => array( 'itk_commerce_before_footer', 10 ),
            'footer'         => array( 'wp_footer', 80 ),
        );

        foreach ( $this->repository->all() as $snippet ) {
            if ( ! is_array( $snippet ) || empty( $snippet['enabled'] ) ) {
                continue;
            }
            $location = isset( $snippet['location'] ) ? sanitize_key( $snippet['location'] ) : '';
            if ( ! isset( $hook_map[ $location ] ) ) {
                continue;
            }
            add_action(
                $hook_map[ $location ][0],
                function () use ( $snippet ) {
                    $this->render( $snippet );
                },
                $hook_map[ $location ][1]
            );
        }
    }

    /** @param array<string,mixed> $snippet Snippet. @return void */
    public function render( array $snippet ) {
        if ( $this->safe_mode() || empty( $snippet['enabled'] ) ) {
            return;
        }
        $conditions = isset( $snippet['conditions'] ) && is_array( $snippet['conditions'] ) ? $snippet['conditions'] : array();
        if ( ! $this->matcher->matches( $conditions ) ) {
            return;
        }

        $type = isset( $snippet['type'] ) ? sanitize_key( $snippet['type'] ) : '';
        $code = isset( $snippet['code'] ) ? (string) $snippet['code'] : '';
        if ( '' === trim( $code ) ) {
            return;
        }

        $validation = $this->repository->validate_code( $type, $code );
        if ( is_wp_error( $validation ) ) {
            $this->repository->disable_after_error( (string) ( $snippet['id'] ?? '' ), $validation->get_error_message() );
            return;
        }

        if ( 'html' === $type ) {
            echo wp_kses_post( do_shortcode( $code ) );
            return;
        }
        if ( 'css' === $type ) {
            echo '<style data-itk-commerce-snippet="' . esc_attr( (string) ( $snippet['id'] ?? '' ) ) . '">' . $code . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- validated administrator CSS.
            return;
        }
        if ( 'js' === $type ) {
            echo '<script data-itk-commerce-snippet="' . esc_attr( (string) ( $snippet['id'] ?? '' ) ) . '">' . $code . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- validated administrator JS.
            return;
        }
        if ( 'shortcode' === $type ) {
            echo do_shortcode( $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode owns its output contract.
            return;
        }
        if ( 'elementor' === $type ) {
            $template_id = absint( trim( $code ) );
            if ( $template_id && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
                echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor renders its own template.
            }
            return;
        }
        if ( 'php' === $type ) {
            $this->execute_php( $snippet, $code );
        }
    }

    /** @return void */
    public function shutdown_guard() {
        if ( '' === $this->active_php_snippet ) {
            return;
        }
        $error = error_get_last();
        if ( ! is_array( $error ) || ! in_array( $error['type'] ?? 0, array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
            return;
        }
        $this->repository->disable_after_error(
            $this->active_php_snippet,
            sprintf( 'Fatal runtime error: %s in %s:%d', (string) ( $error['message'] ?? '' ), basename( (string) ( $error['file'] ?? '' ) ), absint( $error['line'] ?? 0 ) )
        );
    }

    /** @param array<string,mixed> $snippet Snippet. @param string $code PHP. @return void */
    private function execute_php( array $snippet, $code ) {
        $id = sanitize_key( (string) ( $snippet['id'] ?? '' ) );
        if ( '' === $id ) {
            return;
        }
        $this->active_php_snippet = $id;

        // Run in a static closure so snippets cannot reach the runtime object via
        // `$this`. token_get_all(TOKEN_PARSE) and the conservative forbidden-
        // construct list have already validated the code before this point.
        $runner = static function ( $validated_code ) {
            eval( $validated_code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- dedicated admin-only, syntax-checked, versioned, safe-mode runtime.
        };

        try {
            $runner( $code );
            $this->active_php_snippet = '';
        } catch ( \Throwable $error ) {
            $this->active_php_snippet = '';
            $this->repository->disable_after_error( $id, $error->getMessage() );
            do_action( 'itk_commerce_code_snippet_error', $id, $error );
        }
    }

    /** @return bool */
    private function safe_mode() {
        if ( defined( 'ITK_COMMERCE_CODE_SAFE_MODE' ) && ITK_COMMERCE_CODE_SAFE_MODE ) {
            return true;
        }
        return ! (bool) apply_filters( 'itk_commerce_code_manager_runtime_enabled', true );
    }
}
