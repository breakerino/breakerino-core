<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > Hooks
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     08/04/2023
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

defined('ABSPATH') || exit;

trait Singleton {
	/**
	 * Instances
	 *
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * Singleton instance
	 *
	 * @access public
	 */
	final public static function instance() {
		$className = get_called_class();
		
		// Hotfix: Do not initialize in WP CLI context
		if (defined('WP_CLI') && \WP_CLI) {
			return new $className(); // TODO
		}

		if (!isset(self::$instances[$className]) || !(self::$instances[$className] instanceof $className)) {
			self::$instances[$className] = new $className();
		}
		return self::$instances[$className];
	}
	
	/**
	 * Private constructor
	 *
	 * @access private
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Default init method
	 * This method can be overridden if needed.
	 *
	 * @access protected
	 */
	protected function init() {}

	/**
	 * Prevent clonning of the singleton object
	 *
	 * @access private
	 */
	private function __clone() {}

	/**
	 * Prevent de-serializing of the Singleton object
	 *
	 * @access private
	 */
	public function __wakeup() {}
}
