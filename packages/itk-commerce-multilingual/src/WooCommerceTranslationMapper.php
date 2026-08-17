<?php
/**
 * Read-only WooCommerce product/taxonomy translation mapping.
 *
 * Commercial identity remains WooCommerce-owned; this adapter replaces only
 * customer-facing textual view values through the published translation store.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class WooCommerceTranslationMapper {
    /** @var callable */
    private $translator;

    /** @var LanguageContext */
    private $context;

    /**
     * @param callable        $translator fn( string $key, string $source, string $language_code ): string.
     * @param LanguageContext $context Current request language context.
     */
    public function __construct( $translator, LanguageContext $context ) {
        $this->translator = is_callable( $translator ) ? $translator : static function ( $key, $source ) {
            unset( $key );
            return (string) $source;
        };
        $this->context = $context;
    }

    /** @return void */
    public function register() {
        add_filter( 'woocommerce_product_get_name', array( $this, 'product_name' ), 20, 2 );
        add_filter( 'woocommerce_product_get_description', array( $this, 'product_description' ), 20, 2 );
        add_filter( 'woocommerce_product_get_short_description', array( $this, 'product_short_description' ), 20, 2 );
        add_filter( 'woocommerce_product_variation_get_name', array( $this, 'product_name' ), 20, 2 );
        add_filter( 'woocommerce_product_variation_get_description', array( $this, 'product_description' ), 20, 2 );
        add_filter( 'woocommerce_product_variation_get_short_description', array( $this, 'product_short_description' ), 20, 2 );
        add_filter( 'get_term', array( $this, 'term' ), 20, 2 );
        add_filter( 'woocommerce_attribute_label', array( $this, 'attribute_label' ), 20, 3 );
        add_filter( 'itk_commerce_woocommerce_translation_mapper', array( $this, 'filter_mapper' ) );
    }

    /** @param mixed $value Product name. @param mixed $product WC_Product-like object. @return mixed */
    public function product_name( $value, $product ) {
        return $this->product_field( $value, $product, 'name' );
    }

    /** @param mixed $value Product description. @param mixed $product WC_Product-like object. @return mixed */
    public function product_description( $value, $product ) {
        return $this->product_field( $value, $product, 'description' );
    }

    /** @param mixed $value Product short description. @param mixed $product WC_Product-like object. @return mixed */
    public function product_short_description( $value, $product ) {
        return $this->product_field( $value, $product, 'short-description' );
    }

    /**
     * Translate only text fields on the existing WooCommerce product identity.
     *
     * @param mixed  $value Source value.
     * @param mixed  $product WC_Product-like object.
     * @param string $field Stable text field.
     * @return mixed
     */
    public function product_field( $value, $product, $field ) {
        if ( ! $this->is_customer_view_request() || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
            return $value;
        }

        $product_id = (int) $product->get_id();
        if ( $product_id <= 0 || ! is_scalar( $value ) ) {
            return $value;
        }

        $key = $this->product_key( $product_id, $field );
        return $this->translate( $key, (string) $value );
    }

    /**
     * Translate WooCommerce product category/tag/attribute term display text.
     * The term is cloned before mutation so object-cache instances are not
     * polluted when a process later switches language context.
     *
     * @param mixed  $term WP_Term-like value.
     * @param string $taxonomy Taxonomy slug.
     * @return mixed
     */
    public function term( $term, $taxonomy ) {
        if ( ! $this->is_customer_view_request() || ! is_object( $term ) || empty( $term->term_id ) || ! $this->is_translatable_taxonomy( $taxonomy ) ) {
            return $term;
        }

        $translated = clone $term;
        $term_id    = (int) $term->term_id;

        if ( isset( $term->name ) && is_scalar( $term->name ) ) {
            $translated->name = $this->translate(
                $this->term_key( $taxonomy, $term_id, 'name' ),
                (string) $term->name
            );
        }

        if ( isset( $term->description ) && is_scalar( $term->description ) ) {
            $translated->description = $this->translate(
                $this->term_key( $taxonomy, $term_id, 'description' ),
                (string) $term->description
            );
        }

        return $translated;
    }

    /**
     * Translate global and product-local attribute labels without changing
     * attribute taxonomy/name identity or option values.
     *
     * @param mixed $label Current label.
     * @param mixed $name Attribute taxonomy/name.
     * @param mixed $product Optional WC_Product-like object.
     * @return mixed
     */
    public function attribute_label( $label, $name, $product = null ) {
        if ( ! $this->is_customer_view_request() || ! is_scalar( $label ) || ! is_scalar( $name ) ) {
            return $label;
        }

        $name = (string) $name;
        if ( 0 === strpos( $name, 'pa_' ) ) {
            $key = 'woocommerce.attribute.' . $this->key_fragment( $name ) . '.label';
        } elseif ( is_object( $product ) && method_exists( $product, 'get_id' ) && (int) $product->get_id() > 0 ) {
            $key = 'woocommerce.product.' . (int) $product->get_id() . '.attribute.' . $this->key_fragment( $name ) . '.label';
        } else {
            return $label;
        }

        return $this->translate( $key, (string) $label );
    }

    /** @param int $product_id Product/variation ID. @param string $field Text field. @return string */
    public function product_key( $product_id, $field ) {
        return 'woocommerce.product.' . max( 0, (int) $product_id ) . '.' . $this->key_fragment( $field );
    }

    /** @param string $taxonomy Taxonomy. @param int $term_id Term ID. @param string $field Field. @return string */
    public function term_key( $taxonomy, $term_id, $field ) {
        return 'woocommerce.term.' . $this->key_fragment( $taxonomy ) . '.' . max( 0, (int) $term_id ) . '.' . $this->key_fragment( $field );
    }

    /** @param mixed $mapper Existing mapper. @return WooCommerceTranslationMapper */
    public function filter_mapper( $mapper ) {
        unset( $mapper );
        return $this;
    }

    /** @param string $key Translation key. @param string $source Source value. @return string */
    private function translate( $key, $source ) {
        return (string) call_user_func( $this->translator, $key, $source, $this->context->code() );
    }

    /** @param string $taxonomy Taxonomy. @return bool */
    private function is_translatable_taxonomy( $taxonomy ) {
        $taxonomy = (string) $taxonomy;
        return in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) || 0 === strpos( $taxonomy, 'pa_' );
    }

    /** @param mixed $value Key fragment. @return string */
    private function key_fragment( $value ) {
        $value = strtolower( trim( (string) $value ) );
        $value = preg_replace( '/[\s\/]+/', '-', $value );
        $value = preg_replace( '/[^a-z0-9_-]+/', '-', (string) $value );
        return trim( (string) $value, '-_' );
    }

    /**
     * AJAX/REST language persistence is intentionally handled by the later
     * WooCommerce session/order context slice. Until then we do not guess a
     * storefront language for those request types.
     *
     * @return bool
     */
    private function is_customer_view_request() {
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
