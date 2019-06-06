<?php
/**
 * @file mod/community.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\RenderedPager;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\User;

function community_init(App $a)
{
	if (!local_user()) {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}
}

function community_content(App $a, $update = 0)
{
	$o = '';

	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	$page_style = Config::get('system', 'community_page_style');

	if ($page_style == CP_NO_INTERNAL_COMMUNITY) {
		notice(L10n::t('Access denied.') . EOL);
		return;
	}

	$accounttype = null;

	if ($a->argc > 2) {
		switch ($a->argv[2]) {
			case 'person':
				$accounttype = User::ACCOUNT_TYPE_PERSON;
				break;
			case 'organisation':
				$accounttype = User::ACCOUNT_TYPE_ORGANISATION;
				break;
			case 'news':
				$accounttype = User::ACCOUNT_TYPE_NEWS;
				break;
			case 'community':
				$accounttype = User::ACCOUNT_TYPE_COMMUNITY;
				break;
		}
	}

	if ($a->argc > 1) {
		$content = $a->argv[1];
	} else {
		if (!empty(Config::get('system', 'singleuser'))) {
			// On single user systems only the global page does make sense
			$content = 'global';
		} else {
			// When only the global community is allowed, we use this as default
			$content = $page_style == CP_GLOBAL_COMMUNITY ? 'global' : 'local';
		}
	}

	if (!in_array($content, ['local', 'global'])) {
		notice(L10n::t('Community option not available.') . EOL);
		return;
	}

	// Check if we are allowed to display the content to visitors
	if (!local_user()) {
		$available = $page_style == CP_USERS_AND_GLOBAL;

		if (!$available) {
			$available = ($page_style == CP_USERS_ON_SERVER) && ($content == 'local');
		}

		if (!$available) {
			$available = ($page_style == CP_GLOBAL_COMMUNITY) && ($content == 'global');
		}

		if (!$available) {
			notice(L10n::t('Not available.') . EOL);
			return;
		}
	}

	if (!$update) {
		$tabs = [];

		if ((local_user() || in_array($page_style, [CP_USERS_AND_GLOBAL, CP_USERS_ON_SERVER])) && empty(Config::get('system', 'singleuser'))) {
			$tabs[] = [
				'label' => L10n::t('Local Community'),
				'url' => 'community/local',
				'sel' => $content == 'local' ? 'active' : '',
				'title' => L10n::t('Posts from local users on this server'),
				'id' => 'community-local-tab',
				'accesskey' => 'l'
			];
		}

		if (local_user() || in_array($page_style, [CP_USERS_AND_GLOBAL, CP_GLOBAL_COMMUNITY])) {
			$tabs[] = [
				'label' => L10n::t('Global Community'),
				'url' => 'community/global',
				'sel' => $content == 'global' ? 'active' : '',
				'title' => L10n::t('Posts from users of the whole federated network'),
				'id' => 'community-global-tab',
				'accesskey' => 'g'
			];
		}

		$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$o .= Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

		Nav::setSelected('community');

		// We need the editor here to be able to reshare an item.
		if (local_user()) {
			$x = [
				'is_owner' => true,
				'allow_location' => $a->user['allow_location'],
				'default_location' => $a->user['default-location'],
				'nickname' => $a->user['nickname'],
				'lockstate' => (is_array($a->user) && (strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) || strlen($a->user['deny_cid']) || strlen($a->user['deny_gid'])) ? 'lock' : 'unlock'),
				'acl' => ACL::getFullSelectorHTML($a->user, true),
				'bang' => '',
				'visitor' => 'block',
				'profile_uid' => local_user(),
			];
			$o .= status_editor($a, $x, 0, true);
		}
	}

	// check if we serve a mobile device and get the user settings accordingly
	if ($a->is_mobile) {
		$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_mobile_network', 20);
	} else {
		$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_network', 40);
	}

	// now that we have the user settings, see if the theme forces
	// a maximum item number which is lower then the user choice
	if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
		$itemspage_network = $a->force_max_items;
	}

	$pager = new RenderedPager($a->query_string, $a->page['page'], $itemspage_network);

	$r = community_getitems($pager->getStart(), $pager->getItemsPerPage(), $content, $accounttype);

	if (!DBA::isResult($r)) {
		info(L10n::t('No results.') . EOL);
		return $o;
	}

	$maxpostperauthor = (int) Config::get('system', 'max_author_posts_community_page');

	if (($maxpostperauthor != 0) && ($content == 'local')) {
		$count = 1;
		$previousauthor = "";
		$numposts = 0;
		$s = [];

		do {
			foreach ($r as $item) {
				if ($previousauthor == $item["author-link"]) {
					++$numposts;
				} else {
					$numposts = 0;
				}
				$previousauthor = $item["author-link"];

				if (($numposts < $maxpostperauthor) && (count($s) < $pager->getItemsPerPage())) {
					$s[] = $item;
				}
			}
			if (count($s) < $pager->getItemsPerPage()) {
				$r = community_getitems($pager->getStart() + ($count * $pager->getItemsPerPage()), $pager->getItemsPerPage(), $content, $accounttype);
			}
		} while ((count($s) < $pager->getItemsPerPage()) && ( ++$count < 50) && (count($r) > 0));
	} else {
		$s = $r;
	}

	$o .= conversation($a, $s, $pager, 'community', $update, false, 'commented', local_user());

	if (!$update) {
		$o .= $pager->renderMinimal(count($r));
	}

	$t = Renderer::getMarkupTemplate("community.tpl");
	return Renderer::replaceMacros($t, [
		'$content' => $o,
		'$header' => '',
		'$show_global_community_hint' => ($content == 'global') && Config::get('system', 'show_global_community_hint'),
		'$global_community_hint' => L10n::t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
	]);
}

function community_getitems($start, $itemspage, $content, $accounttype)
{
	if ($content == 'local') {
		if (!is_null($accounttype)) {
			$sql_accounttype = " AND `user`.`account-type` = ?";
			$values = [$accounttype, $start, $itemspage];
		} else {
			$sql_accounttype = "";
			$values = [$start, $itemspage];
		}

		$r = DBA::p("SELECT `item`.`uri`, `author`.`url` AS `author-link` FROM `thread`
			STRAIGHT_JOIN `user` ON `user`.`uid` = `thread`.`uid` AND NOT `user`.`hidewall`
			STRAIGHT_JOIN `item` ON `item`.`id` = `thread`.`iid`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id`=`item`.`author-id`
			WHERE `thread`.`visible` AND NOT `thread`.`deleted` AND NOT `thread`.`moderated`
			AND NOT `thread`.`private` AND `thread`.`wall` AND `thread`.`origin` $sql_accounttype
			ORDER BY `thread`.`commented` DESC LIMIT ?, ?", $values);
		return DBA::toArray($r);
	} elseif ($content == 'global') {
		if (!is_null($accounttype)) {
			$condition = ["`uid` = ? AND `owner`.`contact-type` = ?", 0, $accounttype];
		} else {
			$condition = ['uid' => 0];
		}

		$r = Item::selectThreadForUser(0, ['uri'], $condition, ['order' => ['commented' => true], 'limit' => [$start, $itemspage]]);
		return DBA::toArray($r);
	}

	// Should never happen
	return [];
}
