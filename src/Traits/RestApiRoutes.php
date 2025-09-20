<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > RestApi
 * ------------------------------------------------------------------------------
 * @created     16/06/2022
 * @updated     19/03/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

defined('ABSPATH') || exit;

use Breakerino\Core\Exceptions\Generic as GenericException;
use Breakerino\Core\Utils\Logger;

trait RestApiRoutes {
	/**
	 * Get REST API config
	 * 
	 * @return array
	 */
	private function get_rest_api_config(array $config): array {
		return array_merge($config, [
			'endpoints' => array_map(
				function ($endpoint) {
					if (!isset($endpoint['callback'])) {
						throw new GenericException('Callback not defined.', [], null);
					}

					// Adjust callback if it should be called from parent class.
					if (is_array($endpoint['callback']) && reset($endpoint['callback']) === '$this') {
						$endpoint['callback'] = [$this, $endpoint['callback'][1]];
					} else if (array_key_exists('class', $endpoint)) {
						$endpoint['callback'] = [$endpoint['class'], $endpoint['callback']];
					}

					// Check if hook callback is valid.
					if (!is_callable($endpoint['callback'])) {
						throw new GenericException('"%1$s" is not a valid callback', [json_encode($endpoint['callback'])], null);
					}

					// Adjust callback if it should be called from parent class.
					if (isset($endpoint['permission_callback'])) {
						if (is_array($endpoint['permission_callback']) && reset($endpoint['permission_callback']) === '$this') {
							$endpoint['permission_callback'] = [$this, $endpoint['permission_callback'][1]];
						} else if (array_key_exists('class', $endpoint)) {
							$endpoint['permission_callback'] = [$endpoint['class'], $endpoint['permission_callback']];
						}
						
						// Check if hook callback is valid.
						if (!is_callable($endpoint['permission_callback'])) {
							Logger::warning('"%1$s" is not a valid permission callback', json_encode($endpoint['permission_callback']));
							$endpoint['permission_callback'] = null;
						}
					}

					return $endpoint;
				},
				$config['endpoints']
			)
		]);
	}

	/**
	 * Register plugin REST API endpoints
	 *
	 * @return void
	 */
	public function initialize_rest_api(): void {
		if (!defined('self::PLUGIN_REST_API_CONFIG')) {
			return;
		}

		$config = $this->get_rest_api_config(self::PLUGIN_REST_API_CONFIG);
		$config = apply_filters('breakerino/rest_api/config/' . $config['namespace'] . '/' . $config['version'], $config);
		new \Breakerino\Core\Utils\RestApi($config);
	}
}
