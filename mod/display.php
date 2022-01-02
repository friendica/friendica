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
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\ActivityPub\Objects;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\DFRN;

function display_init(App $a)
{
	if (ActivityPub::isRequest()) {
		(new Objects(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), $_SERVER, ['guid' => DI::args()->getArgv()[1] ?? null]))->run();
	}

	if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
		return;
	}

	$nick = ((DI::args()->getArgc() > 1) ? DI::args()->getArgv()[1] : '');

	$item = null;
	$item_user = local_user();

	$fields = ['uri-id', 'parent-uri-id', 'author-id', 'author-link', 'body', 'uid', 'guid', 'gravity'];

	// If there is only one parameter, then check if this parameter could be a guid
	if (DI::args()->getArgc() == 2) {
		$nick = '';

		// Does the local user have this item?
		if (local_user()) {
			$item = Post::selectFirstForUser(local_user(), $fields, ['guid' => DI::args()->getArgv()[1], 'uid' => local_user()]);
			if (DBA::isResult($item)) {
				$nick = $a->getLoggedInUserNickname();
			}
		}

		// Is this item private but could be visible to the remove visitor?
		if (!DBA::isResult($item) && remote_user()) {
			$item = Post::selectFirst($fields, ['guid' => DI::args()->getArgv()[1], 'private' => Item::PRIVATE, 'origin' => true]);
			if (DBA::isResult($item)) {
				if (!Contact::isFollower(remote_user(), $item['uid'])) {
					$item = null;
				} else {
					$item_user = $item['uid'];
				}
			}
		}

		// Is it an item with uid=0?
		if (!DBA::isResult($item)) {
			$item = Post::selectFirstForUser(local_user(), $fields, ['guid' => DI::args()->getArgv()[1], 'private' => [Item::PUBLIC, Item::UNLISTED], 'uid' => 0]);
		}
	} elseif (DI::args()->getArgc() >= 3 && $nick == 'feed-item') {
		$uri_id = DI::args()->getArgv()[2];
		if (substr($uri_id, -5) == '.atom') {
			$uri_id = substr($uri_id, 0, -5);
		}
		$item = Post::selectFirstForUser(local_user(), $fields, ['uri-id' => $uri_id, 'private' => [Item::PUBLIC, Item::UNLISTED], 'uid' => 0]);
	}

	if (!DBA::isResult($item)) {
		return;
	}

	if (DI::args()->getArgc() >= 3 && $nick == 'feed-item') {
		displayShowFeed($item['uri-id'], $item['uid'], DI::args()->getArgc() > 3 && DI::args()->getArgv()[3] == 'conversation.atom');
	}

	if (!empty($_SERVER['HTTP_ACCEPT']) && strstr($_SERVER['HTTP_ACCEPT'], 'application/atom+xml')) {
		Logger::info('Directly serving XML for uri-id '.$item['uri-id']);
		displayShowFeed($item['uri-id'], $item['uid'], false);
	}

	if ($item['gravity'] != GRAVITY_PARENT) {
		$parent = Post::selectFirstForUser($item_user, $fields, ['uid' => $item['uid'], 'uri-id' => $item['parent-uri-id']]);
		$item = $parent ?: $item;
	}

	$profiledata = display_fetchauthor($item);

	DI::page()['aside'] = Widget\VCard::getHTML($profiledata);
}

function display_fetchauthor($item)
{
	$profiledata = Contact::getByURLForUser($item['author-link'], local_user());

	// Check for a repeated message
	$shared = Item::getShareArray($item);
	if (!empty($shared) && empty($shared['comment'])) {
		$profiledata = [
			'uid' => 0,
			'id' => -1,
			'nickname' => '',
			'name' => '',
			'picdate' => '',
			'photo' => '',
			'url' => '',
			'network' => '',
		];

		if (!empty($shared['author'])) {
			$profiledata['name'] = $shared['author'];
		}

		if (!empty($shared['profile'])) {
			$profiledata['url'] = $shared['profile'];
		}

		if (!empty($shared['avatar'])) {
			$profiledata['photo'] = $shared['avatar'];
		}

		$profiledata['nickname'] = $profiledata['name'];
		$profiledata['network'] = Protocol::PHANTOM;

		$profiledata['address'] = '';
		$profiledata['about'] = '';

		$profiledata = Contact::getByURLForUser($profiledata['url'], local_user()) ?: $profiledata;
	}

	if (!empty($profiledata['photo'])) {
		$profiledata['photo'] = DI::baseUrl()->remove($profiledata['photo']);
	}

	return $profiledata;
}

function display_content(App $a, $update = false, $update_uid = 0)
{
	if (DI::config()->get('system','block_public') && !Session::isAuthenticated()) {
		throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
	}

	$o = '';

	$item = null;

	$force = (bool)($_REQUEST['force'] ?? false);

	if ($update) {
		$uri_id = $_REQUEST['uri_id'];
		$item = Post::selectFirst(['uid', 'parent-uri-id'], ['uri-id' => $uri_id, 'uid' => [0, $update_uid]], ['order' => ['uid' => true]]);
		if (!empty($item)) {
			if ($item['uid'] != 0) {
				$a->setProfileOwner($item['uid']);
			} else {
				$a->setProfileOwner($update_uid);
			}
			$parent_uri_id = $item['parent-uri-id'];
		}
	} else {
		$uri_id = ((DI::args()->getArgc() > 2) ? DI::args()->getArgv()[2] : 0);
		$parent_uri_id = $uri_id;

		if (DI::args()->getArgc() == 2) {
			$fields = ['uri-id', 'parent-uri-id', 'uid'];

			if (local_user()) {
				$condition = ['guid' => DI::args()->getArgv()[1], 'uid' => [0, local_user()]];
				$item = Post::selectFirstForUser(local_user(), $fields, $condition, ['order' => ['uid' => true]]);
				if (DBA::isResult($item)) {
					$uri_id = $item['uri-id'];
					$parent_uri_id = $item['parent-uri-id'];
				}
			}

			if (($parent_uri_id == 0) && remote_user()) {
				$item = Post::selectFirst($fields, ['guid' => DI::args()->getArgv()[1], 'private' => Item::PRIVATE, 'origin' => true]);
				if (DBA::isResult($item) && Contact::isFollower(remote_user(), $item['uid'])) {
					$uri_id = $item['uri-id'];
					$parent_uri_id = $item['parent-uri-id'];
				}
			}

			if ($parent_uri_id == 0) {
				$condition = ['private' => [Item::PUBLIC, Item::UNLISTED], 'guid' => DI::args()->getArgv()[1], 'uid' => 0];
				$item = Post::selectFirstForUser(local_user(), $fields, $condition);
				if (DBA::isResult($item)) {
					$uri_id = $item['uri-id'];
					$parent_uri_id = $item['parent-uri-id'];
				}
			}
		}
	}

	if (empty($item)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('The requested item doesn\'t exist or has been deleted.'));
	}

	if (!DI::pConfig()->get(local_user(), 'system', 'detailed_notif')) {
		DI::notification()->setAllSeenForUser(local_user(), ['parent-uri-id' => $item['parent-uri-id']]);
		DI::notify()->setAllSeenForUser(local_user(), ['parent-uri-id' => $item['parent-uri-id']]);
	}

	// We are displaying an "alternate" link if that post was public. See issue 2864
	$is_public = Post::exists(['uri-id' => $uri_id, 'private' => [Item::PUBLIC, Item::UNLISTED]]);
	if ($is_public) {
		// For the atom feed the nickname doesn't matter at all, we only need the item id.
		$alternate = DI::baseUrl().'/display/feed-item/'.$uri_id.'.atom';
		$conversation = DI::baseUrl().'/display/feed-item/' . $parent_uri_id . '/conversation.atom';
	} else {
		$alternate = '';
		$conversation = '';
	}

	DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('display-head.tpl'),
				['$alternate' => $alternate,
					'$conversation' => $conversation]);

	$is_remote_contact = false;
	$item_uid = local_user();
	$page_uid = 0;

	$parent = null;
	if (!local_user() && !empty($parent_uri_id)) {
		$parent = Post::selectFirst(['uid'], ['uri-id' => $parent_uri_id, 'wall' => true]);
	}

	if (DBA::isResult($parent)) {
		$page_uid = $page_uid ?? 0 ?: $parent['uid'];
		$is_remote_contact = Session::getRemoteContactID($page_uid);
		if ($is_remote_contact) {
			$item_uid = $parent['uid'];
		}
	} else {
		$page_uid = $item['uid'];
	}

	if (!empty($page_uid) && ($page_uid != local_user())) {
		$page_user = User::getById($page_uid);
	}

	$is_owner = local_user() && (in_array($page_uid, [local_user(), 0]));

	if (!empty($page_user['hidewall']) && !$is_owner && !$is_remote_contact) {
		throw new HTTPException\ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
	}

	// We need the editor here to be able to reshare an item.
	if ($is_owner && !$update) {
		$o .= DI::conversation()->statusEditor([], 0, true);
	}
	$sql_extra = Item::getPermissionsSQLByUserId($page_uid);

	if (local_user() && (local_user() == $page_uid)) {
		$condition = ['parent-uri-id' => $parent_uri_id, 'uid' => local_user(), 'unseen' => true];
		$unseen = Post::exists($condition);
	} else {
		$unseen = false;
	}

	if ($update && !$unseen && !$force) {
		return '';
	}

	$condition = ["`uri-id` = ? AND `uid` IN (0, ?) " . $sql_extra, $uri_id, $item_uid];
	$fields = ['parent-uri-id', 'body', 'title', 'author-name', 'author-avatar', 'plink', 'author-id', 'owner-id', 'contact-id'];
	$item = Post::selectFirstForUser($page_uid, $fields, $condition);

	if (!DBA::isResult($item)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('The requested item doesn\'t exist or has been deleted.'));
	}

	$item['uri-id'] = $item['parent-uri-id'];

	if ($unseen) {
		$condition = ['parent-uri-id' => $parent_uri_id, 'uid' => local_user(), 'unseen' => true];
		Item::update(['unseen' => false], $condition);
	}

	if (!$update && local_user()) {
		$o .= "<script> var netargs = '?uri_id=" . $item['uri-id'] . "'; </script>";
	}

	$o .= DI::conversation()->create([$item], 'display', $update_uid, false, 'commented', $item_uid);

	// Preparing the meta header
	$description = trim(BBCode::toPlaintext($item['body']));
	$title = trim(BBCode::toPlaintext($item['title']));
	$author_name = $item['author-name'];

	$image = DI::baseUrl()->remove($item['author-avatar']);

	if ($title == '') {
		$title = $author_name;
	}

	// Limit the description to 160 characters
	if (strlen($description) > 160) {
		$description = substr($description, 0, 157) . '...';
	}

	$description = htmlspecialchars($description, ENT_COMPAT, 'UTF-8', true); // allow double encoding here
	$title = htmlspecialchars($title, ENT_COMPAT, 'UTF-8', true); // allow double encoding here
	$author_name = htmlspecialchars($author_name, ENT_COMPAT, 'UTF-8', true); // allow double encoding here

	$page = DI::page();

	if (DBA::exists('contact', ['unsearchable' => true, 'id' => [$item['contact-id'], $item['author-id'], $item['owner-id']]])) {
		$page['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
	}

	DI::page()['htmlhead'] .= '<meta name="author" content="'.$author_name.'" />'."\n";
	$page['htmlhead'] .= '<meta name="title" content="'.$title.'" />'."\n";
	$page['htmlhead'] .= '<meta name="fulltitle" content="'.$title.'" />'."\n";
	$page['htmlhead'] .= '<meta name="description" content="'.$description.'" />'."\n";

	// Schema.org microdata
	$page['htmlhead'] .= '<meta itemprop="name" content="'.$title.'" />'."\n";
	$page['htmlhead'] .= '<meta itemprop="description" content="'.$description.'" />'."\n";
	$page['htmlhead'] .= '<meta itemprop="image" content="'.$image.'" />'."\n";
	$page['htmlhead'] .= '<meta itemprop="author" content="'.$author_name.'" />'."\n";

	// Twitter cards
	$page['htmlhead'] .= '<meta name="twitter:card" content="summary" />'."\n";
	$page['htmlhead'] .= '<meta name="twitter:title" content="'.$title.'" />'."\n";
	$page['htmlhead'] .= '<meta name="twitter:description" content="'.$description.'" />'."\n";
	$page['htmlhead'] .= '<meta name="twitter:image" content="'.DI::baseUrl().'/'.$image.'" />'."\n";
	$page['htmlhead'] .= '<meta name="twitter:url" content="'.$item["plink"].'" />'."\n";

	// Dublin Core
	$page['htmlhead'] .= '<meta name="DC.title" content="'.$title.'" />'."\n";
	$page['htmlhead'] .= '<meta name="DC.description" content="'.$description.'" />'."\n";

	// Open Graph
	$page['htmlhead'] .= '<meta property="og:type" content="website" />'."\n";
	$page['htmlhead'] .= '<meta property="og:title" content="'.$title.'" />'."\n";
	$page['htmlhead'] .= '<meta property="og:image" content="'.DI::baseUrl().'/'.$image.'" />'."\n";
	$page['htmlhead'] .= '<meta property="og:url" content="'.$item["plink"].'" />'."\n";
	$page['htmlhead'] .= '<meta property="og:description" content="'.$description.'" />'."\n";
	$page['htmlhead'] .= '<meta name="og:article:author" content="'.$author_name.'" />'."\n";
	// article:tag

	return $o;
}

function displayShowFeed(int $uri_id, int $uid, bool $conversation)
{
	$xml = DFRN::itemFeed($uri_id, $uid, $conversation);
	if ($xml == '') {
		throw new HTTPException\InternalServerErrorException(DI::l10n()->t('The feed for this item is unavailable.'));
	}
	header("Content-type: application/atom+xml");
	echo $xml;
	exit();
}
