<?php
/**
 * Customer/generic package separation smoke test.
 */

$root = dirname( __DIR__, 2 );
$packages = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/packages', FilesystemIterator::SKIP_DOTS ) );
foreach ( $packages as $file ) {
    if ( ! $file->isFile() ) {
        continue;
    }
    $ext = strtolower( $file->getExtension() );
    if ( ! in_array( $ext, array( 'php', 'js', 'css', 'json' ), true ) ) {
        continue;
    }
    $content = file_get_contents( $file->getPathname() );
    if ( preg_match( '/\bAl-Lord\b|\bal-lord\b/i', $content ) ) {
        throw new RuntimeException( 'Reference customer leaked into generic package: ' . $file->getPathname() );
    }
}

$profile = $root . '/client-profiles/al-lord/profile.json';
$data = json_decode( file_get_contents( $profile ), true, 512, JSON_THROW_ON_ERROR );
if ( ( $data['profile_id'] ?? '' ) !== 'al-lord' ) {
    throw new RuntimeException( 'Al-Lord profile identity mismatch.' );
}

$forbidden = array( 'password', 'passwd', 'secret', 'api_key', 'api_secret', 'private_key', 'access_token', 'refresh_token', 'client_secret' );
$walk = function ( $value ) use ( &$walk, $forbidden ) {
    if ( ! is_array( $value ) ) {
        return;
    }
    foreach ( $value as $key => $item ) {
        if ( is_string( $key ) && in_array( strtolower( $key ), $forbidden, true ) ) {
            throw new RuntimeException( 'Forbidden secret-like profile key: ' . $key );
        }
        $walk( $item );
    }
};
$walk( $data );

fwrite( STDOUT, "Customer boundary smoke test passed.\n" );
