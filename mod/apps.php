<?php
/**
 * @file mod/apps.php
 */
use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\L10n;

function apps_content()
{
	$privateaddons = Config::get('config', 'private_addons');
	if ($privateaddons === "1") {
		if (! local_user()) {
			info(L10n::t('You must be logged in to use addons. '));
			return;
		};
	}

	$title = L10n::t('Applications');

	$apps = Nav::getAppMenu();

	if (count($apps) == 0) {
		notice(L10n::t('No installed applications.') . EOL);
	}

	$tpl = Text::getMarkupTemplate('apps.tpl');
	return App::replaceMacros($tpl, [
		'$title' => $title,
		'$apps'  => $apps,
	]);
}
