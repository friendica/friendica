<?php

function nav(&$a) {

	/**
	 *
	 * Build page header and site navigation bars
	 *
	 */

	$ssl_state = ((local_user()) ? true : false);

	if(!(x($a->page,'nav')))
		$a->page['nav'] = '';

	/**
	 * Placeholder div for popup panel
	 */

	$a->page['nav'] .= '<div id="panel" style="display: none;"></div>' ;

	/**
	 *
	 * Our network is distributed, and as you visit friends some of the 
	 * sites look exactly the same - it isn't always easy to know where you are.
	 * Display the current site location as a navigation aid.
	 *
	 */

	$myident = ((is_array($a->user) && isset($a->user['nickname'])) ? $a->user['nickname'] . '@' : '');
		
	$sitelocation = $myident . substr($a->get_baseurl($ssl_state),strpos($a->get_baseurl($ssl_state),'//') + 2 );


	// nav links: array of array('href', 'text', 'extra css classes', 'title')
	$nav = Array();

	/**
	 * Display login or logout
	 */

	$nav['usermenu']=array();
	$userinfo = null;

	if(local_user()) {
		$nav['logout'] = Array('logout',t('Logout'), "", t('End this session'));
		
		// user menu
		$nav['usermenu'][] = Array('profile/' . $a->user['nickname'], t('Status'), "", t('Your posts and conversations'));
		$nav['usermenu'][] = Array('profile/' . $a->user['nickname']. '?tab=profile', t('Profile'), "", t('Your profile page'));
		$nav['usermenu'][] = Array('photos/' . $a->user['nickname'], t('Photos'), "", t('Your photos'));
		$nav['usermenu'][] = Array('events/', t('Events'), "", t('Your events'));
		$nav['usermenu'][] = Array('notes/', t('Personal notes'), "", t('Your personal photos'));
		
		// user info
		$r = q("SELECT micro FROM contact WHERE uid=%d AND self=1", intval($a->user['uid']));
		$userinfo = array(
			'icon' => (count($r) ? $r[0]['micro']: $a->get_baseurl($ssl_state)."/images/person-48.jpg"),
			'name' => $a->user['username'],
		);
		
	}
	else {
		$nav['login'] = Array('login',t('Login'), ($a->module == 'login'?'selected':''), t('Sign in'));
	}


	/**
	 * "Home" should also take you home from an authenticated remote profile connection
	 */

	$homelink = get_my_url();
	if(! $homelink)
		$homelink = ((x($_SESSION,'visitor_home')) ? $_SESSION['visitor_home'] : '');

	if(($a->module != 'home') && (! (local_user()))) 
		$nav['home'] = array($homelink, t('Home'), "", t('Home Page'));


	if(($a->config['register_policy'] == REGISTER_OPEN) && (! local_user()) && (! remote_user()))
		$nav['register'] = array('register',t('Register'), "", t('Create an account'));

	$help_url = $a->get_baseurl($ssl_state) . '/help';

	if(! get_config('system','hide_help'))
		$nav['help'] = array($help_url, t('Help'), "", t('Help and documentation'));

	if(count($a->apps)>0)
		$nav['apps'] = array('apps', t('Apps'), "", t('Addon applications, utilities, games'));

	$nav['search'] = array('search', t('Search'), "", t('Search site content'));

	$gdirpath = 'directory';

	if(strlen(get_config('system','singleuser'))) {
		$gdir = dirname(get_config('system','directory_submit_url'));
		if(strlen($gdir))
			$gdirpath = $gdir;
	}
	elseif(! get_config('system','no_community_page'))
		$nav['community'] = array('community', t('Community'), "", t('Conversations on this site'));

	$nav['directory'] = array($gdirpath, t('Directory'), "", t('People directory')); 

	/**
	 *
	 * The following nav links are only show to logged in users
	 *
	 */

	if(local_user()) {

		$nav['network'] = array('network', t('Network'), "", t('Conversations from your friends'));

		$nav['home'] = array('profile/' . $a->user['nickname'], t('Home'), "", t('Your posts and conversations'));


		/* only show friend requests for normal pages. Other page types have automatic friendship. */

		if($_SESSION['page_flags'] == PAGE_NORMAL) {
			$nav['introductions'] = array('notifications/intros',	t('Introductions'), "", t('Friend Requests'));
			$nav['notifications'] = array('notifications',	t('Notifications'), "", t('Notifications'));
			$nav['notifications']['all']=array('notifications/system', t('See all notifications'), "", "");
			$nav['notifications']['mark'] = array('', t('Mark all system notifications seen'), '','');

		}

		$nav['messages'] = array('message', t('Messages'), "", t('Private mail'));
		$nav['messages']['inbox'] = array('message', t('Inbox'), "", t('Inbox'));
		$nav['messages']['outbox']= array('message/sent', t('Outbox'), "", t('Outbox'));
		$nav['messages']['new'] = array('message/new', t('New Message'), "", t('New Message'));
		
		if(is_array($a->identities) && count($a->identities) > 1) {
			$nav['manage'] = array('manage', t('Manage'), "", t('Manage other pages'));
		}

		$nav['settings'] = array('settings', t('Settings'),"", t('Account settings'));
		$nav['profiles'] = array('profiles', t('Profiles'),"", t('Manage/edit profiles'));
		$nav['contacts'] = array('contacts', t('Contacts'),"", t('Manage/edit friends and contacts'));
	}

	/**
	 * Admin page
	 */
	 if (is_site_admin()){
		 $nav['admin'] = array('admin/', t('Admin'), "", t('Site setup and configuration'));
	 }


	/**
	 *
	 * Provide a banner/logo/whatever
	 *
	 */

	$banner = get_config('system','banner');

	if($banner === false) 
		$banner .= '<a href="http://friendica.com"><img id="logo-img" src="images/friendica-32.png" alt="logo" /></a><span id="logo-text"><a href="http://friendica.com">Friendica</a></span>';


	$tpl = get_markup_template('nav.tpl');

	$a->page['nav'] .= replace_macros($tpl, array(
		'$langselector' => lang_selector(),
		'$sitelocation' => $sitelocation,
		'$nav' => $nav,
		'$banner' =>  $banner,
		'$emptynotifications' => t('Nothing new here'),
		'$userinfo' => $userinfo,
		'$sel' => 	$a->nav_sel,
		'$apps' => $a->apps,
	));

	call_hooks('page_header', $a->page['nav']);
}

/*
 * Set a menu item in navbar as selected
 * 
 */
function nav_set_selected($item){
	$a = get_app();
    $a->nav_sel = array(
		'community' 	=> null,
		'network' 		=> null,
		'home'			=> null,
		'profiles'		=> null,
		'introductions' => null,
		'notifications'	=> null,
		'messages'		=> null,
		'directory'	    => null,
		'settings'		=> null,
		'contacts'		=> null,
		'manage'        => null,
		'register'      => null,
	);
	$a->nav_sel[$item] = 'selected';
}
