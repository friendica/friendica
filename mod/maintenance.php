<?php
/**
 * @file mod/maintenance.php
 */
use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\L10n;

function maintenance_content(App $a)
{
	$reason = Config::get('system', 'maintenance_reason');

	if (substr(Text::normaliseLink($reason), 0, 7) == 'http://') {
		header("HTTP/1.1 307 Temporary Redirect");
		header("Location:".$reason);
		return;
	}

	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 600');

	return Text::replaceMacros(Text::getMarkupTemplate('maintenance.tpl'), [
		'$sysdown' => L10n::t('System down for maintenance'),
		'$reason' => $reason
	]);
}
