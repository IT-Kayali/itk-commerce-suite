<?php
/**
 * Code Manager module definition.
 *
 * @package ITK_Commerce_Code_Manager
 */

namespace ITK\Commerce\CodeManager;

use ITK\Commerce\Core\Contracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class CodeManagerModule implements ModuleInterface {
    /** @var SnippetRepository */
    private $repository;

    /** @return string */
    public function id() { return MODULE_ID; }

    /** @return string */
    public function version() { return VERSION; }

    /** @return array<string,mixed> */
    public function requirements() {
        return array(
            'core'      => '0.1.0-dev',
            'php'       => '8.1',
            'wordpress' => '6.6',
            'modules'   => array(),
        );
    }

    /** @return void */
    public function register() {
        $this->repository = new SnippetRepository();
        $matcher = new ConditionMatcher();
        $runtime = new SnippetRuntime( $this->repository, $matcher );
        $runtime->register();

        if ( is_admin() ) {
            ( new AdminPage( $this->repository ) )->register();
        }

        add_filter( 'itk_commerce_code_snippet_repository', array( $this, 'repository_filter' ) );
        do_action( 'itk_commerce_code_manager_loaded', $this, $this->repository );
    }

    /** @param mixed $existing Existing repository. @return SnippetRepository */
    public function repository_filter( $existing ) {
        unset( $existing );
        return $this->repository;
    }
}
