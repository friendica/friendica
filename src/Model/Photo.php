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

namespace Friendica\Model;

use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Storage\ExternalResource;
use Friendica\Model\Storage\InvalidClassStorageException;
use Friendica\Model\Storage\ReferenceStorageException;
use Friendica\Model\Storage\StorageException;
use Friendica\Model\Storage\SystemResource;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Security\Security;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;

require_once "include/dba.php";

/**
 * Class to handle photo dabatase table
 */
class Photo
{
	const CONTACT_PHOTOS = 'Contact Photos';

	/**
	 * Select rows from the photo table and returns them as array
	 *
	 * @param array $fields     Array of selected fields, empty for all
	 * @param array $conditions Array of fields for conditions
	 * @param array $params     Array of several parameters
	 *
	 * @return boolean|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::selectToArray
	 */
	public static function selectToArray(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		return DBA::selectToArray('photo', $fields, $conditions, $params);
	}

	/**
	 * Retrieve a single record from the photo table
	 *
	 * @param array $fields     Array of selected fields, empty for all
	 * @param array $conditions Array of fields for conditions
	 * @param array $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function selectFirst(array $fields = [], array $conditions = [], array $params = [])
	{
		if (empty($fields)) {
			$fields = self::getFields();
		}

		return DBA::selectFirst("photo", $fields, $conditions, $params);
	}

	/**
	 * Get photos for user id
	 *
	 * @param integer $uid        User id
	 * @param string  $resourceid Rescource ID of the photo
	 * @param array   $conditions Array of fields for conditions
	 * @param array   $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getPhotosForUser($uid, $resourceid, array $conditions = [], array $params = [])
	{
		$conditions["resource-id"] = $resourceid;
		$conditions["uid"] = $uid;

		return self::selectToArray([], $conditions, $params);
	}

	/**
	 * Get a photo for user id
	 *
	 * @param integer $uid        User id
	 * @param string  $resourceid Rescource ID of the photo
	 * @param integer $scale      Scale of the photo. Defaults to 0
	 * @param array   $conditions Array of fields for conditions
	 * @param array   $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getPhotoForUser($uid, $resourceid, $scale = 0, array $conditions = [], array $params = [])
	{
		$conditions["resource-id"] = $resourceid;
		$conditions["uid"] = $uid;
		$conditions["scale"] = $scale;

		return self::selectFirst([], $conditions, $params);
	}

	/**
	 * Get a single photo given resource id and scale
	 *
	 * This method checks for permissions. Returns associative array
	 * on success, "no sign" image info, if user has no permission,
	 * false if photo does not exists
	 *
	 * @param string  $resourceid Rescource ID of the photo
	 * @param integer $scale      Scale of the photo. Defaults to 0
	 *
	 * @return boolean|array
	 * @throws \Exception
	 */
	public static function getPhoto(string $resourceid, int $scale = 0)
	{
		$r = self::selectFirst(["uid"], ["resource-id" => $resourceid]);
		if (!DBA::isResult($r)) {
			return false;
		}

		$uid = $r["uid"];

		$accessible = $uid ? (bool)DI::pConfig()->get($uid, 'system', 'accessible-photos', false) : false;

		$sql_acl = Security::getPermissionsSQLByUserId($uid, $accessible);

		$conditions = ["`resource-id` = ? AND `scale` <= ? " . $sql_acl, $resourceid, $scale];
		$params = ["order" => ["scale" => true]];
		$photo = self::selectFirst([], $conditions, $params);

		return $photo;
	}

	/**
	 * Check if photo with given conditions exists
	 *
	 * @param array $conditions Array of extra conditions
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public static function exists(array $conditions)
	{
		return DBA::exists("photo", $conditions);
	}


	/**
	 * Get Image data for given row id. null if row id does not exist
	 *
	 * @param array $photo Photo data. Needs at least 'id', 'type', 'backend-class', 'backend-ref'
	 *
	 * @return \Friendica\Object\Image
	 */
	public static function getImageDataForPhoto(array $photo)
	{
		if (!empty($photo['data'])) {
			return $photo['data'];
		}

		try {
			$backendClass = DI::storageManager()->getByName($photo['backend-class'] ?? '');
			/// @todo refactoring this returning, because the storage returns a "string" which is casted in different ways - a check "instanceof Image" will fail!
			return $backendClass->get($photo['backend-ref'] ?? '');
		} catch (InvalidClassStorageException $storageException) {
			try {
				// legacy data storage in "data" column
				$i = self::selectFirst(['data'], ['id' => $photo['id']]);
				if ($i !== false) {
					return $i['data'];
				} else {
					DI::logger()->info('Stored legacy data is empty', ['photo' => $photo]);
				}
			} catch (\Exception $exception) {
				DI::logger()->info('Unexpected database exception', ['photo' => $photo, 'exception' => $exception]);
			}
		} catch (ReferenceStorageException $referenceStorageException) {
			DI::logger()->debug('Invalid reference for photo', ['photo' => $photo, 'exception' => $referenceStorageException]);
		} catch (StorageException $storageException) {
			DI::logger()->info('Unexpected storage exception', ['photo' => $photo, 'exception' => $storageException]);
		} catch (\ImagickException $imagickException) {
			DI::logger()->info('Unexpected imagick exception', ['photo' => $photo, 'exception' => $imagickException]);
		}

		return null;
	}

	/**
	 * Get Image object for given row id. null if row id does not exist
	 *
	 * @param array $photo Photo data. Needs at least 'id', 'type', 'backend-class', 'backend-ref'
	 *
	 * @return \Friendica\Object\Image
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getImageForPhoto(array $photo): Image
	{
		return new Image(self::getImageDataForPhoto($photo), $photo['type']);
	}

	/**
	 * Return a list of fields that are associated with the photo table
	 *
	 * @return array field list
	 * @throws \Exception
	 */
	private static function getFields()
	{
		$allfields = DBStructure::definition(DI::app()->getBasePath(), false);
		$fields = array_keys($allfields["photo"]["fields"]);
		array_splice($fields, array_search("data", $fields), 1);
		return $fields;
	}

	/**
	 * Construct a photo array for a system resource image
	 *
	 * @param string $filename Image file name relative to code root
	 * @param string $mimetype Image mime type. Is guessed by file name when empty.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createPhotoForSystemResource($filename, $mimetype = '')
	{
		if (empty($mimetype)) {
			$mimetype = Images::guessTypeByExtension($filename);
		}

		$fields = self::getFields();
		$values = array_fill(0, count($fields), "");

		$photo                  = array_combine($fields, $values);
		$photo['backend-class'] = SystemResource::NAME;
		$photo['backend-ref']   = $filename;
		$photo['type']          = $mimetype;
		$photo['cacheable']     = false;

		return $photo;
	}

	/**
	 * Construct a photo array for an external resource image
	 *
	 * @param string $url      Image URL
	 * @param int    $uid      User ID of the requesting person
	 * @param string $mimetype Image mime type. Is guessed by file name when empty.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createPhotoForExternalResource($url, $uid = 0, $mimetype = '')
	{
		if (empty($mimetype)) {
			$mimetype = Images::guessTypeByExtension($url);
		}

		$fields = self::getFields();
		$values = array_fill(0, count($fields), "");

		$photo                  = array_combine($fields, $values);
		$photo['backend-class'] = ExternalResource::NAME;
		$photo['backend-ref']   = json_encode(['url' => $url, 'uid' => $uid]);
		$photo['type']          = $mimetype;
		$photo['cacheable']     = true;

		return $photo;
	}

	/**
	 * store photo metadata in db and binary in default backend
	 *
	 * @param Image   $Image     Image object with data
	 * @param integer $uid       User ID
	 * @param integer $cid       Contact ID
	 * @param integer $rid       Resource ID
	 * @param string  $filename  Filename
	 * @param string  $album     Album name
	 * @param integer $scale     Scale
	 * @param integer $profile   Is a profile image? optional, default = 0
	 * @param string  $allow_cid Permissions, allowed contacts. optional, default = ""
	 * @param string  $allow_gid Permissions, allowed groups. optional, default = ""
	 * @param string  $deny_cid  Permissions, denied contacts.optional, default = ""
	 * @param string  $deny_gid  Permissions, denied greoup.optional, default = ""
	 * @param string  $desc      Photo caption. optional, default = ""
	 *
	 * @return boolean True on success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function store(Image $Image, $uid, $cid, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = "", $allow_gid = "", $deny_cid = "", $deny_gid = "", $desc = "")
	{
		$photo = self::selectFirst(["guid"], ["`resource-id` = ? AND `guid` != ?", $rid, ""]);
		if (DBA::isResult($photo)) {
			$guid = $photo["guid"];
		} else {
			$guid = System::createGUID();
		}

		$existing_photo = self::selectFirst(["id", "created", "backend-class", "backend-ref"], ["resource-id" => $rid, "uid" => $uid, "contact-id" => $cid, "scale" => $scale]);
		$created = DateTimeFormat::utcNow();
		if (DBA::isResult($existing_photo)) {
			$created = $existing_photo["created"];
		}

		// Get defined storage backend.
		// if no storage backend, we use old "data" column in photo table.
		// if is an existing photo, reuse same backend
		$data        = "";
		$backend_ref = "";
		$storage     = "";

		try {
			if (DBA::isResult($existing_photo)) {
				$backend_ref = (string)$existing_photo["backend-ref"];
				$storage     = DI::storageManager()->getWritableStorageByName($existing_photo["backend-class"] ?? '');
			} else {
				$storage = DI::storage();
			}
			$backend_ref = $storage->put($Image->asString(), $backend_ref);
		} catch (InvalidClassStorageException $storageException) {
			$data = $Image->asString();
		}

		$fields = [
			"uid" => $uid,
			"contact-id" => $cid,
			"guid" => $guid,
			"resource-id" => $rid,
			"hash" => md5($Image->asString()),
			"created" => $created,
			"edited" => DateTimeFormat::utcNow(),
			"filename" => basename($filename),
			"type" => $Image->getType(),
			"album" => $album,
			"height" => $Image->getHeight(),
			"width" => $Image->getWidth(),
			"datasize" => strlen($Image->asString()),
			"data" => $data,
			"scale" => $scale,
			"profile" => $profile,
			"allow_cid" => $allow_cid,
			"allow_gid" => $allow_gid,
			"deny_cid" => $deny_cid,
			"deny_gid" => $deny_gid,
			"desc" => $desc,
			"backend-class" => (string)$storage,
			"backend-ref" => $backend_ref
		];

		if (DBA::isResult($existing_photo)) {
			$r = DBA::update("photo", $fields, ["id" => $existing_photo["id"]]);
		} else {
			$r = DBA::insert("photo", $fields);
		}

		return $r;
	}


	/**
	 * Delete info from table and data from storage
	 *
	 * @param array $conditions Field condition(s)
	 * @param array $options    Options array, Optional
	 *
	 * @return boolean
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::delete
	 */
	public static function delete(array $conditions, array $options = [])
	{
		// get photo to delete data info
		$photos = DBA::select('photo', ['id', 'backend-class', 'backend-ref'], $conditions);

		while ($photo = DBA::fetch($photos)) {
			try {
				$backend_class = DI::storageManager()->getWritableStorageByName($photo['backend-class'] ?? '');
				$backend_class->delete($item['backend-ref'] ?? '');
				// Delete the photos after they had been deleted successfully
				DBA::delete("photo", ['id' => $photo['id']]);
			} catch (InvalidClassStorageException $storageException) {
				DI::logger()->debug('Storage class not found.', ['conditions' => $conditions, 'exception' => $storageException]);
			} catch (ReferenceStorageException $referenceStorageException) {
				DI::logger()->debug('Photo doesn\'t exist.', ['conditions' => $conditions, 'exception' => $referenceStorageException]);
			}
		}

		DBA::close($photos);

		return DBA::delete("photo", $conditions, $options);
	}

	/**
	 * Update a photo
	 *
	 * @param array         $fields     Contains the fields that are updated
	 * @param array         $conditions Condition array with the key values
	 * @param Image         $img        Image to update. Optional, default null.
	 * @param array|boolean $old_fields Array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean  Was the update successfull?
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   \Friendica\Database\DBA::update
	 */
	public static function update($fields, $conditions, Image $img = null, array $old_fields = [])
	{
		if (!is_null($img)) {
			// get photo to update
			$photos = self::selectToArray(['backend-class', 'backend-ref'], $conditions);

			foreach($photos as $photo) {
				try {
					$backend_class         = DI::storageManager()->getWritableStorageByName($photo['backend-class'] ?? '');
					$fields["backend-ref"] = $backend_class->put($img->asString(), $photo['backend-ref']);
				} catch (InvalidClassStorageException $storageException) {
					$fields["data"] = $img->asString();
				}
			}
			$fields['updated'] = DateTimeFormat::utcNow();
		}

		$fields['edited'] = DateTimeFormat::utcNow();

		return DBA::update("photo", $fields, $conditions, $old_fields);
	}

	/**
	 * @param string  $image_url     Remote URL
	 * @param integer $uid           user id
	 * @param integer $cid           contact id
	 * @param boolean $quit_on_error optional, default false
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function importProfilePhoto($image_url, $uid, $cid, $quit_on_error = false)
	{
		$thumb = "";
		$micro = "";

		$photo = DBA::selectFirst(
			"photo", ["resource-id"], ["uid" => $uid, "contact-id" => $cid, "scale" => 4, "album" => self::CONTACT_PHOTOS]
		);
		if (!empty($photo['resource-id'])) {
			$resource_id = $photo["resource-id"];
		} else {
			$resource_id = self::newResource();
		}

		$photo_failure = false;

		$filename = basename($image_url);
		if (!empty($image_url)) {
			$ret = DI::httpClient()->get($image_url);
			$img_str = $ret->getBody();
			$type = $ret->getContentType();
		} else {
			$img_str = '';
			$type = '';
		}

		if ($quit_on_error && ($img_str == "")) {
			return false;
		}

		$type = Images::getMimeTypeByData($img_str, $image_url, $type);

		$Image = new Image($img_str, $type);
		if ($Image->isValid()) {
			$Image->scaleToSquare(300);

			$filesize = strlen($Image->asString());
			$maximagesize = DI::config()->get('system', 'maximagesize');
			if (!empty($maximagesize) && ($filesize > $maximagesize)) {
				Logger::info('Avatar exceeds image limit', ['uid' => $uid, 'cid' => $cid, 'maximagesize' => $maximagesize, 'size' => $filesize, 'type' => $Image->getType()]);
				if ($Image->getType() == 'image/gif') {
					$Image->toStatic();
					$Image = new Image($Image->asString(), 'image/png');

					$filesize = strlen($Image->asString());
					Logger::info('Converted gif to a static png', ['uid' => $uid, 'cid' => $cid, 'size' => $filesize, 'type' => $Image->getType()]);
				}
				if ($filesize > $maximagesize) {
					foreach ([160, 80] as $pixels) {
						if ($filesize > $maximagesize) {
							Logger::info('Resize', ['uid' => $uid, 'cid' => $cid, 'size' => $filesize, 'max' => $maximagesize, 'pixels' => $pixels, 'type' => $Image->getType()]);
							$Image->scaleDown($pixels);
							$filesize = strlen($Image->asString());
						}
					}
				}
				Logger::info('Avatar is resized', ['uid' => $uid, 'cid' => $cid, 'size' => $filesize, 'type' => $Image->getType()]);
			}

			$r = self::store($Image, $uid, $cid, $resource_id, $filename, self::CONTACT_PHOTOS, 4);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(80);

			$r = self::store($Image, $uid, $cid, $resource_id, $filename, self::CONTACT_PHOTOS, 5);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(48);

			$r = self::store($Image, $uid, $cid, $resource_id, $filename, self::CONTACT_PHOTOS, 6);

			if ($r === false) {
				$photo_failure = true;
			}

			$suffix = "?ts=" . time();

			$image_url = DI::baseUrl() . "/photo/" . $resource_id . "-4." . $Image->getExt() . $suffix;
			$thumb = DI::baseUrl() . "/photo/" . $resource_id . "-5." . $Image->getExt() . $suffix;
			$micro = DI::baseUrl() . "/photo/" . $resource_id . "-6." . $Image->getExt() . $suffix;

			// Remove the cached photo
			$a = DI::app();
			$basepath = $a->getBasePath();

			if (is_dir($basepath . "/photo")) {
				$filename = $basepath . "/photo/" . $resource_id . "-4." . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . "/photo/" . $resource_id . "-5." . $Image->getExt();
				if (file_exists($filename)) {
					unlink($filename);
				}
				$filename = $basepath . "/photo/" . $resource_id . "-6." . $Image->getExt();
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
			$contact = Contact::getById($cid) ?: [];
			$image_url = Contact::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
			$thumb = Contact::getDefaultAvatar($contact, Proxy::SIZE_THUMB);
			$micro = Contact::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
		}

		return [$image_url, $thumb, $micro];
	}

	/**
	 * @param array $exifCoord coordinate
	 * @param string $hemi      hemi
	 * @return float
	 */
	public static function getGps($exifCoord, $hemi)
	{
		$degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == "W" || $hemi == "S") ? -1 : 1;

		return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
	}

	/**
	 * @param string $coordPart coordPart
	 * @return float
	 */
	private static function gps2Num($coordPart)
	{
		$parts = explode("/", $coordPart);

		if (count($parts) <= 0) {
			return 0;
		}

		if (count($parts) == 1) {
			return $parts[0];
		}

		return floatval($parts[0]) / floatval($parts[1]);
	}

	/**
	 * Fetch the photo albums that are available for a viewer
	 *
	 * The query in this function is cost intensive, so it is cached.
	 *
	 * @param int  $uid    User id of the photos
	 * @param bool $update Update the cache
	 *
	 * @return array Returns array of the photo albums
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getAlbums($uid, $update = false)
	{
		$sql_extra = Security::getPermissionsSQLByUserId($uid);

		$key = "photo_albums:".$uid.":".local_user().":".remote_user();
		$albums = DI::cache()->get($key);
		if (is_null($albums) || $update) {
			if (!DI::config()->get("system", "no_count", false)) {
				/// @todo This query needs to be renewed. It is really slow
				// At this time we just store the data in the cache
				$albums = q("SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`, ANY_VALUE(`created`) AS `created`
					FROM `photo`
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
					GROUP BY `album` ORDER BY `created` DESC",
					intval($uid),
					DBA::escape(self::CONTACT_PHOTOS),
					DBA::escape(DI::l10n()->t(self::CONTACT_PHOTOS))
				);
			} else {
				// This query doesn't do the count and is much faster
				$albums = q("SELECT DISTINCT(`album`), '' AS `total`
					FROM `photo` USE INDEX (`uid_album_scale_created`)
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra",
					intval($uid),
					DBA::escape(self::CONTACT_PHOTOS),
					DBA::escape(DI::l10n()->t(self::CONTACT_PHOTOS))
				);
			}
			DI::cache()->set($key, $albums, Duration::DAY);
		}
		return $albums;
	}

	/**
	 * @param int $uid User id of the photos
	 * @return void
	 * @throws \Exception
	 */
	public static function clearAlbumCache($uid)
	{
		$key = "photo_albums:".$uid.":".local_user().":".remote_user();
		DI::cache()->set($key, null, Duration::DAY);
	}

	/**
	 * Generate a unique photo ID.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function newResource()
	{
		return System::createGUID(32, false);
	}

	/**
	 * Extracts the rid from a local photo URI
	 *
	 * @param string $image_uri The URI of the photo
	 * @return string The rid of the photo, or an empty string if the URI is not local
	 */
	public static function ridFromURI(string $image_uri)
	{
		if (!stristr($image_uri, DI::baseUrl() . '/photo/')) {
			return '';
		}
		$image_uri = substr($image_uri, strrpos($image_uri, '/') + 1);
		$image_uri = substr($image_uri, 0, strpos($image_uri, '-'));
		if (!strlen($image_uri)) {
			return '';
		}
		return $image_uri;
	}

	/**
	 * Changes photo permissions that had been embedded in a post
	 *
	 * @todo This function currently does have some flaws:
	 * - Sharing a post with a forum will create a photo that only the forum can see.
	 * - Sharing a photo again that been shared non public before doesn't alter the permissions.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function setPermissionFromBody($body, $uid, $original_contact_id, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny)
	{
		// Simplify image codes
		$img_body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);
		$img_body = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", '[img]$1[/img]', $img_body);

		// Search for images
		if (!preg_match_all("/\[img\](.*?)\[\/img\]/", $img_body, $match)) {
			return false;
		}
		$images = $match[1];
		if (empty($images)) {
			return false;
		}

		foreach ($images as $image) {
			$image_rid = self::ridFromURI($image);
			if (empty($image_rid)) {
				continue;
			}

			// Ensure to only modify photos that you own
			$srch = '<' . intval($original_contact_id) . '>';

			$condition = [
				'allow_cid' => $srch, 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '',
				'resource-id' => $image_rid, 'uid' => $uid
			];
			if (!Photo::exists($condition)) {
				$photo = self::selectFirst(['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'uid'], ['resource-id' => $image_rid]);
				if (!DBA::isResult($photo)) {
					Logger::info('Image not found', ['resource-id' => $image_rid]);
				} else {
					Logger::info('Mismatching permissions', ['condition' => $condition, 'photo' => $photo]);
				}
				continue;
			}

			/**
			 * @todo Existing permissions need to be mixed with the new ones.
			 * Otherwise this creates problems with sharing the same picture multiple times
			 * Also check if $str_contact_allow does contain a public forum.
			 * Then set the permissions to public.
			 */

			self::setPermissionForRessource($image_rid, $uid, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		}

		return true;
	}

	/**
	 * Add permissions to photo ressource
	 * @todo mix with previous photo permissions
	 * 
	 * @param string $image_rid
	 * @param integer $uid
	 * @param string $str_contact_allow
	 * @param string $str_group_allow
	 * @param string $str_contact_deny
	 * @param string $str_group_deny
	 * @return void
	 */
	public static function setPermissionForRessource(string $image_rid, int $uid, string $str_contact_allow, string $str_group_allow, string $str_contact_deny, string $str_group_deny)
	{
		$fields = ['allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow,
		'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny,
		'accessible' => DI::pConfig()->get($uid, 'system', 'accessible-photos', false)];

		$condition = ['resource-id' => $image_rid, 'uid' => $uid];
		Logger::info('Set permissions', ['condition' => $condition, 'permissions' => $fields]);
		Photo::update($fields, $condition);
	}

	/**
	 * Strips known picture extensions from picture links
	 *
	 * @param string $name Picture link
	 * @return string stripped picture link
	 * @throws \Exception
	 */
	public static function stripExtension($name)
	{
		$name = str_replace([".jpg", ".png", ".gif"], ["", "", ""], $name);
		foreach (Images::supportedTypes() as $m => $e) {
			$name = str_replace("." . $e, "", $name);
		}
		return $name;
	}

	/**
	 * Fetch the guid and scale from picture links
	 *
	 * @param string $name Picture link
	 * @return array
	 */
	public static function getResourceData(string $name):array
	{
		$base = DI::baseUrl()->get();

		$guid = str_replace([Strings::normaliseLink($base), '/photo/'], '', Strings::normaliseLink($name));

		if (parse_url($guid, PHP_URL_SCHEME)) {
			return [];
		}

		$guid = self::stripExtension($guid);
		if (substr($guid, -2, 1) != "-") {
			return [];
		}

		$scale = intval(substr($guid, -1, 1));
		if (!is_numeric($scale)) {
			return [];
		}

		$guid = substr($guid, 0, -2);
		return ['guid' => $guid, 'scale' => $scale];
	}

	/**
	 * Tests if the picture link points to a locally stored picture
	 *
	 * @param string $name Picture link
	 * @return boolean
	 * @throws \Exception
	 */
	public static function isLocal($name)
	{
		return (bool)self::getIdForName($name);
	}

	/**
	 * Return the id of a local photo
	 *
	 * @param string $name Picture link
	 * @return int
	 */
	public static function getIdForName($name)
	{
		$data = self::getResourceData($name);
		if (empty($data)) {
			return 0;
		}

		$photo = DBA::selectFirst('photo', ['id'], ['resource-id' => $data['guid'], 'scale' => $data['scale']]);
		if (!empty($photo['id'])) {
			return $photo['id'];
		}
		return 0;
	}

	/**
	 * Tests if the link points to a locally stored picture page
	 *
	 * @param string $name Page link
	 * @return boolean
	 * @throws \Exception
	 */
	public static function isLocalPage($name)
	{
		$base = DI::baseUrl()->get();

		$guid = str_replace(Strings::normaliseLink($base), '', Strings::normaliseLink($name));
		$guid = preg_replace("=/photos/.*/image/(.*)=ism", '$1', $guid);
		if (empty($guid)) {
			return false;
		}

		return DBA::exists('photo', ['resource-id' => $guid]);
	}

	/**
	 * 
	 * @param int   $uid   User ID
	 * @param array $files uploaded file array
	 * @return array photo record
	 */
	public static function upload(int $uid, array $files)
	{
		Logger::info('starting new upload');

		$user = User::getOwnerDataById($uid);
		if (empty($user)) {
			Logger::notice('User not found', ['uid' => $uid]);
			return [];
		}

		if (empty($files)) {
			Logger::notice('Empty upload file');
			return [];
		}

		if (!empty($files['tmp_name'])) {
			if (is_array($files['tmp_name'])) {
				$src = $files['tmp_name'][0];
			} else {
				$src = $files['tmp_name'];
			}
		} else {
			$src = '';
		}

		if (!empty($files['name'])) {
			if (is_array($files['name'])) {
				$filename = basename($files['name'][0]);
			} else {
				$filename = basename($files['name']);
			}
		} else {
			$filename = '';
		}

		if (!empty($files['size'])) {
			if (is_array($files['size'])) {
				$filesize = intval($files['size'][0]);
			} else {
				$filesize = intval($files['size']);
			}
		} else {
			$filesize = 0;
		}

		if (!empty($files['type'])) {
			if (is_array($files['type'])) {
				$filetype = $files['type'][0];
			} else {
				$filetype = $files['type'];
			}
		} else {
			$filetype = '';
		}

		if (empty($src)) {
			Logger::notice('No source file name', ['uid' => $uid, 'files' => $files]);
			return [];
		}

		$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

		Logger::info('File upload', ['src' => $src, 'filename' => $filename, 'size' => $filesize, 'type' => $filetype]);

		$imagedata = @file_get_contents($src);
		$Image = new Image($imagedata, $filetype);
		if (!$Image->isValid()) {
			Logger::notice('Image is unvalid', ['uid' => $uid, 'files' => $files]);
			return [];
		}

		$Image->orient($src);
		@unlink($src);

		$max_length = DI::config()->get('system', 'max_image_length');
		if (!$max_length) {
			$max_length = MAX_IMAGE_LENGTH;
		}
		if ($max_length > 0) {
			$Image->scaleDown($max_length);
			$filesize = strlen($Image->asString());
			Logger::info('File upload: Scaling picture to new size', ['max-length' => $max_length]);
		}

		$width = $Image->getWidth();
		$height = $Image->getHeight();

		$maximagesize = DI::config()->get('system', 'maximagesize');

		if (!empty($maximagesize) && ($filesize > $maximagesize)) {
			// Scale down to multiples of 640 until the maximum size isn't exceeded anymore
			foreach ([5120, 2560, 1280, 640] as $pixels) {
				if (($filesize > $maximagesize) && (max($width, $height) > $pixels)) {
					Logger::info('Resize', ['size' => $filesize, 'width' => $width, 'height' => $height, 'max' => $maximagesize, 'pixels' => $pixels]);
					$Image->scaleDown($pixels);
					$filesize = strlen($Image->asString());
					$width = $Image->getWidth();
					$height = $Image->getHeight();
				}
			}
			if ($filesize > $maximagesize) {
				@unlink($src);
				Logger::notice('Image size is too big', ['size' => $filesize, 'max' => $maximagesize]);
				return [];
			}
		}

		$resource_id = Photo::newResource();
		$album       = DI::l10n()->t('Wall Photos');
		$defperm     = '<' . $user['id'] . '>';

		$smallest = 0;

		$r = Photo::store($Image, $user['uid'], 0, $resource_id, $filename, $album, 0, 0, $defperm);
		if (!$r) {
			Logger::notice('Photo could not be stored');
			return [];
		}

		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, $user['uid'], 0, $resource_id, $filename, $album, 1, 0, $defperm);
			if ($r) {
				$smallest = 1;
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, $user['uid'], 0, $resource_id, $filename, $album, 2, 0, $defperm);
			if ($r && ($smallest == 0)) {
				$smallest = 2;
			}
		}

		$condition = ['resource-id' => $resource_id];
		$photo = self::selectFirst(['id', 'datasize', 'width', 'height', 'type'], $condition, ['order' => ['width' => true]]);
		if (empty($photo)) {
			Logger::notice('Photo not found', ['condition' => $condition]);
			return [];
		}

		$picture = [];

		$picture['id']        = $photo['id'];
		$picture['size']      = $photo['datasize'];
		$picture['width']     = $photo['width'];
		$picture['height']    = $photo['height'];
		$picture['type']      = $photo['type'];
		$picture['albumpage'] = DI::baseUrl() . '/photos/' . $user['nickname'] . '/image/' . $resource_id;
		$picture['picture']   = DI::baseUrl() . '/photo/{$resource_id}-0.' . $Image->getExt();
		$picture['preview']   = DI::baseUrl() . '/photo/{$resource_id}-{$smallest}.' . $Image->getExt();

		Logger::info('upload done', ['picture' => $picture]);
		return $picture;
	}
}
