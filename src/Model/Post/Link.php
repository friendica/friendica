<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Model\Post;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Images;
use Friendica\Util\Proxy;
use Friendica\Object\Image;

/**
 * Class Link
 *
 * This Model class handles post related external links
 */
class Link
{
	/**
	 * Check if the link is stored
	 *
	 * @param int $uriId
	 * @param string $url URL
	 * @return bool Whether record has been found
	 */
	public static function exists(int $uriId, string $url): bool
	{
		return DBA::exists('post-link', ['uri-id' => $uriId, 'url' => $url]);
	}

	/**
	 * Returns URL by URI id and other URL
	 *
	 * @param int $uriId
	 * @param string $url
	 * @param string $size
	 * @return string Found link URL + id on success, $url on failture
	 */
	public static function getByLink(int $uriId, string $url, string $size = ''): string
	{
		if (empty($uriId) || empty($url) || Proxy::isLocalImage($url)) {
			return $url;
		}

		if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) {
			Logger::info('Bad URL, quitting', ['uri-id' => $uriId, 'url' => $url, 'callstack' => System::callstack(20)]);
			return $url;
		}

		$link = DBA::selectFirst('post-link', ['id'], ['uri-id' => $uriId, 'url' => $url]);
		if (!empty($link['id'])) {
			$id = $link['id'];
			Logger::info('Found', ['id' => $id, 'uri-id' => $uriId, 'url' => $url]);
		} else {
			$fields = self::fetchMimeType($url);
			$fields['uri-id'] = $uriId;
			$fields['url'] = $url;

			DBA::insert('post-link', $fields, Database::INSERT_IGNORE);
			$id = DBA::lastInsertId();
			Logger::info('Inserted', $fields);
		}

		if (empty($id)) {
			return $url;
		}

		$url = DI::baseUrl() . '/photo/link/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;

			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;

			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;

			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;

			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}
		return $url . $id;
	}

	/**
	 * Fetches MIME type by URL and Accept: header
	 *
	 * @param string $url URL to fetch
	 * @param string $accept Comma-separated list of expected response MIME type(s)
	 * @return array Discovered MIME type and blurhash or empty array on failure
	 */
	private static function fetchMimeType(string $url, string $accept = HttpClientAccept::DEFAULT): array
	{
		$timeout = DI::config()->get('system', 'xrd_timeout');

		try {
			$curlResult = HTTPSignature::fetchRaw($url, 0, [HttpClientOptions::TIMEOUT => $timeout, HttpClientOptions::ACCEPT_CONTENT => $accept]);
			if (empty($curlResult) || !$curlResult->isSuccess()) {
				return [];
			}
		} catch (\Exception $exception) {
			Logger::notice('Error fetching url', ['url' => $url, 'exception' => $exception]);
			return [];
		}
		$fields = ['mimetype' => $curlResult->getHeader('Content-Type')[0]];

		$img_str = $curlResult->getBody();
		$image = new Image($img_str, Images::getMimeTypeByData($img_str));
		if ($image->isValid()) {
			$fields['mimetype'] = $image->getType();
			$fields['width']    = $image->getWidth();
			$fields['height']   = $image->getHeight();
			$fields['blurhash'] = $image->getBlurHash();
		}

		return $fields;
	}

	/**
	 * Add external links and replace them in the body
	 *
	 * @param integer $uriId
	 * @param string $body Item body formatted with BBCodes
	 * @return string Body with replaced links
	 */
	public static function insertFromBody(int $uriId, string $body): string
	{
		if (preg_match_all("/\[img\=([0-9]*)x([0-9]*)\](http.*?)\[\/img\]/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[3], self::getByLink($uriId, $picture[3]), $body);
			}
		}

		if (preg_match_all("/\[img=(http[^\[\]]*)\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[1], self::getByLink($uriId, $picture[1]), $body);
			}
		}

		if (preg_match_all("/\[img\](http[^\[\]]*)\[\/img\]/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[1], self::getByLink($uriId, $picture[1]), $body);
			}
		}

		return trim($body);
	}
}
