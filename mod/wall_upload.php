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
 * Module for uploading a picture to the profile wall
 *
 * By default the picture will be stored in the photo album with the name Wall Photos.
 * You can specify a different album by adding an optional query string "album="
 * to the url
 *
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Strings;

function wall_upload_post(App $a, $desktopmode = true)
{
	Logger::info("wall upload: starting new upload");

	$r_json = (!empty($_GET['response']) && $_GET['response'] == 'json');
	$album = trim($_GET['album'] ?? '');

	if (DI::args()->getArgc() > 1) {
		if (empty($_FILES['media'])) {
			$nick = DI::args()->getArgv()[1];			
			$user = DBA::selectFirst('owner-view', ['id', 'uid', 'nickname', 'page-flags'], ['nickname' => $nick, 'blocked' => false]);
			if (!DBA::isResult($user)) {
				if ($r_json) {
					echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
					exit();
				}
				return;
			}
		} else {
			$user = DBA::selectFirst('owner-view', ['id', 'uid', 'nickname', 'page-flags'], ['uid' => BaseApi::getCurrentUserID(), 'blocked' => false]);
		}
	} else {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
			exit();
		}
		return;
	}

	/*
	 * Setup permissions structures
	 */
	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid   = $user['uid'];
	$default_cid      = $user['id'];
	$page_owner_nick  = $user['nickname'];
	$community_page   = (($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);

	if ((local_user()) && (local_user() == $page_owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(Session::getRemoteContactID($page_owner_uid))) {
		$contact_id = Session::getRemoteContactID($page_owner_uid);
		$can_post = DBA::exists('contact', ['blocked' => false, 'pending' => false, 'id' => $contact_id, 'uid' => $page_owner_uid]);
		$visitor = $contact_id;
	}

	if (!$can_post) {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Permission denied.')]);
			exit();
		}
		notice(DI::l10n()->t('Permission denied.'));
		exit();
	}

	if (empty($_FILES['userfile']) && empty($_FILES['media'])) {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
		}
		exit();
	}

	$src = '';
	$filename = '';
	$filesize = 0;
	$filetype = '';
	if (!empty($_FILES['userfile'])) {
		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];

	} elseif (!empty($_FILES['media'])) {
		if (!empty($_FILES['media']['tmp_name'])) {
			if (is_array($_FILES['media']['tmp_name'])) {
				$src = $_FILES['media']['tmp_name'][0];
			} else {
				$src = $_FILES['media']['tmp_name'];
			}
		}

		if (!empty($_FILES['media']['name'])) {
			if (is_array($_FILES['media']['name'])) {
				$filename = basename($_FILES['media']['name'][0]);
			} else {
				$filename = basename($_FILES['media']['name']);
			}
		}

		if (!empty($_FILES['media']['size'])) {
			if (is_array($_FILES['media']['size'])) {
				$filesize = intval($_FILES['media']['size'][0]);
			} else {
				$filesize = intval($_FILES['media']['size']);
			}
		}

		if (!empty($_FILES['media']['type'])) {
			if (is_array($_FILES['media']['type'])) {
				$filetype = $_FILES['media']['type'][0];
			} else {
				$filetype = $_FILES['media']['type'];
			}
		}
	}

	if ($src == "") {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
			exit();
		}
		notice(DI::l10n()->t('Invalid request.'));
		exit();
	}

	$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

	Logger::info("File upload src: " . $src . " - filename: " . $filename .
		" - size: " . $filesize . " - type: " . $filetype);

	$imagedata = @file_get_contents($src);
	$Image = new Image($imagedata, $filetype);

	if (!$Image->isValid()) {
		$msg = DI::l10n()->t('Unable to process image.');
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			echo  $msg. EOL;
		}
		@unlink($src);
		exit();
	}

	$Image->orient($src);
	@unlink($src);

	$max_length = DI::config()->get('system', 'max_image_length');
	if ($max_length > 0) {
		$Image->scaleDown($max_length);
		$filesize = strlen($Image->asString());
		Logger::info("File upload: Scaling picture to new size " . $max_length);
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
			Logger::notice('Image size is too big', ['size' => $filesize, 'max' => $maximagesize]);
			$msg = DI::l10n()->t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize));
			if ($r_json) {
				echo json_encode(['error' => $msg]);
			} else {
				echo  $msg. EOL;
			}
			@unlink($src);
			exit();
		}
	}

	$resource_id = Photo::newResource();

	$smallest = 0;

	// If we don't have an album name use the Wall Photos album
	if (!strlen($album)) {
		$album = DI::l10n()->t('Wall Photos');
	}

	$defperm = '<' . $default_cid . '>';

	$r = Photo::store($Image, $page_owner_uid, $visitor, $resource_id, $filename, $album, 0, Photo::DEFAULT, $defperm);

	if (!$r) {
		$msg = DI::l10n()->t('Image upload failed.');
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			echo  $msg. EOL;
		}
		exit();
	}

	if ($width > 640 || $height > 640) {
		$Image->scaleDown(640);
		$r = Photo::store($Image, $page_owner_uid, $visitor, $resource_id, $filename, $album, 1, Photo::DEFAULT, $defperm);
		if ($r) {
			$smallest = 1;
		}
	}

	if ($width > 320 || $height > 320) {
		$Image->scaleDown(320);
		$r = Photo::store($Image, $page_owner_uid, $visitor, $resource_id, $filename, $album, 2, Photo::DEFAULT, $defperm);
		if ($r && ($smallest == 0)) {
			$smallest = 2;
		}
	}

	if (!$desktopmode) {
		$photo = Photo::selectFirst(['id', 'datasize', 'width', 'height', 'type'], ['resource-id' => $resource_id], ['order' => ['width']]);
		if (!$photo) {
			if ($r_json) {
				echo json_encode(['error' => '']);
				exit();
			}
			return false;
		}
		$picture = [];

		$picture["id"]        = $photo["id"];
		$picture["size"]      = $photo["datasize"];
		$picture["width"]     = $photo["width"];
		$picture["height"]    = $photo["height"];
		$picture["type"]      = $photo["type"];
		$picture["albumpage"] = DI::baseUrl() . '/photos/' . $page_owner_nick . '/image/' . $resource_id;
		$picture["picture"]   = DI::baseUrl() . "/photo/{$resource_id}-0." . $Image->getExt();
		$picture["preview"]   = DI::baseUrl() . "/photo/{$resource_id}-{$smallest}." . $Image->getExt();

		if ($r_json) {
			echo json_encode(['picture' => $picture]);
			exit();
		}
		Logger::info("upload done");
		return $picture;
	}

	Logger::info("upload done");

	if ($r_json) {
		echo json_encode(['ok' => true]);
		exit();
	}

	echo  "\n\n" . '[url=' . DI::baseUrl() . '/photos/' . $page_owner_nick . '/image/' . $resource_id . '][img]' . DI::baseUrl() . "/photo/{$resource_id}-{$smallest}.".$Image->getExt()."[/img][/url]\n\n";
	exit();
	// NOTREACHED
}
