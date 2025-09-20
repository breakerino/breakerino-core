<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Api > Response
 * ------------------------------------------------------------------------------
 * @created     22/01/2025
 * @updated     22/01/2025
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Api;

use Breakerino\Core\Traits\Props;

defined('ABSPATH') || exit;

class Response {
	use Props;
	
	/**
	 * Response data
	 *
	 * @var array
	 */
	protected $data = [];
	
	/**
	 * Response errors
	 *
	 * @var array
	 */
	protected $errors = [];
	
	/**
	 * Response status
	 *
	 * @var int
	 */
	protected $status = \WP_Http::OK;
	
	/**
	 * Response headers
	 *
	 * @var array
	 */
	protected $headers = [];
	
	/**
	 * Constructor
	 *
	 * @param array $props
	 */
	public function __construct(array $props = []) {
		$this->set_props($props);
	}
	
	/**
	 * Set response data
	 *
	 * @param array $data
	 * @return void
	 */
	public function set_data(array $data) {
		$this->data = $data;
	}
	
	/**
	 * Set response error
	 *
	 * @param array $error
	 * @return void
	 */
	public function set_error(array $error) {
		$this->errors[] = $error;
	}
	
	/**
	 * Set response status
	 *
	 * @param integer $status
	 * @return void
	 */
	public function set_status(int $status) {
		$this->status = $status;
	}
	
	/**
	 * Set response header
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function set_header(string $key, string $value) {
		$this->headers[$key] = $value;
	}
	
	/**
	 * Send response
	 *
	 * @return \WP_REST_Response
	 */
	public function send() {
		return new \WP_REST_Response(['data' => $this->data, 'errors' => $this->errors], $this->status, $this->headers);
	}
}
