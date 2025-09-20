<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > Hooks
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     14/12/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

use \Breakerino\Core\Exceptions\Generic as GenericException;

defined('ABSPATH') || exit;

trait Shortcodes {
	/**
	 * Register single shortcode
	 *
	 * @param array $args
	 * @return void
	 */
	private function register_shortcode(array $args, string $tag = ''): void {
		$args = \wp_parse_args($args, [
			'callback' => null,
			'class' => null
		]);

		// Check if shortcode with same tag already exists.
		if (empty($tag)) {
			throw new GenericException('No shortcode tag has been provided.', [$tag], null, static::class . '\Shortcodes');
		}

		// Check if shortcode with same tag already exists.
		if (\shortcode_exists($tag)) {
			throw new GenericException('Shortcode "%1$s" already exists.', [$tag], null, static::class . '\Shortcodes');
		}

		// Adjust callback if it should be called from parent class.
		if (is_array($args['callback']) && reset($args['callback']) === '$this') {
			$args['callback'] = [$this, $args['callback'][1]];
		} else if (array_key_exists('class', $args)) {
			$args['callback'] = [$args['class'], $args['callback']];
		}

		// Check if shortcode callback is valid.
		if (!is_callable($args['callback'])) {
			throw new GenericException('"%1$s" is not a valid shortcode callback (%2$s)', [json_encode($args['callback']), $tag], null, static::class . '\Shortcodes');
		}

		// Register shortcode
		call_user_func('add_shortcode', $tag, $args['callback']);
	}

	/**
	 * Register provided shortcodes
	 *
	 * @param array $shortcode
	 * @return void
	 */
	private function register_shortcodes(array $shortcodes): void {
		array_walk($shortcodes, [$this, 'register_shortcode']);
	}
}
