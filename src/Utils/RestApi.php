<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > RestApi
 * ------------------------------------------------------------------------------
 * An universal base class to set up a connector with the internal API 
 * for two-way communication. All you need is to pass the config.
 * 
 * Use is by extending your API class with this class.
 * It is recommended to implement interface class with defined endpoint
 * handlers, which you can customize and extend it to your needs. 
 * ------------------------------------------------------------------------------
 * @updated     28/09/2021
 * @updated     19/03/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */
namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

use Breakerino\Core\Exceptions\Generic as GenericException;

class RestApi {

	/**
	 * API Namespace
	 * 
	 * @var     string      $namespace
	 * @access  protected
	 */
	protected $namespace;

	/**
	 * API Version
	 * 
	 * @var     int      $version
	 * @access  protected
	 */
	protected $version;

	/**
	 * API Endpoints
	 * 
	 * @var     array      $endpoints
	 * @access  protected
	 */
	protected $endpoints = [];

	/**
	 * API Credentials
	 * 
	 * @var     array      $credentials
	 * @access  protected
	 */
	protected $credentials = [];

	/**
	 * List of error messages
	 * 
	 * @var     array      ERROR_MESSAGES
	 * @access  private
	 */

	private const ERROR_MESSAGES = [
		'UNKNOWN_ENDPOINT'              => "API endpoint %s is not defined",
		'URL_PARAM_MISSING'             => "Required URL paramter %s is not defined.",
		'UNSUPPORTED_PROPERTY_GIVEN'    => "Propety %s is not supported.",
		'API_RESPONSE_ERROR'            => "An error occured while making API call.\nRequest URL: %s\nRequest args: %s"
	];

	private const REGEX_PATTERNS = [
		'BEARER_TOKEN' => "/Basic\s(\S+)/"
	];

	/**
	 * Initialize and load API config
	 * 
	 * @var     array      $props
	 * @access  public
	 */
	public function __construct(array $props) {
		$this->set_props($props);
		$this->register_routes();
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
				throw new GenericException(self::ERROR_MESSAGES['UNSUPPORTED_PROPERTY_GIVEN'], $name);
			}

			$this->{$name} = $value;
		}
	}

	/**
	 * Get permission callback for given enpoint
	 * 
	 * @var     \WP_REST_Request		$request
	 * @var     array      					$endpoint
	 * @access  protected
	 * 
	 * @return  callable
	 */
	protected function get_permission_callback(array $endpoint): ?callable {
		return function (\WP_REST_Request $request) use ($endpoint) {
			if (!isset($endpoint['auth'])) {
				return true;
			}

			switch ($endpoint['auth']) {
				case 'bearer':
					return $this->validate_request_by_bearer_token($request);
				case 'nonce':
					return $this->validate_request_by_nonce_token($request);
				default:
					return true;
			}
		};
	}

	public function register_routes() {
		foreach ($this->endpoints as $route => $endpoint) {
			\register_rest_route(
				$this->namespace . '/' . $this->version,
				$route,
				[
					'methods' => $endpoint['args']['method'],
					'args' => array_key_exists('args', $endpoint['args']) ? $endpoint['args']['args'] : [],
					'callback' => $endpoint['callback'],
					'permission_callback' => isset($endpoint['permission_callback']) && ! empty($endpoint['permission_callback']) ? $endpoint['permission_callback'] : $this->get_permission_callback($endpoint)
				]
			);
		}
	}

	public function get_request_bearer_token(string $authHeader): string {

		if (empty($authHeader)) {
			throw new GenericException('Authorization header not provided.');
		}

		preg_match(self::REGEX_PATTERNS['BEARER_TOKEN'], $authHeader, $matches);

		if (!isset($matches[1]) || !base64_decode($matches[1], true)) {
			return '';
		}

		return $matches[1];
	}

	protected function get_valid_bearer_token(): string {
		return base64_encode(sprintf('%1$s:%2$s', $this->credentials['username'], $this->credentials['password']));
	}

	public function validate_request_by_nonce_token(\WP_REST_Request $request) {
		return \wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
	}

	public function validate_request_by_bearer_token(\WP_REST_Request $request) {
		if (empty($this->credentials)) {
			return false;
		}

		return $this->get_request_bearer_token($request->get_header('Authorization')) === $this->get_valid_bearer_token();
	}
}
