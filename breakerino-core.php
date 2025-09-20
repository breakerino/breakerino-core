<?php
/**
 * Plugin Name: Breakerino Core
 * Plugin URI:  https://breakerino.me
 * Description: Core dependency plugin that provides essential functionality and services to other Breakerino plugins.
 * Version:     1.0.0
 * Author:      Breakerino
 * Author URI:  https://breakerino.me
 * Text Domain: breakerino-core
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 8.1
 *
 * @package   Breakerino
 * @author    Breakerino
 * @link      https://breakerino.me
 * @copyright 2025 Breakerino
 */

defined( 'ABSPATH' ) || exit;

define( 'BREAKERINO_CORE_PLUGIN_FILE', __FILE__ );
define( 'BREAKERINO_CORE_PLUGIN_VERSION', '1.0.0' );
define( 'BREAKERINO_CORE_PLUGIN_VERSION_DATE', '2025-02-27' );
define( 'BREAKERINO_CORE_PLUGIN_DEPENDENCIES', [] );

// Include autoloader
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

/**
 * Returns the main plugin instance
 *
 * @since  1.0.0
 * @return Breakerino\Core\Plugin
 */
function BreakerinoCore() { // BreakerinoCore
	return Breakerino\Core\Plugin::instance();
}

BreakerinoCore();

add_action('plugins_loaded', function() {
	do_action('breakerino_core_init');
}, 10);