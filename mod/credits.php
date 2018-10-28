<?php
/**
 * @file mod/credits.php
 * Show a credits page for all the developers who helped with the project
 * (only contributors to the git repositories for friendica core and the
 * addons repository will be listed though ATM)
 */
use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\L10n;

function credits_content()
{
	/* fill the page with credits */
	$credits_string = file_get_contents('util/credits.txt');
	$names = explode("\n", htmlspecialchars($credits_string));
	$tpl = Text::getMarkupTemplate('credits.tpl');
	return Text::replaceMacros($tpl, [
		'$title'  => L10n::t('Credits'),
		'$thanks' => L10n::t('Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'),
		'$names'  => $names,
	]);
}
