<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > AppData
 * ------------------------------------------------------------------------------
 * @created     27/03/2024
 * @updated     27/03/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

defined('ABSPATH') || exit;

trait AppData {
	/**
	 * Retrieve app config
	 *
	 * @return void
	 */
	public function get_config() {
		// TODO: Error handling
		$config = require $this->get_file_path('includes/app/config.php');
		return \apply_filters(sprintf('breakerino/%s/config', $this->get_id(true)), $config);
	}

	/**
	 * Retrieve app theme
	 *
	 * @return void
	 */
	public function get_theme() {
		// TODO: Error handling
		$theme = require $this->get_file_path('includes/app/theme.php');
		return \apply_filters(sprintf('breakerino/%s/theme', $this->get_id(true)), $theme);
	}

	/**
	 * Retrieve app translations
	 *
	 * @return void
	 */
	public function get_translations() {
		// TODO: Error handling
		$translations = require $this->get_file_path('includes/app/translations.php');
		return \apply_filters(sprintf('breakerino/%s/translations', $this->get_id(true)), $translations);
	}

	/**
	 * Retrieve app data
	 *
	 * @return void
	 */
	public function get_app_data() {
		return [
			'theme' => $this->get_theme(),
			'config' => $this->get_config(),
			'translations' => $this->get_translations()
		];
	}
}