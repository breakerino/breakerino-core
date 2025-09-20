<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Traits > Assets
 * ------------------------------------------------------------------------------
 * @created     05/06/2022
 * @updated     14/12/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Traits;

use \Breakerino\Core\Exceptions\Generic as GenericException;

defined('ABSPATH') || exit;

// TODO: Dynamically load assets from the folder.

trait Assets {
	private $assetTypeMap = [
		'style'     => 'css',
		'script'    => 'js'
	];

	/**
	 * Register plugin styles & scripts
	 * 
	 * @since   1.0.0
	 */
	private function register_assets(array $assets): void {
		foreach ($assets as $type => $assets) {

			if (!array_key_exists($type, $this->assetTypeMap)) {
				throw new GenericException('"%1$s" is not a valid asset type.', [$type]);
			}

			if (!method_exists($this, 'get_relative_base_url')) {
				throw new GenericException('Required method "%1$s" does not exists in class "%2$s".', ['get_relative_base_url', get_class($this)], null, static::class . '\Assets');
			}

			if (!method_exists($this, 'get_version')) {
				throw new GenericException('Required method "%1$s" does not exists in class "%2$s".', ['get_version', get_class($this)], null, static::class . '\Assets');
			}

			foreach ($assets as $assetID => $assetFile) {
				call_user_func(
					'wp_register_' . $type,
					$assetID,
					sprintf('%1$s/assets/%2$s/%3$s', $this->get_relative_base_url(), $this->assetTypeMap[$type], $assetFile),
					[],
					$this->get_version(),
					$type === 'style' ? 'all' : true
				);
			}
		}
	}

	public function enqueue_asset(string $id, string $type): void {
		call_user_func(
			'wp_enqueue_' . $type,
			$id
		);
	}

	/**
	 * Enqueue plugin styles & scripts
	 * 
	 * @since   1.0.0
	 */
	private function enqueue_assets(array $assets): void {
		foreach ($assets as $type => $assetEntries) {
			foreach ($assetEntries as $id => $file) {
				$this->enqueue_asset($id, $type);
			}
		}
	}
}
