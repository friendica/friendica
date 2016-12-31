<?php
/**
 * @file include/Photo.php
 * @brief This file contains the Photo class for image processing
 */

require_once("include/photos.php");

class Photo {

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
	 */
	static function supportedTypes() {
		if (class_exists('Imagick')) {

			// Imagick::queryFormats won't help us a lot there...
			// At least, not yet, other parts of friendica uses this array
			$t = array(
				'image/jpeg' => 'jpg',
				'image/png' => 'png',
				'image/gif' => 'gif'
			);
		} else {
			$t = array();
			$t['image/jpeg'] ='jpg';
			if (imagetypes() & IMG_PNG) {
				$t['image/png'] = 'png';
			}
		}

		return $t;
	}

	public function __construct($data, $type=null) {
		$this->imagick = class_exists('Imagick');
		$this->types = $this->supportedTypes();
		if (!array_key_exists($type, $this->types)){
			$type='image/jpeg';
		}
		$this->type = $type;

		if ($this->is_imagick() && $this->load_data($data)) {
			return true;
		} else {
			// Failed to load with Imagick, fallback
			$this->imagick = false;
		}
		return $this->load_data($data);
	}

	public function __destruct() {
		if ($this->image) {
			if ($this->is_imagick()) {
				$this->image->clear();
				$this->image->destroy();
				return;
			}
			imagedestroy($this->image);
		}
	}

	public function is_imagick() {
		return $this->imagick;
	}

	/**
	 * @brief Maps Mime types to Imagick formats
	 * @return arr With with image formats (mime type as key)
	 */
	public function get_FormatsMap() {
		$m = array(
			'image/jpeg' => 'JPG',
			'image/png' => 'PNG',
			'image/gif' => 'GIF'
		);
		return $m;
	}

	private function load_data($data) {
		if ($this->is_imagick()) {
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
			$map = $this->get_FormatsMap();
			$format = $map[$type];
			$this->image->setFormat($format);

			// Always coalesce, if it is not a multi-frame image it won't hurt anyway
			$this->image = $this->image->coalesceImages();

			/*
			 * setup the compression here, so we'll do it only once
			 */
			switch($this->getType()){
				case "image/png":
					$quality = get_config('system', 'png_quality');
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
					$quality = get_config('system', 'jpeg_quality');
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

	public function is_valid() {
		if ($this->is_imagick()) {
			return ($this->image !== false);
		}
		return $this->valid;
	}

	public function getWidth() {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
			return $this->image->getImageWidth();
		}
		return $this->width;
	}

	public function getHeight() {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
			return $this->image->getImageHeight();
		}
		return $this->height;
	}

	public function getImage() {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
			/* Clean it */
			$this->image = $this->image->deconstructImages();
			return $this->image;
		}
		return $this->image;
	}

	public function getType() {
		if (!$this->is_valid()) {
			return false;
		}

		return $this->type;
	}

	public function getExt() {
		if (!$this->is_valid()) {
			return false;
		}

		return $this->types[$this->getType()];
	}

	public function scaleImage($max) {
		if (!$this->is_valid()) {
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


		if ($this->is_imagick()) {
			/*
			 * If it is not animated, there will be only one iteration here,
			 * so don't bother checking
			 */
			// Don't forget to go back to the first frame
			$this->image->setFirstIterator();
			do {

				// FIXME - implement horizantal bias for scaling as in followin GD functions
				// to allow very tall images to be constrained only horizontally. 

				$this->image->scaleImage($dest_width, $dest_height);
			} while ($this->image->nextImage());

			// These may not be necessary any more
			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();

			return;
		}


		$dest = imagecreatetruecolor($dest_width, $dest_height);
		imagealphablending($dest, false);
		imagesavealpha($dest, true);
		if ($this->type=='image/png') {
			imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
		}
		imagecopyresampled($dest, $this->image, 0, 0, 0, 0, $dest_width, $dest_height, $width, $height);
		if ($this->image) {
			imagedestroy($this->image);
		}
		$this->image = $dest;
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	public function rotate($degrees) {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
			$this->image->setFirstIterator();
			do {
				$this->image->rotateImage(new ImagickPixel(), -$degrees); // ImageMagick rotates in the opposite direction of imagerotate()
			} while ($this->image->nextImage());
			return;
		}

		$this->image  = imagerotate($this->image,$degrees,0);
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	public function flip($horiz = true, $vert = false) {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
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

	public function orient($filename) {
		if ($this->is_imagick()) {
			// based off comment on http://php.net/manual/en/imagick.getimageorientation.php
			$orientation = $this->image->getImageOrientation();
			switch ($orientation) {
			case imagick::ORIENTATION_BOTTOMRIGHT:
				$this->image->rotateimage("#000", 180);
				break;
			case imagick::ORIENTATION_RIGHTTOP:
				$this->image->rotateimage("#000", 90);
				break;
			case imagick::ORIENTATION_LEFTBOTTOM:
				$this->image->rotateimage("#000", -90);
				break;
			}

			$this->image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
			return true;
		}
		// based off comment on http://php.net/manual/en/function.imagerotate.php

		if (!$this->is_valid()) {
			return false;
		}

		if ((!function_exists('exif_read_data')) || ($this->getType() !== 'image/jpeg')) {
			return;
		}

		$exif = @exif_read_data($filename,null,true);
		if (!$exif) {
			return;
		}

		$ort = $exif['IFD0']['Orientation'];

		switch($ort)
		{
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



	public function scaleImageUp($min) {
		if (!$this->is_valid()) {
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

		if ($this->is_imagick()) {
			return $this->scaleImage($dest_width, $dest_height);
		}

		$dest = imagecreatetruecolor($dest_width, $dest_height);
		imagealphablending($dest, false);
		imagesavealpha($dest, true);
		if ($this->type=='image/png') {
			imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
		}
		imagecopyresampled($dest, $this->image, 0, 0, 0, 0, $dest_width, $dest_height, $width, $height);
		if ($this->image) {
			imagedestroy($this->image);
		}
		$this->image = $dest;
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}



	public function scaleImageSquare($dim) {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
			$this->image->setFirstIterator();
			do {
				$this->image->scaleImage($dim, $dim);
			} while ($this->image->nextImage());
			return;
		}

		$dest = imagecreatetruecolor($dim, $dim);
		imagealphablending($dest, false);
		imagesavealpha($dest, true);
		if ($this->type=='image/png') {
			imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127)); // fill with alpha
		}
		imagecopyresampled($dest, $this->image, 0, 0, 0, 0, $dim, $dim, $this->width, $this->height);
		if ($this->image) {
			imagedestroy($this->image);
		}
		$this->image = $dest;
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}


	public function cropImage($max, $x, $y, $w, $h) {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
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
			return $this->scaleImage($max);
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

	public function saveImage($path) {
		if (!$this->is_valid()) {
			return false;
		}

		$string = $this->imageString();

		$a = get_app();

		$stamp1 = microtime(true);
		file_put_contents($path, $string);
		$a->save_timestamp($stamp1, "file");
	}

	public function imageString() {
		if (!$this->is_valid()) {
			return false;
		}

		if ($this->is_imagick()) {
			/* Clean it */
			$this->image = $this->image->deconstructImages();
			$string = $this->image->getImagesBlob();
			return $string;
		}

		$quality = false;

		ob_start();

		// Enable interlacing
		imageinterlace($this->image, true);

		switch($this->getType()){
			case "image/png":
				$quality = get_config('system', 'png_quality');
				if ((!$quality) || ($quality > 9)) {
					$quality = PNG_QUALITY;
				}
				imagepng($this->image, null, $quality);
				break;
			case "image/jpeg":
				$quality = get_config('system', 'jpeg_quality');
				if ((!$quality) || ($quality > 100)) {
					$quality = JPEG_QUALITY;
				}
				imagejpeg($this->image, null, $quality);
		}
		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}



	public function store($uid, $cid, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = '', $allow_gid = '', $deny_cid = '', $deny_gid = '') {

		$r = q("SELECT `guid` FROM `photo` WHERE `resource-id` = '%s' AND `guid` != '' LIMIT 1",
			dbesc($rid)
		);
		if (dbm::is_result($r)) {
			$guid = $r[0]['guid'];
		} else {
			$guid = get_guid();
		}

		$x = q("SELECT `id` FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `contact-id` = %d AND `scale` = %d LIMIT 1",
			dbesc($rid),
			intval($uid),
			intval($cid),
			intval($scale)
		);
		if (dbm::is_result($x)) {
			$r = q("UPDATE `photo`
				SET `uid` = %d,
				`contact-id` = %d,
				`guid` = '%s',
				`resource-id` = '%s',
				`created` = '%s',
				`edited` = '%s',
				`filename` = '%s',
				`type` = '%s',
				`album` = '%s',
				`height` = %d,
				`width` = %d,
				`datasize` = %d,
				`data` = '%s',
				`scale` = %d,
				`profile` = %d,
				`allow_cid` = '%s',
				`allow_gid` = '%s',
				`deny_cid` = '%s',
				`deny_gid` = '%s'
				WHERE `id` = %d",

				intval($uid),
				intval($cid),
				dbesc($guid),
				dbesc($rid),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename($filename)),
				dbesc($this->getType()),
				dbesc($album),
				intval($this->getHeight()),
				intval($this->getWidth()),
				dbesc(strlen($this->imageString())),
				dbesc($this->imageString()),
				intval($scale),
				intval($profile),
				dbesc($allow_cid),
				dbesc($allow_gid),
				dbesc($deny_cid),
				dbesc($deny_gid),
				intval($x[0]['id'])
			);
		} else {
			$r = q("INSERT INTO `photo`
				(`uid`, `contact-id`, `guid`, `resource-id`, `created`, `edited`, `filename`, type, `album`, `height`, `width`, `datasize`, `data`, `scale`, `profile`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`)
				VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, '%s', %d, %d, '%s', '%s', '%s', '%s')",
				intval($uid),
				intval($cid),
				dbesc($guid),
				dbesc($rid),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc(basename($filename)),
				dbesc($this->getType()),
				dbesc($album),
				intval($this->getHeight()),
				intval($this->getWidth()),
				dbesc(strlen($this->imageString())),
				dbesc($this->imageString()),
				intval($scale),
				intval($profile),
				dbesc($allow_cid),
				dbesc($allow_gid),
				dbesc($deny_cid),
				dbesc($deny_gid)
			);
		}

		return $r;
	}
}


/**
 * Guess image mimetype from filename or from Content-Type header
 *
 * @arg $filename string Image filename
 * @arg $fromcurl boolean Check Content-Type header from curl request
 */
function guess_image_type($filename, $fromcurl=false) {
	logger('Photo: guess_image_type: '.$filename . ($fromcurl?' from curl headers':''), LOGGER_DEBUG);
	$type = null;
	if ($fromcurl) {
		$a = get_app();
		$headers=array();
		$h = explode("\n",$a->get_curl_headers());
		foreach ($h as $l) {
			list($k,$v) = array_map("trim", explode(":", trim($l), 2));
			$headers[$k] = $v;
		}
		if (array_key_exists('Content-Type', $headers))
			$type = $headers['Content-Type'];
	}
	if (is_null($type)){
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
			$types = Photo::supportedTypes();
			$type = "image/jpeg";
			foreach ($types as $m => $e){
				if ($ext == $e) {
					$type = $m;
				}
			}
		}
	}
	logger('Photo: guess_image_type: type='.$type, LOGGER_DEBUG);
	return $type;

}

/**
 * @brief Updates the avatar links in a contact only if needed
 *
 * @param string $avatar Link to avatar picture
 * @param int $uid User id of contact owner
 * @param int $cid Contact id
 * @param bool $force force picture update
 *
 * @return array Returns array of the different avatar sizes
 */
function update_contact_avatar($avatar, $uid, $cid, $force = false) {

	$r = q("SELECT `avatar`, `photo`, `thumb`, `micro` FROM `contact` WHERE `id` = %d LIMIT 1", intval($cid));
	if (!dbm::is_result($r)) {
		return false;
	} else {
		$data = array($r[0]["photo"], $r[0]["thumb"], $r[0]["micro"]);
	}

	if (($r[0]["avatar"] != $avatar) OR $force) {
		$photos = import_profile_photo($avatar, $uid, $cid, true);

		if ($photos) {
			q("UPDATE `contact` SET `avatar` = '%s', `photo` = '%s', `thumb` = '%s', `micro` = '%s', `avatar-date` = '%s' WHERE `id` = %d",
				dbesc($avatar), dbesc($photos[0]), dbesc($photos[1]), dbesc($photos[2]),
				dbesc(datetime_convert()), intval($cid));
			return $photos;
		}
	}

	return $data;
}

function import_profile_photo($photo, $uid, $cid, $quit_on_error = false) {

	$r = q("SELECT `resource-id` FROM `photo` WHERE `uid` = %d AND `contact-id` = %d AND `scale` = 4 AND `album` = 'Contact Photos' LIMIT 1",
		intval($uid),
		intval($cid)
	);
	if (dbm::is_result($r) && strlen($r[0]['resource-id'])) {
		$hash = $r[0]['resource-id'];
	} else {
		$hash = photo_new_resource();
	}

	$photo_failure = false;

	$filename = basename($photo);
	$img_str = fetch_url($photo, true);

	if ($quit_on_error AND ($img_str == "")) {
		return false;
	}

	$type = guess_image_type($photo, true);
	$img = new Photo($img_str, $type);
	if ($img->is_valid()) {

		$img->scaleImageSquare(175);

		$r = $img->store($uid, $cid, $hash, $filename, 'Contact Photos', 4);

		if ($r === false)
			$photo_failure = true;

		$img->scaleImage(80);

		$r = $img->store($uid, $cid, $hash, $filename, 'Contact Photos', 5);

		if ($r === false)
			$photo_failure = true;

		$img->scaleImage(48);

		$r = $img->store($uid, $cid, $hash, $filename, 'Contact Photos', 6);

		if ($r === false) {
			$photo_failure = true;
		}

		$photo = App::get_baseurl() . '/photo/' . $hash . '-4.' . $img->getExt();
		$thumb = App::get_baseurl() . '/photo/' . $hash . '-5.' . $img->getExt();
		$micro = App::get_baseurl() . '/photo/' . $hash . '-6.' . $img->getExt();
	} else {
		$photo_failure = true;
	}

	if ($photo_failure AND $quit_on_error) {
		return false;
	}

	if ($photo_failure) {
		$photo = App::get_baseurl() . '/images/person-175.jpg';
		$thumb = App::get_baseurl() . '/images/person-80.jpg';
		$micro = App::get_baseurl() . '/images/person-48.jpg';
	}

	return(array($photo,$thumb,$micro));

}

function get_photo_info($url) {
	$data = array();

	$data = Cache::get($url);

	if (is_null($data) OR !$data OR !is_array($data)) {
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

function scale_image($width, $height, $max) {

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
	return array("width" => $dest_width, "height" => $dest_height);
}

function store_photo($a, $uid, $imagedata = "", $url = "") {
	$r = q("SELECT `user`.`nickname`, `user`.`page-flags`, `contact`.`id` FROM `user` INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
		WHERE `user`.`uid` = %d AND `user`.`blocked` = 0 AND `contact`.`self` = 1 LIMIT 1",
		intval($uid));

	if (!dbm::is_result($r)) {
		logger("Can't detect user data for uid ".$uid, LOGGER_DEBUG);
		return(array());
	}

	$page_owner_nick  = $r[0]['nickname'];

	/// @TODO
	/// $default_cid      = $r[0]['id'];
	/// $community_page   = (($r[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if ((strlen($imagedata) == 0) AND ($url == "")) {
		logger("No image data and no url provided", LOGGER_DEBUG);
		return(array());
	} elseif (strlen($imagedata) == 0) {
		logger("Uploading picture from ".$url, LOGGER_DEBUG);

		$stamp1 = microtime(true);
		$imagedata = @file_get_contents($url);
		$a->save_timestamp($stamp1, "file");
	}

	$maximagesize = get_config('system', 'maximagesize');

	if (($maximagesize) && (strlen($imagedata) > $maximagesize)) {
		logger("Image exceeds size limit of ".$maximagesize, LOGGER_DEBUG);
		return(array());
	}

/*
	$r = q("select sum(octet_length(data)) as total from photo where uid = %d and scale = 0 and album != 'Contact Photos' ",
		intval($uid)
	);

	$limit = service_class_fetch($uid,'photo_upload_limit');

	if (($limit !== false) && (($r[0]['total'] + strlen($imagedata)) > $limit)) {
		logger("Image exceeds personal limit of uid ".$uid, LOGGER_DEBUG);
		return(array());
	}
*/

	$tempfile = tempnam(get_temppath(), "cache");

	$stamp1 = microtime(true);
	file_put_contents($tempfile, $imagedata);
	$a->save_timestamp($stamp1, "file");

	$data = getimagesize($tempfile);

	if (!isset($data["mime"])) {
		unlink($tempfile);
		logger("File is no picture", LOGGER_DEBUG);
		return(array());
	}

	$ph = new Photo($imagedata, $data["mime"]);

	if (!$ph->is_valid()) {
		unlink($tempfile);
		logger("Picture is no valid picture", LOGGER_DEBUG);
		return(array());
	}

	$ph->orient($tempfile);
	unlink($tempfile);

	$max_length = get_config('system', 'max_image_length');
	if (! $max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$ph->scaleImage($max_length);
	}

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$hash = photo_new_resource();

	$smallest = 0;

	// Pictures are always public by now
	//$defperm = '<'.$default_cid.'>';
	$defperm = "";
	$visitor = 0;

	$r = $ph->store($uid, $visitor, $hash, $tempfile, t('Wall Photos'), 0, 0, $defperm);

	if (!$r) {
		logger("Picture couldn't be stored", LOGGER_DEBUG);
		return(array());
	}

	$image = array("page" => App::get_baseurl().'/photos/'.$page_owner_nick.'/image/'.$hash,
			"full" => App::get_baseurl()."/photo/{$hash}-0.".$ph->getExt());

	if ($width > 800 || $height > 800) {
		$image["large"] = App::get_baseurl()."/photo/{$hash}-0.".$ph->getExt();
	}

	if ($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($uid, $visitor, $hash, $tempfile, t('Wall Photos'), 1, 0, $defperm);
		if ($r) {
			$image["medium"] = App::get_baseurl()."/photo/{$hash}-1.".$ph->getExt();
		}
	}

	if ($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store($uid, $visitor, $hash, $tempfile, t('Wall Photos'), 2, 0, $defperm);
		if ($r) {
			$image["small"] = App::get_baseurl()."/photo/{$hash}-2.".$ph->getExt();
		}
	}

	if ($width > 160 AND $height > 160) {
		$x = 0;
		$y = 0;

		$min = $ph->getWidth();
		if ($min > 160) {
			$x = ($min - 160) / 2;
		}

		if ($ph->getHeight() < $min) {
			$min = $ph->getHeight();
			if ($min > 160) {
				$y = ($min - 160) / 2;
			}
		}

		$min = 160;
		$ph->cropImage(160, $x, $y, $min, $min);

		$r = $ph->store($uid, $visitor, $hash, $tempfile, t('Wall Photos'), 3, 0, $defperm);
		if ($r) {
			$image["thumb"] = App::get_baseurl()."/photo/{$hash}-3.".$ph->getExt();
		}
	}

	// Set the full image as preview image. This will be overwritten, if the picture is larger than 640.
	$image["preview"] = $image["full"];

	// Deactivated, since that would result in a cropped preview, if the picture wasn't larger than 320
	//if (isset($image["thumb"]))
	//	$image["preview"] = $image["thumb"];

	// Unsure, if this should be activated or deactivated
	//if (isset($image["small"]))
	//	$image["preview"] = $image["small"];

	if (isset($image["medium"])) {
		$image["preview"] = $image["medium"];
	}

	return($image);
}
