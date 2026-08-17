<?php
/**
 * Module registry and dependency resolver.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Modules;

use ITK\Commerce\Core\Contracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class ModuleRegistry {
    /** @var string */
    private $core_version;

    /** @var array<string,ModuleInterface> */
    private $modules = array();

    /** @var array<string,string[]> */
    private $errors = array();

    /** @var string[] */
    private $booted = array();

    /**
     * @param string $core_version Current Commerce Core version.
     */
    public function __construct( $core_version ) {
        $this->core_version = (string) $core_version;
    }

    /**
     * Register an installed module without booting it yet.
     *
     * @param ModuleInterface $module Module instance.
     * @return bool
     */
    public function register( ModuleInterface $module ) {
        $id = sanitize_key( $module->id() );

        if ( '' === $id || $id !== $module->id() ) {
            $this->add_error( $id ?: 'unknown', 'invalid_module_id' );
            return false;
        }

        if ( isset( $this->modules[ $id ] ) ) {
            $this->add_error( $id, 'duplicate_module_id' );
            return false;
        }

        $this->modules[ $id ] = $module;
        return true;
    }

    /**
     * Boot enabled modules in dependency order.
     *
     * @param string[] $enabled_module_ids Enabled module identifiers.
     * @return string[] Successfully booted module identifiers.
     */
    public function boot( array $enabled_module_ids ) {
        $enabled = array_values( array_unique( array_filter( array_map( 'sanitize_key', $enabled_module_ids ) ) ) );
        $pending = array();

        foreach ( $enabled as $id ) {
            if ( ! isset( $this->modules[ $id ] ) ) {
                $this->add_error( $id, 'module_not_installed' );
                continue;
            }

            $module = $this->modules[ $id ];

            if ( ! $this->environment_is_compatible( $module, $enabled ) ) {
                continue;
            }

            $pending[ $id ] = $module;
        }

        while ( ! empty( $pending ) ) {
            $progress = false;

            foreach ( $pending as $id => $module ) {
                $dependencies = $this->module_dependencies( $module );

                if ( array_diff( $dependencies, $this->booted ) ) {
                    continue;
                }

                try {
                    $module->register();
                    $this->booted[] = $id;
                    unset( $pending[ $id ] );
                    $progress = true;

                    do_action( 'itk_commerce_module_loaded', $id, $module );
                } catch ( \Throwable $throwable ) {
                    $this->add_error( $id, 'module_boot_failed' );
                    unset( $pending[ $id ] );
                    $progress = true;

                    do_action( 'itk_commerce_module_boot_error', $id, $throwable );
                }
            }

            if ( ! $progress ) {
                foreach ( array_keys( $pending ) as $id ) {
                    $this->add_error( $id, 'dependency_cycle_or_unresolved_dependency' );
                }
                break;
            }
        }

        return $this->booted;
    }

    /**
     * @return array<string,ModuleInterface>
     */
    public function all() {
        return $this->modules;
    }

    /**
     * @param string $id Module identifier.
     * @return ModuleInterface|null
     */
    public function get( $id ) {
        $id = sanitize_key( $id );
        return isset( $this->modules[ $id ] ) ? $this->modules[ $id ] : null;
    }

    /**
     * @return string[]
     */
    public function booted() {
        return $this->booted;
    }

    /**
     * @return array<string,string[]>
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Validate runtime requirements before booting a module.
     *
     * @param ModuleInterface $module Module instance.
     * @param string[]        $enabled Enabled module identifiers.
     * @return bool
     */
    private function environment_is_compatible( ModuleInterface $module, array $enabled ) {
        $requirements = $module->requirements();
        $id           = $module->id();
        $compatible   = true;

        if ( ! is_array( $requirements ) ) {
            $this->add_error( $id, 'invalid_requirements' );
            return false;
        }

        $versions = array(
            'core'        => $this->core_version,
            'php'         => PHP_VERSION,
            'wordpress'   => get_bloginfo( 'version' ),
            'woocommerce' => defined( 'WC_VERSION' ) ? WC_VERSION : null,
        );

        foreach ( array( 'core', 'php', 'wordpress', 'woocommerce' ) as $key ) {
            if ( empty( $requirements[ $key ] ) ) {
                continue;
            }

            if ( null === $versions[ $key ] || version_compare( (string) $versions[ $key ], (string) $requirements[ $key ], '<' ) ) {
                $this->add_error( $id, 'requires_' . $key . '_' . sanitize_key( (string) $requirements[ $key ] ) );
                $compatible = false;
            }
        }

        foreach ( $this->module_dependencies( $module ) as $dependency ) {
            if ( ! in_array( $dependency, $enabled, true ) ) {
                $this->add_error( $id, 'dependency_not_enabled_' . $dependency );
                $compatible = false;
            } elseif ( ! isset( $this->modules[ $dependency ] ) ) {
                $this->add_error( $id, 'dependency_not_installed_' . $dependency );
                $compatible = false;
            }
        }

        return $compatible;
    }

    /**
     * @param ModuleInterface $module Module instance.
     * @return string[]
     */
    private function module_dependencies( ModuleInterface $module ) {
        $requirements = $module->requirements();
        $dependencies = isset( $requirements['modules'] ) && is_array( $requirements['modules'] ) ? $requirements['modules'] : array();

        return array_values( array_unique( array_filter( array_map( 'sanitize_key', $dependencies ) ) ) );
    }

    /**
     * @param string $id    Module identifier.
     * @param string $error Machine-readable error code.
     * @return void
     */
    private function add_error( $id, $error ) {
        if ( ! isset( $this->errors[ $id ] ) ) {
            $this->errors[ $id ] = array();
        }

        if ( ! in_array( $error, $this->errors[ $id ], true ) ) {
            $this->errors[ $id ][] = $error;
        }
    }
}
