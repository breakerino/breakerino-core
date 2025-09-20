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

use \Breakerino\Core\Exceptions\Generic as GenericException;

defined('ABSPATH') || exit;

trait Hooks {
	/**
	 * Register single hook
	 *
	 * @param array $args
	 * @return void
	 */
	private function register_hook(array $args) {
		// TODO: Sanitize passed args (wp_parse_args?)
		
		// Check if provided hook type is valid.
		if (!in_array($args['type'], ['filter', 'action'])) {
			throw new GenericException('"%1$s" is not a valid hook type.', [$args['type']], null, static::class . '\Hooks');
		}

		// Adjust callback if it should be called from parent class.
		if (is_array($args['callback']) && reset($args['callback']) === '$this') {
			$args['callback'] = [$this, $args['callback'][1]];
		} else if (array_key_exists('class', $args)) {
			$args['callback'] = [$args['class'], $args['callback']];
		}

		// Check if hook callback is valid.
		if (!is_callable($args['callback'])) {
			throw new GenericException('"%1$s" is not a valid callback (%2$s)', [json_encode($args['callback']), json_encode($args['hooks'])], null,);
		}

		foreach ($args['hooks'] as $hookName) {
			// Register hook
			call_user_func('add_' . $args['type'], $hookName, $args['callback'], $args['priority'], $args['args'] ?? 0);
		};
	}


	/**
	 * Register provided action & filter  hooks
	 *
	 * @param array $hooks
	 * @return void
	 */
	private function register_hooks(array $hooks): void {
		array_walk($hooks, [$this, 'register_hook']);
	}
}
