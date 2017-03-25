<?php

/**
 * @file mod/wall_upload.php
 * @brief Module for uploading a picture to the profile wall
 * 
 * By default the picture will be stored in the photo album with the name Wall Photos.
 * You can specify a different album by adding an optional query string "album="
 * to the url
 */

use \Friendica\Core\Config;

require_once 'include/Photo.php';


function wall_upload_post(App $a, $desktopmode = true) {

	logger("wall upload: starting new upload", LOGGER_DEBUG);

	$r_json = (x($_GET, 'response') && $_GET['response'] == 'json');
	$album = (x($_GET, 'album') ? notags(trim($_GET['album'])) : '');

	if ($a->argc > 1) {
		if (! x($_FILES, 'media')) {
			$nick = $a->argv[1];
			$r = q("SELECT `user`.*, `contact`.`id` FROM `user`
				INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
				WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0
				AND `contact`.`self` = 1 LIMIT 1",
				dbesc($nick)
			);

			if (! dbm::is_result($r)) {
				if ($r_json) {
					echo json_encode(array('error'=>t('Invalid request.')));
					killme();
				}
				return;
			}
		} else {
			$user_info = api_get_user($a);
			$r = q("SELECT `user`.*, `contact`.`id` FROM `user`
				INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
				WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0
				AND `contact`.`self` = 1 LIMIT 1",
				dbesc($user_info['screen_name'])
			);
		}
	} else {
		if ($r_json) {
			echo json_encode(array('error'=>t('Invalid request.')));
			killme();
		}
		return;
	}

	/*
	 * Setup permissions structures
	 */
	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid   = $r[0]['uid'];
	$default_cid      = $r[0]['id'];
	$page_owner_nick  = $r[0]['nickname'];
	$community_page   = (($r[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if ((local_user()) && (local_user() == $page_owner_uid)) {
		$can_post = true;
	} else {
		if ($community_page && remote_user()) {
			$contact_id = 0;
			if (is_array($_SESSION['remote'])) {
				foreach ($_SESSION['remote'] as $v) {
					if ($v['uid'] == $page_owner_uid) {
						$contact_id = $v['cid'];
						break;
					}
				}
			}

			if ($contact_id) {
				$r = q("SELECT `uid` FROM `contact`
					WHERE `blocked` = 0 AND `pending` = 0
					AND `id` = %d AND `uid` = %d LIMIT 1",
					intval($contact_id),
					intval($page_owner_uid)
				);
				if (dbm::is_result($r)) {
					$can_post = true;
					$visitor = $contact_id;
				}
			}
		}
	}


	if (! $can_post) {
		if ($r_json) {
			echo json_encode(array('error'=>t('Permission denied.')));
			killme();
		}
		notice(t('Permission denied.') . EOL);
		killme();
	}

	if (! x($_FILES, 'userfile') && ! x($_FILES, 'media')) {
		if ($r_json) {
			echo json_encode(array('error'=>t('Invalid request.')));
		}
		killme();
	}

	$src = "";
	if (x($_FILES, 'userfile')) {
		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];

	} elseif (x($_FILES, 'media')) {
		if (is_array($_FILES['media']['tmp_name'])) {
			$src = $_FILES['media']['tmp_name'][0];
		} else {
			$src = $_FILES['media']['tmp_name'];
		}

		if (is_array($_FILES['media']['name'])) {
			$filename = basename($_FILES['media']['name'][0]);
		} else {
			$filename = basename($_FILES['media']['name']);
		}

		if (is_array($_FILES['media']['size'])) {
			$filesize = intval($_FILES['media']['size'][0]);
		} else {
			$filesize = intval($_FILES['media']['size']);
		}

		if (is_array($_FILES['media']['type'])) {
			$filetype = $_FILES['media']['type'][0];
		} else {
			$filetype = $_FILES['media']['type'];
		}
	}

	if ($src=="") {
		if ($r_json) {
			echo json_encode(array('error'=>t('Invalid request.')));
			killme();
		}
		notice(t('Invalid request.').EOL);
		killme();
	}

	// This is a special treatment for picture upload from Twidere
	if (($filename == "octet-stream") && ($filetype != "")) {
		$filename = $filetype;
		$filetype = "";
	}

	if ($filetype=="") {
		$filetype=guess_image_type($filename);
	}

	// If there is a temp name, then do a manual check
	// This is more reliable than the provided value

	$imagedata = getimagesize($src);
	if ($imagedata) {
		$filetype = $imagedata['mime'];
	}

	logger("File upload src: " . $src . " - filename: " . $filename .
		" - size: " . $filesize . " - type: " . $filetype, LOGGER_DEBUG);

	$maximagesize = Config::get('system', 'maximagesize');

	if (($maximagesize) && ($filesize > $maximagesize)) {
		$msg = sprintf(t('Image exceeds size limit of %s'), formatBytes($maximagesize));
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL;
		}
		@unlink($src);
		killme();
	}


	$limit = service_class_fetch($page_owner_uid, 'photo_upload_limit');

	if ($limit) {
		$r = q("SELECT SUM(OCTET_LENGTH(`data`)) AS `total` FROM `photo`
			WHERE `uid` = %d AND `scale` = 0
			AND `album` != 'Contact Photos' ",
			intval($page_owner_uid)
		);
		$size = $r[0]['total'];

		if (($size + strlen($imagedata)) > $limit) {
			$msg = upgrade_message(true);
			if ($r_json) {
				echo json_encode(array('error'=>$msg));
			} else {
				echo  $msg. EOL;
			}
			@unlink($src);
			killme();
		}
	}

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata, $filetype);

	if (! $ph->is_valid()) {
		$msg = t('Unable to process image.');
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL;
		}
		@unlink($src);
		killme();
	}

	$ph->orient($src);
	@unlink($src);

	$max_length = Config::get('system', 'max_image_length');
	if (! $max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$ph->scaleImage($max_length);
		logger("File upload: Scaling picture to new size " . $max_length, LOGGER_DEBUG);
	}

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$hash = photo_new_resource();

	$smallest = 0;

	// If we don't have an album name use the Wall Photos album
	if (! strlen($album)) {
		$album = t('Wall Photos');
	}

	$defperm = '<' . $default_cid . '>';

	$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, $album, 0, 0, $defperm);

	if (! $r) {
		$msg = t('Image upload failed.');
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL;
		}
		killme();
	}

	if ($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, $album, 1, 0, $defperm);
		if ($r) {
			$smallest = 1;
		}
	}

	if ($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, $album, 2, 0, $defperm);
		if ($r && ($smallest == 0)) {
			$smallest = 2;
		}
	}

	$basename = basename($filename);

	if (!$desktopmode) {
		$r = q("SELECT `id`, `datasize`, `width`, `height`, `type` FROM `photo`
			WHERE `resource-id` = '%s'
			ORDER BY `width` DESC LIMIT 1",
			$hash
		);
		if (!$r) {
			if ($r_json) {
				echo json_encode(array('error'=>''));
				killme();
			}
			return false;
		}
		$picture = array();

		$picture["id"]        = $r[0]["id"];
		$picture["size"]      = $r[0]["datasize"];
		$picture["width"]     = $r[0]["width"];
		$picture["height"]    = $r[0]["height"];
		$picture["type"]      = $r[0]["type"];
		$picture["albumpage"] = App::get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash;
		$picture["picture"]   = App::get_baseurl() . "/photo/{$hash}-0." . $ph->getExt();
		$picture["preview"]   = App::get_baseurl() . "/photo/{$hash}-{$smallest}." . $ph->getExt();

		if ($r_json) {
			echo json_encode(array('picture'=>$picture));
			killme();
		}
		return $picture;
	}


	if ($r_json) {
		echo json_encode(array('ok'=>true));
		killme();
	}

/* mod Waitman Gobble NO WARRANTY */
	// if we get the signal then return the image url info in BBCODE
	if ($_REQUEST['hush']!='yeah') {
		echo  "\n\n" . '[url=' . App::get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '][img]' . App::get_baseurl() . "/photo/{$hash}-{$smallest}.".$ph->getExt()."[/img][/url]\n\n";
	} else {
		$m = '[url='.App::get_baseurl().'/photos/'.$page_owner_nick.'/image/'.$hash.'][img]'.App::get_baseurl()."/photo/{$hash}-{$smallest}.".$ph->getExt()."[/img][/url]";
		return($m);
	}
/* mod Waitman Gobble NO WARRANTY */

	killme();
	// NOTREACHED
}
