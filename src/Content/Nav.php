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

namespace Friendica\Content;

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Conversation\Community;

class Nav
{
	private static $selected = [
		'global'    => null,
		'community' => null,
		'network'   => null,
		'home'      => null,
		'profiles'  => null,
		'introductions' => null,
		'notifications' => null,
		'messages'  => null,
		'directory' => null,
		'settings'  => null,
		'contacts'  => null,
		'delegation'=> null,
		'calendar'  => null,
		'register'  => null
	];

	/**
	 * An array of HTML links provided by addons providing a module via the app_menu hook
	 *
	 * @var array
	 */
	private static $app_menu = null;

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
	 * @param  App    $a
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function build(App $a): string
	{
		// Placeholder div for popup panel
		$nav = '<div id="panel" style="display: none;"></div>';

		$nav_info = self::getInfo($a);

		$tpl = Renderer::getMarkupTemplate('nav.tpl');

		$nav .= Renderer::replaceMacros($tpl, [
			'$sitelocation' => $nav_info['sitelocation'],
			'$nav'          => $nav_info['nav'],
			'$banner'       => $nav_info['banner'],
			'$emptynotifications' => DI::l10n()->t('Nothing new here'),
			'$userinfo'     => $nav_info['userinfo'],
			'$sel'          => self::$selected,
			'$apps'         => self::getAppMenu(),
			'$home'         => DI::l10n()->t('Go back'),
			'$clear_notifs' => DI::l10n()->t('Clear notifications'),
			'$search_hint'  => DI::l10n()->t('@name, !forum, #tags, content')
		]);

		Hook::callAll('page_header', $nav);

		return $nav;
	}

	/**
	 * Returns the addon app menu
	 *
	 * @return array
	 */
	public static function getAppMenu(): array
	{
		if (is_null(self::$app_menu)) {
			self::populateAppMenu();
		}

		return self::$app_menu;
	}

	/**
	 * Fills the apps static variable with apps that require a menu
	 *
	 * @return void
	 */
	private static function populateAppMenu()
	{
		self::$app_menu = [];

		//Don't populate apps_menu if apps are private
		$privateapps = DI::config()->get('config', 'private_addons', false);
		if (DI::userSession()->getLocalUserId() || !$privateapps) {
			$arr = ['app_menu' => self::$app_menu];

			Hook::callAll('app_menu', $arr);

			self::$app_menu = $arr['app_menu'];
		}
	}

	/**
	 * Prepares a list of navigation links
	 *
	 * @param  App   $a
	 * @return array Navigation links
	 *    string 'sitelocation' => The webbie (username@site.com)
	 *    array 'nav' => Array of links used in the nav menu
	 *    string 'banner' => Formatted html link with banner image
	 *    array 'userinfo' => Array of user information (name, icon)
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getInfo(App $a): array
	{
		$ssl_state = (bool) DI::userSession()->getLocalUserId();

		/*
		 * Our network is distributed, and as you visit friends some of the
		 * sites look exactly the same - it isn't always easy to know where you are.
		 * Display the current site location as a navigation aid.
		 */

		$myident = !empty($a->getLoggedInUserNickname()) ? $a->getLoggedInUserNickname() . '@' : '';

		$sitelocation = $myident . substr(DI::baseUrl()->get($ssl_state), strpos(DI::baseUrl()->get($ssl_state), '//') + 2);

		$nav = [
			'admin'         => null,
			'moderation'    => null,
			'apps'          => null,
			'community'     => null,
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
		if (DI::userSession()->isAuthenticated()) {
			$nav['logout'] = ['logout', DI::l10n()->t('Logout'), '', DI::l10n()->t('End this session')];
		} else {
			$nav['login'] = ['login', DI::l10n()->t('Login'), (DI::args()->getModuleName() == 'login' ? 'selected' : ''), DI::l10n()->t('Sign in')];
		}

		if ($a->isLoggedIn()) {
			// user menu
			$nav['usermenu'][] = ['profile/' . $a->getLoggedInUserNickname(), DI::l10n()->t('Status'), '', DI::l10n()->t('Your posts and conversations')];
			$nav['usermenu'][] = ['profile/' . $a->getLoggedInUserNickname() . '/profile', DI::l10n()->t('Profile'), '', DI::l10n()->t('Your profile page')];
			$nav['usermenu'][] = ['profile/' . $a->getLoggedInUserNickname() . '/photos', DI::l10n()->t('Photos'), '', DI::l10n()->t('Your photos')];
			$nav['usermenu'][] = ['profile/' . $a->getLoggedInUserNickname() . '/media', DI::l10n()->t('Media'), '', DI::l10n()->t('Your postings with media')];
			$nav['usermenu'][] = ['calendar/', DI::l10n()->t('Calendar'), '', DI::l10n()->t('Your calendar')];
			$nav['usermenu'][] = ['notes/', DI::l10n()->t('Personal notes'), '', DI::l10n()->t('Your personal notes')];

			// user info
			$contact = DBA::selectFirst('contact', ['id', 'url', 'avatar', 'micro', 'name', 'nick', 'baseurl', 'updated'], ['uid' => $a->getLoggedInUserId(), 'self' => true]);
			$userinfo = [
				'icon' => Contact::getMicro($contact),
				'name' => $contact['name'],
			];
		}

		// "Home" should also take you home from an authenticated remote profile connection
		$homelink = Profile::getMyURL();
		if (! $homelink) {
			$homelink = DI::session()->get('visitor_home', '');
		}

		if (DI::args()->getModuleName() != 'home' && ! DI::userSession()->getLocalUserId()) {
			$nav['home'] = [$homelink, DI::l10n()->t('Home'), '', DI::l10n()->t('Home Page')];
		}

		if (intval(DI::config()->get('config', 'register_policy')) === \Friendica\Module\Register::OPEN && !DI::userSession()->isAuthenticated()) {
			$nav['register'] = ['register', DI::l10n()->t('Register'), '', DI::l10n()->t('Create an account')];
		}

		$help_url = 'help';

		if (!DI::config()->get('system', 'hide_help')) {
			$nav['help'] = [$help_url, DI::l10n()->t('Help'), '', DI::l10n()->t('Help and documentation')];
		}

		if (count(self::getAppMenu()) > 0) {
			$nav['apps'] = ['apps', DI::l10n()->t('Apps'), '', DI::l10n()->t('Addon applications, utilities, games')];
		}

		if (DI::userSession()->getLocalUserId() || !DI::config()->get('system', 'local_search')) {
			$nav['search'] = ['search', DI::l10n()->t('Search'), '', DI::l10n()->t('Search site content')];

			$nav['searchoption'] = [
				DI::l10n()->t('Full Text'),
				DI::l10n()->t('Tags'),
				DI::l10n()->t('Contacts')
			];

			if (DI::config()->get('system', 'poco_local_search')) {
				$nav['searchoption'][] = DI::l10n()->t('Forums');
			}
		}

		$gdirpath = 'directory';
		if (DI::config()->get('system', 'singleuser') && DI::config()->get('system', 'directory')) {
			$gdirpath = Profile::zrl(DI::config()->get('system', 'directory'), true);
		}

		if ((DI::userSession()->getLocalUserId() || DI::config()->get('system', 'community_page_style') != Community::DISABLED_VISITOR) &&
			!(DI::config()->get('system', 'community_page_style') == Community::DISABLED)) {
			$nav['community'] = ['community', DI::l10n()->t('Community'), '', DI::l10n()->t('Conversations on this and other servers')];
		}

		if (DI::userSession()->getLocalUserId()) {
			$nav['calendar'] = ['calendar', DI::l10n()->t('Calendar'), '', DI::l10n()->t('Calendar')];
		}

		$nav['directory'] = [$gdirpath, DI::l10n()->t('Directory'), '', DI::l10n()->t('People directory')];

		$nav['about'] = ['friendica', DI::l10n()->t('Information'), '', DI::l10n()->t('Information about this friendica instance')];

		if (DI::config()->get('system', 'tosdisplay')) {
			$nav['tos'] = ['tos', DI::l10n()->t('Terms of Service'), '', DI::l10n()->t('Terms of Service of this Friendica instance')];
		}

		// The following nav links are only show to logged in users
		if (DI::userSession()->getLocalUserId() && !empty($a->getLoggedInUserNickname())) {
			$nav['network'] = ['network', DI::l10n()->t('Network'), '', DI::l10n()->t('Conversations from your friends')];

			$nav['home'] = ['profile/' . $a->getLoggedInUserNickname(), DI::l10n()->t('Home'), '', DI::l10n()->t('Your posts and conversations')];

			// Don't show notifications for public communities
			if (DI::session()->get('page_flags', '') != User::PAGE_FLAGS_COMMUNITY) {
				$nav['introductions'] = ['notifications/intros', DI::l10n()->t('Introductions'), '', DI::l10n()->t('Friend Requests')];
				$nav['notifications'] = ['notifications',	DI::l10n()->t('Notifications'), '', DI::l10n()->t('Notifications')];
				$nav['notifications']['all'] = ['notifications/system', DI::l10n()->t('See all notifications'), '', ''];
				$nav['notifications']['mark'] = ['', DI::l10n()->t('Mark as seen'), '', DI::l10n()->t('Mark all system notifications as seen')];
			}

			$nav['messages'] = ['message', DI::l10n()->t('Messages'), '', DI::l10n()->t('Private mail')];
			$nav['messages']['inbox'] = ['message', DI::l10n()->t('Inbox'), '', DI::l10n()->t('Inbox')];
			$nav['messages']['outbox'] = ['message/sent', DI::l10n()->t('Outbox'), '', DI::l10n()->t('Outbox')];
			$nav['messages']['new'] = ['message/new', DI::l10n()->t('New Message'), '', DI::l10n()->t('New Message')];

			if (User::hasIdentities(DI::userSession()->getSubManagedUserId() ?: DI::userSession()->getLocalUserId())) {
				$nav['delegation'] = ['delegation', DI::l10n()->t('Accounts'), '', DI::l10n()->t('Manage other pages')];
			}

			$nav['settings'] = ['settings', DI::l10n()->t('Settings'), '', DI::l10n()->t('Account settings')];

			$nav['contacts'] = ['contact', DI::l10n()->t('Contacts'), '', DI::l10n()->t('Manage/edit friends and contacts')];
		}

		// Show the link to the admin configuration page if user is admin
		if ($a->isSiteAdmin()) {
			$nav['admin']      = ['admin/', DI::l10n()->t('Admin'), '', DI::l10n()->t('Site setup and configuration')];
			$nav['moderation'] = ['moderation/', DI::l10n()->t('Moderation'), '', DI::l10n()->t('Content and user moderation')];
		}

		$nav['navigation'] = ['navigation/', DI::l10n()->t('Navigation'), '', DI::l10n()->t('Site map')];

		// Provide a banner/logo/whatever
		$banner = DI::config()->get('system', 'banner');
		if (is_null($banner)) {
			$banner = '<a href="https://friendi.ca"><img id="logo-img" width="32" height="32" src="images/friendica.svg" alt="logo" /></a><span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>';
		}

		$nav_info = [
			'banner'       => $banner,
			'nav'          => $nav,
			'sitelocation' => $sitelocation,
			'userinfo'     => $userinfo,
		];

		Hook::callAll('nav_info', $nav_info);

		return $nav_info;
	}
}
