<?php
/**
 * @file mod/editpost.php
 */
use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;

function editpost_content(App $a) {

	$o = '';

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (! $post_id) {
		notice(L10n::t('Item not found') . EOL);
		return;
	}

	$itm = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($post_id),
		intval(local_user())
	);

	if (! DBM::is_result($itm)) {
		notice(L10n::t('Item not found') . EOL);
		return;
	}

	$geotag = '';

	$o .= replace_macros(get_markup_template("section_title.tpl"),[
		'$title' => L10n::t('Edit post')
	]);

	$tpl = get_markup_template('jot-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$ispublic' => '&nbsp;', // L10n::t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->user['nickname']
	]);

	$tpl = get_markup_template('jot-end.tpl');
	$a->page['end'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$ispublic' => '&nbsp;', // L10n::t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->user['nickname']
	]);


	$tpl = get_markup_template("jot.tpl");

	if (strlen($itm['allow_cid']) || strlen($itm['allow_gid']) || strlen($itm['deny_cid']) || strlen($itm['deny_gid'])) {
		$lockstate = 'lock';
	} else {
		$lockstate = 'unlock';
	}

	$jotplugins = '';
	$jotnets = '';

	$mail_disabled = ((function_exists('imap_open') && (! Config::get('system','imap_disabled'))) ? 0 : 1);

	$mail_enabled = false;
	$pubmail_enabled = false;

	if(! $mail_disabled) {
		$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1",
			intval(local_user())
		);
		if (DBM::is_result($r)) {
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
          	. L10n::t("Post to Email") . '</div>';
	}*/



	Addon::callHooks('jot_tool', $jotplugins);
	//Addon::callHooks('jot_networks', $jotnets);


	//$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));

	$o .= replace_macros($tpl,[
		'$is_edit' => true,
		'$return_path' => $_SESSION['return_url'],
		'$action' => 'item',
		'$share' => L10n::t('Save'),
		'$upload' => L10n::t('Upload photo'),
		'$shortupload' => L10n::t('upload photo'),
		'$attach' => L10n::t('Attach file'),
		'$shortattach' => L10n::t('attach file'),
		'$weblink' => L10n::t('Insert web link'),
		'$shortweblink' => L10n::t('web link'),
		'$video' => L10n::t('Insert video link'),
		'$shortvideo' => L10n::t('video link'),
		'$audio' => L10n::t('Insert audio link'),
		'$shortaudio' => L10n::t('audio link'),
		'$setloc' => L10n::t('Set your location'),
		'$shortsetloc' => L10n::t('set location'),
		'$noloc' => L10n::t('Clear browser location'),
		'$shortnoloc' => L10n::t('clear location'),
		'$wait' => L10n::t('Please wait'),
		'$permset' => L10n::t('Permission settings'),
		'$ptyp' => $itm[0]['type'],
		'$content' => undo_post_tagging($itm[0]['body']),
		'$post_id' => $post_id,
		'$baseurl' => System::baseUrl(),
		'$defloc' => $a->user['default-location'],
		'$visitor' => 'none',
		'$pvisit' => 'none',
		'$emailcc' => L10n::t('CC: email addresses'),
		'$public' => L10n::t('Public post'),
		'$jotnets' => $jotnets,
		'$title' => htmlspecialchars($itm[0]['title']),
		'$placeholdertitle' => L10n::t('Set title'),
		'$category' => file_tag_file_to_list($itm[0]['file'], 'category'),
		'$placeholdercategory' => (Feature::isEnabled(local_user(),'categories') ? L10n::t("Categories \x28comma-separated list\x29") : ''),
		'$emtitle' => L10n::t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $lockstate,
		'$acl' => '', // populate_acl((($group) ? $group_acl : $a->user)),
		'$bang' => ($lockstate === 'lock' ? '!' : ''),
		'$profile_uid' => $_SESSION['uid'],
		'$preview' => L10n::t('Preview'),
		'$jotplugins' => $jotplugins,
		'$sourceapp' => L10n::t($a->sourcename),
		'$cancel' => L10n::t('Cancel'),
		'$rand_num' => random_digits(12),

		//jot nav tab (used in some themes)
		'$message' => L10n::t('Message'),
		'$browser' => L10n::t('Browser'),
		'$shortpermset' => L10n::t('permissions'),
	]);

	return $o;
}
