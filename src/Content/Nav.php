<?php
/**
 * @file src/Content/Nav.php
 */
namespace Friendica\Content;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Profile;
use dba;

require_once 'boot.php';
require_once 'dba.php';
require_once 'include/pgettext.php';
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
			'$emptynotifications' => t('Nothing new here'),
			'$userinfo' => $nav_info['userinfo'],
			'$sel' =>  $a->nav_sel,
			'$apps' => $a->apps,
			'$clear_notifs' => t('Clear notifications'),
			'$search_hint' => t('@name, !forum, #tags, content')
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
			$nav['logout'] = ['logout', t('Logout'), '', t('End this session')];
	
			// user menu
			$nav['usermenu'][] = ['profile/' . $a->user['nickname'], t('Status'), '', t('Your posts and conversations')];
			$nav['usermenu'][] = ['profile/' . $a->user['nickname'] . '?tab=profile', t('Profile'), '', t('Your profile page')];
			$nav['usermenu'][] = ['photos/' . $a->user['nickname'], t('Photos'), '', t('Your photos')];
			$nav['usermenu'][] = ['videos/' . $a->user['nickname'], t('Videos'), '', t('Your videos')];
			$nav['usermenu'][] = ['events/', t('Events'), '', t('Your events')];
			$nav['usermenu'][] = ['notes/', t('Personal notes'), '', t('Your personal notes')];
	
			// user info
			$contact = dba::selectFirst('contact', ['micro'], ['uid' => $a->user['uid'], 'self' => true]);
			$userinfo = [
				'icon' => (DBM::isResult($contact) ? $a->remove_baseurl($contact['micro']) : 'images/person-48.jpg'),
				'name' => $a->user['username'],
			];
		} else {
			$nav['login'] = ['login', t('Login'), ($a->module == 'login' ? 'selected' : ''), t('Sign in')];
		}
	
		// "Home" should also take you home from an authenticated remote profile connection
		$homelink = Profile::getMyURL();
		if (! $homelink) {
			$homelink = ((x($_SESSION, 'visitor_home')) ? $_SESSION['visitor_home'] : '');
		}
	
		if (($a->module != 'home') && (! (local_user()))) {
			$nav['home'] = [$homelink, t('Home'), '', t('Home Page')];
		}
	
		if (($a->config['register_policy'] == REGISTER_OPEN) && (! local_user()) && (! remote_user())) {
			$nav['register'] = ['register', t('Register'), '', t('Create an account')];
		}
	
		$help_url = 'help';
	
		if (!Config::get('system', 'hide_help')) {
			$nav['help'] = [$help_url, t('Help'), '', t('Help and documentation')];
		}
	
		if (count($a->apps) > 0) {
			$nav['apps'] = ['apps', t('Apps'), '', t('Addon applications, utilities, games')];
		}
	
		if (local_user() || !Config::get('system', 'local_search')) {
			$nav['search'] = ['search', t('Search'), '', t('Search site content')];
	
			$nav['searchoption'] = [
				t('Full Text'),
				t('Tags'),
				t('Contacts')
			];
	
			if (Config::get('system', 'poco_local_search')) {
				$nav['searchoption'][] = t('Forums');
			}
		}
	
		$gdirpath = 'directory';
	
		if (strlen(Config::get('system', 'singleuser'))) {
			$gdir = Config::get('system', 'directory');
			if (strlen($gdir)) {
				$gdirpath = Profile::zrl($gdir, true);
			}
		}
	
		if (local_user() || Config::get('system', 'community_page_style') != CP_NO_COMMUNITY_PAGE) {
			$nav['community'] = ['community', t('Community'), '', t('Conversations on this and other servers')];
		}
	
		if (local_user()) {
			$nav['events'] = ['events', t('Events'), '', t('Events and Calendar')];
		}
	
		$nav['directory'] = [$gdirpath, t('Directory'), '', t('People directory')];
	
		$nav['about'] = ['friendica', t('Information'), '', t('Information about this friendica instance')];
	
		// The following nav links are only show to logged in users
		if (local_user()) {
			$nav['network'] = ['network', t('Network'), '', t('Conversations from your friends')];
			$nav['net_reset'] = ['network/0?f=&order=comment&nets=all', t('Network Reset'), '', t('Load Network page with no filters')];
	
			$nav['home'] = ['profile/' . $a->user['nickname'], t('Home'), '', t('Your posts and conversations')];
	
			if (in_array($_SESSION['page_flags'], [PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE, PAGE_PRVGROUP])) {
				// only show friend requests for normal pages. Other page types have automatic friendship.
				if (in_array($_SESSION['page_flags'], [PAGE_NORMAL, PAGE_SOAPBOX, PAGE_PRVGROUP])) {
					$nav['introductions'] = ['notifications/intros', t('Introductions'), '', t('Friend Requests')];
				}
				if (in_array($_SESSION['page_flags'], [PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE])) {
					$nav['notifications'] = ['notifications',	t('Notifications'), '', t('Notifications')];
					$nav['notifications']['all'] = ['notifications/system', t('See all notifications'), '', ''];
					$nav['notifications']['mark'] = ['', t('Mark as seen'), '', t('Mark all system notifications seen')];
				}
			}
	
			$nav['messages'] = ['message', t('Messages'), '', t('Private mail')];
			$nav['messages']['inbox'] = ['message', t('Inbox'), '', t('Inbox')];
			$nav['messages']['outbox'] = ['message/sent', t('Outbox'), '', t('Outbox')];
			$nav['messages']['new'] = ['message/new', t('New Message'), '', t('New Message')];
	
			if (is_array($a->identities) && count($a->identities) > 1) {
				$nav['manage'] = ['manage', t('Manage'), '', t('Manage other pages')];
			}
	
			$nav['delegations'] = ['delegate', t('Delegations'), '', t('Delegate Page Management')];
	
			$nav['settings'] = ['settings', t('Settings'), '', t('Account settings')];
	
			if (Feature::isEnabled(local_user(), 'multi_profiles')) {
				$nav['profiles'] = ['profiles', t('Profiles'), '', t('Manage/Edit Profiles')];
			}
	
			$nav['contacts'] = ['contacts', t('Contacts'), '', t('Manage/edit friends and contacts')];
		}
	
		// Show the link to the admin configuration page if user is admin
		if (is_site_admin()) {
			$nav['admin'] = ['admin/', t('Admin'), '', t('Site setup and configuration')];
		}
	
		$nav['navigation'] = ['navigation/', t('Navigation'), '', t('Site map')];
	
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
