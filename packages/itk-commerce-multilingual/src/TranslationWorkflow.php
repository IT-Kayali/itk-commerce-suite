<?php
/**
 * Translation lookup and draft/review/publish workflow service.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslationWorkflow {
    /** @var TranslationSchema */
    private $schema;

    /** @var TranslationRepository */
    private $repository;

    /** @var LanguageContext */
    private $context;

    /**
     * @param TranslationSchema     $schema Translation workflow schema.
     * @param TranslationRepository $repository Translation repository.
     * @param LanguageContext       $context Current language context.
     */
    public function __construct( TranslationSchema $schema, TranslationRepository $repository, LanguageContext $context ) {
        $this->schema     = $schema;
        $this->repository = $repository;
        $this->context    = $context;
    }

    /** @return void */
    public function register() {
        add_filter( 'itk_commerce_translate_text', array( $this, 'filter_text' ), 10, 3 );
        add_filter( 'itk_commerce_translation_repository', array( $this, 'filter_repository' ) );
        add_filter( 'itk_commerce_translation_workflow', array( $this, 'filter_workflow' ) );
    }

    /**
     * Resolve only published translations. Draft/review revisions never leak
     * into customer-facing output.
     *
     * @param string $source Default/source text.
     * @param string $key Stable translation key.
     * @param string $language_code Optional explicit language code.
     * @return string
     */
    public function filter_text( $source, $key, $language_code = '' ) {
        return $this->translate( $key, $source, $language_code );
    }

    /**
     * @param string $key Stable translation key.
     * @param mixed  $source Default/source text.
     * @param string $language_code Optional explicit language code.
     * @return string
     */
    public function translate( $key, $source = '', $language_code = '' ) {
        $source = $this->schema->normalize_value( $source );
        $code   = $this->schema->normalize_language_code( $language_code );
        if ( '' === $code ) {
            $code = $this->context->code();
        }

        $published = $this->repository->published( $key, $code );
        if ( is_array( $published ) ) {
            return (string) $published['translation_value'];
        }

        $fallback = $this->context->fallback_code();
        if ( '' !== $fallback && $fallback !== $code ) {
            $published = $this->repository->published( $key, $fallback );
            if ( is_array( $published ) ) {
                return (string) $published['translation_value'];
            }
        }

        return $source;
    }

    /**
     * @param string $key Stable translation key.
     * @param string $language_code Target language.
     * @param mixed  $value Translation value.
     * @param mixed  $source Source/default text.
     * @param int    $author_id Optional author ID.
     * @return array<string,mixed>|\WP_Error|false
     */
    public function create_draft( $key, $language_code, $value, $source = '', $author_id = 0 ) {
        $author_id = $this->resolve_user_id( $author_id );
        $revision  = $this->repository->create_draft( $key, $language_code, $value, $source, $author_id );

        if ( is_array( $revision ) ) {
            do_action( 'itk_commerce_translation_draft_created', $revision, $key, $language_code, $source );
        }

        return $revision;
    }

    /** @param int $revision_id Revision ID. @param int $reviewer_id Optional reviewer ID. @return array<string,mixed>|\WP_Error|false */
    public function submit_for_review( $revision_id, $reviewer_id = 0 ) {
        $reviewer_id = $this->resolve_user_id( $reviewer_id );
        $revision    = $this->repository->transition(
            $revision_id,
            TranslationSchema::STATUS_DRAFT,
            TranslationSchema::STATUS_REVIEW,
            $reviewer_id
        );

        if ( is_array( $revision ) ) {
            do_action( 'itk_commerce_translation_submitted_for_review', $revision );
        }

        return $revision;
    }

    /** @param int $revision_id Revision ID. @param int $reviewer_id Optional reviewer ID. @return array<string,mixed>|\WP_Error|false */
    public function return_to_draft( $revision_id, $reviewer_id = 0 ) {
        $reviewer_id = $this->resolve_user_id( $reviewer_id );
        $revision    = $this->repository->transition(
            $revision_id,
            TranslationSchema::STATUS_REVIEW,
            TranslationSchema::STATUS_DRAFT,
            $reviewer_id
        );

        if ( is_array( $revision ) ) {
            do_action( 'itk_commerce_translation_returned_to_draft', $revision );
        }

        return $revision;
    }

    /** @param int $revision_id Revision ID. @param int $reviewer_id Optional reviewer ID. @return array<string,mixed>|\WP_Error|false */
    public function publish( $revision_id, $reviewer_id = 0 ) {
        $reviewer_id = $this->resolve_user_id( $reviewer_id );
        $revision    = $this->repository->transition(
            $revision_id,
            TranslationSchema::STATUS_REVIEW,
            TranslationSchema::STATUS_PUBLISHED,
            $reviewer_id
        );

        if ( is_array( $revision ) ) {
            do_action( 'itk_commerce_translation_published', $revision );
        }

        return $revision;
    }

    /** @param mixed $repository Existing value. @return TranslationRepository */
    public function filter_repository( $repository ) {
        unset( $repository );
        return $this->repository;
    }

    /** @param mixed $workflow Existing value. @return TranslationWorkflow */
    public function filter_workflow( $workflow ) {
        unset( $workflow );
        return $this;
    }

    /** @return TranslationRepository */
    public function repository() {
        return $this->repository;
    }

    /** @return TranslationSchema */
    public function schema() {
        return $this->schema;
    }

    /** @param int $user_id Explicit ID. @return int */
    private function resolve_user_id( $user_id ) {
        $user_id = max( 0, (int) $user_id );
        if ( 0 === $user_id && function_exists( 'get_current_user_id' ) ) {
            $user_id = max( 0, (int) get_current_user_id() );
        }
        return $user_id;
    }
}
