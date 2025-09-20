<?php
/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Utils > Console
 * ------------------------------------------------------------------------------
 * @created     28/09/2021
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

class Config {

	/**
	 * plugin
	 *
	 * @var mixed
	 */
	private $plugin;

	/**
	 * __construct
	 *
	 * @param  mixed $plugin
	 * @return void
	 */
	public function __construct(\Breakerino\Core\Abstracts\Plugin &$plugin) {
		$this->set_plugin($plugin);
		$this->set_configs_base_dir();
	}

	/**
	 * set_plugin
	 *
	 * @param  mixed $plugin
	 * @return void
	 */
	public function set_plugin(\Breakerino\Core\Abstracts\Plugin &$plugin) {
		$this->plugin = &$plugin;
	}

	/**
	 * set_configs_base_dir
	 *
	 * @param  mixed $plugin
	 * @return void
	 */
	public function set_configs_base_dir() {
		$this->configsBaseDir = sprintf('%s/includes/config', $this->plugin->get_base_path());
	}

	/**
	 * get
	 *
	 * @param  mixed $type
	 * @param  mixed $name
	 * @return array
	 */
	public function get(string $type, string $name): array {

		$filePath = sprintf('%1$s/%2$s/%3$s.php', $this->configsBaseDir, $type, $name);

		if (!file_exists($filePath)) {
			throw new \Exception(sprintf('Config file %1s/%2$s not found.', $type, $name));
		}

		$config = require $filePath;

		if (empty($config) || !is_array($config)) {
			return [];
		}

		return $config;
	}
}