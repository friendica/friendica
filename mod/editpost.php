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
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Util\Crypto;

function editpost_content(App $a)
{
	$o = '';

	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$post_id = ((DI::args()->getArgc() > 1) ? intval(DI::args()->getArgv()[1]) : 0);

	if (!$post_id) {
		notice(DI::l10n()->t('Item not found'));
		return;
	}

	$fields = ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
		'body', 'title', 'uri-id', 'wall', 'post-type', 'guid'];

	$item = Post::selectFirstForUser(local_user(), $fields, ['id' => $post_id, 'uid' => local_user()]);

	if (!DBA::isResult($item)) {
		notice(DI::l10n()->t('Item not found'));
		return;
	}

	$user = User::getById(local_user());

	$geotag = '';

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate("section_title.tpl"), [
		'$title' => DI::l10n()->t('Edit post')
	]);

	$tpl = Renderer::getMarkupTemplate('jot-header.tpl');
	DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
		'$ispublic' => '&nbsp;', // DI::l10n()->t('Visible to <strong>everybody</strong>'),
		'$geotag' => $geotag,
		'$nickname' => $a->getLoggedInUserNickname(),
		'$is_mobile' => DI::mode()->isMobile(),
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
		'$share' => DI::l10n()->t('Save'),
		'$loading' => DI::l10n()->t('Loading...'),
		'$upload' => DI::l10n()->t('Upload photo'),
		'$shortupload' => DI::l10n()->t('upload photo'),
		'$attach' => DI::l10n()->t('Attach file'),
		'$shortattach' => DI::l10n()->t('attach file'),
		'$weblink' => DI::l10n()->t('Insert web link'),
		'$shortweblink' => DI::l10n()->t('web link'),
		'$video' => DI::l10n()->t('Insert video link'),
		'$shortvideo' => DI::l10n()->t('video link'),
		'$audio' => DI::l10n()->t('Insert audio link'),
		'$shortaudio' => DI::l10n()->t('audio link'),
		'$setloc' => DI::l10n()->t('Set your location'),
		'$shortsetloc' => DI::l10n()->t('set location'),
		'$noloc' => DI::l10n()->t('Clear browser location'),
		'$shortnoloc' => DI::l10n()->t('clear location'),
		'$wait' => DI::l10n()->t('Please wait'),
		'$permset' => DI::l10n()->t('Permission settings'),
		'$wall' => $item['wall'],
		'$posttype' => $item['post-type'],
		'$content' => undo_post_tagging($item['body']),
		'$post_id' => $post_id,
		'$defloc' => $user['default-location'],
		'$visitor' => 'none',
		'$pvisit' => 'none',
		'$emailcc' => DI::l10n()->t('CC: email addresses'),
		'$public' => DI::l10n()->t('Public post'),
		'$jotnets' => $jotnets,
		'$title' => $item['title'],
		'$placeholdertitle' => DI::l10n()->t('Set title'),
		'$category' => Post\Category::getCSVByURIId($item['uri-id'], local_user(), Post\Category::CATEGORY),
		'$placeholdercategory' => (Feature::isEnabled(local_user(),'categories') ? DI::l10n()->t("Categories \x28comma-separated list\x29") : ''),
		'$emtitle' => DI::l10n()->t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $lockstate,
		'$acl' => '', // populate_acl((($group) ? $group_acl : $a->user)),
		'$bang' => ($lockstate === 'lock' ? '!' : ''),
		'$profile_uid' => $_SESSION['uid'],
		'$preview' => DI::l10n()->t('Preview'),
		'$jotplugins' => $jotplugins,
		'$cancel' => DI::l10n()->t('Cancel'),
		'$rand_num' => Crypto::randomDigits(12),

		// Formatting button labels
		'$edbold'   => DI::l10n()->t('Bold'),
		'$editalic' => DI::l10n()->t('Italic'),
		'$eduline'  => DI::l10n()->t('Underline'),
		'$edquote'  => DI::l10n()->t('Quote'),
		'$edcode'   => DI::l10n()->t('Code'),
		'$edurl'    => DI::l10n()->t('Link'),
		'$edattach' => DI::l10n()->t('Link or Media'),

		//jot nav tab (used in some themes)
		'$message' => DI::l10n()->t('Message'),
		'$browser' => DI::l10n()->t('Browser'),
		'$shortpermset' => DI::l10n()->t('Permissions'),

		'$compose_link_title' => DI::l10n()->t('Open Compose page'),
	]);

	return $o;
}

function undo_post_tagging($s) {
	$matches = null;
	$cnt = preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $s, $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (in_array($mtch[1], ['!', '@'])) {
				$contact = Contact::getByURL($mtch[2], false, ['addr']);
				$mtch[3] = empty($contact['addr']) ? $mtch[2] : $contact['addr'];
			}
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}
