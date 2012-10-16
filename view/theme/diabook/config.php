<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if(!local_user())
		return;

	$font_size = get_pconfig(local_user(), 'diabook', 'font_size' );
	$line_height = get_pconfig(local_user(), 'diabook', 'line_height' );
	$resolution = get_pconfig(local_user(), 'diabook', 'resolution' );
	$color = get_pconfig(local_user(), 'diabook', 'color' );
	$TSearchTerm = get_pconfig(local_user(), 'diabook', 'TSearchTerm' );
	$ELZoom = get_pconfig(local_user(), 'diabook', 'ELZoom' );
	$ELPosX = get_pconfig(local_user(), 'diabook', 'ELPosX' );
	$ELPosY = get_pconfig(local_user(), 'diabook', 'ELPosY' );
	$close_pages = get_pconfig(local_user(), 'diabook', 'close_pages' );
	$close_mapquery = get_pconfig(local_user(), 'diabook', 'close_mapquery' );
	$close_profiles = get_pconfig(local_user(), 'diabook', 'close_profiles' );
	$close_helpers = get_pconfig(local_user(), 'diabook', 'close_helpers' );
	$close_services = get_pconfig(local_user(), 'diabook', 'close_services' );
	$close_friends = get_pconfig(local_user(), 'diabook', 'close_friends' );
	$close_twitter = get_pconfig(local_user(), 'diabook', 'close_twitter' );
	$close_lastusers = get_pconfig(local_user(), 'diabook', 'close_lastusers' );
	$close_lastphotos = get_pconfig(local_user(), 'diabook', 'close_lastphotos' );
	$close_lastlikes = get_pconfig(local_user(), 'diabook', 'close_lastlikes' );


	return diabook_form($a,$font_size, $line_height, $resolution, $color, $TSearchTerm, $ELZoom, $ELPosX, $ELPosY, $close_pages, $close_mapquery, $close_profiles, $close_helpers, $close_services, $close_friends, $close_twitter, $close_lastusers, $close_lastphotos, $close_lastlikes);
}

function theme_post(&$a){
	if(! local_user())
		return;

	if (isset($_POST['diabook-settings-submit'])){
		set_pconfig(local_user(), 'diabook', 'font_size', $_POST['diabook_font_size']);
		set_pconfig(local_user(), 'diabook', 'line_height', $_POST['diabook_line_height']);
		set_pconfig(local_user(), 'diabook', 'resolution', $_POST['diabook_resolution']);
		set_pconfig(local_user(), 'diabook', 'color', $_POST['diabook_color']);
		set_pconfig(local_user(), 'diabook', 'TSearchTerm', $_POST['diabook_TSearchTerm']);
		set_pconfig(local_user(), 'diabook', 'ELZoom', $_POST['diabook_ELZoom']);
		set_pconfig(local_user(), 'diabook', 'ELPosX', $_POST['diabook_ELPosX']);
		set_pconfig(local_user(), 'diabook', 'ELPosY', $_POST['diabook_ELPosY']);
		set_pconfig(local_user(), 'diabook', 'ELPosY', $_POST['diabook_ELPosY']);
		set_pconfig(local_user(), 'diabook', 'close_pages', $_POST['diabook_close_pages']);
		set_pconfig(local_user(), 'diabook', 'close_mapquery', $_POST['diabook_close_mapquery']);
		set_pconfig(local_user(), 'diabook', 'close_profiles', $_POST['diabook_close_profiles']);
		set_pconfig(local_user(), 'diabook', 'close_helpers', $_POST['diabook_close_helpers']);
		set_pconfig(local_user(), 'diabook', 'close_services', $_POST['diabook_close_services']);
		set_pconfig(local_user(), 'diabook', 'close_friends', $_POST['diabook_close_friends']);
		set_pconfig(local_user(), 'diabook', 'close_twitter', $_POST['diabook_close_twitter']);
		set_pconfig(local_user(), 'diabook', 'close_lastusers', $_POST['diabook_close_lastusers']);
		set_pconfig(local_user(), 'diabook', 'close_lastphotos', $_POST['diabook_close_lastphotos']);
		set_pconfig(local_user(), 'diabook', 'close_lastlikes', $_POST['diabook_close_lastlikes']);

	}
}


function theme_admin(&$a){
	$font_size = get_config('diabook', 'font_size' );
	$line_height = get_config('diabook', 'line_height' );
	$resolution = get_config('diabook', 'resolution' );
	$color = get_config('diabook', 'color' );
	$TSearchTerm = get_config('diabook', 'TSearchTerm' );
	$ELZoom = get_config('diabook', 'ELZoom' );
	$ELPosX = get_config('diabook', 'ELPosX' );
	$ELPosY = get_config('diabook', 'ELPosY' );
	$close_pages = get_config('diabook', 'close_pages' );
	$close_mapquery = get_config('diabook', 'close_mapquery' );
	$close_profiles = get_config('diabook', 'close_profiles' );
	$close_helpers = get_config('diabook', 'close_helpers' );
	$close_services = get_config('diabook', 'close_services' );
	$close_friends = get_config('diabook', 'close_friends' );
	$close_twitter = get_config('diabook', 'close_twitter' );
	$close_lastusers = get_config('diabook', 'close_lastusers' );
	$close_lastphotos = get_config('diabook', 'close_lastphotos' );
	$close_lastlikes = get_config('diabook', 'close_lastlikes' );

	return diabook_form($a,$font_size, $line_height, $resolution, $color, $TSearchTerm, $ELZoom, $ELPosX, $ELPosY, $close_pages, $close_mapquery, $close_profiles, $close_helpers, $close_services, $close_friends, $close_twitter, $close_lastusers, $close_lastphotos, $close_lastlikes);
}

function theme_admin_post(&$a){
	if (isset($_POST['diabook-settings-submit'])){
		set_config('diabook', 'font_size', $_POST['diabook_font_size']);
		set_config('diabook', 'line_height', $_POST['diabook_line_height']);
		set_config('diabook', 'resolution', $_POST['diabook_resolution']);
		set_config('diabook', 'color', $_POST['diabook_color']);
		set_config('diabook', 'TSearchTerm', $_POST['diabook_TSearchTerm']);
		set_config('diabook', 'ELZoom', $_POST['diabook_ELZoom']);
		set_config('diabook', 'ELPosX', $_POST['diabook_ELPosX']);
		set_config('diabook', 'close_pages', $_POST['diabook_close_pages']);
		set_config('diabook', 'close_mapquery', $_POST['diabook_close_mapquery']);
		set_config('diabook', 'close_profiles', $_POST['diabook_close_profiles']);
		set_config('diabook', 'close_helpers', $_POST['diabook_close_helpers']);
		set_config('diabook', 'close_services', $_POST['diabook_close_services']);
		set_config('diabook', 'close_friends', $_POST['diabook_close_friends']);
		set_config('diabook', 'close_twitter', $_POST['diabook_close_twitter']);
		set_config('diabook', 'close_lastusers', $_POST['diabook_close_lastusers']);
		set_config('diabook', 'close_lastphotos', $_POST['diabook_close_lastphotos']);
		set_config('diabook', 'close_lastlikes', $_POST['diabook_close_lastlikes']);

	}
}


function diabook_form(&$a, $font_size, $line_height, $resolution, $color, $TSearchTerm, $ELZoom, $ELPosX, $ELPosY, $close_pages, $close_mapquery, $close_profiles, $close_helpers, $close_services, $close_friends, $close_twitter, $close_lastusers, $close_lastphotos, $close_lastlikes){
	$line_heights = array(
		"1.3"=>"1.3",
		"---"=>"---",
		"1.6"=>"1.6",
		"1.5"=>"1.5",
		"1.4"=>"1.4",
		"1.2"=>"1.2",
		"1.1"=>"1.1",
	);

	$font_sizes = array(
		'14'=>'14',
		"---"=>"---",
		"16"=>"16",
		"15"=>"15",
		'13.5'=>'13.5',
		'13'=>'13',
		'12.5'=>'12.5',
		'12'=>'12',
		);
	$resolutions = array(
		'normal'=>'normal',
		'wide'=>'wide',
		);
	$colors = array(
		'diabook'=>'diabook',
		'aerith'=>'aerith',
		'blue'=>'blue',
		'green'=>'green',
		'pink'=>'pink',
		'red'=>'red',
		'dark'=>'dark',
		);

	$close_or_not = array('1'=>t("don't show"),	'0'=>t("show"),);



	$t = file_get_contents( dirname(__file__). "/theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$font_size' => array('diabook_font_size', t('Set font-size for posts and comments'), $font_size, '', $font_sizes),
		'$line_height' => array('diabook_line_height', t('Set line-height for posts and comments'), $line_height, '', $line_heights),
		'$resolution' => array('diabook_resolution', t('Set resolution for middle column'), $resolution, '', $resolutions),
		'$color' => array('diabook_color', t('Set color scheme'), $color, '', $colors),
		'$TSearchTerm' => array('diabook_TSearchTerm', t('Set twitter search term'), $TSearchTerm, '', $TSearchTerm),
		'$ELZoom' => array('diabook_ELZoom', t('Set zoomfactor for Earth Layer'), $ELZoom, '', $ELZoom),
		'$ELPosX' => array('diabook_ELPosX', t('Set longitude (X) for Earth Layers'), $ELPosX, '', $ELPosX),
		'$ELPosY' => array('diabook_ELPosY', t('Set latitude (Y) for Earth Layers'), $ELPosY, '', $ELPosY),
		'$close_pages' => array('diabook_close_pages', t('Community Pages'), $close_pages, '', $close_or_not),
		'$close_mapquery' => array('diabook_close_mapquery', t('Earth Layers'), $close_mapquery, '', $close_or_not),
		'$close_profiles' => array('diabook_close_profiles', t('Community Profiles'), $close_profiles, '', $close_or_not),
		'$close_helpers' => array('diabook_close_helpers', t('Help or @NewHere ?'), $close_helpers, '', $close_or_not),
		'$close_services' => array('diabook_close_services', t('Connect Services'), $close_services, '', $close_or_not),
		'$close_friends' => array('diabook_close_friends', t('Find Friends'), $close_friends, '', $close_or_not),
		'$close_twitter' => array('diabook_close_twitter', t('Last tweets'), $close_twitter, '', $close_or_not),
		'$close_lastusers' => array('diabook_close_lastusers', t('Last users'), $close_lastusers, '', $close_or_not),
		'$close_lastphotos' => array('diabook_close_lastphotos', t('Last photos'), $close_lastphotos, '', $close_or_not),
		'$close_lastlikes' => array('diabook_close_lastlikes', t('Last likes'), $close_lastlikes, '', $close_or_not),
	));
	return $o;
}
