<?php
/**
 * Commerce Search & Filter module definition.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter;

use ITK\Commerce\Core\Contracts\ModuleInterface;
use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class SearchFilterModule implements ModuleInterface {
    /** @var FilterSchema|null */
    private $schema = null;

    /** @var UrlState|null */
    private $url_state = null;

    /** @var WooQueryAdapter|null */
    private $query_adapter = null;

    /** @var FilterRenderer|null */
    private $renderer = null;

    /** @var CatalogAsyncNavigation|null */
    private $async_navigation = null;

    /** @var CatalogNoResultsToolbar|null */
    private $no_results_toolbar = null;

    /** @return string */
    public function id() {
        return MODULE_ID;
    }

    /** @return string */
    public function version() {
        return VERSION;
    }

    /** @return array<string,mixed> */
    public function requirements() {
        return array(
            'core'        => '0.1.0-dev',
            'php'         => '8.1',
            'wordpress'   => '6.6',
            'woocommerce' => null,
            'modules'     => array(),
        );
    }

    /**
     * Build the bounded schema/query layer, progressive server-rendered UI and
     * optional Fetch/History enhancement. Server-rendered GET navigation remains
     * authoritative whenever JavaScript or async navigation is unavailable.
     *
     * @return void
     */
    public function register() {
        if ( null !== $this->schema ) {
            return;
        }

        $this->schema = new FilterSchema();
        $raw          = $this->profile_definitions();

        /**
         * Filter raw profile/default filter definitions before schema validation.
         *
         * @param array<int,array<string,mixed>> $raw Raw filter definitions.
         */
        $raw = apply_filters( 'itk_commerce_search_filter_definitions_raw', $raw );
        $raw = is_array( $raw ) ? $raw : $this->schema->defaults();

        $definitions = $this->schema->normalize( $raw );

        /**
         * Filter already-normalized filter definitions. Consumers must return the
         * same normalized schema shape; the schema validates again afterwards.
         *
         * @param array<int,array<string,mixed>> $definitions Normalized definitions.
         */
        $definitions = apply_filters( 'itk_commerce_search_filter_definitions', $definitions );
        $definitions = $this->schema->normalize( is_array( $definitions ) ? $definitions : array() );

        $this->url_state          = new UrlState( $definitions );
        $this->query_adapter      = new WooQueryAdapter( $this->url_state );
        $this->renderer           = new FilterRenderer( $definitions, $this->url_state );
        $this->async_navigation   = new CatalogAsyncNavigation();
        $this->no_results_toolbar = new CatalogNoResultsToolbar( $this->renderer );

        $this->query_adapter->register();
        $this->renderer->register();
        $this->async_navigation->register();
        $this->no_results_toolbar->register();

        if ( is_admin() ) {
            ( new Admin\FilterBuilderPage( $this->schema ) )->register();
        }

        /**
         * Fires after the Search/Filter services are ready.
         *
         * @param SearchFilterModule     $module Module instance.
         * @param FilterSchema           $schema Schema service.
         * @param UrlState               $url_state URL-state service.
         * @param WooQueryAdapter        $query_adapter Query adapter.
         * @param FilterRenderer         $renderer Progressive filter renderer.
         * @param CatalogAsyncNavigation $async_navigation Fetch/History enhancement.
         */
        do_action( 'itk_commerce_search_filter_loaded', $this, $this->schema, $this->url_state, $this->query_adapter, $this->renderer, $this->async_navigation );
    }

    /** @return array<int,array<string,mixed>> */
    public function definitions() {
        return null !== $this->url_state ? $this->url_state->definitions() : array();
    }

    /** @return UrlState|null */
    public function url_state() {
        return $this->url_state;
    }

    /** @return WooQueryAdapter|null */
    public function query_adapter() {
        return $this->query_adapter;
    }

    /** @return FilterRenderer|null */
    public function renderer() {
        return $this->renderer;
    }

    /** @return CatalogAsyncNavigation|null */
    public function async_navigation() {
        return $this->async_navigation;
    }

    /**
     * Load filter definitions from the active profile while retaining neutral
     * defaults only when the module has never been configured. An explicitly
     * saved empty definition list means the customer intentionally wants no
     * catalog filters and is therefore preserved.
     *
     * @return array<int,array<string,mixed>>
     */
    private function profile_definitions() {
        if ( ! class_exists( '\\ITK\\Commerce\\Core\\Core' ) ) {
            return $this->schema->defaults();
        }

        $core       = Core::instance();
        $profile_id = $core->settings()->active_profile_id();
        $profile    = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        if ( ! is_array( $profile ) ) {
            return $this->schema->defaults();
        }

        $configuration = isset( $profile['modules']['configuration'][ MODULE_ID ] ) && is_array( $profile['modules']['configuration'][ MODULE_ID ] )
            ? $profile['modules']['configuration'][ MODULE_ID ]
            : array();
        $filters = isset( $configuration['filters'] ) && is_array( $configuration['filters'] )
            ? $configuration['filters']
            : array();

        if ( ! array_key_exists( 'definitions', $filters ) || ! is_array( $filters['definitions'] ) ) {
            return $this->schema->defaults();
        }

        return $filters['definitions'];
    }
}
