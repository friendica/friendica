<?php

function maintenance_content(&$a) {
	return replace_macros(get_markup_template('maintenance.tpl'), array(
		'$sysdown' => t('System down for maintenance')
	));
}
