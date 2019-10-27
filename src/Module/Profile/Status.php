<?php

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Module\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Security;
use Friendica\Util\Strings;
use Friendica\Util\XML;

require_once 'boot.php';

class Status extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$args = self::getClass(Arguments::class);

		$a = self::getApp();

		ProfileModel::load($a, $parameters['nickname']);

		if (!$a->profile['net-publish'] || $a->profile['hidewall']) {
			$a->page['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/dfrn_poll/' . $parameters['nickname'] . '" title="DFRN: ' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\n";
		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $parameters['nickname'] . '/" title="' . L10n::t('%s\'s posts', $a->profile['username']) . '"/>' . "\n";
		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $parameters['nickname'] . '/comments" title="' . L10n::t('%s\'s comments', $a->profile['username']) . '"/>' . "\n";
		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $parameters['nickname'] . '/activity" title="' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\n";

		$category = $datequery = $datequery2 = '';

		/** @var DateTimeFormat $dtFormat */
		$dtFormat = self::getClass(DateTimeFormat::class);

		if ($args->getArgc() > 2) {
			for ($x = 2; $x < $args->getArgc(); $x++) {
				if ($dtFormat->isYearMonth($args->get($x))) {
					if ($datequery) {
						$datequery2 = Strings::escapeHtml($args->get($x));
					} else {
						$datequery = Strings::escapeHtml($args->get($x));
					}
				} else {
					$category = $args->get($x);
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

		if ($a->profile['profile_uid'] == local_user()) {
			Nav::setSelected('home');
		}

		$remote_contact = Session::getRemoteContactID($a->profile['profile_uid']);
		$is_owner = local_user() == $a->profile['profile_uid'];
		$last_updated_key = "profile:" . $a->profile['profile_uid'] . ":" . local_user() . ":" . $remote_contact;

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact) {
			notice(L10n::t('Access to this profile has been restricted.') . EOL);
			return '';
		}

		$o .= ProfileModel::getTabs($a, 'status', $is_owner, $a->profile['nickname']);

		$o .= Widget::commonFriendsVisitor($a->profile['profile_uid']);

		$commpage = $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$commvisitor = $commpage && $remote_contact;

		$a->page['aside'] .= Widget::postedByYear(System::baseUrl() . '/profile/' . $a->profile['nickname'] . '/status', $a->profile['profile_uid'] ?? 0, true);
		$a->page['aside'] .= Widget::categories(System::baseUrl() . '/profile/' . $a->profile['nickname'] . '/status', XML::escape($category));
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

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$sql_extra = Item::getPermissionsSQLByUserId($a->profile['profile_uid']);
		$sql_extra2 = '';

		$last_updated_array = Session::get('last_updated', []);

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

		$pager = new Pager($args->getQueryString(), $itemspage_network);

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

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		Session::set('last_updated', $last_updated_array);

		if ($is_owner && !Config::get('theme', 'hide_eventlist')) {
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

		$o .= conversation($a, $items, $pager, 'profile', false, false, 'received', $a->profile['profile_uid']);

		$o .= $pager->renderMinimal(count($items));

		return $o;
	}
}
