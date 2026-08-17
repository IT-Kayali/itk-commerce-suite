<?php
/**
 * Dependency-free smoke test for the Layouts module contract.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );
define( 'ITK\\Commerce\\Layouts\\VERSION', '0.1.0-dev' );
define( 'ITK\\Commerce\\Layouts\\MODULE_ID', 'itk-commerce-layouts' );

require dirname( __DIR__, 2 ) . '/packages/itk-commerce-core/src/Contracts/ModuleInterface.php';
require dirname( __DIR__, 2 ) . '/packages/itk-commerce-layouts/src/LayoutsModule.php';

function itk_layouts_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
}

$module       = new \ITK\Commerce\Layouts\LayoutsModule();
$requirements = $module->requirements();

itk_layouts_assert( 'itk-commerce-layouts' === $module->id(), 'Stable module ID is declared.' );
itk_layouts_assert( '0.1.0-dev' === $module->version(), 'Module version matches the package version.' );
itk_layouts_assert( '0.1.0-dev' === $requirements['core'], 'Minimum Core version is declared.' );
itk_layouts_assert( '8.1' === $requirements['php'], 'Minimum PHP version is declared.' );
itk_layouts_assert( '6.6' === $requirements['wordpress'], 'Minimum WordPress version is declared.' );
itk_layouts_assert( isset( $requirements['modules'] ) && array() === $requirements['modules'], 'Module dependency list is explicit.' );

echo "Layouts module contract smoke test passed.\n";
