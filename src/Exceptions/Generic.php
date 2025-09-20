<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Exceptions > BreakerinoException
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Exceptions;

defined('ABSPATH') || exit;

class Generic extends \Exception {
	const CODE_SEVERITY_MAP = [
		1 => 'error',
		2 => 'warning',
		3 => 'notice',
	];

	public const DEFAULT_MODULE = 'Core';
	public const DEFAULT_CODE = 2;
	public const DEFAULT_MESSAGE = 'An error has occurred';

	public const MESSAGE_PREFIX_FORMAT = '[%s] %s: ';

	public function __construct($message = self::DEFAULT_MESSAGE, $messageArgs = [], $code = self::DEFAULT_CODE, $module = self::DEFAULT_MODULE) {
		$code = array_key_exists($code, self::CODE_SEVERITY_MAP) ? $code : self::DEFAULT_CODE;
		$message = $this->format_message($message, $messageArgs, $module, $code);

		parent::__construct($message, $code);
	}

	protected function format_message($message, $messageArgs, $module, $code) {
		if (empty($message)) {
			return '';
		}

		if (!is_array($messageArgs)) {
			return $message;
		}

		return vsprintf(self::MESSAGE_PREFIX_FORMAT, [$module, ucfirst(self::CODE_SEVERITY_MAP[$code])]) . vsprintf($message, $messageArgs);
	}
}
