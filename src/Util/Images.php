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

namespace Friendica\Util;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Photo;

/**
 * Image utilities
 */
class Images
{
	/**
	 * Maps Mime types to Imagick formats
	 *
	 * @return array
	 */
	public static function getFormatsMap()
	{
		$m = [
			'image/jpeg' => 'JPG',
			'image/png' => 'PNG',
			'image/gif' => 'GIF'
		];

		return $m;
	}

	/**
	 * Returns supported image mimetypes and corresponding file extensions
	 *
	 * @return array
	 */
	public static function supportedTypes()
	{
		$types = [
			'image/jpeg' => 'jpg'
		];
		if (class_exists('Imagick')) {
			// Imagick::queryFormats won't help us a lot there...
			// At least, not yet, other parts of friendica uses this array
			$types += [
				'image/png' => 'png',
				'image/gif' => 'gif'
			];
		} elseif (imagetypes() & IMG_PNG) {
			$types += [
				'image/png' => 'png'
			];
		}

		return $types;
	}

	/**
	 * Fetch image mimetype from the image data or guessing from the file name
	 *
	 * @param string $image_data Image data
	 * @param string $filename   File name (for guessing the type via the extension)
	 * @param string $mime       default mime type
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function getMimeTypeByData(string $image_data, string $filename = '', string $mime = '')
	{
		if (substr($mime, 0, 6) == 'image/') {
			Logger::info('Using default mime type', ['filename' => $filename, 'mime' => $mime]);
			return $mime;
		}

		$image = @getimagesizefromstring($image_data);
		if (!empty($image['mime'])) {
			Logger::info('Mime type detected via data', ['filename' => $filename, 'default' => $mime, 'mime' => $image['mime']]);
			return $image['mime'];
		}

		return self::guessTypeByExtension($filename);
	}

	/**
	 * Fetch image mimetype from the image data or guessing from the file name
	 *
	 * @param string $sourcefile Source file of the image
	 * @param string $filename   File name (for guessing the type via the extension)
	 * @param string $mime       default mime type
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function getMimeTypeBySource(string $sourcefile, string $filename = '', string $mime = '')
	{
		if (substr($mime, 0, 6) == 'image/') {
			Logger::info('Using default mime type', ['filename' => $filename, 'mime' => $mime]);
			return $mime;
		}

		$image = @getimagesize($sourcefile);
		if (!empty($image['mime'])) {
			Logger::info('Mime type detected via file', ['filename' => $filename, 'default' => $mime, 'image' => $image]);
			return $image['mime'];
		}

		return self::guessTypeByExtension($filename);
	}

	/**
	 * Guess image mimetype from the filename
	 *
	 * @param string $filename   Image filename
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function guessTypeByExtension(string $filename)
	{
		$ext = pathinfo(parse_url($filename, PHP_URL_PATH), PATHINFO_EXTENSION);
		$types = self::supportedTypes();
		$type = 'image/jpeg';
		foreach ($types as $m => $e) {
			if ($ext == $e) {
				$type = $m;
			}
		}

		Logger::info('Mime type guessed via extension', ['filename' => $filename, 'type' => $type]);
		return $type;
	}

	/**
	 * @param string $url
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getInfoFromURLCached($url)
	{
		$data = [];

		if (empty($url)) {
			return $data;
		}

		$data = DI::cache()->get($url);

		if (empty($data) || !is_array($data)) {
			$data = self::getInfoFromURL($url);

			DI::cache()->set($url, $data);
		}

		return $data;
	}

	/**
	 * @param string $url
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getInfoFromURL($url)
	{
		$data = [];

		if (empty($url)) {
			return $data;
		}

		if (Network::isLocalLink($url) && ($data = Photo::getResourceData($url))) {
			$photo = Photo::selectFirst([], ['resource-id' => $data['guid'], 'scale' => $data['scale']]);
			if (!empty($photo)) {
				$img_str = Photo::getImageDataForPhoto($photo);
			}
			// @todo Possibly add a check for locally stored files
		}

		if (empty($img_str)) {
			$img_str = DI::httpClient()->fetch($url, 4);
		}

		if (!$img_str) {
			return [];
		}

		$filesize = strlen($img_str);

		try {
			$data = @getimagesizefromstring($img_str);
		} catch (\Exception $e) {
			return [];
		}

		if ($data) {
			$data['size'] = $filesize;
		}

		return $data;
	}

	/**
	 * @param integer $width
	 * @param integer $height
	 * @param integer $max
	 * @return array
	 */
	public static function getScalingDimensions($width, $height, $max)
	{
		if ((!$width) || (!$height)) {
			return ['width' => 0, 'height' => 0];
		}

		if ($width > $max && $height > $max) {
			// very tall image (greater than 16:9)
			// constrain the width - let the height float.

			if ((($height * 9) / 16) > $width) {
				$dest_width = $max;
				$dest_height = intval(($height * $max) / $width);
			} elseif ($width > $height) {
				// else constrain both dimensions
				$dest_width = $max;
				$dest_height = intval(($height * $max) / $width);
			} else {
				$dest_width = intval(($width * $max) / $height);
				$dest_height = $max;
			}
		} else {
			if ($width > $max) {
				$dest_width = $max;
				$dest_height = intval(($height * $max) / $width);
			} else {
				if ($height > $max) {
					// very tall image (greater than 16:9)
					// but width is OK - don't do anything

					if ((($height * 9) / 16) > $width) {
						$dest_width = $width;
						$dest_height = $height;
					} else {
						$dest_width = intval(($width * $max) / $height);
						$dest_height = $max;
					}
				} else {
					$dest_width = $width;
					$dest_height = $height;
				}
			}
		}

		return ['width' => $dest_width, 'height' => $dest_height];
	}
}
