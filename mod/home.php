<?php

if(! function_exists('home_init')) {
function home_init(App &$a) {

	$ret = array();
	call_hooks('home_init',$ret);

	if (local_user() && ($a->user['nickname'])) {
		goaway(App::get_baseurl()."/network");
	}

	if (strlen(get_config('system','singleuser'))) {
		goaway(App::get_baseurl()."/profile/" . get_config('system','singleuser'));
	}

}}


if(! function_exists('home_content')) {
function home_content(App &$a) {

	$o = '';

	if (x($_SESSION,'theme')) {
		unset($_SESSION['theme']);
	}
	if (x($_SESSION,'mobile-theme')) {
		unset($_SESSION['mobile-theme']);
	}

	/// @TODO No absolute path used, maybe risky (security)
	if (file_exists('home.html')) {
		if (file_exists('home.css')) {
			$a->page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="'.App::get_baseurl().'/home.css'.'" media="all" />';
		}

		$o .= file_get_contents('home.html');}

	else {
		$o .= '<h1>'.((x($a->config,'sitename')) ? sprintf(t("Welcome to %s"), $a->config['sitename']) : "").'</h1>';
	}


	$o .= login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);

	call_hooks("home_content",$o);

	return $o;


}}
