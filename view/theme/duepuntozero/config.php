<?php
/**
 * Theme settings
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;

function theme_content(App $a)
{
	if (!Session::user()->isLocal()) {
		return;
	}

	$colorset = PConfig::get(Session::user()->getUid(), 'duepuntozero', 'colorset');
	$user = true;

	return clean_form($a, $colorset, $user);
}

function theme_post(App $a)
{
	if (!Session::user()->isLocal()) {
		return;
	}

	if (isset($_POST['duepuntozero-settings-submit'])) {
		PConfig::set(Session::user()->getUid(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

function theme_admin(App $a)
{
	$colorset = Config::get('duepuntozero', 'colorset');
	$user = false;

	return clean_form($a, $colorset, $user);
}

function theme_admin_post(App $a)
{
	if (isset($_POST['duepuntozero-settings-submit'])) {
		Config::set('duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

/// @TODO $a is no longer used
function clean_form(App $a, &$colorset, $user)
{
	$colorset = [
		'default'     => L10n::t('default'),
		'greenzero'   => L10n::t('greenzero'),
		'purplezero'  => L10n::t('purplezero'),
		'easterbunny' => L10n::t('easterbunny'),
		'darkzero'    => L10n::t('darkzero'),
		'comix'       => L10n::t('comix'),
		'slackr'      => L10n::t('slackr'),
	];

	if ($user) {
		$color = PConfig::get(Session::user()->getUid(), 'duepuntozero', 'colorset');
	} else {
		$color = Config::get('duepuntozero', 'colorset');
	}

	$t = Renderer::getMarkupTemplate("theme_settings.tpl");
	$o = Renderer::replaceMacros($t, [
		'$submit'   => L10n::t('Submit'),
		'$baseurl'  => $a->getBaseURL(),
		'$title'    => L10n::t("Theme settings"),
		'$colorset' => ['duepuntozero_colorset', L10n::t('Variations'), $color, '', $colorset],
	]);

	return $o;
}
