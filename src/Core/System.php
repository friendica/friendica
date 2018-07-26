<?php
/**
 * @file src/Core/System.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
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
	 */
	public static function baseUrl($ssl = false)
	{
		return self::getApp()->get_baseurl($ssl);
	}

	/**
	 * @brief Removes the baseurl from an url. This avoids some mixed content problems.
	 *
	 * @param string $orig_url The url to be cleaned
	 *
	 * @return string The cleaned url
	 */
	public static function removedBaseUrl($orig_url)
	{
		return self::getApp()->remove_baseurl($orig_url);
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
		$counter = 0;
		$previous = ['class' => '', 'function' => ''];

		// The ignore list contains all functions that are only wrapper functions
		$ignore = ['fetchUrl', 'call_user_func_array'];

		while ($func = array_pop($trace)) {
			if (!empty($func['class'])) {
				// Don't show multiple calls from the "dba" class to show the essential parts of the callstack
				if ((($previous['class'] != $func['class']) || ($func['class'] != 'dba')) && ($previous['function'] != 'q')) {
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
	 */
	public static function xmlExit($st, $message = '')
	{
		$result = ['status' => $st];

		if ($message != '') {
			$result['message'] = $message;
		}

		if ($st) {
			logger('xml_status returning non_zero: ' . $st . " message=" . $message);
		}

		header("Content-type: text/xml");

		$xmldata = ["result" => $result];

		echo XML::fromArray($xmldata, $xml);

		killme();
	}

	/**
	 * @brief Send HTTP status header and exit.
	 *
	 * @param integer $val         HTTP status result value
	 * @param array   $description optional message
	 *                             'title' => header title
	 *                             'description' => optional message
	 */
	public static function httpExit($val, $description = [])
	{
		$err = '';
		if ($val >= 400) {
			$err = 'Error';
			if (!isset($description["title"])) {
				$description["title"] = $err." ".$val;
			}
		}

		if ($val >= 200 && $val < 300) {
			$err = 'OK';
		}

		logger('http_status_exit ' . $val);
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);

		if (isset($description["title"])) {
			$tpl = get_markup_template('http_status.tpl');
			echo replace_macros($tpl, ['$title' => $description["title"],
				'$description' => defaults($description, 'description', '')]);
		}

		killme();
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
		killme();
	}

	/**
	 * Generates a GUID with the given parameters
	 *
	 * @param int          $size     The size of the GUID (default is 16)
	 * @param bool|string  $prefix   A given prefix (default is empty)
	 * @return string a generated GUID
	 */
	public static function createGUID($size = 16, $prefix = '')
	{
		if (is_bool($prefix) && !$prefix) {
			$prefix = '';
		} elseif (empty($prefix)) {
			$prefix = hash('crc32', self::getApp()->get_hostname());
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
	 * Generates a process identifier for the logging
	 *
	 * @param string $prefix A given prefix
	 *
	 * @return string a generated process identifier
	 */
	public static function processID($prefix)
	{
		// We aren't calling any other function here.
		// Doing so could easily create an endless loop
		$trailer = $prefix . ':' . getmypid() . ':';
		return substr($trailer . uniqid('') . mt_rand(), 0, 26);
	}

	/// @todo Move the following functions from boot.php
	/*
	function killme()
	function goaway($s)
	function local_user()
	function public_contact()
	function remote_user()
	function notice($s)
	function info($s)
	function is_site_admin()
	function random_digits($digits)
	function get_server()
	function get_temppath()
	function get_cachefile($file, $writemode = true)
	function get_itemcachepath()
	function get_spoolpath()
	function current_load()
	*/
}
