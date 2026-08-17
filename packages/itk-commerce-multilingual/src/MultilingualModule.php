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

    /** @var LanguageRouter|null */
    private $router = null;

    /** @var LanguageSwitcher|null */
    private $switcher = null;

    /** @var MultilingualSeo|null */
    private $seo = null;

    /** @var TranslationSchema|null */
    private $translation_schema = null;

    /** @var TranslationRepository|null */
    private $translation_repository = null;

    /** @var TranslationWorkflow|null */
    private $translation_workflow = null;

    /** @var WooCommerceLanguageContext|null */
    private $woocommerce_language_context = null;

    /** @var OrderLanguageScope|null */
    private $order_language_scope = null;

    /** @var OrderTranslationLanguageBridge|null */
    private $order_translation_bridge = null;

    /** @var WooCommerceTranslationMapper|null */
    private $woocommerce_mapper = null;

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

    /** @return void */
    public function register() {
        if ( null !== $this->schema ) {
            return;
        }

        $this->schema = new LanguageSchema();
        $raw          = $this->profile_config();

        $raw = apply_filters( 'itk_commerce_multilingual_config_raw', $raw );
        $raw = is_array( $raw ) ? $raw : $this->schema->defaults();

        $config = $this->schema->normalize( $raw );
        $config = apply_filters( 'itk_commerce_multilingual_config', $config );
        $config = $this->schema->normalize( is_array( $config ) ? $config : array() );

        $this->context = new LanguageContext( $config );
        $this->context->register();

        $this->router = new LanguageRouter( $this->context );
        $this->router->register();

        $this->switcher = new LanguageSwitcher( $this->context, $this->router );
        $this->switcher->register();

        $this->seo = new MultilingualSeo( $this->context, $this->router );
        $this->seo->register();

        $this->translation_schema     = new TranslationSchema();
        $this->translation_repository = new TranslationRepository( $this->translation_schema );
        $this->translation_workflow   = new TranslationWorkflow(
            $this->translation_schema,
            $this->translation_repository,
            $this->context
        );
        $this->translation_workflow->register();

        $this->woocommerce_language_context = new WooCommerceLanguageContext( $this->context, $this->router );
        $this->woocommerce_language_context->register();

        $this->order_translation_bridge = new OrderTranslationLanguageBridge();
        $this->order_translation_bridge->register();

        $this->order_language_scope = new OrderLanguageScope( $this->context, $this->woocommerce_language_context );
        $this->order_language_scope->register();

        $this->woocommerce_mapper = new WooCommerceTranslationMapper(
            array( $this->translation_workflow, 'translate' ),
            $this->context
        );
        $this->woocommerce_mapper->register();

        do_action(
            'itk_commerce_multilingual_loaded',
            $this,
            $this->schema,
            $this->context,
            $config,
            $this->router,
            $this->switcher,
            $this->translation_workflow,
            $this->woocommerce_language_context,
            $this->order_language_scope,
            $this->woocommerce_mapper
        );
    }

    /** @return LanguageSchema|null */
    public function schema() {
        return $this->schema;
    }

    /** @return LanguageContext|null */
    public function context() {
        return $this->context;
    }

    /** @return LanguageRouter|null */
    public function router() {
        return $this->router;
    }

    /** @return LanguageSwitcher|null */
    public function switcher() {
        return $this->switcher;
    }

    /** @return MultilingualSeo|null */
    public function seo() {
        return $this->seo;
    }

    /** @return TranslationSchema|null */
    public function translation_schema() {
        return $this->translation_schema;
    }

    /** @return TranslationRepository|null */
    public function translation_repository() {
        return $this->translation_repository;
    }

    /** @return TranslationWorkflow|null */
    public function translation_workflow() {
        return $this->translation_workflow;
    }

    /** @return WooCommerceLanguageContext|null */
    public function woocommerce_language_context() {
        return $this->woocommerce_language_context;
    }

    /** @return OrderLanguageScope|null */
    public function order_language_scope() {
        return $this->order_language_scope;
    }

    /** @return WooCommerceTranslationMapper|null */
    public function woocommerce_mapper() {
        return $this->woocommerce_mapper;
    }

    /** @return array<string,mixed> */
    private function profile_config() {
        if ( ! class_exists( '\ITK\Commerce\Core\Core' ) ) {
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
