<?php
/**
 * Versioned translation storage installation.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslationInstaller {
    const DB_VERSION_OPTION = 'itk_commerce_multilingual_translation_db_version';
    const DB_VERSION        = '1';

    /** @return void */
    public static function maybe_install() {
        if ( self::DB_VERSION !== (string) get_option( self::DB_VERSION_OPTION, '' ) ) {
            self::install();
        }
    }

    /** @return void */
    public static function install() {
        global $wpdb;

        if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_charset_collate' ) ) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( self::schema_sql( $wpdb ) as $sql ) {
            dbDelta( $sql );
        }

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
    }

    /**
     * Return deterministic table names for the current site prefix.
     *
     * @param object|null $database Optional wpdb-compatible object.
     * @return array{entries:string,revisions:string}
     */
    public static function table_names( $database = null ) {
        global $wpdb;
        $database = is_object( $database ) ? $database : $wpdb;
        $prefix   = is_object( $database ) && isset( $database->prefix ) ? (string) $database->prefix : 'wp_';

        return array(
            'entries'   => $prefix . 'itk_commerce_translation_entries',
            'revisions' => $prefix . 'itk_commerce_translation_revisions',
        );
    }

    /**
     * dbDelta-compatible schema. No foreign keys are used so WordPress prefix,
     * multisite and table upgrade behavior stay portable.
     *
     * @param object|null $database Optional wpdb-compatible object.
     * @return string[]
     */
    public static function schema_sql( $database = null ) {
        global $wpdb;
        $database = is_object( $database ) ? $database : $wpdb;
        $tables   = self::table_names( $database );
        $collate  = is_object( $database ) && method_exists( $database, 'get_charset_collate' )
            ? (string) $database->get_charset_collate()
            : '';

        $entries = "CREATE TABLE {$tables['entries']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 translation_key varchar(191) NOT NULL,
 language_code varchar(32) NOT NULL,
 source_hash char(64) NOT NULL DEFAULT '',
 current_revision_id bigint(20) unsigned DEFAULT NULL,
 published_revision_id bigint(20) unsigned DEFAULT NULL,
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY translation_identity (translation_key,language_code),
 KEY language_code (language_code),
 KEY current_revision (current_revision_id),
 KEY published_revision (published_revision_id)
) {$collate};";

        $revisions = "CREATE TABLE {$tables['revisions']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 entry_id bigint(20) unsigned NOT NULL,
 revision_no int(10) unsigned NOT NULL,
 translation_value longtext NOT NULL,
 workflow_status varchar(20) NOT NULL DEFAULT 'draft',
 author_id bigint(20) unsigned NOT NULL DEFAULT 0,
 reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 published_at datetime DEFAULT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY entry_revision (entry_id,revision_no),
 KEY entry_status (entry_id,workflow_status),
 KEY workflow_status (workflow_status)
) {$collate};";

        return array( $entries, $revisions );
    }
}
