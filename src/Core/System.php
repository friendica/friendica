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
		$ignore = ['fetchUrl'];

		while ($func = array_pop($trace)) {
			if (!empty($func['class'])) {
				// Don't show multiple calls from the same function (mostly used for "dba" class)
				if (($previous['class'] != $func['class']) && ($previous['function'] != 'q')) {
					$classparts = explode("\\", $func['class']);
					$callstack[] = array_pop($classparts).'::'.$func['function'];
					$previous = $func;
				}
			} elseif (!in_array($func['function'], $ignore)) {
				$callstack[] = $func['function'];
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
	 * @brief Called from db initialisation when db is dead.
	 */
	static public function unavailable() {
echo <<< EOT
<html>
	<head><title>System Unavailable</title></head>
	<body>Apologies but this site is unavailable at the moment. Please try again later.</body>
</html>
EOT;

		killme();
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
			echo replace_macros(
				$tpl,
				[
					'$title' => $description["title"],
					'$description' => $description["description"]]
			);
		}

		killme();
	}

	/**
	 * @brief Encodes content to json
	 *
	 * This function encodes an array to json format
	 * and adds an application/json HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @param array $x The input content
	 */
	public static function jsonExit($x)
	{
		header("content-type: application/json");
		echo json_encode($x);
		killme();
	}

	/// @todo Move the following functions from boot.php
	/*
	function get_guid($size = 16, $prefix = "")
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
