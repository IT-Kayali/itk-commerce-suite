<?php
/**
 * Language-aware canonical and hreflang contracts.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class MultilingualSeo {
    /** @var LanguageContext */
    private $context;

    /** @var LanguageRouter */
    private $router;

    /**
     * @param LanguageContext $context Current Commerce language context.
     * @param LanguageRouter  $router Directory-language router.
     */
    public function __construct( LanguageContext $context, LanguageRouter $router ) {
        $this->context = $context;
        $this->router  = $router;
    }

    /** @return void */
    public function register() {
        add_filter( 'get_canonical_url', array( $this, 'filter_singular_canonical' ), 20, 2 );
        add_action( 'wp_head', array( $this, 'render_head_links' ), 9 );
        add_filter( 'itk_commerce_multilingual_canonical_url', array( $this, 'filter_canonical_url' ), 10, 2 );
        add_filter( 'itk_commerce_multilingual_alternate_urls', array( $this, 'filter_alternate_urls' ), 10, 2 );
    }

    /**
     * Localize WordPress Core's singular canonical URL. WordPress's own
     * rel_canonical() output remains authoritative for singular pages.
     *
     * @param string $canonical_url Existing WordPress canonical URL.
     * @param mixed  $post Current post object.
     * @return string
     */
    public function filter_singular_canonical( $canonical_url, $post = null ) {
        if ( ! $this->is_indexable_request() || ! $this->is_current_post( $post ) ) {
            return (string) $canonical_url;
        }

        $localized = $this->canonical_url();
        return '' !== $localized ? $localized : (string) $canonical_url;
    }

    /**
     * Return the canonical URL for the current route with all query and fragment
     * state removed. Entity-aware permalink services may replace the generic
     * same-path URL with the current language's translated entity slug.
     *
     * @param string $language_code Optional explicit target language.
     * @return string
     */
    public function canonical_url( $language_code = '' ) {
        if ( ! $this->is_indexable_request() ) {
            return '';
        }

        $code = $this->enabled_code( $language_code );
        if ( '' === $code ) {
            $code = $this->context->code();
        }

        $source = $this->current_path();
        if ( '' === $source ) {
            $source = '/';
        }

        $url = $this->language_url( $code, $source );
        return (string) apply_filters( 'itk_commerce_multilingual_canonical_target', $url, $code, $source );
    }

    /**
     * Build hreflang alternatives for every enabled language plus x-default.
     * URLs intentionally use only the current path, never the current query
     * string, so tracking/filter/action parameters cannot create SEO variants.
     * Entity-aware URL filters replace the current slug with the target
     * language's own product/taxonomy slug where available.
     *
     * @return array<int,array{hreflang:string,code:string,url:string,current:bool,x_default:bool}>
     */
    public function alternate_urls() {
        if ( ! $this->is_indexable_request() ) {
            return array();
        }

        $source = $this->current_path();
        if ( '' === $source ) {
            $source = '/';
        }

        $items = array();
        foreach ( $this->context->enabled_languages() as $language ) {
            if ( empty( $language['code'] ) ) {
                continue;
            }

            $code = $this->enabled_code( $language['code'] );
            if ( '' === $code ) {
                continue;
            }

            $url = $this->language_url( $code, $source );
            if ( '' === $url ) {
                continue;
            }

            $items[] = array(
                'hreflang'  => $this->hreflang_code( $code ),
                'code'      => $code,
                'url'       => $url,
                'current'   => $code === $this->context->code(),
                'x_default' => false,
            );
        }

        $default_code = $this->context->default_code();
        $default_url  = $this->language_url( $default_code, $source );
        if ( '' !== $default_url ) {
            $items[] = array(
                'hreflang'  => 'x-default',
                'code'      => $default_code,
                'url'       => $default_url,
                'current'   => false,
                'x_default' => true,
            );
        }

        $filtered = apply_filters( 'itk_commerce_multilingual_hreflang_targets', $items, $source, $this->context );
        return is_array( $filtered ) ? array_values( $filtered ) : $items;
    }

    /**
     * Print hreflang links for every indexable localized route. Core already
     * prints singular canonicals, so this method prints its own canonical only
     * for non-singular views where Core rel_canonical() does not.
     *
     * @return void
     */
    public function render_head_links() {
        if ( ! $this->is_indexable_request() ) {
            return;
        }

        $manage = apply_filters( 'itk_commerce_multilingual_manage_head_links', true, $this );
        if ( ! $manage ) {
            return;
        }

        if ( ! $this->is_singular_request() ) {
            $canonical = $this->canonical_url();
            $render    = apply_filters( 'itk_commerce_multilingual_render_canonical', true, $canonical, $this );
            if ( $render && '' !== $canonical ) {
                echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
            }
        }

        $render_hreflang = apply_filters( 'itk_commerce_multilingual_render_hreflang', true, $this );
        if ( ! $render_hreflang ) {
            return;
        }

        foreach ( $this->alternate_urls() as $item ) {
            if ( ! is_array( $item ) || empty( $item['hreflang'] ) || empty( $item['url'] ) ) {
                continue;
            }

            echo '<link rel="alternate" hreflang="' . esc_attr( (string) $item['hreflang'] ) . '" href="' . esc_url( (string) $item['url'] ) . '" />' . "\n";
        }
    }

    /**
     * Public canonical filter callback.
     *
     * @param mixed  $url Existing URL.
     * @param string $language_code Optional target language.
     * @return string
     */
    public function filter_canonical_url( $url, $language_code = '' ) {
        unset( $url );
        return $this->canonical_url( $language_code );
    }

    /**
     * Public alternate-URL filter callback.
     *
     * @param mixed $items Existing items.
     * @param mixed $context Optional caller context.
     * @return array<int,array<string,mixed>>
     */
    public function filter_alternate_urls( $items, $context = null ) {
        unset( $items, $context );
        return $this->alternate_urls();
    }

    /** @return bool */
    public function is_indexable_request() {
        if ( function_exists( 'is_admin' ) && is_admin() ) {
            return false;
        }
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return false;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return false;
        }
        if ( function_exists( 'is_404' ) && is_404() ) {
            return false;
        }
        if ( function_exists( 'is_search' ) && is_search() ) {
            return false;
        }
        if ( function_exists( 'is_feed' ) && is_feed() ) {
            return false;
        }
        if ( function_exists( 'is_trackback' ) && is_trackback() ) {
            return false;
        }
        if ( function_exists( 'is_preview' ) && is_preview() ) {
            return false;
        }

        return true;
    }

    /** @return string */
    public function current_path() {
        $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url( $uri, PHP_URL_PATH );
        if ( ! is_string( $path ) || '' === $path ) {
            return '/';
        }

        return '/' . ltrim( $path, '/' );
    }

    /** @param string $code Target language. @param string $source Source path/URL. @return string */
    private function language_url( $code, $source ) {
        $url = function_exists( 'apply_filters' )
            ? apply_filters( 'itk_commerce_language_url', '', $code, $source )
            : $this->router->url_for( $code, $source );

        if ( ! is_string( $url ) || '' === $url ) {
            $url = $this->router->url_for( $code, $source );
        }
        return is_string( $url ) ? $url : '';
    }

    /** @param mixed $code Candidate language code. @return string */
    private function enabled_code( $code ) {
        $code = strtolower( str_replace( '_', '-', trim( (string) $code ) ) );
        if ( ! preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ) {
            return '';
        }

        foreach ( $this->context->enabled_languages() as $language ) {
            if ( isset( $language['code'] ) && $code === (string) $language['code'] ) {
                return $code;
            }
        }

        return '';
    }

    /** @param string $code Public language code. @return string */
    private function hreflang_code( $code ) {
        $segments = explode( '-', strtolower( $code ) );
        if ( count( $segments ) > 1 && 2 === strlen( $segments[1] ) && ctype_alpha( $segments[1] ) ) {
            $segments[1] = strtoupper( $segments[1] );
        }
        return implode( '-', $segments );
    }

    /** @return bool */
    private function is_singular_request() {
        return function_exists( 'is_singular' ) && is_singular();
    }

    /** @param mixed $post Post passed to get_canonical_url filter. @return bool */
    private function is_current_post( $post ) {
        if ( ! $this->is_singular_request() ) {
            return false;
        }

        if ( ! is_object( $post ) || ! isset( $post->ID ) || ! function_exists( 'get_queried_object_id' ) ) {
            return true;
        }

        return (int) $post->ID === (int) get_queried_object_id();
    }
}
