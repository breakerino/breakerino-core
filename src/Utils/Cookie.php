<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > Cookie
 * ------------------------------------------------------------------------------
 * @created     19/03/2024
 * @updated     19/03/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * 
 * @original 		https://github.com/josantonius/php-cookie
 * @requires 		PHP > 8.1
 * ------------------------------------------------------------------------------
 */


namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

use \Breakerino\Core\Exceptions\Generic as GenericException;
use \Breakerino\Core\Traits\Singleton;

/**
 * Cookie handler.
 */
class Cookie {
	use Singleton;

	private string $domain = '';
	private int|string|\DateTime $expires = 0;
	private bool $httpOnly = false;
	private string $path = '/';
	private bool $raw = false;
	private null|string $sameSite = null;
	private bool $secure = false;

	public function init() {
		$this->maybe_throw_same_site_wrong_value_exception();
	}

	/**
	 * Set class properties
	 * 
	 * @var     array      $props
	 * @access  protected
	 */
	public function _set_props($props): void {
		foreach ($props as $name => $value) {
			// Check if defined
			if (!property_exists($this, $name)) {
				continue;
			}

			// Set/merge value
			$this->{$name} = is_array($this->{$name}) ? array_merge($this->{$name}, $value) : $value;
		}
	}
	
	public function _reset_props(): void {
		$this->domain = '';
		$this->expires = 0;
		$this->httpOnly = false;
		$this->path = '/';
		$this->raw = false;
		$this->sameSite = null;
		$this->secure = false;	
	}

	/**
	 * Gets all cookies.
	 *
	 * @return array<string, mixed>
	 */
	public function _all(): array {
		return $_COOKIE ?? [];
	}

	/**
	 * Checks if a cookie exists.
	 */
	public function _has(string $name): bool {
		return isset($_COOKIE[$name]);
	}

	/**
	 * Gets a cookie by name.
	 *
	 * Optionally defines a default value when the cookie does not exist.
	 */
	public function _get(string $name, mixed $default = null): mixed {
		return $_COOKIE[$name] ?? $default;
	}

	/**
	 * Sets a cookie by name.
	 *
	 * @param null|int|string|\DateTime $expires The time the cookie will expire.
	 *                                          Integers will be handled as unix time except zero.
	 *                                          Strings will be handled as date/time formats.
	 *
	 * @see https://www.php.net/manual/en/datetime.formats.php
	 *
	 * @throws GenericException if headers already sent.
	 * @throws GenericException if failure in date/time string analysis.
	 */
	public function _set(string $name, mixed $value, null|int|string|\DateTime $expires = null): void {
		$this->maybe_throw_headers_sent_exception();

		$params = [$name, $value, $this->get_options($expires === null ? $this->expires : $expires)];

		$this->raw ? setrawcookie(...$params) : setcookie(...$params);
	}

	/**
	 * Sets several cookies at once.
	 *
	 * If cookies exist they are replaced, if they do not exist they are created.
	 *
	 * @param array<string, mixed>     $data    An array of cookies.
	 * @param null|int|string|\DateTime $expires The time the cookie will expire.
	 *                                          Integers will be handled as unix time except zero.
	 *                                          Strings will be handled as date/time formats.
	 *
	 * @see https://www.php.net/manual/en/datetime.formats.php
	 *
	 * @throws GenericException if headers already sent.
	 */
	public function _replace(array $data, null|int|string|\DateTime $expires = null): void {
		$this->maybe_throw_headers_sent_exception();

		foreach ($data as $name => $value) {
			$this->_set($name, $value, $expires);
		}
	}

	/**
	 * Deletes a cookie by name and returns its value.
	 *
	 * Optionally defines a default value when the cookie does not exist.
	 *
	 * @throws GenericException if headers already sent.
	 */
	public function _pull(string $name, mixed $default = null): mixed {
		$this->maybe_throw_headers_sent_exception();

		$value = $_COOKIE[$name] ?? $default;

		$this->_remove($name);

		return $value;
	}

	/**
	 * Deletes a cookie by name.
	 *
	 * @throws GenericException if headers already sent.
	 * @throws GenericException if failure in date/time string analysis.
	 */
	public function _remove(string $name): void {
		$this->maybe_throw_headers_sent_exception();

		$params = [$name, '', $this->get_options(1, false)];

		$this->raw ? setrawcookie(...$params) : setcookie(...$params);
	}

	/**
	 * Deletes all cookies.
	 *
	 * @throws GenericException if headers already sent.
	 */
	public function _clear(): void {
		$this->maybe_throw_headers_sent_exception();

		foreach ($_COOKIE ?? [] as $name) {
			$this->_remove($name);
		}
	}

	/**
	 * Gets all cookies.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return self::instance()->has(...func_get_args());
	}

	/**
	 * Checks if a cookie exists.
	 */
	public static function has(string $name): bool {
		return self::instance()->has(...func_get_args());
	}

	/**
	 * Gets a cookie by name.
	 *
	 * Optionally defines a default value when the cookie does not exist.
	 */
	public static function get(string $name, mixed $default = null): mixed {
		return self::instance()->_get(...func_get_args());
	}

	/**
	 * Sets a cookie by name.
	 *
	 * @param null|int|string|\DateTime $expires The time the cookie will expire.
	 *                                          Integers will be handled as unix time except zero.
	 *                                          Strings will be handled as date/time formats.
	 *
	 * @see https://www.php.net/manual/en/datetime.formats.php
	 *
	 * @throws GenericException if headers already sent.
	 * @throws GenericException if failure in date/time string analysis.
	 */
	public static function set(string $name, mixed $value, null|int|string|\DateTime $expires = null): void {
		self::instance()->_set(...func_get_args());
	}

	/**
	 * Sets several cookies at once.
	 *
	 * If cookies exist they are replaced, if they do not exist they are created.
	 *
	 * @param array<string, mixed>     $data    An array of cookies.
	 * @param null|int|string|\DateTime $expires The time the cookie will expire.
	 *                                          Integers will be handled as unix time except zero.
	 *                                          Strings will be handled as date/time formats.
	 *
	 * @see https://www.php.net/manual/en/datetime.formats.php
	 *
	 * @throws GenericException if headers already sent.
	 */
	public static function replace(array $data, null|int|string|\DateTime $expires = null): void {
		self::instance()->_replace(...func_get_args());
	}

	/**
	 * Deletes a cookie by name and returns its value.
	 *
	 * Optionally defines a default value when the cookie does not exist.
	 *
	 * @throws GenericException if headers already sent.
	 */
	public static function pull(string $name, mixed $default = null): mixed {
		return self::instance()->_pull(...func_get_args());
	}

	/**
	 * Deletes a cookie by name.
	 *
	 * @throws GenericException if headers already sent.
	 * @throws GenericException if failure in date/time string analysis.
	 */
	public static function remove(string $name): void {
		self::instance()->_remove(...func_get_args());
	}

	/**
	 * Deletes all cookies.
	 *
	 * @throws GenericException if headers already sent.
	 */
	public static function clear(): void {
		self::instance()->_clear(...func_get_args());
	}
	
	/**
	 * Set class properties
	 * 
	 * @var     array      $props
	 * @access  protected
	 */
	public static function set_props($props): void {
		self::instance()->_set_props(...func_get_args());
	}
	
		/**
	 * Set class properties
	 * 
	 * @var     array      $props
	 * @access  protected
	 */
	public static function reset_props(): void {
		self::instance()->_reset_props(...func_get_args());
	}

	/**
	 * Gets cookie options.
	 *
	 * @throws GenericException if failure in date/time string analysis.
	 */
	private function get_options(null|int|string|\DateTime $expires, bool $formatTime = true): array {
		if ($formatTime) {
			$expires = $this->format_expiration_time($expires);
		}

		$options = [
			'domain' => $this->domain,
			'expires' => $expires,
			'httponly' => $this->httpOnly,
			'path' => $this->path,
			'secure' => $this->secure,
		];

		if ($this->sameSite !== null) {
			$options['samesite'] = $this->sameSite;
		}

		return $options;
	}

	/**
	 * Format the expiration time.
	 *
	 * @throws GenericException if failure in date/time string analysis.
	 */
	private function format_expiration_time(int|string|\DateTime $expires): int {
		if ($expires instanceof \DateTime) {
			return (int) $expires->format('U');
		} elseif (is_int($expires)) {
			return $expires;
		} elseif (is_string($expires)) {
			try {
				return (int) (new \DateTime($expires))->format('U');
			} catch (\Throwable $exception) {
				throw new GenericException($exception->getMessage(), $exception);
			}
		}
	}

	/**
	 * Throw exception if headers have already been sent.
	 *
	 * @throws GenericException if headers already sent.
	 */
	private function maybe_throw_headers_sent_exception(): void {
		headers_sent($file, $line) && throw new GenericException(sprintf(
			'The headers have already been sent in "%s" at line %d.',
			$file,
			$line
		));
	}

	/**
	 * Throw exception if $sameSite value is wrong.
	 *
	 * @throws GenericException if $sameSite value is wrong.
	 */
	private function maybe_throw_same_site_wrong_value_exception(): void {
		$values = ['none', 'lax', 'strict'];

		if ($this->sameSite && !in_array(strtolower($this->sameSite), $values)) {
			throw new GenericException(
				'Invalid value for the sameSite param. Available values: ' . implode('|', $values)
			);
		}
	}
}
