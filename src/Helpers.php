<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Helpers
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     22/01/2025
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core;

defined('ABSPATH') || exit;

class Helpers {
	/**
	 * Get normalized HTTP headers
	 *
	 * @return array
	 */
	public static function get_http_headers() {
		if (isset($_GLOBALS['_HTTP_HEADERS']) && is_array($_GLOBALS['_HTTP_HEADERS'])) {
			return $_GLOBALS['_HTTP_HEADERS'];
		}

		$headers = function_exists('apache_request_headers') ? apache_request_headers() : getallheaders();

		// Normalize keys casing
		$headersKeys = array_map(function ($name) {
			return mb_strtolower($name);
		}, array_keys($headers));
		$headerValues = array_values($headers);

		$_GLOBALS['_HTTP_HEADERS'] = array_combine($headersKeys, $headerValues);
		return $_GLOBALS['_HTTP_HEADERS'];
	}

	/**
	 * Get single HTTP header
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return array
	 */
	public static function get_http_header($name, $default = null) {
		$headers = self::get_http_headers();
		return isset($headers[$name]) ? $headers[$name] : $default;
	}

	/**
	 * Check if current request is REST (with route check)
	 *
	 * @param string $restPath
	 * @return boolean
	 */
	public static function is_rest_request($restPath = '', $method = 'GET') {
		if (empty($_SERVER['REQUEST_URI'])) {
			return false;
		}

		$restEndpointURL = \untrailingslashit(\rest_get_url_prefix()) . $restPath;

		return strpos($_SERVER['REQUEST_URI'], $restEndpointURL) !== false;
	}

	public static function is_cli() {
		return defined('WP_CLI') && \WP_CLI;
	}

	public static function is_breakerino_rest() {
		return self::is_rest_request('/breakerino/v1/'); // && self::get_http_header('x-initiator') === 'breakerino';
	}

	public static function is_dev_mode() {
		return \apply_filters('breakerino/core/is_dev_mode', false);
	}

	public static function is_debug_mode() {
		return \apply_filters('breakerino/core/is_debug_mode', false);
	}
	
	public static function is_frontend_mode_enabled() {
		return \apply_filters('breakerino/core/is_frontend_mode_enabled', false);
	}

	public static function get_post_by_meta($metaKey, $metaValue) {
		global $wpdb;

		$postID = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT postID FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				$metaKey,
				$metaValue
			)
		);

		return $postID ? (int) $postID : null;
	}

	public static function is_request_from_same_origin() {
		// Bypass in dev mode (TODO: Filterable instead)
		if ( self::is_dev_mode() ) {
			return true;
		}

		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) : null;
		return $referer === parse_url( get_site_url(), PHP_URL_HOST );
	}

	public static function convert_array_to_object(array $array) {
		return json_decode(json_encode($array, JSON_FORCE_OBJECT), false);
	}
	
	public static function get_current_scope() {
		return \is_admin() && !\wp_doing_ajax() ? 'admin' : 'public';
	}
	
	public static function get_request_bearer_token() {
		$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim($_SERVER['HTTP_AUTHORIZATION']) : '';

		if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
			return $matches[1] ?? null;
		}
		
		return null;
	}
	
	/**
	 * Gets sanitized query params
	 *
	 * @param array $params
	 * @return array
	 */
	public static function get_sanitized_params(array $params = [], array $exclude = []) {
		if ( empty($params) ) {
			$params = $_GET;
		}
		
		$sanitizedParams = [];

		foreach ($params as $key => $value) {
			if (in_array($key, $exclude)) {
				continue;
			}

			$sanitizedParams[$key] = \sanitize_text_field($value);
		}

		return $sanitizedParams;
	}
}
