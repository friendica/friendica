<?php

namespace Friendica\Util;

use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\DI;

/**
 * @brief Proxy utilities class
 */
class Proxy
{

	/**
	 * Default time to keep images in proxy storage
	 */
	const DEFAULT_TIME = 86400; // 1 Day

	/**
	 * Sizes constants
	 */
	const SIZE_MICRO  = 'micro';
	const SIZE_THUMB  = 'thumb';
	const SIZE_SMALL  = 'small';
	const SIZE_MEDIUM = 'medium';
	const SIZE_LARGE  = 'large';

	/**
	 * Accepted extensions
	 *
	 * @var array
	 * @todo Make this configurable?
	 */
	private static $extensions = [
		'jpg',
		'jpeg',
		'gif',
		'png',
	];

	/**
	 * @brief Private constructor
	 */
	private function __construct () {
		// No instances from utilities classes
	}

	/**
	 * @brief Transform a remote URL into a local one.
	 *
	 * This function only performs the URL replacement on http URL and if the
	 * provided URL isn't local, "the isn't deactivated" (sic) and if the config
	 * system.proxy_disabled is set to false.
	 *
	 * @param string $url       The URL to proxyfy
	 * @param bool   $writemode Returns a local path the remote URL should be saved to
	 * @param string $size      One of the ProxyUtils::SIZE_* constants
	 *
	 * @return string The proxyfied URL or relative path
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function proxifyUrl($url, $writemode = false, $size = '')
	{
		// Get application instance
		$a = DI::app();

		// Trim URL first
		$url = trim($url);

		// Is no http in front of it?
		/// @TODO To weak test for being a valid URL
		if (substr($url, 0, 4) !== 'http') {
			return $url;
		}

		// Only continue if it isn't a local image and the isn't deactivated
		if (self::isLocalImage($url)) {
			$url = str_replace(Strings::normaliseLink(System::baseUrl()) . '/', System::baseUrl() . '/', $url);
			return $url;
		}

		// Is the proxy disabled?
		if (Config::get('system', 'proxy_disabled')) {
			return $url;
		}

		// Image URL may have encoded ampersands for display which aren't desirable for proxy
		$url = html_entity_decode($url, ENT_NOQUOTES, 'utf-8');

		// Creating a sub directory to reduce the amount of files in the cache directory
		$basepath = $a->getBasePath() . '/proxy';

		$shortpath = hash('md5', $url);
		$longpath = substr($shortpath, 0, 2);

		if (is_dir($basepath) && $writemode && !is_dir($basepath . '/' . $longpath)) {
			mkdir($basepath . '/' . $longpath);
			chmod($basepath . '/' . $longpath, 0777);
		}

		$longpath .= '/' . strtr(base64_encode($url), '+/', '-_');

		// Extract the URL extension
		$extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

		if (in_array($extension, self::$extensions)) {
			$shortpath .= '.' . $extension;
			$longpath .= '.' . $extension;
		}

		$proxypath = System::baseUrl() . '/proxy/' . $longpath;

		if ($size != '') {
			$size = ':' . $size;
		}

		// Too long files aren't supported by Apache
		// Writemode in combination with long files shouldn't be possible
		if ((strlen($proxypath) > 250) && $writemode) {
			return $shortpath;
		} elseif (strlen($proxypath) > 250) {
			return System::baseUrl() . '/proxy/' . $shortpath . '?url=' . urlencode($url);
		} elseif ($writemode) {
			return $longpath;
		} else {
			return $proxypath . $size;
		}
	}

	/**
	 * @brief "Proxifies" HTML code's image tags
	 *
	 * "Proxifies", means replaces image URLs in given HTML code with those from
	 * proxy storage directory.
	 *
	 * @param string $html Un-proxified HTML code
	 *
	 * @return string Proxified HTML code
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function proxifyHtml($html)
	{
		$html = str_replace(Strings::normaliseLink(System::baseUrl()) . '/', System::baseUrl() . '/', $html);

		return preg_replace_callback('/(<img [^>]*src *= *["\'])([^"\']+)(["\'][^>]*>)/siU', 'self::replaceUrl', $html);
	}

	/**
	 * @brief Checks if the URL is a local URL.
	 *
	 * @param string $url
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function isLocalImage($url)
	{
		if (substr($url, 0, 1) == '/') {
			return true;
		}

		if (strtolower(substr($url, 0, 5)) == 'data:') {
			return true;
		}

		// links normalised - bug #431
		$baseurl = Strings::normaliseLink(System::baseUrl());
		$url = Strings::normaliseLink($url);

		return (substr($url, 0, strlen($baseurl)) == $baseurl);
	}

	/**
	 * @brief Return the array of query string parameters from a URL
	 *
	 * @param string $url URL to parse
	 * @return array Associative array of query string parameters
	 */
	private static function parseQuery($url)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		$query = html_entity_decode($query);

		parse_str($query, $arr);

		return $arr;
	}

	/**
	 * @brief Call-back method to replace the UR
	 *
	 * @param array $matches Matches from preg_replace_callback()
	 * @return string Proxified HTML image tag
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function replaceUrl(array $matches)
	{
		// if the picture seems to be from another picture cache then take the original source
		$queryvar = self::parseQuery($matches[2]);

		if (!empty($queryvar['url']) && substr($queryvar['url'], 0, 4) == 'http') {
			$matches[2] = urldecode($queryvar['url']);
		}

		// Following line changed per bug #431
		if (self::isLocalImage($matches[2])) {
			return $matches[1] . $matches[2] . $matches[3];
		}

		// Return proxified HTML
		return $matches[1] . self::proxifyUrl(htmlspecialchars_decode($matches[2])) . $matches[3];
	}

}
