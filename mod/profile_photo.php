<?php
/**
 * @file mod/profile_photo.php
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Object\Image;
use Friendica\Util\Strings;

function profile_photo_init(App $a)
{
	if (!local_user()) {
		return;
	}

	Profile::load($a, $a->user['nickname']);
}

function profile_photo_post(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	BaseModule::checkFormSecurityTokenRedirectOnError('/profile_photo', 'profile_photo');

	if (!empty($_POST['cropfinal']) && $_POST['cropfinal'] == 1) {

		// unless proven otherwise
		$is_default_profile = 1;

		if ($_REQUEST['profile']) {
			$r = q("select id, `is-default` from profile where id = %d and uid = %d limit 1", intval($_REQUEST['profile']),
				intval(local_user())
			);

			if (DBA::isResult($r) && (!intval($r[0]['is-default']))) {
				$is_default_profile = 0;
			}
		}



		// phase 2 - we have finished cropping

		if ($a->argc != 2) {
			notice(L10n::t('Image uploaded but image cropping failed.') . EOL);
			return;
		}

		$image_id = $a->argv[1];

		if (substr($image_id, -2, 1) == '-') {
			$scale = substr($image_id, -1, 1);
			$image_id = substr($image_id, 0, -2);
		}


		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$base_image = Photo::selectFirst([], ['resource-id' => $image_id, 'uid' => local_user(), 'scale' => $scale]);

		$path = 'profile/' . $a->user['nickname'];
		if (DBA::isResult($base_image)) {

			$Image = Photo::getImageForPhoto($base_image);
			if ($Image->isValid()) {
				$Image->crop(300, $srcX, $srcY, $srcW, $srcH);

				$r = Photo::store($Image, local_user(), 0, $base_image['resource-id'], $base_image['filename'],
						L10n::t('Profile Photos'), 4, $is_default_profile);

				if ($r === false) {
					notice(L10n::t('Image size reduction [%s] failed.', "300") . EOL);
				}

				$Image->scaleDown(80);

				$r = Photo::store($Image, local_user(), 0, $base_image['resource-id'], $base_image['filename'],
						L10n::t('Profile Photos'), 5, $is_default_profile);

				if ($r === false) {
					notice(L10n::t('Image size reduction [%s] failed.', "80") . EOL);
				}

				$Image->scaleDown(48);

				$r = Photo::store($Image, local_user(), 0, $base_image['resource-id'], $base_image['filename'],
						L10n::t('Profile Photos'), 6, $is_default_profile);

				if ($r === false) {
					notice(L10n::t('Image size reduction [%s] failed.', "48") . EOL);
				}

				// If setting for the default profile, unset the profile photo flag from any other photos I own

				if ($is_default_profile) {
					q("UPDATE `photo` SET `profile` = 0 WHERE `profile` = 1 AND `resource-id` != '%s' AND `uid` = %d",
						DBA::escape($base_image['resource-id']), intval(local_user())
					);
				} else {
					q("update profile set photo = '%s', thumb = '%s' where id = %d and uid = %d",
						DBA::escape(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-4.' . $Image->getExt()),
						DBA::escape(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-5.' . $Image->getExt()),
						intval($_REQUEST['profile']), intval(local_user())
					);
				}

				Contact::updateSelfFromUserID(local_user(), true);

				info(L10n::t('Shift-reload the page or clear browser cache if the new photo does not display immediately.') . EOL);
				// Update global directory in background
				if ($path && strlen(Config::get('system', 'directory'))) {
					Worker::add(PRIORITY_LOW, "Directory", $a->getBaseURL() . '/' . $path);
				}

				Worker::add(PRIORITY_LOW, 'ProfileUpdate', local_user());
			} else {
				notice(L10n::t('Unable to process image') . EOL);
			}
		}

		DI::baseUrl()->redirect($path);
		return; // NOTREACHED
	}

	$src = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];
	if ($filetype == "") {
		$filetype = Image::guessType($filename);
	}

	$maximagesize = Config::get('system', 'maximagesize');

	if (($maximagesize) && ($filesize > $maximagesize)) {
		notice(L10n::t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize)) . EOL);
		@unlink($src);
		return;
	}

	$imagedata = @file_get_contents($src);
	$ph = new Image($imagedata, $filetype);

	if (!$ph->isValid()) {
		notice(L10n::t('Unable to process image.') . EOL);
		@unlink($src);
		return;
	}

	$ph->orient($src);
	@unlink($src);

	$imagecrop = profile_photo_crop_ui_head($a, $ph);
	DI::baseUrl()->redirect('profile_photo/use/' . $imagecrop['hash']);
}

function profile_photo_content(App $a)
{

	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$newuser = false;

	if ($a->argc == 2 && $a->argv[1] === 'new') {
		$newuser = true;
	}

	$imagecrop = [];

	if (isset($a->argv[1]) && $a->argv[1] == 'use' && $a->argc >= 3) {
		// BaseModule::checkFormSecurityTokenRedirectOnError('/profile_photo', 'profile_photo');

		$resource_id = $a->argv[2];
		//die(":".local_user());

		$r = Photo::selectToArray([], ["resource-id" => $resource_id, "uid" => local_user()], ["order" => ["scale" => false]]);
		if (!DBA::isResult($r)) {
			notice(L10n::t('Permission denied.') . EOL);
			return;
		}

		$havescale = false;
		foreach ($r as $rr) {
			if ($rr['scale'] == 5) {
				$havescale = true;
			}
		}

		// set an already uloaded photo as profile photo
		// if photo is in 'Profile Photos', change it in db
		if (($r[0]['album'] == L10n::t('Profile Photos')) && ($havescale)) {
			q("UPDATE `photo` SET `profile`=0 WHERE `profile`=1 AND `uid`=%d", intval(local_user()));

			q("UPDATE `photo` SET `profile`=1 WHERE `uid` = %d AND `resource-id` = '%s'", intval(local_user()),
				DBA::escape($resource_id)
			);

			Contact::updateSelfFromUserID(local_user(), true);

			// Update global directory in background
			$url = $_SESSION['my_url'];
			if ($url && strlen(Config::get('system', 'directory'))) {
				Worker::add(PRIORITY_LOW, "Directory", $url);
			}

			DI::baseUrl()->redirect('profile/' . $a->user['nickname']);
			return; // NOTREACHED
		}
		$ph = Photo::getImageForPhoto($r[0]);
		
		$imagecrop = profile_photo_crop_ui_head($a, $ph);
		// go ahead as we have jus uploaded a new photo to crop
	}

	$profiles = q("select `id`,`profile-name` as `name`,`is-default` as `default` from profile where uid = %d",
		intval(local_user())
	);

	if (empty($imagecrop)) {
		$tpl = Renderer::getMarkupTemplate('profile_photo.tpl');

		$o = Renderer::replaceMacros($tpl,
			[
			'$user' => $a->user['nickname'],
			'$lbl_upfile' => L10n::t('Upload File:'),
			'$lbl_profiles' => L10n::t('Select a profile:'),
			'$title' => L10n::t('Upload Profile Photo'),
			'$submit' => L10n::t('Upload'),
			'$profiles' => $profiles,
			'$form_security_token' => BaseModule::getFormSecurityToken("profile_photo"),
			'$select' => sprintf('%s %s', L10n::t('or'),
				($newuser) ? '<a href="' . System::baseUrl() . '">' . L10n::t('skip this step') . '</a>' : '<a href="' . System::baseUrl() . '/photos/' . $a->user['nickname'] . '">' . L10n::t('select a photo from your photo albums') . '</a>')
		]);

		return $o;
	} else {
		$filename = $imagecrop['hash'] . '-' . $imagecrop['resolution'] . '.' . $imagecrop['ext'];
		$tpl = Renderer::getMarkupTemplate("cropbody.tpl");
		$o = Renderer::replaceMacros($tpl,
			[
			'$filename'  => $filename,
			'$profile'   => (isset($_REQUEST['profile']) ? intval($_REQUEST['profile']) : 0),
			'$resource'  => $imagecrop['hash'] . '-' . $imagecrop['resolution'],
			'$image_url' => System::baseUrl() . '/photo/' . $filename,
			'$title'     => L10n::t('Crop Image'),
			'$desc'      => L10n::t('Please adjust the image cropping for optimum viewing.'),
			'$form_security_token' => BaseModule::getFormSecurityToken("profile_photo"),
			'$done'      => L10n::t('Done Editing')
		]);
		return $o;
	}
}

function profile_photo_crop_ui_head(App $a, Image $image)
{
	$max_length = Config::get('system', 'max_image_length');
	if (!$max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$image->scaleDown($max_length);
	}

	$width = $image->getWidth();
	$height = $image->getHeight();

	if ($width < 175 || $height < 175) {
		$image->scaleUp(300);
		$width = $image->getWidth();
		$height = $image->getHeight();
	}

	$hash = Photo::newResource();


	$smallest = 0;
	$filename = '';

	$r = Photo::store($image, local_user(), 0, $hash, $filename, L10n::t('Profile Photos'), 0);

	if ($r) {
		info(L10n::t('Image uploaded successfully.') . EOL);
	} else {
		notice(L10n::t('Image upload failed.') . EOL);
	}

	if ($width > 640 || $height > 640) {
		$image->scaleDown(640);
		$r = Photo::store($image, local_user(), 0, $hash, $filename, L10n::t('Profile Photos'), 1);

		if ($r === false) {
			notice(L10n::t('Image size reduction [%s] failed.', "640") . EOL);
		} else {
			$smallest = 1;
		}
	}

	$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate("crophead.tpl"), []);

	$imagecrop = [
		'hash'       => $hash,
		'resolution' => $smallest,
		'ext'        => $image->getExt(),
	];

	return $imagecrop;
}
