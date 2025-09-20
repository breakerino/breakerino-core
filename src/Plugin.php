<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Core > Plugin
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     22/01/2025
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core;

defined('ABSPATH') || exit;

use Breakerino\Core\Abstracts\Plugin as PluginBase;
use Breakerino\Core\Utils\ReactAppLoader;

class Plugin extends PluginBase implements Constants {
	public const PLUGIN_ID         	= 'breakerino-core';
	public const PLUGIN_NAME		 		= 'Breakerino Core';

	public const PLUGIN_HOOKS = [
		'global'     => [
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_footer'],
				'callback' 	=> ['$this', 'handle_render_breakerino_root'],
				'priority' 	=> 100,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_head'],
				'callback' 	=> ['$this', 'handle_inject_frontend_assets_preload'],
				'priority' 	=> 0,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_loaded'],
				'callback' 	=> ['$this', 'handle_enqueue_scripts'],
				'priority' 	=> 10,
				'args'		=> 0
			],
			[
				'type'		=> 'filter',
				'hooks'		=> ['rocket_defer_inline_exclusions', 'rocket_delay_js_exclusions', 'rocket_exclude_defer_js'],
				'callback' 	=> ['$this', 'handle_adjust_rocket_js_exclusions_list'],
				'priority' 	=> 10,
				'args'		=> 1
			],
		]
	];

	/**
	 * Undocumented function
	 *
	 * @return mixed
	 */
	public function get_build_version() {
		return apply_filters('breakerino/core/build_version', sprintf('%s/%s', $this->get_version('view'), $this->get_version_date()), $this);
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $list
	 * @return void
	 */
	public function handle_adjust_rocket_js_exclusions_list($list) {
		if ( ! is_array($list) )  {
			$list = [];
		}

		$list[] = 'data-breakerino';
		$list[] = '/wp-content/plugins/breakerino-(.*)/(.*).js';

		return $list;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_enqueue_scripts() {
		if ( ! Helpers::is_frontend_mode_enabled() ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		// TODO: Throw warning if not exists (in dev mode only)
		wp_enqueue_script(
			'breakerino-core',
			sprintf('%s/%s/%s/js/index.js', $this->get_base_url(), self::BUILD_BASE_DIR, $this->get_build_version()),
			[],
			null,
			$this->get_build_version()
		);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_inject_frontend_assets_preload() {
		if ( ! Helpers::is_frontend_mode_enabled() ) {
			return;
		}

		if ( ReactAppLoader::is_frontend_dev_mode() ) {
			return;
		}

		$preloadedAppAssets = apply_filters('breakerino/core/preloaded_app_assets', self::PRELOADED_APP_ASSETS, $this);

		// TODO: Throw warning if not exists (in dev mode only)
		// TODO: Allow to preload various file types (js/css/svg/...)
		foreach ($preloadedAppAssets as $assetFile) {
			echo sprintf(
				'<link rel="modulepreload" crossorigin fetchpriority="high" href="%s" />' . PHP_EOL,
				sprintf('%s/%s/%s/%s', $this->get_base_url(), self::BUILD_BASE_DIR, $this->get_build_version(), $assetFile),
			);
		}

		echo sprintf('<link rel="preload" as="image" href="%s" />', sprintf('%s/%s/%s/%s', $this->get_base_url(), self::BUILD_BASE_DIR, $this->get_build_version(), 'icons/icons.svg'));
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function add_data_layer_script() {
		$data = apply_filters('breakerino/frontend/data', []);

		$script = '<script type="text/javascript" data-breakerino>' . "\n";
		$script .= sprintf('window.%1$s = {', 'breakerino') . "\n";

		foreach ( $data as $id => $json ) {
			$script .= sprintf('"%s": %s,',
				$id,
				json_encode($json, empty($json) ? JSON_FORCE_OBJECT : 0)
			) . "\n";
		}

		$script .= '};' . "\n";
		$script .= 'document.dispatchEvent(new CustomEvent("breakerino:data.init"));' . "\n";
		$script .= '</script>' . "\n";

		echo $script;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_render_breakerino_root() {
		if ( ! Helpers::is_frontend_mode_enabled() ) {
			return;
		}

		ob_start();

		$this->add_data_layer_script();

		// TODO: Reusable core helper for rendering app root (except breakerino-root ofc)
		echo sprintf(
			'<div id="%1$s" style="display: none;"></div>',
			'breakerino-root',
		);

		echo ob_get_clean();
	}
}