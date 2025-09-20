<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino | Helpers | Transient Cache Client
 * ------------------------------------------------------------------------------
 * @updated    	13/09/2022
 * @version			1.0.0
 * @author     	Matúš Mendel
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class TransientCacheClient {
	private $prefix = 'wp';
	private $ttl = \MINUTE_IN_SECONDS;

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct($config) {
		$this->set_props($config);
	}

	private function get_cache_key(string $key) {
		return sprintf('%s_%s', $this->prefix, $key);;
	}

	/**
	 * Load config and set required properties
	 * 
	 * @var     array      $props
	 * @access  protected
	 */
	protected function set_props($props): void {
		foreach ($props as $name => $value) {
			if (!property_exists($this, $name)) {
				continue;
			}
			$this->{$name} = $value;
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		$cacheKey = $this->get_cache_key($key);
		// NOTE: Maybe use cache groups and single transient to reduce db queries
		$cache = \get_transient($cacheKey);
		return $cache;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param null|integer $ttl
	 * @return boolean
	 */
	public function set($key, $value, $ttl = null) {
		$cacheKey = $this->get_cache_key($key);
		return \set_transient($cacheKey, $value, \MINUTE_IN_SECONDS);
	}
}