<?php
/**
 * @file mod/community.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Core\Acl;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;

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

	require_once 'include/security.php';
	require_once 'include/conversation.php';

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

		$tab_tpl = get_markup_template('common_tabs.tpl');
		$o .= replace_macros($tab_tpl, ['$tabs' => $tabs]);

		Nav::setSelected('community');

		// We need the editor here to be able to reshare an item.
		if (local_user()) {
			$x = [
				'is_owner' => true,
				'allow_location' => $a->user['allow_location'],
				'default_location' => $a->user['default-location'],
				'nickname' => $a->user['nickname'],
				'lockstate' => (is_array($a->user) && (strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) || strlen($a->user['deny_cid']) || strlen($a->user['deny_gid'])) ? 'lock' : 'unlock'),
				'acl' => Acl::getFullSelectorHTML($a->user, true),
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

	$a->set_pager_itemspage($itemspage_network);

	$r = community_getitems($a->pager['start'], $a->pager['itemspage'], $content);

	if (!DBM::is_result($r)) {
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

				if (($numposts < $maxpostperauthor) && (count($s) < $a->pager['itemspage'])) {
					$s[] = $item;
				}
			}
			if (count($s) < $a->pager['itemspage']) {
				$r = community_getitems($a->pager['start'] + ($count * $a->pager['itemspage']), $a->pager['itemspage'], $content);
			}
		} while ((count($s) < $a->pager['itemspage']) && ( ++$count < 50) && (count($r) > 0));
	} else {
		$s = $r;
	}

	$o .= conversation($a, $s, 'community', $update);

	if (!$update) {
		$o .= alt_pager($a, count($r));
	}

	$t = get_markup_template("community.tpl");
	return replace_macros($t, [
		'$content' => $o,
		'$header' => '',
		'$show_global_community_hint' => ($content == 'global') && Config::get('system', 'show_global_community_hint'),
		'$global_community_hint' => L10n::t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
	]);
}

function community_getitems($start, $itemspage, $content)
{
	if ($content == 'local') {
		$r = dba::p("SELECT `item`.`uri`, `item`.`author-link` FROM `thread`
			INNER JOIN `user` ON `user`.`uid` = `thread`.`uid` AND NOT `user`.`hidewall`
			INNER JOIN `item` ON `item`.`id` = `thread`.`iid`
			WHERE `thread`.`visible` AND NOT `thread`.`deleted` AND NOT `thread`.`moderated`
			AND NOT `thread`.`private` AND `thread`.`wall` AND `thread`.`origin`
			ORDER BY `thread`.`commented` DESC LIMIT " . intval($start) . ", " . intval($itemspage)
		);
		return dba::inArray($r);
	} elseif ($content == 'global') {
		$r = dba::p("SELECT `uri` FROM `thread`
				INNER JOIN `item` ON `item`.`id` = `thread`.`iid`
		                INNER JOIN `contact` AS `author` ON `author`.`id`=`item`.`author-id`
				WHERE `thread`.`uid` = 0 AND NOT `author`.`hidden` AND NOT `author`.`blocked`
				ORDER BY `thread`.`commented` DESC LIMIT " . intval($start) . ", " . intval($itemspage));
		return dba::inArray($r);
	}

	// Should never happen
	return [];
}
