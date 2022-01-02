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

namespace Friendica\Object;

use Exception;
use Friendica\DI;
use Friendica\Util\Images;
use Imagick;
use ImagickPixel;

/**
 * Class to handle images
 */
class Image
{
	/** @var Imagick|resource */
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
	 * Constructor
	 * @param string  $data
	 * @param boolean $type optional, default null
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function __construct($data, $type = null)
	{
		$this->imagick = class_exists('Imagick');
		$this->types = Images::supportedTypes();
		if (!array_key_exists($type, $this->types)) {
			$type = 'image/jpeg';
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
	 * Destructor
	 *
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
	 * @param string $data data
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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
			$map = Images::getFormatsMap();
			$format = $map[$this->type];
			$this->image->setFormat($format);

			// Always coalesce, if it is not a multi-frame image it won't hurt anyway
			try {
				$this->image = $this->image->coalesceImages();
			} catch (Exception $e) {
				return false;
			}

			/*
			 * setup the compression here, so we'll do it only once
			 */
			switch ($this->getType()) {
				case "image/png":
					$quality = DI::config()->get('system', 'png_quality');
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
					$quality = DI::config()->get('system', 'jpeg_quality');
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
			try {
				/* Clean it */
				$this->image = $this->image->deconstructImages();
				return $this->image;
			} catch (Exception $e) {
				return false;
			}
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

		$ort = isset($exif['IFD0']['Orientation']) ? $exif['IFD0']['Orientation'] : 1;

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
	 * Scale image to target dimensions
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
				try {
					$this->image->scaleImage($dest_width, $dest_height);
				} catch (Exception $e) {
					// Imagick couldn't use the data
					return false;
				}
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
	 * Convert a GIF to a PNG to make it static
	 */
	public function toStatic()
	{
		if ($this->type != 'image/gif') {
			return;
		}

		if ($this->isImagick()) {
			$this->type == 'image/png';
			$this->image->setFormat('png');
		}
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
	 * Magic method allowing string casting of an Image object
	 *
	 * Ex: $data = $Image->asString();
	 * can be replaced by
	 * $data = (string) $Image;
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __toString() {
		return $this->asString();
	}

	/**
	 * @return mixed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function asString()
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			try {
				/* Clean it */
				$this->image = $this->image->deconstructImages();
				$string = $this->image->getImagesBlob();
				return $string;
			} catch (Exception $e) {
				return false;
			}
		}

		ob_start();

		// Enable interlacing
		imageinterlace($this->image, true);

		switch ($this->getType()) {
			case "image/png":
				$quality = DI::config()->get('system', 'png_quality');
				imagepng($this->image, null, $quality);
				break;
			case "image/jpeg":
				$quality = DI::config()->get('system', 'jpeg_quality');
				imagejpeg($this->image, null, $quality);
		}
		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}
}
