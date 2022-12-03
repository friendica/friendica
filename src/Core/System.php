<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Network\HTTPException\MovedPermanentlyException;
use Friendica\Network\HTTPException\TemporaryRedirectException;
use Friendica\Util\BasePath;
use Friendica\Util\XML;
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
				$this->logger->warning('system load for process too high.', ['load' => $load, 'process' => 'backend', 'maxsysload' => $maxsysload]);
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
	public function run(string $command, array $args)
	{
		if (!function_exists('proc_open')) {
			$this->logger->warning('"proc_open" not available - quitting');
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

		$this->logger->info('Executed "proc_open"', ['command' => $cmdline, 'callstack' => System::callstack(10)]);
	}

	/**
	 * Returns a string with a callstack. Can be used for logging.
	 *
	 * @param integer $depth  How many calls to include in the stacks after filtering
	 * @param int     $offset How many calls to shave off the top of the stack, for example if
	 *                        this is called from a centralized method that isn't relevant to the callstack
	 * @return string
	 */
	public static function callstack(int $depth = 4, int $offset = 0): string
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		// We remove at least the first two items from the list since they contain data that we don't need.
		$trace = array_slice($trace, 2 + $offset);

		$callstack = [];
		$previous = ['class' => '', 'function' => '', 'database' => false];

		// The ignore list contains all functions that are only wrapper functions
		$ignore = ['call_user_func_array'];

		while ($func = array_pop($trace)) {
			if (!empty($func['class'])) {
				if (in_array($previous['function'], ['insert', 'fetch', 'toArray', 'exists', 'count', 'selectFirst', 'selectToArray',
					'select', 'update', 'delete', 'selectFirstForUser', 'selectForUser'])
					&& (substr($previous['class'], 0, 15) === 'Friendica\Model')) {
					continue;
				}

				// Don't show multiple calls from the Database classes to show the essential parts of the callstack
				$func['database'] = in_array($func['class'], ['Friendica\Database\DBA', 'Friendica\Database\Database']);
				if (!$previous['database'] || !$func['database']) {	
					$classparts = explode("\\", $func['class']);
					$callstack[] = array_pop($classparts).'::'.$func['function'];
					$previous = $func;
				}
			} elseif (!in_array($func['function'], $ignore)) {
				$func['database'] = ($func['function'] == 'q');
				$callstack[] = $func['function'];
				$func['class'] = '';
				$previous = $func;
			}
		}

		$callstack2 = [];
		while ((count($callstack2) < $depth) && (count($callstack) > 0)) {
			$callstack2[] = array_pop($callstack);
		}

		return implode(', ', $callstack2);
	}

	/**
	 * Generic XML return
	 * Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable
	 * of $st and an optional text <message> of $message and terminates the current process.
	 *
	 * @param        $st
	 * @param string $message
	 * @throws \Exception
	 */
	public static function xmlExit($st, $message = '')
	{
		$result = ['status' => $st];

		if ($message != '') {
			$result['message'] = $message;
		}

		if ($st) {
			Logger::notice('xml_status returning non_zero: ' . $st . " message=" . $message);
		}

		DI::apiResponse()->setType(Response::TYPE_XML);
		DI::apiResponse()->addContent(XML::fromArray(["result" => $result], $xml));
		DI::page()->exit(DI::apiResponse()->generate());

		self::exit();
	}

	/**
	 * Send HTTP status header and exit.
	 *
	 * @param integer $val     HTTP status result value
	 * @param string  $message Error message. Optional.
	 * @param string  $content Response body. Optional.
	 * @throws \Exception
	 */
	public static function httpError($httpCode, $message = '', $content = '')
	{
		if ($httpCode >= 400) {
			Logger::debug('Exit with error', ['code' => $httpCode, 'message' => $message, 'callstack' => System::callstack(20), 'method' => DI::args()->getMethod(), 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}
		DI::apiResponse()->setStatus($httpCode, $message);
		DI::apiResponse()->addContent($content);
		DI::page()->exit(DI::apiResponse()->generate());

		self::exit();
	}

	/**
	 * This function adds the content and a content-teype HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param string $content
	 * @param string $type
	 * @param string|null $content_type
	 * @return void
	 */
	public static function httpExit(string $content, string $type = Response::TYPE_HTML, ?string $content_type = null) {
		DI::apiResponse()->setType($type, $content_type);
		DI::apiResponse()->addContent($content);
		DI::page()->exit(DI::apiResponse()->generate());

		self::exit();
	}

	public static function jsonError($httpCode, $content, $content_type = 'application/json')
	{
		if ($httpCode >= 400) {
			Logger::debug('Exit with error', ['code' => $httpCode, 'content_type' => $content_type, 'callstack' => System::callstack(20), 'method' => DI::args()->getMethod(), 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function jsonExit($content, $content_type = 'application/json', int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) {
		DI::apiResponse()->setType(Response::TYPE_JSON, $content_type);
		DI::apiResponse()->addContent(json_encode($content, $options));
		DI::page()->exit(DI::apiResponse()->generate());

		self::exit();
	}

	/**
	 * Exit the program execution.
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
			$prefix = hash('crc32', DI::baseUrl()->getHostname());
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

		return max($load_arr[0], $load_arr[1]);
	}

	/**
	 * Fetch the load and number of processes
	 *
	 * @return array
	 */
	public static function getLoadAvg(): array
	{
		if (@is_readable('/proc/loadavg')) {
			$content = @file_get_contents('/proc/loadavg');
			if (empty($content)) {
				$content = shell_exec('uptime | sed "s/.*averages*: //" | sed "s/,//"');
			}
		}

		if (empty($content) || !preg_match("#([.\d]+)\s([.\d]+)\s([.\d]+)\s(\d+)/(\d+)#", $content, $matches)) {
			$load_arr = sys_getloadavg();
			if (empty($load_arr)) {
				return [];
			}
			return [
				'average1'  => $load_arr[0],
				'average5'  => $load_arr[1],
				'average15' => $load_arr[2],
				'runnable'  => 0,
				'scheduled' => 0
			];
		}

		return [
			'average1'  => (float)$matches[1],
			'average5'  => (float)$matches[2],
			'average15' => (float)$matches[3],
			'runnable'  => (float)$matches[4],
			'scheduled' => (float)$matches[5]
		];
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
	 */
	public static function externalRedirect($url, $code = 302)
	{
		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			Logger::warning('No fully qualified URL provided', ['url' => $url, 'callstack' => self::callstack(20)]);
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
	 * @param bool $check_writable
	 *
	 * @return boolean the directory is usable
	 */
	private static function isDirectoryUsable($directory, $check_writable = true)
	{
		if ($directory == '') {
			Logger::warning('Directory is empty. This shouldn\'t happen.');
			return false;
		}

		if (!file_exists($directory)) {
			Logger::warning('Path does not exist', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		if (is_file($directory)) {
			Logger::warning('Path is a file', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		if (!is_dir($directory)) {
			Logger::warning('Path is not a directory', ['directory' => $directory, 'user' => static::getUser()]);
			return false;
		}

		if ($check_writable && !is_writable($directory)) {
			Logger::warning('Path is not writable', ['directory' => $directory, 'user' => static::getUser()]);
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
			$new_temppath = $temppath . "/" . DI::baseUrl()->getHostname();
			if (!is_dir($new_temppath)) {
				/// @TODO There is a mkdir()+chmod() upwards, maybe generalize this (+ configurable) into a function/method?
				mkdir($new_temppath);
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
}
