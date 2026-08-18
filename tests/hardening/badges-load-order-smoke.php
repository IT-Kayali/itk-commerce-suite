<?php
/**
 * Ensure the badges plugin can be loaded before Commerce Core without fatalling.
 *
 * WordPress loads active plugin files before firing plugins_loaded. Because plugin
 * file order is not a safe dependency boundary, the badges module class must not
 * implement the Core ModuleInterface until that interface is actually available.
 */

$root = dirname( __DIR__, 2 );

define( 'ABSPATH', $root . '/' );

$GLOBALS['itk_badges_test_actions'] = array();

/**
 * Minimal WordPress action stub used while dependencies are intentionally absent.
 *
 * @param string   $hook Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $accepted_args Accepted args.
 * @return true
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['itk_badges_test_actions'][] = array( $hook, $callback, $priority, $accepted_args );
    return true;
}

require $root . '/packages/itk-commerce-badges/itk-commerce-badges.php';

$plugins_loaded_registered = false;
foreach ( $GLOBALS['itk_badges_test_actions'] as $action ) {
    if ( 'plugins_loaded' === $action[0] ) {
        $plugins_loaded_registered = true;
        break;
    }
}

if ( ! $plugins_loaded_registered ) {
    throw new RuntimeException( 'Badges plugin did not defer initialization to plugins_loaded.' );
}

if ( class_exists( 'ITK\\Commerce\\Badges\\BadgesModule', false ) ) {
    throw new RuntimeException( 'Badges module class loaded before Commerce Core dependencies were available.' );
}

fwrite( STDOUT, "Badges plugin load-order smoke test passed.\n" );
