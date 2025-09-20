<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > ReactAppLegacy
 * ------------------------------------------------------------------------------
 * @created     06/10/2022
 * @updated     06/10/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * @deprecated
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class ReactAppLegacy {
	public const DEFAULT_APP_NAMESPACE = 'breakerino';

	public const DEFAULT_APP_SHORTCODE_ATTS = [
		'nonce' => null
	];

	public const DEFAULT_APP_DATA = [
		'config' => [],
		'theme' => [],
		'translations' => []
	];

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	private $appName = '';

	/**
	 * Undocumented variable
	 *
	 * @var array
	 */
	private $appData = self::DEFAULT_APP_DATA;

	/**
	 * Undocumented variable
	 *
	 * @var array
	 */
	private $appShortcodeAtts = self::DEFAULT_APP_SHORTCODE_ATTS;

	public function __construct(array $props) {
		$this->set_props($props);
	}

	/**
	 * Load config and set properties
	 *
	 * @var     array      $props
	 * @access  protected
	 */
	protected function set_props($props): void {
		foreach ($props as $name => $value) {
			if (!property_exists($this, $name)) {
				continue;
			}
			if (is_array($this->{$name})) {
				$this->{$name} = array_merge($this->{$name}, $value);
			} else {
				$this->{$name} = $value;
			}
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function get_app_data_script() {
		return sprintf(
			'
				if ( ! (\'%1$s\' in window) ) window.%1$s = {};
				if ( ! ( \'%2$s\' in window.%1$s ) ) window.%1$s.%2$s = %3$s;
			',
			self::DEFAULT_APP_NAMESPACE,
			$this->appName,
			//lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $this->appName)))),
			json_encode($this->appData)
		);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function get_app_script_name() {
		return sprintf('%1$s-%2$s-app', self::DEFAULT_APP_NAMESPACE, $this->appName);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function inject_app_scripts() {
		\wp_enqueue_script($this->get_app_script_name());
		\wp_add_inline_script($this->get_app_script_name(), $this->get_app_data_script(), 'before');
	}

	/**
	 * Undocumented function
	 *
	 * @param array $atts
	 * @return void
	 */
	public function render($atts = []) {
		$atts = \shortcode_atts($this->appShortcodeAtts, $atts);

		$htmlAttributes = '';

		foreach ($atts as $key => $value) {
			if (is_null($value)) {
				continue;
			}

			if ($key === 'nonce') {
				$value = \wp_create_nonce($value);
			}

			$htmlAttributes .= sprintf(' data-%1$s="%2$s"', $key, $value);
		}

		$this->inject_app_scripts();

		return sprintf('<div style="width: 100%%;" data-root-id="%1$s"%2$s></div>', self::DEFAULT_APP_NAMESPACE . '-' . $this->appName, $htmlAttributes);
	}
}
