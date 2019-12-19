<?php

namespace Friendica\Core;

use Friendica\App;
use Friendica\Core\Config\IConfiguration;
use Psr\Log\LoggerInterface;

/**
 * Methods for interacting with the current process or create new process
 *
 * @todo 2019.12 Next release, this class holds all process relevant methods based on the big Worker class
 *       - Starting new processes (including checks)
 *       - Enabling multi-node processing (e.g. for docker service)
 *         - Using an process-id per node
 *         - Using memory locks for multi-node locking (redis, memcached, ..)
 */
final class Process
{
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var App\Mode
	 */
	private $mode;

	/**
	 * @var IConfiguration
	 */
	private $config;

	/**
	 * @var string
	 */
	private $basePath;

	public function __construct(LoggerInterface $logger, App\Mode $mode, IConfiguration $config, string $basepath)
	{
		$this->logger   = $logger;
		$this->mode     = $mode;
		$this->config   = $config;
		$this->basePath = $basepath;
	}

	/**
	 * @brief Checks if the maximum number of database processes is reached
	 *
	 * @return bool Is the limit reached?
	 */
	public function isMaxProcessesReached()
	{
		// Deactivated, needs more investigating if this check really makes sense
		return false;

		/*
		 * Commented out to suppress static analyzer issues
		 *
		if ($this->mode->isBackend()) {
			$process = 'backend';
			$max_processes = $this->config->get('system', 'max_processes_backend');
			if (intval($max_processes) == 0) {
				$max_processes = 5;
			}
		} else {
			$process = 'frontend';
			$max_processes = $this->config->get('system', 'max_processes_frontend');
			if (intval($max_processes) == 0) {
				$max_processes = 20;
			}
		}

		$processlist = DBA::processlist();
		if ($processlist['list'] != '') {
			$this->logger->debug('Processcheck: Processes: ' . $processlist['amount'] . ' - Processlist: ' . $processlist['list']);

			if ($processlist['amount'] > $max_processes) {
				$this->logger->debug('Processcheck: Maximum number of processes for ' . $process . ' tasks (' . $max_processes . ') reached.');
				return true;
			}
		}
		return false;
		 */
	}

	/**
	 * @brief Checks if the minimal memory is reached
	 *
	 * @return bool Is the memory limit reached?
	 */
	public function isMinMemoryReached()
	{
		$min_memory = $this->config->get('system', 'min_memory', 0);
		if ($min_memory == 0) {
			return false;
		}

		if (!is_readable('/proc/meminfo')) {
			return false;
		}

		$memdata = explode("\n", file_get_contents('/proc/meminfo'));

		$meminfo = [];
		foreach ($memdata as $line) {
			$data = explode(':', $line);
			if (count($data) != 2) {
				continue;
			}
			list($key, $val) = $data;
			$meminfo[$key] = (int)trim(str_replace('kB', '', $val));
			$meminfo[$key] = (int)($meminfo[$key] / 1024);
		}

		if (!isset($meminfo['MemFree'])) {
			return false;
		}

		$free = $meminfo['MemFree'];

		$reached = ($free < $min_memory);

		if ($reached) {
			$this->logger->debug('Minimal memory reached.', ['free' => $free, 'memtotal' => $meminfo['MemTotal'], 'limit' => $min_memory]);
		}

		return $reached;
	}

	/**
	 * @brief Checks if the maximum load is reached
	 *
	 * @return bool Is the load reached?
	 */
	public function isMaxLoadReached()
	{
		if ($this->mode->isBackend()) {
			$process    = 'backend';
			$maxsysload = intval($this->config->get('system', 'maxloadavg'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		} else {
			$process    = 'frontend';
			$maxsysload = intval($this->config->get('system', 'maxloadavg_frontend'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		}

		$load = System::currentLoad();
		if ($load) {
			if (intval($load) > $maxsysload) {
				$this->logger->info('system load for process too high.', ['load' => $load, 'process' => $process, 'maxsysload' => $maxsysload]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Executes a child process with 'proc_open'
	 *
	 * @param string $command The command to execute
	 * @param array  $args    Arguments to pass to the command ( [ 'key' => value, 'key2' => value2, ... ]
	 */
	public function run($command, $args)
	{
		if (!function_exists('proc_open')) {
			return;
		}

		$cmdline = $this->config->get('config', 'php_path', 'php') . ' ' . escapeshellarg($command);

		foreach ($args as $key => $value) {
			if (!is_null($value) && is_bool($value) && !$value) {
				continue;
			}

			$cmdline .= ' --' . $key;
			if (!is_null($value) && !is_bool($value)) {
				$cmdline .= ' ' . $value;
			}
		}

		if ($this->isMinMemoryReached()) {
			return;
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$resource = proc_open('cmd /c start /b ' . $cmdline, [], $foo, $this->basePath);
		} else {
			$resource = proc_open($cmdline . ' &', [], $foo, $this->basePath);
		}
		if (!is_resource($resource)) {
			$this->logger->debug('We got no resource for command.', ['cmd' => $cmdline]);
			return;
		}
		proc_close($resource);
	}
}
