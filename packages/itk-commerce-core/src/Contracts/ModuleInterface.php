<?php
/**
 * Contract implemented by installable Commerce Suite modules.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Contracts;

defined( 'ABSPATH' ) || exit;

interface ModuleInterface {
    /**
     * Stable machine-readable module identifier.
     *
     * @return string
     */
    public function id();

    /**
     * Installed module version.
     *
     * @return string
     */
    public function version();

    /**
     * Register hooks, services and extension points for this module.
     *
     * @return void
     */
    public function register();
}
