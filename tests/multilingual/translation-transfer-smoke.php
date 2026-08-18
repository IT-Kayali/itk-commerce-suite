<?php
/**
 * Dependency-light translation import/export smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const VERSION        = '0.1.0-dev';
    const MODULE_ID      = 'itk-commerce-multilingual';
    const SCHEMA_VERSION = 2;
}

namespace {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );
    define( 'ARRAY_A', 'ARRAY_A' );

    final class WP_Error {
        private $code;
        private $message;
        public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }

    function is_wp_error( $value ) { return $value instanceof WP_Error; }
    function get_locale() { return 'de_DE'; }
    function get_current_user_id() { return 17; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return (string) $value; }
    function add_filter() {}
    function apply_filters( $hook, $value ) { unset( $hook ); return $value; }
    function do_action() {}
    function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }

    final class ITK_Transfer_Fake_DB {
        public $prefix = 'wp_';
        public $insert_id = 0;
        public $entries = array();
        public $revisions = array();
        private $snapshot = null;

        public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4'; }

        public function prepare( $query, ...$args ) {
            foreach ( $args as $arg ) {
                $replacement = is_int( $arg ) ? (string) $arg : "'" . str_replace( "'", "''", (string) $arg ) . "'";
                $query = preg_replace( '/%[dsf]/', $replacement, $query, 1 );
            }
            return $query;
        }

        public function insert( $table, $data, $format = null ) {
            unset( $format );
            if ( false !== strpos( $table, 'translation_entries' ) ) {
                foreach ( $this->entries as $entry ) {
                    if ( $entry['translation_key'] === $data['translation_key'] && $entry['language_code'] === $data['language_code'] ) {
                        return false;
                    }
                }
                $id = count( $this->entries ) + 1;
                $data['id'] = $id;
                $this->entries[ $id ] = $data;
                $this->insert_id = $id;
                return 1;
            }

            if ( false !== strpos( $table, 'translation_revisions' ) ) {
                $id = count( $this->revisions ) + 1;
                $data['id'] = $id;
                $this->revisions[ $id ] = $data;
                $this->insert_id = $id;
                return 1;
            }

            return false;
        }

        public function update( $table, $data, $where, $format = null, $where_format = null ) {
            unset( $format, $where_format );
            $rows =& $this->entries;
            if ( false !== strpos( $table, 'translation_revisions' ) ) {
                $rows =& $this->revisions;
            }
            $count = 0;
            foreach ( $rows as $id => $row ) {
                $match = true;
                foreach ( $where as $key => $value ) {
                    if ( ! array_key_exists( $key, $row ) || (string) $row[ $key ] !== (string) $value ) {
                        $match = false;
                        break;
                    }
                }
                if ( $match ) {
                    $rows[ $id ] = array_merge( $row, $data );
                    ++$count;
                }
            }
            return $count;
        }

        public function delete( $table, $where, $format = null ) {
            unset( $format );
            $rows =& $this->revisions;
            if ( false === strpos( $table, 'translation_revisions' ) ) {
                $rows =& $this->entries;
            }
            foreach ( $rows as $id => $row ) {
                $match = true;
                foreach ( $where as $key => $value ) {
                    if ( ! array_key_exists( $key, $row ) || (string) $row[ $key ] !== (string) $value ) {
                        $match = false;
                    }
                }
                if ( $match ) {
                    unset( $rows[ $id ] );
                    return 1;
                }
            }
            return 0;
        }

        public function query( $sql ) {
            if ( 'START TRANSACTION' === $sql ) {
                $this->snapshot = array( $this->entries, $this->revisions );
            } elseif ( 'ROLLBACK' === $sql && is_array( $this->snapshot ) ) {
                $this->entries = $this->snapshot[0];
                $this->revisions = $this->snapshot[1];
                $this->snapshot = null;
            } elseif ( 'COMMIT' === $sql ) {
                $this->snapshot = null;
            }
            return true;
        }

        public function get_var( $sql ) {
            if ( preg_match( '/WHERE entry_id = (\d+)/', $sql, $matches ) ) {
                $entry_id = (int) $matches[1];
                $max = 0;
                foreach ( $this->revisions as $revision ) {
                    if ( (int) $revision['entry_id'] === $entry_id ) {
                        $max = max( $max, (int) $revision['revision_no'] );
                    }
                }
                return $max;
            }
            return null;
        }

        public function get_row( $sql, $output = ARRAY_A ) {
            unset( $output );
            if ( false !== strpos( $sql, 'INNER JOIN' ) ) {
                preg_match( "/translation_key = '([^']+)'/", $sql, $key_match );
                preg_match( "/language_code = '([^']+)'/", $sql, $lang_match );
                $key  = isset( $key_match[1] ) ? $key_match[1] : '';
                $lang = isset( $lang_match[1] ) ? $lang_match[1] : '';
                foreach ( $this->entries as $entry ) {
                    if ( $entry['translation_key'] !== $key || $entry['language_code'] !== $lang || empty( $entry['published_revision_id'] ) ) {
                        continue;
                    }
                    $revision = $this->revisions[ (int) $entry['published_revision_id'] ];
                    if ( 'published' !== $revision['workflow_status'] ) {
                        continue;
                    }
                    return array(
                        'entry_id'          => $entry['id'],
                        'translation_key'   => $entry['translation_key'],
                        'language_code'     => $entry['language_code'],
                        'source_hash'       => $entry['source_hash'],
                        'revision_id'       => $revision['id'],
                        'revision_no'       => $revision['revision_no'],
                        'translation_value' => $revision['translation_value'],
                        'workflow_status'   => $revision['workflow_status'],
                        'author_id'         => $revision['author_id'],
                        'reviewer_id'       => $revision['reviewer_id'],
                        'published_at'      => $revision['published_at'],
                    );
                }
                return null;
            }

            if ( false !== strpos( $sql, 'translation_revisions' ) && preg_match( '/WHERE id = (\d+)/', $sql, $matches ) ) {
                $id = (int) $matches[1];
                return isset( $this->revisions[ $id ] ) ? $this->revisions[ $id ] : null;
            }
            if ( false !== strpos( $sql, 'translation_entries' ) && preg_match( '/WHERE id = (\d+)/', $sql, $matches ) ) {
                $id = (int) $matches[1];
                return isset( $this->entries[ $id ] ) ? $this->entries[ $id ] : null;
            }
            if ( false !== strpos( $sql, 'translation_entries' ) ) {
                preg_match( "/translation_key = '([^']+)'/", $sql, $key_match );
                preg_match( "/language_code = '([^']+)'/", $sql, $lang_match );
                foreach ( $this->entries as $entry ) {
                    if ( $entry['translation_key'] === ( isset( $key_match[1] ) ? $key_match[1] : '' ) && $entry['language_code'] === ( isset( $lang_match[1] ) ? $lang_match[1] : '' ) ) {
                        return $entry;
                    }
                }
            }
            return null;
        }

        public function get_results( $sql, $output = ARRAY_A ) {
            unset( $output );
            if ( false !== strpos( $sql, 'INNER JOIN' ) && false !== strpos( $sql, 'translation_entries' ) ) {
                $use_published = false !== strpos( $sql, 'published_revision_id' );
                $result = array();
                foreach ( $this->entries as $entry ) {
                    $pointer = $use_published ? 'published_revision_id' : 'current_revision_id';
                    if ( empty( $entry[ $pointer ] ) || empty( $this->revisions[ (int) $entry[ $pointer ] ] ) ) {
                        continue;
                    }
                    $revision = $this->revisions[ (int) $entry[ $pointer ] ];
                    $result[] = array(
                        'translation_key'   => $entry['translation_key'],
                        'language_code'     => $entry['language_code'],
                        'source_hash'       => $entry['source_hash'],
                        'revision_no'       => $revision['revision_no'],
                        'translation_value' => $revision['translation_value'],
                        'workflow_status'   => $revision['workflow_status'],
                        'updated_at'        => $revision['updated_at'],
                        'published_at'      => $revision['published_at'],
                    );
                }
                usort( $result, static function ( $a, $b ) {
                    return array( $a['language_code'], $a['translation_key'] ) <=> array( $b['language_code'], $b['translation_key'] );
                } );
                return $result;
            }

            if ( preg_match( '/WHERE entry_id = (\d+)/', $sql, $matches ) ) {
                $entry_id = (int) $matches[1];
                $rows = array_values( array_filter( $this->revisions, static function ( $row ) use ( $entry_id ) {
                    return (int) $row['entry_id'] === $entry_id;
                } ) );
                usort( $rows, static function ( $a, $b ) { return (int) $b['revision_no'] <=> (int) $a['revision_no']; } );
                return $rows;
            }
            return array();
        }
    }

    $wpdb = new ITK_Transfer_Fake_DB();

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationInstaller.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationRepository.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationWorkflow.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationTransfer.php';

    function itk_transfer_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Translation transfer failure: {$message}\n" );
            exit( 1 );
        }
    }

    $language_schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $context = new \ITK\Commerce\Multilingual\LanguageContext( $language_schema->normalize( array(
        'default' => 'de',
        'fallback' => 'de',
        'languages' => array(
            array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'enabled' => true ),
            array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
        ),
    ) ) );
    $schema = new \ITK\Commerce\Multilingual\TranslationSchema();
    $repository = new \ITK\Commerce\Multilingual\TranslationRepository( $schema, $wpdb );
    $workflow = new \ITK\Commerce\Multilingual\TranslationWorkflow( $schema, $repository, $context );
    $transfer = new \ITK\Commerce\Multilingual\TranslationTransfer( $schema, $repository, $workflow, $wpdb );

    $draft = $workflow->create_draft( 'commerce.checkout.pay', 'ar', 'ادفع الآن', 'Pay now', 17 );
    $workflow->submit_for_review( (int) $draft['id'], 18 );
    $workflow->publish( (int) $draft['id'], 18 );

    $json = $transfer->export( 'json', array( 'scope' => 'published' ) );
    itk_transfer_assert( is_string( $json ) && false !== strpos( $json, 'commerce.checkout.pay' ) && false !== strpos( $json, 'ادفع الآن' ), 'JSON export must contain the published translation.' );
    $json_data = json_decode( $json, true );
    itk_transfer_assert( 'itk-commerce-translations' === $json_data['schema'] && 1 === (int) $json_data['schema_version'], 'JSON export must include bounded transfer schema metadata.' );
    itk_transfer_assert( ! isset( $json_data['records'][0]['author_id'] ) && ! isset( $json_data['records'][0]['reviewer_id'] ), 'Exports must not leak user identifiers.' );

    $csv = $transfer->export( 'csv', array( 'scope' => 'published' ) );
    itk_transfer_assert( is_string( $csv ) && false !== strpos( $csv, 'translation_key,language_code,translation_value' ) && false !== strpos( $csv, 'commerce.checkout.pay' ), 'CSV export must expose stable interchange columns.' );

    if ( class_exists( 'DOMDocument' ) ) {
        $xliff = $transfer->export( 'xliff', array( 'scope' => 'published' ) );
        itk_transfer_assert( is_string( $xliff ) && false !== strpos( $xliff, '<xliff' ) && false !== strpos( $xliff, 'target-language="ar"' ), 'XLIFF export must produce a language-scoped XLIFF document.' );
        $xliff_analysis = $transfer->analyze_import( 'xliff', $xliff );
        itk_transfer_assert( is_array( $xliff_analysis ) && 1 === $xliff_analysis['summary']['valid'], 'XLIFF export must roundtrip through the parser.' );
    }

    $analysis = $transfer->analyze_import( 'json', $json );
    itk_transfer_assert( is_array( $analysis ) && 1 === $analysis['summary']['unchanged'], 'Identical published JSON must be detected as unchanged.' );

    $import_data = $json_data;
    $import_data['records'][0]['translation_value'] = 'الدفع الآن';
    $import_json = json_encode( $import_data, JSON_UNESCAPED_UNICODE );
    $changed = $transfer->analyze_import( 'json', $import_json );
    itk_transfer_assert( 1 === $changed['summary']['conflicts'], 'Changed existing identity must be reported as a conflict before import.' );

    $applied = $transfer->import_as_drafts( 'json', $import_json, 22 );
    itk_transfer_assert( is_array( $applied ) && 1 === count( $applied['created'] ), 'Changed import must create exactly one draft revision.' );
    itk_transfer_assert( 'draft' === $applied['created'][0]['workflow_status'], 'Imported records must never bypass the draft workflow.' );
    itk_transfer_assert( 'ادفع الآن' === $workflow->translate( 'commerce.checkout.pay', 'Pay now', 'ar' ), 'Existing published translation must stay live after a changed import.' );

    $bad_schema = $json_data;
    $bad_schema['schema_version'] = 99;
    $bad = $transfer->analyze_import( 'json', json_encode( $bad_schema ) );
    itk_transfer_assert( is_wp_error( $bad ) && 'invalid_json_schema' === $bad->get_error_code(), 'Unknown JSON schema versions must be rejected.' );

    $duplicate = $json_data;
    $duplicate['records'][] = $duplicate['records'][0];
    $duplicate_analysis = $transfer->analyze_import( 'json', json_encode( $duplicate, JSON_UNESCAPED_UNICODE ) );
    itk_transfer_assert( 1 === $duplicate_analysis['summary']['invalid'], 'Duplicate key/language identities must be rejected during analysis.' );

    $unsafe_xliff = '<?xml version="1.0"?><!DOCTYPE xliff [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><xliff version="1.2"></xliff>';
    $unsafe = $transfer->analyze_import( 'xliff', $unsafe_xliff );
    itk_transfer_assert( is_wp_error( $unsafe ) && 'unsafe_xliff' === $unsafe->get_error_code(), 'DTD/entity declarations must be rejected before XML parsing.' );

    echo "Translation transfer smoke test passed.\n";
}
