<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Core > Abstracts > Plugin
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     14/12/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Abstracts;

use Breakerino\Core\Utils\Logger as Log;
use Breakerino\Core\Utils\Console;
use Breakerino\Core\Helpers;

use Breakerino\Core\Traits\Singleton;
use Breakerino\Core\Traits\Constants;
use Breakerino\Core\Traits\Shortcodes;
use Breakerino\Core\Traits\Hooks;
use Breakerino\Core\Traits\Assets;

use Breakerino\Core\Exceptions\Generic as GenericException;

use Breakerino\Core\Interfaces\Plugin as PluginInterface;

defined('ABSPATH') || exit;

abstract class Plugin implements PluginInterface {
	use Singleton;
	use Constants;
	use Hooks;
	use Shortcodes;
	use Assets;

	private const PLUGIN_SCOPES_LIST 	= ['admin', 'public', 'global'];
	private const PLUGIN_PARTS_LIST 	= ['hooks', 'shortcodes', 'assets'];

	protected const PLUGIN_DEFAULT_HOOKS = [
		'global'     => [
			[
				'type'		=> 'action',
				'hooks'		=> ['plugins_loaded'],
				'callback' 	=> ['$this', 'handle_load_plugin_textdomain'],
				'priority' 	=> 20,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['admin_enqueue_scripts', 'wp_enqueue_scripts'],
				'callback' 	=> ['$this', 'handle_enqueue_global_plugin_assets'],
				'priority' 	=> 10,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_loaded'],
				'callback' 	=> ['$this', 'handle_register_plugin_assets'],
				'priority' 	=> 10,
				'args'		=> 0
			],
		]
	];

	/**
	 * Plugin ID
	 *
	 * @var		string		$pluginID;
	 * @access	private
	 */
	private $pluginID;

	/**
	 * Plugin name
	 *
	 * @var		string		$pluginName;
	 * @access	private
	 */
	private $pluginName;

	/**
	 * Plugin file
	 *
	 * @var		string		$pluginFile;
	 * @access	private
	 */
	private $pluginFile;

	/**
	 * Plugin version
	 *
	 * @var		string		$pluginVersion;
	 * @access	private
	 */
	private $pluginVersion;

	/**
	 * Plugin version date
	 *
	 * @var		string		$pluginVersionDate;
	 * @access	private
	 */
	private $pluginVersionDate;

	/**
	 * Plugin prefix
	 *
	 * @var		string		$pluginPrefix;
	 * @access	private
	 */
	private $pluginPrefix;

	/**
	 * Plugin base path
	 *
	 * @var		string		$pluginBasePath;
	 * @access	private
	 */
	private $pluginBasePath;

	/**
	 * Plugin base URL
	 *
	 * @var		string		$pluginBaseUrl;
	 * @access	private
	 */
	private $pluginBaseUrl;

	/**
	 * Plugin assets
	 *
	 * @var		array		$pluginAssets;
	 * @access	private
	 */
	private $pluginAssets;

	/**
	 * Plugin hooks
	 *
	 * @var		string		$pluginHooks;
	 * @access	private
	 */
	private $pluginHooks;

	/**
	 * Plugin shortcodes
	 *
	 * @var			array		$pluginShortcodes;
	 * @access	private
	 */
	private $pluginShortcodes;

	/**
	 * Plugin init
	 *
	 * @access	private
	 */
	private function init() {
		try {
			// Set base meta properties
			$this->set_id();
			$this->set_prefix();
			$this->set_name();
			$this->set_version();
			$this->set_version_date();

			// Set file-related properties
			$this->set_plugin_file();
			$this->set_plugin_base_path();
			$this->set_plugin_base_url();

			// Set plugin parts (hooks, shortcodes, assets)
			$this->set_plugin_parts();

			// Register plugin parts
			$this->register_plugin_activation_hooks();
			$this->register_plugin_part('shortcodes');
			$this->register_plugin_part('hooks');

			// Run custom initialize plugin callback
			$this->initialize_plugin();

			Console::log($this->get_name() . ' - Initialized', [
				'id' => $this->get_id(),
				'name' => $this->get_name(),
				'prefix' => $this->get_prefix(),
				'version' => $this->get_version(),
				'base_path' => $this->get_base_path(),
				'base_url' => $this->get_base_url(false),
				'hooks' => $this->get_hooks(),
				'shortcodes' => $this->get_shortcodes(),
				'assets' => $this->get_assets()
			]);
		} catch (\Breakerino\Core\Exceptions\Generic $e) {
			Console::log($e->getMessage());
			Log::error($e->getMessage());
		} catch (\Exception $e) {
			Console::log($e->getMessage(), ['trace' => $e->getTrace()]);
			Log::debug($e->getMessage());
		}
	}

	/**
	 * Plugin activation handler
	 *
	 * @return	void
	 * @access	public
	 */
	public function activate(): void {
	}

	/**
	 * Plugin deactivation handler
	 *
	 * @return	void
	 * @access	public
	 */
	public function deactivate(): void {
	}

	/**
	 * Check dependencies
	 *
	 * @return void
	 */
	final public function check_dependencies() {
		$errors = [];

		foreach ($this->get_global_constant('PLUGIN_DEPENDENCIES', true, []) as $pluginFile) {
			if (\is_plugin_active($pluginFile)) {
				continue;
			}

			// TODO: Use try-catch block with GenericException instead, also prepare some system for throwing admin notices
			$errors[] = sprintf('[%s] Required plugin %s is not installed or activated.', $this->get_name(), $pluginFile);
		}

		if (!empty($errors)) {
			if ( Helpers::is_cli() ) {
				foreach ($errors as $error) {
					\WP_CLI::error($error);
				}

				die();
			}

			die(implode('<br/>', $errors));
		}
	}

	/**
	 * Plugin initialization handler
	 *
	 * @return	void
	 * @access	public
	 */
	public function initialize_plugin(): void {
	}

	/**
	 * Plugin uninstall handler
	 *
	 * @return	void
	 * @access	public
	 */
	public static function uninstall(): void {
	}

	/**
	 * Get plugin ID
	 *
	 * @return	string		$pluginID
	 * @access	public
	 */
	final public function get_id(bool $omitPrefix = false): string {
		return ! $omitPrefix ? $this->pluginID : str_replace('breakerino-', '', $this->pluginID);
	}

	/**
	 * Get plugin prefix
	 *
	 * @return	string		$pluginPrefix
	 * @access	public
	 */
	final public function get_prefix(): string {
		return $this->pluginPrefix;
	}

	/**
	 * Get plugin file
	 *
	 * @return	string		$pluginFile
	 * @access	public
	 */
	final public function get_plugin_file(): string {
		return $this->pluginFile;
	}

	/**
	 * Get plugin name
	 *
	 * @return	string		$pluginName
	 * @access	public
	 */
	public function get_name($context = null): string {
		return $context === 'view' ? apply_filters($this->get_prefix() . 'get_plugin_name', $this->pluginName, $this) : $this->pluginName;
	}

	/**
	 * Get plugin version
	 *
	 * @return	string		$pluginVersion
	 * @access	public
	 */
	public function get_version($context = null): string {
		return $context === 'view' ? apply_filters($this->get_prefix() . 'get_plugin_version', $this->pluginVersion, $this) : $this->pluginVersion;
	}

	/**
	 * Get plugin version
	 *
	 * @return	string		$pluginVersion
	 * @access	public
	 */
	public function get_version_date($context = null): string {
		return $context === 'view' ? apply_filters($this->get_prefix() . 'get_plugin_version_date', $this->pluginVersionDate, $this) : $this->pluginVersionDate;
	}

	/**
	 * Get plugin base path
	 *
	 * @return	string		$pluginBasePath
	 * @access	public
	 */
	final public function get_base_path(): string {
		return $this->pluginBasePath;
	}

	/**
	 * Get plugin file path
	 *
	 * @return	string		$pluginBasePath
	 * @access	public
	 */
	final public function get_file_path($path): string {
		return \trailingslashit($this->get_base_path()) . $path;
	}

	/**
	 * Get plugin base URL
	 *
	 * @return	string		$pluginBaseUrl
	 * @access	public
	 */
	final public function get_base_url(): string {
		return apply_filters($this->get_prefix() . 'get_plugin_base_url', $this->pluginBaseUrl, $this);
	}

	/**
	 * Get plugin base URL
	 *
	 * @return	string
	 * @access	public
	 */
	final public function get_relative_base_url(): string {
		return wp_make_link_relative($this->get_base_url());
	}

	/**
	 * Get plugin assets
	 *
	 * @return	array		$pluginAssets
	 * @access	public
	 */
	final public function get_assets(): array {
		return $this->pluginAssets;
	}

	/**
	 * Get plugin hooks
	 *
	 * @return	array		$pluginHooks
	 * @access	public
	 */
	final public function get_hooks(): array {
		return $this->pluginHooks;
	}

	/**
	 * Get plugin shortdodes
	 *
	 * @return	array		$pluginShortcodes
	 * @access	public
	 */
	final public function get_shortcodes(): array {
		return $this->pluginShortcodes;
	}

	/**
	 * Set plugin name
	 *
	 * @access	private
	 */
	private function set_id() {
		$this->pluginID = $this->get_class_constant('PLUGIN_ID', false);
	}

	/**
	 * Set plugin display name
	 *
	 * @access	private
	 */
	private function set_name() {
		$this->pluginName = $this->get_class_constant('PLUGIN_NAME', false);
	}

	/**
	 * Set plugin version
	 *
	 * @access	private
	 */
	private function set_version() {
		$this->pluginVersion = $this->get_global_constant('PLUGIN_VERSION', true, '1.0.0');
	}

	/**
	 * Set plugin version
	 *
	 * @access	private
	 */
	private function set_version_date() {
		$this->pluginVersionDate = $this->get_global_constant('PLUGIN_VERSION_DATE', true, date('Y-m-d'));
	}

	/**
	 * Set plugin prefix
	 *
	 * @access	private
	 */
	private function set_prefix() {
		$this->pluginPrefix = strtolower(str_replace('-', '_', $this->get_id())) . '_';
	}

	/**
	 * Set plugin file
	 *
	 * @access	private
	 */
	private function set_plugin_file() {
		$this->pluginFile = $this->get_global_constant('PLUGIN_FILE', false);
	}

	/**
	 * Set plugin base path
	 *
	 * @access	private
	 */
	private function set_plugin_base_path() {
		$this->pluginBasePath = untrailingslashit(plugin_dir_path($this->get_plugin_file()));
	}

	/**
	 * Set plugin base URL
	 *
	 * @access	private
	 */
	private function set_plugin_base_url() {
		$this->pluginBaseUrl = untrailingslashit(plugin_dir_url($this->get_plugin_file()));
	}

	/**
	 * Set plugin parts
	 *
	 * @return void
	 */
	private function set_plugin_parts() {
		foreach (self::PLUGIN_PARTS_LIST as $pluginPart) {
			$defaultPartList = $this->get_class_constant('PLUGIN_DEFAULT_' . strtoupper($pluginPart), false, []);
			$pluginPartList = $this->get_class_constant('PLUGIN_' . strtoupper($pluginPart), true, []);

			foreach (self::PLUGIN_SCOPES_LIST as $scope) {
				$propertyName = 'plugin' . ucfirst($pluginPart);

				if (!property_exists($this, $propertyName)) {
					throw new GenericException("Property %s does not exist in class %s", [$propertyName, get_class($this)]);
				}

				$this->{$propertyName}[$scope] = array_merge_recursive(
					$defaultPartList[$scope] ?? [],
					\apply_filters($this->get_prefix() . $pluginPart . '_' . $scope, $pluginPartList[$scope] ?? [], $this)
				);
			}
		}
	}

	/**
	 * Register plugin activation/deactivation hooks
	 *
	 * @return void
	 */
	private function register_plugin_activation_hooks() {
		$pluginFile = $this->get_global_constant('PLUGIN_FILE', false);

		\register_activation_hook($pluginFile, [$this, 'check_dependencies']);
		\register_activation_hook($pluginFile, [$this, 'activate']);
		\register_deactivation_hook($pluginFile, [$this, 'deactivate']);
		\register_uninstall_hook($pluginFile, [static::class, 'uninstall']);
	}

	/**
	 * Register plugin parts
	 *
	 * @return void
	 */
	private function register_plugin_part($pluginPart) {
		if (!in_array($pluginPart, self::PLUGIN_PARTS_LIST)) {
			throw new GenericException('Plugin part %s is not supported', [$pluginPart]);
		}

		$currentScope = \is_admin() && !\wp_doing_ajax() ? 'admin' : 'public';

		$getterMethodName = 'get_' . $pluginPart;
		$registerMethodName = 'register_' . $pluginPart;

		if (!method_exists($this, $getterMethodName)) {
			throw new GenericException('Method %s does not exist', [$getterMethodName]);
		}

		if (!method_exists($this, $registerMethodName)) {
			throw new GenericException('Method %s does not exist', [$registerMethodName]);
		}

		foreach ($this->{$getterMethodName}() as $scope => $partList) {
			if ($scope === 'global' || $currentScope === $scope) {
				$this->{$registerMethodName}($partList);
			}
		}
	}

	/**
	 * Load plugin text domain
	 *
	 * @return	void
	 * @access	protected
	 * @since 	1.0.0
	 */
	final public function handle_load_plugin_textdomain() {
		load_plugin_textdomain($this->get_id(), false,  dirname(plugin_basename($this->get_plugin_file())) . '/languages');
	}

	/**
	 * Register assets
	 *
	 * @return void
	 */
	final public function handle_register_plugin_assets() {
		$this->register_plugin_part('assets');
	}

	/**
	 * Enqueue global assets
	 *
	 * @return  void
	 */
	final public function handle_enqueue_global_plugin_assets(): void {
		$this->enqueue_assets($this->get_assets()['global']);
	}
}
