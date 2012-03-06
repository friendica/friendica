<?php

$a->hooks[] = array('plugin_settings', 'view/theme/quattro/theme.php', 'quattro_settings');
$a->hooks[] = array('plugin_settings_post', 'view/theme/quattro/theme.php', 'quattro_settings_post');


function quattro_settings(&$a, &$o){
	if(!local_user())
		return;		
	
	$align = get_pconfig(local_user(), 'quattro', 'align' );
	
	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$align' => array('quattro_align', t('Alignment'), $align, '', array('left'=>t('Left'), 'center'=>t('Center'))),
	));
}

function quattro_settings_post(&$a){
	if(! local_user())
		return;
	if (isset($_POST['quattro-settings-submit'])){
		set_pconfig(local_user(), 'quattro', 'align', $_POST['quattro_align']);
	}
	goaway($a->get_baseurl()."/settings/addon");
}


$quattro_align = get_pconfig(local_user(), 'quattro', 'align' );

if(local_user() && $quattro_align=="center"){
	
	$a->page['htmlhead'].="
	<style>
		html { width: 100%; margin:0px; padding:0px; }
		body {
			margin: 50px auto;
			width: 900px;
		}
	</style>
	";
	
}
