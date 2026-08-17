<?php
/**
 * Bounded translation identity and workflow schema.
 *
 * @package ITK_Commerce_Multilingual
 */

namespace ITK\Commerce\Multilingual;

defined( 'ABSPATH' ) || exit;

final class TranslationSchema {
    const STATUS_DRAFT     = 'draft';
    const STATUS_REVIEW    = 'review';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED  = 'archived';

    /** @return string[] */
    public function public_statuses() {
        return array( self::STATUS_DRAFT, self::STATUS_REVIEW, self::STATUS_PUBLISHED );
    }

    /** @return string[] */
    public function storage_statuses() {
        return array( self::STATUS_DRAFT, self::STATUS_REVIEW, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED );
    }

    /**
     * Translation keys are stable machine identifiers, not display strings.
     * Examples: commerce.checkout.pay, customer.footer.tagline.
     *
     * @param mixed $key Raw key.
     * @return string
     */
    public function normalize_key( $key ) {
        $key = strtolower( trim( (string) $key ) );
        $key = preg_replace( '/[\s\/]+/', '.', $key );
        $key = preg_replace( '/[^a-z0-9._:-]+/', '-', (string) $key );
        $key = preg_replace( '/\.{2,}/', '.', (string) $key );
        $key = trim( (string) $key, '.:-_' );

        if ( '' === $key || strlen( $key ) > 191 || ! preg_match( '/^[a-z0-9][a-z0-9._:-]*$/', $key ) ) {
            return '';
        }

        return $key;
    }

    /** @param mixed $code Raw language code. @return string */
    public function normalize_language_code( $code ) {
        $code = strtolower( str_replace( '_', '-', trim( (string) $code ) ) );
        return preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $code ) ? $code : '';
    }

    /** @param mixed $status Raw status. @return string */
    public function normalize_status( $status ) {
        $status = strtolower( trim( (string) $status ) );
        return in_array( $status, $this->storage_statuses(), true ) ? $status : '';
    }

    /**
     * Values may contain controlled markup owned by the consuming component,
     * therefore persistence does not run display-context escaping here.
     *
     * @param mixed $value Raw translation value.
     * @return string
     */
    public function normalize_value( $value ) {
        if ( is_scalar( $value ) || null === $value ) {
            return (string) $value;
        }
        return '';
    }

    /** @param mixed $source Source string. @return string */
    public function source_hash( $source ) {
        return hash( 'sha256', (string) $source );
    }

    /**
     * Revisions move forward through review. Published revisions are immutable;
     * editing a published translation creates a new draft revision instead.
     *
     * @param string $from Current status.
     * @param string $to Target status.
     * @return bool
     */
    public function can_transition( $from, $to ) {
        $from = $this->normalize_status( $from );
        $to   = $this->normalize_status( $to );

        $allowed = array(
            self::STATUS_DRAFT => array( self::STATUS_REVIEW ),
            self::STATUS_REVIEW => array( self::STATUS_DRAFT, self::STATUS_PUBLISHED ),
            self::STATUS_PUBLISHED => array( self::STATUS_ARCHIVED ),
            self::STATUS_ARCHIVED => array(),
        );

        return '' !== $from && '' !== $to && in_array( $to, $allowed[ $from ], true );
    }

    /**
     * @param mixed $key Translation key.
     * @param mixed $language_code Public language code.
     * @return array{key:string,language_code:string}|null
     */
    public function identity( $key, $language_code ) {
        $key           = $this->normalize_key( $key );
        $language_code = $this->normalize_language_code( $language_code );

        if ( '' === $key || '' === $language_code ) {
            return null;
        }

        return array(
            'key'           => $key,
            'language_code' => $language_code,
        );
    }
}
