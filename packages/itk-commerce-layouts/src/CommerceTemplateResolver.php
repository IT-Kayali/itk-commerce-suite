<?php
/**
 * Resolve profile-driven WooCommerce page models and visual options.
 *
 * @package ITK_Commerce_Layouts
 */

namespace ITK\Commerce\Layouts;

use ITK\Commerce\Core\Core;
defined( 'ABSPATH' ) || exit;

final class CommerceTemplateResolver {
    /** @var array<string,mixed>|null|false */
    private $profile = false;

    /**
     * Resolve the configured model for one commerce page area.
     * Theme validation remains authoritative for unknown model identifiers.
     *
     * @param string $default_model Theme default/current model.
     * @param string $area          shop/product/cart/checkout.
     * @return string
     */
    public function resolve_model( $default_model, $area ) {
        $area    = sanitize_key( $area );
        $profile = $this->active_profile();

        if ( ! is_array( $profile ) || empty( $profile['layouts']['commerce'][ $area ] ) || ! is_array( $profile['layouts']['commerce'][ $area ] ) ) {
            return sanitize_key( $default_model );
        }

        $config = $profile['layouts']['commerce'][ $area ];
        $model  = ! empty( $config['model'] ) ? sanitize_key( $config['model'] ) : sanitize_key( $default_model );

        /**
         * Filter the profile-resolved commerce model before Theme validation.
         *
         * @param string                   $model   Selected model.
         * @param string                   $area    Commerce area.
         * @param array<string,mixed>      $config  Area configuration.
         * @param array<string,mixed>|null $profile Active profile.
         */
        return sanitize_key( apply_filters( 'itk_commerce_profile_template_model', $model, $area, $config, $profile ) );
    }

    /**
     * Merge profile visual options into Theme defaults. The Theme's option
     * contract performs the final bounded validation.
     *
     * @param array<string,mixed> $defaults Theme defaults/current options.
     * @param string              $area     Commerce area.
     * @return array<string,mixed>
     */
    public function resolve_options( $defaults, $area ) {
        $defaults = is_array( $defaults ) ? $defaults : array();
        $area     = sanitize_key( $area );
        $profile  = $this->active_profile();

        if ( ! is_array( $profile ) || empty( $profile['layouts']['commerce'][ $area ]['options'] ) || ! is_array( $profile['layouts']['commerce'][ $area ]['options'] ) ) {
            return $defaults;
        }

        $options = array_merge( $defaults, $profile['layouts']['commerce'][ $area ]['options'] );

        /**
         * Filter profile-resolved commerce options before Theme validation.
         *
         * @param array<string,mixed>      $options Profile options merged with defaults.
         * @param string                   $area    Commerce area.
         * @param array<string,mixed>|null $profile Active profile.
         */
        $filtered = apply_filters( 'itk_commerce_profile_template_options', $options, $area, $profile );

        return is_array( $filtered ) ? $filtered : $defaults;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function active_profile() {
        if ( false !== $this->profile ) {
            return is_array( $this->profile ) ? $this->profile : null;
        }

        $core          = Core::instance();
        $profile_id    = $core->settings()->active_profile_id();
        $this->profile = $profile_id ? $core->profiles()->get( $profile_id ) : null;

        return is_array( $this->profile ) ? $this->profile : null;
    }
}
