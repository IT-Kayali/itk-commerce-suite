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
     * Build the bounded filter schema, attach the WooCommerce query adapter and
     * register a progressive server-rendered filter UI. AJAX remains an optional
     * enhancement in the next isolated Phase 4 slice.
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

        $this->url_state     = new UrlState( $definitions );
        $this->query_adapter = new WooQueryAdapter( $this->url_state );
        $this->renderer      = new FilterRenderer( $definitions, $this->url_state );

        $this->query_adapter->register();
        $this->renderer->register();

        /**
         * Fires after the Search/Filter foundation and progressive UI are ready.
         *
         * @param SearchFilterModule $module Module instance.
         * @param FilterSchema       $schema Schema service.
         * @param UrlState           $url_state URL-state service.
         * @param WooQueryAdapter    $query_adapter Query adapter.
         * @param FilterRenderer     $renderer Progressive filter renderer.
         */
        do_action( 'itk_commerce_search_filter_loaded', $this, $this->schema, $this->url_state, $this->query_adapter, $this->renderer );
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

    /**
     * Load filter definitions from the active profile while retaining neutral
     * defaults when the module has not yet been configured for that customer.
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

        if (
            ! is_array( $profile ) ||
            empty( $profile['modules']['configuration'][ MODULE_ID ]['filters']['definitions'] ) ||
            ! is_array( $profile['modules']['configuration'][ MODULE_ID ]['filters']['definitions'] )
        ) {
            return $this->schema->defaults();
        }

        return $profile['modules']['configuration'][ MODULE_ID ]['filters']['definitions'];
    }
}
