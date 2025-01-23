<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Content\Text\BBCode;
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
	const SIZE_SMALL  = 'small'; // 320
	const SIZE_MEDIUM = 'medium'; // 640
	const SIZE_LARGE  = 'large'; // 1024

	/**
	 * Pixel Sizes
	 */
	const PIXEL_MICRO  = 48;
	const PIXEL_THUMB  = 80;
	const PIXEL_SMALL  = 320;
	const PIXEL_MEDIUM = 640;
	const PIXEL_LARGE  = 1024;

	/**
	 * Private constructor
	 */
	private function __construct()
	{
		// No instances from utilities classes
	}

	/**
	 * "Proxifies" HTML code's image tags
	 *
	 * "Proxifies", means replaces image URLs in given HTML code with those from
	 * proxy storage directory.
	 *
	 * @param string $html Un-proxified HTML code
	 * @param int $uriid
	 *
	 * @return string Proxified HTML code
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function proxifyHtml(string $html, int $uriid): string
	{
		$html = str_replace(Strings::normaliseLink(DI::baseUrl()) . '/', DI::baseUrl() . '/', $html);

		if (!preg_match_all('/(<img [^>]*src *= *["\'])([^"\']+)(["\'][^>]*>)/siU', $html, $matches, PREG_SET_ORDER)) {
			return $html;
		}

		foreach ($matches as $match) {
			$html = str_replace($match[0], self::replaceUrl($match, $uriid), $html);
		}

		return $html;
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

		return DI::baseUrl()->isLocalUrl($url);
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
	private static function replaceUrl(array $matches, int $uriid): string
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
		return $matches[1] . BBCode::proxyUrl(htmlspecialchars_decode($matches[2]), BBCode::INTERNAL, $uriid, Proxy::SIZE_MEDIUM) . $matches[3];
	}

	public static function getPixelsFromSize(string $size): int
	{
		switch ($size) {
			case Proxy::SIZE_MICRO:
				return Proxy::PIXEL_MICRO;
			case Proxy::SIZE_THUMB:
				return Proxy::PIXEL_THUMB;
			case Proxy::SIZE_SMALL:
				return Proxy::PIXEL_SMALL;
			case Proxy::SIZE_MEDIUM:
				return Proxy::PIXEL_MEDIUM;
			case Proxy::SIZE_LARGE:
				return Proxy::PIXEL_LARGE;
			default:
				return 0;
		}
	}
}
