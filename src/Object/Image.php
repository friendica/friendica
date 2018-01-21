<?php
/**
 * @file src/Object/Image.php
 * @brief This file contains the Image class for image processing
 */
namespace Friendica\Object;

use Friendica\App;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Photo;
use Exception;
use Imagick;
use ImagickPixel;

/**
 * Class to handle images
 */
class Image
{
	private $image;

	/*
	 * Put back gd stuff, not everybody have Imagick
	 */
	private $imagick;
	private $width;
	private $height;
	private $valid;
	private $type;
	private $types;

	/**
	 * @brief supported mimetypes and corresponding file extensions
	 * @return array
	 */
	public static function supportedTypes()
	{
		if (class_exists('Imagick')) {
			// Imagick::queryFormats won't help us a lot there...
			// At least, not yet, other parts of friendica uses this array
			$t = [
				'image/jpeg' => 'jpg',
				'image/png' => 'png',
				'image/gif' => 'gif'
			];
		} else {
			$t = [];
			$t['image/jpeg'] ='jpg';
			if (imagetypes() & IMG_PNG) {
				$t['image/png'] = 'png';
			}
		}

		return $t;
	}

	/**
	 * @brief Constructor
	 * @param object  $data data
	 * @param boolean $type optional, default null
	 * @return object
	 */
	public function __construct($data, $type = null)
	{
		$this->imagick = class_exists('Imagick');
		$this->types = static::supportedTypes();
		if (!array_key_exists($type, $this->types)) {
			$type='image/jpeg';
		}
		$this->type = $type;

		if ($this->isImagick() && $this->loadData($data)) {
			return true;
		} else {
			// Failed to load with Imagick, fallback
			$this->imagick = false;
		}
		return $this->loadData($data);
	}

	/**
	 * @brief Destructor
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->image) {
			if ($this->isImagick()) {
				$this->image->clear();
				$this->image->destroy();
				return;
			}
			if (is_resource($this->image)) {
				imagedestroy($this->image);
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function isImagick()
	{
		return $this->imagick;
	}

	/**
	 * @brief Maps Mime types to Imagick formats
	 * @return array With with image formats (mime type as key)
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
	 * @param object $data data
	 * @return boolean
	 */
	private function loadData($data)
	{
		if ($this->isImagick()) {
			$this->image = new Imagick();
			try {
				$this->image->readImageBlob($data);
			} catch (Exception $e) {
				// Imagick couldn't use the data
				return false;
			}

			/*
			 * Setup the image to the format it will be saved to
			 */
			$map = self::getFormatsMap();
			$format = $map[$this->type];
			$this->image->setFormat($format);

			// Always coalesce, if it is not a multi-frame image it won't hurt anyway
			$this->image = $this->image->coalesceImages();

			/*
			 * setup the compression here, so we'll do it only once
			 */
			switch ($this->getType()) {
				case "image/png":
					$quality = Config::get('system', 'png_quality');
					if ((! $quality) || ($quality > 9)) {
						$quality = PNG_QUALITY;
					}
					/*
					 * From http://www.imagemagick.org/script/command-line-options.php#quality:
					 *
					 * 'For the MNG and PNG image formats, the quality value sets
					 * the zlib compression level (quality / 10) and filter-type (quality % 10).
					 * The default PNG "quality" is 75, which means compression level 7 with adaptive PNG filtering,
					 * unless the image has a color map, in which case it means compression level 7 with no PNG filtering'
					 */
					$quality = $quality * 10;
					$this->image->setCompressionQuality($quality);
					break;
				case "image/jpeg":
					$quality = Config::get('system', 'jpeg_quality');
					if ((! $quality) || ($quality > 100)) {
						$quality = JPEG_QUALITY;
					}
					$this->image->setCompressionQuality($quality);
			}

			// The 'width' and 'height' properties are only used by non-Imagick routines.
			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
			$this->valid  = true;

			return true;
		}

		$this->valid = false;
		$this->image = @imagecreatefromstring($data);
		if ($this->image !== false) {
			$this->width  = imagesx($this->image);
			$this->height = imagesy($this->image);
			$this->valid  = true;
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);

			return true;
		}

		return false;
	}

	/**
	 * @return boolean
	 */
	public function isValid()
	{
		if ($this->isImagick()) {
			return ($this->image !== false);
		}
		return $this->valid;
	}

	/**
	 * @return mixed
	 */
	public function getWidth()
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			return $this->image->getImageWidth();
		}
		return $this->width;
	}

	/**
	 * @return mixed
	 */
	public function getHeight()
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			return $this->image->getImageHeight();
		}
		return $this->height;
	}

	/**
	 * @return mixed
	 */
	public function getImage()
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			/* Clean it */
			$this->image = $this->image->deconstructImages();
			return $this->image;
		}
		return $this->image;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		if (!$this->isValid()) {
			return false;
		}

		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getExt()
	{
		if (!$this->isValid()) {
			return false;
		}

		return $this->types[$this->getType()];
	}

	/**
	 * @param integer $max max dimension
	 * @return mixed
	 */
	public function scaleDown($max)
	{
		if (!$this->isValid()) {
			return false;
		}

		$width = $this->getWidth();
		$height = $this->getHeight();

		$dest_width = $dest_height = 0;

		if ((! $width)|| (! $height)) {
			return false;
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

		return $this->scale($dest_width, $dest_height);
	}

	/**
	 * @param integer $degrees degrees to rotate image
	 * @return mixed
	 */
	public function rotate($degrees)
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			$this->image->setFirstIterator();
			do {
				$this->image->rotateImage(new ImagickPixel(), -$degrees); // ImageMagick rotates in the opposite direction of imagerotate()
			} while ($this->image->nextImage());
			return;
		}

		// if script dies at this point check memory_limit setting in php.ini
		$this->image  = imagerotate($this->image, $degrees, 0);
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	/**
	 * @param boolean $horiz optional, default true
	 * @param boolean $vert  optional, default false
	 * @return mixed
	 */
	public function flip($horiz = true, $vert = false)
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			$this->image->setFirstIterator();
			do {
				if ($horiz) {
					$this->image->flipImage();
				}
				if ($vert) {
					$this->image->flopImage();
				}
			} while ($this->image->nextImage());
			return;
		}

		$w = imagesx($this->image);
		$h = imagesy($this->image);
		$flipped = imagecreate($w, $h);
		if ($horiz) {
			for ($x = 0; $x < $w; $x++) {
				imagecopy($flipped, $this->image, $x, 0, $w - $x - 1, 0, 1, $h);
			}
		}
		if ($vert) {
			for ($y = 0; $y < $h; $y++) {
				imagecopy($flipped, $this->image, 0, $y, 0, $h - $y - 1, $w, 1);
			}
		}
		$this->image = $flipped;
	}

	/**
	 * @param string $filename filename
	 * @return mixed
	 */
	public function orient($filename)
	{
		if ($this->isImagick()) {
			// based off comment on http://php.net/manual/en/imagick.getimageorientation.php
			$orientation = $this->image->getImageOrientation();
			switch ($orientation) {
				case Imagick::ORIENTATION_BOTTOMRIGHT:
					$this->image->rotateimage("#000", 180);
					break;
				case Imagick::ORIENTATION_RIGHTTOP:
					$this->image->rotateimage("#000", 90);
					break;
				case Imagick::ORIENTATION_LEFTBOTTOM:
					$this->image->rotateimage("#000", -90);
					break;
			}

			$this->image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
			return true;
		}
		// based off comment on http://php.net/manual/en/function.imagerotate.php

		if (!$this->isValid()) {
			return false;
		}

		if ((!function_exists('exif_read_data')) || ($this->getType() !== 'image/jpeg')) {
			return;
		}

		$exif = @exif_read_data($filename, null, true);
		if (!$exif) {
			return;
		}

		$ort = $exif['IFD0']['Orientation'];

		switch ($ort) {
			case 1: // nothing
				break;

			case 2: // horizontal flip
				$this->flip();
				break;

			case 3: // 180 rotate left
				$this->rotate(180);
				break;

			case 4: // vertical flip
				$this->flip(false, true);
				break;

			case 5: // vertical flip + 90 rotate right
				$this->flip(false, true);
				$this->rotate(-90);
				break;

			case 6: // 90 rotate right
				$this->rotate(-90);
				break;

			case 7: // horizontal flip + 90 rotate right
				$this->flip();
				$this->rotate(-90);
				break;

			case 8: // 90 rotate left
				$this->rotate(90);
				break;
		}

		//	logger('exif: ' . print_r($exif,true));
		return $exif;
	}

	/**
	 * @param integer $min minimum dimension
	 * @return mixed
	 */
	public function scaleUp($min)
	{
		if (!$this->isValid()) {
			return false;
		}

		$width = $this->getWidth();
		$height = $this->getHeight();

		$dest_width = $dest_height = 0;

		if ((!$width)|| (!$height)) {
			return false;
		}

		if ($width < $min && $height < $min) {
			if ($width > $height) {
				$dest_width = $min;
				$dest_height = intval(($height * $min) / $width);
			} else {
				$dest_width = intval(($width * $min) / $height);
				$dest_height = $min;
			}
		} else {
			if ($width < $min) {
				$dest_width = $min;
				$dest_height = intval(($height * $min) / $width);
			} else {
				if ($height < $min) {
					$dest_width = intval(($width * $min) / $height);
					$dest_height = $min;
				} else {
					$dest_width = $width;
					$dest_height = $height;
				}
			}
		}

		return $this->scale($dest_width, $dest_height);
	}

	/**
	 * @param integer $dim dimension
	 * @return mixed
	 */
	public function scaleToSquare($dim)
	{
		if (!$this->isValid()) {
			return false;
		}

		return $this->scale($dim, $dim);
	}

	/**
	 * @brief Scale image to target dimensions
	 *
	 * @param int $dest_width
	 * @param int $dest_height
	 * @return boolean
	 */
	private function scale($dest_width, $dest_height)
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			/*
			 * If it is not animated, there will be only one iteration here,
			 * so don't bother checking
			 */
			// Don't forget to go back to the first frame
			$this->image->setFirstIterator();
			do {
				// FIXME - implement horizontal bias for scaling as in following GD functions
				// to allow very tall images to be constrained only horizontally.
				$this->image->scaleImage($dest_width, $dest_height);
			} while ($this->image->nextImage());

			// These may not be necessary anymore
			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
		} else {
			$dest = imagecreatetruecolor($dest_width, $dest_height);
			imagealphablending($dest, false);
			imagesavealpha($dest, true);

			if ($this->type=='image/png') {
				imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
			}

			imagecopyresampled($dest, $this->image, 0, 0, 0, 0, $dest_width, $dest_height, $this->width, $this->height);

			if ($this->image) {
				imagedestroy($this->image);
			}

			$this->image = $dest;
			$this->width  = imagesx($this->image);
			$this->height = imagesy($this->image);
		}

		return true;
	}

	/**
	 * @param integer $max maximum
	 * @param integer $x   x coordinate
	 * @param integer $y   y coordinate
	 * @param integer $w   width
	 * @param integer $h   height
	 * @return mixed
	 */
	public function crop($max, $x, $y, $w, $h)
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			$this->image->setFirstIterator();
			do {
				$this->image->cropImage($w, $h, $x, $y);
				/*
				 * We need to remove the canva,
				 * or the image is not resized to the crop:
				 * http://php.net/manual/en/imagick.cropimage.php#97232
				 */
				$this->image->setImagePage(0, 0, 0, 0);
			} while ($this->image->nextImage());
			return $this->scaleDown($max);
		}

		$dest = imagecreatetruecolor($max, $max);
		imagealphablending($dest, false);
		imagesavealpha($dest, true);
		if ($this->type=='image/png') {
			imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
		}
		imagecopyresampled($dest, $this->image, 0, 0, $x, $y, $max, $max, $w, $h);
		if ($this->image) {
			imagedestroy($this->image);
		}
		$this->image = $dest;
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	/**
	 * @param string $path file path
	 * @return mixed
	 */
	public function saveToFilePath($path)
	{
		if (!$this->isValid()) {
			return false;
		}

		$string = $this->asString();

		$a = get_app();

		$stamp1 = microtime(true);
		file_put_contents($path, $string);
		$a->save_timestamp($stamp1, "file");
	}

	/**
	 * @brief Magic method allowing string casting of an Image object
	 *
	 * Ex: $data = $Image->asString();
	 * can be replaced by
	 * $data = (string) $Image;
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->asString();
	}

	/**
	 * @return mixed
	 */
	public function asString()
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			/* Clean it */
			$this->image = $this->image->deconstructImages();
			$string = $this->image->getImagesBlob();
			return $string;
		}

		$quality = false;

		ob_start();

		// Enable interlacing
		imageinterlace($this->image, true);

		switch ($this->getType()) {
			case "image/png":
				$quality = Config::get('system', 'png_quality');
				if ((!$quality) || ($quality > 9)) {
					$quality = PNG_QUALITY;
				}
				imagepng($this->image, null, $quality);
				break;
			case "image/jpeg":
				$quality = Config::get('system', 'jpeg_quality');
				if ((!$quality) || ($quality > 100)) {
					$quality = JPEG_QUALITY;
				}
				imagejpeg($this->image, null, $quality);
		}
		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}

	/**
	 * Guess image mimetype from filename or from Content-Type header
	 *
	 * @param string  $filename Image filename
	 * @param boolean $fromcurl Check Content-Type header from curl request
	 *
	 * @return object
	 */
	public static function guessType($filename, $fromcurl = false)
	{
		logger('Image: guessType: '.$filename . ($fromcurl?' from curl headers':''), LOGGER_DEBUG);
		$type = null;
		if ($fromcurl) {
			$a = get_app();
			$headers=[];
			$h = explode("\n", $a->get_curl_headers());
			foreach ($h as $l) {
				list($k,$v) = array_map("trim", explode(":", trim($l), 2));
				$headers[$k] = $v;
			}
			if (array_key_exists('Content-Type', $headers))
				$type = $headers['Content-Type'];
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
				$image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
			} else {
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				$types = self::supportedTypes();
				$type = "image/jpeg";
				foreach ($types as $m => $e) {
					if ($ext == $e) {
						$type = $m;
					}
				}
			}
		}
		logger('Image: guessType: type='.$type, LOGGER_DEBUG);
		return $type;
	}

	/**
	 * @param string $url url
	 * @return object
	 */
	public static function getInfoFromURL($url)
	{
		$data = [];

		$data = Cache::get($url);

		if (is_null($data) || !$data || !is_array($data)) {
			$img_str = fetch_url($url, true, $redirects, 4);
			$filesize = strlen($img_str);

			if (function_exists("getimagesizefromstring")) {
				$data = getimagesizefromstring($img_str);
			} else {
				$tempfile = tempnam(get_temppath(), "cache");

				$a = get_app();
				$stamp1 = microtime(true);
				file_put_contents($tempfile, $img_str);
				$a->save_timestamp($stamp1, "file");

				$data = getimagesize($tempfile);
				unlink($tempfile);
			}

			if ($data) {
				$data["size"] = $filesize;
			}

			Cache::set($url, $data);
		}

		return $data;
	}

	/**
	 * @param integer $width  width
	 * @param integer $height height
	 * @param integer $max    max
	 * @return array
	 */
	public static function getScalingDimensions($width, $height, $max)
	{
		$dest_width = $dest_height = 0;

		if ((!$width) || (!$height)) {
			return false;
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
		return ["width" => $dest_width, "height" => $dest_height];
	}

	/**
	 * @brief This function is used by the fromgplus addon
	 * @param object  $a         App
	 * @param integer $uid       user id
	 * @param string  $imagedata optional, default empty
	 * @param string  $url       optional, default empty
	 * @return array
	 */
	public static function storePhoto(App $a, $uid, $imagedata = "", $url = "")
	{
		$r = q(
			"SELECT `user`.`nickname`, `user`.`page-flags`, `contact`.`id` FROM `user` INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
			WHERE `user`.`uid` = %d AND `user`.`blocked` = 0 AND `contact`.`self` = 1 LIMIT 1",
			intval($uid)
		);

		if (!DBM::isResult($r)) {
			logger("Can't detect user data for uid ".$uid, LOGGER_DEBUG);
			return([]);
		}

		$page_owner_nick  = $r[0]['nickname'];

		/// @TODO
		/// $default_cid      = $r[0]['id'];
		/// $community_page   = (($r[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);

		if ((strlen($imagedata) == 0) && ($url == "")) {
			logger("No image data and no url provided", LOGGER_DEBUG);
			return([]);
		} elseif (strlen($imagedata) == 0) {
			logger("Uploading picture from ".$url, LOGGER_DEBUG);

			$stamp1 = microtime(true);
			$imagedata = @file_get_contents($url);
			$a->save_timestamp($stamp1, "file");
		}

		$maximagesize = Config::get('system', 'maximagesize');

		if (($maximagesize) && (strlen($imagedata) > $maximagesize)) {
			logger("Image exceeds size limit of ".$maximagesize, LOGGER_DEBUG);
			return([]);
		}

		$tempfile = tempnam(get_temppath(), "cache");

		$stamp1 = microtime(true);
		file_put_contents($tempfile, $imagedata);
		$a->save_timestamp($stamp1, "file");

		$data = getimagesize($tempfile);

		if (!isset($data["mime"])) {
			unlink($tempfile);
			logger("File is no picture", LOGGER_DEBUG);
			return([]);
		}

		$Image = new Image($imagedata, $data["mime"]);

		if (!$Image->isValid()) {
			unlink($tempfile);
			logger("Picture is no valid picture", LOGGER_DEBUG);
			return([]);
		}

		$Image->orient($tempfile);
		unlink($tempfile);

		$max_length = Config::get('system', 'max_image_length');
		if (! $max_length) {
			$max_length = MAX_IMAGE_LENGTH;
		}

		if ($max_length > 0) {
			$Image->scaleDown($max_length);
		}

		$width = $Image->getWidth();
		$height = $Image->getHeight();

		$hash = photo_new_resource();

		$smallest = 0;

		// Pictures are always public by now
		//$defperm = '<'.$default_cid.'>';
		$defperm = "";
		$visitor = 0;

		$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, t('Wall Photos'), 0, 0, $defperm);

		if (!$r) {
			logger("Picture couldn't be stored", LOGGER_DEBUG);
			return([]);
		}

		$image = ["page" => System::baseUrl().'/photos/'.$page_owner_nick.'/image/'.$hash,
			"full" => System::baseUrl()."/photo/{$hash}-0.".$Image->getExt()];

		if ($width > 800 || $height > 800) {
			$image["large"] = System::baseUrl()."/photo/{$hash}-0.".$Image->getExt();
		}

		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, t('Wall Photos'), 1, 0, $defperm);
			if ($r) {
				$image["medium"] = System::baseUrl()."/photo/{$hash}-1.".$Image->getExt();
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, t('Wall Photos'), 2, 0, $defperm);
			if ($r) {
				$image["small"] = System::baseUrl()."/photo/{$hash}-2.".$Image->getExt();
			}
		}

		if ($width > 160 && $height > 160) {
			$x = 0;
			$y = 0;

			$min = $Image->getWidth();
			if ($min > 160) {
				$x = ($min - 160) / 2;
			}

			if ($Image->getHeight() < $min) {
				$min = $Image->getHeight();
				if ($min > 160) {
					$y = ($min - 160) / 2;
				}
			}

			$min = 160;
			$Image->crop(160, $x, $y, $min, $min);

			$r = Photo::store($Image, $uid, $visitor, $hash, $tempfile, t('Wall Photos'), 3, 0, $defperm);
			if ($r) {
				$image["thumb"] = System::baseUrl()."/photo/{$hash}-3.".$Image->getExt();
			}
		}

		// Set the full image as preview image. This will be overwritten, if the picture is larger than 640.
		$image["preview"] = $image["full"];

		// Deactivated, since that would result in a cropped preview, if the picture wasn't larger than 320
		//if (isset($image["thumb"]))
		//  $image["preview"] = $image["thumb"];

		// Unsure, if this should be activated or deactivated
		//if (isset($image["small"]))
		//  $image["preview"] = $image["small"];

		if (isset($image["medium"])) {
			$image["preview"] = $image["medium"];
		}

		return($image);
	}
}
