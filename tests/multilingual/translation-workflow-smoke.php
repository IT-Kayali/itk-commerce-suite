<?php
/**
 * Dependency-light Translation repository/workflow smoke test.
 */

namespace ITK\Commerce\Multilingual {
    const SCHEMA_VERSION = 1;
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
    function get_current_user_id() { return 77; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function sanitize_html_class( $value ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ); }
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function add_filter() {}
    function do_action( $hook, ...$args ) { $GLOBALS['itk_translation_actions'][] = array( $hook, $args ); }

    final class ITK_Translation_Fake_DB {
        public $prefix = 'wp_';
        public $insert_id = 0;
        public $entries = array();
        public $revisions = array();
        private $snapshot = null;

        public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'; }

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

            $id = count( $this->revisions ) + 1;
            $data['id'] = $id;
            $this->revisions[ $id ] = $data;
            $this->insert_id = $id;
            return 1;
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
                if ( ! $match ) {
                    continue;
                }
                $rows[ $id ] = array_merge( $row, $data );
                ++$count;
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
                    if ( ! isset( $row[ $key ] ) || (string) $row[ $key ] !== (string) $value ) {
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
                $this->entries   = $this->snapshot[0];
                $this->revisions = $this->snapshot[1];
                $this->snapshot  = null;
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
                $key  = isset( $key_match[1] ) ? $key_match[1] : '';
                $lang = isset( $lang_match[1] ) ? $lang_match[1] : '';
                foreach ( $this->entries as $entry ) {
                    if ( $entry['translation_key'] === $key && $entry['language_code'] === $lang ) {
                        return $entry;
                    }
                }
            }

            return null;
        }

        public function get_results( $sql, $output = ARRAY_A ) {
            unset( $output );
            if ( ! preg_match( '/WHERE entry_id = (\d+)/', $sql, $matches ) ) {
                return array();
            }
            $entry_id = (int) $matches[1];
            $rows = array_values( array_filter( $this->revisions, static function ( $row ) use ( $entry_id ) {
                return (int) $row['entry_id'] === $entry_id;
            } ) );
            usort( $rows, static function ( $a, $b ) { return (int) $b['revision_no'] <=> (int) $a['revision_no']; } );
            return $rows;
        }
    }

    $GLOBALS['itk_translation_actions'] = array();
    $wpdb = new ITK_Translation_Fake_DB();

    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/LanguageContext.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationInstaller.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationSchema.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationRepository.php';
    require dirname( __DIR__, 2 ) . '/packages/itk-commerce-multilingual/src/TranslationWorkflow.php';

    function itk_translation_assert( $condition, $message ) {
        if ( ! $condition ) {
            fwrite( STDERR, "Translation workflow failure: {$message}\n" );
            exit( 1 );
        }
    }

    $language_schema = new \ITK\Commerce\Multilingual\LanguageSchema();
    $config = $language_schema->normalize( array(
        'default' => 'ar',
        'fallback' => 'de',
        'languages' => array(
            array( 'code' => 'de', 'locale' => 'de_DE', 'label' => 'Deutsch', 'enabled' => true ),
            array( 'code' => 'ar', 'locale' => 'ar', 'label' => 'العربية', 'direction' => 'rtl', 'enabled' => true ),
            array( 'code' => 'en', 'locale' => 'en_US', 'label' => 'English', 'enabled' => true ),
        ),
    ) );
    $context = new \ITK\Commerce\Multilingual\LanguageContext( $config );

    $schema = new \ITK\Commerce\Multilingual\TranslationSchema();
    itk_translation_assert( 'customer.footer.tagline' === $schema->normalize_key( 'Customer/Footer Tagline' ), 'Translation keys must normalize to stable machine identifiers.' );
    itk_translation_assert( $schema->can_transition( 'draft', 'review' ), 'Draft must be submittable for review.' );
    itk_translation_assert( ! $schema->can_transition( 'draft', 'published' ), 'Draft must not bypass review.' );
    itk_translation_assert( ! $schema->can_transition( 'published', 'draft' ), 'Published revisions must remain immutable.' );

    $tables = \ITK\Commerce\Multilingual\TranslationInstaller::table_names( $wpdb );
    $sql    = \ITK\Commerce\Multilingual\TranslationInstaller::schema_sql( $wpdb );
    itk_translation_assert( 'wp_itk_commerce_translation_entries' === $tables['entries'], 'Entry table must use the active WordPress prefix.' );
    itk_translation_assert( 2 === count( $sql ) && false !== strpos( $sql[0], 'UNIQUE KEY translation_identity' ), 'Installer must expose versionable entry/revision table SQL.' );

    $repository = new \ITK\Commerce\Multilingual\TranslationRepository( $schema, $wpdb );
    $workflow   = new \ITK\Commerce\Multilingual\TranslationWorkflow( $schema, $repository, $context );

    $draft = $workflow->create_draft( 'commerce.header.welcome', 'ar', 'مرحباً', 'Welcome' );
    itk_translation_assert( is_array( $draft ) && 'draft' === $draft['workflow_status'], 'Creating a translation must create a draft revision.' );
    itk_translation_assert( 77 === (int) $draft['author_id'], 'Workflow should capture the current user when no author ID is supplied.' );
    itk_translation_assert( 'Welcome' === $workflow->translate( 'commerce.header.welcome', 'Welcome', 'ar' ), 'Draft text must never leak into storefront output.' );

    $blocked = $workflow->publish( (int) $draft['id'], 88 );
    itk_translation_assert( is_wp_error( $blocked ) && 'invalid_transition' === $blocked->get_error_code(), 'Publishing must require review.' );

    $review = $workflow->submit_for_review( (int) $draft['id'], 88 );
    itk_translation_assert( is_array( $review ) && 'review' === $review['workflow_status'], 'Draft should move to review.' );

    $published = $workflow->publish( (int) $draft['id'], 88 );
    itk_translation_assert( is_array( $published ) && 'published' === $published['workflow_status'], 'Reviewed revision should publish.' );
    itk_translation_assert( 'مرحباً' === $workflow->translate( 'commerce.header.welcome', 'Welcome', 'ar' ), 'Published text must resolve for the target language.' );

    $second = $workflow->create_draft( 'commerce.header.welcome', 'ar', 'أهلاً وسهلاً', 'Welcome' );
    itk_translation_assert( 2 === (int) $second['revision_no'], 'New edits must append a revision instead of overwriting history.' );
    itk_translation_assert( 'مرحباً' === $workflow->translate( 'commerce.header.welcome', 'Welcome', 'ar' ), 'Existing published revision must remain live while a new draft exists.' );

    $review2 = $workflow->submit_for_review( (int) $second['id'], 99 );
    $draft2  = $workflow->return_to_draft( (int) $review2['id'], 99 );
    itk_translation_assert( 'draft' === $draft2['workflow_status'], 'Reviewer must be able to return a review to draft.' );
    $workflow->submit_for_review( (int) $draft2['id'], 99 );
    $published2 = $workflow->publish( (int) $draft2['id'], 99 );
    itk_translation_assert( 'أهلاً وسهلاً' === $workflow->translate( 'commerce.header.welcome', 'Welcome', 'ar' ), 'New published revision must replace the live translation.' );
    itk_translation_assert( 'archived' === $repository->revision( (int) $draft['id'] )['workflow_status'], 'Previous published revision must become archived history.' );
    itk_translation_assert( 'published' === $published2['workflow_status'], 'Latest reviewed revision must remain published.' );

    $de = $workflow->create_draft( 'commerce.checkout.pay', 'de', 'Jetzt bezahlen', 'Pay now', 55 );
    $workflow->submit_for_review( (int) $de['id'], 66 );
    $workflow->publish( (int) $de['id'], 66 );
    itk_translation_assert( 'Jetzt bezahlen' === $workflow->translate( 'commerce.checkout.pay', 'Pay now', 'en' ), 'Missing target translation must fall back to the configured fallback language.' );
    itk_translation_assert( 'Pay now' === $workflow->translate( 'commerce.checkout.unknown', 'Pay now', 'en' ), 'Missing target and fallback translations must return source text.' );

    $entry = $repository->entry( 'commerce.header.welcome', 'ar' );
    $history = $repository->revisions( (int) $entry['id'] );
    itk_translation_assert( 2 === count( $history ), 'Repository must retain both translation revisions.' );
    itk_translation_assert( hash( 'sha256', 'Welcome' ) === $entry['source_hash'], 'Entry must retain a deterministic source hash for stale-translation detection.' );

    echo "Translation workflow smoke test passed.\n";
}
