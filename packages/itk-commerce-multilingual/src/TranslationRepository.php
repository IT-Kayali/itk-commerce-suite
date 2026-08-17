<?php
/**
 * Translation entry/revision persistence.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslationRepository {
    /** @var TranslationSchema */
    private $schema;

    /** @var object */
    private $db;

    /** @var array{entries:string,revisions:string} */
    private $tables;

    /**
     * @param TranslationSchema $schema Translation schema.
     * @param object|null       $database Optional wpdb-compatible object.
     */
    public function __construct( TranslationSchema $schema, $database = null ) {
        global $wpdb;

        $this->schema = $schema;
        $this->db     = is_object( $database ) ? $database : $wpdb;
        $this->tables = TranslationInstaller::table_names( $this->db );
    }

    /**
     * Create an append-only draft revision. Existing published content is not
     * changed until a review revision is explicitly published.
     *
     * @param string $key Stable translation key.
     * @param string $language_code Public language code.
     * @param mixed  $value Translation value.
     * @param mixed  $source Source/default string used for stale detection.
     * @param int    $author_id Author user ID.
     * @return array<string,mixed>|\WP_Error
     */
    public function create_draft( $key, $language_code, $value, $source = '', $author_id = 0 ) {
        $identity = $this->schema->identity( $key, $language_code );
        if ( null === $identity ) {
            return $this->error( 'invalid_identity', 'Translation key or language code is invalid.' );
        }

        $value = $this->schema->normalize_value( $value );
        $entry = $this->ensure_entry( $identity['key'], $identity['language_code'], $this->schema->source_hash( $source ) );
        if ( $this->is_error( $entry ) ) {
            return $entry;
        }

        $next_revision = 1 + (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COALESCE(MAX(revision_no), 0) FROM {$this->tables['revisions']} WHERE entry_id = %d",
                (int) $entry['id']
            )
        );
        $now = $this->now();

        $inserted = $this->db->insert(
            $this->tables['revisions'],
            array(
                'entry_id'          => (int) $entry['id'],
                'revision_no'       => $next_revision,
                'translation_value' => $value,
                'workflow_status'   => TranslationSchema::STATUS_DRAFT,
                'author_id'         => max( 0, (int) $author_id ),
                'reviewer_id'       => 0,
                'created_at'        => $now,
                'updated_at'        => $now,
                'published_at'      => null,
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', null )
        );

        if ( false === $inserted ) {
            return $this->error( 'draft_insert_failed', 'Could not create translation draft revision.' );
        }

        $revision_id = (int) $this->db->insert_id;
        $updated     = $this->db->update(
            $this->tables['entries'],
            array(
                'source_hash'         => $this->schema->source_hash( $source ),
                'current_revision_id' => $revision_id,
                'updated_at'          => $now,
            ),
            array( 'id' => (int) $entry['id'] ),
            array( '%s', '%d', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            if ( method_exists( $this->db, 'delete' ) ) {
                $this->db->delete( $this->tables['revisions'], array( 'id' => $revision_id ), array( '%d' ) );
            }
            return $this->error( 'entry_pointer_failed', 'Could not link the new draft revision.' );
        }

        return $this->revision( $revision_id );
    }

    /**
     * Transition a draft/review revision with an optimistic expected-status
     * guard. Publishing is handled separately because it also updates entry
     * pointers and archives the previous live revision.
     *
     * @param int    $revision_id Revision ID.
     * @param string $expected Expected current status.
     * @param string $target Target status.
     * @param int    $reviewer_id Reviewer user ID when applicable.
     * @return array<string,mixed>|\WP_Error
     */
    public function transition( $revision_id, $expected, $target, $reviewer_id = 0 ) {
        $revision = $this->revision( $revision_id );
        if ( empty( $revision ) ) {
            return $this->error( 'revision_not_found', 'Translation revision was not found.' );
        }

        if ( $expected !== $revision['workflow_status'] || ! $this->schema->can_transition( $expected, $target ) ) {
            return $this->error( 'invalid_transition', 'Translation revision workflow transition is not allowed.' );
        }

        if ( TranslationSchema::STATUS_PUBLISHED === $target ) {
            return $this->publish( $revision_id, $reviewer_id );
        }

        $data = array(
            'workflow_status' => $target,
            'updated_at'      => $this->now(),
        );
        $formats = array( '%s', '%s' );

        if ( TranslationSchema::STATUS_DRAFT === $target || TranslationSchema::STATUS_REVIEW === $target ) {
            $data['reviewer_id'] = max( 0, (int) $reviewer_id );
            $formats[]           = '%d';
        }

        $updated = $this->db->update(
            $this->tables['revisions'],
            $data,
            array(
                'id'              => (int) $revision_id,
                'workflow_status' => $expected,
            ),
            $formats,
            array( '%d', '%s' )
        );

        if ( false === $updated || 0 === $updated ) {
            return $this->error( 'transition_failed', 'Translation revision changed concurrently or could not be updated.' );
        }

        return $this->revision( $revision_id );
    }

    /**
     * Publish one reviewed revision atomically where the database supports
     * transactions. The previously published revision becomes archived history.
     *
     * @param int $revision_id Revision ID.
     * @param int $reviewer_id Reviewer user ID.
     * @return array<string,mixed>|\WP_Error
     */
    public function publish( $revision_id, $reviewer_id = 0 ) {
        $revision = $this->revision( $revision_id );
        if ( empty( $revision ) || TranslationSchema::STATUS_REVIEW !== $revision['workflow_status'] ) {
            return $this->error( 'publish_requires_review', 'Only a reviewed translation revision can be published.' );
        }

        $entry = $this->entry_by_id( (int) $revision['entry_id'] );
        if ( empty( $entry ) ) {
            return $this->error( 'entry_not_found', 'Translation entry was not found.' );
        }

        $this->db->query( 'START TRANSACTION' );
        $now = $this->now();

        if ( ! empty( $entry['published_revision_id'] ) && (int) $entry['published_revision_id'] !== (int) $revision_id ) {
            $archived = $this->db->update(
                $this->tables['revisions'],
                array(
                    'workflow_status' => TranslationSchema::STATUS_ARCHIVED,
                    'updated_at'      => $now,
                ),
                array(
                    'id'              => (int) $entry['published_revision_id'],
                    'workflow_status' => TranslationSchema::STATUS_PUBLISHED,
                ),
                array( '%s', '%s' ),
                array( '%d', '%s' )
            );

            if ( false === $archived ) {
                $this->db->query( 'ROLLBACK' );
                return $this->error( 'archive_failed', 'Could not archive the previous published translation.' );
            }
        }

        $published = $this->db->update(
            $this->tables['revisions'],
            array(
                'workflow_status' => TranslationSchema::STATUS_PUBLISHED,
                'reviewer_id'     => max( 0, (int) $reviewer_id ),
                'updated_at'      => $now,
                'published_at'    => $now,
            ),
            array(
                'id'              => (int) $revision_id,
                'workflow_status' => TranslationSchema::STATUS_REVIEW,
            ),
            array( '%s', '%d', '%s', '%s' ),
            array( '%d', '%s' )
        );

        if ( false === $published || 0 === $published ) {
            $this->db->query( 'ROLLBACK' );
            return $this->error( 'publish_failed', 'Translation revision changed concurrently or could not be published.' );
        }

        $linked = $this->db->update(
            $this->tables['entries'],
            array(
                'current_revision_id'   => (int) $revision_id,
                'published_revision_id' => (int) $revision_id,
                'updated_at'            => $now,
            ),
            array( 'id' => (int) $entry['id'] ),
            array( '%d', '%d', '%s' ),
            array( '%d' )
        );

        if ( false === $linked ) {
            $this->db->query( 'ROLLBACK' );
            return $this->error( 'publish_pointer_failed', 'Could not link the published translation revision.' );
        }

        $this->db->query( 'COMMIT' );
        return $this->revision( $revision_id );
    }

    /**
     * Return the published record for one key/language.
     *
     * @param string $key Translation key.
     * @param string $language_code Language code.
     * @return array<string,mixed>|null
     */
    public function published( $key, $language_code ) {
        $identity = $this->schema->identity( $key, $language_code );
        if ( null === $identity ) {
            return null;
        }

        $sql = $this->db->prepare(
            "SELECT e.id AS entry_id, e.translation_key, e.language_code, e.source_hash, r.id AS revision_id, r.revision_no, r.translation_value, r.workflow_status, r.author_id, r.reviewer_id, r.published_at
             FROM {$this->tables['entries']} e
             INNER JOIN {$this->tables['revisions']} r ON r.id = e.published_revision_id
             WHERE e.translation_key = %s AND e.language_code = %s AND r.workflow_status = %s
             LIMIT 1",
            $identity['key'],
            $identity['language_code'],
            TranslationSchema::STATUS_PUBLISHED
        );

        $row = $this->db->get_row( $sql, ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    /** @param int $revision_id Revision ID. @return array<string,mixed>|null */
    public function revision( $revision_id ) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['revisions']} WHERE id = %d LIMIT 1",
            max( 0, (int) $revision_id )
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    /** @param int $entry_id Entry ID. @return array<string,mixed>|null */
    public function entry_by_id( $entry_id ) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['entries']} WHERE id = %d LIMIT 1",
            max( 0, (int) $entry_id )
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    /**
     * @param string $key Translation key.
     * @param string $language_code Language code.
     * @return array<string,mixed>|null
     */
    public function entry( $key, $language_code ) {
        $identity = $this->schema->identity( $key, $language_code );
        if ( null === $identity ) {
            return null;
        }

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['entries']} WHERE translation_key = %s AND language_code = %s LIMIT 1",
            $identity['key'],
            $identity['language_code']
        );
        $row = $this->db->get_row( $sql, ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    /**
     * @param int $entry_id Entry ID.
     * @return array<int,array<string,mixed>>
     */
    public function revisions( $entry_id ) {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->tables['revisions']} WHERE entry_id = %d ORDER BY revision_no DESC",
            max( 0, (int) $entry_id )
        );
        $rows = $this->db->get_results( $sql, ARRAY_A );
        return is_array( $rows ) ? $rows : array();
    }

    /**
     * @param string $key Normalized key.
     * @param string $language_code Normalized language code.
     * @param string $source_hash Source hash.
     * @return array<string,mixed>|\WP_Error
     */
    private function ensure_entry( $key, $language_code, $source_hash ) {
        $entry = $this->entry( $key, $language_code );
        if ( ! empty( $entry ) ) {
            return $entry;
        }

        $now      = $this->now();
        $inserted = $this->db->insert(
            $this->tables['entries'],
            array(
                'translation_key'       => $key,
                'language_code'         => $language_code,
                'source_hash'           => $source_hash,
                'current_revision_id'   => null,
                'published_revision_id' => null,
                'created_at'            => $now,
                'updated_at'            => $now,
            ),
            array( '%s', '%s', '%s', null, null, '%s', '%s' )
        );

        if ( false === $inserted ) {
            $entry = $this->entry( $key, $language_code );
            if ( ! empty( $entry ) ) {
                return $entry;
            }
            return $this->error( 'entry_insert_failed', 'Could not create translation entry.' );
        }

        return $this->entry_by_id( (int) $this->db->insert_id );
    }

    /** @return string */
    private function now() {
        return gmdate( 'Y-m-d H:i:s' );
    }

    /** @param mixed $value Potential error. @return bool */
    private function is_error( $value ) {
        return function_exists( 'is_wp_error' ) && is_wp_error( $value );
    }

    /** @param string $code Error code. @param string $message Error message. @return \WP_Error|false */
    private function error( $code, $message ) {
        if ( class_exists( '\WP_Error' ) ) {
            return new \WP_Error( $code, $message );
        }
        return false;
    }
}
