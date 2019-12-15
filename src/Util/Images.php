<?php

namespace Friendica\Util;

use Friendica\Core\Cache;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Imagick;

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
	 * Guess image mimetype from filename or from Content-Type header
	 *
	 * @param string  $filename Image filename
	 * @param boolean $fromcurl Check Content-Type header from curl request
	 * @param string  $header   passed headers to take into account
	 *
	 * @return string|null
	 * @throws \Exception
	 */
	public static function guessType($filename, $fromcurl = false, $header = '')
	{
		Logger::info('Image: guessType: ' . $filename . ($fromcurl ? ' from curl headers' : ''));
		$type = null;
		if ($fromcurl) {
			$headers = [];
			$h = explode("\n", $header);
			foreach ($h as $l) {
				$data = array_map("trim", explode(":", trim($l), 2));
				if (count($data) > 1) {
					list($k, $v) = $data;
					$headers[$k] = $v;
				}
			}

			if (array_key_exists('Content-Type', $headers)) {
				$type = $headers['Content-Type'];
			}
		}

		if (is_null($type)) {
			// Guessing from extension? Isn't that... dangerous?
			if (class_exists('Imagick') && file_exists($filename) && is_readable($filename)) {
				/**
				 * Well, this not much better,
				 * but at least it comes from the data inside the image,
				 * we won't be tricked by a manipulated extension
				 */
				$image = new Imagick($filename);
				$type = $image->getImageMimeType();
			} else {
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				$types = self::supportedTypes();
				$type = 'image/jpeg';
				foreach ($types as $m => $e) {
					if ($ext == $e) {
						$type = $m;
					}
				}
			}
		}

		Logger::info('Image: guessType: type=' . $type);
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

		$data = Cache::get($url);

		if (empty($data) || !is_array($data)) {
			$data = self::getInfoFromURL($url);

			Cache::set($url, $data);
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

		$img_str = Network::fetchUrl($url, true, 4);

		if (!$img_str) {
			return [];
		}

		$filesize = strlen($img_str);

		try {
			if (function_exists("getimagesizefromstring")) {
				$data = @getimagesizefromstring($img_str);
			} else {
				$tempfile = tempnam(get_temppath(), "cache");

				$stamp1 = microtime(true);
				file_put_contents($tempfile, $img_str);
				DI::profiler()->saveTimestamp($stamp1, "file", System::callstack());

				$data = getimagesize($tempfile);
				unlink($tempfile);
			}
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
