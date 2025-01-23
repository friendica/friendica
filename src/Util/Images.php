<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Object\Image;

/**
 * Image utilities
 */
class Images
{
	// @todo add IMAGETYPE_AVIF once our minimal supported PHP version is 8.1.0
	const IMAGETYPES = [IMAGETYPE_WEBP, IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_BMP];

	/**
	 * Get the Imagick format for the given image type
	 *
	 * @param int $imagetype
	 * @return string
	 */
	public static function getImagickFormatByImageType(int $imagetype): string
	{
		$formats = [
			// @todo add "IMAGETYPE_AVIF => 'AVIF'" once our minimal supported PHP version is 8.1.0
			IMAGETYPE_WEBP => 'WEBP',
			IMAGETYPE_PNG  => 'PNG',
			IMAGETYPE_JPEG => 'JPEG',
			IMAGETYPE_GIF  => 'GIF',
			IMAGETYPE_BMP  => 'BMP',
		];

		if (empty($formats[$imagetype])) {
			return '';
		}

		return $formats[$imagetype];
	}

	/**
	 * Sanitize the provided mime type, replace invalid mime types with valid ones.
	 *
	 * @param string $mimetype
	 * @return string
	 */
	private static function sanitizeMimeType(string $mimetype): string
	{
		$mimetype = current(explode(';', $mimetype));

		if ($mimetype == 'image/jpg') {
			$mimetype = image_type_to_mime_type(IMAGETYPE_JPEG);
		} elseif (in_array($mimetype, ['image/vnd.mozilla.apng', 'image/apng'])) {
			$mimetype = image_type_to_mime_type(IMAGETYPE_PNG);
		} elseif (in_array($mimetype, ['image/x-ms-bmp', 'image/x-bmp'])) {
			$mimetype = image_type_to_mime_type(IMAGETYPE_BMP);
		}

		return $mimetype;
	}

	/**
	 * Replace invalid extensions with valid ones.
	 *
	 * @param string $extension
	 * @return string
	 */
	private static function sanitizeExtensions(string $extension): string
	{
		if (in_array($extension, ['jpg', 'jpe', 'jfif'])) {
			$extension = image_type_to_extension(IMAGETYPE_JPEG, false);
		} elseif ($extension == 'apng') {
			$extension = image_type_to_extension(IMAGETYPE_PNG, false);
		} elseif ($extension == 'dib') {
			$extension = image_type_to_extension(IMAGETYPE_BMP, false);
		}

		return $extension;
	}

	/**
	 * Get the image type for the given mime type
	 *
	 * @param string $mimetype
	 * @return integer
	 */
	public static function getImageTypeByMimeType(string $mimetype): int
	{
		$mimetype = self::sanitizeMimeType($mimetype);

		foreach (self::IMAGETYPES as $type) {
			if ($mimetype == image_type_to_mime_type($type)) {
				return $type;
			}
		}

		DI::logger()->debug('Undetected mimetype', ['mimetype' => $mimetype]);
		return 0;
	}

	/**
	 * Get the extension for the given image type
	 *
	 * @param integer $type
	 * @return string
	 */
	public static function getExtensionByImageType(int $type): string
	{
		if (empty($type)) {
			DI::logger()->debug('Invalid image type', ['type' => $type]);
			return '';
		}

		return image_type_to_extension($type);
	}

	/**
	 * Return file extension for MIME type
	 *
	 * @param string $mimetype MIME type
	 * @return string File extension for MIME type
	 */
	public static function getExtensionByMimeType(string $mimetype): string
	{
		if (empty($mimetype)) {
			return '';
		}

		return self::getExtensionByImageType(self::getImageTypeByMimeType($mimetype));
	}

	/**
	 * Returns supported image mimetypes
	 *
	 * @return array
	 */
	public static function supportedMimeTypes(): array
	{
		$types = [];

		// @todo enable, once our lowest supported PHP version is 8.1.0
		//if (imagetypes() & IMG_AVIF) {
		//	$types[] = image_type_to_mime_type(IMAGETYPE_AVIF);
		//}
		if (imagetypes() & IMG_WEBP) {
			$types[] = image_type_to_mime_type(IMAGETYPE_WEBP);
		}
		if (imagetypes() & IMG_PNG) {
			$types[] = image_type_to_mime_type(IMAGETYPE_PNG);
		}
		if (imagetypes() & IMG_JPG) {
			$types[] = image_type_to_mime_type(IMAGETYPE_JPEG);
		}
		if (imagetypes() & IMG_GIF) {
			$types[] = image_type_to_mime_type(IMAGETYPE_GIF);
		}
		if (imagetypes() & IMG_BMP) {
			$types[] = image_type_to_mime_type(IMAGETYPE_BMP);
		}

		return $types;
	}

	/**
	 * Checks if the provided mime type can be handled for resizing.
	 * Only with Imagick installed, animated GIF and WebP keep their animation after resize.
	 *
	 * @param string $mimetype
	 * @return boolean
	 */
	public static function canResize(string $mimetype): bool
	{
		if (in_array(self::getImageTypeByMimeType($mimetype), [IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
			return class_exists('Imagick');
		}
		return true;
	}

	/**
	 * Fetch image mimetype from the image data or guessing from the file name
	 *
	 * @param string $image_data Image data
	 *
	 * @return string MIME type
	 *
	 * @throws \Exception
	 */
	public static function getMimeTypeByData(string $image_data): string
	{
		$image = @getimagesizefromstring($image_data);
		if (!empty($image['mime'])) {
			return $image['mime'];
		}

		DI::logger()->debug('Undetected mime type', ['image' => $image, 'size' => strlen($image_data)]);

		return '';
	}

	/**
	 * Checks if the provided mime type is supported by the system
	 *
	 * @param string $mimetype
	 * @return boolean
	 */
	public static function isSupportedMimeType(string $mimetype): bool
	{
		if (substr($mimetype, 0, 6) != 'image/') {
			return false;
		}

		return in_array(self::sanitizeMimeType($mimetype), self::supportedMimeTypes());
	}

	/**
	 * Checks if the provided mime type is supported. If not, it is fetched from the provided image data.
	 *
	 * @param string $mimetype
	 * @param string $image_data
	 * @return string
	 */
	public static function addMimeTypeByDataIfInvalid(string $mimetype, string $image_data): string
	{
		$mimetype = self::sanitizeMimeType($mimetype);

		if (($image_data == '') || self::isSupportedMimeType($mimetype)) {
			return $mimetype;
		}

		$alternative = self::getMimeTypeByData($image_data);
		return $alternative ?: $mimetype;
	}

	/**
	 * Checks if the provided mime type is supported. If not, it is fetched from the provided file name.
	 *
	 * @param string $mimetype
	 * @param string $filename
	 * @return string
	 */
	public static function addMimeTypeByExtensionIfInvalid(string $mimetype, string $filename): string
	{
		$mimetype = self::sanitizeMimeType($mimetype);

		if (($filename == '') || self::isSupportedMimeType($mimetype)) {
			return $mimetype;
		}

		$alternative = self::guessTypeByExtension($filename);
		return $alternative ?: $mimetype;
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
		if (empty($filename)) {
			return '';
		}

		$ext = strtolower(pathinfo(parse_url($filename, PHP_URL_PATH), PATHINFO_EXTENSION));
		$ext = self::sanitizeExtensions($ext);
		if ($ext == '') {
			return '';
		}

		foreach (self::IMAGETYPES as $type) {
			if ($ext == image_type_to_extension($type, false)) {
				return image_type_to_mime_type($type);
			}
		}

		DI::logger()->debug('Unhandled extension', ['filename' => $filename, 'extension' => $ext]);
		return '';
	}

	/**
	 * Gets info array from given URL, cached data has priority
	 *
	 * @param string $url
	 * @param bool   $ocr
	 * @return array Info
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getInfoFromURLCached(string $url, bool $ocr = false): array
	{
		$data = [];

		if (empty($url)) {
			return $data;
		}

		$cacheKey = 'getInfoFromURL:' . sha1($url . $ocr);

		$data = DI::cache()->get($cacheKey);

		if (!is_array($data)) {
			$data = self::getInfoFromURL($url, $ocr);

			DI::cache()->set($cacheKey, $data);
		}

		return $data;
	}

	/**
	 * Gets info from URL uncached
	 *
	 * @param string $url
	 * @param bool   $ocr
	 * @return array Info array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getInfoFromURL(string $url, bool $ocr = false): array
	{
		$data = [];

		if (empty($url)) {
			return $data;
		}

		if (DI::baseUrl()->isLocalUrl($url) && ($data = Photo::getResourceData($url))) {
			$photo = Photo::selectFirst([], ['resource-id' => $data['guid'], 'scale' => $data['scale']]);
			if (!empty($photo)) {
				$img_str = Photo::getImageDataForPhoto($photo);
			}
			// @todo Possibly add a check for locally stored files
		}

		if (empty($img_str)) {
			try {
				$img_str = DI::httpClient()->fetch($url, HttpClientAccept::IMAGE, 4, '', HttpClientRequest::MEDIAVERIFIER);
			} catch (\Exception $exception) {
				DI::logger()->notice('Image is invalid', ['url' => $url, 'exception' => $exception]);
				return [];
			}
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

		if (!$data) {
			return [];
		}

		$image = new Image($img_str, '', $url, false);

		if ($image->isValid()) {
			$data['blurhash'] = $image->getBlurHash($img_str);

			if ($ocr) {
				$media = ['img_str' => $img_str];
				Hook::callAll('ocr-detection', $media);
				if (!empty($media['description'])) {
					$data['description'] = $media['description'];
				}
			}
		}

		$data['size'] = $filesize;

		return $data;
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
				$dest_width  = $max;
				$dest_height = intval(ceil(($height * $max) / $width));
			} elseif ($width > $height) {
				// else constrain both dimensions
				$dest_width  = $max;
				$dest_height = intval(ceil(($height * $max) / $width));
			} else {
				$dest_width  = intval(ceil(($width * $max) / $height));
				$dest_height = $max;
			}
		} else {
			if ($width > $max) {
				$dest_width  = $max;
				$dest_height = intval(ceil(($height * $max) / $width));
			} else {
				if ($height > $max) {
					// very tall image (greater than 16:9)
					// but width is OK - don't do anything

					if ((($height * 9) / 16) > $width) {
						$dest_width  = $width;
						$dest_height = $height;
					} else {
						$dest_width  = intval(ceil(($width * $max) / $height));
						$dest_height = $max;
					}
				} else {
					$dest_width  = $width;
					$dest_height = $height;
				}
			}
		}

		return ['width' => $dest_width, 'height' => $dest_height];
	}

	/**
	 * Get a BBCode tag for an local photo page URL with a preview thumbnail and an image description
	 *
	 * @param string $resource_id
	 * @param string $nickname The local user owner of the resource
	 * @param int    $preview Preview image size identifier, either 0, 1 or 2 in decreasing order of size
	 * @param string $ext Image file extension
	 * @param string $description
	 * @return string
	 */
	public static function getBBCodeByResource(string $resource_id, string $nickname, int $preview, string $ext, string $description = ''): string
	{
		return self::getBBCodeByUrl(
			DI::baseUrl() . '/photos/' . $nickname . '/image/' . $resource_id,
			DI::baseUrl() . '/photo/' . $resource_id . '-' . $preview . $ext,
			$description
		);
	}

	/**
	 * Get a BBCode tag for an image URL with a preview thumbnail and an image description
	 *
	 * @param string $photo Full image URL
	 * @param string $preview Preview image URL
	 * @param string $description
	 * @return string
	 */
	public static function getBBCodeByUrl(string $photo, string $preview = null, string $description = ''): string
	{
		if (!empty($preview)) {
			return '[url=' . $photo . '][img=' . $preview . ']' . $description . '[/img][/url]';
		}

		return '[img=' . $photo . ']' . $description . '[/img]';
	}

	/**
	 * Get the maximum possible upload size in bytes
	 *
	 * @return integer
	 */
	public static function getMaxUploadBytes(): int
	{
		$upload_size = ini_get('upload_max_filesize') ?: DI::config()->get('system', 'maximagesize');
		return Strings::getBytesFromShorthand($upload_size);
	}
}
