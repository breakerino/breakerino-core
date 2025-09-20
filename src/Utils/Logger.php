<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > Logger
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     15/03/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

use Breakerino\Core\Traits\Singleton;
use Breakerino\Core\Helpers;

defined('ABSPATH') || exit;

// TODO: Switch to JSON, use some standardized log format and open-source preview tool
// TODO: Auto-cleanup - keep logs only for X=7 days, clean previous logs on next log trigger or schedule action daily

class Logger {
	use Singleton;

	/**
	 * logsBaseDir
	 *
	 * @var string
	 */
	private $logsBaseDir;

	private const SINGLELINE_MESSAGE_FORMAT = '[%1$s] %2$s | %3$s: %4$s' . "\n";
	private const MULTILINE_MESSAGE_HEADER_FORMAT = '[%1$s] %2$s | %3$s' . "\n";

	/**
	 * __construct
	 *
	 * @return void
	 */
	final protected function init() {
		$this->set_log_file_path();
	}

	/**
	 * ------------------------------
	 * Logger
	 * ------------------------------
	 */
	public function log($type = null, string $message, ...$args) {
		if ( in_array($type, ['debug', 'json']) && ! Helpers::is_debug_mode() ) {
			return;
		}
		
		$message = $this->get_log_message($type, $message, ...$args);
		error_log($message, 3, $this->get_log_file_path());
	}

	/**
	 * ------------------------------
	 * Getters
	 * ------------------------------
	 */
	private function get_log_file_path(): string {
		$currentDate = \date('Y-m-d', \current_time('timestamp'));
		return sprintf('%1$s/%2$s.log', $this->logsBaseDir, $currentDate);
	}

	private function get_datetime(): string {
		return (string) date('Y-m-d H:i:s', \current_time('timestamp'));
	}

	private function get_divider(string $content = ''): string {
		return (string) str_repeat('-', strlen($content) + 15) . "\n";
	}

	protected function get_log_message($type = null, $message, ...$args): string {
		if (!is_array($message)) {
			return sprintf(self::SINGLELINE_MESSAGE_FORMAT, $this->get_datetime(), 'Breakerino', strtoupper($type), sprintf($message, ...$args));
		}

		$messageHeader  = sprintf(self::MULTILINE_MESSAGE_HEADER_FORMAT, $this->get_datetime(), 'Breakerino', strtoupper($type));
		$messageContent = '';

		foreach ($message as $key => $value) {
			$messageContent .= sprintf('%s: %s', $key, $value) . "\n";
		}

		return implode('', [
			$this->get_divider($messageHeader),
			$messageHeader,
			$this->get_divider($messageHeader),
			$messageContent,
			$this->get_divider($messageHeader)
		]);
	}

	/**
	 * ------------------------------
	 * Setters
	 * ------------------------------
	 */
	private function set_log_file_path() {
		$uploadsDir = \wp_upload_dir();
		
		$this->logsBaseDir = sprintf('%1$s/%2$s/logs', $uploadsDir['basedir'], 'breakerino');

		if (!is_dir($this->logsBaseDir)) {
			mkdir($this->logsBaseDir, 0775, true);
		}
	}

	/**
	 * ------------------------------
	 * Helpers
	 * ------------------------------
	 */
	public static function notice($message, ...$args) {
		self::instance()->log('notice', $message, ...$args);
	}

	public static function warning($message, ...$args) {
		self::instance()->log('warning', $message, ...$args);
	}

	public static function error($message, ...$args) {
		self::instance()->log('error', $message, ...$args);
	}

	public static function debug($message, ...$args) {
		self::instance()->log('debug', $message, ...$args);
	}

	public static function info($message, ...$args) {
		self::instance()->log('info', $message, ...$args);
	}

	public static function json($message, $flags = 0): void {
		self::instance()->log('json', json_encode($message, $flags), []);
		//self::instance()->log('json', json_encode($message, $flags)  . "\n", []);
	}
}
