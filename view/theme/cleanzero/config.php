<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if(!local_user())
		return;		
	
	$resize = get_pconfig(local_user(), 'cleanzero', 'resize' );
	$color = get_pconfig(local_user(), 'cleanzero', 'color' );
       $font_size = get_pconfig(local_user(), 'cleanzero', 'font_size' );
	$theme_width= get_pconfig(local_user(), 'cleanzero', 'theme_width' );
	
	return cleanzero_form($a,$color,$font_size,$resize,$theme_width);
}

function theme_post(&$a){
	if(! local_user())
		return;
	
	if (isset($_POST['cleanzero-settings-submit'])){
		set_pconfig(local_user(), 'cleanzero', 'resize', $_POST['cleanzero_resize']);	
		set_pconfig(local_user(), 'cleanzero', 'color', $_POST['cleanzero_color']);
		set_pconfig(local_user(), 'cleanzero', 'font_size', $_POST['cleanzero_font_size']);
		set_pconfig(local_user(), 'cleanzero', 'theme_width', $_POST['cleanzero_theme_width']);
	}
}


function theme_admin(&$a){
	$resize = get_config('cleanzero', 'resize' );
	$color = get_config('cleanzero', 'color' );
	$font_size = get_config('cleanzero', 'font_size' );
	$theme_width= get_config('cleanzero', 'theme_width' );
	return cleanzero_form($a,$color,$font_size,$resize,$theme_width);
}

function theme_admin_post(&$a){
	if (isset($_POST['cleanzero-settings-submit'])){
		set_config('cleanzero', 'resize', $_POST['cleanzero_resize']);
		set_config('cleanzero', 'color', $_POST['cleanzero_color']);
		set_config('cleanzero', 'font_size', $_POST['cleanzero_font_size']);
		set_config('cleanzero', 'theme_width', $_POST['cleanzero_theme_width']);

	}
}


function cleanzero_form(&$a, $color,$font_size,$resize,$theme_width){
	$colors = array(
		"cleanzero"=>"cleanzero", 
		"cleanzero-green"=>"green",
		"cleanzero-purple"=>"purple"
	);
	$font_sizes = array(
		'12'=>'12',
		"---"=>"---",
		"16"=>"16",		
		"14"=>"14",
		'10'=>'10',
		);
	$resizes = array(
		"0"=>"0 (no resizing)",
		"600"=>"1 (600px)",
		"300"=>"2 (300px)",
		"250"=>"3 (250px)",
		"150"=>"4 (150px)",
	       );
	$theme_widths =array (
		"standard"=>"standard",
		"narrow"=>"narrow",
		"wide"=>"wide",
		);

	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$resize' => array('cleanzero_resize',t ('Set resize level for images in posts and comments (width and height)'),$resize,'',$resizes),
		'$font_size' => array('cleanzero_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$theme_width' => array('cleanzero_theme_width', t('Set theme width'), $theme_width, '', $theme_widths),
		'$color' => array('cleanzero_color', t('Color scheme'), $color, '', $colors),
	));
	return $o;
}
