<?php

/**
 * @file src/Model/Photo.php
 * @brief This file contains the Photo class for database interface
 */
namespace Friendica\Model;

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\StorageManager;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Storage\IStorage;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Security;
use Friendica\Util\Strings;

require_once "include/dba.php";

/**
 * Class to handle photo dabatase table
 */
class Photo
{
	/**
	 * @brief Select rows from the photo table and returns them as array
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
	 * @brief Retrieve a single record from the photo table
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
	 * @brief Get photos for user id
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
	 * @brief Get a photo for user id
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
	 * @brief Get a single photo given resource id and scale
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
	public static function getPhoto($resourceid, $scale = 0)
	{
		$r = self::selectFirst(["uid"], ["resource-id" => $resourceid]);
		if (!DBA::isResult($r)) {
			return false;
		}

		$uid = $r["uid"];

		$sql_acl = Security::getPermissionsSQLByUserId($uid);

		$conditions = ["`resource-id` = ? AND `scale` <= ? " . $sql_acl, $resourceid, $scale];
		$params = ["order" => ["scale" => true]];
		$photo = self::selectFirst([], $conditions, $params);

		return $photo;
	}

	/**
	 * @brief Check if photo with given conditions exists
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
	 * @brief Get Image object for given row id. null if row id does not exist
	 *
	 * @param array $photo Photo data. Needs at least 'id', 'type', 'backend-class', 'backend-ref'
	 *
	 * @return \Friendica\Object\Image
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getImageForPhoto(array $photo)
	{
		$data = "";

		if ($photo["backend-class"] == "") {
			// legacy data storage in "data" column
			$i = self::selectFirst(["data"], ["id" => $photo["id"]]);
			if ($i === false) {
				return null;
			}
			$data = $i["data"];
		} else {
			$backendClass = $photo["backend-class"];
			$backendRef = $photo["backend-ref"];
			$data = $backendClass::get($backendRef);
		}

		if ($data === "") {
			return null;
		}

		return new Image($data, $photo["type"]);
	}

	/**
	 * @brief Return a list of fields that are associated with the photo table
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
	 * @brief Construct a photo array for a system resource image
	 *
	 * @param string $filename Image file name relative to code root
	 * @param string $mimetype Image mime type. Defaults to "image/jpeg"
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createPhotoForSystemResource($filename, $mimetype = "image/jpeg")
	{
		$fields = self::getFields();
		$values = array_fill(0, count($fields), "");

		$photo = array_combine($fields, $values);
		$photo["backend-class"] = Storage\SystemResource::class;
		$photo["backend-ref"] = $filename;
		$photo["type"] = $mimetype;
		$photo["cacheable"] = false;

		return $photo;
	}


	/**
	 * @brief store photo metadata in db and binary in default backend
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
		$data = "";
		$backend_ref = "";

		/** @var IStorage $backend_class */
		if (DBA::isResult($existing_photo)) {
			$backend_ref = (string)$existing_photo["backend-ref"];
			$backend_class = (string)$existing_photo["backend-class"];
		} else {
			$backend_class = StorageManager::getBackend();
		}

		if ($backend_class === "") {
			$data = $Image->asString();
		} else {
			$backend_ref = $backend_class::put($Image->asString(), $backend_ref);
		}


		$fields = [
			"uid" => $uid,
			"contact-id" => $cid,
			"guid" => $guid,
			"resource-id" => $rid,
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
			"backend-class" => $backend_class,
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
	 * @brief Delete info from table and data from storage
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
		$photos = self::selectToArray(['backend-class', 'backend-ref'], $conditions);

		foreach($photos as $photo) {
			/** @var IStorage $backend_class */
			$backend_class = (string)$photo["backend-class"];
			if ($backend_class !== "") {
				$backend_class::delete($photo["backend-ref"]);
			}
		}

		return DBA::delete("photo", $conditions, $options);
	}

	/**
	 * @brief Update a photo
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
				/** @var IStorage $backend_class */
				$backend_class = (string)$photo["backend-class"];
				if ($backend_class !== "") {
					$fields["backend-ref"] = $backend_class::put($img->asString(), $photo["backend-ref"]);
				} else {
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
			"photo", ["resource-id"], ["uid" => $uid, "contact-id" => $cid, "scale" => 4, "album" => "Contact Photos"]
		);
		if (!empty($photo['resource-id'])) {
			$resource_id = $photo["resource-id"];
		} else {
			$resource_id = self::newResource();
		}

		$photo_failure = false;

		$filename = basename($image_url);
		if (!empty($image_url)) {
			$ret = Network::curl($image_url, true);
			$img_str = $ret->getBody();
			$type = $ret->getContentType();
		} else {
			$img_str = '';
		}

		if ($quit_on_error && ($img_str == "")) {
			return false;
		}

		if (empty($type)) {
			$type = Images::guessType($image_url, true);
		}

		$Image = new Image($img_str, $type);
		if ($Image->isValid()) {
			$Image->scaleToSquare(300);

			$r = self::store($Image, $uid, $cid, $resource_id, $filename, "Contact Photos", 4);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(80);

			$r = self::store($Image, $uid, $cid, $resource_id, $filename, "Contact Photos", 5);

			if ($r === false) {
				$photo_failure = true;
			}

			$Image->scaleDown(48);

			$r = self::store($Image, $uid, $cid, $resource_id, $filename, "Contact Photos", 6);

			if ($r === false) {
				$photo_failure = true;
			}

			$suffix = "?ts=" . time();

			$image_url = System::baseUrl() . "/photo/" . $resource_id . "-4." . $Image->getExt() . $suffix;
			$thumb = System::baseUrl() . "/photo/" . $resource_id . "-5." . $Image->getExt() . $suffix;
			$micro = System::baseUrl() . "/photo/" . $resource_id . "-6." . $Image->getExt() . $suffix;

			// Remove the cached photo
			$a = \get_app();
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
			$image_url = System::baseUrl() . "/images/person-300.jpg";
			$thumb = System::baseUrl() . "/images/person-80.jpg";
			$micro = System::baseUrl() . "/images/person-48.jpg";
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
	 * @brief Fetch the photo albums that are available for a viewer
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
		$albums = Cache::get($key);
		if (is_null($albums) || $update) {
			if (!Config::get("system", "no_count", false)) {
				/// @todo This query needs to be renewed. It is really slow
				// At this time we just store the data in the cache
				$albums = q("SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`, ANY_VALUE(`created`) AS `created`
					FROM `photo`
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
					GROUP BY `album` ORDER BY `created` DESC",
					intval($uid),
					DBA::escape("Contact Photos"),
					DBA::escape(L10n::t("Contact Photos"))
				);
			} else {
				// This query doesn't do the count and is much faster
				$albums = q("SELECT DISTINCT(`album`), '' AS `total`
					FROM `photo` USE INDEX (`uid_album_scale_created`)
					WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra",
					intval($uid),
					DBA::escape("Contact Photos"),
					DBA::escape(L10n::t("Contact Photos"))
				);
			}
			Cache::set($key, $albums, Cache::DAY);
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
		Cache::set($key, null, Cache::DAY);
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
			if (!stristr($image, System::baseUrl() . '/photo/')) {
				continue;
			}
			$image_uri = substr($image,strrpos($image,'/') + 1);
			$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
			if (!strlen($image_uri)) {
				continue;
			}

			// Ensure to only modify photos that you own
			$srch = '<' . intval($original_contact_id) . '>';

			$condition = [
				'allow_cid' => $srch, 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '',
				'resource-id' => $image_uri, 'uid' => $uid
			];
			if (!Photo::exists($condition)) {
				continue;
			}

			/// @todo Check if $str_contact_allow does contain a public forum. Then set the permissions to public.

			$fields = ['allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow,
					'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny];
			$condition = ['resource-id' => $image_uri, 'uid' => $uid];
			Logger::info('Set permissions', ['condition' => $condition, 'permissions' => $fields]);
			Photo::update($fields, $condition);
		}

		return true;
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
	 * Returns the GUID from picture links
	 *
	 * @param string $name Picture link
	 * @return string GUID
	 * @throws \Exception
	 */
	public static function getGUID($name)
	{
		$a = \get_app();
		$base = $a->getBaseURL();

		$guid = str_replace([Strings::normaliseLink($base), '/photo/'], '', Strings::normaliseLink($name));

		$guid = self::stripExtension($guid);
		if (substr($guid, -2, 1) != "-") {
			return '';
		}

		$scale = intval(substr($guid, -1, 1));
		if (!is_numeric($scale)) {
			return '';
		}

		$guid = substr($guid, 0, -2);
		return $guid;
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
		$guid = self::getGUID($name);

		if (empty($guid)) {
			return false;
		}

		return DBA::exists('photo', ['resource-id' => $guid]);
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
		$a = \get_app();
		$base = $a->getBaseURL();

		$guid = str_replace(Strings::normaliseLink($base), '', Strings::normaliseLink($name));
		$guid = preg_replace("=/photos/.*/image/(.*)=ism", '$1', $guid);
		if (empty($guid)) {
			return false;
		}

		return DBA::exists('photo', ['resource-id' => $guid]);
	}
}
