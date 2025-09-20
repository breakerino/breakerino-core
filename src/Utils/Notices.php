<?php

/** 
 * ------------------------------------------------------------------------------
 * Breakerino > WooNotices
 * ------------------------------------------------------------------------------
 * @created     21/06/2022
 * @updated     21/06/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

use Breakerino\Core\Exceptions\Generic as GenericException;

class Notices {
	use \Breakerino\Core\Traits\Singleton;

	public const NOTICE_TYPES = ['error', 'warning', 'success', 'notice', 'info'];

	protected $notices = [];
	protected $noticeTypes = [];

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function init() {
		$this->noticeTypes = apply_filters('breakerino_notices_types', self::NOTICE_TYPES);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $message
	 * @param string $type
	 * @return boolean
	 */
	public function add(string $message, string $type = 'info') {
		if (!in_array($type, $this->noticeTypes)) {
			throw new GenericException('Invalid notice type');
		}

		// Avoid duplicates
		if (isset($this->notices[$type]) && in_array($message, $this->notices[$type])) {
			return false;
		}

		$this->notices[$type][uniqid()] = $message;
		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $id
	 * @param string $type
	 * @return void
	 */
	public function remove(string $id, string $type = 'info') {
		if (!in_array($type, $this->noticeTypes)) {
			throw new GenericException('Invalid notice type');
		}

		if (!isset($this->notices[$type][$id])) {
			unset($this->notices[$type][$id]);
			return true;
		}

		return false;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $type
	 * @return void
	 */
	public function clear_all($types = []) {
		if (!$types) {
			$this->notices = [];
			return;
		}

		foreach ($types as $type) {
			if (!in_array($type, $this->noticeTypes)) {
				throw new GenericException('Invalid notice type');
			}

			$this->notices[$type] = [];
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param array $type
	 * @return array
	 */
	public function get_all($types = []) {
		if (!$types) {
			return \apply_filters('breakerino_get_all_notices', $this->notices, $this);
		}

		$notices = [];

		foreach ($types as $type) {
			if (!in_array($type, $this->noticeTypes)) {
				throw new GenericException('Invalid notice type');
			}

			if (isset($this->notices[$type])) {
				$notices[$type] = \apply_filters('breakerino_get_' . $type . '_notices', $this->notices[$type], $this);
			}
		}

		return $notices;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $type
	 * @return boolean
	 */
	public function has_notices(array $types = []) {
		if (!$types) {
			foreach ($this->notices as $type => $messages) {
				if (!empty($messages)) {
					return true;
				}
			}

			return false;
		}

		$validCount = 0;

		foreach ($types as $type) {
			if (!in_array($type, $this->noticeTypes)) {
				throw new GenericException('Invalid notice type');
			}
			$validCount += (int) \apply_filters('breakerino_has_' . $type . '_notices', !empty($this->notices[$type]), $this);
		}

		return $validCount === count($types);
	}
}
