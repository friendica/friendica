<?php
/**
 * @file mod/editpost.php
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\FileTag;
use Friendica\Model\Item;
use Friendica\Registry\App as AppR;
use Friendica\Util\Crypto;

function editpost_content(App $a)
{
	$o = '';

	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (!$post_id) {
		notice(L10n::t('Item not found') . EOL);
		return;
	}

	$fields = ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
		'type', 'body', 'title', 'file', 'wall', 'post-type', 'guid'];

	$item = Item::selectFirstForUser(local_user(), $fields, ['id' => $post_id, 'uid' => local_user()]);

	if (!DBA::isResult($item)) {
		notice(L10n::t('Item not found') . EOL);
		return;
	}

	$geotag = '';

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate("section_title.tpl"), [
		'$title' => L10n::t('Edit post')
	]);

	$tpl                         = Renderer::getMarkupTemplate('jot-header.tpl');
	AppR::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
		'$ispublic' => '&nbsp;', // L10n::t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->user['nickname']
	]);

	if (strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid'])) {
		$lockstate = 'lock';
	} else {
		$lockstate = 'unlock';
	}

	$jotplugins = '';
	$jotnets = '';

	Hook::callAll('jot_tool', $jotplugins);

	$tpl = Renderer::getMarkupTemplate("jot.tpl");
	$o .= Renderer::replaceMacros($tpl, [
		'$is_edit' => true,
		'$return_path' => '/display/' . $item['guid'],
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
		'$wall' => $item['wall'],
		'$posttype' => $item['post-type'],
		'$content' => undo_post_tagging($item['body']),
		'$post_id' => $post_id,
		'$defloc' => $a->user['default-location'],
		'$visitor' => 'none',
		'$pvisit' => 'none',
		'$emailcc' => L10n::t('CC: email addresses'),
		'$public' => L10n::t('Public post'),
		'$jotnets' => $jotnets,
		'$title' => $item['title'],
		'$placeholdertitle' => L10n::t('Set title'),
		'$category' => FileTag::fileToList($item['file'], 'category'),
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
		'$rand_num' => Crypto::randomDigits(12),

		//jot nav tab (used in some themes)
		'$message' => L10n::t('Message'),
		'$browser' => L10n::t('Browser'),
		'$shortpermset' => L10n::t('permissions'),
	]);

	return $o;
}

function undo_post_tagging($s) {
	$matches = null;
	$cnt = preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $s, $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (in_array($mtch[1], ['!', '@'])) {
				$contact = Contact::getDetailsByURL($mtch[2]);
				$mtch[3] = empty($contact['addr']) ? $mtch[2] : $contact['addr'];
			}
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}
