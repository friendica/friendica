<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if(!local_user())
		return;		
	
	$align = get_pconfig(local_user(), 'quattro', 'align' );
	$color = get_pconfig(local_user(), 'quattro', 'color' );
	
	return quattro_form($a,$align, $color);
}

function theme_post(&$a){
	if(! local_user())
		return;
	
	if (isset($_POST['quattro-settings-submit'])){
		set_pconfig(local_user(), 'quattro', 'align', $_POST['quattro_align']);
		set_pconfig(local_user(), 'quattro', 'color', $_POST['quattro_color']);
	}
}


function theme_admin(&$a){
	$align = get_config('quattro', 'align' );
	$color = get_config('quattro', 'color' );
	
	return quattro_form($a,$align, $color);
}

function theme_admin_post(&$a){
	if (isset($_POST['quattro-settings-submit'])){
		set_config('quattro', 'align', $_POST['quattro_align']);
		set_config('quattro', 'color', $_POST['quattro_color']);
	}
}


function quattro_form(&$a, $align, $color){
	$colors = array(
		"dark"=>"Quattro", 
		"green"=>"Green"
	);
	
	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$align' => array('quattro_align', t('Alignment'), $align, '', array('left'=>t('Left'), 'center'=>t('Center'))),
		'$color' => array('quattro_color', t('Color scheme'), $color, '', $colors),
	));
	return $o;
}
