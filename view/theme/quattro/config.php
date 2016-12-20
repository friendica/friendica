<?php
/**
 * Theme settings
 */



function theme_content(App &$a){
	if (!local_user()) {
		return;
	}

	$align = get_pconfig(local_user(), 'quattro', 'align' );
	$color = get_pconfig(local_user(), 'quattro', 'color' );
	$tfs = get_pconfig(local_user(),"quattro","tfs");
	$pfs = get_pconfig(local_user(),"quattro","pfs");

	return quattro_form($a,$align, $color, $tfs, $pfs);
}

function theme_post(App &$a){
	if (! local_user()) {
		return;
	}

	if (isset($_POST['quattro-settings-submit'])){
		set_pconfig(local_user(), 'quattro', 'align', $_POST['quattro_align']);
		set_pconfig(local_user(), 'quattro', 'color', $_POST['quattro_color']);
		set_pconfig(local_user(), 'quattro', 'tfs', $_POST['quattro_tfs']);
		set_pconfig(local_user(), 'quattro', 'pfs', $_POST['quattro_pfs']);
	}
}


function theme_admin(App &$a){
	$align = get_config('quattro', 'align' );
	$color = get_config('quattro', 'color' );
    $tfs = get_config("quattro","tfs");
    $pfs = get_config("quattro","pfs");    

	return quattro_form($a,$align, $color, $tfs, $pfs);
}

function theme_admin_post(App &$a){
	if (isset($_POST['quattro-settings-submit'])){
		set_config('quattro', 'align', $_POST['quattro_align']);
		set_config('quattro', 'color', $_POST['quattro_color']);
        set_config('quattro', 'tfs', $_POST['quattro_tfs']);
		set_config('quattro', 'pfs', $_POST['quattro_pfs']);
	}
}

/// @TODO $a is no longer used here
function quattro_form(&$a, $align, $color, $tfs, $pfs){
	$colors = array(
		"dark"=>"Quattro", 
		"lilac"=>"Lilac", 
		"green"=>"Green"
	);
    
    if ($tfs===false) $tfs="20";
    if ($pfs===false) $pfs="12";
    
	$t = get_markup_template("theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => App::get_baseurl(),
		'$title' => t("Theme settings"),
		'$align' => array('quattro_align', t('Alignment'), $align, '', array('left'=>t('Left'), 'center'=>t('Center'))),
		'$color' => array('quattro_color', t('Color scheme'), $color, '', $colors),
        '$pfs' => array('quattro_pfs', t('Posts font size'), $pfs),
        '$tfs' => array('quattro_tfs',t('Textareas font size'), $tfs),
	));
	return $o;
}
