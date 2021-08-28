<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Network\HTTPClientOptions;
use Friendica\Util\Proxy;

/**
 * Class Link
 *
 * This Model class handles post related external links
 */
class Link
{
	public static function getByLink(int $uri_id, string $url, $size = '')
	{
		if (empty($uri_id) || empty($url) || Proxy::isLocalImage($url)) {
			return $url;
		}

		if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) {
			Logger::info('Bad URL, quitting', ['uri-id' => $uri_id, 'url' => $url, 'callstack' => System::callstack(20)]);
			return $url;
		}

		$link = DBA::selectFirst('post-link', ['id'], ['uri-id' => $uri_id, 'url' => $url]);
		if (!empty($link['id'])) {
			$id = $link['id'];
			Logger::info('Found', ['id' => $id, 'uri-id' => $uri_id, 'url' => $url]);
		} else {
			$mime = self::fetchMimeType($url);

			DBA::insert('post-link', ['uri-id' => $uri_id, 'url' => $url, 'mimetype' => $mime], Database::INSERT_IGNORE);
			$id = DBA::lastInsertId();
			Logger::info('Inserted', ['id' => $id, 'uri-id' => $uri_id, 'url' => $url]);
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

	private static function fetchMimeType(string $url)
	{
		$timeout = DI::config()->get('system', 'xrd_timeout');

		$curlResult = DI::httpClient()->head($url, [HTTPClientOptions::TIMEOUT => $timeout]);
		if ($curlResult->isSuccess()) {
			if (empty($media['mimetype'])) {
				return $curlResult->getHeader('Content-Type')[0] ?? '';
			}
		}
		return '';
	}

	/**
	 * Add external links and replace them in the body
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return string Body with replaced links
	 */
	public static function insertFromBody(int $uriid, string $body)
	{
		if (preg_match_all("/\[img\=([0-9]*)x([0-9]*)\](http.*?)\[\/img\]/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[3], self::getByLink($uriid, $picture[3]), $body);
			}
		}

		if (preg_match_all("/\[img=(http[^\[\]]*)\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[1], self::getByLink($uriid, $picture[1]), $body);
			}
		}

		if (preg_match_all("/\[img\](http[^\[\]]*)\[\/img\]/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[1], self::getByLink($uriid, $picture[1]), $body);
			}
		}

		return trim($body);
	}
}
