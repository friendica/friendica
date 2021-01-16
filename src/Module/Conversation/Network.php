<?php

namespace Friendica\Module\Conversation;

use Friendica\BaseModule;
use Friendica\Content\BoundariesPager;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Content\Text\HTML;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Module\Security\Login;
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

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form();
		}

		self::parseRequest($parameters, $_GET);

		$module = 'network';

		DI::page()['aside'] .= Widget::accounttypes($module, self::$accountTypeString);
		DI::page()['aside'] .= Group::sidebarWidget($module, $module . '/group', 'standard', self::$groupId);
		DI::page()['aside'] .= ForumManager::widget($module . '/forum', local_user(), self::$forumContactId);
		DI::page()['aside'] .= Widget::postedByYear($module . '/archive', local_user(), false);
		DI::page()['aside'] .= Widget::networks($module, !self::$forumContactId ? self::$network : '');
		DI::page()['aside'] .= Widget\SavedSearches::getHTML(DI::args()->getQueryString());
		DI::page()['aside'] .= Widget::fileAs('filed', null);

		$arr = ['query' => DI::args()->getQueryString()];
		Hook::callAll('network_content_init', $arr);

		$o = '';

		// Fetch a page full of parent items for this page
		$params = ['limit' => self::$itemsPerPage];
		$table = 'network-thread-view';

		$items = self::getItems($table, $params);

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		}

		if (!(isset($_GET['mode']) AND ($_GET['mode'] == 'raw'))) {
			$o .= self::getTabsHTML(self::$selectedTab);

			Nav::setSelected(DI::args()->get(0));

			$content = '';

			if (self::$forumContactId) {
				// If self::$forumContactId belongs to a communitity forum or a privat goup,.add a mention to the status editor
				$condition = ["`id` = ? AND (`forum` OR `prv`)", self::$forumContactId];
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
					'uid'     => local_user(),
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
				'is_owner' => true,
				'allow_location' => $a->user['allow_location'],
				'default_location' => $a->user['default-location'],
				'nickname' => $a->user['nickname'],
				'lockstate' => (self::$groupId || self::$forumContactId || self::$network || (is_array($a->user) &&
					(strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) ||
						strlen($a->user['deny_cid']) || strlen($a->user['deny_gid']))) ? 'lock' : 'unlock'),
				'default_perms' => ACL::getDefaultUserPermissions($a->user),
				'acl' => ACL::getFullSelectorHTML(DI::page(), $a->user, true, $default_permissions),
				'bang' => ((self::$groupId || self::$forumContactId || self::$network) ? '!' : ''),
				'visitor' => 'block',
				'profile_uid' => local_user(),
				'content' => $content,
			];

			$o .= status_editor($a, $x);
		}

		if (self::$groupId) {
			$group = DBA::selectFirst('group', ['name'], ['id' => self::$groupId, 'uid' => local_user()]);
			if (!DBA::isResult($group)) {
				notice(DI::l10n()->t('No such group'));
			}

			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
				'$title' => DI::l10n()->t('Group: %s', $group['name'])
			]) . $o;
		} elseif (self::$forumContactId) {
			$contact = Contact::getById(self::$forumContactId);
			if (DBA::isResult($contact)) {
				$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('viewcontact_template.tpl'), [
					'contacts' => [ModuleContact::getContactTemplateVars($contact)],
					'id' => DI::args()->get(0),
				]) . $o;
			} else {
				notice(DI::l10n()->t('Invalid contact.'));
			}
		} elseif (!DI::config()->get('theme', 'hide_eventlist')) {
			$o .= Profile::getBirthdays();
			$o .= Profile::getEventsReminderHTML();
		}

		if (self::$order === 'received') {
			$ordering = '`received`';
		} else {
			$ordering = '`commented`';
		}

		$o .= conversation(DI::app(), $items, 'network', false, false, $ordering, local_user());

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
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

	protected static function parseRequest(array $parameters, array $get)
	{
		self::$groupId = $parameters['group_id'] ?? 0;

		self::$forumContactId = $parameters['contact_id'] ?? 0;

		self::$selectedTab = Session::get('network-tab', DI::pConfig()->get(local_user(), 'network.view', 'selected_tab', ''));

		self::$order = 'commented';

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
		} elseif (in_array(self::$selectedTab, ['received', 'star', 'mention'])) {
			self::$order = 'received';
		}

		self::$selectedTab = self::$selectedTab ?? self::$order;

		Session::set('network-tab', self::$selectedTab);
		DI::pConfig()->set(local_user(), 'network.view', 'selected_tab', self::$selectedTab);

		self::$accountTypeString = $get['accounttype'] ?? $parameters['accounttype'] ?? '';
		self::$accountType = User::getAccountTypeByString(self::$accountTypeString);

		self::$network = $get['nets'] ?? '';

		self::$dateFrom = $parameters['from'] ?? '';
		self::$dateTo = $parameters['to'] ?? '';

		if (DI::mode()->isMobile()) {
			self::$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			self::$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
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
		$conditionFields['uid'] = local_user();
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
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` <= ? ", DateTimeFormat::convert(self::$dateFrom, 'UTC', date_default_timezone_get())]);
		}
		if (self::$dateTo) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` >= ? ", DateTimeFormat::convert(self::$dateTo, 'UTC', date_default_timezone_get())]);
		}

		if (self::$groupId) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)", self::$groupId]);
		} elseif (self::$forumContactId) {
			$conditionFields['contact-id'] = self::$forumContactId;
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
			$parents = array_column($items, 'parent');
		} else {
			$parents = [];
		}

		// We aren't going to try and figure out at the item, group, and page
		// level which items you've seen and which you haven't. If you're looking
		// at the top level network page just mark everything seen.
		if (!self::$groupId && !self::$forumContactId && !self::$star && !self::$mention) {
			$condition = ['unseen' => true, 'uid' => local_user()];
			self::setItemsSeenByCondition($condition);
		} elseif (!empty($parents)) {
			$condition = ['unseen' => true, 'uid' => local_user(), 'parent' => $parents];
			self::setItemsSeenByCondition($condition);
		}

		return $items;
	}
}
