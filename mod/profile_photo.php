<?php
/**
 * @file mod/profile_photo.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Object\Image;

function profile_photo_init(App $a)
{
	if (! local_user()) {
		return;
	}

	Profile::load($a, $a->user['nickname']);
}

function profile_photo_post(App $a) {

	if (! local_user()) {
		notice ( t('Permission denied.') . EOL );
		return;
	}

	check_form_security_token_redirectOnErr('/profile_photo', 'profile_photo');

	if((x($_POST,'cropfinal')) && ($_POST['cropfinal'] == 1)) {

		// unless proven otherwise
		$is_default_profile = 1;

		if($_REQUEST['profile']) {
			$r = q("select id, `is-default` from profile where id = %d and uid = %d limit 1",
				intval($_REQUEST['profile']),
				intval(local_user())
			);
			if (DBM::isResult($r) && (! intval($r[0]['is-default'])))
				$is_default_profile = 0;
		}



		// phase 2 - we have finished cropping

		if($a->argc != 2) {
			notice( t('Image uploaded but image cropping failed.') . EOL );
			return;
		}

		$image_id = $a->argv[1];

		if(substr($image_id,-2,1) == '-') {
			$scale = substr($image_id,-1,1);
			$image_id = substr($image_id,0,-2);
		}


		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = %d LIMIT 1",
			dbesc($image_id),
			dbesc(local_user()),
			intval($scale));

		if (DBM::isResult($r)) {

			$base_image = $r[0];

			$Image = new Image($base_image['data'], $base_image['type']);
			if ($Image->isValid()) {
				$Image->crop(175,$srcX,$srcY,$srcW,$srcH);

				$r = Photo::store($Image, local_user(), 0, $base_image['resource-id'],$base_image['filename'], t('Profile Photos'), 4, $is_default_profile);

				if ($r === false) {
					notice ( sprintf(t('Image size reduction [%s] failed.'),"175") . EOL );
				}

				$Image->scaleDown(80);

				$r = Photo::store($Image, local_user(), 0, $base_image['resource-id'],$base_image['filename'], t('Profile Photos'), 5, $is_default_profile);

				if ($r === false) {
					notice( sprintf(t('Image size reduction [%s] failed.'),"80") . EOL );
				}

				$Image->scaleDown(48);

				$r = Photo::store($Image, local_user(), 0, $base_image['resource-id'],$base_image['filename'], t('Profile Photos'), 6, $is_default_profile);

				if ($r === false) {
					notice( sprintf(t('Image size reduction [%s] failed.'),"48") . EOL );
				}

				// If setting for the default profile, unset the profile photo flag from any other photos I own

				if($is_default_profile) {
					$r = q("UPDATE `photo` SET `profile` = 0 WHERE `profile` = 1 AND `resource-id` != '%s' AND `uid` = %d",
						dbesc($base_image['resource-id']),
						intval(local_user())
					);

					$r = q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s'  WHERE `self` AND `uid` = %d",
						dbesc(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-4.' . $Image->getExt()),
						dbesc(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-5.' . $Image->getExt()),
						dbesc(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-6.' . $Image->getExt()),
						intval(local_user())
					);
				} else {
					$r = q("update profile set photo = '%s', thumb = '%s' where id = %d and uid = %d",
						dbesc(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-4.' . $Image->getExt()),
						dbesc(System::baseUrl() . '/photo/' . $base_image['resource-id'] . '-5.' . $Image->getExt()),
						intval($_REQUEST['profile']),
						intval(local_user())
					);
				}

				// we'll set the updated profile-photo timestamp even if it isn't the default profile,
				// so that browsers will do a cache update unconditionally

				$r = q("UPDATE `contact` SET `avatar-date` = '%s' WHERE `self` = 1 AND `uid` = %d",
					dbesc(datetime_convert()),
					intval(local_user())
				);

				info( t('Shift-reload the page or clear browser cache if the new photo does not display immediately.') . EOL);
				// Update global directory in background
				$url = System::baseUrl() . '/profile/' . $a->user['nickname'];
				if ($url && strlen(Config::get('system','directory'))) {
					Worker::add(PRIORITY_LOW, "Directory", $url);
				}

				Worker::add(PRIORITY_LOW, 'ProfileUpdate', local_user());
			} else {
				notice( t('Unable to process image') . EOL);
			}
		}

		goaway(System::baseUrl() . '/profiles');
		return; // NOTREACHED
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];
	if ($filetype == "") {
		$filetype = Image::guessType($filename);
	}

	$maximagesize = Config::get('system', 'maximagesize');

	if (($maximagesize) && ($filesize > $maximagesize)) {
		notice(sprintf(t('Image exceeds size limit of %s'), formatBytes($maximagesize)) . EOL);
		@unlink($src);
		return;
	}

	$imagedata = @file_get_contents($src);
	$ph = new Image($imagedata, $filetype);

	if (! $ph->isValid()) {
		notice(t('Unable to process image.') . EOL);
		@unlink($src);
		return;
	}

	$ph->orient($src);
	@unlink($src);
	return profile_photo_crop_ui_head($a, $ph);
}


function profile_photo_content(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	$newuser = false;

	if($a->argc == 2 && $a->argv[1] === 'new')
		$newuser = true;

	if( $a->argv[1]=='use'){
		if ($a->argc<3){
			notice( t('Permission denied.') . EOL );
			return;
		};

//		check_form_security_token_redirectOnErr('/profile_photo', 'profile_photo');

		$resource_id = $a->argv[2];
		//die(":".local_user());
		$r=q("SELECT * FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' ORDER BY `scale` ASC",
			intval(local_user()),
			dbesc($resource_id)
			);
		if (!DBM::isResult($r)){
			notice( t('Permission denied.') . EOL );
			return;
		}
		$havescale = false;
		foreach ($r as $rr) {
			if($rr['scale'] == 5)
				$havescale = true;
		}

		// set an already uloaded photo as profile photo
		// if photo is in 'Profile Photos', change it in db
		if (($r[0]['album']== t('Profile Photos')) && ($havescale)){
			$r=q("UPDATE `photo` SET `profile`=0 WHERE `profile`=1 AND `uid`=%d",
				intval(local_user()));

			$r=q("UPDATE `photo` SET `profile`=1 WHERE `uid` = %d AND `resource-id` = '%s'",
				intval(local_user()),
				dbesc($resource_id)
				);

			$r = q("UPDATE `contact` SET `avatar-date` = '%s' WHERE `self` = 1 AND `uid` = %d",
				dbesc(datetime_convert()),
				intval(local_user())
			);

			// Update global directory in background
			$url = $_SESSION['my_url'];
			if ($url && strlen(Config::get('system','directory'))) {
				Worker::add(PRIORITY_LOW, "Directory", $url);
			}

			goaway(System::baseUrl() . '/profiles');
			return; // NOTREACHED
		}
		$ph = new Image($r[0]['data'], $r[0]['type']);
		profile_photo_crop_ui_head($a, $ph);
		// go ahead as we have jus uploaded a new photo to crop
	}

	$profiles = q("select `id`,`profile-name` as `name`,`is-default` as `default` from profile where uid = %d",
		intval(local_user())
	);


	if(! x($a->config,'imagecrop')) {

		$tpl = get_markup_template('profile_photo.tpl');

		$o .= replace_macros($tpl,[
			'$user' => $a->user['nickname'],
			'$lbl_upfile' => t('Upload File:'),
			'$lbl_profiles' => t('Select a profile:'),
			'$title' => t('Upload Profile Photo'),
			'$submit' => t('Upload'),
			'$profiles' => $profiles,
			'$form_security_token' => get_form_security_token("profile_photo"),
			'$select' => sprintf('%s %s', t('or'), ($newuser) ? '<a href="' . System::baseUrl() . '">' . t('skip this step') . '</a>' : '<a href="'. System::baseUrl() . '/photos/' . $a->user['nickname'] . '">' . t('select a photo from your photo albums') . '</a>')
		]);

		return $o;
	}
	else {
		$filename = $a->config['imagecrop'] . '-' . $a->config['imagecrop_resolution'] . '.'.$a->config['imagecrop_ext'];
		$resolution = $a->config['imagecrop_resolution'];
		$tpl = get_markup_template("cropbody.tpl");
		$o .= replace_macros($tpl,[
			'$filename' => $filename,
			'$profile' => intval($_REQUEST['profile']),
			'$resource' => $a->config['imagecrop'] . '-' . $a->config['imagecrop_resolution'],
			'$image_url' => System::baseUrl() . '/photo/' . $filename,
			'$title' => t('Crop Image'),
			'$desc' => t('Please adjust the image cropping for optimum viewing.'),
			'$form_security_token' => get_form_security_token("profile_photo"),
			'$done' => t('Done Editing')
		]);
		return $o;
	}

	return; // NOTREACHED
}


function profile_photo_crop_ui_head(App $a, Image $Image) {
	$max_length = Config::get('system','max_image_length');
	if (! $max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$Image->scaleDown($max_length);
	}

	$width = $Image->getWidth();
	$height = $Image->getHeight();

	if ($width < 175 || $height < 175) {
		$Image->scaleUp(200);
		$width = $Image->getWidth();
		$height = $Image->getHeight();
	}

	$hash = photo_new_resource();


	$smallest = 0;

	$r = Photo::store($Image, local_user(), 0 , $hash, $filename, t('Profile Photos'), 0 );

	if ($r) {
		info( t('Image uploaded successfully.') . EOL );
	} else {
		notice( t('Image upload failed.') . EOL );
	}

	if ($width > 640 || $height > 640) {
		$Image->scaleDown(640);
		$r = Photo::store($Image, local_user(), 0 , $hash, $filename, t('Profile Photos'), 1 );

		if ($r === false) {
			notice( sprintf(t('Image size reduction [%s] failed.'),"640") . EOL );
		} else {
			$smallest = 1;
		}
	}

	$a->config['imagecrop'] = $hash;
	$a->config['imagecrop_resolution'] = $smallest;
	$a->config['imagecrop_ext'] = $Image->getExt();
	$a->page['htmlhead'] .= replace_macros(get_markup_template("crophead.tpl"), []);
	$a->page['end'] .= replace_macros(get_markup_template("cropend.tpl"), []);
	return;
}
