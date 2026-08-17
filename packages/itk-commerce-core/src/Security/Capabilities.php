<?php
/**
 * Commerce Suite roles and capabilities foundation.
 *
 * @package ITK_Commerce_Core
 */

namespace ITK\Commerce\Core\Security;

defined( 'ABSPATH' ) || exit;

final class Capabilities {
    /**
     * @return string[]
     */
    public static function all() {
        return array(
            'itk_manage_commerce',
            'itk_manage_design',
            'itk_manage_modules',
            'itk_manage_profiles',
            'itk_manage_translations',
            'itk_manage_documents',
        );
    }

    /**
     * Install suite-specific roles and grant administrative capabilities.
     * Existing roles are extended without removing unrelated permissions.
     *
     * @return void
     */
    public static function install() {
        add_role(
            'itk_designer',
            __( 'Commerce Designer', 'itk-commerce-core' ),
            array(
                'read'              => true,
                'itk_manage_design' => true,
            )
        );

        add_role(
            'itk_translator',
            __( 'Commerce Translator', 'itk-commerce-core' ),
            array(
                'read'                    => true,
                'itk_manage_translations' => true,
            )
        );

        add_role(
            'itk_document_manager',
            __( 'Commerce Document Manager', 'itk-commerce-core' ),
            array(
                'read'                 => true,
                'itk_manage_documents' => true,
            )
        );

        self::grant_all_to_role( 'administrator' );
        self::grant_shop_manager_capabilities();
    }

    /**
     * Keep persisted roles/capabilities on deactivation. Removing them while
     * users are assigned could unexpectedly lock administrators out of suite
     * functionality. Permanent cleanup belongs to an explicit uninstall flow.
     *
     * @return void
     */
    public static function deactivate() {}

    /**
     * @param string $role_name WordPress role slug.
     * @return void
     */
    private static function grant_all_to_role( $role_name ) {
        $role = get_role( $role_name );

        if ( ! $role ) {
            return;
        }

        foreach ( self::all() as $capability ) {
            $role->add_cap( $capability );
        }
    }

    /**
     * Grant shop managers operational capabilities but not module/code-level
     * administration by default.
     *
     * @return void
     */
    private static function grant_shop_manager_capabilities() {
        $role = get_role( 'shop_manager' );

        if ( ! $role ) {
            return;
        }

        foreach ( array( 'itk_manage_commerce', 'itk_manage_profiles', 'itk_manage_documents' ) as $capability ) {
            $role->add_cap( $capability );
        }
    }
}
