<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module\Profile;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Protocol;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Module\BaseProfile;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Security\Security;
use Friendica\Util\Strings;
use Friendica\Util\XML;

class Status extends BaseProfile
{
	public static function content(array $parameters = [])
	{
		$args = DI::args();

		$a = DI::app();

		ProfileModel::load($a, $parameters['nickname']);

		if (empty($a->profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		if (!$a->profile['net-publish']) {
			DI::page()['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/dfrn_poll/' . $parameters['nickname'] . '" title="DFRN: ' . DI::l10n()->t('%s\'s timeline', $a->profile['name']) . '"/>' . "\n";
		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/feed/' . $parameters['nickname'] . '/" title="' . DI::l10n()->t('%s\'s posts', $a->profile['name']) . '"/>' . "\n";
		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/feed/' . $parameters['nickname'] . '/comments" title="' . DI::l10n()->t('%s\'s comments', $a->profile['name']) . '"/>' . "\n";
		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/feed/' . $parameters['nickname'] . '/activity" title="' . DI::l10n()->t('%s\'s timeline', $a->profile['name']) . '"/>' . "\n";

		$category = $datequery = $datequery2 = '';

		$dtFormat = DI::dtFormat();

		if ($args->getArgc() > 3) {
			for ($x = 3; $x < $args->getArgc(); $x++) {
				if ($dtFormat->isYearMonthDay($args->get($x))) {
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

		if (DI::config()->get('system', 'block_public') && !local_user() && !Session::getRemoteContactID($a->profile['uid'])) {
			return Login::form();
		}

		$o = '';

		if ($a->profile['uid'] == local_user()) {
			Nav::setSelected('home');
		}

		$remote_contact = Session::getRemoteContactID($a->profile['uid']);
		$is_owner = local_user() == $a->profile['uid'];
		$last_updated_key = "profile:" . $a->profile['uid'] . ":" . local_user() . ":" . $remote_contact;

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact) {
			notice(DI::l10n()->t('Access to this profile has been restricted.'));
			return '';
		}

		$o .= self::getTabsHTML($a, 'status', $is_owner, $a->profile['nickname']);

		$o .= Widget::commonFriendsVisitor($a->profile['uid'], $a->profile['nickname']);

		$commpage = $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$commvisitor = $commpage && $remote_contact;

		DI::page()['aside'] .= Widget::postedByYear(DI::baseUrl() . '/profile/' . $a->profile['nickname'] . '/status', $a->profile['profile_uid'] ?? 0, true);
		DI::page()['aside'] .= Widget::categories(DI::baseUrl() . '/profile/' . $a->profile['nickname'] . '/status', XML::escape($category));
		DI::page()['aside'] .= Widget::tagCloud();

		if (Security::canWriteToUserWall($a->profile['uid'])) {
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
				'acl' => $is_owner ? ACL::getFullSelectorHTML(DI::page(), $a->user, true) : '',
				'bang' => '',
				'visitor' => $is_owner || $commvisitor ? 'block' : 'none',
				'profile_uid' => $a->profile['uid'],
			];

			$o .= status_editor($a, $x);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$condition = Item::getPermissionsConditionArrayByUserId($a->profile['uid']);

		$last_updated_array = Session::get('last_updated', []);

		if (!empty($category)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `category-view` WHERE `name` = ? AND `type` = ? AND `uid` = ?)",
				$category, Category::CATEGORY, $a->profile['uid']]);
		}

		if (!empty($hashtags)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `tag-search-view` WHERE `name` = ? AND `uid` = ?)",
				$hashtags, $a->profile['uid']]);
		}

		if (!empty($datequery)) {
			$condition = DBA::mergeConditions($condition, ["`received` <= ?", DateTimeFormat::convert($datequery, 'UTC', date_default_timezone_get())]);
		}
		if (!empty($datequery2)) {
			$condition = DBA::mergeConditions($condition, ["`received` >= ?", DateTimeFormat::convert($datequery2, 'UTC', date_default_timezone_get())]);
		}

		// Does the profile page belong to a forum?
		// If not then we can improve the performance with an additional condition
		$condition2 = ['uid' => $a->profile['uid'], 'page-flags' => [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]];
		if (!DBA::exists('user', $condition2)) {
			$condition = DBA::mergeConditions($condition, ['contact-id' => $a->profile['id']]);
		}

		if (DI::mode()->isMobile()) {
			$itemspage_network = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemspage_network = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$condition = DBA::mergeConditions($condition, ["((`gravity` = ? AND `wall`) OR
			(`gravity` = ? AND `vid` = ? AND `origin` AND `thr-parent-id` IN
				(SELECT `uri-id` FROM `item` AS `i`
					WHERE `gravity` = ? AND `network` IN (?, ?, ?, ?) AND `uid` IN (?, ?)
						AND `i`.`uri-id` = `item`.`thr-parent-id`)))",
			GRAVITY_PARENT, GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), GRAVITY_PARENT,
			Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::DIASPORA, Protocol::OSTATUS,
			0, $a->profile['uid']]);

		$condition = DBA::mergeConditions($condition, ['uid' => $a->profile['uid'], 'network' => Protocol::FEDERATED,
			'visible' => true, 'deleted' => false, 'moderated' => false]);

		$pager = new Pager(DI::l10n(), $args->getQueryString(), $itemspage_network);
		$params = ['limit' => [$pager->getStart(), $pager->getItemsPerPage()], 'order' => ['received' => true]];

		$items_stmt = DBA::select('item', ['uri', 'thr-parent-id', 'gravity', 'author-id', 'received'], $condition, $params);

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		Session::set('last_updated', $last_updated_array);

		if ($is_owner && !DI::config()->get('theme', 'hide_eventlist')) {
			$o .= ProfileModel::getBirthdays();
			$o .= ProfileModel::getEventsReminderHTML();
		}

		if ($is_owner) {
			$unseen = Post::exists(['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			}
		}

		$items = DBA::toArray($items_stmt);

		if ($pager->getStart() == 0 && !empty($a->profile['uid'])) {
			$condition = ['private' => [Item::PUBLIC, Item::UNLISTED]];
			$remote_user = Session::getRemoteContactID($a->profile['uid']);
			if (!empty($remote_user)) {
				$permissionSets = DI::permissionSet()->selectByContactId($remote_user, $a->profile['uid']);
				if (!empty($permissionSets)) {
					$condition = ['psid' => array_merge($permissionSets->column('id'),
							[DI::permissionSet()->getIdFromACL($a->profile['uid'], '', '', '', '')])];
				}
			} elseif ($a->profile['uid'] == local_user()) {
				$condition = [];
			}
	
			$pinned_items = Item::selectPinned($a->profile['uid'], ['uri', 'pinned'], $condition);
			$pinned = Item::inArray($pinned_items);
			$items = array_merge($items, $pinned);
		}

		$o .= conversation($a, $items, 'profile', false, false, 'pinned_received', $a->profile['uid']);

		$o .= $pager->renderMinimal(count($items));

		return $o;
	}
}
