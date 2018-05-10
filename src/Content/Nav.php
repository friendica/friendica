<?php
/**
 * @file src/Content/Nav.php
 */
namespace Friendica\Content;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Profile;
use dba;

require_once 'boot.php';
require_once 'dba.php';
require_once 'include/text.php';

class Nav
{
	/**
	 * Build page header and site navigation bars
	 */
	public static function build(App $a)
	{
		if (!(x($a->page, 'nav'))) {
			$a->page['nav'] = '';
		}

		$a->page['htmlhead'] .= replace_macros(get_markup_template('nav_head.tpl'), []);

		/*
		 * Placeholder div for popup panel
		 */

		$a->page['nav'] .= '<div id="panel" style="display: none;"></div>' ;

		$nav_info = self::getInfo($a);

		/*
		 * Build the page
		 */

		$tpl = get_markup_template('nav.tpl');

		$a->page['nav'] .= replace_macros($tpl, [
			'$baseurl' => System::baseUrl(),
			'$sitelocation' => $nav_info['sitelocation'],
			'$nav' => $nav_info['nav'],
			'$banner' => $nav_info['banner'],
			'$emptynotifications' => L10n::t('Nothing new here'),
			'$userinfo' => $nav_info['userinfo'],
			'$sel' =>  $a->nav_sel,
			'$apps' => $a->apps,
			'$clear_notifs' => L10n::t('Clear notifications'),
			'$search_hint' => L10n::t('@name, !forum, #tags, content')
		]);

		Addon::callHooks('page_header', $a->page['nav']);
	}

	/**
	 * Prepares a list of navigation links
	 *
	 * @brief Prepares a list of navigation links
	 * @param App $a
	 * @return array Navigation links
	 *	string 'sitelocation' => The webbie (username@site.com)
	 *	array 'nav' => Array of links used in the nav menu
	 *	string 'banner' => Formatted html link with banner image
	 *	array 'userinfo' => Array of user information (name, icon)
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

		if (local_user()) {
			$nav['logout'] = ['logout', L10n::t('Logout'), '', L10n::t('End this session')];

			// user menu
			$nav['usermenu'][] = ['profile/' . $a->user['nickname'], L10n::t('Status'), '', L10n::t('Your posts and conversations')];
			$nav['usermenu'][] = ['profile/' . $a->user['nickname'] . '?tab=profile', L10n::t('Profile'), '', L10n::t('Your profile page')];
			$nav['usermenu'][] = ['photos/' . $a->user['nickname'], L10n::t('Photos'), '', L10n::t('Your photos')];
			$nav['usermenu'][] = ['videos/' . $a->user['nickname'], L10n::t('Videos'), '', L10n::t('Your videos')];
			$nav['usermenu'][] = ['events/', L10n::t('Events'), '', L10n::t('Your events')];
			$nav['usermenu'][] = ['notes/', L10n::t('Personal notes'), '', L10n::t('Your personal notes')];

			// user info
			$contact = dba::selectFirst('contact', ['micro'], ['uid' => $a->user['uid'], 'self' => true]);
			$userinfo = [
				'icon' => (DBM::is_result($contact) ? $a->remove_baseurl($contact['micro']) : 'images/person-48.jpg'),
				'name' => $a->user['username'],
			];
		} else {
			$nav['login'] = ['login', L10n::t('Login'), ($a->module == 'login' ? 'selected' : ''), L10n::t('Sign in')];
		}

		// "Home" should also take you home from an authenticated remote profile connection
		$homelink = Profile::getMyURL();
		if (! $homelink) {
			$homelink = ((x($_SESSION, 'visitor_home')) ? $_SESSION['visitor_home'] : '');
		}

		if (($a->module != 'home') && (! (local_user()))) {
			$nav['home'] = [$homelink, L10n::t('Home'), '', L10n::t('Home Page')];
		}

		if (($a->config['register_policy'] == REGISTER_OPEN) && (! local_user()) && (! remote_user())) {
			$nav['register'] = ['register', L10n::t('Register'), '', L10n::t('Create an account')];
		}

		$help_url = 'help';

		if (!Config::get('system', 'hide_help')) {
			$nav['help'] = [$help_url, L10n::t('Help'), '', L10n::t('Help and documentation')];
		}

		if (count($a->apps) > 0) {
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

		// The following nav links are only show to logged in users
		if (local_user()) {
			$nav['network'] = ['network', L10n::t('Network'), '', L10n::t('Conversations from your friends')];
			$nav['net_reset'] = ['network/0?f=&order=comment&nets=all', L10n::t('Network Reset'), '', L10n::t('Load Network page with no filters')];

			$nav['home'] = ['profile/' . $a->user['nickname'], L10n::t('Home'), '', L10n::t('Your posts and conversations')];

			// Don't show notifications for public communities
			if ($_SESSION['page_flags'] != PAGE_COMMUNITY) {
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
				$nav['manage'] = ['manage', L10n::t('Manage'), '', L10n::t('Manage other pages')];
			}

			$nav['delegations'] = ['delegate', L10n::t('Delegations'), '', L10n::t('Delegate Page Management')];

			$nav['settings'] = ['settings', L10n::t('Settings'), '', L10n::t('Account settings')];

			if (Feature::isEnabled(local_user(), 'multi_profiles')) {
				$nav['profiles'] = ['profiles', L10n::t('Profiles'), '', L10n::t('Manage/Edit Profiles')];
			}

			$nav['contacts'] = ['contacts', L10n::t('Contacts'), '', L10n::t('Manage/edit friends and contacts')];
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

		Addon::callHooks('nav_info', $nav);

		return [
			'sitelocation' => $sitelocation,
			'nav' => $nav,
			'banner' => $banner,
			'userinfo' => $userinfo,
		];
	}

	/**
	 * Set a menu item in navbar as selected
	 */
	public static function setSelected($item)
	{
		$a = get_app();
		$a->nav_sel = [
			'global' 	=> null,
			'community' 	=> null,
			'network' 	=> null,
			'home'		=> null,
			'profiles'	=> null,
			'introductions' => null,
			'notifications'	=> null,
			'messages'	=> null,
			'directory'	=> null,
			'settings'	=> null,
			'contacts'	=> null,
			'manage'	=> null,
			'events'	=> null,
			'register'	=> null
		];
		$a->nav_sel[$item] = 'selected';
	}
}
