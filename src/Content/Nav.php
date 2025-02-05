<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\App\BaseURL;
use Friendica\App\Router;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\Conversation\Community;
use Friendica\Module\Home;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Security\OpenWebAuth;
use Psr\EventDispatcher\EventDispatcherInterface;

class Nav
{
	private static $selected = [
		'global'        => null,
		'community'     => null,
		'channel'       => null,
		'network'       => null,
		'home'          => null,
		'profiles'      => null,
		'introductions' => null,
		'notifications' => null,
		'messages'      => null,
		'directory'     => null,
		'settings'      => null,
		'contacts'      => null,
		'delegation'    => null,
		'calendar'      => null,
		'register'      => null
	];

	/**
	 * An array of HTML links provided by addons providing a module via the app_menu hook
	 *
	 * @var array|null
	 */
	private $appMenu = null;

	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var IHandleUserSessions */
	private $session;
	/** @var Database */
	private $database;
	/** @var IManageConfigValues */
	private $config;
	/** @var Router */
	private $router;

	private EventDispatcherInterface $eventDispatcher;

	public function __construct(BaseURL $baseUrl, L10n $l10n, IHandleUserSessions $session, Database $database, IManageConfigValues $config, Router $router, EventDispatcherInterface $eventDispatcher)
	{
		$this->baseUrl         = $baseUrl;
		$this->l10n            = $l10n;
		$this->session         = $session;
		$this->database        = $database;
		$this->config          = $config;
		$this->router          = $router;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * Set a menu item in navbar as selected
	 *
	 * @param string $item
	 */
	public static function setSelected(string $item)
	{
		self::$selected[$item] = 'selected';
	}

	/**
	 * Build page header and site navigation bars
	 *
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MethodNotAllowedException
	 * @throws HTTPException\ServiceUnavailableException
	 */
	public function getHtml(): string
	{
		// Placeholder div for popup panel
		$nav = '<div id="panel" style="display: none;"></div>';

		$nav_info = $this->getInfo();

		$tpl = Renderer::getMarkupTemplate('nav.tpl');

		$nav .= Renderer::replaceMacros($tpl, [
			'$sitelocation'       => $nav_info['sitelocation'],
			'$nav'                => $nav_info['nav'],
			'$banner'             => $nav_info['banner'],
			'$emptynotifications' => $this->l10n->t('Nothing new here'),
			'$userinfo'           => $nav_info['userinfo'],
			'$sel'                => self::$selected,
			'$apps'               => $this->getAppMenu(),
			'$home'               => $this->l10n->t('Home'),
			'$skip'               => $this->l10n->t('Skip to main content'),
			'$clear_notifs'       => $this->l10n->t('Clear notifications'),
			'$search_hint'        => $this->l10n->t('@name, !group, #tags, content')
		]);

		$nav = $this->eventDispatcher->dispatch(
			new HtmlFilterEvent(HtmlFilterEvent::PAGE_HEADER, $nav)
		)->getHtml();

		return $nav;
	}

	/**
	 * Returns the addon app menu
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function getAppMenu(): array
	{
		if (is_null($this->appMenu)) {
			$this->appMenu = $this->populateAppMenu();
		}

		return $this->appMenu;
	}

	/**
	 * Returns menus for apps that require one
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function populateAppMenu(): array
	{
		$appMenu = [];

		//Don't populate apps_menu if apps are private
		if (
			$this->session->getLocalUserId()
			|| !$this->config->get('config', 'private_addons', false)
		) {
			$arr = ['app_menu' => $appMenu];

			$arr = $this->eventDispatcher->dispatch(
				new ArrayFilterEvent(ArrayFilterEvent::APP_MENU, $arr)
			)->getArray();

			$appMenu = $arr['app_menu'] ?? [];
		}

		return $appMenu;
	}

	/**
	 * Prepares a list of navigation links
	 *
	 * @return array Navigation links
	 *    string 'sitelocation' => The webbie (username@site.com)
	 *    array 'nav' => Array of links used in the nav menu
	 *    string 'banner' => Formatted html link with banner image
	 *    array 'userinfo' => Array of user information (name, icon)
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MethodNotAllowedException
	 */
	private function getInfo(): array
	{
		/*
		 * Our network is distributed, and as you visit friends some
		 * sites look exactly the same - it isn't always easy to know where you are.
		 * Display the current site location as a navigation aid.
		 */

		$myident = !empty($this->session->getLocalUserNickname()) ? $this->session->getLocalUserNickname() . '@' : '';

		$sitelocation = $myident . substr($this->baseUrl, strpos($this->baseUrl, '//') + 2);

		$nav = [
			'admin'         => null,
			'moderation'    => null,
			'apps'          => null,
			'community'     => null,
			'channel'       => null,
			'home'          => null,
			'calendar'      => null,
			'login'         => null,
			'logout'        => null,
			'langselector'  => null,
			'messages'      => null,
			'network'       => null,
			'notifications' => null,
			'remote'        => null,
			'search'        => null,
			'usermenu'      => [],
		];

		// Display login or logout
		$userinfo = null;

		// nav links: array of array('href', 'text', 'extra css classes', 'title')
		if ($this->session->isAuthenticated()) {
			$nav['logout'] = ['logout', $this->l10n->t('Logout'), '', $this->l10n->t('End this session')];
		} else {
			$nav['login'] = ['login', $this->l10n->t('Login'), ($this->router->getModuleClass() == Login::class ? 'selected' : ''), $this->l10n->t('Sign in')];
		}

		if ($this->session->isAuthenticated()) {
			// user menu
			$nav['usermenu'][] = ['profile/' . $this->session->getLocalUserNickname(), $this->l10n->t('Conversations'), '', $this->l10n->t('Conversations you started')];
			$nav['usermenu'][] = ['profile/' . $this->session->getLocalUserNickname() . '/profile', $this->l10n->t('Profile'), '', $this->l10n->t('Your profile page')];
			$nav['usermenu'][] = ['profile/' . $this->session->getLocalUserNickname() . '/photos', $this->l10n->t('Photos'), '', $this->l10n->t('Your photos')];
			$nav['usermenu'][] = ['profile/' . $this->session->getLocalUserNickname() . '/media', $this->l10n->t('Media'), '', $this->l10n->t('Your postings with media')];
			$nav['usermenu'][] = ['calendar/', $this->l10n->t('Calendar'), '', $this->l10n->t('Your calendar')];
			$nav['usermenu'][] = ['notes/', $this->l10n->t('Personal notes'), '', $this->l10n->t('Your personal notes')];

			// user info
			$contact  = $this->database->selectFirst('contact', ['id', 'url', 'avatar', 'micro', 'name', 'nick', 'baseurl', 'updated'], ['uid' => $this->session->getLocalUserId(), 'self' => true]);
			$userinfo = [
				'icon' => Contact::getMicro($contact),
				'name' => $contact['name'],
			];
		}

		// "Home" should also take you home from an authenticated remote profile connection
		$homelink = $this->session->getMyUrl();
		if (!$homelink) {
			$homelink = $this->session->get('visitor_home', '');
		}

		if ($this->router->getModuleClass() != Home::class && !$this->session->getLocalUserId()) {
			$nav['home'] = [$homelink, $this->l10n->t('Home'), '', $this->l10n->t('Home Page')];
		}

		if (\Friendica\Module\Register::getPolicy() === \Friendica\Module\Register::OPEN && !$this->session->isAuthenticated()) {
			$nav['register'] = ['register', $this->l10n->t('Register'), '', $this->l10n->t('Create an account')];
		}

		$help_url = 'help';

		if (!$this->config->get('system', 'hide_help')) {
			$nav['help'] = [$help_url, $this->l10n->t('Help'), '', $this->l10n->t('Help and documentation')];
		}

		if (count($this->getAppMenu()) > 0) {
			$nav['apps'] = ['apps', $this->l10n->t('Apps'), '', $this->l10n->t('Addon applications, utilities, games')];
		}

		if ($this->session->getLocalUserId() || !$this->config->get('system', 'local_search')) {
			$nav['search'] = ['search', $this->l10n->t('Search'), '', $this->l10n->t('Search site content')];

			$nav['searchoption'] = [
				$this->l10n->t('Full Text'),
				$this->l10n->t('Tags'),
				$this->l10n->t('Contacts')
			];

			if ($this->config->get('system', 'poco_local_search')) {
				$nav['searchoption'][] = $this->l10n->t('Groups');
			}
		}

		$gdirpath = 'directory';
		if ($this->config->get('system', 'singleuser') && $this->config->get('system', 'directory')) {
			$gdirpath = OpenWebAuth::getZrlUrl($this->config->get('system', 'directory'), true);
		}

		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::COMMUNITY) && (($this->session->getLocalUserId() || $this->config->get('system', 'community_page_style') != Community::DISABLED_VISITOR) &&
			!($this->config->get('system', 'community_page_style') == Community::DISABLED))) {
			$nav['community'] = ['community', $this->l10n->t('Community'), '', $this->l10n->t('Conversations on this and other servers')];
		}

		if ($this->session->getLocalUserId()) {
			$nav['calendar'] = ['calendar', $this->l10n->t('Calendar'), '', $this->l10n->t('Calendar')];
		}

		$nav['directory'] = [$gdirpath, $this->l10n->t('Directory'), '', $this->l10n->t('People directory')];

		$nav['about'] = ['friendica', $this->l10n->t('Information'), '', $this->l10n->t('Information about this friendica instance')];

		if ($this->config->get('system', 'tosdisplay')) {
			$nav['tos'] = ['tos', $this->l10n->t('Terms of Service'), '', $this->l10n->t('Terms of Service of this Friendica instance')];
		}

		// The following nav links are only show to logged-in users
		if ($this->session->getLocalUserNickname()) {
			$nav['network'] = ['network', $this->l10n->t('Network'), '', $this->l10n->t('Conversations from your friends')];

			$nav['home'] = ['profile/' . $this->session->getLocalUserNickname(), $this->l10n->t('Home'), '', $this->l10n->t('Your posts and conversations')];

			// Don't show notifications for public communities
			if ($this->session->get('page_flags', '') != User::PAGE_FLAGS_COMMUNITY) {
				$nav['introductions']         = ['notifications/intros', $this->l10n->t('Introductions'), '', $this->l10n->t('Friend Requests')];
				$nav['notifications']         = ['notifications',	$this->l10n->t('Notifications'), '', $this->l10n->t('Notifications')];
				$nav['notifications']['all']  = ['notifications/system', $this->l10n->t('See all notifications'), '', ''];
				$nav['notifications']['mark'] = ['', $this->l10n->t('Mark as seen'), '', $this->l10n->t('Mark all system notifications as seen')];
			}

			$nav['messages']           = ['message', $this->l10n->t('Messages'), '', $this->l10n->t('Private mail')];
			$nav['messages']['inbox']  = ['message', $this->l10n->t('Inbox'), '', $this->l10n->t('Inbox')];
			$nav['messages']['outbox'] = ['message/sent', $this->l10n->t('Outbox'), '', $this->l10n->t('Outbox')];
			$nav['messages']['new']    = ['message/new', $this->l10n->t('New Message'), '', $this->l10n->t('New Message')];

			if (User::hasIdentities($this->session->getSubManagedUserId() ?: $this->session->getLocalUserId())) {
				$nav['delegation'] = ['delegation', $this->l10n->t('Accounts'), '', $this->l10n->t('Manage other pages')];
			}

			$nav['settings'] = ['settings', $this->l10n->t('Settings'), '', $this->l10n->t('Account settings')];

			$nav['contacts'] = ['contact', $this->l10n->t('Contacts'), '', $this->l10n->t('Manage/edit friends and contacts')];
		}

		// Show the link to the admin configuration page if user is admin
		if ($this->session->isSiteAdmin()) {
			$nav['admin']      = ['admin/', $this->l10n->t('Admin'), '', $this->l10n->t('Site setup and configuration')];
			$nav['moderation'] = ['moderation/', $this->l10n->t('Moderation'), '', $this->l10n->t('Content and user moderation')];
		}

		$nav['navigation'] = ['navigation/', $this->l10n->t('Navigation'), '', $this->l10n->t('Site map')];

		// Provide a banner/logo/whatever
		$banner = $this->config->get('system', 'banner');
		if (is_null($banner)) {
			$banner = '<a href="https://friendi.ca"><img id="logo-img" width="32" height="32" src="images/friendica.svg" alt="logo" /></a><span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>';
		}

		$nav_info = [
			'banner'       => $banner,
			'nav'          => $nav,
			'sitelocation' => $sitelocation,
			'userinfo'     => $userinfo,
		];

		$nav_info = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::NAV_INFO, $nav_info)
		)->getArray();

		return $nav_info;
	}
}
