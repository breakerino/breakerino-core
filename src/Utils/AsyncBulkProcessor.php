<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino | Utils | Async Bulk Processor
 * ------------------------------------------------------------------------------
 * @created    	13/09/2022
 * @updated    	03/06/2022
 * @version			1.1.1
 * @author     	Matúš Mendel
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\Core\Utils;

defined('ABSPATH') || exit;

use Breakerino\Core\Utils\Logger;

class AsyncBulkProcessor {
	/**
	 * Process ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Limit per bulk
	 *
	 * @var int
	 */
	public $limit;

	/**
	 * Current bulk offset
	 *
	 * @var int
	 */
	public $offset;

	/**
	 * Total entries
	 *
	 * @var int
	 */
	public $count;

	/**
	 * Process callback
	 *
	 * @var string|array
	 */
	public $callback;

	/**
	 * Process interval
	 *
	 * @var int|null
	 */
	public $interval = null;

	public $currentStep = 0;

	public $totalSteps = 0;

	public $triggerAfterFinishHook = true;


	/**
	 * Undocumented function
	 *
	 * @param string $id
	 * @param array $args
	 */
	public function __construct($id, $args, $reset = false) {
		$this->set_id($id);
		$this->set_props($args, $reset);
	}

	private function log($type, $message, ...$args) {
		if (!method_exists('Breakerino\Core\Utils\Logger', $type)) {
			return false;
		}

		return Logger::{$type}("Bulk Processor | {$this->id} | {$message}", ...$args);
	}

	/**
	 * Undocumented function
	 *
	 * @param array $args
	 * @return void
	 */
	private function set_props($args, $reset = false) {
		$processArgs = !$reset ? \wp_parse_args($this->get_state('args', $args), [
			'limit' => 1,
			'offset' => 0,
			'currentStep' => null,
			'totalSteps' => null,
			'count' => null,
			'trigger_after_finish_hook' => true // default=true
		]) : $args;

		$this->set_process_callback($args['process_callback']);

		// NOTE: Should be probably done only initially (to keep the initial count, i.e. can differ during process)
		if (!isset($processArgs['count']) || empty($processArgs['count'])) {
			if (!is_callable($args['count_callback'])) {
				throw new \Exception('Invalid count callback provided');
			}

			$processArgs['count'] = call_user_func($args['count_callback']);
		}

		$this->set_interval($args['interval']);

		$this->set_count($processArgs['count']);
		$this->set_limit($processArgs['limit'] <= $processArgs['count'] ? $processArgs['limit'] : $processArgs['count']);
		$this->set_offset($processArgs['offset'] <= $processArgs['count'] ? $processArgs['offset'] : $processArgs['count']);

		if (is_null($processArgs['totalSteps']) && $this->limit > 0) { # NOTE: Division by zero
			$processArgs['totalSteps'] = ceil($this->count / $this->limit);
			$processArgs['currentStep'] = 0;
		}

		$this->currentStep = $processArgs['currentStep'] + 1;
		$this->totalSteps = $processArgs['totalSteps'];

		$this->triggerAfterFinishHook = isset($args['trigger_after_finish_hook']) ? $args['trigger_after_finish_hook'] : true;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_status() {
		$isProcessing = $this->get_state('processing', false);

		if ($isProcessing) {
			return 'processing';
		}

		// $nextSchedule = $this->get_state('next_schedule');

		// if ( $nextSchedule ) {
		// 	return 'scheduled';
		// }

		if ($this->offset >= $this->count) {
			return 'finished';
		}

		if ($this->offset > 0) {
			return 'in_progress';
		}

		return 'pending';
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	private function get_state_prefix() {
		return sprintf('wa_bulk_process_%s_', $this->id);
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $limit
	 * @return void
	 */
	public function set_limit($limit) {
		$this->limit = $limit;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $limit
	 * @return void
	 */
	public function set_interval($interval) {
		$this->interval = $interval;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $callback
	 * @return void
	 */
	public function set_process_callback($callback) {
		if (!is_callable($callback)) {
			throw new \Exception('Invalid process callback provided');
		}

		$this->callback = $callback;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $id
	 * @return void
	 */
	public function set_id($id) {
		$this->id = $id;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $offset
	 * @return void
	 */
	public function set_offset($offset) {
		$this->offset = $offset;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function set_count($count) {
		$this->count = $count;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	private function prepare_next_bulk() {
		$nextOffset = $this->offset + $this->limit;

		$this->set_offset($nextOffset);

		if (($this->offset + $this->limit) > $this->count) {
			$this->set_limit($this->count - $this->offset);
		}

		$this->set_state('args', [
			'count' => $this->count, // added @04/05/23 23:32
			'offset' => $this->offset,
			'limit' => $this->limit,
			'currentStep' => $this->currentStep,
			'totalSteps' => $this->totalSteps
		], \HOUR_IN_SECONDS);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	private function process_bulk() {
		if (!is_callable($this->callback)) {
			throw new \Exception('Invalid process callback provided.');
		}

		$this->log('debug', '%s/%s | Processing bulk... (limit: %s, offset: %s, count: %s)', $this->get_current_step(), $this->get_total_steps(), $this->limit, $this->offset, $this->count);

		$this->set_state('processing', true, \HOUR_IN_SECONDS / 2);

		$response = ['processed' => false, 'result' => null];

		try {
			// Benchmark::start();
			$response['result'] = call_user_func($this->callback, [
				'offset' => $this->offset,
				'limit' => $this->limit
			]);
			// Benchmark::stop();

			$response['processed'] = true;

			// $this->log('debug', '%s/%s | Finished in %ss (limit: %s, offset: %s, count: %s)', $this->get_current_step(), $this->get_total_steps(), Benchmark::get_raw_time(true), $this->limit, $this->offset, $this->count);
			$this->log('debug', '%s/%s | Result: %s', $this->get_current_step(), $this->get_total_steps(), json_encode($response['result']));
		} catch (\Throwable $e) {
			$this->log('debug', '%s/%s | An error occured while processing bulk: %s (limit: %s, offset: %s, count: %s)', $this->get_current_step(), $this->get_total_steps(), $e->getMessage(), $this->limit, $this->offset, $this->count);
		}

		$this->set_state('processing', false, \HOUR_IN_SECONDS / 2);

		return $response;
	}

	/**
	 * ------------------------------------------------
	 * State
	 * TODO: Abstract to Trait
	 * ------------------------------------------------
	 */

	/**
	 * Get process state value
	 *
	 * @return mixed
	 */
	private function get_state($type, $default = null) {
		//$value = get_state($this->get_state_prefix() . $type);
		//return $value !== false ? $value : $default;

		$value = \get_option($this->get_state_prefix() . $type);

		if (!isset($value['expires_at'], $value['data'])) {
			return $default;
		}

		if (is_numeric($value['expires_at']) && $value['expires_at'] < time()) {
			return $default;
		}

		return $value['data'] ?? $default;
	}

	/**
	 * Set process state value
	 *
	 * @return bool
	 */
	private function set_state($type, $data, $expiration = \MINUTE_IN_SECONDS) {
		return \update_option($this->get_state_prefix() . $type, [
			'expires_at' => time() + $expiration,
			'data' => $data
		]);
		//return set_state($this->get_state_prefix() . $type, $data, $expiration);
	}

	/**
	 * Delete process state value
	 *
	 * @return bool
	 */
	private function delete_state($type) {
		return delete_option($this->get_state_prefix() . $type);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_current_step() {
		return $this->currentStep;
		//return ceil($this->offset / $this->limit) + 1;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function get_total_steps() {
		//return ceil($this->count / $this->limit);
		return $this->totalSteps;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function process() {
		try {
			$this->log('debug', $this->get_status());

			if ($this->get_status() === 'processing') {
				throw new \Exception('Another process is already running, skipping...');
			}

			if ($this->get_status() === 'finished') {
				throw new \Exception('Bulk process is already finished, skipping...');
			}

			# NOTE: Temp disabled
			// $nextSchedule = $this->get_state('next_schedule');

			// if ( ! empty($nextSchedule) ) {
			// 	$scheduledDate = wp_date('c', $nextSchedule);
			// 	throw new \Exception("Next run scheduled at \"{$scheduledDate}\", skipping...");
			// }

			$result = $this->process_bulk();

			if ($result['processed']) {
				$this->prepare_next_bulk();
			}

			// Reset if finished and schedule next if should
			if ($this->get_status() === 'finished') {
				$this->end();

				$this->log('debug', 'Processed finished.');

				if ($this->triggerAfterFinishHook === true) {
					$this->log('debug', 'Triggering after finish hook "%s" for %s...', 'breakerino_bulk_process_after', $this->id);
					\do_action('breakerino_bulk_process_after', $this->id);
					\do_action('breakerino/bulk_process/after', $this->id, $this);
				}

				// NOTE: Temp disabled
				// if ( !is_null($this->interval) ) {
				// 	$this->set_state('next_schedule', time() + $this->interval, $this->interval);
				// }
			}
		} catch (\Exception $e) {
			$this->log('error', $e->getMessage());
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function end() {
		$this->set_offset(0);
		$this->delete_state('args');
		$this->delete_state('processing');
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function reset() {
		$this->end();
		$this->delete_state('next_schedule');
		$this->log('debug', 'Processed cancelled.');
	}
}
