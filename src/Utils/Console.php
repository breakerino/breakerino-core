<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > Console
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     08/04/2023
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class Console {
	public static function log($message, $data = [], $module = 'Breakerino') {
		// Disable logging in production mode
		if ( ! \apply_filters('breakerino/core/is_dev_mode', false) ) {
			return;
		}
		
		// Disable logging on frontend and for non-admins, in rest api and cli
		if ((defined('WP_CLI') && \WP_CLI) || (defined('REST_REQUEST') && \REST_REQUEST) || !function_exists('is_user_logged_in') || !\is_user_logged_in() || !\current_user_can('manage_options')) {
			return;
		}

		\add_action('wp_head', function () use ($message, $data, $module) {
			echo sprintf('<script>console.debug(\'[%s] %s\', {data: %s})</script>', $module, $message, json_encode($data));
		});
	}
}