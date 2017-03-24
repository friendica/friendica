<?php

use \Friendica\Core\Config;

function maintenance_content(App $a) {

	$reason = Config::get('system', 'maintenance_reason');

	if (substr(normalise_link($reason), 0, 7) == 'http://') {
		header("HTTP/1.1 307 Temporary Redirect");
		header("Location:".$reason);
		return;
	}

	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 600');

	return replace_macros(get_markup_template('maintenance.tpl'), array(
		'$sysdown' => t('System down for maintenance'),
		'$reason' => $reason
	));
}
