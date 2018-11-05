<?php
/**
 * @file mod/apps.php
 */
use Friendica\Content\Nav;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;

function apps_content()
{
	$privateaddons = Config::get('config', 'private_addons');
	if ($privateaddons === "1") {
		if (!Session::user()->isLocal()) {
			info(L10n::t('You must be logged in to use addons. '));
			return;
		};
	}

	$title = L10n::t('Applications');

	$apps = Nav::getAppMenu();

	if (count($apps) == 0) {
		notice(L10n::t('No installed applications.') . EOL);
	}

	$tpl = Renderer::getMarkupTemplate('apps.tpl');
	return Renderer::replaceMacros($tpl, [
		'$title' => $title,
		'$apps'  => $apps,
	]);
}
