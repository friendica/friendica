<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Worker\UpdateContact;

/**
 *  Manages and show Contacts and their content
 */
class Contact extends BaseModule
{
	const TAB_CONVERSATIONS = 1;
	const TAB_POSTS         = 2;
	const TAB_PROFILE       = 3;
	const TAB_CONTACTS      = 4;
	const TAB_ADVANCED      = 5;
	const TAB_MEDIA         = 6;

	private static function batchActions()
	{
		if (empty($_POST['contact_batch']) || !is_array($_POST['contact_batch'])) {
			return;
		}

		$redirectUrl = $_POST['command'] ?? '';
		if (substr($redirectUrl, 0, 7) != 'contact') {
			$redirectUrl = 'contact';
		}
		if (!empty($_POST['parameter'])) {
			$redirectUrl .= '?' . $_POST['parameter'];
		}

		self::checkFormSecurityTokenRedirectOnError($redirectUrl, 'contact_batch_actions');

		$orig_records = Model\Contact::selectToArray(['id', 'uid'], ['id' => $_POST['contact_batch'], 'uid' => [0, DI::userSession()->getLocalUserId()], 'self' => false, 'deleted' => false]);

		$count_actions = 0;
		foreach ($orig_records as $orig_record) {
			$cdata = Model\Contact::getPublicAndUserContactID($orig_record['id'], DI::userSession()->getLocalUserId());
			if (empty($cdata) || DI::userSession()->getPublicContactId() === $cdata['public']) {
				// No action available on your own contact
				continue;
			}

			if (!empty($_POST['contacts_batch_update']) && $cdata['user']) {
				self::updateContactFromPoll($cdata['user']);
				$count_actions++;
			}

			if (!empty($_POST['contacts_batch_block'])) {
				self::toggleBlockContact($cdata['public'], DI::userSession()->getLocalUserId());
				$count_actions++;
			}

			if (!empty($_POST['contacts_batch_ignore'])) {
				self::toggleIgnoreContact($cdata['public']);
				$count_actions++;
			}

			if (!empty($_POST['contacts_batch_collapse'])) {
				self::toggleCollapseContact($cdata['public']);
				$count_actions++;
			}
		}
		if ($count_actions > 0) {
			DI::sysmsg()->addInfo(DI::l10n()->tt('%d contact edited.', '%d contacts edited.', $count_actions));
		}

		DI::baseUrl()->redirect($redirectUrl);
	}

	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		// @TODO: Replace with parameter from router
		if (DI::args()->getArgv()[1] === 'batch') {
			self::batchActions();
		}
	}

	/* contact actions */

	/**
	 * @param int $contact_id Id of contact with uid != 0
	 * @throws NotFoundException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateContactFromPoll(int $contact_id)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => DI::userSession()->getLocalUserId(), 'deleted' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		try {
			UpdateContact::add(Worker::PRIORITY_HIGH, $contact_id);
		} catch (\InvalidArgumentException $e) {
			DI::logger()->notice($e->getMessage(), ['contact' => $contact]);
		}
	}

	/**
	 * Toggles the blocked status of a contact identified by id.
	 *
	 * @param int $contact_id Id of the contact with uid = 0
	 * @param int $owner_id   Id of the user we want to block the contact for
	 * @throws \Exception
	 */
	private static function toggleBlockContact(int $contact_id, int $owner_id)
	{
		$blocked = !Model\Contact\User::isBlocked($contact_id, $owner_id);
		Model\Contact\User::setBlocked($contact_id, $owner_id, $blocked);
	}

	/**
	 * Toggles the ignored status of a contact identified by id.
	 *
	 * @param int $contact_id Id of the contact with uid = 0
	 * @throws \Exception
	 */
	private static function toggleIgnoreContact(int $contact_id)
	{
		$ignored = !Model\Contact\User::isIgnored($contact_id, DI::userSession()->getLocalUserId());
		Model\Contact\User::setIgnored($contact_id, DI::userSession()->getLocalUserId(), $ignored);
	}

	/**
	 * Toggles the collapsed status of a contact identified by id.
	 *
	 * @param int $contact_id Id of the contact with uid = 0
	 * @throws \Exception
	 */
	private static function toggleCollapseContact(int $contact_id)
	{
		$collapsed = !Model\Contact\User::isCollapsed($contact_id, DI::userSession()->getLocalUserId());
		Model\Contact\User::setCollapsed($contact_id, DI::userSession()->getLocalUserId(), $collapsed);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		$search = trim($_GET['search'] ?? '');
		$nets   = trim($_GET['nets'] ?? '');
		$rel    = trim($_GET['rel'] ?? '');
		$circle = trim($_GET['circle'] ?? '');

		$accounttype   = $_GET['accounttype'] ?? '';
		$accounttypeid = User::getAccountTypeByString($accounttype);

		$page = DI::page();

		$page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$vcard_widget      = '';
		$findpeople_widget = Widget::findPeople();
		if (isset($_GET['add'])) {
			$follow_widget = Widget::follow($_GET['add']);
		} else {
			$follow_widget = Widget::follow();
		}

		$account_widget  = Widget::accountTypes($_SERVER['REQUEST_URI'], $accounttype);
		$networks_widget = Widget::networks($_SERVER['REQUEST_URI'], $nets);
		$rel_widget      = Widget::contactRels($_SERVER['REQUEST_URI'], $rel);
		$circles_widget  = Widget::circles($_SERVER['REQUEST_URI'], $circle);

		DI::page()['aside'] .= $vcard_widget . $findpeople_widget . $follow_widget . $rel_widget . $circles_widget . $networks_widget . $account_widget;

		$tpl = Renderer::getMarkupTemplate('contacts-head.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, []);

		$o = '';
		Nav::setSelected('contact');

		$_SESSION['return_path'] = DI::args()->getQueryString();

		$sql_values = [DI::userSession()->getLocalUserId()];

		// @TODO: Replace with parameter from router
		$type = DI::args()->getArgv()[1] ?? '';

		switch ($type) {
			case 'blocked':
				$sql_extra = " AND `id` IN (SELECT `cid` FROM `user-contact` WHERE `user-contact`.`uid` = ? AND `user-contact`.`blocked`)";
				// This makes the query look for contact.uid = 0
				array_unshift($sql_values, 0);
				break;
			case 'hidden':
				$sql_extra = " AND `hidden` AND NOT `blocked` AND NOT `pending`";
				break;
			case 'ignored':
				$sql_extra = " AND `id` IN (SELECT `cid` FROM `user-contact` WHERE `user-contact`.`uid` = ? AND `user-contact`.`ignored`)";
				// This makes the query look for contact.uid = 0
				array_unshift($sql_values, 0);
				break;
			case 'collapsed':
				$sql_extra = " AND `id` IN (SELECT `cid` FROM `user-contact` WHERE `user-contact`.`uid` = ? AND `user-contact`.`collapsed`)";
				// This makes the query look for contact.uid = 0
				array_unshift($sql_values, 0);
				break;
			case 'archived':
				$sql_extra = " AND `archive` AND NOT `blocked` AND NOT `pending`";
				break;
			case 'pending':
				$sql_extra = " AND `pending` AND NOT `archive` AND ((`rel` = ?)
					OR `id` IN (SELECT `contact-id` FROM `intro` WHERE `intro`.`uid` = ? AND NOT `ignore`))";
				$sql_values[] = Model\Contact::SHARING;
				$sql_values[] = DI::userSession()->getLocalUserId();
				break;
			default:
				$sql_extra = " AND NOT `archive` AND NOT `blocked` AND NOT `pending`";
				break;
		}

		if (isset($accounttypeid)) {
			$sql_extra .= " AND `contact-type` = ?";
			$sql_values[] = $accounttypeid;
		}

		$searching  = false;
		$search_hdr = null;
		if ($search) {
			$searching  = true;
			$search_hdr = $search;
			$search_txt = preg_quote(trim($search, ' @!'));
			$sql_extra .= " AND (`name` REGEXP ? OR `url` REGEXP ? OR `nick` REGEXP ? OR `addr` REGEXP ? OR `alias` REGEXP ?)";
			$sql_values[] = $search_txt;
			$sql_values[] = $search_txt;
			$sql_values[] = $search_txt;
			$sql_values[] = $search_txt;
			$sql_values[] = $search_txt;
		}

		if ($nets) {
			$sql_extra .= " AND network = ? ";
			$sql_values[] = $nets;
		}

		switch ($rel) {
			case 'followers':
				$sql_extra .= " AND `rel` IN (?, ?)";
				$sql_values[] = Model\Contact::FOLLOWER;
				$sql_values[] = Model\Contact::FRIEND;
				break;
			case 'following':
				$sql_extra .= " AND `rel` IN (?, ?)";
				$sql_values[] = Model\Contact::SHARING;
				$sql_values[] = Model\Contact::FRIEND;
				break;
			case 'mutuals':
				$sql_extra .= " AND `rel` = ?";
				$sql_values[] = Model\Contact::FRIEND;
				break;
			case 'nothing':
				$sql_extra .= " AND `rel` = ?";
				$sql_values[] = Model\Contact::NOTHING;
				break;
			default:
				$sql_extra .= " AND `rel` != ?";
				$sql_values[] = Model\Contact::NOTHING;
				break;
		}

		if ($circle) {
			$sql_extra .= " AND `id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)";
			$sql_values[] = $circle;
		}

		$networks = Widget::unavailableNetworks();
		$sql_extra .= " AND NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")";
		$sql_values = array_merge($sql_values, $networks);

		$condition = ["`uid` = ? AND NOT `self` AND NOT `deleted`" . $sql_extra];
		$condition = array_merge($condition, $sql_values);

		$total = DBA::count('contact', $condition);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

		$contacts = [];

		$stmt = DBA::select('contact', [], $condition, ['order' => ['name'], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]]);

		while ($contact = DBA::fetch($stmt)) {
			$contact['blocked']  = Model\Contact\User::isBlocked($contact['id'], DI::userSession()->getLocalUserId());
			$contact['readonly'] = Model\Contact\User::isIgnored($contact['id'], DI::userSession()->getLocalUserId());
			$contacts[]          = self::getContactTemplateVars($contact);
		}
		DBA::close($stmt);

		$tabs = [
			[
				'label'     => DI::l10n()->t('All Contacts'),
				'url'       => 'contact',
				'sel'       => !$type ? 'active' : '',
				'title'     => DI::l10n()->t('Show all contacts'),
				'id'        => 'showall-tab',
				'accesskey' => 'l',
			],
			[
				'label'     => DI::l10n()->t('Pending'),
				'url'       => 'contact/pending',
				'sel'       => $type == 'pending' ? 'active' : '',
				'title'     => DI::l10n()->t('Only show pending contacts'),
				'id'        => 'showpending-tab',
				'accesskey' => 'p',
			],
			[
				'label'     => DI::l10n()->t('Blocked'),
				'url'       => 'contact/blocked',
				'sel'       => $type == 'blocked' ? 'active' : '',
				'title'     => DI::l10n()->t('Only show blocked contacts'),
				'id'        => 'showblocked-tab',
				'accesskey' => 'b',
			],
			[
				'label'     => DI::l10n()->t('Ignored'),
				'url'       => 'contact/ignored',
				'sel'       => $type == 'ignored' ? 'active' : '',
				'title'     => DI::l10n()->t('Only show ignored contacts'),
				'id'        => 'showignored-tab',
				'accesskey' => 'i',
			],
			[
				'label'     => DI::l10n()->t('Collapsed'),
				'url'       => 'contact/collapsed',
				'sel'       => $type == 'collapsed' ? 'active' : '',
				'title'     => DI::l10n()->t('Only show collapsed contacts'),
				'id'        => 'showcollapsed-tab',
				'accesskey' => 'c',
			],
			[
				'label'     => DI::l10n()->t('Archived'),
				'url'       => 'contact/archived',
				'sel'       => $type == 'archived' ? 'active' : '',
				'title'     => DI::l10n()->t('Only show archived contacts'),
				'id'        => 'showarchived-tab',
				'accesskey' => 'y',
			],
			[
				'label'     => DI::l10n()->t('Hidden'),
				'url'       => 'contact/hidden',
				'sel'       => $type == 'hidden' ? 'active' : '',
				'title'     => DI::l10n()->t('Only show hidden contacts'),
				'id'        => 'showhidden-tab',
				'accesskey' => 'h',
			],
			[
				'label'     => DI::l10n()->t('Circles'),
				'url'       => 'circle',
				'sel'       => '',
				'title'     => DI::l10n()->t('Organize your contact circles'),
				'id'        => 'contactcircles-tab',
				'accesskey' => 'e',
			],
		];

		$tabs_tpl  = Renderer::getMarkupTemplate('common_tabs.tpl');
		$tabs_html = Renderer::replaceMacros($tabs_tpl, ['$tabs' => $tabs, '$more' => DI::l10n()->t('More')]);

		switch ($rel) {
			case 'followers':
				$header = DI::l10n()->t('Followers');
				break;
			case 'following':
				$header = DI::l10n()->t('Following');
				break;
			case 'mutuals':
				$header = DI::l10n()->t('Mutual friends');
				break;
			case 'nothing':
				$header = DI::l10n()->t('No relationship');
				break;
			default:
				$header = DI::l10n()->t('Contacts');
		}

		switch ($type) {
			case 'pending':
				$header .= ' - ' . DI::l10n()->t('Pending');
				break;
			case 'blocked':
				$header .= ' - ' . DI::l10n()->t('Blocked');
				break;
			case 'hidden':
				$header .= ' - ' . DI::l10n()->t('Hidden');
				break;
			case 'ignored':
				$header .= ' - ' . DI::l10n()->t('Ignored');
				break;
			case 'collapsed':
				$header .= ' - ' . DI::l10n()->t('Collapsed');
				break;
			case 'archived':
				$header .= ' - ' . DI::l10n()->t('Archived');
				break;
		}

		$header .= $nets ? ' - ' . ContactSelector::networkToName($nets) : '';

		$tpl = Renderer::getMarkupTemplate('contacts-template.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'              => $header,
			'$tabs'                => $tabs_html,
			'$total'               => $total,
			'$search'              => $search_hdr,
			'$desc'                => DI::l10n()->t('Search your contacts'),
			'$finding'             => $searching ? DI::l10n()->t('Results for: %s', $search) : '',
			'$submit'              => DI::l10n()->t('Find'),
			'$cmd'                 => DI::args()->getCommand(),
			'$parameter'           => http_build_query($request),
			'$contacts'            => $contacts,
			'$form_security_token' => BaseModule::getFormSecurityToken('contact_batch_actions'),
			'multiselect'          => 1,
			'$batch_actions'       => [
				'contacts_batch_update'   => DI::l10n()->t('Update'),
				'contacts_batch_block'    => DI::l10n()->t('Block') . '/' . DI::l10n()->t('Unblock'),
				'contacts_batch_ignore'   => DI::l10n()->t('Ignore') . '/' . DI::l10n()->t('Unignore'),
				'contacts_batch_collapse' => DI::l10n()->t('Collapse') . '/' . DI::l10n()->t('Uncollapse'),
			],
			'$h_batch_actions' => DI::l10n()->t('Batch Actions'),
			'$paginate'        => $pager->renderFull($total),
		]);

		return $o;
	}

	/**
	 * List of pages for the Contact TabBar
	 *
	 * Available Pages are 'Conversations', 'Profile', 'Contacts' and 'Common Friends'
	 *
	 * @param array $contact    The contact array
	 * @param int   $active_tab 1 if tab should be marked as active
	 *
	 * @return string HTML string of the contact page tabs buttons.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getTabsHTML(array $contact, int $active_tab)
	{
		$cid  = $pcid = $contact['id'];
		$data = Model\Contact::getPublicAndUserContactID($contact['id'], DI::userSession()->getLocalUserId());
		if (!empty($data['user']) && ($contact['id'] == $data['public'])) {
			$cid = $data['user'];
		} elseif (!empty($data['public'])) {
			$pcid = $data['public'];
		}

		// tabs
		$tabs = [
			[
				'label'     => DI::l10n()->t('Profile'),
				'url'       => 'contact/' . $cid,
				'sel'       => (($active_tab == self::TAB_PROFILE) ? 'active' : ''),
				'title'     => DI::l10n()->t('Profile Details'),
				'id'        => 'profile-tab',
				'accesskey' => 'o',
			],
			[
				'label'     => DI::l10n()->t('Conversations'),
				'url'       => 'contact/' . $pcid . '/conversations',
				'sel'       => (($active_tab == self::TAB_CONVERSATIONS) ? 'active' : ''),
				'title'     => DI::l10n()->t('Conversations started by this contact'),
				'id'        => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label'     => DI::l10n()->t('Posts and Comments'),
				'url'       => 'contact/' . $pcid . '/posts',
				'sel'       => (($active_tab == self::TAB_POSTS) ? 'active' : ''),
				'title'     => DI::l10n()->t('Individual Posts and Replies'),
				'id'        => 'posts-tab',
				'accesskey' => 'p',
			],
			[
				'label'     => DI::l10n()->t('Media'),
				'url'       => 'contact/' . $pcid . '/media',
				'sel'       => (($active_tab == self::TAB_MEDIA) ? 'active' : ''),
				'title'     => DI::l10n()->t('Posts containing media objects'),
				'id'        => 'media-tab',
				'accesskey' => 'd',
			],
			[
				'label'     => DI::l10n()->t('Contacts'),
				'url'       => 'contact/' . $pcid . '/contacts',
				'sel'       => (($active_tab == self::TAB_CONTACTS) ? 'active' : ''),
				'title'     => DI::l10n()->t('View all known contacts'),
				'id'        => 'contacts-tab',
				'accesskey' => 't'
			],
		];

		if (!empty($contact['network']) && in_array($contact['network'], [Protocol::FEED, Protocol::MAIL]) && ($cid != $pcid)) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Advanced'),
				'url'       => 'contact/' . $cid . '/advanced/',
				'sel'       => (($active_tab == self::TAB_ADVANCED) ? 'active' : ''),
				'title'     => DI::l10n()->t('Advanced Contact Settings'),
				'id'        => 'advanced-tab',
				'accesskey' => 'r'
			];
		}

		$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$tab_str = Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs, '$more' => DI::l10n()->t('More')]);

		return $tab_str;
	}

	/**
	 * Return the fields for the contact template
	 *
	 * @param array $contact Contact array
	 * @return array Template fields
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getContactTemplateVars(array $contact): array
	{
		$alt_text = '';

		if (!empty($contact['url']) && isset($contact['uid']) && ($contact['uid'] == 0) && DI::userSession()->getLocalUserId()) {
			$personal = Model\Contact::getByURL($contact['url'], false, ['uid', 'rel', 'self'], DI::userSession()->getLocalUserId());
			if (!empty($personal)) {
				$contact['uid']  = $personal['uid'];
				$contact['rel']  = $personal['rel'];
				$contact['self'] = $personal['self'];
			}
		}

		if (!empty($contact['uid']) && !empty($contact['rel']) && DI::userSession()->getLocalUserId() == $contact['uid']) {
			switch ($contact['rel']) {
				case Model\Contact::FRIEND:
					$alt_text = DI::l10n()->t('Mutual Friendship');
					break;

				case Model\Contact::FOLLOWER:
					$alt_text = DI::l10n()->t('is a fan of yours');
					break;

				case Model\Contact::SHARING:
					$alt_text = DI::l10n()->t('you are a fan of');
					break;

				default:
					break;
			}
		}

		$url = Model\Contact::magicLinkByContact($contact);

		if (strpos($url, 'contact/redir/') === 0) {
			$sparkle = ' class="sparkle" ';
		} else {
			$sparkle = '';
		}

		if ($contact['pending']) {
			if (in_array($contact['rel'], [Model\Contact::FRIEND, Model\Contact::SHARING])) {
				$alt_text = DI::l10n()->t('Pending outgoing contact request');
			} else {
				$alt_text = DI::l10n()->t('Pending incoming contact request');
			}
		}

		if ($contact['self']) {
			$alt_text = DI::l10n()->t('This is you');
			$url      = $contact['url'];
			$sparkle  = '';
		}

		return [
			'id'           => $contact['id'],
			'url'          => $url,
			'img_hover'    => DI::l10n()->t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
			'photo_menu'   => Model\Contact::photoMenu($contact, DI::userSession()->getLocalUserId()),
			'thumb'        => Model\Contact::getThumb($contact, true),
			'alt_text'     => $alt_text,
			'name'         => $contact['name'],
			'nick'         => $contact['nick'],
			'details'      => $contact['location'],
			'tags'         => $contact['keywords'],
			'about'        => $contact['about'],
			'account_type' => Model\Contact::getAccountType($contact['contact-type']),
			'sparkle'      => $sparkle,
			'itemurl'      => ($contact['addr'] ?? '') ?: $contact['url'],
			'network'      => ContactSelector::networkToName($contact['network'], $contact['protocol'], $contact['gsid']),
		];
	}
}
