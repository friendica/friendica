<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if(!local_user())
		return;		
	
	$font_size = get_pconfig(local_user(), 'diabook-red', 'font_size' );
	$line_height = get_pconfig(local_user(), 'diabook-red', 'line_height' );
	$resolution = get_pconfig(local_user(), 'diabook-red', 'resolution' );
	
	return diabook_form($a,$font_size, $line_height,$resolution);
}

function theme_post(&$a){
	if(! local_user())
		return;
	
	if (isset($_POST['diabook-blue-settings-submit'])){
		set_pconfig(local_user(), 'diabook-red', 'font_size', $_POST['diabook-red_font_size']);
		set_pconfig(local_user(), 'diabook-red', 'line_height', $_POST['diabook-red_line_height']);
		set_pconfig(local_user(), 'diabook-red', 'resolution', $_POST['diabook-red_resolution']);	
	}
}


function theme_admin(&$a){
	$font_size = get_config('diabook-red', 'font_size' );
	$line_height = get_config('diabook-red', 'line_height' );
	$resolution = get_config('diabook-red', 'resolution' );
	
	return diabook_form($a,$font_size, $line_height,$resolution);
}

function theme_admin_post(&$a){
	if (isset($_POST['diabook-red-settings-submit'])){
		set_config('diabook-red', 'font_size', $_POST['diabook-red_font_size']);
		set_config('diabook-red', 'line_height', $_POST['diabook-red_line_height']);
		set_config('diabook-red', 'resolution', $_POST['diabook-red_resolution']);
	}
}


function diabook_form(&$a, $font_size, $line_height, $resolution){
	$line_heights = array(
		"1.3"=>"1.3",
		"---"=>"---",
		"1.5"=>"1.5",		
		"1.4"=>"1.4",
		"1.2"=>"1.2",
		"1.1"=>"1.1",
	);
	
	$font_sizes = array(
		'13'=>'13',
		"---"=>"---",
		"15"=>"15",
		'14'=>'14',
		'13.5'=>'13.5',		
		'12.5'=>'12.5',
		'12'=>'12',
		);
	$resolutions = array(
		'normal'=>'normal',
		'wide'=>'wide',		
		);
	
	
	
	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$font_size' => array('diabook-red_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('diabook-red_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$resolution' => array('diabook-red_resolution', t('Set resolution for middle column'), $resolution, '', $resolutions),	
	));
	return $o;
}
