<?php
/**
 * @file src/Content/Nav.php
 */
namespace Friendica\Content;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Model\User;

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
		'events'    => null,
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
	public static function setSelected($item)
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
	public static function build(App $a)
	{
		// Placeholder div for popup panel
		$nav = '<div id="panel" style="display: none;"></div>';

		$nav_info = self::getInfo($a);

		$tpl = Renderer::getMarkupTemplate('nav.tpl');

		$nav .= Renderer::replaceMacros($tpl, [
			'$sitelocation' => $nav_info['sitelocation'],
			'$nav'          => $nav_info['nav'],
			'$banner'       => $nav_info['banner'],
			'$emptynotifications' => L10n::t('Nothing new here'),
			'$userinfo'     => $nav_info['userinfo'],
			'$sel'          => self::$selected,
			'$apps'         => self::getAppMenu(),
			'$clear_notifs' => L10n::t('Clear notifications'),
			'$search_hint'  => L10n::t('@name, !forum, #tags, content')
		]);

		Hook::callAll('page_header', $nav);

		return $nav;
	}

	/**
	 * Returns the addon app menu
	 *
	 * @return array
	 */
	public static function getAppMenu()
	{
		if (is_null(self::$app_menu)) {
			self::populateAppMenu();
		}

		return self::$app_menu;
	}

	/**
	 * Fills the apps static variable with apps that require a menu
	 */
	private static function populateAppMenu()
	{
		self::$app_menu = [];

		//Don't populate apps_menu if apps are private
		$privateapps = Config::get('config', 'private_addons', false);
		if (local_user() || !$privateapps) {
			$arr = ['app_menu' => self::$app_menu];

			Hook::callAll('app_menu', $arr);

			self::$app_menu = $arr['app_menu'];
		}
	}

	/**
	 * Prepares a list of navigation links
	 *
	 * @brief Prepares a list of navigation links
	 * @param  App   $a
	 * @return array Navigation links
	 *    string 'sitelocation' => The webbie (username@site.com)
	 *    array 'nav' => Array of links used in the nav menu
	 *    string 'banner' => Formatted html link with banner image
	 *    array 'userinfo' => Array of user information (name, icon)
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getInfo(App $a)
	{
		$ssl_state = ((local_user()) ? true : false);

		/*
		 * Our network is distributed, and as you visit friends some of the
		 * sites look exactly the same - it isn't always easy to know where you are.
		 * Display the current site location as a navigation aid.
		 */

		$myident = ((is_array($a->user) && isset($a->user['nickname'])) ? $a->user['nickname'] . '@' : '');

		$sitelocation = $myident . substr(System::baseUrl($ssl_state), strpos(System::baseUrl($ssl_state), '//') + 2);

		// nav links: array of array('href', 'text', 'extra css classes', 'title')
		$nav = [];

		// Display login or logout
		$nav['usermenu'] = [];
		$userinfo = null;

		if (Session::isAuthenticated()) {
			$nav['logout'] = ['logout', L10n::t('Logout'), '', L10n::t('End this session')];
		} else {
			$nav['login'] = ['login', L10n::t('Login'), (DI::module()->getName() == 'login' ? 'selected' : ''), L10n::t('Sign in')];
		}

		if (local_user()) {
			// user menu
			$nav['usermenu'][] = ['profile/' . $a->user['nickname'], L10n::t('Status'), '', L10n::t('Your posts and conversations')];
			$nav['usermenu'][] = ['profile/' . $a->user['nickname'] . '?tab=profile', L10n::t('Profile'), '', L10n::t('Your profile page')];
			$nav['usermenu'][] = ['photos/' . $a->user['nickname'], L10n::t('Photos'), '', L10n::t('Your photos')];
			$nav['usermenu'][] = ['videos/' . $a->user['nickname'], L10n::t('Videos'), '', L10n::t('Your videos')];
			$nav['usermenu'][] = ['events/', L10n::t('Events'), '', L10n::t('Your events')];
			$nav['usermenu'][] = ['notes/', L10n::t('Personal notes'), '', L10n::t('Your personal notes')];

			// user info
			$contact = DBA::selectFirst('contact', ['micro'], ['uid' => $a->user['uid'], 'self' => true]);
			$userinfo = [
				'icon' => (DBA::isResult($contact) ? DI::baseUrl()->remove($contact['micro']) : 'images/person-48.jpg'),
				'name' => $a->user['username'],
			];
		}

		// "Home" should also take you home from an authenticated remote profile connection
		$homelink = Profile::getMyURL();
		if (! $homelink) {
			$homelink = Session::get('visitor_home', '');
		}

		if ((DI::module()->getName() != 'home') && (! (local_user()))) {
			$nav['home'] = [$homelink, L10n::t('Home'), '', L10n::t('Home Page')];
		}

		if (intval(Config::get('config', 'register_policy')) === \Friendica\Module\Register::OPEN && !Session::isAuthenticated()) {
			$nav['register'] = ['register', L10n::t('Register'), '', L10n::t('Create an account')];
		}

		$help_url = 'help';

		if (!Config::get('system', 'hide_help')) {
			$nav['help'] = [$help_url, L10n::t('Help'), '', L10n::t('Help and documentation')];
		}

		if (count(self::getAppMenu()) > 0) {
			$nav['apps'] = ['apps', L10n::t('Apps'), '', L10n::t('Addon applications, utilities, games')];
		}

		if (local_user() || !Config::get('system', 'local_search')) {
			$nav['search'] = ['search', L10n::t('Search'), '', L10n::t('Search site content')];

			$nav['searchoption'] = [
				L10n::t('Full Text'),
				L10n::t('Tags'),
				L10n::t('Contacts')
			];

			if (Config::get('system', 'poco_local_search')) {
				$nav['searchoption'][] = L10n::t('Forums');
			}
		}

		$gdirpath = 'directory';

		if (strlen(Config::get('system', 'singleuser'))) {
			$gdir = Config::get('system', 'directory');
			if (strlen($gdir)) {
				$gdirpath = Profile::zrl($gdir, true);
			}
		}

		if ((local_user() || Config::get('system', 'community_page_style') != CP_NO_COMMUNITY_PAGE) &&
			!(Config::get('system', 'community_page_style') == CP_NO_INTERNAL_COMMUNITY)) {
			$nav['community'] = ['community', L10n::t('Community'), '', L10n::t('Conversations on this and other servers')];
		}

		if (local_user()) {
			$nav['events'] = ['events', L10n::t('Events'), '', L10n::t('Events and Calendar')];
		}

		$nav['directory'] = [$gdirpath, L10n::t('Directory'), '', L10n::t('People directory')];

		$nav['about'] = ['friendica', L10n::t('Information'), '', L10n::t('Information about this friendica instance')];

		if (Config::get('system', 'tosdisplay')) {
			$nav['tos'] = ['tos', L10n::t('Terms of Service'), '', L10n::t('Terms of Service of this Friendica instance')];
		}

		// The following nav links are only show to logged in users
		if (local_user()) {
			$nav['network'] = ['network', L10n::t('Network'), '', L10n::t('Conversations from your friends')];
			$nav['net_reset'] = ['network/?f=', L10n::t('Network Reset'), '', L10n::t('Load Network page with no filters')];

			$nav['home'] = ['profile/' . $a->user['nickname'], L10n::t('Home'), '', L10n::t('Your posts and conversations')];

			// Don't show notifications for public communities
			if (Session::get('page_flags', '') != User::PAGE_FLAGS_COMMUNITY) {
				$nav['introductions'] = ['notifications/intros', L10n::t('Introductions'), '', L10n::t('Friend Requests')];
				$nav['notifications'] = ['notifications',	L10n::t('Notifications'), '', L10n::t('Notifications')];
				$nav['notifications']['all'] = ['notifications/system', L10n::t('See all notifications'), '', ''];
				$nav['notifications']['mark'] = ['', L10n::t('Mark as seen'), '', L10n::t('Mark all system notifications seen')];
			}

			$nav['messages'] = ['message', L10n::t('Messages'), '', L10n::t('Private mail')];
			$nav['messages']['inbox'] = ['message', L10n::t('Inbox'), '', L10n::t('Inbox')];
			$nav['messages']['outbox'] = ['message/sent', L10n::t('Outbox'), '', L10n::t('Outbox')];
			$nav['messages']['new'] = ['message/new', L10n::t('New Message'), '', L10n::t('New Message')];

			if (is_array($a->identities) && count($a->identities) > 1) {
				$nav['delegation'] = ['delegation', L10n::t('Delegation'), '', L10n::t('Manage other pages')];
			}

			$nav['settings'] = ['settings', L10n::t('Settings'), '', L10n::t('Account settings')];

			if (Feature::isEnabled(local_user(), 'multi_profiles')) {
				$nav['profiles'] = ['profiles', L10n::t('Profiles'), '', L10n::t('Manage/Edit Profiles')];
			}

			$nav['contacts'] = ['contact', L10n::t('Contacts'), '', L10n::t('Manage/edit friends and contacts')];
		}

		// Show the link to the admin configuration page if user is admin
		if (is_site_admin()) {
			$nav['admin'] = ['admin/', L10n::t('Admin'), '', L10n::t('Site setup and configuration')];
		}

		$nav['navigation'] = ['navigation/', L10n::t('Navigation'), '', L10n::t('Site map')];

		// Provide a banner/logo/whatever
		$banner = Config::get('system', 'banner');
		if (is_null($banner)) {
			$banner = '<a href="https://friendi.ca"><img id="logo-img" src="images/friendica-32.png" alt="logo" /></a><span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>';
		}

		Hook::callAll('nav_info', $nav);

		return [
			'sitelocation' => $sitelocation,
			'nav' => $nav,
			'banner' => $banner,
			'userinfo' => $userinfo,
		];
	}
}
