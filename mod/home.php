<?php
/**
 * @file mod/home.php
 */
use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Module\Login;

if(! function_exists('home_init')) {
function home_init(App $a) {

	$ret = [];
	Addon::callHooks('home_init',$ret);

	if (Session::user()->isLocal() && ($a->user['nickname'])) {
		$a->internalRedirect('network');
	}

	if (strlen(Config::get('system','singleuser'))) {
		$a->internalRedirect('profile/' . Config::get('system','singleuser'));
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
	$defaultheader = '<h1>' . (Config::get('config', 'sitename') ? L10n::t('Welcome to %s', Config::get('config', 'sitename')) : '') . '</h1>';

	$homefilepath = $a->getBasePath() . "/home.html";
	$cssfilepath = $a->getBasePath() . "/home.css";
	if (file_exists($homefilepath)) {
		$customhome = $homefilepath;
		if (file_exists($cssfilepath)) {
			$a->page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="'.System::baseUrl().'/home.css'.'" media="all" />';
		}
	}

	$login = Login::form($a->query_string, intval(Config::get('config', 'register_policy')) === REGISTER_CLOSED ? 0 : 1);

	$content = '';
	Addon::callHooks("home_content",$content);


	$tpl = Renderer::getMarkupTemplate('home.tpl');
	return Renderer::replaceMacros($tpl, [
		'$defaultheader' => $defaultheader,
		'$customhome' => $customhome,
		'$login' => $login,
		'$content' => $content
	]);

	return $o;

}}
