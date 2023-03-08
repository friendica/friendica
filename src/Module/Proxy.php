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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPException\NotModifiedException;
use Friendica\Object\Image;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Images;
use Friendica\Util\Proxy as ProxyUtils;

/**
 * Module Proxy
 *
 * urls:
 * /proxy/[sub1/[sub2/]]<base64url image url>[.ext][:size]
 * /proxy?url=<image url>
 */
class Proxy extends BaseModule
{

	/**
	 * Fetch remote image content
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequestInfo();

		if (!DI::config()->get('system', 'proxify_content')) {
			Logger::notice('Proxy access is forbidden', ['request' => $request, 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'accept' => $_SERVER['HTTP_ACCEPT'] ?? '']);
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
			if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
				header('Etag: ' . $_SERVER['HTTP_IF_NONE_MATCH']);
			}
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
			header('Cache-Control: max-age=31536000');
			if (function_exists('header_remove')) {
				header_remove('Last-Modified');
				header_remove('Expires');
				header_remove('Cache-Control');
			}
			throw new NotModifiedException();
		}

		if (empty($request['url'])) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		if (!DI::userSession()->getLocalUserId()) {
			Logger::debug('Redirecting not logged in user to original address', ['url' => $request['url']]);
			System::externalRedirect($request['url']);
		}

		// It shouldn't happen but it does - spaces in URL
		$request['url'] = str_replace(' ', '+', $request['url']);

		// Fetch the content with the local user
		try {
			$fetchResult = HTTPSignature::fetchRaw($request['url'], DI::userSession()->getLocalUserId(), [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::IMAGE], 'timeout' => 10]);
			$img_str = $fetchResult->getBody();

			if (!$fetchResult->isSuccess() || empty($img_str)) {
				Logger::notice('Error fetching image', ['image' => $request['url'], 'return' => $fetchResult->getReturnCode(), 'empty' => empty($img_str)]);
				self::responseError();
				// stop.
			}
		} catch (\Exception $exception) {
			Logger::notice('Error fetching image', ['image' => $request['url'], 'exception' => $exception]);
			self::responseError();
		}

		Logger::debug('Got picture', ['Content-Type' => $fetchResult->getHeader('Content-Type'), 'uid' => DI::userSession()->getLocalUserId(), 'image' => $request['url']]);

		$mime = Images::getMimeTypeByData($img_str);

		$image = new Image($img_str, $mime);
		if (!$image->isValid()) {
			Logger::notice('The image is invalid', ['image' => $request['url'], 'mime' => $mime]);
			self::responseError();
			// stop.
		}

		// reduce quality - if it isn't a GIF
		if ($image->getType() != 'image/gif') {
			$image->scaleDown($request['size']);
		}

		self::responseImageHttpCache($image);
		// stop.
	}

	/**
	 * Build info about requested image to be proxied
	 *
	 * @return array
	 *    [
	 *      'url' => requested url,
	 *      'size' => requested image size (int)
	 *      'sizetype' => requested image size (string): ':micro', ':thumb', ':small', ':medium', ':large'
	 *    ]
	 * @throws \Exception
	 */
	private function getRequestInfo(): array
	{
		$size = ProxyUtils::PIXEL_LARGE;
		$sizetype = '';

		if (!empty($this->parameters['url']) && empty($_REQUEST['url'])) {
			$url = $this->parameters['url'];

			// thumb, small, medium and large.
			if (substr($url, -6) == ':micro') {
				$size = ProxyUtils::PIXEL_MICRO;
				$sizetype = ':micro';
				$url = substr($url, 0, -6);
			} elseif (substr($url, -6) == ':thumb') {
				$size = ProxyUtils::PIXEL_THUMB;
				$sizetype = ':thumb';
				$url = substr($url, 0, -6);
			} elseif (substr($url, -6) == ':small') {
				$size = ProxyUtils::PIXEL_SMALL;
				$url = substr($url, 0, -6);
				$sizetype = ':small';
			} elseif (substr($url, -7) == ':medium') {
				$size = ProxyUtils::PIXEL_MEDIUM;
				$url = substr($url, 0, -7);
				$sizetype = ':medium';
			} elseif (substr($url, -6) == ':large') {
				$size = ProxyUtils::PIXEL_LARGE;
				$url = substr($url, 0, -6);
				$sizetype = ':large';
			}

			$pos = strrpos($url, '=.');
			if ($pos) {
				$url = substr($url, 0, $pos + 1);
			}

			$url = str_replace(['.jpg', '.jpeg', '.gif', '.png'], ['','','',''], $url);

			$url = base64_decode(strtr($url, '-_', '+/'), true);
		} else {
			$url = $_REQUEST['url'] ?? '';
		}

		return [
			'url' => $url,
			'size' => $size,
			'sizetype' => $sizetype,
		];
	}

	/**
	 * In case of an error just stop. We don't return content to avoid caching problems
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function responseError()
	{
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}

	/**
	 * Output the image with cache headers
	 *
	 * @param Image $img
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function responseImageHttpCache(Image $img)
	{
		if (is_null($img) || !$img->isValid()) {
			Logger::notice('The cached image is invalid');
			self::responseError();
			// stop.
		}
		header('Content-type: ' . $img->getType());
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
		header('Etag: "' . md5($img->asString()) . '"');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (31536000)) . ' GMT');
		header('Cache-Control: max-age=31536000');
		echo $img->asString();
		System::exit();
	}
}
