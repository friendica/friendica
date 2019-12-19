<?php

namespace Friendica\Util;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\IConfiguration;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * A class to store profiling data
 * It can handle different logging data for specific functions or global performance measures
 *
 * It stores the data as log entries (@see LoggerInterface)
 */
class Profiler implements ContainerInterface
{
	/**
	 * @var array The global performance array
	 */
	private $performance;
	/**
	 * @var array The function specific callstack
	 */
	private $callstack;
	/**
	 * @var bool True, if the Profiler is enabled
	 */
	private $enabled;
	/**
	 * @var bool True, if the Profiler should measure the whole rendertime including functions
	 */
	private $rendertime;

	/**
	 * True, if the Profiler should measure the whole rendertime including functions
	 *
	 * @return bool
	 */
	public function isRendertime()
	{
		return $this->rendertime;
	}

	/**
	 * Updates the enabling of the current profiler
	 *
	 * @param IConfiguration $config
	 */
	public function update(IConfiguration $config)
	{
		$this->enabled = $config->get('system', 'profiler');
		$this->rendertime = $config->get('rendertime', 'callstack');
	}

	/**
	 * @param ConfigCache $configCache The configuration cache
	 */
	public function __construct(ConfigCache $configCache)
	{
		$this->enabled = $configCache->get('system', 'profiler');
		$this->rendertime = $configCache->get('rendertime', 'callstack');
		$this->reset();
	}

	/**
	 * Saves a timestamp for a value - f.e. a call
	 * Necessary for profiling Friendica
	 *
	 * @param int $timestamp the Timestamp
	 * @param string $value A value to profile
	 * @param string $callstack The callstack of the current profiling data
	 */
	public function saveTimestamp($timestamp, $value, $callstack = '')
	{
		if (!$this->enabled) {
			return;
		}

		$duration = floatval(microtime(true) - $timestamp);

		if (!isset($this->performance[$value])) {
			// Prevent ugly E_NOTICE
			$this->performance[$value] = 0;
		}

		$this->performance[$value] += (float) $duration;
		$this->performance['marktime'] += (float) $duration;

		if (!isset($this->callstack[$value][$callstack])) {
			// Prevent ugly E_NOTICE
			$this->callstack[$value][$callstack] = 0;
		}

		$this->callstack[$value][$callstack] += (float) $duration;
	}

	/**
	 * Resets the performance and callstack profiling
	 */
	public function reset()
	{
		$this->resetPerformance();
		$this->resetCallstack();
	}

	/**
	 * Resets the performance profiling data
	 */
	public function resetPerformance()
	{
		$this->performance = [];
		$this->performance['start'] = microtime(true);
		$this->performance['database'] = 0;
		$this->performance['database_write'] = 0;
		$this->performance['cache'] = 0;
		$this->performance['cache_write'] = 0;
		$this->performance['network'] = 0;
		$this->performance['file'] = 0;
		$this->performance['rendering'] = 0;
		$this->performance['parser'] = 0;
		$this->performance['marktime'] = 0;
		$this->performance['marktime'] = microtime(true);
	}

	/**
	 * Resets the callstack profiling data
	 */
	public function resetCallstack()
	{
		$this->callstack = [];
		$this->callstack['database'] = [];
		$this->callstack['database_write'] = [];
		$this->callstack['cache'] = [];
		$this->callstack['cache_write'] = [];
		$this->callstack['network'] = [];
		$this->callstack['file'] = [];
		$this->callstack['rendering'] = [];
		$this->callstack['parser'] = [];
	}

	/**
	 * Returns the rendertime string
	 *
	 * @return string the rendertime
	 */
	public function getRendertimeString()
	{
		$output = '';

		if (!$this->enabled || !$this->rendertime) {
			return $output;
		}

		if (isset($this->callstack["database"])) {
			$output .= "\nDatabase Read:\n";
			foreach ($this->callstack["database"] as $func => $time) {
				$time = round($time, 3);
				if ($time > 0) {
					$output .= $func . ": " . $time . "\n";
				}
			}
		}
		if (isset($this->callstack["database_write"])) {
			$output .= "\nDatabase Write:\n";
			foreach ($this->callstack["database_write"] as $func => $time) {
				$time = round($time, 3);
				if ($time > 0) {
					$output .= $func . ": " . $time . "\n";
				}
			}
		}
		if (isset($this->callstack["cache"])) {
			$output .= "\nCache Read:\n";
			foreach ($this->callstack["cache"] as $func => $time) {
				$time = round($time, 3);
				if ($time > 0) {
					$output .= $func . ": " . $time . "\n";
				}
			}
		}
		if (isset($this->callstack["cache_write"])) {
			$output .= "\nCache Write:\n";
			foreach ($this->callstack["cache_write"] as $func => $time) {
				$time = round($time, 3);
				if ($time > 0) {
					$output .= $func . ": " . $time . "\n";
				}
			}
		}
		if (isset($this->callstack["network"])) {
			$output .= "\nNetwork:\n";
			foreach ($this->callstack["network"] as $func => $time) {
				$time = round($time, 3);
				if ($time > 0) {
					$output .= $func . ": " . $time . "\n";
				}
			}
		}

		return $output;
	}

	/**
	 * Save the current profiling data to a log entry
	 *
	 * @param LoggerInterface $logger  The logger to save the current log
	 * @param string          $message Additional message for the log
	 */
	public function saveLog(LoggerInterface $logger, $message = '')
	{
		$duration = microtime(true) - $this->get('start');
		$logger->info(
			$message,
			[
				'action' => 'profiling',
				'database_read' => round($this->get('database') - $this->get('database_write'), 3),
				'database_write' => round($this->get('database_write'), 3),
				'cache_read' => round($this->get('cache'), 3),
				'cache_write' => round($this->get('cache_write'), 3),
				'network_io' => round($this->get('network'), 2),
				'file_io' => round($this->get('file'), 2),
				'other_io' => round($duration - ($this->get('database')
						+ $this->get('cache') + $this->get('cache_write')
						+ $this->get('network') + $this->get('file')), 2),
				'total' => round($duration, 2)
			]
		);

		if ($this->isRendertime()) {
			$output = $this->getRendertimeString();
			$logger->info($message . ": " . $output, ['action' => 'profiling']);
		}
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return int Entry.
	 */
	public function get($id)
	{
		if (!$this->has($id)) {
			return 0;
		} else {
			return $this->performance[$id];
		}
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has($id)
	{
		return isset($this->performance[$id]);
	}
}
