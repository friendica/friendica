<?php
/**
 * @file src/Core/System.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
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
	 * @param bool $ssl Whether to append http or https under SSL_POLICY_SELFSIGN
	 * @return string Friendica server base URL
	 * @throws InternalServerErrorException
	 */
	public static function baseUrl($ssl = false)
	{
		return self::getApp()->getBaseURL($ssl);
	}

	/**
	 * @brief Removes the baseurl from an url. This avoids some mixed content problems.
	 *
	 * @param string $orig_url The url to be cleaned
	 *
	 * @return string The cleaned url
	 * @throws \Exception
	 */
	public static function removedBaseUrl($orig_url)
	{
		return self::getApp()->removeBaseURL($orig_url);
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
	 * @param integer $val         HTTP status result value
	 * @param array   $description optional message
	 *                             'title' => header title
	 *                             'description' => optional message
	 * @throws InternalServerErrorException
	 */
	public static function httpExit($val, $description = [])
	{
		$err = '';
		if ($val >= 400) {
			if (!empty($description['title'])) {
				$err = $description['title'];
			} else {
				$title = [
					'400' => L10n::t('Error 400 - Bad Request'),
					'401' => L10n::t('Error 401 - Unauthorized'),
					'403' => L10n::t('Error 403 - Forbidden'),
					'404' => L10n::t('Error 404 - Not Found'),
					'500' => L10n::t('Error 500 - Internal Server Error'),
					'503' => L10n::t('Error 503 - Service Unavailable'),
					];
				$err = defaults($title, $val, 'Error ' . $val);
				$description['title'] = $err;
			}
			if (empty($description['description'])) {
				// Explanations are taken from https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
				$explanation = [
					'400' => L10n::t('The server cannot or will not process the request due to an apparent client error.'),
					'401' => L10n::t('Authentication is required and has failed or has not yet been provided.'),
					'403' => L10n::t('The request was valid, but the server is refusing action. The user might not have the necessary permissions for a resource, or may need an account.'),
					'404' => L10n::t('The requested resource could not be found but may be available in the future.'),
					'500' => L10n::t('An unexpected condition was encountered and no more specific message is suitable.'),
					'503' => L10n::t('The server is currently unavailable (because it is overloaded or down for maintenance). Please try again later.'),
					];
				if (!empty($explanation[$val])) {
					$description['description'] = $explanation[$val];
				}
			}
		}

		if ($val >= 200 && $val < 300) {
			$err = 'OK';
		}

		Logger::log('http_status_exit ' . $val);
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);

		if (isset($description["title"])) {
			$tpl = Renderer::getMarkupTemplate('http_status.tpl');
			echo Renderer::replaceMacros($tpl, ['$title' => $description["title"],
				'$description' => defaults($description, 'description', '')]);
		}

		exit();
	}

	/**
	 * @brief Encodes content to json.
	 *
	 * This function encodes an array to json format
	 * and adds an application/json HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param array  $x The input content.
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
			$prefix = hash('crc32', self::getApp()->getHostName());
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
	 * @param string $url The new Location to redirect
	 * @throws InternalServerErrorException If the URL is not fully qualified
	 */
	public static function externalRedirect($url)
	{
		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			throw new InternalServerErrorException("'$url' is not a fully qualified URL, please use App->internalRedirect() instead");
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
