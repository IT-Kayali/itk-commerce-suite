<?php
/**
 * Safe CSV/JSON/XLIFF translation interchange foundation.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslationTransfer {
    const SCHEMA_NAME    = 'itk-commerce-translations';
    const SCHEMA_VERSION = 1;
    const MAX_BYTES      = 5242880;
    const MAX_RECORDS    = 10000;

    /** @var TranslationSchema */
    private $schema;

    /** @var TranslationRepository */
    private $repository;

    /** @var TranslationWorkflow */
    private $workflow;

    /** @var object */
    private $db;

    /** @var array{entries:string,revisions:string} */
    private $tables;

    /**
     * @param TranslationSchema     $schema Translation identity/workflow schema.
     * @param TranslationRepository $repository Translation persistence service.
     * @param TranslationWorkflow   $workflow Translation workflow service.
     * @param object|null           $database Optional wpdb-compatible object.
     */
    public function __construct( TranslationSchema $schema, TranslationRepository $repository, TranslationWorkflow $workflow, $database = null ) {
        global $wpdb;

        $this->schema     = $schema;
        $this->repository = $repository;
        $this->workflow   = $workflow;
        $this->db         = is_object( $database ) ? $database : $wpdb;
        $this->tables     = TranslationInstaller::table_names( $this->db );
    }

    /** @return void */
    public function register() {
        add_filter( 'itk_commerce_translation_transfer', array( $this, 'filter_service' ) );
    }

    /** @param mixed $service Existing service. @return TranslationTransfer */
    public function filter_service( $service ) {
        unset( $service );
        return $this;
    }

    /**
     * Export current or published translation revisions.
     *
     * @param string $format json, csv or xliff.
     * @param array<string,mixed> $options Export options: scope=current|published, languages=[] .
     * @return string|\WP_Error|false
     */
    public function export( $format, array $options = array() ) {
        $format = $this->normalize_format( $format );
        if ( '' === $format ) {
            return $this->error( 'unsupported_format', 'Translation export format is not supported.' );
        }

        $scope = isset( $options['scope'] ) && 'published' === strtolower( (string) $options['scope'] ) ? 'published' : 'current';
        $languages = isset( $options['languages'] ) && is_array( $options['languages'] ) ? $options['languages'] : array();
        $rows = $this->export_rows( $scope, $languages );

        $package = array(
            'schema'         => self::SCHEMA_NAME,
            'schema_version' => self::SCHEMA_VERSION,
            'package'        => defined( __NAMESPACE__ . '\\MODULE_ID' ) ? MODULE_ID : 'itk-commerce-multilingual',
            'version'        => defined( __NAMESPACE__ . '\\VERSION' ) ? VERSION : '',
            'scope'          => $scope,
            'generated_at'   => gmdate( 'c' ),
            'records'        => $rows,
        );

        if ( 'json' === $format ) {
            $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
            $json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $package, $flags ) : json_encode( $package, $flags );
            return is_string( $json ) ? $json : $this->error( 'json_encode_failed', 'Could not encode translation export.' );
        }

        if ( 'csv' === $format ) {
            return $this->encode_csv( $rows );
        }

        return $this->encode_xliff( $package );
    }

    /**
     * Parse and validate an import without writing anything.
     *
     * @param string $format json, csv or xliff.
     * @param string $payload Raw import payload.
     * @return array<string,mixed>|\WP_Error|false
     */
    public function analyze_import( $format, $payload ) {
        $format = $this->normalize_format( $format );
        if ( '' === $format ) {
            return $this->error( 'unsupported_format', 'Translation import format is not supported.' );
        }

        $payload = is_string( $payload ) ? $payload : '';
        if ( '' === trim( $payload ) ) {
            return $this->error( 'empty_import', 'Translation import is empty.' );
        }
        if ( strlen( $payload ) > self::MAX_BYTES ) {
            return $this->error( 'import_too_large', 'Translation import exceeds the maximum allowed size.' );
        }
        if ( false !== strpos( $payload, "\0" ) ) {
            return $this->error( 'invalid_import_bytes', 'Translation import contains invalid bytes.' );
        }

        if ( 'json' === $format ) {
            $parsed = $this->decode_json( $payload );
        } elseif ( 'csv' === $format ) {
            $parsed = $this->decode_csv( $payload );
        } else {
            $parsed = $this->decode_xliff( $payload );
        }

        if ( $this->is_error( $parsed ) ) {
            return $parsed;
        }

        $raw_records = isset( $parsed['records'] ) && is_array( $parsed['records'] ) ? $parsed['records'] : array();
        if ( count( $raw_records ) > self::MAX_RECORDS ) {
            return $this->error( 'too_many_records', 'Translation import contains too many records.' );
        }

        $valid = array();
        $invalid = array();
        $seen = array();
        $unchanged = 0;
        $conflicts = 0;

        foreach ( $raw_records as $index => $record ) {
            $normalized = $this->normalize_record( $record );
            if ( null === $normalized ) {
                $invalid[] = array( 'index' => $index, 'reason' => 'invalid_record' );
                continue;
            }

            $identity = $normalized['translation_key'] . '|' . $normalized['language_code'];
            if ( isset( $seen[ $identity ] ) ) {
                $invalid[] = array( 'index' => $index, 'reason' => 'duplicate_identity' );
                continue;
            }
            $seen[ $identity ] = true;

            $published = $this->repository->published( $normalized['translation_key'], $normalized['language_code'] );
            $entry = $this->repository->entry( $normalized['translation_key'], $normalized['language_code'] );

            $state = 'new';
            if ( is_array( $published ) && isset( $published['translation_value'] ) && (string) $published['translation_value'] === $normalized['translation_value'] ) {
                $state = 'unchanged';
                ++$unchanged;
            } elseif ( is_array( $entry ) ) {
                $state = 'conflict';
                ++$conflicts;
            }

            $normalized['import_state'] = $state;
            $valid[] = $normalized;
        }

        return array(
            'schema'         => isset( $parsed['schema'] ) ? (string) $parsed['schema'] : self::SCHEMA_NAME,
            'schema_version' => isset( $parsed['schema_version'] ) ? (int) $parsed['schema_version'] : self::SCHEMA_VERSION,
            'format'         => $format,
            'records'        => $valid,
            'invalid'        => $invalid,
            'summary'        => array(
                'total'     => count( $raw_records ),
                'valid'     => count( $valid ),
                'invalid'   => count( $invalid ),
                'new'       => max( 0, count( $valid ) - $unchanged - $conflicts ),
                'conflicts' => $conflicts,
                'unchanged' => $unchanged,
            ),
        );
    }

    /**
     * Apply a previously analyzable import as new draft revisions only.
     * Existing published translations remain live until normal review/publish.
     *
     * @param string $format json, csv or xliff.
     * @param string $payload Raw import payload.
     * @param int $author_id Optional importing user ID.
     * @return array<string,mixed>|\WP_Error|false
     */
    public function import_as_drafts( $format, $payload, $author_id = 0 ) {
        $analysis = $this->analyze_import( $format, $payload );
        if ( $this->is_error( $analysis ) ) {
            return $analysis;
        }
        if ( ! empty( $analysis['invalid'] ) ) {
            return $this->error( 'import_validation_failed', 'Translation import contains invalid records and was not applied.' );
        }

        $created = array();
        $skipped = array();
        foreach ( $analysis['records'] as $record ) {
            if ( 'unchanged' === $record['import_state'] ) {
                $skipped[] = array(
                    'translation_key' => $record['translation_key'],
                    'language_code'   => $record['language_code'],
                    'reason'          => 'unchanged',
                );
                continue;
            }

            $revision = $this->workflow->create_draft(
                $record['translation_key'],
                $record['language_code'],
                $record['translation_value'],
                isset( $record['source'] ) ? $record['source'] : '',
                max( 0, (int) $author_id )
            );

            if ( $this->is_error( $revision ) || false === $revision ) {
                return $this->error( 'import_apply_failed', 'Translation import could not create all draft revisions.' );
            }

            $created[] = $revision;
        }

        do_action( 'itk_commerce_translation_imported', $created, $skipped, $analysis );

        return array(
            'created'  => $created,
            'skipped'  => $skipped,
            'analysis' => $analysis,
        );
    }

    /**
     * Read current/published revisions without exporting secrets or users.
     *
     * @param string $scope current or published.
     * @param array<int,mixed> $languages Optional language allow-list.
     * @return array<int,array<string,mixed>>
     */
    private function export_rows( $scope, array $languages ) {
        $pointer = 'published' === $scope ? 'published_revision_id' : 'current_revision_id';
        $allowed = array();
        foreach ( $languages as $language ) {
            $code = $this->schema->normalize_language_code( $language );
            if ( '' !== $code ) {
                $allowed[ $code ] = true;
            }
        }

        $sql = "SELECT e.translation_key, e.language_code, e.source_hash, r.revision_no, r.translation_value, r.workflow_status, r.updated_at, r.published_at
                FROM {$this->tables['entries']} e
                INNER JOIN {$this->tables['revisions']} r ON r.id = e.{$pointer}
                ORDER BY e.language_code ASC, e.translation_key ASC";
        $rows = $this->db->get_results( $sql, ARRAY_A );
        $rows = is_array( $rows ) ? $rows : array();

        $result = array();
        foreach ( $rows as $row ) {
            $code = isset( $row['language_code'] ) ? $this->schema->normalize_language_code( $row['language_code'] ) : '';
            $key  = isset( $row['translation_key'] ) ? $this->schema->normalize_key( $row['translation_key'] ) : '';
            if ( '' === $code || '' === $key || ( ! empty( $allowed ) && ! isset( $allowed[ $code ] ) ) ) {
                continue;
            }

            $result[] = array(
                'translation_key'   => $key,
                'language_code'     => $code,
                'translation_value' => isset( $row['translation_value'] ) ? (string) $row['translation_value'] : '',
                'workflow_status'   => isset( $row['workflow_status'] ) ? $this->schema->normalize_status( $row['workflow_status'] ) : '',
                'source_hash'       => isset( $row['source_hash'] ) ? (string) $row['source_hash'] : '',
                'revision_no'       => isset( $row['revision_no'] ) ? (int) $row['revision_no'] : 0,
                'updated_at'        => isset( $row['updated_at'] ) ? (string) $row['updated_at'] : '',
                'published_at'      => isset( $row['published_at'] ) ? (string) $row['published_at'] : '',
            );
        }

        return $result;
    }

    /** @param array<int,array<string,mixed>> $rows Records. @return string|\WP_Error|false */
    private function encode_csv( array $rows ) {
        $stream = fopen( 'php://temp', 'w+' );
        if ( false === $stream ) {
            return $this->error( 'csv_stream_failed', 'Could not create translation CSV export.' );
        }

        fputcsv( $stream, array( 'translation_key', 'language_code', 'translation_value', 'workflow_status', 'source_hash', 'revision_no', 'updated_at', 'published_at' ) );
        foreach ( $rows as $row ) {
            fputcsv( $stream, array(
                $row['translation_key'],
                $row['language_code'],
                $row['translation_value'],
                $row['workflow_status'],
                $row['source_hash'],
                $row['revision_no'],
                $row['updated_at'],
                $row['published_at'],
            ) );
        }

        rewind( $stream );
        $csv = stream_get_contents( $stream );
        fclose( $stream );
        return is_string( $csv ) ? $csv : $this->error( 'csv_encode_failed', 'Could not encode translation CSV export.' );
    }

    /** @param array<string,mixed> $package Export package. @return string|\WP_Error|false */
    private function encode_xliff( array $package ) {
        if ( ! class_exists( '\\DOMDocument' ) ) {
            return $this->error( 'xml_extension_missing', 'XLIFF export requires the DOM XML extension.' );
        }

        $dom = new \DOMDocument( '1.0', 'UTF-8' );
        $dom->formatOutput = true;
        $xliff = $dom->createElement( 'xliff' );
        $xliff->setAttribute( 'version', '1.2' );
        $xliff->setAttribute( 'xmlns', 'urn:oasis:names:tc:xliff:document:1.2' );
        $dom->appendChild( $xliff );

        $groups = array();
        foreach ( $package['records'] as $record ) {
            $groups[ $record['language_code'] ][] = $record;
        }

        foreach ( $groups as $language => $records ) {
            $file = $dom->createElement( 'file' );
            $file->setAttribute( 'datatype', 'plaintext' );
            $file->setAttribute( 'original', self::SCHEMA_NAME );
            $file->setAttribute( 'source-language', 'und' );
            $file->setAttribute( 'target-language', $language );
            $body = $dom->createElement( 'body' );

            foreach ( $records as $record ) {
                $unit = $dom->createElement( 'trans-unit' );
                $unit->setAttribute( 'id', sha1( $record['translation_key'] . '|' . $language ) );
                $unit->setAttribute( 'resname', $record['translation_key'] );
                $source = $dom->createElement( 'source' );
                $source->appendChild( $dom->createTextNode( $record['translation_key'] ) );
                $target = $dom->createElement( 'target' );
                $target->setAttribute( 'state', $this->xliff_state( $record['workflow_status'] ) );
                $target->appendChild( $dom->createTextNode( $record['translation_value'] ) );
                $unit->appendChild( $source );
                $unit->appendChild( $target );

                $props = $dom->createElement( 'prop-group' );
                foreach ( array( 'workflow_status', 'source_hash', 'revision_no' ) as $property ) {
                    $prop = $dom->createElement( 'prop' );
                    $prop->setAttribute( 'prop-type', 'itk-' . str_replace( '_', '-', $property ) );
                    $prop->appendChild( $dom->createTextNode( (string) $record[ $property ] ) );
                    $props->appendChild( $prop );
                }
                $unit->appendChild( $props );
                $body->appendChild( $unit );
            }

            $file->appendChild( $body );
            $xliff->appendChild( $file );
        }

        $xml = $dom->saveXML();
        return is_string( $xml ) ? $xml : $this->error( 'xliff_encode_failed', 'Could not encode translation XLIFF export.' );
    }

    /** @param string $payload JSON. @return array<string,mixed>|\WP_Error|false */
    private function decode_json( $payload ) {
        $data = json_decode( $payload, true );
        if ( ! is_array( $data ) || self::SCHEMA_NAME !== ( isset( $data['schema'] ) ? (string) $data['schema'] : '' ) || self::SCHEMA_VERSION !== ( isset( $data['schema_version'] ) ? (int) $data['schema_version'] : 0 ) ) {
            return $this->error( 'invalid_json_schema', 'Translation JSON schema or version is invalid.' );
        }
        if ( ! isset( $data['records'] ) || ! is_array( $data['records'] ) ) {
            return $this->error( 'invalid_json_records', 'Translation JSON records are invalid.' );
        }
        return $data;
    }

    /** @param string $payload CSV. @return array<string,mixed>|\WP_Error|false */
    private function decode_csv( $payload ) {
        $stream = fopen( 'php://temp', 'w+' );
        if ( false === $stream ) {
            return $this->error( 'csv_stream_failed', 'Could not read translation CSV import.' );
        }
        fwrite( $stream, $payload );
        rewind( $stream );

        $headers = fgetcsv( $stream );
        if ( ! is_array( $headers ) ) {
            fclose( $stream );
            return $this->error( 'invalid_csv_header', 'Translation CSV header is missing.' );
        }
        $headers = array_map( 'trim', $headers );
        foreach ( array( 'translation_key', 'language_code', 'translation_value' ) as $required ) {
            if ( ! in_array( $required, $headers, true ) ) {
                fclose( $stream );
                return $this->error( 'invalid_csv_header', 'Translation CSV is missing required columns.' );
            }
        }

        $records = array();
        while ( false !== ( $row = fgetcsv( $stream ) ) ) {
            if ( array( null ) === $row || array() === $row ) {
                continue;
            }
            $row = array_pad( $row, count( $headers ), '' );
            $records[] = array_combine( $headers, array_slice( $row, 0, count( $headers ) ) );
            if ( count( $records ) > self::MAX_RECORDS ) {
                break;
            }
        }
        fclose( $stream );

        return array(
            'schema'         => self::SCHEMA_NAME,
            'schema_version' => self::SCHEMA_VERSION,
            'records'        => $records,
        );
    }

    /** @param string $payload XLIFF XML. @return array<string,mixed>|\WP_Error|false */
    private function decode_xliff( $payload ) {
        if ( false !== stripos( $payload, '<!DOCTYPE' ) || false !== stripos( $payload, '<!ENTITY' ) ) {
            return $this->error( 'unsafe_xliff', 'XLIFF with DTD or entity declarations is not allowed.' );
        }
        if ( ! class_exists( '\\DOMDocument' ) || ! class_exists( '\\DOMXPath' ) ) {
            return $this->error( 'xml_extension_missing', 'XLIFF import requires the DOM XML extension.' );
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors( true );
        $loaded = $dom->loadXML( $payload, LIBXML_NONET | LIBXML_NOCDATA );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        if ( ! $loaded ) {
            return $this->error( 'invalid_xliff', 'Translation XLIFF XML is invalid.' );
        }

        $xpath = new \DOMXPath( $dom );
        $xpath->registerNamespace( 'x', 'urn:oasis:names:tc:xliff:document:1.2' );
        $files = $xpath->query( '//x:file' );
        if ( false === $files || 0 === $files->length ) {
            return $this->error( 'invalid_xliff', 'Translation XLIFF contains no files.' );
        }

        $records = array();
        foreach ( $files as $file ) {
            $language = $this->schema->normalize_language_code( $file->attributes->getNamedItem( 'target-language' ) ? $file->attributes->getNamedItem( 'target-language' )->nodeValue : '' );
            if ( '' === $language ) {
                continue;
            }
            $units = $xpath->query( './/x:trans-unit', $file );
            if ( false === $units ) {
                continue;
            }
            foreach ( $units as $unit ) {
                $key = $unit->attributes->getNamedItem( 'resname' ) ? $unit->attributes->getNamedItem( 'resname' )->nodeValue : '';
                $targets = $xpath->query( './x:target', $unit );
                if ( false === $targets || 0 === $targets->length ) {
                    continue;
                }
                $status = '';
                $props = $xpath->query( './/x:prop[@prop-type="itk-workflow-status"]', $unit );
                if ( false !== $props && $props->length > 0 ) {
                    $status = $props->item( 0 )->textContent;
                }
                $records[] = array(
                    'translation_key'   => $key,
                    'language_code'     => $language,
                    'translation_value' => $targets->item( 0 )->textContent,
                    'workflow_status'   => $status,
                );
                if ( count( $records ) > self::MAX_RECORDS ) {
                    break 2;
                }
            }
        }

        return array(
            'schema'         => self::SCHEMA_NAME,
            'schema_version' => self::SCHEMA_VERSION,
            'records'        => $records,
        );
    }

    /** @param mixed $record Raw record. @return array<string,mixed>|null */
    private function normalize_record( $record ) {
        if ( ! is_array( $record ) ) {
            return null;
        }
        $key = $this->schema->normalize_key( isset( $record['translation_key'] ) ? $record['translation_key'] : '' );
        $code = $this->schema->normalize_language_code( isset( $record['language_code'] ) ? $record['language_code'] : '' );
        if ( '' === $key || '' === $code || ! array_key_exists( 'translation_value', $record ) ) {
            return null;
        }

        $status = $this->schema->normalize_status( isset( $record['workflow_status'] ) ? $record['workflow_status'] : '' );
        if ( TranslationSchema::STATUS_ARCHIVED === $status ) {
            $status = '';
        }

        return array(
            'translation_key'   => $key,
            'language_code'     => $code,
            'translation_value' => $this->schema->normalize_value( $record['translation_value'] ),
            'workflow_status'   => '' !== $status ? $status : TranslationSchema::STATUS_DRAFT,
            'source'            => isset( $record['source'] ) && is_scalar( $record['source'] ) ? (string) $record['source'] : '',
            'source_hash'       => isset( $record['source_hash'] ) && is_scalar( $record['source_hash'] ) ? (string) $record['source_hash'] : '',
        );
    }

    /** @param string $format Raw format. @return string */
    private function normalize_format( $format ) {
        $format = strtolower( trim( (string) $format ) );
        if ( 'xlf' === $format ) {
            $format = 'xliff';
        }
        return in_array( $format, array( 'json', 'csv', 'xliff' ), true ) ? $format : '';
    }

    /** @param string $status Workflow status. @return string */
    private function xliff_state( $status ) {
        if ( TranslationSchema::STATUS_PUBLISHED === $status ) {
            return 'final';
        }
        if ( TranslationSchema::STATUS_REVIEW === $status ) {
            return 'needs-review-translation';
        }
        return 'translated';
    }

    /** @param mixed $value Potential error. @return bool */
    private function is_error( $value ) {
        return function_exists( 'is_wp_error' ) && is_wp_error( $value );
    }

    /** @param string $code Error code. @param string $message Error message. @return \WP_Error|false */
    private function error( $code, $message ) {
        if ( class_exists( '\\WP_Error' ) ) {
            return new \WP_Error( $code, $message );
        }
        return false;
    }
}
