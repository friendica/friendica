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

namespace Friendica\Util;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Object\Image;

/**
 * Image utilities
 */
class Images
{
	/**
	 * Maps Mime types to Imagick formats
	 *
	 * @return array Format map
	 */
	public static function getFormatsMap()
	{
		return [
			'image/jpeg' => 'JPG',
			'image/jpg' => 'JPG',
			'image/png' => 'PNG',
			'image/gif' => 'GIF',
		];
	}

	/**
	 * Return file extension for MIME type
	 *
	 * @param string $mimetype MIME type
	 * @return string File extension for MIME type
	 */
	public static function getExtensionByMimeType(string $mimetype): string
	{
		switch ($mimetype) {
			case 'image/png':
				$imagetype = IMAGETYPE_PNG;
				break;

			case 'image/gif':
				$imagetype = IMAGETYPE_GIF;
				break;

			case 'image/jpeg':
			case 'image/jpg':
				$imagetype = IMAGETYPE_JPEG;
				break;

			default: // Unknown type must be a blob then
				return 'blob';
				break;
		}

		return image_type_to_extension($imagetype);
	}

	/**
	 * Returns supported image mimetypes and corresponding file extensions
	 *
	 * @return array
	 */
	public static function supportedTypes(): array
	{
		$types = [
			'image/jpeg' => 'jpg',
			'image/jpg' => 'jpg',
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
	 * @param string $default    Default MIME type
	 * @return string MIME type
	 * @throws \Exception
	 */
	public static function getMimeTypeByData(string $image_data, string $filename = '', string $default = ''): string
	{
		if (substr($default, 0, 6) == 'image/') {
			Logger::info('Using default mime type', ['filename' => $filename, 'mime' => $default]);
			return $default;
		}

		$image = @getimagesizefromstring($image_data);
		if (!empty($image['mime'])) {
			Logger::info('Mime type detected via data', ['filename' => $filename, 'default' => $default, 'mime' => $image['mime']]);
			return $image['mime'];
		}

		return self::guessTypeByExtension($filename);
	}

	/**
	 * Fetch image mimetype from the image data or guessing from the file name
	 *
	 * @param string $sourcefile Source file of the image
	 * @param string $filename   File name (for guessing the type via the extension)
	 * @param string $default    default MIME type
	 * @return string MIME type
	 * @throws \Exception
	 */
	public static function getMimeTypeBySource(string $sourcefile, string $filename = '', string $default = ''): string
	{
		if (substr($default, 0, 6) == 'image/') {
			Logger::info('Using default mime type', ['filename' => $filename, 'mime' => $default]);
			return $default;
		}

		$image = @getimagesize($sourcefile);
		if (!empty($image['mime'])) {
			Logger::info('Mime type detected via file', ['filename' => $filename, 'default' => $default, 'image' => $image]);
			return $image['mime'];
		}

		return self::guessTypeByExtension($filename);
	}

	/**
	 * Guess image MIME type from the filename's extension
	 *
	 * @param string $filename Image filename
	 * @return string Guessed MIME type by extension
	 * @throws \Exception
	 */
	public static function guessTypeByExtension(string $filename): string
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
	 * Gets info array from given URL, cached data has priority
	 *
	 * @param string $url
	 * @return array Info
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getInfoFromURLCached(string $url): array
	{
		$data = [];

		if (empty($url)) {
			return $data;
		}

		$cacheKey = 'getInfoFromURL:' . sha1($url);

		$data = DI::cache()->get($cacheKey);

		if (empty($data) || !is_array($data)) {
			$data = self::getInfoFromURL($url);

			DI::cache()->set($cacheKey, $data);
		}

		return $data ?? [];
	}

	/**
	 * Gets info from URL uncached
	 *
	 * @param string $url
	 * @return array Info array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getInfoFromURL(string $url): array
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
			$img_str = DI::httpClient()->fetch($url, HttpClientAccept::IMAGE, 4);
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
			$image = new Image($img_str);

			if ($image->isValid()) {
				$data['blurhash'] = $image->getBlurHash();
			}

			$data['size'] = $filesize;
		}

		return is_array($data) ? $data : [];
	}

	/**
	 * Returns scaling information
	 *
	 * @param integer $width Width
	 * @param integer $height Height
	 * @param integer $max Max width/height
	 * @return array Scaling dimensions
	 */
	public static function getScalingDimensions(int $width, int $height, int $max): array
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
