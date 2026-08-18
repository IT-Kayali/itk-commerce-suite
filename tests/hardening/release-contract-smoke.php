<?php
/**
 * Release contract smoke test for the Phase 6-9 package set.
 */

$root = dirname( __DIR__, 2 );
$expected = array(
    'itk-commerce-theme',
    'itk-commerce-core',
    'itk-commerce-layouts',
    'itk-commerce-search-filter',
    'itk-commerce-multilingual',
    'itk-commerce-documents',
    'itk-commerce-elementor',
    'itk-commerce-badges',
    'itk-commerce-wishlist-compare',
    'itk-commerce-gift-boxes',
    'itk-commerce-code-manager',
);

foreach ( $expected as $package ) {
    $path = $root . '/packages/' . $package;
    if ( ! is_dir( $path ) ) {
        throw new RuntimeException( 'Missing package directory: ' . $package );
    }

    if ( 'itk-commerce-theme' !== $package ) {
        $bootstrap = $path . '/' . $package . '.php';
        if ( ! is_file( $bootstrap ) ) {
            throw new RuntimeException( 'Missing plugin bootstrap: ' . $bootstrap );
        }
    }

    $manifest = $path . '/compatibility.json';
    if ( ! is_file( $manifest ) ) {
        throw new RuntimeException( 'Missing compatibility manifest: ' . $package );
    }

    $data = json_decode( file_get_contents( $manifest ), true, 512, JSON_THROW_ON_ERROR );
    if ( ! isset( $data['package'] ) || $data['package'] !== $package ) {
        throw new RuntimeException( 'Compatibility manifest package mismatch: ' . $package );
    }
}

$required_docs = array(
    'docs/ROADMAP.md',
    'docs/HARDENING.md',
    'docs/PHASE-0-AUDIT.md',
    'docs/AL-LORD-ROLLOUT.md',
);
foreach ( $required_docs as $relative ) {
    if ( ! is_file( $root . '/' . $relative ) ) {
        throw new RuntimeException( 'Missing release document: ' . $relative );
    }
}

$profile_dir = $root . '/client-profiles/al-lord';
if ( ! is_dir( $profile_dir ) ) {
    throw new RuntimeException( 'Missing Al-Lord reference profile directory.' );
}

fwrite( STDOUT, "Phase 6-9 release contract smoke test passed.\n" );
