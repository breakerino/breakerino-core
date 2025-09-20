<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > ReactAppLoader
 * ------------------------------------------------------------------------------
 * @created     03/01/2023
 * @updated     24/03/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

use Breakerino\Core\Traits\Singleton;
use Breakerino\Core\Helpers;

class ReactAppLoader {
	use Singleton;

	public const ROOT_APP_ID = 'breakerino-root';  // TODO: Passable
	public const APP_DEFAULT_PROPS = [
		'appID' => '',
		'appDataID' => '',
		'appRootID' => '',
		'appData' => [],
		'appRootAttributes' => [],
		'appRenderArgs' => [],
		'appDependencies' => [],
	];

	//
	public const APP_DEV_SERVER_PROTOCOL = 'http';  // TODO: Filterable
	public const APP_DEV_SERVER_PORT = 5000; // TODO: Filterable
	public const APP_DEV_SERVER_BASE_PATH = '/wa-dev';  // TODO: Filterable

	public static function get_server_ip() {
		return $_SERVER['HTTP_X_SERVER_ADDR'] ?? $_SERVER['SERVER_ADDR'];
	}

	private $apps = [];

	public function init() {
		\add_filter('script_loader_tag', [$this, 'extend_script_tag'], 10, 3);
	}

	public static function register_app($app) {
		$instance = self::instance();
		return $instance->add_app($app);
	}

	public static function get_app_hash($app) {
		return $app['appRootID']; // TODO: Support multiple instance of the same app
	}

	public static function is_app_dev_server_running() {
		$connection = @fsockopen(self::get_server_ip(), self::APP_DEV_SERVER_PORT);
		return is_resource($connection);
	}

	public static function is_frontend_dev_mode_allowed() {
		return \apply_filters('breakerino/core/is_frontend_dev_mode_allowed', false);
	}

	public static function is_frontend_dev_mode() {
		return self::is_frontend_dev_mode_allowed() && Helpers::is_dev_mode() && self::is_app_dev_server_running();
	}

	private function get_dev_server_scripts() {
		// Vite React refresh script
		$tag = sprintf('
			<script type="module" defer>
				import RefreshRuntime from "%1$s://%2$s:%3$s/@react-refresh"
				RefreshRuntime.injectIntoGlobalHook(window)
				window.$RefreshReg$ = () => {}
				window.$RefreshSig$ = () => (type) => type
				window.__vite_plugin_react_preamble_installed__ = true
			</script>
	', self::APP_DEV_SERVER_PROTOCOL, self::get_server_ip(), self::APP_DEV_SERVER_PORT . self::APP_DEV_SERVER_BASE_PATH);

		// Vite client script
		$tag .= sprintf('<script type="module" defer src="%1$s://%2$s:%3$s/@vite/client"></script>', self::APP_DEV_SERVER_PROTOCOL, self::get_server_ip(), self::APP_DEV_SERVER_PORT . self::APP_DEV_SERVER_BASE_PATH);

		// Bootstrap script
		$tag .= sprintf('<script type="module" defer src="%1$s://%2$s:%3$s/src/main.tsx"></script>', self::APP_DEV_SERVER_PROTOCOL, self::get_server_ip(), self::APP_DEV_SERVER_PORT . self::APP_DEV_SERVER_BASE_PATH);

		// Vite SVG spritemap
		$tag .= sprintf('<script type="module" src="%1$s://%2$s:%3$s/@vite-plugin-svg-spritemap/client"></script>', self::APP_DEV_SERVER_PROTOCOL, self::get_server_ip(), self::APP_DEV_SERVER_PORT . self::APP_DEV_SERVER_BASE_PATH);

		// TODO: Filterable/extendable

		return $tag;
	}

	private function get_app_script($src) {
		return sprintf('<script defer type="module" crossorigin src="%s"></script>', \esc_url($src));
	}

	public function extend_script_tag($tag, $handle, $src) {
		if ('breakerino-core' !== $handle) {
			return $tag;
		}


		return self::is_frontend_dev_mode() ? $this->get_dev_server_scripts() : $this->get_app_script($src);
	}

	public function add_app($app) {
		$app = \wp_parse_args($app, self::APP_DEFAULT_PROPS);

		$appHash = $this->get_app_hash($app);

		if (array_key_exists($appHash, $this->apps)) {
			return;
		}

		$this->apps[$appHash] = new ReactApp($app);
		return $this->apps[$appHash];
	}

	public function render_root() {
		return sprintf(
			'<div id="%1$s" style="display: none;"></div>',
			self::ROOT_APP_ID,
		);
	}
}
