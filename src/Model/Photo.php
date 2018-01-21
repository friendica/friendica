<?php
/**
 * @file src/Model/Photo.php
 * @brief This file contains the Photo class for database interface
 */
namespace Friendica\Model;

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Object\Image;
use dba;

require_once 'include/dba.php';

/**
 * Class to handle photo dabatase table
 */
class Photo
{
	/**
	 * @param Image   $Image     image
	 * @param integer $uid       uid
	 * @param integer $cid       cid
	 * @param integer $rid       rid
	 * @param string  $filename  filename
	 * @param string  $album     album name
	 * @param integer $scale     scale
	 * @param integer $profile   optional, default = 0
	 * @param string  $allow_cid optional, default = ''
	 * @param string  $allow_gid optional, default = ''
	 * @param string  $deny_cid  optional, default = ''
	 * @param string  $deny_gid  optional, default = ''
	 * @param string  $desc      optional, default = ''
	 * @return object
	 */
	public static function store(Image $Image, $uid, $cid, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = '', $allow_gid = '', $deny_cid = '', $deny_gid = '', $desc = '')
	{
		$photo = dba::selectFirst('photo', ['guid'], ["`resource-id` = ? AND `guid` != ?", $rid, '']);
		if (DBM::isResult($photo)) {
			$guid = $photo['guid'];
		} else {
			$guid = get_guid();
		}

		$existing_photo = dba::selectFirst('photo', ['id'], ['resource-id' => $rid, 'uid' => $uid, 'contact-id' => $cid, 'scale' => $scale]);

		$fields = [
			'uid' => $uid,
			'contact-id' => $cid,
			'guid' => $guid,
			'resource-id' => $rid,
			'created' => datetime_convert(),
			'edited' => datetime_convert(),
			'filename' => basename($filename),
			'type' => $Image->getType(),
			'album' => $album,
			'height' => $Image->getHeight(),
			'width' => $Image->getWidth(),
			'datasize' => strlen($Image->asString()),
			'data' => $Image->asString(),
			'scale' => $scale,
			'profile' => $profile,
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid' => $deny_cid,
			'deny_gid' => $deny_gid,
			'desc' => $desc
		];

		if (DBM::isResult($existing_photo)) {
			$r = dba::update('photo', $fields, ['id' => $existing_photo['id']]);
		} else {
			$r = dba::insert('photo', $fields);
		}

		return $r;
	}

	/**
	 * @param string  $image_url     Remote URL
	 * @param integer $uid           user id
	 * @param integer $cid           contact id
	 * @param boolean $quit_on_error optional, default false
	 * @return array
	 */
	public static function importProfilePhoto($image_url, $uid, $cid, $quit_on_error = false)
	{
		$photo = dba::selectFirst(
			'photo', ['resource-id'], ['uid' => $uid, 'contact-id' => $cid, 'scale' => 4, 'album' => 'Contact Photos']
		);
		if (x($photo['resource-id'])) {
			$hash = $photo['resource-id'];
		} else {
			$hash = photo_new_resource();
		}

		$photo_failure = false;

		$filename = basename($image_url);
		$img_str = fetch_url($image_url, true);

		if ($quit_on_error && ($img_str == "")) {
			return false;
		}

		$type = Image::guessType($image_url, true);
		$Image = new Image($img_str, $type);
		if ($Image->isValid()) {
			$Image->scaleToSquare(175);

			$r = self::store($Image, $uid, $cid, $hash, $filename, 'Contact Photos', 4);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(80);

			$r = self::store($Image, $uid, $cid, $hash, $filename, 'Contact Photos', 5);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(48);

			$r = self::store($Image, $uid, $cid, $hash, $filename, 'Contact Photos', 6);

			if ($r === false) {
				$photo_failure = true;
			}

			$suffix = '?ts=' . time();

			$image_url = System::baseUrl() . '/photo/' . $hash . '-4.' . $Image->getExt() . $suffix;
			$thumb = System::baseUrl() . '/photo/' . $hash . '-5.' . $Image->getExt() . $suffix;
			$micro = System::baseUrl() . '/photo/' . $hash . '-6.' . $Image->getExt() . $suffix;

			// Remove the cached photo
			$a = get_app();
			$basepath = $a->get_basepath();

			if (is_dir($basepath . "/photo")) {
				$filename = $basepath . '/photo/' . $hash . '-4.' . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . '/photo/' . $hash . '-5.' . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . '/photo/' . $hash . '-6.' . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
			}
		} else {
			$photo_failure = true;
		}

		if ($photo_failure && $quit_on_error) {
			return false;
		}

		if ($photo_failure) {
			$image_url = System::baseUrl() . '/images/person-175.jpg';
			$thumb = System::baseUrl() . '/images/person-80.jpg';
			$micro = System::baseUrl() . '/images/person-48.jpg';
		}

		return [$image_url, $thumb, $micro];
	}

	/**
	 * @param string $exifCoord coordinate
	 * @param string $hemi      hemi
	 * @return float
	 */
	public static function getGps($exifCoord, $hemi)
	{
		$degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == 'W' || $hemi == 'S') ? -1 : 1;

		return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
	}

	/**
	 * @param string $coordPart coordPart
	 * @return float
	 */
	private static function gps2Num($coordPart)
	{
		$parts = explode('/', $coordPart);

		if (count($parts) <= 0) {
			return 0;
		}

		if (count($parts) == 1) {
			return $parts[0];
		}

		return floatval($parts[0]) / floatval($parts[1]);
	}

	/**
	 * @brief Fetch the photo albums that are available for a viewer
	 *
	 * The query in this function is cost intensive, so it is cached.
	 *
	 * @param int  $uid    User id of the photos
	 * @param bool $update Update the cache
	 *
	 * @return array Returns array of the photo albums
	 */
	public static function getAlbums($uid, $update = false)
	{
		$sql_extra = permissions_sql($uid);

		$key = "photo_albums:".$uid.":".local_user().":".remote_user();
		$albums = Cache::get($key);
		if (is_null($albums) || $update) {
			if (!Config::get('system', 'no_count', false)) {
				/// @todo This query needs to be renewed. It is really slow
				// At this time we just store the data in the cache
				$albums = q("SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`, ANY_VALUE(`created`) AS `created`
					FROM `photo`
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
					GROUP BY `album` ORDER BY `created` DESC",
					intval($uid),
					dbesc('Contact Photos'),
					dbesc(t('Contact Photos'))
				);
			} else {
				// This query doesn't do the count and is much faster
				$albums = q("SELECT DISTINCT(`album`), '' AS `total`
					FROM `photo` USE INDEX (`uid_album_scale_created`)
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra",
					intval($uid),
					dbesc('Contact Photos'),
					dbesc(t('Contact Photos'))
				);
			}
			Cache::set($key, $albums, CACHE_DAY);
		}
		return $albums;
	}

	/**
	 * @param int $uid User id of the photos
	 * @return void
	 */
	public static function clearAlbumCache($uid)
	{
		$key = "photo_albums:".$uid.":".local_user().":".remote_user();
		Cache::set($key, null, CACHE_DAY);
	}
}
