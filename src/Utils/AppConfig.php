<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > Console
 * ------------------------------------------------------------------------------
 * @created     28/09/2021
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class AppConfig {
	protected $namespace = 'breakerino';
	protected $config = [];

	public function __construct($props) {
		$this->set_props($props);
	}

	public function add() {
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

			$this->{$name} = is_array($this->{$name}) ? array_merge($value, $this->{$name}) : $value;
		}
	}
}
