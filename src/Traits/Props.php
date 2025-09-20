<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > Hooks
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

defined('ABSPATH') || exit;

trait Props {
	/**
	 * Set class properties
	 * 
	 * @var     array      $props
	 * @access  protected
	 */
	protected function set_props($props): void {
		foreach ($props as $name => $value) {
			// Check if defined
			if (!property_exists($this, $name)) {
				continue;
			}

			// Check if is has same type
			if (gettype($this->{$name}) !== gettype($value)) {
				continue;
			}

			// Set/merge value
			$this->{$name} = is_array($this->{$name}) ? array_merge($this->{$name}, $value) : $value;
		}
	}
}
