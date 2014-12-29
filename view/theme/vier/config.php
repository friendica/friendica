<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if(!local_user())
		return;

	$style = get_pconfig(local_user(), 'vier', 'style');

	if ($style == "")
		$style = get_config('vier', 'style');

	return vier_form($a,$style);
}

function theme_post(&$a){
	if(! local_user())
		return;

	if (isset($_POST['vier-settings-submit'])){
		set_pconfig(local_user(), 'vier', 'style', $_POST['vier_style']);
	}
}


function theme_admin(&$a){
	$style = get_config('vier', 'style');
	return vier_form($a,$style);
}

function theme_admin_post(&$a){
	if (isset($_POST['vier-settings-submit'])){
		set_config('vier', 'style', $_POST['vier_style']);
	}
}


function vier_form(&$a, $style){
	$styles = array(
		"shadow"=>"Shadow",
		"flat"=>"Flat",
		"netcolour"=>"Coloured Networks",
		"breathe"=>"Breathe",
		"plus"=>"Plus",
		"dark"=>"Dark"
	);
	$t = get_markup_template("theme_settings.tpl" );
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(),
		'$title' => t("Theme settings"),
		'$style' => array('vier_style',t ('Set style'),$style,'',$styles),
	));
	return $o;
}
