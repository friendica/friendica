<?php
/**
 * @file mod/apps.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;

function apps_content(App $a)
{
	$privateaddons = Config::get('config', 'private_addons');
	if ($privateaddons === "1") {
		if (! local_user()) {
			info(L10n::t('You must be logged in to use addons. '));
			return;
		};
	}

	$title = L10n::t('Applications');

	if (count($a->apps) == 0) {
		notice(L10n::t('No installed applications.') . EOL);
	}

	$tpl = get_markup_template('apps.tpl');
	return replace_macros($tpl, [
		'$title' => $title,
		'$apps' => $a->apps,
	]);
}
