<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > ReactApp
 * ------------------------------------------------------------------------------
 * @created     03/01/2023
 * @updated     21/04/2023
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

use Breakerino\Core\Traits\Props;

defined('ABSPATH') || exit;

class ReactApp {
	use Props;

	// <noscript>You need to enable Javascript to run this app.</noscript>
	public const APP_ROOT_RENDER_FORMAT = '
		<div data-root-id="%1$s"%2$s>%3$s</div>
	'; // %1$s = rootID; %2$s = rootAttributes; %3$s = rootContent (pre-rendered)

	protected $appID = '';
	protected $appDataID = '';
	protected $appRootID = '';
	protected $appData = [];
	protected $appRootAttributes = [
		'style' => 'width:100%;font-size:16px',
	];
	protected $appRenderArgs = [];
	protected $appDependencies = [];

	public function __construct($props) {
		$this->set_props($props);
		// TODO: Allow to register shortcode only if enabled in props
		\add_shortcode($this->get_app_root_id(), [$this, 'render_app']);
	}

	/**
	 * ------------------------------------------
	 * Getters
	 * ------------------------------------------
	 */

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_render_args() {
		return \apply_filters(sprintf('breakerino/app/%s/render_args', $this->get_app_id()), $this->appRenderArgs, $this);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_root_attributes($renderArgs = []) {
		return \apply_filters(sprintf('breakerino/app/%s/root_attributes', $this->get_app_id()), $this->appRootAttributes, $renderArgs, $this);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_namespace() {
		return 'breakerino';
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_data($renderArgs) {
		return \apply_filters(sprintf('breakerino/app/%s/app_data', $this->get_app_id()), $this->appData, $renderArgs, $this);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_id() {
		return $this->appID;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_data_id() {
		return \apply_filters(sprintf('breakerino/app/%s/data_id', $this->get_app_id()), $this->appDataID, $this);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_root_id() {
		return \apply_filters(sprintf('breakerino/app/%s/root_id', $this->get_app_id()), $this->appRootID, $this);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_app_default_content() {
		return \apply_filters(sprintf('breakerino/app/%s/initial_html', $this->get_app_id()), '', $this);
	}

	private function generate_html_attributes($attributes) {
		$output = '';

		foreach ($attributes as $key => $value) {
			if (is_null($value)) {
				continue;
			}

			$output .= sprintf(' %1$s="%2$s"', $key, $value);
		}

		return $output;
	}

	private function get_app_root_html($renderArgs, $rootAttributes) {
		return sprintf(
			self::APP_ROOT_RENDER_FORMAT,
			$this->get_app_root_id(),
			$this->generate_html_attributes($rootAttributes),
			$this->get_app_default_content()
		);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function inject_app_script() {
		// optional, somehow chain it from core
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $atts
	 * @return void
	 */
	public function render_app($args = []) {
		$renderArgs = \wp_parse_args($args, $this->get_app_render_args());

		if (!\apply_filters(sprintf('breakerino/app/%s/render', $this->get_app_id()), true, $renderArgs, $this)) {
			return null;
		}

		$rootAttributes = $this->get_app_root_attributes($renderArgs);

		//$this->inject_app_script($renderArgs);
		
		add_filter('breakerino/frontend/data', function($data) use ($renderArgs) {
			$data[$this->get_app_data_id()] = $this->get_app_data($renderArgs);
			return $data;
		}, 10, 1);
		
		//$this->inject_app_config($renderArgs);

		return $this->get_app_root_html($renderArgs, $rootAttributes);
	}
}
