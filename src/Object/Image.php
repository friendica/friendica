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

namespace Friendica\Object;

use Exception;
use Friendica\DI;
use Friendica\Util\Images;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use GDImage;
use kornrunner\Blurhash\Blurhash;

/**
 * Class to handle images
 */
class Image
{
	/** @var GDImage|Imagick|resource */
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
	 *
	 * @param string $data Image data
	 * @param string $type optional, default null
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function __construct(string $data, string $type = null)
	{
		$this->imagick = class_exists('Imagick');
		$this->types = Images::supportedTypes();
		if (!array_key_exists($type, $this->types)) {
			$type = 'image/jpeg';
		}
		$this->type = $type;

		if ($this->isImagick() && (empty($data) || $this->loadData($data))) {
			$this->valid = !empty($data);
			return;
		} else {
			// Failed to load with Imagick, fallback
			$this->imagick = false;
		}
		$this->loadData($data);
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
	 * Loads image data into handler class
	 *
	 * @param string $data Image data
	 * @return boolean Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function loadData(string $data): bool
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
				case 'image/png':
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

				case 'image/jpg':
				case 'image/jpeg':
					$quality = DI::config()->get('system', 'jpeg_quality');
					$this->image->setCompressionQuality($quality);
			}

			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
			$this->valid  = !empty($this->image);

			return $this->valid;
		}

		$this->valid = false;
		try {
			$this->image = @imagecreatefromstring($data);
			if ($this->image !== false) {
				$this->width  = imagesx($this->image);
				$this->height = imagesy($this->image);
				$this->valid  = true;
				imagealphablending($this->image, false);
				imagesavealpha($this->image, true);

				return true;
			}
		} catch (\Throwable $error) {
			/** @see https://github.com/php/doc-en/commit/d09a881a8e9059d11e756ee59d75bf404d6941ed */
			if (strstr($error->getMessage(), "gd-webp cannot allocate temporary buffer")) {
				DI::logger()->notice('Image is probably animated and therefore unsupported', ['error' => $error]);
			} else {
				DI::logger()->warning('Unexpected throwable.', ['error' => $error]);
			}
		}

		return false;
	}

	/**
	 * @return boolean
	 */
	public function isValid(): bool
	{
		if ($this->isImagick()) {
			return !empty($this->image);
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
	 * Scales image down
	 *
	 * @param integer $max max dimension
	 * @return mixed
	 */
	public function scaleDown(int $max)
	{
		if (!$this->isValid()) {
			return false;
		}

		$width = $this->getWidth();
		$height = $this->getHeight();

		$scale = Images::getScalingDimensions($width, $height, $max);
		if ($scale) {
			return $this->scale($scale['width'], $scale['height']);
		} else {
			return false;
		}

	}

	/**
	 * Rotates image
	 *
	 * @param integer $degrees degrees to rotate image
	 * @return mixed
	 */
	public function rotate(int $degrees)
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->isImagick()) {
			$this->image->setFirstIterator();
			do {
				$this->image->rotateImage(new ImagickPixel(), -$degrees); // ImageMagick rotates in the opposite direction of imagerotate()
			} while ($this->image->nextImage());

			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
			return;
		}

		// if script dies at this point check memory_limit setting in php.ini
		$this->image  = imagerotate($this->image, $degrees, 0);
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	/**
	 * Flips image
	 *
	 * @param boolean $horiz optional, default true
	 * @param boolean $vert  optional, default false
	 * @return mixed
	 */
	public function flip(bool $horiz = true, bool $vert = false)
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
	 * Fixes orientation and maybe returns EXIF data (?)
	 *
	 * @param string $filename Filename
	 * @return mixed
	 */
	public function orient(string $filename)
	{
		if ($this->isImagick()) {
			// based off comment on http://php.net/manual/en/imagick.getimageorientation.php
			$orientation = $this->image->getImageOrientation();
			switch ($orientation) {
				case Imagick::ORIENTATION_BOTTOMRIGHT:
					$this->rotate(180);
					break;
				case Imagick::ORIENTATION_RIGHTTOP:
					$this->rotate(-90);
					break;
				case Imagick::ORIENTATION_LEFTBOTTOM:
					$this->rotate(90);
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
	 * Rescales image to minimum size
	 *
	 * @param integer $min Minimum dimension
	 * @return mixed
	 */
	public function scaleUp(int $min)
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
	 * Scales image to square
	 *
	 * @param integer $dim Dimension
	 * @return mixed
	 */
	public function scaleToSquare(int $dim)
	{
		if (!$this->isValid()) {
			return false;
		}

		return $this->scale($dim, $dim);
	}

	/**
	 * Scale image to target dimensions
	 *
	 * @param int $dest_width Destination width
	 * @param int $dest_height Destination height
	 * @return boolean Success
	 */
	private function scale(int $dest_width, int $dest_height): bool
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
	 *
	 * @return void
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
	 * Crops image
	 *
	 * @param integer $max maximum
	 * @param integer $x   x coordinate
	 * @param integer $y   y coordinate
	 * @param integer $w   width
	 * @param integer $h   height
	 * @return mixed
	 */
	public function crop(int $max, int $x, int $y, int $w, int $h)
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
		$this->image  = $dest;
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);

		// All successful
		return true;
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
	public function __toString(): string
	{
		return (string) $this->asString();
	}

	/**
	 * Returns image as string or false on failure
	 *
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
				return $this->image->getImagesBlob();
			} catch (Exception $e) {
				return false;
			}
		}

		$stream = fopen('php://memory','r+');

		// Enable interlacing
		imageinterlace($this->image, true);

		switch ($this->getType()) {
			case 'image/png':
				$quality = DI::config()->get('system', 'png_quality');
				imagepng($this->image, $stream, $quality);
				break;

			case 'image/jpeg':
			case 'image/jpg':
				$quality = DI::config()->get('system', 'jpeg_quality');
				imagejpeg($this->image, $stream, $quality);
				break;
		}
		rewind($stream);
		return stream_get_contents($stream);
	}

	/**
	 * Create a blurhash out of a given image string
	 *
	 * @param string $img_str
	 * @return string
	 */
	public function getBlurHash(): string
	{
		$image = New Image($this->asString());
		if (empty($image) || !$this->isValid()) {
			return '';
		}

		$width  = $image->getWidth();
		$height = $image->getHeight();

		if (max($width, $height) > 90) {
			$image->scaleDown(90);
			$width  = $image->getWidth();
			$height = $image->getHeight();
		}

		if (empty($width) || empty($height)) {
			return '';
		}

		$pixels = [];
		for ($y = 0; $y < $height; ++$y) {
			$row = [];
			for ($x = 0; $x < $width; ++$x) {
				if ($image->isImagick()) {
					try {
						$colors = $image->image->getImagePixelColor($x, $y)->getColor();
					} catch (\Exception $exception) {
						return '';
					}
					$row[] = [$colors['r'], $colors['g'], $colors['b']];
				} else {
					$index = imagecolorat($image->image, $x, $y);
					$colors = @imagecolorsforindex($image->image, $index);
					$row[] = [$colors['red'], $colors['green'], $colors['blue']];
				}
			}
			$pixels[] = $row;
		}

		// The components define the amount of details (1 to 9).
		$components_x = 9;
		$components_y = 9;

		return Blurhash::encode($pixels, $components_x, $components_y);
	}

	/**
	 * Create an image out of a blurhash
	 *
	 * @param string $blurhash
	 * @param integer $width
	 * @param integer $height
	 * @return void
	 */
	public function getFromBlurHash(string $blurhash, int $width, int $height)
	{
		$scaled = Images::getScalingDimensions($width, $height, 90);
		$pixels = Blurhash::decode($blurhash, $scaled['width'], $scaled['height']);

		if ($this->isImagick()) {
			$this->image = new Imagick();
			$draw  = new ImagickDraw();
			$this->image->newImage($scaled['width'], $scaled['height'], '', 'png');
		} else {
			$this->image = imagecreatetruecolor($scaled['width'], $scaled['height']);
		}

		for ($y = 0; $y < $scaled['height']; ++$y) {
			for ($x = 0; $x < $scaled['width']; ++$x) {
				[$r, $g, $b] = $pixels[$y][$x];
				if ($this->isImagick()) {
					$draw->setFillColor("rgb($r, $g, $b)");
					$draw->point($x, $y);
				} else {
					imagesetpixel($this->image, $x, $y, imagecolorallocate($this->image, $r, $g, $b));
				}
			}
		}

		if ($this->isImagick()) {
			$this->image->drawImage($draw);
			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
		} else {
			$this->width  = imagesx($this->image);
			$this->height = imagesy($this->image);
		}

		$this->valid = !empty($this->image);

		$this->scaleUp(min($width, $height));
	}
}
