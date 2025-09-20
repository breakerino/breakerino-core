<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > Benchmark
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class Benchmark {

	public static $startTime 	= null;
	public static $duration 	= null;

	public static function start() {
		self::$startTime = microtime(true);
	}

	public static function stop() {
		self::$duration = microtime(true) - self::$startTime;
		self::$startTime = null;
	}

	public static function get_raw_time() {
		return self::$duration;
	}

	public static function get_time() {
		return self::$duration ? sprintf( 'Executed in: %s seconds.', self::$duration ): false;
	}

}