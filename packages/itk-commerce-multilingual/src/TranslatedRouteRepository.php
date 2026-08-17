<?php
/**
 * Current translated entity routes plus historical redirect aliases.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslatedRouteRepository implements TranslatedRouteStoreInterface {
    /** @var object */
    private $db;

    /** @var array<string,string> */
    private $tables;

    /** @param object|null $database Optional wpdb-compatible object. */
    public function __construct( $database = null ) {
        global $wpdb;
        $this->db     = is_object( $database ) ? $database : $wpdb;
        $this->tables = TranslationInstaller::table_names( $this->db );
    }

    /** {@inheritdoc} */
    public function current( $language_code, $entity_type, $object_id, $taxonomy = '' ) {
        $identity = $this->identity( $language_code, $entity_type, $object_id, $taxonomy );
        if ( null === $identity || ! is_object( $this->db ) || ! method_exists( $this->db, 'get_row' ) ) {
            return null;
        }

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['routes']} WHERE language_code = %s AND entity_type = %s AND object_id = %d AND taxonomy = %s LIMIT 1",
            $identity['language_code'],
            $identity['entity_type'],
            $identity['object_id'],
            $identity['taxonomy']
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        if ( ! is_array( $row ) ) {
            return null;
        }

        $row['alias'] = false;
        return $row;
    }

    /** {@inheritdoc} */
    public function resolve( $language_code, $entity_type, $taxonomy, $slug ) {
        $code     = $this->normalize_language_code( $language_code );
        $type     = $this->normalize_entity_type( $entity_type );
        $taxonomy = $this->normalize_taxonomy( $type, $taxonomy );
        $slug     = $this->normalize_slug( $slug );

        if ( '' === $code || '' === $type || '' === $slug || ! is_object( $this->db ) || ! method_exists( $this->db, 'get_row' ) ) {
            return null;
        }

        $hash = $this->route_hash( $taxonomy, $slug );
        $sql  = $this->db->prepare(
            "SELECT * FROM {$this->tables['routes']} WHERE language_code = %s AND entity_type = %s AND taxonomy = %s AND route_hash = %s LIMIT 1",
            $code,
            $type,
            $taxonomy,
            $hash
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        if ( is_array( $row ) ) {
            $row['alias'] = false;
            return $row;
        }

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['route_aliases']} WHERE language_code = %s AND entity_type = %s AND taxonomy = %s AND route_hash = %s LIMIT 1",
            $code,
            $type,
            $taxonomy,
            $hash
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        if ( ! is_array( $row ) ) {
            return null;
        }

        $row['translated_slug'] = isset( $row['alias_slug'] ) ? (string) $row['alias_slug'] : $slug;
        $row['alias']           = true;
        $current                = $this->current( $code, $type, isset( $row['object_id'] ) ? (int) $row['object_id'] : 0, $taxonomy );
        $row['current_slug']    = is_array( $current ) && isset( $current['translated_slug'] ) ? (string) $current['translated_slug'] : '';
        return $row;
    }

    /** {@inheritdoc} */
    public function validate_slug( $language_code, $entity_type, $object_id, $taxonomy, $slug ) {
        $identity = $this->identity( $language_code, $entity_type, $object_id, $taxonomy );
        $slug     = $this->normalize_slug( $slug );
        if ( null === $identity || '' === $slug ) {
            return $this->error( 'invalid_translated_route', 'Translated route identity or slug is invalid.' );
        }

        $existing = $this->resolve( $identity['language_code'], $identity['entity_type'], $identity['taxonomy'], $slug );
        if ( ! is_array( $existing ) ) {
            return true;
        }

        $same_entity = (int) $existing['object_id'] === $identity['object_id']
            && (string) $existing['taxonomy'] === $identity['taxonomy'];

        if ( $same_entity ) {
            return true;
        }

        return $this->error(
            'translated_slug_conflict',
            'The translated slug is already assigned to another entity in this language and route scope.'
        );
    }

    /** {@inheritdoc} */
    public function publish( $language_code, $entity_type, $object_id, $taxonomy, $source_slug, $translated_slug, $translation_key ) {
        $identity        = $this->identity( $language_code, $entity_type, $object_id, $taxonomy );
        $source_slug     = $this->normalize_slug( $source_slug );
        $translated_slug = $this->normalize_slug( $translated_slug );
        $translation_key = trim( (string) $translation_key );

        if ( null === $identity || '' === $source_slug || '' === $translated_slug || '' === $translation_key ) {
            return $this->error( 'invalid_translated_route', 'Translated route publication data is invalid.' );
        }

        $validation = $this->validate_slug(
            $identity['language_code'],
            $identity['entity_type'],
            $identity['object_id'],
            $identity['taxonomy'],
            $translated_slug
        );
        if ( true !== $validation ) {
            return $validation;
        }

        $current = $this->current(
            $identity['language_code'],
            $identity['entity_type'],
            $identity['object_id'],
            $identity['taxonomy']
        );
        $now      = $this->now();
        $new_hash = $this->route_hash( $identity['taxonomy'], $translated_slug );

        if ( is_array( $current ) && isset( $current['translated_slug'] ) && $translated_slug === (string) $current['translated_slug'] ) {
            $updated = $this->db->update(
                $this->tables['routes'],
                array(
                    'source_slug'     => $source_slug,
                    'translation_key' => $translation_key,
                    'updated_at'      => $now,
                ),
                array( 'id' => (int) $current['id'] ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
            if ( false === $updated ) {
                return $this->error( 'translated_route_update_failed', 'Could not refresh the translated route.' );
            }
            return $this->current( $identity['language_code'], $identity['entity_type'], $identity['object_id'], $identity['taxonomy'] );
        }

        if ( method_exists( $this->db, 'query' ) ) {
            $this->db->query( 'START TRANSACTION' );
        }

        if ( is_array( $current ) && ! empty( $current['translated_slug'] ) ) {
            $old_slug = (string) $current['translated_slug'];
            $old_hash = $this->route_hash( $identity['taxonomy'], $old_slug );
            $alias    = $this->alias_by_hash( $identity['language_code'], $identity['entity_type'], $identity['taxonomy'], $old_hash );

            if ( ! is_array( $alias ) ) {
                $inserted_alias = $this->db->insert(
                    $this->tables['route_aliases'],
                    array(
                        'language_code' => $identity['language_code'],
                        'entity_type'   => $identity['entity_type'],
                        'object_id'     => $identity['object_id'],
                        'taxonomy'      => $identity['taxonomy'],
                        'alias_slug'    => $old_slug,
                        'route_hash'    => $old_hash,
                        'created_at'    => $now,
                    ),
                    array( '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
                );
                if ( false === $inserted_alias ) {
                    $this->rollback();
                    return $this->error( 'translated_route_alias_failed', 'Could not preserve the previous translated slug as a redirect alias.' );
                }
            }
        }

        $this->remove_same_entity_alias(
            $identity['language_code'],
            $identity['entity_type'],
            $identity['object_id'],
            $identity['taxonomy'],
            $new_hash
        );

        if ( is_array( $current ) ) {
            $updated = $this->db->update(
                $this->tables['routes'],
                array(
                    'source_slug'     => $source_slug,
                    'translated_slug' => $translated_slug,
                    'route_hash'      => $new_hash,
                    'translation_key' => $translation_key,
                    'updated_at'      => $now,
                ),
                array( 'id' => (int) $current['id'] ),
                array( '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
            if ( false === $updated ) {
                $this->rollback();
                return $this->error( 'translated_route_update_failed', 'Could not publish the translated route.' );
            }
        } else {
            $inserted = $this->db->insert(
                $this->tables['routes'],
                array(
                    'language_code'   => $identity['language_code'],
                    'entity_type'     => $identity['entity_type'],
                    'object_id'       => $identity['object_id'],
                    'taxonomy'        => $identity['taxonomy'],
                    'source_slug'     => $source_slug,
                    'translated_slug' => $translated_slug,
                    'route_hash'      => $new_hash,
                    'translation_key' => $translation_key,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ),
                array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
            );
            if ( false === $inserted ) {
                $this->rollback();
                return $this->error( 'translated_route_insert_failed', 'Could not publish the translated route.' );
            }
        }

        if ( method_exists( $this->db, 'query' ) ) {
            $this->db->query( 'COMMIT' );
        }

        return $this->current( $identity['language_code'], $identity['entity_type'], $identity['object_id'], $identity['taxonomy'] );
    }

    /**
     * Stable collision key. Taxonomy is part of the hash so identical term
     * slugs may exist in separate taxonomy route spaces.
     *
     * @param string $taxonomy Taxonomy route scope.
     * @param string $slug Normalized slug.
     * @return string
     */
    public function route_hash( $taxonomy, $slug ) {
        return hash( 'sha256', (string) $taxonomy . "\0" . $this->normalize_slug( $slug ) );
    }

    /** @param string $code Language. @param string $type Entity type. @param string $taxonomy Taxonomy. @param string $hash Route hash. @return array<string,mixed>|null */
    private function alias_by_hash( $code, $type, $taxonomy, $hash ) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['route_aliases']} WHERE language_code = %s AND entity_type = %s AND taxonomy = %s AND route_hash = %s LIMIT 1",
            $code,
            $type,
            $taxonomy,
            $hash
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    /** @return void */
    private function remove_same_entity_alias( $code, $type, $object_id, $taxonomy, $hash ) {
        $alias = $this->alias_by_hash( $code, $type, $taxonomy, $hash );
        if ( ! is_array( $alias ) || (int) $alias['object_id'] !== (int) $object_id || ! method_exists( $this->db, 'delete' ) ) {
            return;
        }

        $this->db->delete( $this->tables['route_aliases'], array( 'id' => (int) $alias['id'] ), array( '%d' ) );
    }

    /** @return array{language_code:string,entity_type:string,object_id:int,taxonomy:string}|null */
    private function identity( $language_code, $entity_type, $object_id, $taxonomy ) {
        $code = $this->normalize_language_code( $language_code );
        $type = $this->normalize_entity_type( $entity_type );
        $id   = max( 0, (int) $object_id );
        $tax  = $this->normalize_taxonomy( $type, $taxonomy );

        if ( '' === $code || '' === $type || 0 === $id || ( 'term' === $type && '' === $tax ) ) {
            return null;
        }

        return array(
            'language_code' => $code,
            'entity_type'   => $type,
            'object_id'     => $id,
            'taxonomy'      => $tax,
        );
    }

    /** @param mixed $code Candidate language. @return string */
    private function normalize_language_code( $code ) {
        $code = strtolower( str_replace( '_', '-', trim( (string) $code ) ) );
        return preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ? $code : '';
    }

    /** @param mixed $type Entity type. @return string */
    private function normalize_entity_type( $type ) {
        $type = strtolower( trim( (string) $type ) );
        return in_array( $type, array( 'product', 'term' ), true ) ? $type : '';
    }

    /** @param string $type Entity type. @param mixed $taxonomy Candidate taxonomy. @return string */
    private function normalize_taxonomy( $type, $taxonomy ) {
        if ( 'term' !== $type ) {
            return '';
        }

        $taxonomy = strtolower( trim( (string) $taxonomy ) );
        return preg_match( '/^[a-z0-9_-]{1,64}$/', $taxonomy ) ? $taxonomy : '';
    }

    /** @param mixed $slug Slug. @return string */
    private function normalize_slug( $slug ) {
        return trim( (string) $slug, " \t\n\r\0\x0B/" );
    }

    /** @return void */
    private function rollback() {
        if ( is_object( $this->db ) && method_exists( $this->db, 'query' ) ) {
            $this->db->query( 'ROLLBACK' );
        }
    }

    /** @return string */
    private function now() {
        return gmdate( 'Y-m-d H:i:s' );
    }

    /** @param string $code Error code. @param string $message Message. @return \WP_Error|false */
    private function error( $code, $message ) {
        if ( class_exists( '\WP_Error' ) ) {
            return new \WP_Error( $code, $message );
        }
        return false;
    }
}
