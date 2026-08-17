<?php
/**
 * Normalize checkbox fields that are omitted by HTML forms when unchecked.
 *
 * @package ITK_Commerce_Search_Filter
 */

namespace ITK\Commerce\SearchFilter\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Ensure builder booleans with schema defaults of true can explicitly be saved
 * as false. WordPress/PHP omit unchecked checkboxes from the POST payload.
 *
 * Runs before FilterBuilderPage::save() at the default priority 10.
 *
 * @return void
 */
function normalize_posted_definitions() {
    if ( ! isset( $_POST['definitions'] ) || ! is_array( $_POST['definitions'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the save handler validates the nonce immediately afterwards.
        return;
    }

    $definitions = wp_unslash( $_POST['definitions'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    foreach ( $definitions as $index => $definition ) {
        if ( ! is_array( $definition ) ) {
            continue;
        }

        $definition['enabled'] = ! empty( $definition['enabled'] ) ? '1' : '0';

        $type = isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : '';
        if ( 'taxonomy' === $type ) {
            $definition['multiple'] = ! empty( $definition['multiple'] ) ? '1' : '0';
        }

        $definitions[ $index ] = $definition;
    }

    $_POST['definitions'] = $definitions; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- normalized for the nonce-protected save handler.
}
