<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\System;

require_once 'view/theme/frio/php/Image.php';

function theme_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		PConfig::set(local_user(), 'frio', 'scheme',           $_POST['frio_scheme']           ?? '');
		PConfig::set(local_user(), 'frio', 'nav_bg',           $_POST['frio_nav_bg']           ?? '');
		PConfig::set(local_user(), 'frio', 'nav_icon_color',   $_POST['frio_nav_icon_color']   ?? '');
		PConfig::set(local_user(), 'frio', 'link_color',       $_POST['frio_link_color']       ?? '');
		PConfig::set(local_user(), 'frio', 'background_color', $_POST['frio_background_color'] ?? '');
		PConfig::set(local_user(), 'frio', 'contentbg_transp', $_POST['frio_contentbg_transp'] ?? '');
		PConfig::set(local_user(), 'frio', 'background_image', $_POST['frio_background_image'] ?? '');
		PConfig::set(local_user(), 'frio', 'bg_image_option',  $_POST['frio_bg_image_option']  ?? '');
		PConfig::set(local_user(), 'frio', 'css_modified',     time());
	}
}

function theme_admin_post(App $a)
{
	if (!is_site_admin()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		Config::set('frio', 'scheme',           $_POST['frio_scheme']           ?? '');
		Config::set('frio', 'nav_bg',           $_POST['frio_nav_bg']           ?? '');
		Config::set('frio', 'nav_icon_color',   $_POST['frio_nav_icon_color']   ?? '');
		Config::set('frio', 'link_color',       $_POST['frio_link_color']       ?? '');
		Config::set('frio', 'background_color', $_POST['frio_background_color'] ?? '');
		Config::set('frio', 'contentbg_transp', $_POST['frio_contentbg_transp'] ?? '');
		Config::set('frio', 'background_image', $_POST['frio_background_image'] ?? '');
		Config::set('frio', 'bg_image_option',  $_POST['frio_bg_image_option']  ?? '');
		Config::set('frio', 'login_bg_image',   $_POST['frio_login_bg_image']   ?? '');
		Config::set('frio', 'login_bg_color',   $_POST['frio_login_bg_color']   ?? '');
		Config::set('frio', 'css_modified',     time());
	}
}

function theme_content(App $a)
{
	if (!local_user()) {
		return;
	}
	$arr = [];

	$arr['scheme']           = PConfig::get(local_user(), 'frio', 'scheme', PConfig::get(local_user(), 'frio', 'schema'));
	$arr['share_string']     = '';
	$arr['nav_bg']           = PConfig::get(local_user(), 'frio', 'nav_bg');
	$arr['nav_icon_color']   = PConfig::get(local_user(), 'frio', 'nav_icon_color');
	$arr['link_color']       = PConfig::get(local_user(), 'frio', 'link_color');
	$arr['background_color'] = PConfig::get(local_user(), 'frio', 'background_color');
	$arr['contentbg_transp'] = PConfig::get(local_user(), 'frio', 'contentbg_transp');
	$arr['background_image'] = PConfig::get(local_user(), 'frio', 'background_image');
	$arr['bg_image_option']  = PConfig::get(local_user(), 'frio', 'bg_image_option');

	return frio_form($arr);
}

function theme_admin(App $a)
{
	if (!local_user()) {
		return;
	}
	$arr = [];

	$arr['scheme']           = Config::get('frio', 'scheme', Config::get('frio', 'schema'));
	$arr['share_string']     = '';
	$arr['nav_bg']           = Config::get('frio', 'nav_bg');
	$arr['nav_icon_color']   = Config::get('frio', 'nav_icon_color');
	$arr['link_color']       = Config::get('frio', 'link_color');
	$arr['background_color'] = Config::get('frio', 'background_color');
	$arr['contentbg_transp'] = Config::get('frio', 'contentbg_transp');
	$arr['background_image'] = Config::get('frio', 'background_image');
	$arr['bg_image_option']  = Config::get('frio', 'bg_image_option');
	$arr['login_bg_image']   = Config::get('frio', 'login_bg_image');
	$arr['login_bg_color']   = Config::get('frio', 'login_bg_color');

	return frio_form($arr);
}

function frio_form($arr)
{
	require_once 'view/theme/frio/php/scheme.php';

	$scheme_info = get_scheme_info($arr['scheme']);
	$disable = $scheme_info['overwrites'];
	if (!is_array($disable)) {
		$disable = [];
	}

	$scheme_choices = [];
	$scheme_choices['---'] = L10n::t('Custom');
	$files = glob('view/theme/frio/scheme/*.php');
	if ($files) {
		foreach ($files as $file) {
			$f = basename($file, '.php');
			if ($f != 'default') {
				$scheme_name = ucfirst($f);
				$scheme_choices[$f] = $scheme_name;
			}
		}
	}

	$background_image_help = '<strong>' . L10n::t('Note') . ': </strong>' . L10n::t('Check image permissions if all users are allowed to see the image');

	$t = Renderer::getMarkupTemplate('theme_settings.tpl');
	$ctx = [
		'$submit'           => L10n::t('Submit'),
		'$title'            => L10n::t('Theme settings'),
		'$scheme'           => ['frio_scheme', L10n::t('Select color scheme'), $arr['scheme'], '', $scheme_choices],
		'$share_string'     => ['frio_share_string', L10n::t('Copy or paste schemestring'), $arr['share_string'], L10n::t('You can copy this string to share your theme with others. Pasting here applies the schemestring'), false, false],
		'$nav_bg'           => array_key_exists('nav_bg', $disable) ? '' : ['frio_nav_bg', L10n::t('Navigation bar background color'), $arr['nav_bg'], '', false],
		'$nav_icon_color'   => array_key_exists('nav_icon_color', $disable) ? '' : ['frio_nav_icon_color', L10n::t('Navigation bar icon color '), $arr['nav_icon_color'], '', false],
		'$link_color'       => array_key_exists('link_color', $disable) ? '' : ['frio_link_color', L10n::t('Link color'), $arr['link_color'], '', false],
		'$background_color' => array_key_exists('background_color', $disable) ? '' : ['frio_background_color', L10n::t('Set the background color'), $arr['background_color'], '', false],
		'$contentbg_transp' => array_key_exists('contentbg_transp', $disable) ? '' : ['frio_contentbg_transp', L10n::t('Content background opacity'), defaults($arr, 'contentbg_transp', 100), ''],
		'$background_image' => array_key_exists('background_image', $disable) ? '' : ['frio_background_image', L10n::t('Set the background image'), $arr['background_image'], $background_image_help, false],
		'$bg_image_options_title' => L10n::t('Background image style'),
		'$bg_image_options' => Image::get_options($arr),
	];

	if (array_key_exists('login_bg_image', $arr) && !array_key_exists('login_bg_image', $disable)) {
		$ctx['$login_bg_image'] = ['frio_login_bg_image', L10n::t('Login page background image'), $arr['login_bg_image'], $background_image_help, false];
	}

	if (array_key_exists('login_bg_color', $arr) && !array_key_exists('login_bg_color', $disable)) {
		$ctx['$login_bg_color'] = ['frio_login_bg_color', L10n::t('Login page background color'), $arr['login_bg_color'], L10n::t('Leave background image and color empty for theme defaults'), false];
	}

	$o = Renderer::replaceMacros($t, $ctx);

	return $o;
}
