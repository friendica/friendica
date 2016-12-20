<?php

require_once('include/acl_selectors.php');

function editpost_content(App &$a) {

	$o = '';

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (! $post_id) {
		notice( t('Item not found') . EOL);
		return;
	}

	$itm = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($post_id),
		intval(local_user())
	);

	if (! dbm::is_result($itm)) {
		notice( t('Item not found') . EOL);
		return;
	}

/*	$plaintext = false;
	if( local_user() && intval(get_pconfig(local_user(),'system','plaintext')) || !feature_enabled(local_user(),'richtext') )
		$plaintext = true;*/
	$plaintext = true;
	if( local_user() && feature_enabled(local_user(),'richtext') )
		$plaintext = false;


	$o .= replace_macros(get_markup_template("section_title.tpl"),array(
		'$title' => t('Edit post')
	));

	$tpl = get_markup_template('jot-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, array(
		'$baseurl' => App::get_baseurl(),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$ispublic' => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->user['nickname']
	));

	$tpl = get_markup_template('jot-end.tpl');
	$a->page['end'] .= replace_macros($tpl, array(
		'$baseurl' => App::get_baseurl(),
		'$editselect' =>  (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$ispublic' => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->user['nickname']
	));


	$tpl = get_markup_template("jot.tpl");

	if(($group) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid'])))))
		$lockstate = 'lock';
	else
		$lockstate = 'unlock';

	$jotplugins = '';
	$jotnets = '';

	$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	$mail_enabled = false;
	$pubmail_enabled = false;

	if(! $mail_disabled) {
		$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1",
			intval(local_user())
		);
		if (dbm::is_result($r)) {
			$mail_enabled = true;
			if(intval($r[0]['pubmail']))
				$pubmail_enabled = true;
		}
	}

	// I don't think there's any need for the $jotnets when editing the post,
	// and including them makes it difficult for the JS-free theme, so let's
	// disable them
/*	if($mail_enabled) {
       $selected = (($pubmail_enabled) ? ' checked="checked" ' : '');
		$jotnets .= '<div class="profile-jot-net"><input type="checkbox" name="pubmail_enable"' . $selected . ' value="1" /> '
          	. t("Post to Email") . '</div>';
	}*/



	call_hooks('jot_tool', $jotplugins);
	//call_hooks('jot_networks', $jotnets);


	//$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));

	$o .= replace_macros($tpl,array(
		'$is_edit' => true,
		'$return_path' => $_SESSION['return_url'],
		'$action' => 'item',
		'$share' => t('Save'),
		'$upload' => t('Upload photo'),
		'$shortupload' => t('upload photo'),
		'$attach' => t('Attach file'),
		'$shortattach' => t('attach file'),
		'$weblink' => t('Insert web link'),
		'$shortweblink' => t('web link'),
		'$video' => t('Insert video link'),
		'$shortvideo' => t('video link'),
		'$audio' => t('Insert audio link'),
		'$shortaudio' => t('audio link'),
		'$setloc' => t('Set your location'),
		'$shortsetloc' => t('set location'),
		'$noloc' => t('Clear browser location'),
		'$shortnoloc' => t('clear location'),
		'$wait' => t('Please wait'),
		'$permset' => t('Permission settings'),
		'$ptyp' => $itm[0]['type'],
		'$content' => undo_post_tagging($itm[0]['body']),
		'$post_id' => $post_id,
		'$baseurl' => App::get_baseurl(),
		'$defloc' => $a->user['default-location'],
		'$visitor' => 'none',
		'$pvisit' => 'none',
		'$emailcc' => t('CC: email addresses'),
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$title' => htmlspecialchars($itm[0]['title']),
		'$placeholdertitle' => t('Set title'),
		'$category' => file_tag_file_to_list($itm[0]['file'], 'category'),
		'$placeholdercategory' => (feature_enabled(local_user(),'categories') ? t('Categories (comma-separated list)') : ''),
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $lockstate,
		'$acl' => '', // populate_acl((($group) ? $group_acl : $a->user)),
		'$bang' => (($group) ? '!' : ''),
		'$profile_uid' => $_SESSION['uid'],
		'$preview' => t('Preview'),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => t($a->sourcename),
		'$cancel' => t('Cancel'),
		'$rand_num' => random_digits(12),

		//jot nav tab (used in some themes)
		'$message' => t('Message'),
		'$browser' => t('Browser'),
		'$shortpermset' => t('permissions'),
	));

	return $o;

}


