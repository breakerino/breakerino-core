<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino | Helpers | Cache
 * ------------------------------------------------------------------------------
 * @updated    	13/09/2022
 * @version			1.0.0
 * @author     	Matúš Mendel
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class Cache {
	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	public const DEFAULT_CACHE_PROVIDER = 'wp_transient';
	
	/**
	 * Undocumented variable
	 *
	 * @var array
	 */
	public const CACHE_PROVIDERS = [
		'redis' => [
			'class' => '\Credis_Client',
			'config' => [
				'host' => '127.0.0.1',
				'port' => 6379 // TODO
			]
		],
		'wp_transient' => [
			'class' => __NAMESPACE__ . '\TransientCacheClient',
			'config' => [
				'ttl' => \MINUTE_IN_SECONDS * 10,
				'prefix' => 'wa',
			]
		]
	];
	
	/**
	 * Undocumented variable
	 *
	 * @var object
	 */
	private $client;
	
	/**
	 * Undocumented variable
	 *
	 * @var object
	 */
	private $clientConfig = self::CACHE_PROVIDERS[self::DEFAULT_CACHE_PROVIDER]['config'];
	
	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	private $provider = self::DEFAULT_CACHE_PROVIDER;
	
	/**
	 * Undocumented function
	 *
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->set_props($config);
		$this->set_client($this->provider);
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
			if ( is_array($this->{$name}) ) {
				$this->{$name} = array_merge($value, $this->{$name});
			} else {
				$this->{$name} = $value;
			}
		}
	}
	
	/**
	 * Undocumented function
	 *
	 * @param [type] $provider
	 * @return void
	 */
	private function set_client($provider) {
		if ( ! array_key_exists($provider, self::CACHE_PROVIDERS) ) {
			throw new \InvalidArgumentException("Cache provider '$provider' not supported");
		}
		
		$providerClassName = self::CACHE_PROVIDERS[$provider]['class'];
		
		if ( empty($providerClassName) || ! is_string($providerClassName) || ! class_exists($providerClassName) ) {
			throw new \InvalidArgumentException("Cache provider class '$providerClassName' does not exists.");
		}
		
		$this->client = new $providerClassName($this->clientConfig);
	}
	
	/**
	 * Undocumented function
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		//Logger::debug('[Cache] Getting value for key %s...', $key);
		$value = $this->client->get($key);
		//Logger::debug('[Cache] Got value %s for key %s.', json_encode($value), $key);
		return $value;
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
		//Logger::debug('[Cache] Setting value %s for key %s...', json_encode($value), $key);
		return $this->client->set($key, $value, $ttl);
	}
}