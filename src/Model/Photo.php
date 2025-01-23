<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Core\Storage\Type\ExternalResource;
use Friendica\Core\Storage\Exception\InvalidClassStorageException;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Type\SystemResource;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Security\Security;
use Friendica\Util\Network;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;

/**
 * Class to handle photo database table
 */
class Photo
{
	const CONTACT_PHOTOS = 'Contact Photos';
	const PROFILE_PHOTOS = 'Profile Photos';
	const BANNER_PHOTOS  = 'Banner Photos';

	const DEFAULT        = 0;
	const USER_AVATAR    = 10;
	const USER_BANNER    = 11;
	const CONTACT_AVATAR = 20;
	const CONTACT_BANNER = 21;

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

		return DBA::selectFirst('photo', $fields, $conditions, $params);
	}

	/**
	 * Get photos for user id
	 *
	 * @param integer $uid        User id
	 * @param string  $resourceid Resource ID of the photo
	 * @param array   $conditions Array of fields for conditions
	 * @param array   $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getPhotosForUser(int $uid, string $resourceid, array $conditions = [], array $params = [])
	{
		$conditions['resource-id'] = $resourceid;
		$conditions['uid']         = $uid;

		return self::selectToArray([], $conditions, $params);
	}

	/**
	 * Get a photo for user id
	 *
	 * @param integer $uid        User id
	 * @param string  $resourceid Resource ID of the photo
	 * @param integer $scale      Scale of the photo. Defaults to 0
	 * @param array   $conditions Array of fields for conditions
	 * @param array   $params     Array of several parameters
	 *
	 * @return bool|array
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::select
	 */
	public static function getPhotoForUser(int $uid, $resourceid, $scale = 0, array $conditions = [], array $params = [])
	{
		$conditions['resource-id'] = $resourceid;
		$conditions['uid']         = $uid;
		$conditions['scale']       = $scale;

		return self::selectFirst([], $conditions, $params);
	}

	/**
	 * Get a single photo given resource id and scale
	 *
	 * This method checks for permissions. Returns associative array
	 * on success, "no sign" image info, if user has no permission,
	 * false if photo does not exists
	 *
	 * @param string  $resourceid  Resource ID of the photo
	 * @param integer $scale       Scale of the photo. Defaults to 0
	 * @param integer $visitor_uid UID of the visitor
	 *
	 * @return boolean|array
	 * @throws \Exception
	 */
	public static function getPhoto(string $resourceid, int $scale = 0, int $visitor_uid = 0)
	{
		$r = self::selectFirst(['uid'], ['resource-id' => $resourceid]);
		if (!DBA::isResult($r)) {
			return false;
		}

		$uid = $r['uid'];

		$accessible = $uid ? (bool)DI::pConfig()->get($uid, 'system', 'accessible-photos', false) : false;

		if (!empty($visitor_uid) && ($uid == $visitor_uid)) {
			$sql_acl = '';
		} else {
			$sql_acl = Security::getPermissionsSQLByUserId($uid, $accessible);
		}

		$conditions = ["`resource-id` = ? AND `scale` <= ? " . $sql_acl, $resourceid, $scale];
		$params     = ['order' => ['scale' => true]];
		$photo      = self::selectFirst([], $conditions, $params);

		return $photo;
	}

	/**
	 * Returns all browsable albums for a given user
	 *
	 * @param int $uid The given user
	 *
	 * @return array An array of albums
	 * @throws \Exception
	 */
	public static function getBrowsableAlbumsForUser(int $uid): array
	{
		$photos = DBA::toArray(
			DBA::p(
				"SELECT DISTINCT(`album`) AS `album` FROM `photo` WHERE `uid` = ? AND NOT `photo-type` IN (?, ?)",
				$uid,
				static::CONTACT_AVATAR,
				static::CONTACT_BANNER
			)
		);

		return array_column($photos, 'album');
	}

	/**
	 * Returns browsable photos for a given user (optional and a given album)
	 *
	 * @param int         $uid   The given user id
	 * @param string|null $album (optional) The given album
	 *
	 * @return array All photos of the user/album
	 * @throws \Exception
	 */
	public static function getBrowsablePhotosForUser(int $uid, string $album = null): array
	{
		$values = [
			$uid,
			Photo::CONTACT_AVATAR,
			Photo::CONTACT_BANNER
		];

		if (!empty($album)) {
			$sqlExtra  = "AND `album` = ? ";
			$values[]  = $album;
			$sqlExtra2 = "";
		} else {
			$sqlExtra  = '';
			$sqlExtra2 = ' ORDER BY created DESC LIMIT 0, 10';
		}

		return DBA::toArray(
			DBA::p(
				"SELECT `resource-id`, MIN(`id`) AS `id`, MIN(`filename`) AS `filename`, MIN(`type`) AS `type`,
					min(`scale`) AS `hiq`, max(`scale`) AS `loq`, MIN(`desc`) AS `desc`, MIN(`created`) AS `created`
					FROM `photo` WHERE `uid` = ? AND NOT `photo-type` IN (?, ?) $sqlExtra
					GROUP BY `resource-id` $sqlExtra2",
				$values
			)
		);
	}

	/**
	 * Check if photo with given conditions exists
	 *
	 * @param array $conditions Array of extra conditions
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public static function exists(array $conditions): bool
	{
		return DBA::exists('photo', $conditions);
	}


	/**
	 * Get Image data for given row id. null if row id does not exist
	 *
	 * @param array $photo Photo data. Needs at least 'id', 'type', 'backend-class', 'backend-ref'
	 *
	 * @return \Friendica\Object\Image|null Image object or null on error
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
	private static function getFields(): array
	{
		$allfields = DI::dbaDefinition()->getAll();
		$fields    = array_keys($allfields['photo']['fields']);
		array_splice($fields, array_search('data', $fields), 1);
		return $fields;
	}

	/**
	 * Construct a photo array for a given image data string
	 *
	 * @param string $image_data Image data
	 * @param string $mimetype   Image mime type. Is guessed by file name when empty.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createPhotoForImageData(string $image_data, string $mimetype = ''): array
	{
		$fields = self::getFields();
		$values = array_fill(0, count($fields), '');

		$photo = array_combine($fields, $values);

		$photo['data']      = $image_data;
		$photo['type']      = $mimetype ?: Images::getMimeTypeByData($image_data);
		$photo['cacheable'] = false;

		return $photo;
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
	public static function createPhotoForSystemResource(string $filename, string $mimetype = ''): array
	{
		if (empty($mimetype)) {
			$mimetype = Images::guessTypeByExtension($filename);
		}

		$fields = self::getFields();
		$values = array_fill(0, count($fields), '');

		$photo                  = array_combine($fields, $values);
		$photo['backend-class'] = SystemResource::NAME;
		$photo['backend-ref']   = $filename;
		$photo['type']          = $mimetype;
		$photo['filename']      = basename($filename);
		$photo['cacheable']     = false;

		return $photo;
	}

	/**
	 * Construct a photo array for an external resource image
	 *
	 * @param string $url      Image URL
	 * @param int    $uid      User ID of the requesting person
	 * @param string $mimetype Image mime type. Is guessed by file name when empty.
	 * @param string $blurhash The blurhash that will be used to generate a picture when the original picture can't be fetched
	 * @param int    $width    Image width
	 * @param int    $height   Image height
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createPhotoForExternalResource(string $url, int $uid = 0, string $mimetype = '', string $blurhash = null, int $width = null, int $height = null): array
	{
		if (empty($mimetype)) {
			$mimetype = Images::guessTypeByExtension($url);
		}

		$fields = self::getFields();
		$values = array_fill(0, count($fields), '');

		$photo                  = array_combine($fields, $values);
		$photo['backend-class'] = ExternalResource::NAME;
		$photo['backend-ref']   = json_encode(['url' => $url, 'uid' => $uid]);
		$photo['type']          = $mimetype;
		$photo['filename']      = basename(parse_url($url, PHP_URL_PATH));
		$photo['cacheable']     = true;
		$photo['blurhash']      = $blurhash;
		$photo['width']         = $width;
		$photo['height']        = $height;

		return $photo;
	}

	/**
	 * store photo metadata in db and binary in default backend
	 *
	 * @param Image   $image     Image object with data
	 * @param integer $uid       User ID
	 * @param integer $cid       Contact ID
	 * @param string  $rid       Resource ID
	 * @param string  $filename  Filename
	 * @param string  $album     Album name
	 * @param integer $scale     Scale
	 * @param integer $type      Photo type, optional, default: Photo::DEFAULT
	 * @param string  $allow_cid Permissions, allowed contacts. optional, default = ""
	 * @param string  $allow_gid Permissions, allowed circles. optional, default = ""
	 * @param string  $deny_cid  Permissions, denied contacts. optional, default = ""
	 * @param string  $deny_gid  Permissions, denied circle. optional, default = ""
	 * @param string  $desc      Photo caption. optional, default = ""
	 *
	 * @return boolean True on success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function store(Image $image, int $uid, int $cid, string $rid, string $filename, string $album, int $scale, int $type = self::DEFAULT, string $allow_cid = '', string $allow_gid = '', string $deny_cid = '', string $deny_gid = '', string $desc = ''): bool
	{
		$photo = self::selectFirst(['guid'], ["`resource-id` = ? AND `guid` != ?", $rid, '']);
		if (DBA::isResult($photo)) {
			$guid = $photo['guid'];
		} else {
			$guid = System::createGUID();
		}

		$existing_photo = self::selectFirst(['id', 'created', 'backend-class', 'backend-ref'], ['resource-id' => $rid, 'uid' => $uid, 'contact-id' => $cid, 'scale' => $scale]);
		$created        = DateTimeFormat::utcNow();
		if (DBA::isResult($existing_photo)) {
			$created = $existing_photo['created'];
		}

		// Get defined storage backend.
		// if no storage backend, we use old "data" column in photo table.
		// if is an existing photo, reuse same backend
		$data        = '';
		$backend_ref = '';
		$storage     = '';
		$img_str     = $image->asString();

		try {
			if (DBA::isResult($existing_photo)) {
				$backend_ref = (string)$existing_photo['backend-ref'];
				$storage     = DI::storageManager()->getWritableStorageByName($existing_photo['backend-class'] ?? '');
			} else {
				$storage = DI::storage();
			}
			$backend_ref = $storage->put($img_str, $backend_ref);
		} catch (InvalidClassStorageException $storageException) {
			$data = $img_str;
		}

		$fields = [
			'uid'           => $uid,
			'contact-id'    => $cid,
			'guid'          => $guid,
			'resource-id'   => $rid,
			'hash'          => md5($img_str),
			'created'       => $created,
			'edited'        => DateTimeFormat::utcNow(),
			'filename'      => basename($filename),
			'type'          => $image->getType(),
			'album'         => $album,
			'height'        => $image->getHeight(),
			'width'         => $image->getWidth(),
			'datasize'      => strlen($img_str),
			'blurhash'      => $image->getBlurHash($img_str),
			'data'          => $data,
			'scale'         => $scale,
			'photo-type'    => $type,
			'profile'       => false,
			'allow_cid'     => $allow_cid,
			'allow_gid'     => $allow_gid,
			'deny_cid'      => $deny_cid,
			'deny_gid'      => $deny_gid,
			'desc'          => $desc,
			'backend-class' => (string)$storage,
			'backend-ref'   => $backend_ref
		];

		$fields = DI::dbaDefinition()->truncateFieldsForTable('photo', $fields);

		if (DBA::isResult($existing_photo)) {
			$r = DBA::update('photo', $fields, ['id' => $existing_photo['id']]);
		} else {
			$r = DBA::insert('photo', $fields);
		}

		return $r;
	}


	/**
	 * Delete info from table and data from storage
	 *
	 * @param array $conditions Field condition(s)
	 *
	 * @throws \Exception
	 * @see   \Friendica\Database\DBA::delete
	 */
	public static function delete(array $conditions): bool
	{
		// get photo to delete data info
		$photos = DBA::select('photo', ['id', 'backend-class', 'backend-ref'], $conditions);

		while ($photo = DBA::fetch($photos)) {
			try {
				$backend_class = DI::storageManager()->getWritableStorageByName($photo['backend-class'] ?? '');
				$backend_class->delete($photo['backend-ref'] ?? '');
				// Delete the photos after they had been deleted successfully
				DBA::delete('photo', ['id' => $photo['id']]);
			} catch (InvalidClassStorageException $storageException) {
				DI::logger()->debug('Storage class not found.', ['conditions' => $conditions, 'exception' => $storageException]);
			} catch (ReferenceStorageException $referenceStorageException) {
				DI::logger()->debug('Photo doesn\'t exist.', ['conditions' => $conditions, 'exception' => $referenceStorageException]);
			}
		}

		DBA::close($photos);

		return DBA::delete('photo', $conditions);
	}

	/**
	 * Update a photo
	 *
	 * @param array $fields     Contains the fields that are updated
	 * @param array $conditions Condition array with the key values
	 * @param Image $image      Image to update. Optional, default null.
	 * @param array $old_fields Array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean  Was the update successful?
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @see   \Friendica\Database\DBA::update
	 */
	public static function update(array $fields, array $conditions, Image $image = null, array $old_fields = []): bool
	{
		if (!is_null($image)) {
			// get photo to update
			$photos = self::selectToArray(['backend-class', 'backend-ref'], $conditions);

			foreach ($photos as $photo) {
				try {
					$backend_class         = DI::storageManager()->getWritableStorageByName($photo['backend-class'] ?? '');
					$fields['backend-ref'] = $backend_class->put($image->asString(), $photo['backend-ref']);
				} catch (InvalidClassStorageException $storageException) {
					$fields['data'] = $image->asString();
				}
			}
			$fields['updated'] = DateTimeFormat::utcNow();
		}

		$fields['edited'] = DateTimeFormat::utcNow();

		return DBA::update('photo', $fields, $conditions, $old_fields);
	}

	/**
	 * @param string  $image_url     Remote URL
	 * @param integer $uid           user id
	 * @param integer $cid           contact id
	 * @param boolean $quit_on_error optional, default false
	 * @return array|bool Array on success, false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function importProfilePhoto(string $image_url, int $uid, int $cid, bool $quit_on_error = false)
	{
		$thumb = '';
		$micro = '';

		$photo = DBA::selectFirst(
			'photo',
			['resource-id'],
			['uid' => $uid, 'contact-id' => $cid, 'scale' => 4, 'photo-type' => self::CONTACT_AVATAR]
		);
		if (!empty($photo['resource-id'])) {
			$resource_id = $photo['resource-id'];
		} else {
			$resource_id = self::newResource();
		}

		$photo_failure = false;

		if (!Network::isValidHttpUrl($image_url)) {
			DI::logger()->warning('Invalid image url', ['image_url' => $image_url, 'uid' => $uid, 'cid' => $cid]);
			return false;
		}

		$filename = basename($image_url);
		if (!empty($image_url)) {
			try {
				$ret = DI::httpClient()->get($image_url, HttpClientAccept::IMAGE, [HttpClientOptions::REQUEST => HttpClientRequest::MEDIAPROXY]);
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
				return false;
			}
			DI::logger()->debug('Got picture', ['Content-Type' => $ret->getHeader('Content-Type'), 'url' => $image_url]);
			$img_str = $ret->getBodyString();
			$type    = $ret->getContentType();
		} else {
			$img_str = '';
			$type    = '';
		}

		if ($quit_on_error && ($img_str == '')) {
			return false;
		}

		$image = new Image($img_str, $type, $image_url);
		if ($image->isValid()) {
			$image->scaleToSquare(300);

			$filesize     = strlen($image->asString());
			$maximagesize = Strings::getBytesFromShorthand(DI::config()->get('system', 'maximagesize'));

			if ($maximagesize && ($filesize > $maximagesize)) {
				DI::logger()->info('Avatar exceeds image limit', ['uid' => $uid, 'cid' => $cid, 'maximagesize' => $maximagesize, 'size' => $filesize, 'type' => $image->getType()]);
				if ($image->getImageType() == IMAGETYPE_GIF) {
					$image->toStatic();
					$image = new Image($image->asString(), image_type_to_mime_type(IMAGETYPE_PNG));

					$filesize = strlen($image->asString());
					DI::logger()->info('Converted gif to a static png', ['uid' => $uid, 'cid' => $cid, 'size' => $filesize, 'type' => $image->getType()]);
				}
				if ($filesize > $maximagesize) {
					foreach ([160, 80] as $pixels) {
						if ($filesize > $maximagesize) {
							DI::logger()->info('Resize', ['uid' => $uid, 'cid' => $cid, 'size' => $filesize, 'max' => $maximagesize, 'pixels' => $pixels, 'type' => $image->getType()]);
							$image->scaleDown($pixels);
							$filesize = strlen($image->asString());
						}
					}
				}
				DI::logger()->info('Avatar is resized', ['uid' => $uid, 'cid' => $cid, 'size' => $filesize, 'type' => $image->getType()]);
			}

			$r = self::store($image, $uid, $cid, $resource_id, $filename, self::CONTACT_PHOTOS, 4, self::CONTACT_AVATAR);

			if ($r === false) {
				$photo_failure = true;
			}

			$image->scaleDown(80);

			$r = self::store($image, $uid, $cid, $resource_id, $filename, self::CONTACT_PHOTOS, 5, self::CONTACT_AVATAR);

			if ($r === false) {
				$photo_failure = true;
			}

			$image->scaleDown(48);

			$r = self::store($image, $uid, $cid, $resource_id, $filename, self::CONTACT_PHOTOS, 6, self::CONTACT_AVATAR);

			if ($r === false) {
				$photo_failure = true;
			}

			$suffix = '?ts=' . time();

			$image_url = DI::baseUrl() . '/photo/' . $resource_id . '-4' . $image->getExt() . $suffix;
			$thumb     = DI::baseUrl() . '/photo/' . $resource_id . '-5' . $image->getExt() . $suffix;
			$micro     = DI::baseUrl() . '/photo/' . $resource_id . '-6' . $image->getExt() . $suffix;
		} else {
			$photo_failure = true;
		}

		if ($photo_failure && $quit_on_error) {
			return false;
		}

		if ($photo_failure) {
			$contact   = Contact::getById($cid) ?: [];
			$image_url = Contact::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
			$thumb     = Contact::getDefaultAvatar($contact, Proxy::SIZE_THUMB);
			$micro     = Contact::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
		}

		$photo = DBA::selectFirst(
			'photo',
			['blurhash'],
			['uid' => $uid, 'contact-id' => $cid, 'scale' => 4, 'photo-type' => self::CONTACT_AVATAR]
		);

		return [$image_url, $thumb, $micro, $photo['blurhash']];
	}

	/**
	 * Returns a float that represents the GPS coordinate from EXIF data
	 *
	 * @param array $exifCoord coordinate
	 * @param string $hemi      hemi
	 * @return float
	 */
	public static function getGps(array $exifCoord, string $hemi): float
	{
		$degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == 'W' || $hemi == 'S') ? -1 : 1;

		return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
	}

	/**
	 * Change GPS to float number
	 *
	 * @param string $coordPart coordPart
	 * @return float
	 */
	private static function gps2Num(string $coordPart): float
	{
		$parts = explode('/', $coordPart);

		if (count($parts) <= 0) {
			return 0;
		}

		if (count($parts) == 1) {
			return (float)$parts[0];
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
	public static function getAlbums(int $uid, bool $update = false): array
	{
		$sql_extra = Security::getPermissionsSQLByUserId($uid);

		$avatar_type = (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserId() == $uid)) ? self::USER_AVATAR : self::DEFAULT;
		$banner_type = (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserId() == $uid)) ? self::USER_BANNER : self::DEFAULT;

		$key    = 'photo_albums:' . $uid . ':' . DI::userSession()->getLocalUserId() . ':' . DI::userSession()->getRemoteUserId();
		$albums = DI::cache()->get($key);

		if (is_null($albums) || $update) {
			if (!DI::config()->get('system', 'no_count', false)) {
				/// @todo This query needs to be renewed. It is really slow
				// At this time we just store the data in the cache
				$albums = DBA::toArray(DBA::p(
					"SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`, MIN(`created`) AS `created`
					FROM `photo`
					WHERE `uid` = ? AND `photo-type` IN (?, ?, ?) $sql_extra
					GROUP BY `album` ORDER BY `created` DESC",
					$uid,
					self::DEFAULT,
					$banner_type,
					$avatar_type
				));
			} else {
				// This query doesn't do the count and is much faster
				$albums = DBA::toArray(DBA::p(
					"SELECT '' AS `total`, `album`, MIN(`created`) AS `created`
					FROM `photo` USE INDEX (`uid_album_scale_created`)
					WHERE `uid` = ? AND `photo-type` IN (?, ?, ?) $sql_extra
					GROUP BY `album` ORDER BY `created` DESC",
					$uid,
					self::DEFAULT,
					$banner_type,
					$avatar_type
				));
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
	public static function clearAlbumCache(int $uid)
	{
		$key = 'photo_albums:' . $uid . ':' . DI::userSession()->getLocalUserId() . ':' . DI::userSession()->getRemoteUserId();
		DI::cache()->set($key, null, Duration::DAY);
	}

	/**
	 * Generate a unique photo ID.
	 *
	 * @return string Resource GUID
	 * @throws \Exception
	 */
	public static function newResource(): string
	{
		return System::createGUID(32, false);
	}

	/**
	 * Extracts the rid from a local photo URI
	 *
	 * @param string $image_uri The URI of the photo
	 * @return string The rid of the photo, or an empty string if the URI is not local
	 */
	public static function ridFromURI(string $image_uri): string
	{
		if (!stristr($image_uri, DI::baseUrl() . '/photo/')) {
			return '';
		}
		$image_uri = substr($image_uri, strrpos($image_uri, '/') + 1);
		$image_uri = substr($image_uri, 0, strpos($image_uri, '-'));
		return trim($image_uri);
	}

	/**
	 * Checks if the given URL is a local photo.
	 * Since it is meant for time critical occasions, the check is done without any database requests.
	 *
	 * @param string $url
	 * @return boolean
	 */
	public static function isPhotoURI(string $url): bool
	{
		return !empty(self::ridFromURI($url));
	}

	/**
	 * Changes photo permissions that had been embedded in a post
	 *
	 * @todo This function currently does have some flaws:
	 * - Sharing a post with a group will create a photo that only the group can see.
	 * - Sharing a photo again that been shared non public before doesn't alter the permissions.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function setPermissionFromBody($body, $uid, $original_contact_id, $str_contact_allow, $str_circle_allow, $str_contact_deny, $str_circle_deny)
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
				'allow_cid'   => $srch, 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '',
				'resource-id' => $image_rid, 'uid' => $uid
			];
			if (!self::exists($condition)) {
				$photo = self::selectFirst(['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'uid'], ['resource-id' => $image_rid]);
				if (!DBA::isResult($photo)) {
					DI::logger()->info('Image not found', ['resource-id' => $image_rid]);
				} else {
					DI::logger()->info('Mismatching permissions', ['condition' => $condition, 'photo' => $photo]);
				}
				continue;
			}

			/**
			 * @todo Existing permissions need to be mixed with the new ones.
			 * Otherwise this creates problems with sharing the same picture multiple times
			 * Also check if $str_contact_allow does contain a public group.
			 * Then set the permissions to public.
			 */

			self::setPermissionForResource($image_rid, $uid, $str_contact_allow, $str_circle_allow, $str_contact_deny, $str_circle_deny);
		}

		return true;
	}

	/**
	 * Add permissions to photo resource
	 * @todo mix with previous photo permissions
	 *
	 * @param string $image_rid
	 * @param integer $uid
	 * @param string $str_contact_allow
	 * @param string $str_circle_allow
	 * @param string $str_contact_deny
	 * @param string $str_circle_deny
	 * @return void
	 */
	public static function setPermissionForResource(string $image_rid, int $uid, string $str_contact_allow, string $str_circle_allow, string $str_contact_deny, string $str_circle_deny)
	{
		$fields = [
			'allow_cid'  => $str_contact_allow, 'allow_gid' => $str_circle_allow,
			'deny_cid'   => $str_contact_deny, 'deny_gid' => $str_circle_deny,
			'accessible' => DI::pConfig()->get($uid, 'system', 'accessible-photos', false)
		];

		$condition = ['resource-id' => $image_rid, 'uid' => $uid];
		DI::logger()->info('Set permissions', ['condition' => $condition, 'permissions' => $fields]);
		self::update($fields, $condition);
	}

	/**
	 * Fetch the guid and scale from picture links
	 *
	 * @param string $name Picture link
	 * @return array
	 */
	public static function getResourceData(string $name): array
	{
		$guid = str_replace([Strings::normaliseLink((string)DI::baseUrl()), '/photo/'], '', Strings::normaliseLink($name));

		if (parse_url($guid, PHP_URL_SCHEME)) {
			return [];
		}

		$guid = pathinfo($guid, PATHINFO_FILENAME);
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
	public static function isLocal(string $name): bool
	{
		// @TODO Maybe a proper check here on true condition?
		return (bool)self::getIdForName($name);
	}

	/**
	 * Return the id of a local photo
	 *
	 * @param string $name Picture link
	 * @return int
	 */
	public static function getIdForName(string $name): int
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
	public static function isLocalPage(string $name): bool
	{
		$guid = str_replace(Strings::normaliseLink((string)DI::baseUrl()), '', Strings::normaliseLink($name));
		$guid = preg_replace("=/photos/.*/image/(.*)=ism", '$1', $guid);
		if (empty($guid)) {
			return false;
		}

		return DBA::exists('photo', ['resource-id' => $guid]);
	}

	/**
	 * Resize to a given maximum file size
	 *
	 * @param Image $image
	 * @param integer $maximagesize
	 * @return Image
	 */
	public static function resizeToFileSize(Image $image, int $maximagesize): Image
	{
		$filesize = strlen($image->asString());
		$width    = $image->getWidth();
		$height   = $image->getHeight();

		if ($maximagesize && ($filesize > $maximagesize)) {
			// Scale down to multiples of 640 until the maximum size isn't exceeded anymore
			foreach ([5120, 2560, 1280, 640, 320] as $pixels) {
				if (($filesize > $maximagesize) && (max($width, $height) > $pixels)) {
					DI::logger()->info('Resize', ['size' => $filesize, 'width' => $width, 'height' => $height, 'max' => $maximagesize, 'pixels' => $pixels]);
					$image->scaleDown($pixels);
					$filesize = strlen($image->asString());
					$width    = $image->getWidth();
					$height   = $image->getHeight();
				}
			}
		}

		return $image;
	}

	/**
	 * Tries to resize image to wanted maximum size
	 *
	 * @param Image $image Image instance
	 * @return Image|null Image instance on success, null on error
	 */
	private static function fitImageSize(Image $image)
	{
		$max_length = DI::config()->get('system', 'max_image_length');
		if ($max_length > 0) {
			$image->scaleDown($max_length);
			DI::logger()->info('File upload: Scaling picture to new size', ['max-length' => $max_length]);
		}

		return self::resizeToFileSize($image, Strings::getBytesFromShorthand(DI::config()->get('system', 'maximagesize')));
	}

	/**
	 * Fetches image from URL and returns an array with instance and local file name
	 *
	 * @param string $image_url URL to image
	 * @return array With: 'image' and 'filename' fields or empty array on error
	 */
	private static function loadImageFromURL(string $image_url): array
	{
		$filename = basename($image_url);
		if (!empty($image_url)) {
			try {
				$ret = DI::httpClient()->get($image_url, HttpClientAccept::IMAGE, [HttpClientOptions::REQUEST => HttpClientRequest::MEDIAPROXY]);
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
				return [];
			}
			DI::logger()->debug('Got picture', ['Content-Type' => $ret->getHeader('Content-Type'), 'url' => $image_url]);
			$img_str = $ret->getBodyString();
			$type    = $ret->getContentType();
		} else {
			$img_str = '';
			$type    = '';
		}

		if (empty($img_str)) {
			DI::logger()->notice('Empty content');
			return [];
		}

		$image = new Image($img_str, $type, $image_url);

		$image = self::fitImageSize($image);
		if (empty($image)) {
			return [];
		}

		return ['image' => $image, 'filename' => $filename];
	}

	/**
	 * Inserts uploaded image into database and removes local temporary file
	 *
	 * @param array $files File array
	 * @return array With 'image' for Image instance and 'filename' for local file name or empty array on error
	 */
	private static function uploadImage(array $files): array
	{
		DI::logger()->info('starting new upload');

		if (empty($files)) {
			DI::logger()->notice('Empty upload file');
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
			DI::logger()->notice('No source file name', ['files' => $files]);
			return [];
		}

		DI::logger()->info('File upload', ['src' => $src, 'filename' => $filename, 'size' => $filesize, 'type' => $filetype]);

		$imagedata = @file_get_contents($src);
		$image     = new Image($imagedata, $filetype, $filename);
		if (!$image->isValid()) {
			DI::logger()->notice('Image is unvalid', ['files' => $files]);
			return [];
		}

		$image->orient($src);
		@unlink($src);

		$image = self::fitImageSize($image);
		if (empty($image)) {
			return [];
		}

		return ['image' => $image, 'filename' => $filename, 'size' => $filesize];
	}

	/**
	 * Handles uploaded image and assigns it to given user id
	 *
	 * @param int         $uid   User ID
	 * @param array       $files uploaded file array
	 * @param string      $album Album name (optional)
	 * @param string|null $allow_cid
	 * @param string|null $allow_gid
	 * @param string      $deny_cid
	 * @param string      $deny_gid
	 * @param string      $desc Description (optional)
	 * @param string      $resource_id GUID (optional)
	 * @return array photo record or empty array on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function upload(int $uid, array $files, string $album = '', string $allow_cid = null, string $allow_gid = null, string $deny_cid = '', string $deny_gid = '', string $desc = '', string $resource_id = ''): array
	{
		$user = User::getOwnerDataById($uid);
		if (empty($user)) {
			DI::logger()->notice('User not found', ['uid' => $uid]);
			return [];
		}

		$data = self::uploadImage($files);
		if (empty($data)) {
			DI::logger()->info('upload failed');
			return [];
		}

		$image    = $data['image'];
		$filename = $data['filename'];
		$filesize = $data['size'];

		$resource_id = $resource_id ?: self::newResource();
		$album       = $album ?: DI::l10n()->t('Wall Photos');

		if (is_null($allow_cid) && is_null($allow_gid)) {
			$allow_cid = '<' . $user['id'] . '>';
			$allow_gid = '';
		}

		$preview = self::storeWithPreview($image, $user['uid'], $resource_id, $filename, $filesize, $album, $desc, $allow_cid, $allow_gid, $deny_cid, $deny_gid);
		if ($preview < 0) {
			DI::logger()->warning('Photo could not be stored', ['uid' => $user['uid'], 'resource_id' => $resource_id, 'filename' => $filename, 'album' => $album]);
			return [];
		}

		$condition = ['resource-id' => $resource_id];
		$photo     = self::selectFirst(['id', 'datasize', 'width', 'height', 'type'], $condition, ['order' => ['width' => true]]);
		if (empty($photo)) {
			DI::logger()->notice('Photo not found', ['condition' => $condition]);
			return [];
		}

		$picture = [];

		$picture['id']          = $photo['id'];
		$picture['resource_id'] = $resource_id;
		$picture['size']        = $photo['datasize'];
		$picture['width']       = $photo['width'];
		$picture['height']      = $photo['height'];
		$picture['type']        = $photo['type'];
		$picture['albumpage']   = DI::baseUrl() . '/photos/' . $user['nickname'] . '/image/' . $resource_id;
		$picture['picture']     = DI::baseUrl() . '/photo/' . $resource_id . '-0.' . $image->getExt();
		$picture['preview']     = DI::baseUrl() . '/photo/' . $resource_id . '-' . $preview . '.' . $image->getExt();

		DI::logger()->info('upload done', ['picture' => $picture]);
		return $picture;
	}

	/**
	 * store photo metadata in db and binary with preview photos in default backend
	 *
	 * @param Image   $image       Image object with data
	 * @param integer $uid         User ID
	 * @param string  $resource_id Resource ID
	 * @param string  $filename    Filename
	 * @param integer $filesize    Filesize
	 * @param string  $album       Album name
	 * @param string  $description Photo caption
	 * @param string  $allow_cid   Permissions, allowed contacts
	 * @param string  $allow_gid   Permissions, allowed circles
	 * @param string  $deny_cid    Permissions, denied contacts
	 * @param string  $deny_gid    Permissions, denied circles
	 *
	 * @return integer preview photo size
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function storeWithPreview(Image $image, int $uid, string $resource_id, string $filename, int $filesize, string $album, string $description, string $allow_cid, string $allow_gid, string $deny_cid, string $deny_gid): int
	{
		$image = self::resizeToFileSize($image, Strings::getBytesFromShorthand(DI::config()->get('system', 'maximagesize')));

		$width   = $image->getWidth();
		$height  = $image->getHeight();
		$preview = 0;

		$result = self::store($image, $uid, 0, $resource_id, $filename, $album, 0, self::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $description);
		if (!$result) {
			DI::logger()->warning('Photo could not be stored', ['uid' => $uid, 'resource_id' => $resource_id, 'filename' => $filename, 'album' => $album]);
			return -1;
		}

		if ($width > Proxy::PIXEL_MEDIUM || $height > Proxy::PIXEL_MEDIUM) {
			$image->scaleDown(Proxy::PIXEL_MEDIUM);
		}

		if ($width > Proxy::PIXEL_SMALL || $height > Proxy::PIXEL_SMALL) {
			$result = self::store($image, $uid, 0, $resource_id, $filename, $album, 1, self::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $description);
			if ($result) {
				$preview = 1;
			}
			$image->scaleDown(Proxy::PIXEL_SMALL);
			$result = self::store($image, $uid, 0, $resource_id, $filename, $album, 2, self::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $description);
			if ($result && ($preview == 0)) {
				$preview = 2;
			}
		}
		return $preview;
	}

	/**
	 * Upload a user avatar
	 *
	 * @param int    $uid   User ID
	 * @param array  $files uploaded file array
	 * @param string $url   External image url
	 * @return string avatar resource
	 */
	public static function uploadAvatar(int $uid, array $files, string $url = ''): string
	{
		if (!empty($files)) {
			$data = self::uploadImage($files);
			if (empty($data)) {
				DI::logger()->info('upload failed');
				return '';
			}
		} elseif (!empty($url)) {
			$data = self::loadImageFromURL($url);
			if (empty($data)) {
				DI::logger()->info('loading from external url failed');
				return '';
			}
		} else {
			DI::logger()->info('Neither files nor url provided');
			return '';
		}

		$image    = $data['image'];
		$filename = $data['filename'];
		$width    = $image->getWidth();
		$height   = $image->getHeight();

		$resource_id = self::newResource();
		$album       = DI::l10n()->t(self::PROFILE_PHOTOS);

		// upload profile image (scales 4, 5, 6)
		DI::logger()->info('starting new profile image upload');

		if ($width > 300 || $height > 300) {
			$image->scaleDown(300);
		}

		$r = self::store($image, $uid, 0, $resource_id, $filename, $album, 4, self::USER_AVATAR);
		if (!$r) {
			DI::logger()->warning('profile image upload with scale 4 (300) failed', ['uid' => $uid, 'resource_id' => $resource_id, 'filename' => $filename, 'album' => $album]);
		}

		if ($width > 80 || $height > 80) {
			$image->scaleDown(80);
		}

		$r = self::store($image, $uid, 0, $resource_id, $filename, $album, 5, self::USER_AVATAR);
		if (!$r) {
			DI::logger()->warning('profile image upload with scale 5 (80) failed', ['uid' => $uid, 'resource_id' => $resource_id, 'filename' => $filename, 'album' => $album]);
		}

		if ($width > 48 || $height > 48) {
			$image->scaleDown(48);
		}

		$r = self::store($image, $uid, 0, $resource_id, $filename, $album, 6, self::USER_AVATAR);
		if (!$r) {
			DI::logger()->warning('profile image upload with scale 6 (48) failed', ['uid' => $uid, 'resource_id' => $resource_id, 'filename' => $filename, 'album' => $album]);
		}

		DI::logger()->info('new profile image upload ended');

		$condition = ["`profile` AND `resource-id` != ? AND `uid` = ?", $resource_id, $uid];
		self::update(['profile' => false, 'photo-type' => self::DEFAULT], $condition);

		Contact::updateSelfFromUserID($uid, true);

		// Update global directory in background
		Profile::publishUpdate($uid);

		return $resource_id;
	}

	/**
	 * Upload a user banner
	 *
	 * @param int    $uid   User ID
	 * @param array  $files uploaded file array
	 * @param string $url   External image url
	 * @return string avatar resource
	 */
	public static function uploadBanner(int $uid, array $files = [], string $url = ''): string
	{
		if (!empty($files)) {
			$data = self::uploadImage($files);
			if (empty($data)) {
				DI::logger()->info('upload failed');
				return '';
			}
		} elseif (!empty($url)) {
			$data = self::loadImageFromURL($url);
			if (empty($data)) {
				DI::logger()->info('loading from external url failed');
				return '';
			}
		} else {
			DI::logger()->info('Neither files nor url provided');
			return '';
		}

		$image    = $data['image'];
		$filename = $data['filename'];
		$width    = $image->getWidth();
		$height   = $image->getHeight();

		$resource_id = self::newResource();
		$album       = DI::l10n()->t(self::BANNER_PHOTOS);

		if ($width > 960) {
			$image->scaleDown(960);
		}

		$r = self::store($image, $uid, 0, $resource_id, $filename, $album, 3, self::USER_BANNER);
		if (!$r) {
			DI::logger()->warning('profile banner upload with scale 3 (960) failed');
		}

		DI::logger()->info('new profile banner upload ended', ['uid' => $uid, 'resource_id' => $resource_id, 'filename' => $filename]);

		$condition = ["`photo-type` = ? AND `resource-id` != ? AND `uid` = ?", self::USER_BANNER, $resource_id, $uid];
		self::update(['photo-type' => self::DEFAULT], $condition);

		Contact::updateSelfFromUserID($uid, true);

		// Update global directory in background
		Profile::publishUpdate($uid);

		return $resource_id;
	}
}
