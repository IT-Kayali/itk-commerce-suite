<?php
/**
 * Versioned snippet persistence, validation and audit trail.
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

defined( 'ABSPATH' ) || exit;

final class SnippetRepository {
    const OPTION       = 'itk_commerce_code_snippets_v2';
    const AUDIT_OPTION = 'itk_commerce_code_audit_v1';
    const MAX_VERSIONS = 20;
    const MAX_AUDIT    = 300;

    /** @return array<string,array<string,mixed>> */
    public function all() {
        $snippets = get_option( self::OPTION, array() );
        return is_array( $snippets ) ? $snippets : array();
    }

    /** @param string $id Snippet ID. @return array<string,mixed>|null */
    public function get( $id ) {
        $snippets = $this->all();
        $id = sanitize_key( $id );
        return isset( $snippets[ $id ] ) && is_array( $snippets[ $id ] ) ? $snippets[ $id ] : null;
    }

    /**
     * Save a snippet as disabled. Any edit requires an explicit second enable
     * action after validation, preventing pasted code from executing on save.
     *
     * @param array<string,mixed> $input Input.
     * @param int                 $user_id User ID.
     * @return array<string,mixed>|\WP_Error
     */
    public function save( array $input, $user_id ) {
        $snippets = $this->all();
        $id = ! empty( $input['id'] ) ? sanitize_key( $input['id'] ) : '';
        if ( '' === $id ) {
            $id = $this->unique_id( sanitize_title( isset( $input['title'] ) ? $input['title'] : 'snippet' ), $snippets );
        }

        $existing = isset( $snippets[ $id ] ) && is_array( $snippets[ $id ] ) ? $snippets[ $id ] : array();
        $type = isset( $input['type'] ) ? sanitize_key( $input['type'] ) : 'html';
        $location = isset( $input['location'] ) ? sanitize_key( $input['location'] ) : 'footer';
        $code = isset( $input['code'] ) ? (string) $input['code'] : '';
        $validation = $this->validate_code( $type, $code );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        if ( ! in_array( $type, $this->types(), true ) ) {
            return new \WP_Error( 'itk_code_type', __( 'Unsupported snippet type.', 'itk-commerce-code-manager' ) );
        }
        if ( ! in_array( $location, $this->locations(), true ) ) {
            return new \WP_Error( 'itk_code_location', __( 'Unsupported snippet location.', 'itk-commerce-code-manager' ) );
        }

        $versions = isset( $existing['versions'] ) && is_array( $existing['versions'] ) ? $existing['versions'] : array();
        if ( $existing ) {
            $versions[] = array(
                'title'      => isset( $existing['title'] ) ? (string) $existing['title'] : '',
                'type'       => isset( $existing['type'] ) ? (string) $existing['type'] : '',
                'location'   => isset( $existing['location'] ) ? (string) $existing['location'] : '',
                'code'       => isset( $existing['code'] ) ? (string) $existing['code'] : '',
                'conditions' => isset( $existing['conditions'] ) && is_array( $existing['conditions'] ) ? $existing['conditions'] : array(),
                'enabled'    => ! empty( $existing['enabled'] ),
                'saved_at'   => isset( $existing['updated_at'] ) ? (string) $existing['updated_at'] : gmdate( 'c' ),
                'saved_by'   => isset( $existing['updated_by'] ) ? absint( $existing['updated_by'] ) : 0,
                'hash'       => hash( 'sha256', isset( $existing['code'] ) ? (string) $existing['code'] : '' ),
            );
        }
        $versions = array_slice( $versions, -self::MAX_VERSIONS );

        $snippet = array(
            'id'         => $id,
            'title'      => sanitize_text_field( isset( $input['title'] ) ? $input['title'] : $id ),
            'type'       => $type,
            'location'   => $location,
            'code'       => $code,
            'conditions' => $this->normalize_conditions( isset( $input['conditions'] ) && is_array( $input['conditions'] ) ? $input['conditions'] : array() ),
            'enabled'    => false,
            'created_at' => isset( $existing['created_at'] ) ? (string) $existing['created_at'] : gmdate( 'c' ),
            'updated_at' => gmdate( 'c' ),
            'updated_by' => max( 0, (int) $user_id ),
            'versions'   => $versions,
            'last_error' => '',
        );
        $snippets[ $id ] = $snippet;
        update_option( self::OPTION, $snippets, false );
        $this->audit( $existing ? 'updated_disabled' : 'created_disabled', $id, $user_id, array( 'hash' => hash( 'sha256', $code ) ) );
        return $snippet;
    }

    /** @param string $id ID. @param bool $enabled Desired state. @param int $user_id User. @return array<string,mixed>|\WP_Error */
    public function set_enabled( $id, $enabled, $user_id ) {
        $snippets = $this->all();
        $id = sanitize_key( $id );
        if ( empty( $snippets[ $id ] ) || ! is_array( $snippets[ $id ] ) ) {
            return new \WP_Error( 'itk_code_missing', __( 'Snippet was not found.', 'itk-commerce-code-manager' ) );
        }
        $snippet = $snippets[ $id ];
        if ( $enabled ) {
            $validation = $this->validate_code( $snippet['type'] ?? '', $snippet['code'] ?? '' );
            if ( is_wp_error( $validation ) ) {
                return $validation;
            }
        }
        $snippet['enabled'] = (bool) $enabled;
        $snippet['updated_at'] = gmdate( 'c' );
        $snippet['updated_by'] = max( 0, (int) $user_id );
        if ( $enabled ) {
            $snippet['last_error'] = '';
        }
        $snippets[ $id ] = $snippet;
        update_option( self::OPTION, $snippets, false );
        $this->audit( $enabled ? 'enabled' : 'disabled', $id, $user_id );
        return $snippet;
    }

    /** @param string $id ID. @param int $user_id User. @return array<string,mixed>|\WP_Error */
    public function rollback( $id, $user_id ) {
        $snippets = $this->all();
        $id = sanitize_key( $id );
        if ( empty( $snippets[ $id ] ) || ! is_array( $snippets[ $id ] ) ) {
            return new \WP_Error( 'itk_code_missing', __( 'Snippet was not found.', 'itk-commerce-code-manager' ) );
        }
        $snippet = $snippets[ $id ];
        $versions = isset( $snippet['versions'] ) && is_array( $snippet['versions'] ) ? $snippet['versions'] : array();
        if ( empty( $versions ) ) {
            return new \WP_Error( 'itk_code_no_version', __( 'No previous snippet version is available.', 'itk-commerce-code-manager' ) );
        }

        $previous = array_pop( $versions );
        $current_snapshot = array(
            'title'      => $snippet['title'] ?? '',
            'type'       => $snippet['type'] ?? '',
            'location'   => $snippet['location'] ?? '',
            'code'       => $snippet['code'] ?? '',
            'conditions' => $snippet['conditions'] ?? array(),
            'enabled'    => false,
            'saved_at'   => $snippet['updated_at'] ?? gmdate( 'c' ),
            'saved_by'   => $snippet['updated_by'] ?? 0,
            'hash'       => hash( 'sha256', (string) ( $snippet['code'] ?? '' ) ),
        );

        $validation = $this->validate_code( $previous['type'] ?? '', $previous['code'] ?? '' );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $snippet['title'] = sanitize_text_field( $previous['title'] ?? $snippet['title'] );
        $snippet['type'] = sanitize_key( $previous['type'] ?? $snippet['type'] );
        $snippet['location'] = sanitize_key( $previous['location'] ?? $snippet['location'] );
        $snippet['code'] = (string) ( $previous['code'] ?? '' );
        $snippet['conditions'] = $this->normalize_conditions( isset( $previous['conditions'] ) && is_array( $previous['conditions'] ) ? $previous['conditions'] : array() );
        $snippet['enabled'] = false;
        $snippet['updated_at'] = gmdate( 'c' );
        $snippet['updated_by'] = max( 0, (int) $user_id );
        $snippet['last_error'] = '';
        $versions[] = $current_snapshot;
        $snippet['versions'] = array_slice( $versions, -self::MAX_VERSIONS );
        $snippets[ $id ] = $snippet;
        update_option( self::OPTION, $snippets, false );
        $this->audit( 'rolled_back_disabled', $id, $user_id, array( 'hash' => hash( 'sha256', $snippet['code'] ) ) );
        return $snippet;
    }

    /** @param string $id ID. @param string $message Error. @return void */
    public function disable_after_error( $id, $message ) {
        $snippets = $this->all();
        $id = sanitize_key( $id );
        if ( empty( $snippets[ $id ] ) || ! is_array( $snippets[ $id ] ) ) {
            return;
        }
        $snippets[ $id ]['enabled'] = false;
        $snippets[ $id ]['last_error'] = sanitize_text_field( $message );
        $snippets[ $id ]['updated_at'] = gmdate( 'c' );
        update_option( self::OPTION, $snippets, false );
        $this->audit( 'auto_disabled_error', $id, 0, array( 'message' => sanitize_text_field( $message ) ) );
    }

    /** @param string $id ID. @param int $user_id User. @return bool */
    public function delete( $id, $user_id ) {
        $snippets = $this->all();
        $id = sanitize_key( $id );
        if ( ! isset( $snippets[ $id ] ) ) {
            return false;
        }
        unset( $snippets[ $id ] );
        update_option( self::OPTION, $snippets, false );
        $this->audit( 'deleted', $id, $user_id );
        return true;
    }

    /** @return array<int,array<string,mixed>> */
    public function audit_log() {
        $rows = get_option( self::AUDIT_OPTION, array() );
        return is_array( $rows ) ? array_values( array_filter( $rows, 'is_array' ) ) : array();
    }

    /** @return string[] */
    public function types() {
        return array( 'html', 'css', 'js', 'shortcode', 'elementor', 'php' );
    }

    /** @return string[] */
    public function locations() {
        return array( 'head_start', 'head_end', 'body_open', 'before_header', 'after_header', 'before_content', 'after_content', 'before_footer', 'footer' );
    }

    /**
     * Validate syntax and reject high-risk PHP language constructs/functions.
     * This is intentionally conservative; complex features belong in a module.
     *
     * @param string $type Type.
     * @param string $code Code.
     * @return true|\WP_Error
     */
    public function validate_code( $type, $code ) {
        $type = sanitize_key( $type );
        $code = is_string( $code ) ? $code : '';
        if ( strlen( $code ) > 262144 ) {
            return new \WP_Error( 'itk_code_too_large', __( 'Snippet exceeds the 256 KiB safety limit.', 'itk-commerce-code-manager' ) );
        }
        if ( false !== strpos( $code, "\0" ) ) {
            return new \WP_Error( 'itk_code_null_byte', __( 'Snippet contains invalid null bytes.', 'itk-commerce-code-manager' ) );
        }
        if ( 'elementor' === $type && '' !== trim( $code ) && ! ctype_digit( trim( $code ) ) ) {
            return new \WP_Error( 'itk_code_elementor_id', __( 'Elementor snippets must contain only a numeric template ID.', 'itk-commerce-code-manager' ) );
        }
        if ( 'css' === $type && preg_match( '/<\s*\/\s*style/i', $code ) ) {
            return new \WP_Error( 'itk_code_css_breakout', __( 'CSS snippet contains a closing style tag.', 'itk-commerce-code-manager' ) );
        }
        if ( 'js' === $type && preg_match( '/<\s*\/\s*script/i', $code ) ) {
            return new \WP_Error( 'itk_code_js_breakout', __( 'JavaScript snippet contains a closing script tag.', 'itk-commerce-code-manager' ) );
        }
        if ( 'php' !== $type ) {
            return true;
        }
        if ( preg_match( '/<\?(?:php|=)?|\?>/i', $code ) ) {
            return new \WP_Error( 'itk_code_php_tags', __( 'PHP snippets must not contain PHP opening or closing tags.', 'itk-commerce-code-manager' ) );
        }

        try {
            $tokens = token_get_all( "<?php\n" . $code, TOKEN_PARSE );
        } catch ( \ParseError $error ) {
            return new \WP_Error( 'itk_code_php_syntax', sprintf( __( 'PHP syntax error: %s', 'itk-commerce-code-manager' ), $error->getMessage() ) );
        }

        $forbidden_tokens = array_filter(
            array(
                defined( 'T_EVAL' ) ? T_EVAL : null,
                defined( 'T_INCLUDE' ) ? T_INCLUDE : null,
                defined( 'T_INCLUDE_ONCE' ) ? T_INCLUDE_ONCE : null,
                defined( 'T_REQUIRE' ) ? T_REQUIRE : null,
                defined( 'T_REQUIRE_ONCE' ) ? T_REQUIRE_ONCE : null,
                defined( 'T_EXIT' ) ? T_EXIT : null,
                defined( 'T_HALT_COMPILER' ) ? T_HALT_COMPILER : null,
            ),
            'is_int'
        );
        $forbidden_functions = array( 'exec', 'system', 'shell_exec', 'passthru', 'proc_open', 'popen', 'pcntl_exec', 'assert', 'create_function', 'putenv', 'ini_set', 'dl' );

        foreach ( $tokens as $token ) {
            if ( is_string( $token ) ) {
                if ( '`' === $token ) {
                    return new \WP_Error( 'itk_code_php_backtick', __( 'Shell execution syntax is not allowed in PHP snippets.', 'itk-commerce-code-manager' ) );
                }
                continue;
            }
            if ( in_array( $token[0], $forbidden_tokens, true ) ) {
                return new \WP_Error( 'itk_code_php_construct', __( 'This PHP language construct is not allowed in Code Manager snippets.', 'itk-commerce-code-manager' ) );
            }
            if ( T_STRING === $token[0] && in_array( strtolower( $token[1] ), $forbidden_functions, true ) ) {
                return new \WP_Error( 'itk_code_php_function', sprintf( __( 'PHP function %s is not allowed in Code Manager snippets.', 'itk-commerce-code-manager' ), $token[1] ) );
            }
        }
        return true;
    }

    /** @param string $action Action. @param string $id ID. @param int $user_id User. @param array<string,mixed> $context Context. @return void */
    private function audit( $action, $id, $user_id, array $context = array() ) {
        $rows = $this->audit_log();
        $rows[] = array(
            'created_at' => gmdate( 'c' ),
            'action'     => sanitize_key( $action ),
            'snippet_id' => sanitize_key( $id ),
            'user_id'    => max( 0, (int) $user_id ),
            'context'    => $this->sanitize_context( $context ),
        );
        update_option( self::AUDIT_OPTION, array_slice( $rows, -self::MAX_AUDIT ), false );
    }

    /** @param array<string,mixed> $conditions Conditions. @return array<string,mixed> */
    private function normalize_conditions( array $conditions ) {
        $list_keys = array( 'languages', 'roles', 'page_types', 'product_ids', 'categories', 'devices' );
        $clean = array();
        foreach ( $list_keys as $key ) {
            $values = isset( $conditions[ $key ] ) && is_array( $conditions[ $key ] ) ? $conditions[ $key ] : array();
            if ( 'product_ids' === $key ) {
                $clean[ $key ] = array_values( array_unique( array_filter( array_map( 'absint', $values ) ) ) );
            } else {
                $clean[ $key ] = array_values( array_unique( array_filter( array_map( 'sanitize_key', $values ) ) ) );
            }
        }
        return $clean;
    }

    /** @param array<string,mixed> $context Context. @return array<string,mixed> */
    private function sanitize_context( array $context ) {
        $clean = array();
        foreach ( $context as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $clean[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
            }
        }
        return $clean;
    }

    /** @param string $candidate Candidate. @param array<string,mixed> $snippets Existing. @return string */
    private function unique_id( $candidate, array $snippets ) {
        $base = sanitize_key( $candidate ) ?: 'snippet';
        $id = $base;
        $index = 2;
        while ( isset( $snippets[ $id ] ) ) {
            $id = $base . '-' . $index;
            $index++;
        }
        return $id;
    }
}
