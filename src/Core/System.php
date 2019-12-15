<?php
/**
 * @file src/Core/System.php
 */
namespace Friendica\Core;

use Friendica\App\BaseURL;
use Friendica\BaseObject;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\XML;

/**
 * @file include/Core/System.php
 *
 * @brief Contains the class with system relevant stuff
 */


/**
 * @brief System methods
 */
class System extends BaseObject
{
	/**
	 * @brief Retrieves the Friendica instance base URL
	 *
	 * @param bool $ssl Whether to append http or https under BaseURL::SSL_POLICY_SELFSIGN
	 * @return string Friendica server base URL
	 * @throws InternalServerErrorException
	 */
	public static function baseUrl($ssl = false)
	{
		return self::getClass(BaseURL::class)->get($ssl);
	}

	/**
	 * @brief Removes the baseurl from an url. This avoids some mixed content problems.
	 *
	 * @param string $orig_url The url to be cleaned
	 *
	 * @return string The cleaned url
	 * @throws \Exception
	 */
	public static function removedBaseUrl(string $orig_url)
	{
		return DI::app()->removeBaseURL($orig_url);
	}

	/**
	 * @brief Returns a string with a callstack. Can be used for logging.
	 * @param integer $depth optional, default 4
	 * @return string
	 */
	public static function callstack($depth = 4)
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		// We remove the first two items from the list since they contain data that we don't need.
		array_shift($trace);
		array_shift($trace);

		$callstack = [];
		$previous = ['class' => '', 'function' => ''];

		// The ignore list contains all functions that are only wrapper functions
		$ignore = ['fetchUrl', 'call_user_func_array'];

		while ($func = array_pop($trace)) {
			if (!empty($func['class'])) {
				// Don't show multiple calls from the "dba" class to show the essential parts of the callstack
				if ((($previous['class'] != $func['class']) || ($func['class'] != 'Friendica\Database\DBA')) && ($previous['function'] != 'q')) {
					$classparts = explode("\\", $func['class']);
					$callstack[] = array_pop($classparts).'::'.$func['function'];
					$previous = $func;
				}
			} elseif (!in_array($func['function'], $ignore)) {
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
			Logger::log('xml_status returning non_zero: ' . $st . " message=" . $message);
		}

		header("Content-type: text/xml");

		$xmldata = ["result" => $result];

		echo XML::fromArray($xmldata, $xml);

		exit();
	}

	/**
	 * @brief Send HTTP status header and exit.
	 *
	 * @param integer $val     HTTP status result value
	 * @param string  $message Error message. Optional.
	 * @param string  $content Response body. Optional.
	 * @throws \Exception
	 */
	public static function httpExit($val, $message = '', $content = '')
	{
		Logger::log('http_status_exit ' . $val);
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $message);

		echo $content;

		exit();
	}

	public static function jsonError($httpCode, $data, $content_type = 'application/json')
	{
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $httpCode);
		self::jsonExit($data, $content_type);
	}

	/**
	 * @brief Encodes content to json.
	 *
	 * This function encodes an array to json format
	 * and adds an application/json HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param mixed  $x The input content.
	 * @param string $content_type Type of the input (Default: 'application/json').
	 */
	public static function jsonExit($x, $content_type = 'application/json') {
		header("Content-type: $content_type");
		echo json_encode($x);
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
			$prefix = hash('crc32', DI::app()->getHostName());
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
	 * Redirects to an external URL (fully qualified URL)
	 * If you want to route relative to the current Friendica base, use App->internalRedirect()
	 *
	 * @param string $url  The new Location to redirect
	 * @param int    $code The redirection code, which is used (Default is 302)
	 *
	 * @throws InternalServerErrorException If the URL is not fully qualified
	 */
	public static function externalRedirect($url, $code = 302)
	{
		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			throw new InternalServerErrorException("'$url' is not a fully qualified URL, please use App->internalRedirect() instead");
		}

		switch ($code) {
			case 302:
				// this is the default code for a REDIRECT
				// We don't need a extra header here
				break;
			case 301:
				header('HTTP/1.1 301 Moved Permanently');
				break;
			case 307:
				header('HTTP/1.1 307 Temporary Redirect');
				break;
		}

		header("Location: $url");
		exit();
	}

	/**
	 * @brief Returns the system user that is executing the script
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
	 * @brief Checks if a given directory is usable for the system
	 *
	 * @param      $directory
	 * @param bool $check_writable
	 *
	 * @return boolean the directory is usable
	 */
	public static function isDirectoryUsable($directory, $check_writable = true)
	{
		if ($directory == '') {
			Logger::log('Directory is empty. This shouldn\'t happen.', Logger::DEBUG);
			return false;
		}

		if (!file_exists($directory)) {
			Logger::log('Path "' . $directory . '" does not exist for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		if (is_file($directory)) {
			Logger::log('Path "' . $directory . '" is a file for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		if (!is_dir($directory)) {
			Logger::log('Path "' . $directory . '" is not a directory for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		if ($check_writable && !is_writable($directory)) {
			Logger::log('Path "' . $directory . '" is not writable for user ' . static::getUser(), Logger::DEBUG);
			return false;
		}

		return true;
	}

	/// @todo Move the following functions from boot.php
	/*
	function killme()
	function local_user()
	function public_contact()
	function remote_user()
	function notice($s)
	function info($s)
	function is_site_admin()
	function get_server()
	function get_temppath()
	function get_cachefile($file, $writemode = true)
	function get_itemcachepath()
	function get_spoolpath()
	*/
}
