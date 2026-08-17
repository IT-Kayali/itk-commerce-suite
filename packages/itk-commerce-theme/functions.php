<?php
/**
 * IT-Kayali Commerce theme bootstrap.
 *
 * @package ITK_Commerce_Theme
 */

namespace ITK\Commerce\Theme;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0-dev';
const DIR     = __DIR__;

require_once DIR . '/inc/setup.php';
require_once DIR . '/inc/assets.php';
require_once DIR . '/inc/template-tags.php';
require_once DIR . '/inc/layouts.php';
require_once DIR . '/inc/woocommerce.php';
require_once DIR . '/inc/commerce-models.php';
require_once DIR . '/inc/mobile-navigation.php';

/**
 * Public hook for integrations that need to know when the theme bootstrap is loaded.
 */
do_action( 'itk_commerce_theme_loaded', VERSION );
