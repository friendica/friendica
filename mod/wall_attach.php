<?php

require_once('include/attach.php');
require_once('include/datetime.php');

function wall_attach_post(App &$a) {

	$r_json = (x($_GET,'response') && $_GET['response']=='json');

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$r = q("SELECT `user`.*, `contact`.`id` FROM `user` LEFT JOIN `contact` on `user`.`uid` = `contact`.`uid`  WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0 and `contact`.`self` = 1 LIMIT 1",
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
		if ($r_json) {
			echo json_encode(array('error'=>t('Invalid request.')));
			killme();
		}
		return;
	}

	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid   = $r[0]['uid'];
	$page_owner_cid   = $r[0]['id'];
	$page_owner_nick  = $r[0]['nickname'];
	$community_page   = (($r[0]['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if((local_user()) && (local_user() == $page_owner_uid))
		$can_post = true;
	else {
		if($community_page && remote_user()) {
			$contact_id = 0;
			if(is_array($_SESSION['remote'])) {
				foreach($_SESSION['remote'] as $v) {
					if($v['uid'] == $page_owner_uid) {
						$contact_id = $v['cid'];
						break;
					}
				}
			}
			if($contact_id) {

				$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
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
	if(! $can_post) {
		if ($r_json) {
			echo json_encode(array('error'=>t('Permission denied.')));
			killme();
		}
		notice( t('Permission denied.') . EOL );
		killme();
	}

	if(! x($_FILES,'userfile')) {
		if ($r_json) {
			echo json_encode(array('error'=>t('Invalid request.')));
		}
		killme();
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$maxfilesize = get_config('system','maxfilesize');

	/* Found html code written in text field of form,
	 * when trying to upload a file with filesize
	 * greater than upload_max_filesize. Cause is unknown.
	 * Then Filesize gets <= 0.
	 */

	if($filesize <=0) {
		$msg = t('Sorry, maybe your upload is bigger than the PHP configuration allows') . EOL .(t('Or - did you try to upload an empty file?'));
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			notice( $msg. EOL );
		}
		@unlink($src);
		killme();
	}

	if(($maxfilesize) && ($filesize > $maxfilesize)) {
		$msg = sprintf(t('File exceeds size limit of %s'), formatBytes($maxfilesize));
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL ;
		}
		@unlink($src);
		killme();
	}

	$r = q("select sum(octet_length(data)) as total from attach where uid = %d ",
		intval($page_owner_uid)
	);

	$limit = service_class_fetch($page_owner_uid,'attach_upload_limit');

	if(($limit !== false) && (($r[0]['total'] + strlen($imagedata)) > $limit)) {
		$msg = upgrade_message(true);
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL ;
		}
		@unlink($src);
		killme();
	}


	$filedata = @file_get_contents($src);
	$mimetype = z_mime_content_type($filename);
	$hash = get_guid(64);
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
		dbesc('<' . $page_owner_cid . '>'),
		dbesc(''),
		dbesc(''),
		dbesc('')
	);

	@unlink($src);

	if(! $r) {
		$msg =  t('File upload failed.');
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL ;
		}
		killme();
	}

	$r = q("SELECT `id` FROM `attach` WHERE `uid` = %d AND `created` = '%s' AND `hash` = '%s' LIMIT 1",
		intval($page_owner_uid),
		dbesc($created),
		dbesc($hash)
	);

	if (! dbm::is_result($r)) {
		$msg = t('File upload failed.');
		if ($r_json) {
			echo json_encode(array('error'=>$msg));
		} else {
			echo  $msg. EOL ;
		}
		killme();
	}

	if ($r_json) {
		echo json_encode(array('ok'=>true));
		killme();
	}

	$lf = "\n";

	echo  $lf . $lf . '[attachment]' . $r[0]['id'] . '[/attachment]' . $lf;

	killme();
	// NOTREACHED
}
