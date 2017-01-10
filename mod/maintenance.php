<?php

function maintenance_content(App &$a) {
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 600');

	return replace_macros(get_markup_template('maintenance.tpl'), array(
		'$sysdown' => t('System down for maintenance')
	));
}
