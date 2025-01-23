<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\MovedPermanentlyException;
use Friendica\Network\HTTPException\TemporaryRedirectException;
use Friendica\Util\BasePath;
use Friendica\Util\XML;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Contains the class with system relevant stuff
 */
class System
{
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var IManageConfigValues
	 */
	private $config;

	/**
	 * @var string
	 */
	private $basePath;

	public function __construct(LoggerInterface $logger, IManageConfigValues $config, string $basepath)
	{
		$this->logger   = $logger;
		$this->config   = $config;
		$this->basePath = $basepath;
	}

	/**
	 * Checks if the maximum number of database processes is reached
	 *
	 * @return bool Is the limit reached?
	 */
	public function isMaxProcessesReached(): bool
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
	 * Checks if the minimal memory is reached
	 *
	 * @return bool Is the memory limit reached?
	 */
	public function isMinMemoryReached(): bool
	{
		// Deactivated, needs more investigating if this check really makes sense
		return false;

		/*
		 * Commented out to suppress static analyzer issues
		 *
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
			[$key, $val]     = $data;
			$meminfo[$key]   = (int)trim(str_replace('kB', '', $val));
			$meminfo[$key]   = (int)($meminfo[$key] / 1024);
		}

		if (!isset($meminfo['MemFree'])) {
			return false;
		}

		$free = $meminfo['MemFree'];

		$reached = ($free < $min_memory);

		if ($reached) {
			$this->logger->warning('Minimal memory reached.', ['free' => $free, 'memtotal' => $meminfo['MemTotal'], 'limit' => $min_memory]);
		}

		return $reached;
		 */
	}

	/**
	 * Checks if the maximum load is reached
	 *
	 * @return bool Is the load reached?
	 */
	public function isMaxLoadReached(): bool
	{
		$maxsysload = intval($this->config->get('system', 'maxloadavg'));
		if ($maxsysload < 1) {
			$maxsysload = 50;
		}

		$load = System::currentLoad();
		if ($load) {
			if (intval($load) > $maxsysload) {
				$this->logger->notice('system load for process too high.', ['load' => $load, 'process' => 'backend', 'maxsysload' => $maxsysload]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Executes a child process with 'proc_open'
	 *
	 * @param string $command The command to execute
	 * @param array  $args    Arguments to pass to the command ( ['arg1', 'arg2', ... ] )
	 * @param array  $options Options to pass to the command ( [ 'key' => value, 'key2' => value2, ... ]
	 */
	public function run(string $command, array $args = [], array $options = [])
	{
		if (!function_exists('proc_open')) {
			$this->logger->warning('"proc_open" not available - quitting');
			return;
		}

		$cmdline = $this->config->get('config', 'php_path', 'php') . ' ' . escapeshellarg($command);

		foreach ($args as $argumment) {
			$cmdline .= ' ' . $argumment;
		}

		foreach ($options as $key => $value) {
			if (!is_null($value) && is_bool($value) && !$value) {
				continue;
			}

			$cmdline .= ' --' . $key;
			if (!is_null($value) && !is_bool($value)) {
				$cmdline .= ' ' . $value;
			}
		}

		if ($this->isMinMemoryReached()) {
			$this->logger->warning('Memory limit reached - quitting');
			return;
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$resource = proc_open('cmd /c start /b ' . $cmdline, [], $foo, $this->basePath);
		} else {
			$resource = proc_open($cmdline . ' &', [], $foo, $this->basePath);
		}

		if (!is_resource($resource)) {
			$this->logger->warning('We got no resource for command.', ['command' => $cmdline]);
			return;
		}

		proc_close($resource);

		$this->logger->info('Executed "proc_open"', ['command' => $cmdline]);
	}

	/**
	 * Returns a string with a callstack. Can be used for logging.
	 *
	 * @param integer $depth   How many calls to include in the stacks after filtering
	 * @param int     $offset  How many calls to shave off the top of the stack, for example if
	 *                         this is called from a centralized method that isn't relevant to the callstack
	 * @param bool    $full    If enabled, the callstack is not compacted
	 * @param array   $exclude
	 * @return string
	 */
	public static function callstack(int $depth = 4, int $offset = 0, bool $full = false, array $exclude = []): string
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		// We remove at least the first two items from the list since they contain data that we don't need.
		$trace = array_slice($trace, 2 + $offset);

		$callstack = [];
		$previous  = ['class' => '', 'function' => '', 'database' => false];

		// The ignore list contains all functions that are only wrapper functions
		$ignore = ['call_user_func_array'];

		while ($func = array_pop($trace)) {
			if (!empty($func['class'])) {
				if (in_array($func['class'], $exclude)) {
					continue;
				}

				if (!$full && in_array($previous['function'], ['insert', 'fetch', 'toArray', 'exists', 'count', 'selectFirst', 'selectToArray',
					'select', 'update', 'delete', 'selectFirstForUser', 'selectForUser'])
					&& (substr($previous['class'], 0, 15) === 'Friendica\Model')) {
					continue;
				}

				// Don't show multiple calls from the Database classes to show the essential parts of the callstack
				$func['database'] = in_array($func['class'], ['Friendica\Database\DBA', 'Friendica\Database\Database']);
				if ($full || !$previous['database'] || !$func['database']) {
					$classparts  = explode("\\", $func['class']);
					$callstack[] = array_pop($classparts).'::'.$func['function'] . (isset($func['line']) ? ' (' . $func['line'] . ')' : '');
					$previous    = $func;
				}
			} elseif (!in_array($func['function'], $ignore)) {
				$func['database'] = ($func['function'] == 'q');
				$callstack[]      = $func['function'] . (isset($func['line']) ? ' (' . $func['line'] . ')' : '');
				$func['class']    = '';
				$previous         = $func;
			}
		}

		$callstack2 = [];
		while ((count($callstack2) < $depth) && (count($callstack) > 0)) {
			$callstack2[] = array_pop($callstack);
		}

		return implode(', ', $callstack2);
	}

	/**
	 * Display current response, including setting all headers
	 *
	 * @param ResponseInterface $response
	 */
	public static function echoResponse(ResponseInterface $response)
	{
		header(
			sprintf(
				"HTTP/%s %s %s",
				$response->getProtocolVersion(),
				$response->getStatusCode(),
				$response->getReasonPhrase()
			)
		);

		foreach ($response->getHeaders() as $key => $header) {
			if (is_array($header)) {
				$header_str = implode(',', $header);
			} else {
				$header_str = $header;
			}

			if (is_int($key)) {
				header($header_str);
			} else {
				header("$key: $header_str");
			}
		}

		echo $response->getBody();
	}

	/**
	 * Generic XML return
	 * Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable
	 * of $st and an optional text <message> of $message and terminates the current process.
	 *
	 * @param mixed  $status
	 * @param string $message
	 * @throws \Exception
	 * @deprecated since 2023.09 Use BaseModule->httpExit() instead
	 */
	public static function xmlExit($status, string $message = '')
	{
		$result = ['status' => $status];

		if ($message != '') {
			$result['message'] = $message;
		}

		if ($status) {
			DI::logger()->notice('xml_status returning non_zero: ' . $status . " message=" . $message);
		}

		self::httpExit(XML::fromArray(['result' => $result]), Response::TYPE_XML);
	}

	/**
	 * Send HTTP status header and exit.
	 *
	 * @param integer $httpCode HTTP status result value
	 * @param string  $message  Error message. Optional.
	 * @param string  $content  Response body. Optional.
	 * @throws \Exception
	 * @deprecated since 2023.09 Use BaseModule->httpError instead
	 */
	public static function httpError($httpCode, $message = '', $content = '')
	{
		if ($httpCode >= 400) {
			DI::logger()->debug('Exit with error', ['code' => $httpCode, 'message' => $message, 'method' => DI::args()->getMethod(), 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}
		DI::apiResponse()->setStatus($httpCode, $message);

		self::httpExit($content);
	}

	/**
	 * This function adds the content and a content-type HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param string      $content
	 * @param string      $type
	 * @param string|null $content_type
	 * @return void
	 * @throws InternalServerErrorException
	 * @deprecated since 2023.09 Use BaseModule->httpExit() instead
	 */
	public static function httpExit(string $content, string $type = Response::TYPE_HTML, ?string $content_type = null)
	{
		DI::apiResponse()->setType($type, $content_type);
		DI::apiResponse()->addContent($content);
		self::echoResponse(DI::apiResponse()->generate());

		self::exit();
	}

	/**
	 * @deprecated since 2023.09 Use BaseModule->jsonError instead
	 */
	public static function jsonError($httpCode, $content, $content_type = 'application/json')
	{
		if ($httpCode >= 400) {
			DI::logger()->debug('Exit with error', ['code' => $httpCode, 'content_type' => $content_type, 'method' => DI::args()->getMethod(), 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}
		DI::apiResponse()->setStatus($httpCode);
		self::jsonExit($content, $content_type);
	}

	/**
	 * Encodes content to json.
	 *
	 * This function encodes an array to json format
	 * and adds an application/json HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param mixed   $content      The input content
	 * @param string  $content_type Type of the input (Default: 'application/json')
	 * @param integer $options      JSON options
	 * @throws InternalServerErrorException
	 * @deprecated since 2023.09 Use BaseModule->jsonExit instead
	 */
	public static function jsonExit($content, string $content_type = 'application/json', int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	{
		self::httpExit(json_encode($content, $options), Response::TYPE_JSON, $content_type);
	}

	/**
	 * Exit the program execution.
	 *
	 * @return never
	 */
	public static function exit()
	{
		DI::page()->logRuntime(DI::config(), 'exit');
		exit();
	}

	/**
	 * Generates a random string in the UUID format
	 *
	 * @param bool|string $prefix A given prefix (default is empty)
	 * @return string a generated UUID
	 * @throws \Exception
	 */
	public static function createUUID($prefix = '')
	{
		$guid = System::createGUID(32, $prefix);
		return substr($guid, 0, 8) . '-' . substr($guid, 8, 4) . '-' . substr($guid, 12, 4) . '-' . substr($guid, 16, 4) . '-' . substr($guid, 20, 12);
	}

	/**
	 * Generates a GUID with the given parameters
	 *
	 * @param int         $size   The size of the GUID (default is 16)
	 * @param bool|string $prefix A given prefix (default is empty)
	 * @return string a generated GUID
	 * @throws \Exception
	 */
	public static function createGUID($size = 16, $prefix = '')
	{
		if (is_bool($prefix) && !$prefix) {
			$prefix = '';
		} elseif (empty($prefix)) {
			$prefix = hash('crc32', DI::baseUrl()->getHost());
		}

		while (strlen($prefix) < ($size - 13)) {
			$prefix .= mt_rand();
		}

		if ($size >= 24) {
			$prefix = substr($prefix, 0, $size - 22);
			return str_replace('.', '', uniqid($prefix, true));
		} else {
			$prefix = substr($prefix, 0, max($size - 13, 0));
			return uniqid($prefix);
		}
	}

	/**
	 * Returns the current Load of the System
	 *
	 * @return integer
	 */
	public static function currentLoad()
	{
		if (!function_exists('sys_getloadavg')) {
			return false;
		}

		$load_arr = sys_getloadavg();

		if (!is_array($load_arr)) {
			return false;
		}

		return round(max($load_arr[0], $load_arr[1]), 2);
	}

	/**
	 * Fetch the load and number of processes
	 *
	 * @param bool $get_processes
	 * @return array
	 */
	public static function getLoadAvg(bool $get_processes = true): array
	{
		$load_arr = sys_getloadavg();
		if (empty($load_arr)) {
			return [];
		}

		$load = [
			'average1'  => $load_arr[0],
			'average5'  => $load_arr[1],
			'average15' => $load_arr[2],
			'runnable'  => 0,
			'scheduled' => 0
		];

		if ($get_processes && @is_readable('/proc/loadavg')) {
			$content = @file_get_contents('/proc/loadavg');
			if (!empty($content) && preg_match("#([.\d]+)\s([.\d]+)\s([.\d]+)\s(\d+)/(\d+)#", $content, $matches)) {
				$load['runnable']  = (float)$matches[4];
				$load['scheduled'] = (float)$matches[5];
			}
		}

		return $load;
	}

	/**
	 * Redirects to an external URL (fully qualified URL)
	 * If you want to route relative to the current Friendica base, use App->internalRedirect()
	 *
	 * @param string $url  The new Location to redirect
	 * @param int    $code The redirection code, which is used (Default is 302)
	 *
	 * @throws FoundException
	 * @throws MovedPermanentlyException
	 * @throws TemporaryRedirectException
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 *
	 * @return never
	 */
	public static function externalRedirect($url, $code = 302)
	{
		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			DI::logger()->warning('No fully qualified URL provided', ['url' => $url]);
			DI::baseUrl()->redirect($url);
		}

		header("Location: $url");

		switch ($code) {
			case 302:
				throw new FoundException();
			case 301:
				throw new MovedPermanentlyException();
			case 307:
				throw new TemporaryRedirectException();
		}
		self::exit();
	}

	/**
	 * Returns the system user that is executing the script
	 *
	 * This mostly returns something like "www-data".
	 *
	 * @return string system username
	 */
	public static function getUser()
	{
		if (!function_exists('posix_getpwuid') || !function_exists('posix_geteuid')) {
			return '';
		}

		$processUser = posix_getpwuid(posix_geteuid());
		return $processUser['name'];
	}

	/**
	 * Checks if a given directory is usable for the system
	 *
	 * @param      $directory
	 *
	 * @return boolean the directory is usable
	 */
	private static function isDirectoryUsable(string $directory): bool
	{
		if (empty($directory)) {
			DI::logger()->warning('Directory is empty. This shouldn\'t happen.');
			return false;
		}

		if (!file_exists($directory)) {
			DI::logger()->info('Path does not exist', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		if (is_file($directory)) {
			DI::logger()->warning('Path is a file', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		if (!is_dir($directory)) {
			DI::logger()->warning('Path is not a directory', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		if (!is_writable($directory)) {
			DI::logger()->warning('Path is not writable', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		return true;
	}

	/**
	 * Exit method used by asynchronous update modules
	 *
	 * @param string $o
	 */
	public static function htmlUpdateExit($o)
	{
		DI::apiResponse()->setType(Response::TYPE_HTML);
		echo "<!DOCTYPE html><html><body>\r\n";
		// We can remove this hack once Internet Explorer recognises HTML5 natively
		echo "<section>";
		// reportedly some versions of MSIE don't handle tabs in XMLHttpRequest documents very well
		echo str_replace("\t", "       ", $o);
		echo "</section>";
		echo "</body></html>\r\n";
		self::exit();
	}

	/**
	 * Fetch the temp path of the system
	 *
	 * @return string Path for temp files
	 */
	public static function getTempPath()
	{
		$temppath = DI::config()->get("system", "temppath");

		if (($temppath != "") && self::isDirectoryUsable($temppath)) {
			// We have a temp path and it is usable
			return BasePath::getRealPath($temppath);
		}

		// We don't have a working preconfigured temp path, so we take the system path.
		$temppath = sys_get_temp_dir();

		// Check if it is usable
		if (($temppath != "") && self::isDirectoryUsable($temppath)) {
			// Always store the real path, not the path through symlinks
			$temppath = BasePath::getRealPath($temppath);

			// To avoid any interferences with other systems we create our own directory
			$new_temppath = $temppath . "/" . DI::baseUrl()->getHost();
			if (!is_dir($new_temppath)) {
				/// @TODO There is a mkdir()+chmod() upwards, maybe generalize this (+ configurable) into a function/method?
				@mkdir($new_temppath);
			}

			if (self::isDirectoryUsable($new_temppath)) {
				// The new path is usable, we are happy
				DI::config()->set("system", "temppath", $new_temppath);
				return $new_temppath;
			} else {
				// We can't create a subdirectory, strange.
				// But the directory seems to work, so we use it but don't store it.
				return $temppath;
			}
		}

		// Reaching this point means that the operating system is configured badly.
		return '';
	}

	/**
	 * Returns the path where spool files are stored
	 *
	 * @return string Spool path
	 */
	public static function getSpoolPath()
	{
		$spoolpath = DI::config()->get('system', 'spoolpath');
		if (($spoolpath != "") && self::isDirectoryUsable($spoolpath)) {
			// We have a spool path and it is usable
			return $spoolpath;
		}

		// We don't have a working preconfigured spool path, so we take the temp path.
		$temppath = self::getTempPath();

		if ($temppath != "") {
			// To avoid any interferences with other systems we create our own directory
			$spoolpath = $temppath . "/spool";
			if (!is_dir($spoolpath)) {
				mkdir($spoolpath);
			}

			if (self::isDirectoryUsable($spoolpath)) {
				// The new path is usable, we are happy
				DI::config()->set("system", "spoolpath", $spoolpath);
				return $spoolpath;
			} else {
				// We can't create a subdirectory, strange.
				// But the directory seems to work, so we use it but don't store it.
				return $temppath;
			}
		}

		// Reaching this point means that the operating system is configured badly.
		return "";
	}

	/**
	 * Fetch the system rules
	 * @param bool $numeric_id If set to "true", the rules are returned with a numeric id as key.
	 *
	 * @return array
	 */
	public static function getRules(bool $numeric_id = false): array
	{
		$rules = [];
		$id    = 0;

		if (DI::config()->get('system', 'tosdisplay')) {
			$rulelist = DI::config()->get('system', 'tosrules') ?: DI::config()->get('system', 'tostext');
			$msg      = BBCode::toPlaintext($rulelist, false);
			foreach (explode("\n", trim($msg)) as $line) {
				$line = trim($line);
				if ($line) {
					if ($numeric_id) {
						$rules[++$id] = $line;
					} else {
						$rules[] = ['id' => (string)++$id, 'text' => $line];
					}
				}
			}
		}

		return $rules;
	}
}
