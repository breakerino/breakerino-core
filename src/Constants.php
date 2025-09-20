<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino Core > Constants
 * ------------------------------------------------------------------------------
 * @created     21/04/2023
 * @updated     21/04/2023
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core;

defined('ABSPATH') || exit;

interface Constants {
	public const BUILD_BASE_DIR = 'dist';
	
	// TODO: Dynamically preload/populate this list (from vite manifest ?)
	// TODO: Filterable (version-specific) or dynamic preloading from vite manifest file (store in transient and force update when build version changes)
	public const PRELOADED_APP_ASSETS = [
	// Assets
	'icons/icons.svg',
	
	// Libs
	'js/vendor/react.js',
	'js/vendor/lodash.js',
	'js/vendor/chakra-ui.js',
	'js/vendor/chakra-react-select.js',
	'js/vendor/mustache.js',
	'js/vendor/splide.js',
	
	// Hooks
	'js/lib/useTranslations.js',
	'js/lib/useProductPrice.js',
	'js/lib/useProductAttributes.js',
	
	// Components
	'js/lib/Icon.js',
	'js/lib/LinkedProducts.js',
	'js/lib/InjectionZone.js',
	'js/lib/QuantitySelect.js',
	'js/components/cart-sidebar.js',
	
	// Apps
	'js/apps/ecommerce.js',
	'js/apps/minicart.js',
	'js/apps/product-area.js',
	'js/apps/megamenu.js',
		
	// Entrypoint
	'js/lib/index.js',
	];
}
