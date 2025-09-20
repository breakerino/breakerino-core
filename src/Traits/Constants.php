<?php
/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > Constants
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

use \Breakerino\Core\Exceptions\Generic as GenericException;

defined('ABSPATH') || exit;

trait Constants {
	
	/**
	 * Get global constant related to class
	 *
	 * @param string $name
	 * @param boolean $allowEmpty
	 * @return mixed
	 */
	final public function get_global_constant(string $name, bool $allowEmpty = true, $default = null) {
		$constantName = strtoupper($this->get_prefix() . $name);
		
		if (!defined($constantName) || (! $allowEmpty && empty(constant($constantName)))) {
			if ( ! is_null($default) ) return $default;
			throw new GenericException('Global constant "%s" is not defined', [$constantName], null, static::class . '\Constants');
		}
		
		return constant($constantName);
	}

	/**
	 * Get inherited class constant
	 *
	 * @param string $name
	 * @param boolean $allowEmpty
	 * @param mixed $default
	 * @return mixed
	 */
	final public function get_class_constant(string $name, bool $allowEmpty = true, $default = null) {
		$constantName = strtoupper($name);
		
		if ((!defined("static::$constantName")) || (! $allowEmpty && empty( constant("static::$constantName")) ) ) {
			if ( ! is_null($default) ) return $default;
			throw new GenericException('Class constant %s is not defined in class %s.', [$constantName, static::class], null, static::class . '\Constants');
		}
		
		return constant("static::$constantName") ?? $default;
	}
}
