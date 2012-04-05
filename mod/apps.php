<?php

function apps_content(&$a) {
	$title = t('Applications');

	if(count($a->apps)==0)
		notice( t('No installed applications.') . EOL);


	$tpl = get_markup_template("apps.tpl");
	return replace_macros($tpl, array(
		'$title' => $title,
		'$apps' => $a->apps,
	));

	

}
