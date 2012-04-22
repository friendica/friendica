<?php

require_once('Photo.php');

function wall_upload_post(&$a) {

	if($a->argc > 1) {
	        if(! x($_FILES,'media')) {
		        $nick = $a->argv[1];
		        $r = q("SELECT `user`.*, `contact`.`id` FROM `user` LEFT JOIN `contact` on `user`.`uid` = `contact`.`uid`  WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0 and `contact`.`self` = 1 LIMIT 1",
			        dbesc($nick)
		        );

		        if(! count($r))
                                return;
		}
                else {
			$user_info = api_get_user($a);
		        $r = q("SELECT `user`.*, `contact`.`id` FROM `user` LEFT JOIN `contact` on `user`.`uid` = `contact`.`uid`  WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0 and `contact`.`self` = 1 LIMIT 1",
			        dbesc($user_info['screen_name'])
		        );
                }
	}
	else
		return;


	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid   = $r[0]['uid'];
	$default_cid      = $r[0]['id'];
	$page_owner_nick  = $r[0]['nickname'];
	$community_page   = (($r[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if((local_user()) && (local_user() == $page_owner_uid))
		$can_post = true;
	else {
		if($community_page && remote_user()) {
			$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
				intval(remote_user()),
				intval($page_owner_uid)
			);
			if(count($r)) {
				$can_post = true;
				$visitor = remote_user();
				$default_cid = $visitor;
			}
		}
	}

	if(! $can_post) {
		notice( t('Permission denied.') . EOL );
		killme();
	}

	if(! x($_FILES,'userfile') && ! x($_FILES,'media'))
		killme();

        if(x($_FILES,'userfile')) {
	        $src      = $_FILES['userfile']['tmp_name'];
	        $filename = basename($_FILES['userfile']['name']);
	        $filesize = intval($_FILES['userfile']['size']);
        }
        elseif(x($_FILES,'media')) {
	        $src = $_FILES['media']['tmp_name'];
                $filename = basename($_FILES['media']['name']);
	        $filesize = intval($_FILES['media']['size']);
        }

	$maximagesize = get_config('system','maximagesize');

	if(($maximagesize) && ($filesize > $maximagesize)) {
		echo  sprintf( t('Image exceeds size limit of %d'), $maximagesize) . EOL;
		@unlink($src);
		killme();
	}

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata);

	if(! $ph->is_valid()) {
		echo ( t('Unable to process image.') . EOL);
		@unlink($src);
		killme();
	}

	@unlink($src);

	$width = $ph->getWidth();
	$height = $ph->getHeight();

	$hash = photo_new_resource();
	
	$smallest = 0;

	$defperm = '<' . $default_cid . '>';

	$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 0, 0, $defperm);

	if(! $r) {
		echo ( t('Image upload failed.') . EOL);
		killme();
	}

	if($width > 640 || $height > 640) {
		$ph->scaleImage(640);
		$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 1, 0, $defperm);
		if($r) 
			$smallest = 1;
	}

	if($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store($page_owner_uid, $visitor, $hash, $filename, t('Wall Photos'), 2, 0, $defperm);
		if($r)
			$smallest = 2;
	}

	$basename = basename($filename);


/* mod Waitman Gobble NO WARRANTY */

//if we get the signal then return the image url info in BBCODE, otherwise this outputs the info and bails (for the ajax image uploader on wall post)
        if ($_REQUEST['hush']!='yeah') {

		/*existing code*/
		if(local_user() && intval(get_pconfig(local_user(),'system','plaintext')))
			echo  "\n\n" . '[url=' . $a->get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '][img]' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.jpg[/img][/url]\n\n";
		else
			echo  '<br /><br /><a href="' . $a->get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '" ><img src="' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.jpg\" alt=\"$basename\" /></a><br /><br />";
		/*existing code*/
		
	} else {
                $m = '[url=' . $a->get_baseurl() . '/photos/' . $page_owner_nick . '/image/' . $hash . '][img]' . $a->get_baseurl() . "/photo/{$hash}-{$smallest}.jpg[/img][/url]";
		return($m);
        }
/* mod Waitman Gobble NO WARRANTY */

	killme();
	// NOTREACHED
}
