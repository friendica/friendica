<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Conversation;

use Friendica\BaseModule;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Content\Text\HTML;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Module\Security\Login;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;

class Network extends BaseModule
{
	/** @var int */
	private static $groupId;
	/** @var int */
	private static $forumContactId;
	/** @var string */
	private static $selectedTab;
	/** @var mixed */
	private static $min_id;
	/** @var mixed */
	private static $max_id;
	/** @var string */
	private static $accountTypeString;
	/** @var int */
	private static $accountType;
	/** @var string */
	private static $network;
	/** @var int */
	private static $itemsPerPage;
	/** @var string */
	private static $dateFrom;
	/** @var string */
	private static $dateTo;
	/** @var int */
	private static $star;
	/** @var int */
	private static $mention;
	/** @var string */
	protected static $order;

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form();
		}

		$this->parseRequest($_GET);

		$module = 'network';

		DI::page()['aside'] .= Widget::accountTypes($module, self::$accountTypeString);
		DI::page()['aside'] .= Group::sidebarWidget($module, $module . '/group', 'standard', self::$groupId);
		DI::page()['aside'] .= ForumManager::widget($module . '/forum', DI::userSession()->getLocalUserId(), self::$forumContactId);
		DI::page()['aside'] .= Widget::postedByYear($module . '/archive', DI::userSession()->getLocalUserId(), false);
		DI::page()['aside'] .= Widget::networks($module, !self::$forumContactId ? self::$network : '');
		DI::page()['aside'] .= Widget\SavedSearches::getHTML(DI::args()->getQueryString());
		DI::page()['aside'] .= Widget::fileAs('filed', '');

		$arr = ['query' => DI::args()->getQueryString()];
		Hook::callAll('network_content_init', $arr);

		$o = '';

		// Fetch a page full of parent items for this page
		$params = ['limit' => self::$itemsPerPage];
		$table = 'network-thread-view';

		$items = self::getItems($table, $params);

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		}

		if (!(isset($_GET['mode']) AND ($_GET['mode'] == 'raw'))) {
			$o .= self::getTabsHTML(self::$selectedTab);

			Nav::setSelected(DI::args()->get(0));

			$content = '';

			if (self::$forumContactId) {
				// If self::$forumContactId belongs to a communitity forum or a privat goup,.add a mention to the status editor
				$condition = ["`id` = ? AND `contact-type` = ?", self::$forumContactId, Contact::TYPE_COMMUNITY];
				$contact = DBA::selectFirst('contact', ['addr'], $condition);
				if (!empty($contact['addr'])) {
					$content = '!' . $contact['addr'];
				}
			}

			$a = DI::app();

			$default_permissions = [];
			if (self::$groupId) {
				$default_permissions['allow_gid'] = [self::$groupId];
			}

			$allowedCids = [];
			if (self::$forumContactId) {
				$allowedCids[] = (int) self::$forumContactId;
			} elseif (self::$network) {
				$condition = [
					'uid'     => DI::userSession()->getLocalUserId(),
					'network' => self::$network,
					'self'    => false,
					'blocked' => false,
					'pending' => false,
					'archive' => false,
					'rel'     => [Contact::SHARING, Contact::FRIEND],
				];
				$contactStmt = DBA::select('contact', ['id'], $condition);
				while ($contact = DBA::fetch($contactStmt)) {
					$allowedCids[] = (int) $contact['id'];
				}
				DBA::close($contactStmt);
			}

			if (count($allowedCids)) {
				$default_permissions['allow_cid'] = $allowedCids;
			}

			$x = [
				'lockstate' => self::$groupId || self::$forumContactId || self::$network || ACL::getLockstateForUserId($a->getLoggedInUserId()) ? 'lock' : 'unlock',
				'acl' => ACL::getFullSelectorHTML(DI::page(), $a->getLoggedInUserId(), true, $default_permissions),
				'bang' => ((self::$groupId || self::$forumContactId || self::$network) ? '!' : ''),
				'content' => $content,
			];

			$o .= DI::conversation()->statusEditor($x);
		}

		if (self::$groupId) {
			$group = DBA::selectFirst('group', ['name'], ['id' => self::$groupId, 'uid' => DI::userSession()->getLocalUserId()]);
			if (!DBA::isResult($group)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('No such group'));
			}

			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
				'$title' => DI::l10n()->t('Group: %s', $group['name'])
			]) . $o;
		} elseif (self::$forumContactId) {
			$contact = Contact::getById(self::$forumContactId);
			if (DBA::isResult($contact)) {
				$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('contact/list.tpl'), [
					'contacts' => [ModuleContact::getContactTemplateVars($contact)],
					'id' => DI::args()->get(0),
				]) . $o;
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Invalid contact.'));
			}
		} elseif (!DI::config()->get('theme', 'hide_eventlist')) {
			$o .= Profile::getBirthdays();
			$o .= Profile::getEventsReminderHTML();
		}

		if (self::$order === 'received') {
			$ordering = '`received`';
		} elseif (self::$order === 'created') {
			$ordering = '`created`';
		} else {
			$ordering = '`commented`';
		}

		$o .= DI::conversation()->create($items, Conversation::MODE_NETWORK, false, false, $ordering, DI::userSession()->getLocalUserId());

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$pager = new BoundariesPager(
				DI::l10n(),
				DI::args()->getQueryString(),
				$items[0][self::$order] ?? null,
				$items[count($items) - 1][self::$order] ?? null,
				self::$itemsPerPage
			);

			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Sets items as seen
	 *
	 * @param array $condition The array with the SQL condition
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function setItemsSeenByCondition(array $condition)
	{
		if (empty($condition)) {
			return;
		}

		$unseen = Post::exists($condition);

		if ($unseen) {
			/// @todo handle huge "unseen" updates in the background to avoid timeout errors
			Item::update(['unseen' => false], $condition);
		}
	}

	/**
	 * Get the network tabs menu
	 *
	 * @param string $selectedTab
	 * @return string Html of the network tabs
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getTabsHTML(string $selectedTab)
	{
		$cmd = DI::args()->getCommand();

		// tabs
		$tabs = [
			[
				'label'	=> DI::l10n()->t('Latest Activity'),
				'url'	=> $cmd . '?' . http_build_query(['order' => 'commented']),
				'sel'	=> !$selectedTab || $selectedTab == 'commented' ? 'active' : '',
				'title'	=> DI::l10n()->t('Sort by latest activity'),
				'id'	=> 'activity-order-tab',
				'accesskey' => 'e',
			],
			[
				'label'	=> DI::l10n()->t('Latest Posts'),
				'url'	=> $cmd . '?' . http_build_query(['order' => 'received']),
				'sel'	=> $selectedTab == 'received' ? 'active' : '',
				'title'	=> DI::l10n()->t('Sort by post received date'),
				'id'	=> 'post-order-tab',
				'accesskey' => 't',
			],
			[
				'label'	=> DI::l10n()->t('Latest Creation'),
				'url'	=> $cmd . '?' . http_build_query(['order' => 'created']),
				'sel'	=> $selectedTab == 'created' ? 'active' : '',
				'title'	=> DI::l10n()->t('Sort by post creation date'),
				'id'	=> 'creation-order-tab',
				'accesskey' => 'q',
			],
			[
				'label'	=> DI::l10n()->t('Personal'),
				'url'	=> $cmd . '?' . http_build_query(['mention' => true]),
				'sel'	=> $selectedTab == 'mention' ? 'active' : '',
				'title'	=> DI::l10n()->t('Posts that mention or involve you'),
				'id'	=> 'personal-tab',
				'accesskey' => 'r',
			],
			[
				'label'	=> DI::l10n()->t('Starred'),
				'url'	=> $cmd . '?' . http_build_query(['star' => true]),
				'sel'	=> $selectedTab == 'star' ? 'active' : '',
				'title'	=> DI::l10n()->t('Favourite Posts'),
				'id'	=> 'starred-posts-tab',
				'accesskey' => 'm',
			],
		];

		$arr = ['tabs' => $tabs];
		Hook::callAll('network_tabs', $arr);

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs']]);
	}

	protected function parseRequest(array $get)
	{
		self::$groupId = $this->parameters['group_id'] ?? 0;

		self::$forumContactId = $this->parameters['contact_id'] ?? 0;

		self::$selectedTab = self::getTimelineOrderBySession(DI::userSession(), DI::pConfig());

		if (!empty($get['star'])) {
			self::$selectedTab = 'star';
			self::$star = true;
		} else {
			self::$star = self::$selectedTab == 'star';
		}

		if (!empty($get['mention'])) {
			self::$selectedTab = 'mention';
			self::$mention = true;
		} else {
			self::$mention = self::$selectedTab == 'mention';
		}

		if (!empty($get['order'])) {
			self::$selectedTab = $get['order'];
			self::$order = $get['order'];
			self::$star = false;
			self::$mention = false;
		} elseif (in_array(self::$selectedTab, ['received', 'star'])) {
			self::$order = 'received';
		} elseif (self::$selectedTab == 'created') {
			self::$order = 'created';
		} else {
			self::$order = 'commented';
		}

		self::$selectedTab = self::$selectedTab ?? self::$order;

		// Prohibit combined usage of "star" and "mention"
		if (self::$selectedTab == 'star') {
			self::$mention = false;
		} elseif (self::$selectedTab == 'mention') {
			self::$star = false;
		}

		DI::session()->set('network-tab', self::$selectedTab);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'network.view', 'selected_tab', self::$selectedTab);

		self::$accountTypeString = $get['accounttype'] ?? $this->parameters['accounttype'] ?? '';
		self::$accountType = User::getAccountTypeByString(self::$accountTypeString);

		self::$network = $get['nets'] ?? '';

		self::$dateFrom = $this->parameters['from'] ?? '';
		self::$dateTo = $this->parameters['to'] ?? '';

		if (DI::mode()->isMobile()) {
			self::$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			self::$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		self::$min_id = $get['min_id'] ?? null;
		self::$max_id = $get['max_id'] ?? null;

		switch (self::$order) {
			case 'received':
				self::$max_id = $get['last_received'] ?? self::$max_id;
				break;
			case 'created':
				self::$max_id = $get['last_created'] ?? self::$max_id;
				break;
			case 'uriid':
				self::$max_id = $get['last_uriid'] ?? self::$max_id;
				break;
			default:
				self::$order = 'commented';
				self::$max_id = $get['last_commented'] ?? self::$max_id;
		}
	}

	protected static function getItems(string $table, array $params, array $conditionFields = [])
	{
		$conditionFields['uid'] = DI::userSession()->getLocalUserId();
		$conditionStrings = [];

		if (!is_null(self::$accountType)) {
			$conditionFields['contact-type'] = self::$accountType;
		}

		if (self::$star) {
			$conditionFields['starred'] = true;
		}
		if (self::$mention) {
			$conditionFields['mention'] = true;
		}
		if (self::$network) {
			$conditionFields['network'] = self::$network;
		}

		if (self::$dateFrom) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` <= ? ", DateTimeFormat::convert(self::$dateFrom, 'UTC', DI::app()->getTimeZone())]);
		}
		if (self::$dateTo) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` >= ? ", DateTimeFormat::convert(self::$dateTo, 'UTC', DI::app()->getTimeZone())]);
		}

		if (self::$groupId) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)", self::$groupId]);
		} elseif (self::$forumContactId) {
			$conditionStrings = DBA::mergeConditions($conditionStrings,
				["((`contact-id` = ?) OR `uri-id` IN (SELECT `parent-uri-id` FROM `post-user-view` WHERE (`contact-id` = ? AND `gravity` = ? AND `vid` = ? AND `uid` = ?)))",
				self::$forumContactId, self::$forumContactId, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), DI::userSession()->getLocalUserId()]);
		}

		// Currently only the order modes "received" and "commented" are in use
		if (isset(self::$max_id)) {
			switch (self::$order) {
				case 'received':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` < ?", self::$max_id]);
					break;
				case 'commented':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` < ?", self::$max_id]);
					break;
				case 'created':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` < ?", self::$max_id]);
					break;
				case 'uriid':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` < ?", self::$max_id]);
					break;
			}
		}

		if (isset(self::$min_id)) {
			switch (self::$order) {
				case 'received':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` > ?", self::$min_id]);
					break;
				case 'commented':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` > ?", self::$min_id]);
					break;
				case 'created':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` > ?", self::$min_id]);
					break;
				case 'uriid':
					$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` > ?", self::$min_id]);
					break;
			}
		}

		if (isset(self::$min_id) && !isset(self::$max_id)) {
			// min_id quirk: querying in reverse order with min_id gets the most recent rows, regardless of how close
			// they are to min_id. We change the query ordering to get the expected data, and we need to reverse the
			// order of the results.
			$params['order'] = [self::$order => false];
		} else {
			$params['order'] = [self::$order => true];
		}

		$items = DBA::selectToArray($table, [], DBA::mergeConditions($conditionFields, $conditionStrings), $params);

		// min_id quirk, continued
		if (isset(self::$min_id) && !isset(self::$max_id)) {
			$items = array_reverse($items);
		}

		if (DBA::isResult($items)) {
			$parents = array_column($items, 'parent-uri-id');
		} else {
			$parents = [];
		}

		// We aren't going to try and figure out at the item, group, and page
		// level which items you've seen and which you haven't. If you're looking
		// at the top level network page just mark everything seen.
		if (!self::$groupId && !self::$forumContactId && !self::$star && !self::$mention) {
			$condition = ['unseen' => true, 'uid' => DI::userSession()->getLocalUserId()];
			self::setItemsSeenByCondition($condition);
		} elseif (!empty($parents)) {
			$condition = ['unseen' => true, 'uid' => DI::userSession()->getLocalUserId(), 'parent-uri-id' => $parents];
			self::setItemsSeenByCondition($condition);
		}

		return $items;
	}

	/**
	 * Returns the selected network tab of the currently logged-in user
	 *
	 * @param IHandleUserSessions         $session
	 * @param IManagePersonalConfigValues $pconfig
	 * @return string
	 */
	public static function getTimelineOrderBySession(IHandleUserSessions $session, IManagePersonalConfigValues $pconfig): string
	{
		return $session->get('network-tab')
			?? $pconfig->get($session->getLocalUserId(), 'network.view', 'selected_tab')
			?? '';
	}
}
