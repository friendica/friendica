<?php

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Module\Login;

if(! function_exists('home_init')) {
function home_init(App $a) {

	$ret = [];
	Addon::callHooks('home_init',$ret);

	if (local_user() && ($a->user['nickname'])) {
		goaway(System::baseUrl()."/network");
	}

	if (strlen(Config::get('system','singleuser'))) {
		goaway(System::baseUrl()."/profile/" . Config::get('system','singleuser'));
	}

}}

if(! function_exists('home_content')) {
function home_content(App $a) {

	if (x($_SESSION,'theme')) {
		unset($_SESSION['theme']);
	}
	if (x($_SESSION,'mobile-theme')) {
		unset($_SESSION['mobile-theme']);
	}

	$customhome = false;
	$defaultheader = '<h1>'.((x($a->config,'sitename')) ? sprintf(t("Welcome to %s"), $a->config['sitename']) : "").'</h1>';

	$homefilepath = $a->basepath . "/home.html";
	$cssfilepath = $a->basepath . "/home.css";
	if (file_exists($homefilepath)) {
		$customhome = $homefilepath;
		if (file_exists($cssfilepath)) {
			$a->page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="'.System::baseUrl().'/home.css'.'" media="all" />';
		}
	} 

	$login = Login::form($a->query_string, $a->config['register_policy'] == REGISTER_CLOSED ? 0 : 1);

	$content = '';
	Addon::callHooks("home_content",$content);


	$tpl = get_markup_template('home.tpl');
	return replace_macros($tpl, [
		'$defaultheader' => $defaultheader,
		'$customhome' => $customhome,
		'$login' => $login,
		'$content' => $content
	]);

	return $o;

}}
