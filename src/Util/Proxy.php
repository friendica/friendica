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

namespace Friendica\Util;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use GuzzleHttp\Psr7\Uri;

/**
 * Proxy utilities class
 */
class Proxy
{
	/**
	 * Sizes constants
	 */
	const SIZE_MICRO  = 'micro'; // 48
	const SIZE_THUMB  = 'thumb'; // 80
	const SIZE_SMALL  = 'small'; // 300
	const SIZE_MEDIUM = 'medium'; // 600
	const SIZE_LARGE  = 'large'; // 1024

	/**
	 * Pixel Sizes
	 */
	const PIXEL_MICRO  = 48;
	const PIXEL_THUMB  = 80;
	const PIXEL_SMALL  = 300;
	const PIXEL_MEDIUM = 600;
	const PIXEL_LARGE  = 1024;

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
	 * Private constructor
	 */
	private function __construct () {
		// No instances from utilities classes
	}

	/**
	 * Transform a remote URL into a local one.
	 *
	 * This function only performs the URL replacement on http URL and if the
	 * provided URL isn't local
	 *
	 * @param string $url       The URL to proxyfy
	 * @param string $size      One of the Proxy::SIZE_* constants
	 * @return string The proxyfied URL or relative path
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function proxifyUrl(string $url, string $size = ''): string
	{
		if (!DI::config()->get('system', 'proxify_content')) {
			return $url;
		}

		// Trim URL first
		$url = trim($url);

		// Quit if not an HTTP/HTTPS link or if local
		if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https']) || self::isLocalImage($url)) {
			return $url;
		}

		// Image URL may have encoded ampersands for display which aren't desirable for proxy
		$url = html_entity_decode($url, ENT_NOQUOTES, 'utf-8');

		$shortpath = hash('md5', $url);
		$longpath = substr($shortpath, 0, 2);

		$longpath .= '/' . strtr(base64_encode($url), '+/', '-_');

		// Extract the URL extension
		$extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

		if (in_array($extension, self::$extensions)) {
			$shortpath .= '.' . $extension;
			$longpath .= '.' . $extension;
		}

		$proxypath = DI::baseUrl() . '/proxy/' . $longpath;

		if ($size != '') {
			$size = ':' . $size;
		}

		Logger::info('Created proxy link', ['url' => $url, 'callstack' => System::callstack(20)]);

		// Too long files aren't supported by Apache
		if (strlen($proxypath) > 250) {
			return DI::baseUrl() . '/proxy/' . $shortpath . '?url=' . urlencode($url);
		} else {
			return $proxypath . $size;
		}
	}

	/**
	 * "Proxifies" HTML code's image tags
	 *
	 * "Proxifies", means replaces image URLs in given HTML code with those from
	 * proxy storage directory.
	 *
	 * @param string $html Un-proxified HTML code
	 *
	 * @return string Proxified HTML code
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function proxifyHtml(string $html): string
	{
		$html = str_replace(Strings::normaliseLink(DI::baseUrl()) . '/', DI::baseUrl() . '/', $html);

		return preg_replace_callback('/(<img [^>]*src *= *["\'])([^"\']+)(["\'][^>]*>)/siU', 'self::replaceUrl', $html);
	}

	/**
	 * Checks if the URL is a local URL.
	 *
	 * @param string $url
	 *
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isLocalImage(string $url): bool
	{
		if (substr($url, 0, 1) == '/') {
			return true;
		}

		if (strtolower(substr($url, 0, 5)) == 'data:') {
			return true;
		}

		return Network::isLocalLink($url);
	}

	/**
	 * Return the array of query string parameters from a URL
	 *
	 * @param string $url URL to parse
	 *
	 * @return array Associative array of query string parameters
	 */
	private static function parseQuery(string $url): array
	{
		try {
			$uri = new Uri($url);

			parse_str($uri->getQuery(), $arr);

			return $arr;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Call-back method to replace the UR
	 *
	 * @param array $matches Matches from preg_replace_callback()
	 *
	 * @return string Proxified HTML image tag
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function replaceUrl(array $matches): string
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
