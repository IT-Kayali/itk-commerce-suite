<?php
/**
 * Virtual language-specific WooCommerce product/taxonomy permalink routing.
 *
 * WordPress/WooCommerce keep the canonical object ID and technical source slug.
 * This service overlays published language slugs at the URL boundary only.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslatedPermalinkService {
    /** @var LanguageContext */
    private $context;

    /** @var LanguageRouter */
    private $router;

    /** @var TranslationRepository */
    private $translations;

    /** @var TranslatedRouteStoreInterface */
    private $routes;

    /** @var bool */
    private $suspend_link_filters = false;

    /** @var array<int,array<string,mixed>> */
    private $resolved_request_routes = array();

    /**
     * @param LanguageContext                $context Request language context.
     * @param LanguageRouter                 $router Directory language router.
     * @param TranslationRepository          $translations Translation repository.
     * @param TranslatedRouteStoreInterface  $routes Route index.
     */
    public function __construct(
        LanguageContext $context,
        LanguageRouter $router,
        TranslationRepository $translations,
        TranslatedRouteStoreInterface $routes
    ) {
        $this->context      = $context;
        $this->router       = $router;
        $this->translations = $translations;
        $this->routes       = $routes;
    }

    /** @return void */
    public function register() {
        add_filter( 'itk_commerce_translation_validate_publish', array( $this, 'validate_translation_publish' ), 20, 3 );
        add_action( 'itk_commerce_translation_published', array( $this, 'synchronize_published_translation' ), 20 );
        add_filter( 'post_type_link', array( $this, 'filter_product_link' ), 20, 4 );
        add_filter( 'term_link', array( $this, 'filter_term_link' ), 20, 3 );
        add_filter( 'request', array( $this, 'filter_request_query_vars' ), 20 );
        add_filter( 'redirect_canonical', array( $this, 'filter_canonical_redirect' ), 20, 2 );
        add_filter( 'itk_commerce_language_url', array( $this, 'filter_language_url' ), 30, 3 );
        add_filter( 'itk_commerce_translated_permalink_service', array( $this, 'filter_service' ) );
        add_filter( 'itk_commerce_translated_route_repository', array( $this, 'filter_route_repository' ) );
    }

    /**
     * Reject invalid/colliding route translations before the translation goes
     * live. Non-route translation keys pass through untouched.
     *
     * @param mixed               $allowed Existing validation result.
     * @param array<string,mixed> $revision Reviewed revision.
     * @param TranslationWorkflow $workflow Translation workflow.
     * @return mixed
     */
    public function validate_translation_publish( $allowed, $revision, $workflow ) {
        if ( false === $allowed || ( function_exists( 'is_wp_error' ) && is_wp_error( $allowed ) ) ) {
            return $allowed;
        }

        if ( ! is_array( $revision ) || ! $workflow instanceof TranslationWorkflow || empty( $revision['entry_id'] ) ) {
            return $allowed;
        }

        $entry = $workflow->repository()->entry_by_id( (int) $revision['entry_id'] );
        if ( ! is_array( $entry ) ) {
            return $allowed;
        }

        $route = $this->parse_translation_key( isset( $entry['translation_key'] ) ? $entry['translation_key'] : '' );
        if ( null === $route ) {
            return $allowed;
        }

        $entity = $this->source_entity( $route );
        if ( null === $entity ) {
            return $this->error( 'translated_route_entity_missing', 'The translated permalink target no longer exists.' );
        }

        $slug = $this->normalize_slug( isset( $revision['translation_value'] ) ? $revision['translation_value'] : '' );
        if ( '' === $slug ) {
            return $this->error( 'translated_slug_empty', 'A translated permalink slug cannot be empty.' );
        }
        if ( strlen( $slug ) > 200 ) {
            return $this->error( 'translated_slug_too_long', 'A translated permalink slug may not exceed the supported WordPress slug length.' );
        }

        $source_collision = $this->validate_source_slug_collision( $route, $slug );
        if ( true !== $source_collision ) {
            return $source_collision;
        }

        return $this->routes->validate_slug(
            isset( $entry['language_code'] ) ? (string) $entry['language_code'] : '',
            $route['entity_type'],
            $route['object_id'],
            $route['taxonomy'],
            $slug
        );
    }

    /**
     * Mirror a successfully published slug revision into the indexed route
     * table. Validation runs before publish; this action is the post-publish
     * route projection and keeps old translated slugs as aliases.
     *
     * @param array<string,mixed> $revision Published revision.
     * @return void
     */
    public function synchronize_published_translation( $revision ) {
        if ( ! is_array( $revision ) || empty( $revision['entry_id'] ) ) {
            return;
        }

        $entry = $this->translations->entry_by_id( (int) $revision['entry_id'] );
        if ( ! is_array( $entry ) ) {
            return;
        }

        $route = $this->parse_translation_key( isset( $entry['translation_key'] ) ? $entry['translation_key'] : '' );
        if ( null === $route ) {
            return;
        }

        $entity = $this->source_entity( $route );
        $slug   = $this->normalize_slug( isset( $revision['translation_value'] ) ? $revision['translation_value'] : '' );
        if ( null === $entity || '' === $slug ) {
            do_action( 'itk_commerce_translated_route_sync_failed', $entry, $revision, $route, 'invalid-entity-or-slug' );
            return;
        }

        $result = $this->routes->publish(
            isset( $entry['language_code'] ) ? (string) $entry['language_code'] : '',
            $route['entity_type'],
            $route['object_id'],
            $route['taxonomy'],
            $entity['source_slug'],
            $slug,
            (string) $entry['translation_key']
        );

        if ( function_exists( 'is_wp_error' ) && is_wp_error( $result ) ) {
            do_action( 'itk_commerce_translated_route_sync_failed', $entry, $revision, $route, $result );
            return;
        }
        if ( false === $result ) {
            do_action( 'itk_commerce_translated_route_sync_failed', $entry, $revision, $route, 'storage-failure' );
            return;
        }

        do_action( 'itk_commerce_translated_route_published', $result, $entry, $revision );
    }

    /**
     * Localize product permalinks without changing post_name.
     *
     * @param string $permalink Existing permalink.
     * @param mixed  $post WP_Post-like product.
     * @param bool   $leavename Whether placeholders should remain.
     * @param bool   $sample Sample permalink flag.
     * @return string
     */
    public function filter_product_link( $permalink, $post, $leavename = false, $sample = false ) {
        unset( $leavename, $sample );

        if ( $this->suspend_link_filters || ! $this->is_link_localization_request() || ! $this->is_product_post( $post ) ) {
            return (string) $permalink;
        }

        return $this->localized_product_permalink( $post, $this->context->code(), (string) $permalink );
    }

    /**
     * Localize supported WooCommerce taxonomy permalinks without changing the
     * stored term slug.
     *
     * @param string $termlink Existing term link.
     * @param mixed  $term WP_Term-like object.
     * @param string $taxonomy Taxonomy.
     * @return string
     */
    public function filter_term_link( $termlink, $term, $taxonomy = '' ) {
        if ( $this->suspend_link_filters || ! $this->is_link_localization_request() || ! $this->is_supported_term( $term, $taxonomy ) ) {
            return (string) $termlink;
        }

        return $this->localized_term_permalink( $term, $this->context->code(), (string) $termlink );
    }

    /**
     * Resolve translated public slugs back to the technical WordPress/WooCommerce
     * query vars after rewrite matching. The object IDs/source slugs remain the
     * persisted identity.
     *
     * @param mixed $query_vars Parsed query vars.
     * @return mixed
     */
    public function filter_request_query_vars( $query_vars ) {
        if ( ! is_array( $query_vars ) || ! $this->is_route_resolution_request() ) {
            return $query_vars;
        }

        $this->resolved_request_routes = array();
        $code                          = $this->context->code();

        if ( isset( $query_vars['product'] ) && is_scalar( $query_vars['product'] ) ) {
            $query_vars['product'] = $this->resolve_product_query_slug( (string) $query_vars['product'], 'product' );
        }

        if ( isset( $query_vars['post_type'], $query_vars['name'] ) && 'product' === (string) $query_vars['post_type'] && is_scalar( $query_vars['name'] ) ) {
            $query_vars['name'] = $this->resolve_product_query_slug( (string) $query_vars['name'], 'name' );
        }

        foreach ( $query_vars as $query_var => $value ) {
            if ( ! is_string( $query_var ) || ! $this->is_supported_taxonomy( $query_var ) || ! is_scalar( $value ) ) {
                continue;
            }

            $query_vars[ $query_var ] = $this->resolve_term_query_path( $code, $query_var, (string) $value );
        }

        return $query_vars;
    }

    /**
     * Keep a valid translated URL canonical and redirect historical translated
     * aliases to the current translated slug rather than back to source slugs.
     *
     * @param mixed  $redirect_url WordPress proposed canonical redirect.
     * @param string $requested_url Requested URL.
     * @return mixed
     */
    public function filter_canonical_redirect( $redirect_url, $requested_url = '' ) {
        if ( empty( $this->resolved_request_routes ) ) {
            return $redirect_url;
        }

        $primary = end( $this->resolved_request_routes );
        if ( ! is_array( $primary ) ) {
            return $redirect_url;
        }

        $target = $this->permalink_for_resolved_route( $primary, $this->context->code() );
        if ( '' === $target ) {
            return $redirect_url;
        }

        if ( is_string( $redirect_url ) && '' !== $redirect_url ) {
            $target = $this->copy_query_fragment( $target, $redirect_url );
        }

        $requested = '' !== (string) $requested_url ? (string) $requested_url : $this->current_request_url();
        if ( $this->urls_equivalent( $target, $requested ) ) {
            return false;
        }

        return $target;
    }

    /**
     * Upgrade the generic same-path language URL to the target language's
     * translated entity permalink when the current request has a supported
     * queried WooCommerce entity.
     *
     * @param mixed  $url Generic LanguageRouter URL.
     * @param string $code Target language.
     * @param string $source_url Optional source URL.
     * @return string
     */
    public function filter_language_url( $url, $code, $source_url = '' ) {
        unset( $source_url );

        $code = $this->normalize_enabled_code( $code );
        if ( '' === $code || ! $this->is_link_localization_request() ) {
            return (string) $url;
        }

        $object = function_exists( 'get_queried_object' ) ? get_queried_object() : null;
        $target = '';

        if ( $this->is_product_post( $object ) ) {
            $target = $this->localized_product_permalink( $object, $code );
        } elseif ( $this->is_supported_term( $object, is_object( $object ) && isset( $object->taxonomy ) ? (string) $object->taxonomy : '' ) ) {
            $target = $this->localized_term_permalink( $object, $code );
        }

        if ( '' === $target ) {
            return (string) $url;
        }

        return $this->copy_query_fragment( $target, is_string( $url ) ? $url : '' );
    }

    /**
     * @param mixed $post Product post.
     * @param string $language_code Target language.
     * @param string $base_permalink Optional unlocalized technical permalink.
     * @return string
     */
    public function localized_product_permalink( $post, $language_code, $base_permalink = '' ) {
        if ( ! $this->is_product_post( $post ) ) {
            return '';
        }

        $code = $this->normalize_enabled_code( $language_code );
        if ( '' === $code ) {
            return '';
        }

        $post_id     = (int) $post->ID;
        $source_slug = isset( $post->post_name ) ? (string) $post->post_name : '';
        $target_slug = $this->translated_slug_or_source( $code, 'product', $post_id, '', $source_slug );
        $base        = '' !== $base_permalink ? $base_permalink : $this->raw_product_permalink( $post );
        if ( '' === $base ) {
            return '';
        }

        $replacements = array( $source_slug => $target_slug );
        foreach ( $this->product_category_terms( $post_id ) as $term ) {
            if ( ! is_object( $term ) || empty( $term->term_id ) || empty( $term->slug ) ) {
                continue;
            }
            $replacements[ (string) $term->slug ] = $this->translated_slug_or_source(
                $code,
                'term',
                (int) $term->term_id,
                'product_cat',
                (string) $term->slug
            );
        }

        $localized = $this->replace_path_slugs( $base, $replacements );
        return $this->router->url_for( $code, $localized );
    }

    /**
     * @param mixed  $term Term object.
     * @param string $language_code Target language.
     * @param string $base_permalink Optional unlocalized technical term link.
     * @return string
     */
    public function localized_term_permalink( $term, $language_code, $base_permalink = '' ) {
        $taxonomy = is_object( $term ) && isset( $term->taxonomy ) ? (string) $term->taxonomy : '';
        if ( ! $this->is_supported_term( $term, $taxonomy ) ) {
            return '';
        }

        $code = $this->normalize_enabled_code( $language_code );
        if ( '' === $code ) {
            return '';
        }

        $base = '' !== $base_permalink ? $base_permalink : $this->raw_term_permalink( $term );
        if ( '' === $base ) {
            return '';
        }

        $replacements = array();
        foreach ( $this->term_hierarchy( $term ) as $part ) {
            if ( ! is_object( $part ) || empty( $part->term_id ) || empty( $part->slug ) ) {
                continue;
            }
            $replacements[ (string) $part->slug ] = $this->translated_slug_or_source(
                $code,
                'term',
                (int) $part->term_id,
                $taxonomy,
                (string) $part->slug
            );
        }

        $localized = $this->replace_path_slugs( $base, $replacements );
        return $this->router->url_for( $code, $localized );
    }

    /**
     * Stable route translation key parser.
     *
     * @param mixed $key Translation key.
     * @return array{entity_type:string,object_id:int,taxonomy:string}|null
     */
    public function parse_translation_key( $key ) {
        $key = strtolower( trim( (string) $key ) );

        if ( preg_match( '/^woocommerce\.product\.(\d+)\.slug$/', $key, $matches ) ) {
            return array(
                'entity_type' => 'product',
                'object_id'   => (int) $matches[1],
                'taxonomy'    => '',
            );
        }

        if ( preg_match( '/^woocommerce\.term\.([a-z0-9_-]+)\.(\d+)\.slug$/', $key, $matches ) && $this->is_supported_taxonomy( $matches[1] ) ) {
            return array(
                'entity_type' => 'term',
                'object_id'   => (int) $matches[2],
                'taxonomy'    => (string) $matches[1],
            );
        }

        return null;
    }

    /** @param mixed $service Existing service. @return TranslatedPermalinkService */
    public function filter_service( $service ) {
        unset( $service );
        return $this;
    }

    /** @param mixed $repository Existing repository. @return TranslatedRouteStoreInterface */
    public function filter_route_repository( $repository ) {
        unset( $repository );
        return $this->routes;
    }

    /** @param string $input Product query slug. @param string $query_var Query variable. @return string */
    private function resolve_product_query_slug( $input, $query_var ) {
        $slug  = $this->normalize_slug( $input );
        $route = $this->routes->resolve( $this->context->code(), 'product', '', $slug );
        if ( ! is_array( $route ) || empty( $route['source_slug'] ) ) {
            return $input;
        }

        $route['query_var']     = $query_var;
        $route['incoming_slug'] = $slug;
        $this->resolved_request_routes[] = $route;
        return (string) $route['source_slug'];
    }

    /** @param string $code Language. @param string $taxonomy Taxonomy. @param string $path Query path. @return string */
    private function resolve_term_query_path( $code, $taxonomy, $path ) {
        $segments = explode( '/', trim( $path, '/' ) );
        if ( empty( $segments ) ) {
            return $path;
        }

        $changed = false;
        foreach ( $segments as $index => $segment ) {
            $slug  = $this->normalize_slug( $segment );
            $route = $this->routes->resolve( $code, 'term', $taxonomy, $slug );
            if ( ! is_array( $route ) || empty( $route['source_slug'] ) ) {
                continue;
            }

            $segments[ $index ]      = (string) $route['source_slug'];
            $route['query_var']      = $taxonomy;
            $route['incoming_slug']  = $slug;
            $this->resolved_request_routes[] = $route;
            $changed                 = true;
        }

        return $changed ? implode( '/', $segments ) : $path;
    }

    /** @param array<string,mixed> $route Resolved route. @param string $language_code Language. @return string */
    private function permalink_for_resolved_route( array $route, $language_code ) {
        $object_id = isset( $route['object_id'] ) ? (int) $route['object_id'] : 0;
        $type      = isset( $route['entity_type'] ) ? (string) $route['entity_type'] : '';

        if ( 'product' === $type && function_exists( 'get_post' ) ) {
            $post = get_post( $object_id );
            return $this->localized_product_permalink( $post, $language_code );
        }

        if ( 'term' === $type && function_exists( 'get_term' ) ) {
            $taxonomy = isset( $route['taxonomy'] ) ? (string) $route['taxonomy'] : '';
            $term     = get_term( $object_id, $taxonomy );
            return $this->localized_term_permalink( $term, $language_code );
        }

        return '';
    }

    /** @param array<string,mixed> $route Parsed route key. @return array{source_slug:string,object:mixed}|null */
    private function source_entity( array $route ) {
        if ( 'product' === $route['entity_type'] && function_exists( 'get_post' ) ) {
            $post = get_post( (int) $route['object_id'] );
            if ( $this->is_product_post( $post ) && ! empty( $post->post_name ) ) {
                return array( 'source_slug' => (string) $post->post_name, 'object' => $post );
            }
            return null;
        }

        if ( 'term' === $route['entity_type'] && function_exists( 'get_term' ) ) {
            $term = get_term( (int) $route['object_id'], (string) $route['taxonomy'] );
            if ( $this->is_supported_term( $term, (string) $route['taxonomy'] ) && ! empty( $term->slug ) ) {
                return array( 'source_slug' => (string) $term->slug, 'object' => $term );
            }
        }

        return null;
    }

    /** @param array<string,mixed> $route Parsed route key. @param string $slug Candidate translated slug. @return true|\WP_Error|false */
    private function validate_source_slug_collision( array $route, $slug ) {
        if ( 'product' === $route['entity_type'] && function_exists( 'get_page_by_path' ) ) {
            $object_mode = defined( 'OBJECT' ) ? OBJECT : 'OBJECT';
            $existing    = get_page_by_path( $slug, $object_mode, 'product' );
            if ( is_object( $existing ) && isset( $existing->ID ) && (int) $existing->ID !== (int) $route['object_id'] ) {
                return $this->error( 'translated_slug_source_conflict', 'The translated product slug conflicts with another product source slug.' );
            }
        }

        if ( 'term' === $route['entity_type'] && function_exists( 'get_term_by' ) ) {
            $existing = get_term_by( 'slug', $slug, (string) $route['taxonomy'] );
            if ( is_object( $existing ) && isset( $existing->term_id ) && (int) $existing->term_id !== (int) $route['object_id'] ) {
                return $this->error( 'translated_slug_source_conflict', 'The translated term slug conflicts with another technical term slug.' );
            }
        }

        return true;
    }

    /** @param string $code Language. @param string $type Entity type. @param int $id ID. @param string $taxonomy Taxonomy. @param string $source_slug Source slug. @return string */
    private function translated_slug_or_source( $code, $type, $id, $taxonomy, $source_slug ) {
        $route = $this->routes->current( $code, $type, $id, $taxonomy );
        if ( is_array( $route ) && ! empty( $route['translated_slug'] ) ) {
            return (string) $route['translated_slug'];
        }
        return (string) $source_slug;
    }

    /** @param string $url URL. @param array<string,string> $replacements Source => translated slug. @return string */
    private function replace_path_slugs( $url, array $replacements ) {
        if ( '' === $url || empty( $replacements ) ) {
            return $url;
        }

        $path = parse_url( $url, PHP_URL_PATH );
        if ( ! is_string( $path ) || '' === $path ) {
            return $url;
        }

        $segments = explode( '/', $path );
        foreach ( $segments as $index => $segment ) {
            if ( '' === $segment ) {
                continue;
            }
            $decoded = rawurldecode( $segment );
            foreach ( $replacements as $source => $target ) {
                if ( $segment === $source || $decoded === rawurldecode( $source ) ) {
                    $segments[ $index ] = $target;
                    break;
                }
            }
        }

        $new_path = implode( '/', $segments );
        $position = strpos( $url, $path );
        return false === $position ? $url : substr_replace( $url, $new_path, $position, strlen( $path ) );
    }

    /** @param mixed $post Product post. @return string */
    private function raw_product_permalink( $post ) {
        if ( ! function_exists( 'get_permalink' ) ) {
            return '';
        }

        $this->suspend_link_filters = true;
        try {
            $url = get_permalink( $post );
        } finally {
            $this->suspend_link_filters = false;
        }
        return is_string( $url ) ? $url : '';
    }

    /** @param mixed $term Term. @return string */
    private function raw_term_permalink( $term ) {
        if ( ! function_exists( 'get_term_link' ) ) {
            return '';
        }

        $this->suspend_link_filters = true;
        try {
            $url = get_term_link( $term );
        } finally {
            $this->suspend_link_filters = false;
        }

        if ( function_exists( 'is_wp_error' ) && is_wp_error( $url ) ) {
            return '';
        }
        return is_string( $url ) ? $url : '';
    }

    /** @param int $post_id Product ID. @return array<int,mixed> */
    private function product_category_terms( $post_id ) {
        if ( ! function_exists( 'get_the_terms' ) ) {
            return array();
        }

        $terms = get_the_terms( $post_id, 'product_cat' );
        if ( ! is_array( $terms ) ) {
            return array();
        }

        $all = array();
        foreach ( $terms as $term ) {
            foreach ( $this->term_hierarchy( $term ) as $part ) {
                if ( is_object( $part ) && isset( $part->term_id ) ) {
                    $all[ (int) $part->term_id ] = $part;
                }
            }
        }
        return array_values( $all );
    }

    /** @param mixed $term Term. @return array<int,mixed> */
    private function term_hierarchy( $term ) {
        if ( ! is_object( $term ) || empty( $term->term_id ) || empty( $term->taxonomy ) ) {
            return array();
        }

        $parts = array();
        if ( function_exists( 'get_ancestors' ) && function_exists( 'get_term' ) ) {
            $ancestors = get_ancestors( (int) $term->term_id, (string) $term->taxonomy, 'taxonomy' );
            if ( is_array( $ancestors ) ) {
                foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
                    $ancestor = get_term( (int) $ancestor_id, (string) $term->taxonomy );
                    if ( is_object( $ancestor ) ) {
                        $parts[] = $ancestor;
                    }
                }
            }
        }
        $parts[] = $term;
        return $parts;
    }

    /** @param mixed $post Candidate post. @return bool */
    private function is_product_post( $post ) {
        return is_object( $post ) && isset( $post->ID, $post->post_type ) && 'product' === (string) $post->post_type;
    }

    /** @param mixed $term Candidate term. @param string $taxonomy Taxonomy. @return bool */
    private function is_supported_term( $term, $taxonomy ) {
        $taxonomy = '' !== $taxonomy ? $taxonomy : ( is_object( $term ) && isset( $term->taxonomy ) ? (string) $term->taxonomy : '' );
        return is_object( $term ) && isset( $term->term_id, $term->slug ) && $this->is_supported_taxonomy( $taxonomy );
    }

    /** @param mixed $taxonomy Taxonomy. @return bool */
    private function is_supported_taxonomy( $taxonomy ) {
        $taxonomy = strtolower( trim( (string) $taxonomy ) );
        return in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) || 0 === strpos( $taxonomy, 'pa_' );
    }

    /** @param mixed $value Slug value. @return string */
    private function normalize_slug( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }

        if ( function_exists( 'sanitize_title' ) ) {
            return trim( (string) sanitize_title( $value ), '/' );
        }

        $value = strtolower( $value );
        $value = preg_replace( '/\s+/', '-', $value );
        return trim( (string) preg_replace( '/[^a-z0-9%_-]+/', '-', (string) $value ), '-/' );
    }

    /** @param mixed $code Candidate code. @return string */
    private function normalize_enabled_code( $code ) {
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

    /** @return bool */
    private function is_link_localization_request() {
        if ( function_exists( 'is_admin' ) && is_admin() ) {
            return false;
        }
        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return false;
        }
        if ( function_exists( 'wp_installing' ) && wp_installing() ) {
            return false;
        }

        $async = ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
        if ( ! $async ) {
            return true;
        }

        return function_exists( 'apply_filters' ) && (bool) apply_filters( 'itk_commerce_allow_async_translation_mapping', false );
    }

    /** @return bool */
    private function is_route_resolution_request() {
        if ( function_exists( 'is_admin' ) && is_admin() ) {
            return false;
        }
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return false;
        }
        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return false;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return false;
        }
        return true;
    }

    /** @param string $target Base target URL. @param string $source Source URL for query/fragment. @return string */
    private function copy_query_fragment( $target, $source ) {
        if ( '' === $target || '' === $source ) {
            return $target;
        }

        $query    = parse_url( $source, PHP_URL_QUERY );
        $fragment = parse_url( $source, PHP_URL_FRAGMENT );

        if ( is_string( $query ) && '' !== $query ) {
            $target .= ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query;
        }
        if ( is_string( $fragment ) && '' !== $fragment ) {
            $target .= '#' . $fragment;
        }
        return $target;
    }

    /** @return string */
    private function current_request_url() {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
        if ( function_exists( 'home_url' ) ) {
            return home_url( $uri );
        }
        return $uri;
    }

    /** @param string $a First URL. @param string $b Second URL. @return bool */
    private function urls_equivalent( $a, $b ) {
        return rtrim( (string) $a, '/' ) === rtrim( (string) $b, '/' );
    }

    /** @param string $code Error code. @param string $message Error message. @return \WP_Error|false */
    private function error( $code, $message ) {
        if ( class_exists( '\WP_Error' ) ) {
            return new \WP_Error( $code, $message );
        }
        return false;
    }
}
