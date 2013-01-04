<?php

/**
 *
 * Friendica
 *
 */

/**
 *
 * bootstrap the application
 *
 */

require_once('boot.php');
require_once('object/BaseObject.php');

$a = new App;
BaseObject::set_app($a);

/**
 *
 * Load the configuration file which contains our DB credentials.
 * Ignore errors. If the file doesn't exist or is empty, we are running in installation mode.
 *
 */

$install = ((file_exists('.htconfig.php') && filesize('.htconfig.php')) ? false : true);

@include(".htconfig.php");



$lang = get_browser_language();
	
load_translation_table($lang);

/**
 *
 * Try to open the database;
 *
 */

require_once("include/dba.php");

if(! $install) {
	$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
    	    unset($db_host, $db_user, $db_pass, $db_data);

	/**
	 * Load configs from db. Overwrite configs from .htconfig.php
	 */

	load_config('config');
	load_config('system');

	require_once("include/session.php");
	load_hooks();
	call_hooks('init_1');
}


/**
 *
 * Important stuff we always need to do.
 *
 * The order of these may be important so use caution if you think they're all
 * intertwingled with no logical order and decide to sort it out. Some of the
 * dependencies have changed, but at least at one time in the recent past - the 
 * order was critical to everything working properly
 *
 */

session_start();

/**
 * Language was set earlier, but we can over-ride it in the session.
 * We have to do it here because the session was just now opened.
 */

if(array_key_exists('system_language',$_POST)) {
	if(strlen($_POST['system_language']))
		$_SESSION['language'] = $_POST['system_language'];
	else
		unset($_SESSION['language']);
}
if((x($_SESSION,'language')) && ($_SESSION['language'] !== $lang)) {
	$lang = $_SESSION['language'];
	load_translation_table($lang);
}

if((x($_GET,'zrl')) && (! $install)) {
	$_SESSION['my_url'] = $_GET['zrl'];
	$a->query_string = preg_replace('/[\?&]zrl=(.*?)([\?&]|$)/is','',$a->query_string);
	zrl_init($a);
}

/**
 *
 * For Mozilla auth manager - still needs sorting, and this might conflict with LRDD header.
 * Apache/PHP lumps the Link: headers into one - and other services might not be able to parse it
 * this way. There's a PHP flag to link the headers because by default this will over-write any other 
 * link header. 
 *
 * What we really need to do is output the raw headers ourselves so we can keep them separate.
 *
 */
 
// header('Link: <' . $a->get_baseurl() . '/amcd>; rel="acct-mgmt";');

if((x($_SESSION,'authenticated')) || (x($_POST,'auth-params')) || ($a->module === 'login'))
	require("include/auth.php");

if(! x($_SESSION,'authenticated'))
	header('X-Account-Management-Status: none');


/* set up page['htmlhead'] and page['end'] for the modules to use */
$a->page['htmlhead'] = '';
$a->page['end'] = '';


if(! x($_SESSION,'sysmsg'))
	$_SESSION['sysmsg'] = array();

if(! x($_SESSION,'sysmsg_info'))
	$_SESSION['sysmsg_info'] = array();

/*
 * check_config() is responsible for running update scripts. These automatically 
 * update the DB schema whenever we push a new one out. It also checks to see if
 * any plugins have been added or removed and reacts accordingly. 
 */


if($install)
	$a->module = 'install';
else
	check_config($a);

nav_set_selected('nothing');

$arr = array('app_menu' => $a->apps);

call_hooks('app_menu', $arr);

$a->apps = $arr['app_menu'];

/**
 *
 * We have already parsed the server path into $a->argc and $a->argv
 *
 * $a->argv[0] is our module name. We will load the file mod/{$a->argv[0]}.php
 * and use it for handling our URL request.
 * The module file contains a few functions that we call in various circumstances
 * and in the following order:
 * 
 * "module"_init
 * "module"_post (only called if there are $_POST variables)
 * "module"_afterpost
 * "module"_content - the string return of this function contains our page body
 *
 * Modules which emit other serialisations besides HTML (XML,JSON, etc.) should do 
 * so within the module init and/or post functions and then invoke killme() to terminate
 * further processing.
 */

if(strlen($a->module)) {

	/**
	 *
	 * We will always have a module name.
	 * First see if we have a plugin which is masquerading as a module.
	 *
	 */

	if(is_array($a->plugins) && in_array($a->module,$a->plugins) && file_exists("addon/{$a->module}/{$a->module}.php")) {
		include_once("addon/{$a->module}/{$a->module}.php");
		if(function_exists($a->module . '_module'))
			$a->module_loaded = true;
	}

	/**
	 * If not, next look for a 'standard' program module in the 'mod' directory
	 */

	if((! $a->module_loaded) && (file_exists("mod/{$a->module}.php"))) {
		include_once("mod/{$a->module}.php");
		$a->module_loaded = true;
	}

	/**
	 *
	 * The URL provided does not resolve to a valid module.
	 *
	 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'. 
	 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic - 
	 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
	 * this will often succeed and eventually do the right thing.
	 *
	 * Otherwise we are going to emit a 404 not found.
	 *
	 */

	if(! $a->module_loaded) {

		// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
		if((x($_SERVER,'QUERY_STRING')) && preg_match('/{[0-9]}/',$_SERVER['QUERY_STRING']) !== 0) {
			killme();
		}

		if((x($_SERVER,'QUERY_STRING')) && ($_SERVER['QUERY_STRING'] === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
			logger('index.php: dreamhost_error_hack invoked. Original URI =' . $_SERVER['REQUEST_URI']);
			goaway($a->get_baseurl() . $_SERVER['REQUEST_URI']);
		}

		logger('index.php: page not found: ' . $_SERVER['REQUEST_URI'] . ' ADDRESS: ' . $_SERVER['REMOTE_ADDR'] . ' QUERY: ' . $_SERVER['QUERY_STRING'], LOGGER_DEBUG);
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		$a->page['content'] = replace_macros($tpl, array(
			'$message' =>  t('Page not found.' )
		));
	}
}

/**
 * load current theme info
 */
$theme_info_file = "view/theme/".current_theme()."/theme.php";
if (file_exists($theme_info_file)){
	require_once($theme_info_file);
}


/* initialise content region */

if(! x($a->page,'content'))
	$a->page['content'] = '';

if(! $install)
	call_hooks('page_content_top',$a->page['content']);

/**
 * Call module functions
 */

if($a->module_loaded) {
	$a->page['page_title'] = $a->module;
	$placeholder = '';

	if(function_exists($a->module . '_init')) {
		call_hooks($a->module . '_mod_init', $placeholder);
		$func = $a->module . '_init';
		$func($a);
	}

	if(function_exists(str_replace('-','_',current_theme()) . '_init')) {
		$func = str_replace('-','_',current_theme()) . '_init';
		$func($a);
	}
//	elseif (x($a->theme_info,"extends") && file_exists("view/theme/".$a->theme_info["extends"]."/theme.php")) {
//		require_once("view/theme/".$a->theme_info["extends"]."/theme.php");
//		if(function_exists(str_replace('-','_',$a->theme_info["extends"]) . '_init')) {
//			$func = str_replace('-','_',$a->theme_info["extends"]) . '_init';
//			$func($a);
//		}
//	}

	if(($_SERVER['REQUEST_METHOD'] === 'POST') && (! $a->error)
		&& (function_exists($a->module . '_post'))
		&& (! x($_POST,'auth-params'))) {
		call_hooks($a->module . '_mod_post', $_POST);
		$func = $a->module . '_post';
		$func($a);
	}

	if((! $a->error) && (function_exists($a->module . '_afterpost'))) {
		call_hooks($a->module . '_mod_afterpost',$placeholder);
		$func = $a->module . '_afterpost';
		$func($a);
	}

	if((! $a->error) && (function_exists($a->module . '_content'))) {
		$arr = array('content' => $a->page['content']);
		call_hooks($a->module . '_mod_content', $arr);
		$a->page['content'] = $arr['content'];
		$func = $a->module . '_content';
		$arr = array('content' => $func($a));
		call_hooks($a->module . '_mod_aftercontent', $arr);
		$a->page['content'] .= $arr['content'];
	}

	if(function_exists(str_replace('-','_',current_theme()) . '_content_loaded')) {
		$func = str_replace('-','_',current_theme()) . '_content_loaded';
		$func($a);
	}

}


/*
 * Create the page head after setting the language
 * and getting any auth credentials
 *
 * Moved init_pagehead() and init_page_end() to after
 * all the module functions have executed so that all
 * theme choices made by the modules can take effect
 */

$a->init_pagehead();

/**
 * Build the page ending -- this is stuff that goes right before
 * the closing </body> tag
 */

$a->init_page_end();

// If you're just visiting, let javascript take you home

if(x($_SESSION,'visitor_home'))
	$homebase = $_SESSION['visitor_home'];
elseif(local_user())
	$homebase = $a->get_baseurl() . '/profile/' . $a->user['nickname'];

if(isset($homebase))
	$a->page['content'] .= '<script>var homebase="' . $homebase . '" ; </script>';

// now that we've been through the module content, see if the page reported
// a permission problem and if so, a 403 response would seem to be in order.

if(stristr( implode("",$_SESSION['sysmsg']), t('Permission denied'))) {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 ' . t('Permission denied.'));
}

/**
 *
 * Report anything which needs to be communicated in the notification area (before the main body)
 *
 */
	
/*if(x($_SESSION,'sysmsg')) {
	$a->page['content'] = "<div id=\"sysmsg\" class=\"error-message\">{$_SESSION['sysmsg']}</div>\r\n"
		. ((x($a->page,'content')) ? $a->page['content'] : '');
	$_SESSION['sysmsg']="";
	unset($_SESSION['sysmsg']);
}
if(x($_SESSION,'sysmsg_info')) {
	$a->page['content'] = "<div id=\"sysmsg_info\" class=\"info-message\">{$_SESSION['sysmsg_info']}</div>\r\n"
		. ((x($a->page,'content')) ? $a->page['content'] : '');
	$_SESSION['sysmsg_info']="";
	unset($_SESSION['sysmsg_info']);
}*/



call_hooks('page_end', $a->page['content']);


/**
 *
 * Add a place for the pause/resume Ajax indicator
 *
 */

$a->page['content'] .=  '<div id="pause"></div>';


/**
 *
 * Add the navigation (menu) template
 *
 */

if($a->module != 'install') {
	nav($a);
}

/**
 * Add a "toggle mobile" link if we're using a mobile device
 */

if($a->is_mobile || $a->is_tablet) {
	if(isset($_SESSION['show-mobile']) && !$_SESSION['show-mobile']) {
		$link = $a->get_baseurl() . '/toggle_mobile?address=' . curPageURL();
	}
	else {
		$link = $a->get_baseurl() . '/toggle_mobile?off=1&address=' . curPageURL();
	}
	$a->page['footer'] = replace_macros(get_markup_template("toggle_mobile_footer.tpl"), array(
	                     	'$toggle_link' => $link,
	                     	'$toggle_text' => t('toggle mobile')
    	                 ));
}

/**
 * Build the page - now that we have all the components
 */

if(!$a->theme['stylesheet'])
	$stylesheet = current_theme_url();
else
	$stylesheet = $a->theme['stylesheet'];
$a->page['htmlhead'] = replace_macros($a->page['htmlhead'], array('$stylesheet' => $stylesheet));

$page    = $a->page;
$profile = $a->profile;

header("Content-type: text/html; charset=utf-8");

$template = 'view/theme/' . current_theme() . '/' 
	. ((x($a->page,'template')) ? $a->page['template'] : 'default' ) . '.php';

if(file_exists($template))
	require_once($template);
else
	require_once(str_replace('theme/' . current_theme() . '/', '', $template));

session_write_close();
exit;
