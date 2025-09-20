<?php
/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Interfaces > Plugin
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     05/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Interfaces;

defined('ABSPATH') || exit;

interface Plugin {
	public const PLUGIN_ID = null;
	public const PLUGIN_NAME = null;
	
	public const PLUGIN_HOOKS = [];
	public const PLUGIN_SHORTCODES = [];
	public const PLUGIN_ASSETS = [];
	
	public function get_id(): string;
	public function get_name(): string;
	public function get_prefix(): string;
	public function get_version(): string;
	
	public function get_base_path(): string;
	public function get_base_url(): string;
	public function get_relative_base_url(): string;
	
	public function activate(): void;
	public function deactivate(): void;
	public static function uninstall(): void;
}
