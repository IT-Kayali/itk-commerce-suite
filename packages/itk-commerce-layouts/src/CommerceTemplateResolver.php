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
     * Resolve the profile-selected product-card model. Theme validation remains
     * authoritative for unknown identifiers.
     *
     * @param string $default_model Theme default/current product-card model.
     * @return string
     */
    public function resolve_product_card_model( $default_model ) {
        $profile = $this->active_profile();
        $config  = $this->product_card_config( $profile );
        $model   = ! empty( $config['model'] ) ? sanitize_key( $config['model'] ) : sanitize_key( $default_model );

        /**
         * Filter the profile-resolved product-card model before Theme validation.
         *
         * @param string                   $model   Selected model.
         * @param array<string,mixed>      $config  Product-card configuration.
         * @param array<string,mixed>|null $profile Active profile.
         */
        return sanitize_key( apply_filters( 'itk_commerce_profile_product_card_model', $model, $config, $profile ) );
    }

    /**
     * Merge profile product-card options into Theme defaults. The Theme performs
     * final bounded validation so portable profiles cannot inject arbitrary CSS.
     *
     * @param array<string,mixed> $defaults Theme defaults/current options.
     * @return array<string,mixed>
     */
    public function resolve_product_card_options( $defaults ) {
        $defaults = is_array( $defaults ) ? $defaults : array();
        $profile  = $this->active_profile();
        $config   = $this->product_card_config( $profile );

        if ( empty( $config['options'] ) || ! is_array( $config['options'] ) ) {
            return $defaults;
        }

        $options = array_merge( $defaults, $config['options'] );

        /**
         * Filter profile-resolved product-card options before Theme validation.
         *
         * @param array<string,mixed>      $options Profile options merged with defaults.
         * @param array<string,mixed>|null $profile Active profile.
         */
        $filtered = apply_filters( 'itk_commerce_profile_product_card_options', $options, $profile );

        return is_array( $filtered ) ? $filtered : $defaults;
    }

    /**
     * Merge profile mini-cart presentation options into Theme defaults. The Theme
     * validates all bounded values after this resolver runs.
     *
     * @param array<string,mixed> $defaults Theme defaults/current options.
     * @return array<string,mixed>
     */
    public function resolve_mini_cart_options( $defaults ) {
        $defaults = is_array( $defaults ) ? $defaults : array();
        $profile  = $this->active_profile();
        $config   = $this->mini_cart_config( $profile );

        if ( empty( $config['options'] ) || ! is_array( $config['options'] ) ) {
            return $defaults;
        }

        $options = array_merge( $defaults, $config['options'] );

        /**
         * Filter profile-resolved mini-cart options before Theme validation.
         *
         * @param array<string,mixed>      $options Profile options merged with defaults.
         * @param array<string,mixed>      $config  Mini-cart configuration.
         * @param array<string,mixed>|null $profile Active profile.
         */
        $filtered = apply_filters( 'itk_commerce_profile_mini_cart_options', $options, $config, $profile );

        return is_array( $filtered ) ? $filtered : $defaults;
    }

    /**
     * Merge profile My Account presentation options into Theme defaults. Account
     * endpoints and data remain WooCommerce-owned; only visual choices are read.
     *
     * @param array<string,mixed> $defaults Theme defaults/current options.
     * @return array<string,mixed>
     */
    public function resolve_account_options( $defaults ) {
        $defaults = is_array( $defaults ) ? $defaults : array();
        $profile  = $this->active_profile();
        $config   = $this->account_config( $profile );

        if ( empty( $config['options'] ) || ! is_array( $config['options'] ) ) {
            return $defaults;
        }

        $options = array_merge( $defaults, $config['options'] );

        /**
         * Filter profile-resolved account options before Theme validation.
         *
         * @param array<string,mixed>      $options Profile options merged with defaults.
         * @param array<string,mixed>      $config  Account configuration.
         * @param array<string,mixed>|null $profile Active profile.
         */
        $filtered = apply_filters( 'itk_commerce_profile_account_options', $options, $config, $profile );

        return is_array( $filtered ) ? $filtered : $defaults;
    }

    /**
     * Product-card presentation belongs to the Layouts module configuration
     * namespace so the Phase 2 Commerce page editor can continue saving its
     * `layouts.commerce` page areas without erasing component configuration.
     *
     * @param array<string,mixed>|null $profile Active profile.
     * @return array<string,mixed>
     */
    private function product_card_config( $profile ) {
        if (
            ! is_array( $profile ) ||
            empty( $profile['modules']['configuration'][ MODULE_ID ]['product_card'] ) ||
            ! is_array( $profile['modules']['configuration'][ MODULE_ID ]['product_card'] )
        ) {
            return array();
        }

        return $profile['modules']['configuration'][ MODULE_ID ]['product_card'];
    }

    /**
     * Mini-cart presentation is stored independently from page templates and
     * product-card settings inside the Layouts module namespace.
     *
     * @param array<string,mixed>|null $profile Active profile.
     * @return array<string,mixed>
     */
    private function mini_cart_config( $profile ) {
        if (
            ! is_array( $profile ) ||
            empty( $profile['modules']['configuration'][ MODULE_ID ]['mini_cart'] ) ||
            ! is_array( $profile['modules']['configuration'][ MODULE_ID ]['mini_cart'] )
        ) {
            return array();
        }

        return $profile['modules']['configuration'][ MODULE_ID ]['mini_cart'];
    }

    /**
     * My Account presentation is stored independently from commerce-page and
     * component configuration so updates cannot erase unrelated settings.
     *
     * @param array<string,mixed>|null $profile Active profile.
     * @return array<string,mixed>
     */
    private function account_config( $profile ) {
        if (
            ! is_array( $profile ) ||
            empty( $profile['modules']['configuration'][ MODULE_ID ]['account'] ) ||
            ! is_array( $profile['modules']['configuration'][ MODULE_ID ]['account'] )
        ) {
            return array();
        }

        return $profile['modules']['configuration'][ MODULE_ID ]['account'];
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
