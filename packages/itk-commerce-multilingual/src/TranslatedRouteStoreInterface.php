<?php
/**
 * Storage contract for language-specific virtual entity slugs.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

interface TranslatedRouteStoreInterface {
    /**
     * Return the current route for one existing entity/language identity.
     *
     * @param string $language_code Public language code.
     * @param string $entity_type Route entity type.
     * @param int    $object_id Existing WordPress/WooCommerce object ID.
     * @param string $taxonomy Taxonomy for term routes.
     * @return array<string,mixed>|null
     */
    public function current( $language_code, $entity_type, $object_id, $taxonomy = '' );

    /**
     * Resolve a current or historical alias slug.
     *
     * @param string $language_code Public language code.
     * @param string $entity_type Route entity type.
     * @param string $taxonomy Taxonomy for term routes.
     * @param string $slug Normalized translated slug.
     * @return array<string,mixed>|null
     */
    public function resolve( $language_code, $entity_type, $taxonomy, $slug );

    /**
     * Validate route uniqueness before a reviewed slug translation publishes.
     *
     * @param string $language_code Public language code.
     * @param string $entity_type Route entity type.
     * @param int    $object_id Existing entity ID.
     * @param string $taxonomy Taxonomy for term routes.
     * @param string $slug Normalized translated slug.
     * @return true|\WP_Error|false
     */
    public function validate_slug( $language_code, $entity_type, $object_id, $taxonomy, $slug );

    /**
     * Promote one translated slug to the current route and preserve the previous
     * translated slug as a redirect alias when it changed.
     *
     * @param string $language_code Public language code.
     * @param string $entity_type Route entity type.
     * @param int    $object_id Existing entity ID.
     * @param string $taxonomy Taxonomy for term routes.
     * @param string $source_slug Canonical WordPress/WooCommerce slug.
     * @param string $translated_slug Published translated slug.
     * @param string $translation_key Stable translation key.
     * @return array<string,mixed>|\WP_Error|false
     */
    public function publish( $language_code, $entity_type, $object_id, $taxonomy, $source_slug, $translated_slug, $translation_key );
}
