<?php

require_once('include/attach.php');
require_once('include/datetime.php');

function wall_attach_post(&$a) {

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($nick)
		);
		if(! count($r))
			return;

	}
	else
		return;

	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid   = $r[0]['uid'];
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
			}
		}
	}

	if(! $can_post) {
		notice( t('Permission denied.') . EOL );
		killme();
	}

	if(! x($_FILES,'userfile'))
		killme();

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$maxfilesize = get_config('system','maxfilesize');

	if(($maxfilesize) && ($filesize > $maxfilesize)) {
		notice( sprintf(t('File exceeds size limit of %d'), $maxfilesize) . EOL);
		@unlink($src);
		return;
	}

	$filedata = @file_get_contents($src);
	$mimetype = z_mime_content_type($filename);
	$hash = random_string();
	$created = datetime_convert();
	$r = q("INSERT INTO `attach` ( `uid`, `hash`, `filename`, `filetype`, `filesize`, `data`, `created`, `edited`, `allow_cid`, `allow_gid`,`deny_cid`, `deny_gid` )
		VALUES ( %d, '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ",
		intval($page_owner_uid),
		dbesc($hash),
		dbesc($filename),
		dbesc($mimetype),
		intval($filesize),
		dbesc($filedata),
		dbesc($created),
		dbesc($created),
		dbesc('<' . $page_owner_uid . '>'),
		dbesc(''),
		dbesc(''),
		dbesc('')
	);		

	@unlink($src);

	if(! $r) {
		echo ( t('File upload failed.') . EOL);
		killme();
	}

	$r = q("SELECT `id` FROM `attach` WHERE `uid` = %d AND `created` = '%s' AND `hash` = '%s' LIMIT 1",
		intval($page_owner_uid),
		dbesc($created),
		dbesc($hash)
	);

	if(! count($r)) {
		echo ( t('File upload failed.') . EOL);
		killme();
	}

	echo  '<br /><br />[attachment]' . $r[0]['id'] . '[/attachment]' . '<br />';

	killme();
	// NOTREACHED
}
