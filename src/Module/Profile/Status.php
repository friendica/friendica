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

namespace Friendica\Module\Profile;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
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
	protected function content(array $request = []): string
	{
		$args = DI::args();

		$a = DI::app();

		$profile = ProfileModel::load($a, $this->parameters['nickname']);

		if (empty($profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		if (!$profile['net-publish']) {
			DI::page()['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/dfrn_poll/' . $this->parameters['nickname'] . '" title="DFRN: ' . DI::l10n()->t('%s\'s timeline', $profile['name']) . '"/>' . "\n";
		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/feed/' . $this->parameters['nickname'] . '/" title="' . DI::l10n()->t('%s\'s posts', $profile['name']) . '"/>' . "\n";
		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/feed/' . $this->parameters['nickname'] . '/comments" title="' . DI::l10n()->t('%s\'s comments', $profile['name']) . '"/>' . "\n";
		DI::page()['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . DI::baseUrl() . '/feed/' . $this->parameters['nickname'] . '/activity" title="' . DI::l10n()->t('%s\'s timeline', $profile['name']) . '"/>' . "\n";

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

		if (DI::config()->get('system', 'block_public') && !DI::userSession()->getLocalUserId() && !DI::userSession()->getRemoteContactID($profile['uid'])) {
			return Login::form();
		}

		$o = '';

		if ($profile['uid'] == DI::userSession()->getLocalUserId()) {
			Nav::setSelected('home');
		}

		$remote_contact = DI::userSession()->getRemoteContactID($profile['uid']);
		$is_owner = DI::userSession()->getLocalUserId() == $profile['uid'];
		$last_updated_key = "profile:" . $profile['uid'] . ":" . DI::userSession()->getLocalUserId() . ":" . $remote_contact;

		if (!empty($profile['hidewall']) && !DI::userSession()->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $profile['nickname'] . '/restricted');
		}

		$o .= self::getTabsHTML('status', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$o .= Widget::commonFriendsVisitor($profile['uid'], $profile['nickname']);

		$commpage = $profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$commvisitor = $commpage && $remote_contact;

		DI::page()['aside'] .= Widget::postedByYear(DI::baseUrl() . '/profile/' . $profile['nickname'] . '/status', $profile['profile_uid'] ?? 0, true);
		DI::page()['aside'] .= Widget::categories($profile['uid'], DI::baseUrl() . '/profile/' . $profile['nickname'] . '/status', $category);
		DI::page()['aside'] .= Widget::tagCloud($profile['uid']);

		if (Security::canWriteToUserWall($profile['uid'])) {
			$x = [
				'is_owner' => $is_owner,
				'allow_location' => ($is_owner || $commvisitor) && $profile['allow_location'],
				'default_location' => $is_owner ? $profile['default-location'] : '',
				'nickname' => $profile['nickname'],
				'acl' => $is_owner ? ACL::getFullSelectorHTML(DI::page(), $a->getLoggedInUserId(), true) : '',
				'visitor' => $is_owner || $commvisitor ? 'block' : 'none',
				'profile_uid' => $profile['uid'],
			];

			$o .= DI::conversation()->statusEditor($x);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$condition = Item::getPermissionsConditionArrayByUserId($profile['uid']);

		$last_updated_array = DI::session()->get('last_updated', []);

		if (!empty($category)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `category-view` WHERE `name` = ? AND `type` = ? AND `uid` = ?)",
				$category, Category::CATEGORY, $profile['uid']]);
		}

		if (!empty($hashtags)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `tag-search-view` WHERE `name` = ? AND `uid` = ?)",
				$hashtags, $profile['uid']]);
		}

		if (!empty($datequery)) {
			$condition = DBA::mergeConditions($condition, ["`received` <= ?", DateTimeFormat::convert($datequery, 'UTC', $a->getTimeZone())]);
		}
		if (!empty($datequery2)) {
			$condition = DBA::mergeConditions($condition, ["`received` >= ?", DateTimeFormat::convert($datequery2, 'UTC', $a->getTimeZone())]);
		}

		// Does the profile page belong to a forum?
		// If not then we can improve the performance with an additional condition
		$condition2 = ['uid' => $profile['uid'], 'account-type' => User::ACCOUNT_TYPE_COMMUNITY];
		if (!DBA::exists('user', $condition2)) {
			$condition = DBA::mergeConditions($condition, ['contact-id' => $profile['id']]);
		}

		if (DI::mode()->isMobile()) {
			$itemspage_network = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemspage_network = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$condition = DBA::mergeConditions($condition, ["((`gravity` = ? AND `wall`) OR
			(`gravity` = ? AND `vid` = ? AND `origin`
			AND EXISTS(SELECT `uri-id` FROM `post` WHERE `uri-id` = `post-user-view`.`thr-parent-id` AND `gravity` = ? AND `network` IN (?, ?))))",
			Item::GRAVITY_PARENT, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), Item::GRAVITY_PARENT, Protocol::ACTIVITYPUB, Protocol::DFRN]);

		$condition = DBA::mergeConditions($condition, ['uid' => $profile['uid'], 'network' => Protocol::FEDERATED,
			'visible' => true, 'deleted' => false]);

		$pager = new Pager(DI::l10n(), $args->getQueryString(), $itemspage_network);
		$params = ['limit' => [$pager->getStart(), $pager->getItemsPerPage()], 'order' => ['received' => true]];

		$items_stmt = Post::select(['uri-id', 'thr-parent-id', 'gravity', 'author-id', 'received'], $condition, $params);

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		DI::session()->set('last_updated', $last_updated_array);

		if ($is_owner && !DI::config()->get('theme', 'hide_eventlist')) {
			$o .= ProfileModel::getBirthdays();
			$o .= ProfileModel::getEventsReminderHTML();
		}

		if ($is_owner) {
			$unseen = Post::exists(['wall' => true, 'unseen' => true, 'uid' => DI::userSession()->getLocalUserId()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => DI::userSession()->getLocalUserId()]);
			}
		}

		$items = Post::toArray($items_stmt);

		if ($pager->getStart() == 0 && !empty($profile['uid'])) {
			$pcid = Contact::getPublicIdByUserId($profile['uid']);
			$pinned = Post\Collection::selectToArrayForContact($pcid, Post\Collection::FEATURED);
			$items = array_merge($items, $pinned);
		}

		$o .= DI::conversation()->create($items, 'profile', false, false, 'pinned_received', $profile['uid']);

		$o .= $pager->renderMinimal(count($items));

		return $o;
	}
}
