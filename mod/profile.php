<?php
/**
 * @file mod/profile.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Login;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\DFRN;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Security;
use Friendica\Util\Strings;
use Friendica\Util\XML;

function profile_init(App $a)
{
	if (empty($a->page['aside'])) {
		$a->page['aside'] = '';
	}

	if ($a->argc < 2) {
		System::httpExit(400);
	}

	$which = filter_var($a->argv[1], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK);

	$profile = 0;
	if (local_user() && $a->argc > 2 && $a->argv[2] === 'view') {
		$which = $a->user['nickname'];
		$profile = filter_var($a->argv[1], FILTER_SANITIZE_NUMBER_INT);
	} else {
		DFRN::autoRedir($a, $which);
	}

	if (ActivityPub::isRequest()) {
		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $which]);
		if (DBA::isResult($user)) {
			$data = ActivityPub\Transmitter::getProfile($user['uid']);
			System::jsonExit($data, 'application/activity+json');
		} elseif (DBA::exists('userd', ['username' => $which])) {
			// Known deleted user
			System::httpExit(410);
		} else {
			// Unknown user
			System::httpExit(404);
		}
	}

	Profile::load($a, $which, $profile);

	$blocked   = !local_user() && !remote_user() && Config::get('system', 'block_public');
	$userblock = !local_user() && !remote_user() && $a->profile['hidewall'];

	if (!empty($a->profile['page-flags']) && $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
		$a->page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
	}

	if (!empty($a->profile['openidserver'])) {
		$a->page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
	}

	if (!empty($a->profile['openid'])) {
		$delegate = strstr($a->profile['openid'], '://') ? $a->profile['openid'] : 'https://' . $a->profile['openid'];
		$a->page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
	}

	// site block
	if (!$blocked && !$userblock) {
		$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], defaults($a->profile, 'pub_keywords', ''));
		if (strlen($keywords)) {
			$a->page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n";
		}
	}

	$a->page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . ($a->profile['net-publish'] ? 'true' : 'false') . '" />' . "\r\n";
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/dfrn_poll/' . $which . '" title="DFRN: ' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\r\n";
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $which . '/" title="' . L10n::t('%s\'s posts', $a->profile['username']) . '"/>' . "\r\n";
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $which . '/comments" title="' . L10n::t('%s\'s comments', $a->profile['username']) . '"/>' . "\r\n";
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $which . '/activity" title="' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\r\n";
	$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $a->getHostName() . ($a->getURLPath() ? '/' . $a->getURLPath() : ''));
	$a->page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . System::baseUrl() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
	header('Link: <' . System::baseUrl() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

	$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
	foreach ($dfrn_pages as $dfrn) {
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"" . System::baseUrl() . "/dfrn_{$dfrn}/{$which}\" />\r\n";
	}
	$a->page['htmlhead'] .= '<link rel="dfrn-poco" href="' . System::baseUrl() . "/poco/{$which}\" />\r\n";
}

function profile_content(App $a, $update = 0)
{
	$category = $datequery = $datequery2 = '';

	if ($a->argc > 2) {
		for ($x = 2; $x < $a->argc; $x ++) {
			if (is_a_date_arg($a->argv[$x])) {
				if ($datequery) {
					$datequery2 = Strings::escapeHtml($a->argv[$x]);
				} else {
					$datequery = Strings::escapeHtml($a->argv[$x]);
				}
			} else {
				$category = $a->argv[$x];
			}
		}
	}

	if (empty($category)) {
		$category = defaults($_GET, 'category', '');
	}

	$hashtags = defaults($_GET, 'tag', '');

	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		return Login::form();
	}

	$groups = [];
	$remote_cid = null;

	$o = '';

	if ($update) {
		// Ensure we've got a profile owner if updating.
		$a->profile['profile_uid'] = $update;
	} elseif ($a->profile['profile_uid'] == local_user()) {
		Nav::setSelected('home');
	}

	$remote_contact = Contact::isFollower(remote_user(), $a->profile['profile_uid']);
	$is_owner = local_user() == $a->profile['profile_uid'];
	$last_updated_key = "profile:" . $a->profile['profile_uid'] . ":" . local_user() . ":" . remote_user();

	if ($remote_contact) {
		$cdata = Contact::getPublicAndUserContacID(remote_user(), $a->profile['profile_uid']);
		if (!empty($cdata['user'])) {
			$groups = Group::getIdsByContactId($cdata['user']);
			$remote_cid = $cdata['user'];
		}
	}

	if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact) {
		notice(L10n::t('Access to this profile has been restricted.') . EOL);
		return;
	}

	if (!$update) {
		$tab = false;
		if (!empty($_GET['tab'])) {
			$tab = Strings::escapeTags(trim($_GET['tab']));
		}

		$o .= Profile::getTabs($a, $is_owner, $a->profile['nickname']);

		if ($tab === 'profile') {
			$o .= Profile::getAdvanced($a);
			Hook::callAll('profile_advanced', $o);
			return $o;
		}

		$o .= Widget::commonFriendsVisitor($a->profile['profile_uid']);

		$commpage = $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$commvisitor = $commpage && $remote_contact;

		$a->page['aside'] .= posted_date_widget(System::baseUrl(true) . '/profile/' . $a->profile['nickname'], $a->profile['profile_uid'], true);
		$a->page['aside'] .= Widget::categories(System::baseUrl(true) . '/profile/' . $a->profile['nickname'], (!empty($category) ? XML::escape($category) : ''));
		$a->page['aside'] .= Widget::tagCloud();

		if (Security::canWriteToUserWall($a->profile['profile_uid'])) {
			$x = [
				'is_owner' => $is_owner,
				'allow_location' => ($is_owner || $commvisitor) && $a->profile['allow_location'],
				'default_location' => $is_owner ? $a->user['default-location'] : '',
				'nickname' => $a->profile['nickname'],
				'lockstate' => is_array($a->user)
					&& (strlen($a->user['allow_cid'])
						|| strlen($a->user['allow_gid'])
						|| strlen($a->user['deny_cid'])
						|| strlen($a->user['deny_gid'])
					) ? 'lock' : 'unlock',
				'acl' => $is_owner ? ACL::getFullSelectorHTML($a->user, true) : '',
				'bang' => '',
				'visitor' => $is_owner || $commvisitor ? 'block' : 'none',
				'profile_uid' => $a->profile['profile_uid'],
			];

			$o .= status_editor($a, $x);
		}
	}

	// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
	$sql_extra = Item::getPermissionsSQLByUserId($a->profile['profile_uid'], $remote_contact, $groups, $remote_cid);
	$sql_extra2 = '';

	if ($update) {
		$last_updated = (defaults($_SESSION['last_updated'], $last_updated_key, 0));

		// If the page user is the owner of the page we should query for unseen
		// items. Otherwise use a timestamp of the last succesful update request.
		if ($is_owner || !$last_updated) {
			$sql_extra4 = " AND `item`.`unseen`";
		} else {
			$gmupdate = gmdate(DateTimeFormat::MYSQL, $last_updated);
			$sql_extra4 = " AND `item`.`received` > '" . $gmupdate . "'";
		}

		$items = q("SELECT DISTINCT(`parent-uri`) AS `uri`, `item`.`created`
			FROM `item` INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			AND NOT `contact`.`blocked` AND NOT `contact`.`pending`
			WHERE `item`.`uid` = %d AND `item`.`visible` AND
			(NOT `item`.`deleted` OR `item`.`gravity` = %d)
			AND NOT `item`.`moderated` AND `item`.`wall`
			$sql_extra4
			$sql_extra
			ORDER BY `item`.`created` DESC",
			intval($a->profile['profile_uid']), intval(GRAVITY_ACTIVITY)
		);

		if (!DBA::isResult($items)) {
			return '';
		}

		$pager = new Pager($a->query_string);
	} else {
		$sql_post_table = "";

		if (!empty($category)) {
			$sql_post_table = sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				DBA::escape(Strings::protectSprintf($category)), intval(TERM_OBJ_POST), intval(TERM_CATEGORY), intval($a->profile['profile_uid']));
		}

		if (!empty($hashtags)) {
			$sql_post_table .= sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				DBA::escape(Strings::protectSprintf($hashtags)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG), intval($a->profile['profile_uid']));
		}

		if (!empty($datequery)) {
			$sql_extra2 .= Strings::protectSprintf(sprintf(" AND `thread`.`created` <= '%s' ", DBA::escape(DateTimeFormat::convert($datequery, 'UTC', date_default_timezone_get()))));
		}
		if (!empty($datequery2)) {
			$sql_extra2 .= Strings::protectSprintf(sprintf(" AND `thread`.`created` >= '%s' ", DBA::escape(DateTimeFormat::convert($datequery2, 'UTC', date_default_timezone_get()))));
		}

		// Does the profile page belong to a forum?
		// If not then we can improve the performance with an additional condition
		$condition = ['uid' => $a->profile['profile_uid'], 'page-flags' => [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]];
		if (!DBA::exists('user', $condition)) {
			$sql_extra3 = sprintf(" AND `thread`.`contact-id` = %d ", intval(intval($a->profile['contact_id'])));
		} else {
			$sql_extra3 = "";
		}

		//  check if we serve a mobile device and get the user settings
		//  accordingly
		if ($a->is_mobile) {
			$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_mobile_network', 10);
		} else {
			$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_network', 20);
		}

		//  now that we have the user settings, see if the theme forces
		//  a maximum item number which is lower then the user choice
		if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
			$itemspage_network = $a->force_max_items;
		}

		$pager = new Pager($a->query_string, $itemspage_network);

		$pager_sql = sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());

		$items = q("SELECT `item`.`uri`
			FROM `thread`
			STRAIGHT_JOIN `item` ON `item`.`id` = `thread`.`iid`
			$sql_post_table
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `thread`.`contact-id`
				AND NOT `contact`.`blocked` AND NOT `contact`.`pending`
			WHERE `thread`.`uid` = %d AND `thread`.`visible`
				AND NOT `thread`.`deleted`
				AND NOT `thread`.`moderated`
				AND `thread`.`wall`
				$sql_extra3 $sql_extra $sql_extra2
			ORDER BY `thread`.`created` DESC $pager_sql",
			intval($a->profile['profile_uid'])
		);
	}

	// Set a time stamp for this page. We will make use of it when we
	// search for new items (update routine)
	$_SESSION['last_updated'][$last_updated_key] = time();

	if ($is_owner && !$update && !Config::get('theme', 'hide_eventlist')) {
		$o .= Profile::getBirthdays();
		$o .= Profile::getEventsReminderHTML();
	}

	if ($is_owner) {
		$unseen = Item::exists(['wall' => true, 'unseen' => true, 'uid' => local_user()]);
		if ($unseen) {
			Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => local_user()]);
		}
	}

	$o .= conversation($a, $items, $pager, 'profile', $update, false, 'created', $a->profile['profile_uid']);

	if (!$update) {
		$o .= $pager->renderMinimal(count($items));
	}

	return $o;
}
