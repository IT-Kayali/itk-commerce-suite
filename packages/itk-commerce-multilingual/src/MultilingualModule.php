<?php
/**
 * Commerce Multilingual module definition.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

use ITK\Commerce\Core\Contracts\ModuleInterface;
use ITK\Commerce\Core\Core;

defined( 'ABSPATH' ) || exit;

final class MultilingualModule implements ModuleInterface {
    /** @var LanguageSchema|null */
    private $schema = null;

    /** @var LanguageContext|null */
    private $context = null;

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
            'core'      => '0.1.0-dev',
            'php'       => '8.1',
            'wordpress' => '6.6',
            'modules'   => array(),
        );
    }

    /**
     * Load normalized active-profile language configuration and expose request
     * language/direction contracts. URL routing and translation storage remain
     * isolated follow-up slices.
     *
     * @return void
     */
    public function register() {
        if ( null !== $this->schema ) {
            return;
        }

        $this->schema = new LanguageSchema();
        $raw          = $this->profile_config();

        /**
         * Filter raw profile language configuration before schema normalization.
         *
         * @param array<string,mixed> $raw Raw configuration.
         */
        $raw = apply_filters( 'itk_commerce_multilingual_config_raw', $raw );
        $raw = is_array( $raw ) ? $raw : $this->schema->defaults();

        $config = $this->schema->normalize( $raw );

        /**
         * Filter normalized language configuration. It is normalized again after
         * integrations return so the public contract remains bounded.
         *
         * @param array<string,mixed> $config Normalized configuration.
         */
        $config = apply_filters( 'itk_commerce_multilingual_config', $config );
        $config = $this->schema->normalize( is_array( $config ) ? $config : array() );

        $this->context = new LanguageContext( $config );
        $this->context->register();

        /**
         * Fires after the multilingual foundation is available.
         *
         * @param MultilingualModule $module Module instance.
         * @param LanguageSchema     $schema Language schema.
         * @param LanguageContext    $context Request language context.
         * @param array<string,mixed> $config Normalized active config.
         */
        do_action( 'itk_commerce_multilingual_loaded', $this, $this->schema, $this->context, $config );
    }

    /** @return LanguageSchema|null */
    public function schema() {
        return $this->schema;
    }

    /** @return LanguageContext|null */
    public function context() {
        return $this->context;
    }

    /**
     * @return array<string,mixed>
     */
    private function profile_config() {
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

        if ( empty( $configuration['languages'] ) || ! is_array( $configuration['languages'] ) ) {
            return $this->schema->defaults();
        }

        return $configuration['languages'];
    }
}
