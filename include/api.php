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
 * Friendica implementation of statusnet/twitter API
 *
 * @file include/api.php
 * @todo Automatically detect if incoming data is HTML or BBCode
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Strings;

$API = [];

/**
 * Register a function to be the endpoint for defined API path.
 *
 * @param string $path   API URL path, relative to DI::baseUrl()
 * @param string $func   Function name to call on path request
 */
function api_register_func($path, $func)
{
	global $API;

	$API[$path] = [
		'func'   => $func,
	];

	// Workaround for hotot
	$path = str_replace("api/", "api/1.1/", $path);

	$API[$path] = [
		'func'   => $func,
	];
}

/**
 * Main API entry point
 *
 * Authenticate user, call registered API function, set HTTP headers
 *
 * @param App\Arguments $args The app arguments (optional, will retrieved by the DI-Container in case of missing)
 * @return string|array API call result
 * @throws Exception
 */
function api_call($command, $extension)
{
	global $API;

	Logger::info('Legacy API call', ['command' => $command, 'extension' => $extension]);

	try {
		foreach ($API as $p => $info) {
			if (strpos($command, $p) === 0) {
				Logger::debug(BaseApi::LOG_PREFIX . 'parameters', ['module' => 'api', 'action' => 'call', 'parameters' => $_REQUEST]);

				$stamp =  microtime(true);
				$return = call_user_func($info['func'], $extension);
				$duration = floatval(microtime(true) - $stamp);

				Logger::info(BaseApi::LOG_PREFIX . 'duration {duration}', ['module' => 'api', 'action' => 'call', 'duration' => round($duration, 2)]);

				DI::profiler()->saveLog(DI::logger(), BaseApi::LOG_PREFIX . 'performance');

				if (false === $return) {
					/*
						* api function returned false withour throw an
						* exception. This should not happend, throw a 500
						*/
					throw new InternalServerErrorException();
				}

				switch ($extension) {
					case "xml":
						header("Content-Type: text/xml");
						break;
					case "json":
						header("Content-Type: application/json");
						if (!empty($return)) {
							$json = json_encode(end($return));
							if (!empty($_GET['callback'])) {
								$json = $_GET['callback'] . "(" . $json . ")";
							}
							$return = $json;
						}
						break;
					case "rss":
						header("Content-Type: application/rss+xml");
						$return  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
						break;
					case "atom":
						header("Content-Type: application/atom+xml");
						$return = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
						break;
				}
				return $return;
			}
		}

		Logger::warning(BaseApi::LOG_PREFIX . 'not implemented', ['module' => 'api', 'action' => 'call', 'query' => DI::args()->getQueryString()]);
		throw new NotFoundException();
	} catch (HTTPException $e) {
		Logger::notice(BaseApi::LOG_PREFIX . 'got exception', ['module' => 'api', 'action' => 'call', 'query' => DI::args()->getQueryString(), 'error' => $e]);
		DI::apiResponse()->error($e->getCode(), $e->getDescription(), $e->getMessage(), $extension);
	}
}

/**
 *
 * @param string $acl_string
 * @param int    $uid
 * @return bool
 * @throws Exception
 */
function check_acl_input($acl_string, $uid)
{
	if (empty($acl_string)) {
		return false;
	}

	$contact_not_found = false;

	// split <x><y><z> into array of cid's
	preg_match_all("/<[A-Za-z0-9]+>/", $acl_string, $array);

	// check for each cid if it is available on server
	$cid_array = $array[0];
	foreach ($cid_array as $cid) {
		$cid = str_replace("<", "", $cid);
		$cid = str_replace(">", "", $cid);
		$condition = ['id' => $cid, 'uid' => $uid];
		$contact_not_found |= !DBA::exists('contact', $condition);
	}
	return $contact_not_found;
}

/**
 * @param string  $mediatype
 * @param array   $media
 * @param string  $type
 * @param string  $album
 * @param string  $allow_cid
 * @param string  $deny_cid
 * @param string  $allow_gid
 * @param string  $deny_gid
 * @param string  $desc
 * @param integer $phototype
 * @param boolean $visibility
 * @param string  $photo_id
 * @param int     $uid
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 */
function save_media_to_database($mediatype, $media, $type, $album, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $desc, $phototype, $visibility, $photo_id, $uid)
{
	$visitor   = 0;
	$src = "";
	$filetype = "";
	$filename = "";
	$filesize = 0;

	if (is_array($media)) {
		if (is_array($media['tmp_name'])) {
			$src = $media['tmp_name'][0];
		} else {
			$src = $media['tmp_name'];
		}
		if (is_array($media['name'])) {
			$filename = basename($media['name'][0]);
		} else {
			$filename = basename($media['name']);
		}
		if (is_array($media['size'])) {
			$filesize = intval($media['size'][0]);
		} else {
			$filesize = intval($media['size']);
		}
		if (is_array($media['type'])) {
			$filetype = $media['type'][0];
		} else {
			$filetype = $media['type'];
		}
	}

	$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

	logger::info(
		"File upload src: " . $src . " - filename: " . $filename .
		" - size: " . $filesize . " - type: " . $filetype);

	// check if there was a php upload error
	if ($filesize == 0 && $media['error'] == 1) {
		throw new InternalServerErrorException("image size exceeds PHP config settings, file was rejected by server");
	}
	// check against max upload size within Friendica instance
	$maximagesize = DI::config()->get('system', 'maximagesize');
	if ($maximagesize && ($filesize > $maximagesize)) {
		$formattedBytes = Strings::formatBytes($maximagesize);
		throw new InternalServerErrorException("image size exceeds Friendica config setting (uploaded size: $formattedBytes)");
	}

	// create Photo instance with the data of the image
	$imagedata = @file_get_contents($src);
	$Image = new Image($imagedata, $filetype);
	if (!$Image->isValid()) {
		throw new InternalServerErrorException("unable to process image data");
	}

	// check orientation of image
	$Image->orient($src);
	@unlink($src);

	// check max length of images on server
	$max_length = DI::config()->get('system', 'max_image_length');
	if ($max_length > 0) {
		$Image->scaleDown($max_length);
		logger::info("File upload: Scaling picture to new size " . $max_length);
	}
	$width = $Image->getWidth();
	$height = $Image->getHeight();

	// create a new resource-id if not already provided
	$resource_id = ($photo_id == null) ? Photo::newResource() : $photo_id;

	if ($mediatype == "photo") {
		// upload normal image (scales 0, 1, 2)
		logger::info("photo upload: starting new photo upload");

		$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 0, Photo::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
		if (!$r) {
			logger::notice("photo upload: image upload with scale 0 (original size) failed");
		}
		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 1, Photo::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: image upload with scale 1 (640x640) failed");
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 2, Photo::DEFAULT, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: image upload with scale 2 (320x320) failed");
			}
		}
		logger::info("photo upload: new photo upload ended");
	} elseif ($mediatype == "profileimage") {
		// upload profile image (scales 4, 5, 6)
		logger::info("photo upload: starting new profile image upload");

		if ($width > 300 || $height > 300) {
			$Image->scaleDown(300);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 4, $phototype, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: profile image upload with scale 4 (300x300) failed");
			}
		}

		if ($width > 80 || $height > 80) {
			$Image->scaleDown(80);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 5, $phototype, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: profile image upload with scale 5 (80x80) failed");
			}
		}

		if ($width > 48 || $height > 48) {
			$Image->scaleDown(48);
			$r = Photo::store($Image, $uid, $visitor, $resource_id, $filename, $album, 6, $phototype, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				logger::notice("photo upload: profile image upload with scale 6 (48x48) failed");
			}
		}
		$Image->__destruct();
		logger::info("photo upload: new profile image upload ended");
	}

	if (!empty($r)) {
		// create entry in 'item'-table on new uploads to enable users to comment/like/dislike the photo
		if ($photo_id == null && $mediatype == "photo") {
			post_photo_item($resource_id, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility, $uid);
		}
		// on success return image data in json/xml format (like /api/friendica/photo does when no scale is given)
		return prepare_photo_data($type, false, $resource_id, $uid);
	} else {
		throw new InternalServerErrorException("image upload failed");
		DI::page()->exit(DI::apiResponse());
	}
}

/**
 *
 * @param string  $hash
 * @param string  $allow_cid
 * @param string  $deny_cid
 * @param string  $allow_gid
 * @param string  $deny_gid
 * @param string  $filetype
 * @param boolean $visibility
 * @param int     $uid
 * @throws InternalServerErrorException
 */
function post_photo_item($hash, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility, $uid)
{
	// get data about the api authenticated user
	$uri = Item::newURI(intval($uid));
	$owner_record = DBA::selectFirst('contact', [], ['uid' => $uid, 'self' => true]);

	$arr = [];
	$arr['guid']          = System::createUUID();
	$arr['uid']           = $uid;
	$arr['uri']           = $uri;
	$arr['post-type']     = Item::PT_IMAGE;
	$arr['wall']          = 1;
	$arr['resource-id']   = $hash;
	$arr['contact-id']    = $owner_record['id'];
	$arr['owner-name']    = $owner_record['name'];
	$arr['owner-link']    = $owner_record['url'];
	$arr['owner-avatar']  = $owner_record['thumb'];
	$arr['author-name']   = $owner_record['name'];
	$arr['author-link']   = $owner_record['url'];
	$arr['author-avatar'] = $owner_record['thumb'];
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['visible']       = $visibility;
	$arr['origin']        = 1;

	$typetoext = Images::supportedTypes();

	// adds link to the thumbnail scale photo
	$arr['body'] = '[url=' . DI::baseUrl() . '/photos/' . $owner_record['nick'] . '/image/' . $hash . ']'
				. '[img]' . DI::baseUrl() . '/photo/' . $hash . '-' . "2" . '.'. $typetoext[$filetype] . '[/img]'
				. '[/url]';

	// do the magic for storing the item in the database and trigger the federation to other contacts
	Item::insert($arr);
}

/**
 *
 * @param string $type
 * @param int    $scale
 * @param string $photo_id
 *
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 */
function prepare_photo_data($type, $scale, $photo_id, $uid)
{
	$scale_sql = ($scale === false ? "" : sprintf("AND scale=%d", intval($scale)));
	$data_sql = ($scale === false ? "" : "data, ");

	// added allow_cid, allow_gid, deny_cid, deny_gid to output as string like stored in database
	// clients needs to convert this in their way for further processing
	$r = DBA::toArray(DBA::p(
		"SELECT $data_sql `resource-id`, `created`, `edited`, `title`, `desc`, `album`, `filename`,
					`type`, `height`, `width`, `datasize`, `profile`, `allow_cid`, `deny_cid`, `allow_gid`, `deny_gid`,
					MIN(`scale`) AS `minscale`, MAX(`scale`) AS `maxscale`
			FROM `photo` WHERE `uid` = ? AND `resource-id` = ? $scale_sql GROUP BY
				   `resource-id`, `created`, `edited`, `title`, `desc`, `album`, `filename`,
				   `type`, `height`, `width`, `datasize`, `profile`, `allow_cid`, `deny_cid`, `allow_gid`, `deny_gid`",
		$uid,
		$photo_id
	));

	$typetoext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif'
	];

	// prepare output data for photo
	if (DBA::isResult($r)) {
		$data = ['photo' => $r[0]];
		$data['photo']['id'] = $data['photo']['resource-id'];
		if ($scale !== false) {
			$data['photo']['data'] = base64_encode($data['photo']['data']);
		} else {
			unset($data['photo']['datasize']); //needed only with scale param
		}
		if ($type == "xml") {
			$data['photo']['links'] = [];
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['links'][$k . ":link"]["@attributes"] = ["type" => $data['photo']['type'],
										"scale" => $k,
										"href" => DI::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']]];
			}
		} else {
			$data['photo']['link'] = [];
			// when we have profile images we could have only scales from 4 to 6, but index of array always needs to start with 0
			$i = 0;
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['link'][$i] = DI::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']];
				$i++;
			}
		}
		unset($data['photo']['resource-id']);
		unset($data['photo']['minscale']);
		unset($data['photo']['maxscale']);
	} else {
		throw new NotFoundException();
	}

	// retrieve item element for getting activities (like, dislike etc.) related to photo
	$condition = ['uid' => $uid, 'resource-id' => $photo_id];
	$item = Post::selectFirst(['id', 'uid', 'uri', 'uri-id', 'parent', 'allow_cid', 'deny_cid', 'allow_gid', 'deny_gid'], $condition);
	if (!DBA::isResult($item)) {
		throw new NotFoundException('Photo-related item not found.');
	}

	$data['photo']['friendica_activities'] = DI::friendicaActivities()->createFromUriId($item['uri-id'], $item['uid'], $type);

	// retrieve comments on photo
	$condition = ["`parent` = ? AND `uid` = ? AND `gravity` IN (?, ?)",
		$item['parent'], $uid, GRAVITY_PARENT, GRAVITY_COMMENT];

	$statuses = Post::selectForUser($uid, [], $condition);

	// prepare output of comments
	$commentData = [];
	while ($status = DBA::fetch($statuses)) {
		$commentData[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'])->toArray();
	}
	DBA::close($statuses);

	$comments = [];
	if ($type == "xml") {
		$k = 0;
		foreach ($commentData as $comment) {
			$comments[$k++ . ":comment"] = $comment;
		}
	} else {
		foreach ($commentData as $comment) {
			$comments[] = $comment;
		}
	}
	$data['photo']['friendica_comments'] = $comments;

	// include info if rights on photo and rights on item are mismatching
	$rights_mismatch = $data['photo']['allow_cid'] != $item['allow_cid'] ||
		$data['photo']['deny_cid'] != $item['deny_cid'] ||
		$data['photo']['allow_gid'] != $item['allow_gid'] ||
		$data['photo']['deny_gid'] != $item['deny_gid'];
	$data['photo']['rights_mismatch'] = $rights_mismatch;

	return $data;
}

/**
 * Add a new group to the database.
 *
 * @param  string $name  Group name
 * @param  int    $uid   User ID
 * @param  array  $users List of users to add to the group
 *
 * @return array
 * @throws BadRequestException
 */
function group_create($name, $uid, $users = [])
{
	// error if no name specified
	if ($name == "") {
		throw new BadRequestException('group name not specified');
	}

	// error message if specified group name already exists
	if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => false])) {
		throw new BadRequestException('group name already exists');
	}

	// Check if the group needs to be reactivated
	if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => true])) {
		$reactivate_group = true;
	}

	// create group
	$ret = Group::create($uid, $name);
	if ($ret) {
		$gid = Group::getIdByName($uid, $name);
	} else {
		throw new BadRequestException('other API error');
	}

	// add members
	$erroraddinguser = false;
	$errorusers = [];
	foreach ($users as $user) {
		$cid = $user['cid'];
		if (DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
			Group::addMember($gid, $cid);
		} else {
			$erroraddinguser = true;
			$errorusers[] = $cid;
		}
	}

	// return success message incl. missing users in array
	$status = ($erroraddinguser ? "missing user" : ((isset($reactivate_group) && $reactivate_group) ? "reactivated" : "ok"));

	return ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
}

/**
 * TWITTER API
 */

/**
 * Returns all lists the user subscribes to.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list
 */
function api_lists_list($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$ret = [];
	/// @TODO $ret is not filled here?
	return DI::apiResponse()->formatData('lists', $type, ["lists_list" => $ret]);
}

api_register_func('api/lists/list', 'api_lists_list', true);
api_register_func('api/lists/subscriptions', 'api_lists_list', true);

/**
 * Returns all groups the user owns.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
function api_lists_ownerships($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// params
	$user_info = DI::twitterUser()->createFromUserId($uid, true)->toArray();

	$groups = DBA::select('group', [], ['deleted' => 0, 'uid' => $uid]);

	// loop through all groups
	$lists = [];
	foreach ($groups as $group) {
		if ($group['visible']) {
			$mode = 'public';
		} else {
			$mode = 'private';
		}
		$lists[] = [
			'name' => $group['name'],
			'id' => intval($group['id']),
			'id_str' => (string) $group['id'],
			'user' => $user_info,
			'mode' => $mode
		];
	}
	return DI::apiResponse()->formatData("lists", $type, ['lists' => ['lists' => $lists]]);
}

api_register_func('api/lists/ownerships', 'api_lists_ownerships', true);

/**
 * list all photos of the authenticated user
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_fr_photos_list($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	$r = DBA::toArray(DBA::p(
		"SELECT `resource-id`, MAX(scale) AS `scale`, `album`, `filename`, `type`, MAX(`created`) AS `created`,
		MAX(`edited`) AS `edited`, MAX(`desc`) AS `desc` FROM `photo`
		WHERE `uid` = ? AND NOT `photo-type` IN (?, ?) GROUP BY `resource-id`, `album`, `filename`, `type`",
		$uid, Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER
	));
	$typetoext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif'
	];
	$data = ['photo'=>[]];
	if (DBA::isResult($r)) {
		foreach ($r as $rr) {
			$photo = [];
			$photo['id'] = $rr['resource-id'];
			$photo['album'] = $rr['album'];
			$photo['filename'] = $rr['filename'];
			$photo['type'] = $rr['type'];
			$thumb = DI::baseUrl() . "/photo/" . $rr['resource-id'] . "-" . $rr['scale'] . "." . $typetoext[$rr['type']];
			$photo['created'] = $rr['created'];
			$photo['edited'] = $rr['edited'];
			$photo['desc'] = $rr['desc'];

			if ($type == "xml") {
				$data['photo'][] = ["@attributes" => $photo, "1" => $thumb];
			} else {
				$photo['thumb'] = $thumb;
				$data['photo'][] = $photo;
			}
		}
	}
	return DI::apiResponse()->formatData("photos", $type, $data);
}

api_register_func('api/friendica/photos/list', 'api_fr_photos_list', true);

/**
 * upload a new photo or change an existing photo
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 */
function api_fr_photo_create_update($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// input params
	$photo_id  = $_REQUEST['photo_id']  ?? null;
	$desc      = $_REQUEST['desc']      ?? null;
	$album     = $_REQUEST['album']     ?? null;
	$album_new = $_REQUEST['album_new'] ?? null;
	$allow_cid = $_REQUEST['allow_cid'] ?? null;
	$deny_cid  = $_REQUEST['deny_cid' ] ?? null;
	$allow_gid = $_REQUEST['allow_gid'] ?? null;
	$deny_gid  = $_REQUEST['deny_gid' ] ?? null;
	// Pictures uploaded via API never get posted as a visible status
	// See https://github.com/friendica/friendica/issues/10990
	$visibility = false;

	// do several checks on input parameters
	// we do not allow calls without album string
	if ($album == null) {
		throw new BadRequestException("no albumname specified");
	}
	// if photo_id == null --> we are uploading a new photo
	if ($photo_id == null) {
		$mode = "create";

		// error if no media posted in create-mode
		if (empty($_FILES['media'])) {
			// Output error
			throw new BadRequestException("no media data submitted");
		}

		// album_new will be ignored in create-mode
		$album_new = "";
	} else {
		$mode = "update";

		// check if photo is existing in databasei
		if (!Photo::exists(['resource-id' => $photo_id, 'uid' => $uid, 'album' => $album])) {
			throw new BadRequestException("photo not available");
		}
	}

	// checks on acl strings provided by clients
	$acl_input_error = false;
	$acl_input_error |= check_acl_input($allow_cid, $uid);
	$acl_input_error |= check_acl_input($deny_cid, $uid);
	$acl_input_error |= check_acl_input($allow_gid, $uid);
	$acl_input_error |= check_acl_input($deny_gid, $uid);
	if ($acl_input_error) {
		throw new BadRequestException("acl data invalid");
	}
	// now let's upload the new media in create-mode
	if ($mode == "create") {
		$media = $_FILES['media'];
		$data = save_media_to_database("photo", $media, $type, $album, trim($allow_cid), trim($deny_cid), trim($allow_gid), trim($deny_gid), $desc, Photo::DEFAULT, $visibility, null, $uid);

		// return success of updating or error message
		if (!is_null($data)) {
			return DI::apiResponse()->formatData("photo_create", $type, $data);
		} else {
			throw new InternalServerErrorException("unknown error - uploading photo failed, see Friendica log for more information");
		}
	}

	// now let's do the changes in update-mode
	if ($mode == "update") {
		$updated_fields = [];

		if (!is_null($desc)) {
			$updated_fields['desc'] = $desc;
		}

		if (!is_null($album_new)) {
			$updated_fields['album'] = $album_new;
		}

		if (!is_null($allow_cid)) {
			$allow_cid = trim($allow_cid);
			$updated_fields['allow_cid'] = $allow_cid;
		}

		if (!is_null($deny_cid)) {
			$deny_cid = trim($deny_cid);
			$updated_fields['deny_cid'] = $deny_cid;
		}

		if (!is_null($allow_gid)) {
			$allow_gid = trim($allow_gid);
			$updated_fields['allow_gid'] = $allow_gid;
		}

		if (!is_null($deny_gid)) {
			$deny_gid = trim($deny_gid);
			$updated_fields['deny_gid'] = $deny_gid;
		}

		$result = false;
		if (count($updated_fields) > 0) {
			$nothingtodo = false;
			$result = Photo::update($updated_fields, ['uid' => $uid, 'resource-id' => $photo_id, 'album' => $album]);
		} else {
			$nothingtodo = true;
		}

		if (!empty($_FILES['media'])) {
			$nothingtodo = false;
			$media = $_FILES['media'];
			$data = save_media_to_database("photo", $media, $type, $album, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $desc, Photo::DEFAULT, $visibility, $photo_id, $uid);
			if (!is_null($data)) {
				return DI::apiResponse()->formatData("photo_update", $type, $data);
			}
		}

		// return success of updating or error message
		if ($result) {
			$answer = ['result' => 'updated', 'message' => 'Image id `' . $photo_id . '` has been updated.'];
			return DI::apiResponse()->formatData("photo_update", $type, ['$result' => $answer]);
		} else {
			if ($nothingtodo) {
				$answer = ['result' => 'cancelled', 'message' => 'Nothing to update for image id `' . $photo_id . '`.'];
				return DI::apiResponse()->formatData("photo_update", $type, ['$result' => $answer]);
			}
			throw new InternalServerErrorException("unknown error - update photo entry in database failed");
		}
	}
	throw new InternalServerErrorException("unknown error - this error on uploading or updating a photo should never happen");
}

api_register_func('api/friendica/photo/create', 'api_fr_photo_create_update', true);
api_register_func('api/friendica/photo/update', 'api_fr_photo_create_update', true);

/**
 * returns the details of a specified photo id, if scale is given, returns the photo data in base 64
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 */
function api_fr_photo_detail($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	if (empty($_REQUEST['photo_id'])) {
		throw new BadRequestException("No photo id.");
	}

	$scale = (!empty($_REQUEST['scale']) ? intval($_REQUEST['scale']) : false);
	$photo_id = $_REQUEST['photo_id'];

	// prepare json/xml output with data from database for the requested photo
	$data = prepare_photo_data($type, $scale, $photo_id, $uid);

	return DI::apiResponse()->formatData("photo_detail", $type, $data);
}

api_register_func('api/friendica/photo', 'api_fr_photo_detail', true);

/**
 * updates the profile image for the user (either a specified profile or the default profile)
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 *
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @see   https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_image
 */
function api_account_update_profile_image($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// input params
	$profile_id = $_REQUEST['profile_id'] ?? 0;

	// error if image data is missing
	if (empty($_FILES['image'])) {
		throw new BadRequestException("no media data submitted");
	}

	// check if specified profile id is valid
	if ($profile_id != 0) {
		$profile = DBA::selectFirst('profile', ['is-default'], ['uid' => $uid, 'id' => $profile_id]);
		// error message if specified profile id is not in database
		if (!DBA::isResult($profile)) {
			throw new BadRequestException("profile_id not available");
		}
		$is_default_profile = $profile['is-default'];
	} else {
		$is_default_profile = 1;
	}

	// get mediadata from image or media (Twitter call api/account/update_profile_image provides image)
	$media = null;
	if (!empty($_FILES['image'])) {
		$media = $_FILES['image'];
	} elseif (!empty($_FILES['media'])) {
		$media = $_FILES['media'];
	}
	// save new profile image
	$data = save_media_to_database("profileimage", $media, $type, DI::l10n()->t(Photo::PROFILE_PHOTOS), "", "", "", "", "", Photo::USER_AVATAR, false, null, $uid);

	// get filetype
	if (is_array($media['type'])) {
		$filetype = $media['type'][0];
	} else {
		$filetype = $media['type'];
	}
	if ($filetype == "image/jpeg") {
		$fileext = "jpg";
	} elseif ($filetype == "image/png") {
		$fileext = "png";
	} else {
		throw new InternalServerErrorException('Unsupported filetype');
	}

	// change specified profile or all profiles to the new resource-id
	if ($is_default_profile) {
		$condition = ["`profile` AND `resource-id` != ? AND `uid` = ?", $data['photo']['id'], $uid];
		Photo::update(['profile' => false, 'photo-type' => Photo::DEFAULT], $condition);
	} else {
		$fields = ['photo' => DI::baseUrl() . '/photo/' . $data['photo']['id'] . '-4.' . $fileext,
			'thumb' => DI::baseUrl() . '/photo/' . $data['photo']['id'] . '-5.' . $fileext];
		DBA::update('profile', $fields, ['id' => $_REQUEST['profile'], 'uid' => $uid]);
	}

	Contact::updateSelfFromUserID($uid, true);

	// Update global directory in background
	Profile::publishUpdate($uid);

	// output for client
	if ($data) {
		$skip_status = $_REQUEST['skip_status'] ?? false;

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info["verified"]);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		return DI::apiResponse()->formatData("user", $type, ['user' => $user_info]);
	} else {
		// SaveMediaToDatabase failed for some reason
		throw new InternalServerErrorException("image upload failed");
	}
}

api_register_func('api/account/update_profile_image', 'api_account_update_profile_image', true);

/**
 * Return all or a specified group of the user with the containing contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_show($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['gid'] ?? 0;

	// get data of the specified group id or all groups if not specified
	if ($gid != 0) {
		$groups = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid, 'id' => $gid]);

		// error message if specified gid is not in database
		if (!DBA::isResult($groups)) {
			throw new BadRequestException("gid not available");
		}
	} else {
		$groups = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid]);
	}

	// loop through all groups and retrieve all members for adding data in the user array
	$grps = [];
	foreach ($groups as $rr) {
		$members = Contact\Group::getById($rr['id']);
		$users = [];

		if ($type == "xml") {
			$user_element = "users";
			$k = 0;
			foreach ($members as $member) {
				$user = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				$users[$k++.":user"] = $user;
			}
		} else {
			$user_element = "user";
			foreach ($members as $member) {
				$user = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				$users[] = $user;
			}
		}
		$grps[] = ['name' => $rr['name'], 'gid' => $rr['id'], $user_element => $users];
	}
	return DI::apiResponse()->formatData("groups", $type, ['group' => $grps]);
}

api_register_func('api/friendica/group_show', 'api_friendica_group_show', true);

/**
 * Delete a group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-destroy
 */
function api_lists_destroy($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['list_id'] ?? 0;

	// error if no gid specified
	if ($gid == 0) {
		throw new BadRequestException('gid not specified');
	}

	// get data of the specified group id
	$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
	// error message if specified gid is not in database
	if (!$group) {
		throw new BadRequestException('gid not available');
	}

	if (Group::remove($gid)) {
		$list = [
			'name' => $group['name'],
			'id' => intval($gid),
			'id_str' => (string) $gid,
			'user' => DI::twitterUser()->createFromUserId($uid, true)->toArray()
		];

		return DI::apiResponse()->formatData("lists", $type, ['lists' => $list]);
	}
}

api_register_func('api/lists/destroy', 'api_lists_destroy', true);

/**
 * Create the specified group with the posted array of contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_create($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$name = $_REQUEST['name'] ?? '';
	$json = json_decode($_POST['json'], true);
	$users = $json['user'];

	$success = group_create($name, $uid, $users);

	return DI::apiResponse()->formatData("group_create", $type, ['result' => $success]);
}

api_register_func('api/friendica/group_create', 'api_friendica_group_create', true);

/**
 * Create a new group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-create
 */
function api_lists_create($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$name = $_REQUEST['name'] ?? '';

	$success = group_create($name, $uid);
	if ($success['success']) {
		$grp = [
			'name' => $success['name'],
			'id' => intval($success['gid']),
			'id_str' => (string) $success['gid'],
			'user' => DI::twitterUser()->createFromUserId($uid, true)->toArray()
		];

		return DI::apiResponse()->formatData("lists", $type, ['lists' => $grp]);
	}
}

api_register_func('api/lists/create', 'api_lists_create', true);

/**
 * Update information about a group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update
 */
function api_lists_update($type)
{
	BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
	$uid = BaseApi::getCurrentUserID();

	// params
	$gid = $_REQUEST['list_id'] ?? 0;
	$name = $_REQUEST['name'] ?? '';

	// error if no gid specified
	if ($gid == 0) {
		throw new BadRequestException('gid not specified');
	}

	// get data of the specified group id
	$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
	// error message if specified gid is not in database
	if (!$group) {
		throw new BadRequestException('gid not available');
	}

	if (Group::update($gid, $name)) {
		$list = [
			'name' => $name,
			'id' => intval($gid),
			'id_str' => (string) $gid,
			'user' => DI::twitterUser()->createFromUserId($uid, true)->toArray()
		];

		return DI::apiResponse()->formatData("lists", $type, ['lists' => $list]);
	}
}

api_register_func('api/lists/update', 'api_lists_update', true);
