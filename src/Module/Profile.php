<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact as ContactModel;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\DFRN;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Security;
use Friendica\Util\Strings;
use Friendica\Util\XML;

require_once 'boot.php';

class Profile extends BaseModule
{
	public static $which = '';
	public static $profile = 0;

	public static function init(array $parameters = [])
	{
		$a = DI::app();

		// @TODO: Replace with parameter from router
		if ($a->argc < 2) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		self::$which = filter_var($a->argv[1], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK);

		// @TODO: Replace with parameter from router
		if (local_user() && $a->argc > 2 && $a->argv[2] === 'view') {
			self::$which = $a->user['nickname'];
			self::$profile = filter_var($a->argv[1], FILTER_SANITIZE_NUMBER_INT);
		}
	}

	public static function rawContent(array $parameters = [])
	{
		if (ActivityPub::isRequest()) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => self::$which]);
			if (DBA::isResult($user)) {
				// The function returns an empty array when the account is removed, expired or blocked
				$data = ActivityPub\Transmitter::getProfile($user['uid']);
				if (!empty($data)) {
					System::jsonExit($data, 'application/activity+json');
				}
			}

			if (DBA::exists('userd', ['username' => self::$which])) {
				// Known deleted user
				$data = ActivityPub\Transmitter::getDeletedUser(self::$which);

				System::jsonError(410, $data);
			} else {
				// Any other case (unknown, blocked, unverified, expired, no profile, no self contact)
				System::jsonError(404, []);
			}
		}
	}

	public static function content(array $parameters = [], $update = 0)
	{
		$a = DI::app();

		if (!$update) {
			ProfileModel::load($a, self::$which, self::$profile);

			$a->page['htmlhead'] .= "\n";

			$blocked   = !local_user() && !Session::getRemoteContactID($a->profile['profile_uid']) && Config::get('system', 'block_public');
			$userblock = !local_user() && !Session::getRemoteContactID($a->profile['profile_uid']) && $a->profile['hidewall'];

			if (!empty($a->profile['page-flags']) && $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
				$a->page['htmlhead'] .= '<meta name="friendica.community" content="true" />' . "\n";
			}

			if (!empty($a->profile['openidserver'])) {
				$a->page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\n";
			}

			if (!empty($a->profile['openid'])) {
				$delegate = strstr($a->profile['openid'], '://') ? $a->profile['openid'] : 'https://' . $a->profile['openid'];
				$a->page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\n";
			}

			// site block
			if (!$blocked && !$userblock) {
				$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $a->profile['pub_keywords'] ?? '');
				if (strlen($keywords)) {
					$a->page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\n";
				}
			}

			$a->page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . ($a->profile['net-publish'] ? 'true' : 'false') . '" />' . "\n";

			if (!$a->profile['net-publish'] || $a->profile['hidewall']) {
				$a->page['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
			}

			$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/dfrn_poll/' . self::$which . '" title="DFRN: ' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\n";
			$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . self::$which . '/" title="' . L10n::t('%s\'s posts', $a->profile['username']) . '"/>' . "\n";
			$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . self::$which . '/comments" title="' . L10n::t('%s\'s comments', $a->profile['username']) . '"/>' . "\n";
			$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . self::$which . '/activity" title="' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\n";
			$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . DI::baseUrl()->getHostname() . (DI::baseUrl()->getUrlPath() ? '/' . DI::baseUrl()->getUrlPath() : ''));
			$a->page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . System::baseUrl() . '/xrd/?uri=' . $uri . '" />' . "\n";
			header('Link: <' . System::baseUrl() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

			$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
			foreach ($dfrn_pages as $dfrn) {
				$a->page['htmlhead'] .= '<link rel="dfrn-' . $dfrn . '" href="' . System::baseUrl() . '/dfrn_' . $dfrn . '/' . self::$which . '" />' . "\n";
			}
			$a->page['htmlhead'] .= '<link rel="dfrn-poco" href="' . System::baseUrl() . '/poco/' . self::$which . '" />' . "\n";
		}

		$category = $datequery = $datequery2 = '';

		if ($a->argc > 2) {
			for ($x = 2; $x < $a->argc; $x ++) {
				if (DI::dtFormat()->isYearMonth($a->argv[$x])) {
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
			$category = $_GET['category'] ?? '';
		}

		$hashtags = $_GET['tag'] ?? '';

		if (Config::get('system', 'block_public') && !local_user() && !Session::getRemoteContactID($a->profile['profile_uid'])) {
			return Login::form();
		}

		$o = '';

		if ($update) {
			// Ensure we've got a profile owner if updating.
			$a->profile['profile_uid'] = $update;
		} elseif ($a->profile['profile_uid'] == local_user()) {
			Nav::setSelected('home');
		}

		$remote_contact = Session::getRemoteContactID($a->profile['profile_uid']);
		$is_owner = local_user() == $a->profile['profile_uid'];
		$last_updated_key = "profile:" . $a->profile['profile_uid'] . ":" . local_user() . ":" . $remote_contact;

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact) {
			notice(L10n::t('Access to this profile has been restricted.') . EOL);
			return '';
		}

		if (!$update) {
			$tab = Strings::escapeTags(trim($_GET['tab'] ?? ''));

			$o .= ProfileModel::getTabs($a, $tab, $is_owner, $a->profile['nickname']);

			if ($tab === 'profile') {
				$o .= ProfileModel::getAdvanced($a);
				Hook::callAll('profile_advanced', $o);
				return $o;
			}

			$o .= Widget::commonFriendsVisitor($a->profile['profile_uid']);

			$commpage = $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
			$commvisitor = $commpage && $remote_contact;

			$a->page['aside'] .= Widget::postedByYear(System::baseUrl(true) . '/profile/' . $a->profile['nickname'], $a->profile['profile_uid'] ?? 0, true);
			$a->page['aside'] .= Widget::categories(System::baseUrl(true) . '/profile/' . $a->profile['nickname'], XML::escape($category));
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
					'acl' => $is_owner ? ACL::getFullSelectorHTML($a->page, $a->user, true) : '',
					'bang' => '',
					'visitor' => $is_owner || $commvisitor ? 'block' : 'none',
					'profile_uid' => $a->profile['profile_uid'],
				];

				$o .= status_editor($a, $x);
			}
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$sql_extra = Item::getPermissionsSQLByUserId($a->profile['profile_uid']);
		$sql_extra2 = '';

		$last_updated_array = Session::get('last_updated', []);

		if ($update) {
			$last_updated = $last_updated_array[$last_updated_key] ?? 0;

			// If the page user is the owner of the page we should query for unseen
			// items. Otherwise use a timestamp of the last succesful update request.
			if ($is_owner || !$last_updated) {
				$sql_extra4 = " AND `item`.`unseen`";
			} else {
				$gmupdate = gmdate(DateTimeFormat::MYSQL, $last_updated);
				$sql_extra4 = " AND `item`.`received` > '" . $gmupdate . "'";
			}

			$items_stmt = DBA::p(
				"SELECT DISTINCT(`parent-uri`) AS `uri`, `item`.`created`
				FROM `item`
				INNER JOIN `contact`
				ON `contact`.`id` = `item`.`contact-id`
					AND NOT `contact`.`blocked`
					AND NOT `contact`.`pending`
				WHERE `item`.`uid` = ?
					AND `item`.`visible`
					AND	(NOT `item`.`deleted` OR `item`.`gravity` = ?)
					AND NOT `item`.`moderated`
					AND `item`.`wall`
					$sql_extra4
					$sql_extra
				ORDER BY `item`.`received` DESC",
				$a->profile['profile_uid'],
				GRAVITY_ACTIVITY
			);

			if (!DBA::isResult($items_stmt)) {
				return '';
			}

			$pager = new Pager(DI::args()->getQueryString());
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
				$sql_extra2 .= Strings::protectSprintf(sprintf(" AND `thread`.`received` <= '%s' ", DBA::escape(DateTimeFormat::convert($datequery, 'UTC', date_default_timezone_get()))));
			}
			if (!empty($datequery2)) {
				$sql_extra2 .= Strings::protectSprintf(sprintf(" AND `thread`.`received` >= '%s' ", DBA::escape(DateTimeFormat::convert($datequery2, 'UTC', date_default_timezone_get()))));
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
			if (DI::mode()->isMobile()) {
				$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_mobile_network', 10);
			} else {
				$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_network', 20);
			}

			//  now that we have the user settings, see if the theme forces
			//  a maximum item number which is lower then the user choice
			if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
				$itemspage_network = $a->force_max_items;
			}

			$pager = new Pager(DI::args()->getQueryString(), $itemspage_network);

			$pager_sql = sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());

			$items_stmt = DBA::p(
				"SELECT `item`.`uri`
				FROM `thread`
				STRAIGHT_JOIN `item` ON `item`.`id` = `thread`.`iid`
				$sql_post_table
				STRAIGHT_JOIN `contact`
				ON `contact`.`id` = `thread`.`contact-id`
					AND NOT `contact`.`blocked`
					AND NOT `contact`.`pending`
				WHERE `thread`.`uid` = ?
					AND `thread`.`visible`
					AND NOT `thread`.`deleted`
					AND NOT `thread`.`moderated`
					AND `thread`.`wall`
					$sql_extra3
					$sql_extra
					$sql_extra2
				ORDER BY `thread`.`received` DESC
				$pager_sql",
				$a->profile['profile_uid']
			);
		}

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		Session::set('last_updated', $last_updated_array);

		if ($is_owner && !$update && !Config::get('theme', 'hide_eventlist')) {
			$o .= ProfileModel::getBirthdays();
			$o .= ProfileModel::getEventsReminderHTML();
		}

		if ($is_owner) {
			$unseen = Item::exists(['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			}
		}

		$items = DBA::toArray($items_stmt);

		if ($pager->getStart() == 0 && !empty($a->profile['profile_uid'])) {
			$pinned_items = Item::selectPinned($a->profile['profile_uid'], ['uri', 'pinned'], ['true' . $sql_extra]);
			$pinned = Item::inArray($pinned_items);
			$items = array_merge($items, $pinned);
		}

		$o .= conversation($a, $items, $pager, 'profile', $update, false, 'pinned_received', $a->profile['profile_uid']);

		if (!$update) {
			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}
}
