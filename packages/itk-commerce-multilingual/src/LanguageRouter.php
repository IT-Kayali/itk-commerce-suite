<?php
/**
 * Directory-style storefront language routing and WordPress locale bridge.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class LanguageRouter {
    /** @var LanguageContext */
    private $context;

    /** @var string */
    private $request_language = '';

    /** @var string */
    private $route_path = '';

    /** @var bool */
    private $explicit_prefix = false;

    /** @var string|null */
    private $original_request_uri = null;

    /** @var string|null */
    private $original_path_info = null;

    /** @var bool */
    private $locale_switch_attempted = false;

    /** @var bool */
    private $locale_switched = false;

    /** @param LanguageContext $context Normalized request language context. */
    public function __construct( LanguageContext $context ) {
        $this->context = $context;
    }

    /** @return void */
    public function register() {
        $this->resolve_current_request();
        $this->apply_wordpress_locale();

        add_filter( 'pre_determine_locale', array( $this, 'filter_pre_locale' ), 20 );
        add_filter( 'locale', array( $this, 'filter_locale' ), 20 );
        add_filter( 'determine_locale', array( $this, 'filter_locale' ), 20 );
        add_filter( 'do_parse_request', array( $this, 'prepare_wordpress_request' ), 9999, 3 );
        add_action( 'parse_request', array( $this, 'restore_request_globals' ), 0 );
        add_action( 'shutdown', array( $this, 'restore_request_globals' ), 0 );
        add_filter( 'itk_commerce_language_route_context', array( $this, 'filter_route_context' ) );
        add_filter( 'itk_commerce_language_url', array( $this, 'filter_language_url' ), 10, 3 );
    }

    /**
     * Resolve a configured language prefix without changing WordPress's route
     * semantics. Unprefixed requests continue in the configured default context.
     *
     * @return void
     */
    public function resolve_current_request() {
        $uri   = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $parts = $this->route_parts_from_uri( $uri );

        $this->request_language = $parts['code'];
        $this->route_path       = $parts['path'];
        $this->explicit_prefix  = $parts['explicit'];

        if ( $this->explicit_prefix ) {
            $this->context->select( $this->request_language );
        } else {
            $this->request_language = $this->context->code();
        }
    }

    /**
     * Run WordPress's public locale switcher before forcing locale filters.
     * This lets already-loaded translations move with the storefront request
     * when the configured locale is installed on the site.
     *
     * @return void
     */
    private function apply_wordpress_locale() {
        if ( ! $this->is_storefront_request() ) {
            return;
        }

        $locale = $this->context->locale();
        if ( '' === $locale || ! function_exists( 'switch_to_locale' ) ) {
            return;
        }

        $this->locale_switch_attempted = true;
        $this->locale_switched         = (bool) switch_to_locale( $locale );

        do_action(
            'itk_commerce_wordpress_locale_applied',
            $locale,
            $this->context->code(),
            $this->locale_switched
        );
    }

    /** @param mixed $locale Existing pre-determined locale. @return mixed */
    public function filter_pre_locale( $locale ) {
        if ( ! $this->is_storefront_request() || '' === $this->context->locale() ) {
            return $locale;
        }

        return $this->context->locale();
    }

    /** @param mixed $locale Existing locale. @return mixed */
    public function filter_locale( $locale ) {
        if ( ! $this->is_storefront_request() || '' === $this->context->locale() ) {
            return $locale;
        }

        return $this->context->locale();
    }

    /**
     * WordPress reads REQUEST_URI after the do_parse_request filter. For a
     * language-prefixed storefront URL we temporarily remove only the language
     * directory, allowing the normal WordPress/WooCommerce rewrite rules to
     * resolve the remaining path unchanged.
     *
     * @param bool         $parse Whether WordPress should parse the request.
     * @param object       $wp Current WP environment.
     * @param array|string $extra_query_vars Extra query variables.
     * @return bool
     */
    public function prepare_wordpress_request( $parse, $wp, $extra_query_vars ) {
        unset( $wp, $extra_query_vars );

        if ( ! $parse || ! $this->explicit_prefix || ! $this->is_storefront_request() ) {
            return (bool) $parse;
        }

        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $this->original_request_uri = (string) $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI']     = $this->internal_request_uri( $this->original_request_uri );
        }

        if ( isset( $_SERVER['PATH_INFO'] ) && '' !== (string) $_SERVER['PATH_INFO'] ) {
            $this->original_path_info = (string) $_SERVER['PATH_INFO'];
            $_SERVER['PATH_INFO']     = $this->internal_request_path();
        }

        do_action( 'itk_commerce_language_route_prepared', $this->route_context() );

        return true;
    }

    /**
     * Restore the public localized request URI immediately after WordPress has
     * parsed its normal route. The shutdown hook is a safety net for aborted
     * parsing paths.
     *
     * @return void
     */
    public function restore_request_globals() {
        if ( null !== $this->original_request_uri ) {
            $_SERVER['REQUEST_URI']      = $this->original_request_uri;
            $this->original_request_uri = null;
        }

        if ( null !== $this->original_path_info ) {
            $_SERVER['PATH_INFO']      = $this->original_path_info;
            $this->original_path_info = null;
        }
    }

    /** @return array<string,mixed> */
    public function route_context() {
        return array(
            'code'                    => $this->context->code(),
            'locale'                  => $this->context->locale(),
            'direction'               => $this->context->direction(),
            'path'                    => $this->route_path,
            'explicit_prefix'         => $this->explicit_prefix,
            'locale_switch_attempted' => $this->locale_switch_attempted,
            'locale_switched'         => $this->locale_switched,
        );
    }

    /** @param mixed $context Existing route context. @return array<string,mixed> */
    public function filter_route_context( $context ) {
        unset( $context );
        return $this->route_context();
    }

    /**
     * Build a same-origin directory-style language URL while preserving only
     * non-action query parameters.
     *
     * @param string $code Target configured language code.
     * @param string $source_url Optional current/same-origin source URL.
     * @return string
     */
    public function url_for( $code, $source_url = '' ) {
        $language = $this->enabled_language( $code );
        if ( empty( $language ) ) {
            return '';
        }

        $source = $this->source_parts( $source_url );
        $path   = isset( $source['path'] ) ? (string) $source['path'] : '/';

        if ( ! empty( $source['external'] ) ) {
            $path = '/';
        }

        $relative = $this->relative_home_path( $path );
        $relative = $this->strip_enabled_language_prefix( $relative );
        $relative = $this->safe_route_path( $relative );

        $target_path = '/' . $language['code'] . '/';
        if ( '' !== $relative ) {
            $target_path .= $relative;
            if ( '/' === substr( $path, -1 ) && '/' !== substr( $target_path, -1 ) ) {
                $target_path .= '/';
            }
        }

        $url   = home_url( $target_path );
        $query = empty( $source['external'] ) && isset( $source['query'] )
            ? $this->safe_query_string( (string) $source['query'] )
            : '';

        if ( '' !== $query ) {
            $url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
        }

        if ( empty( $source['external'] ) && ! empty( $source['fragment'] ) ) {
            $fragment = preg_replace( '/[^A-Za-z0-9._~!$&\'()*+,;=:@\/?%-]/', '', (string) $source['fragment'] );
            if ( '' !== $fragment ) {
                $url .= '#' . $fragment;
            }
        }

        return $url;
    }

    /**
     * Public language URL filter callback.
     *
     * @param mixed  $url Existing URL.
     * @param string $code Target language code.
     * @param string $source_url Optional source URL.
     * @return string
     */
    public function filter_language_url( $url, $code, $source_url = '' ) {
        unset( $url );
        return $this->url_for( $code, $source_url );
    }

    /**
     * @param string $uri Request URI.
     * @return array{code:string,path:string,explicit:bool}
     */
    public function route_parts_from_uri( $uri ) {
        $path     = parse_url( (string) $uri, PHP_URL_PATH );
        $path     = is_string( $path ) ? $path : '/';
        $relative = $this->relative_home_path( $path );
        $trimmed  = trim( $relative, '/' );
        $segments = '' === $trimmed ? array() : explode( '/', $trimmed );
        $first    = ! empty( $segments ) ? rawurldecode( $segments[0] ) : '';
        $language = $this->enabled_language( $first );

        if ( ! empty( $language ) ) {
            array_shift( $segments );
            return array(
                'code'     => $language['code'],
                'path'     => $this->safe_route_path( implode( '/', $segments ) ),
                'explicit' => true,
            );
        }

        return array(
            'code'     => $this->context->code(),
            'path'     => $this->safe_route_path( $trimmed ),
            'explicit' => false,
        );
    }

    /** @return string */
    private function internal_request_path() {
        $home = $this->home_path();
        $path = '' !== $home ? $home : '';
        $path .= '/' . ltrim( $this->route_path, '/' );
        $path  = '/' . trim( $path, '/' );

        return '/' === $path ? '/' : $path;
    }

    /** @param string $original_uri Original localized request URI. @return string */
    private function internal_request_uri( $original_uri ) {
        $query = parse_url( $original_uri, PHP_URL_QUERY );
        $uri   = $this->internal_request_path();

        if ( is_string( $query ) && '' !== $query ) {
            $uri .= '?' . $query;
        }

        return $uri;
    }

    /** @param string $path Absolute request path. @return string */
    private function relative_home_path( $path ) {
        $path      = '/' . ltrim( (string) $path, '/' );
        $home_path = $this->home_path();

        if ( '' !== $home_path ) {
            if ( $path === $home_path || $path === $home_path . '/' ) {
                return '';
            }
            if ( 0 === strpos( $path, $home_path . '/' ) ) {
                $path = substr( $path, strlen( $home_path ) + 1 );
            }
        }

        return trim( $path, '/' );
    }

    /** @return string */
    private function home_path() {
        $home_path = parse_url( home_url( '/' ), PHP_URL_PATH );
        if ( ! is_string( $home_path ) ) {
            return '';
        }

        $home_path = '/' . trim( $home_path, '/' );
        return '/' === $home_path ? '' : $home_path;
    }

    /** @param string $relative Relative home path. @return string */
    private function strip_enabled_language_prefix( $relative ) {
        $relative = trim( (string) $relative, '/' );
        if ( '' === $relative ) {
            return '';
        }

        $segments = explode( '/', $relative );
        $first    = rawurldecode( $segments[0] );

        if ( ! empty( $this->enabled_language( $first ) ) ) {
            array_shift( $segments );
        }

        return implode( '/', $segments );
    }

    /** @param string $path Relative path. @return string */
    private function safe_route_path( $path ) {
        $path = trim( str_replace( '\\', '/', (string) $path ), '/' );
        if ( '' === $path || false !== strpos( $path, "\0" ) ) {
            return '';
        }

        $safe = array();
        foreach ( explode( '/', $path ) as $segment ) {
            $decoded = rawurldecode( $segment );
            if ( '' === $decoded || '.' === $decoded || '..' === $decoded ) {
                continue;
            }
            $safe[] = $segment;
        }

        return implode( '/', $safe );
    }

    /** @param mixed $code Candidate code. @return array<string,mixed> */
    private function enabled_language( $code ) {
        $code = strtolower( str_replace( '_', '-', trim( (string) $code ) ) );
        if ( ! preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ) {
            return array();
        }

        foreach ( $this->context->enabled_languages() as $language ) {
            if ( isset( $language['code'] ) && $code === $language['code'] ) {
                return $language;
            }
        }

        return array();
    }

    /** @param string $source_url Optional source URL. @return array<string,mixed> */
    private function source_parts( $source_url ) {
        $source_url = trim( (string) $source_url );
        if ( '' === $source_url ) {
            $source_url = null !== $this->original_request_uri
                ? $this->original_request_uri
                : ( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/' );
        }

        $parts = parse_url( $source_url );
        if ( false === $parts ) {
            return array( 'path' => '/', 'external' => true );
        }

        $home_host = parse_url( home_url( '/' ), PHP_URL_HOST );
        $host      = isset( $parts['host'] ) ? $parts['host'] : '';
        $external  = '' !== $host && is_string( $home_host ) && 0 !== strcasecmp( $host, $home_host );

        $parts['external'] = $external;
        if ( ! isset( $parts['path'] ) ) {
            $parts['path'] = '/';
        }

        return $parts;
    }

    /** @param string $query Raw query string. @return string */
    private function safe_query_string( $query ) {
        if ( '' === trim( $query ) ) {
            return '';
        }

        $vars = array();
        parse_str( $query, $vars );

        $blocked = array(
            '_wpnonce',
            '_wp_http_referer',
            'nonce',
            'security',
            'action',
            'wc-ajax',
            'add-to-cart',
            'remove_item',
            'undo_item',
            'order_again',
            'language',
            'lang',
        );

        foreach ( array_keys( $vars ) as $key ) {
            $normalized = strtolower( (string) $key );
            if ( in_array( $normalized, $blocked, true ) || 0 === strpos( $normalized, '_wpnonce' ) ) {
                unset( $vars[ $key ] );
            }
        }

        return http_build_query( $vars, '', '&', PHP_QUERY_RFC3986 );
    }

    /** @return bool */
    private function is_storefront_request() {
        if ( function_exists( 'is_admin' ) && is_admin() ) {
            return false;
        }
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return false;
        }
        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return false;
        }
        if ( function_exists( 'wp_installing' ) && wp_installing() ) {
            return false;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return false;
        }

        return true;
    }
}
