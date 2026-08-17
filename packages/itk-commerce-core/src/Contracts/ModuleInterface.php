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
     * Declare minimum environment and module dependencies.
     *
     * Supported keys: core, php, wordpress, woocommerce and modules.
     * The modules value must be an array of module identifiers.
     *
     * @return array<string,mixed>
     */
    public function requirements();

    /**
     * Register hooks, services and extension points for this module.
     *
     * @return void
     */
    public function register();
}
