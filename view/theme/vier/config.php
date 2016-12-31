<?php
/**
 * Theme settings
 */



function theme_content(&$a){
	if (!local_user()) {
		return;
	}

	if (!function_exists('get_vier_config')) {
		return;
	}

	$style = get_pconfig(local_user(), 'vier', 'style');

	if ($style == "") {
		$style = get_config('vier', 'style');
	}

	if ($style == "") {
		$style = "plus";
	}

	$show_pages = get_vier_config('show_pages', true);
	$show_profiles = get_vier_config('show_profiles', true);
	$show_helpers = get_vier_config('show_helpers', true);
	$show_services = get_vier_config('show_services', true);
	$show_friends = get_vier_config('show_friends', true);
	$show_lastusers = get_vier_config('show_lastusers', true);

	return vier_form($a,$style, $show_pages, $show_profiles, $show_helpers,
			$show_services, $show_friends, $show_lastusers);
}

function theme_post(&$a){
	if (! local_user()) {
		return;
	}

	if (isset($_POST['vier-settings-submit'])){
		set_pconfig(local_user(), 'vier', 'style', $_POST['vier_style']);
		set_pconfig(local_user(), 'vier', 'show_pages', $_POST['vier_show_pages']);
		set_pconfig(local_user(), 'vier', 'show_profiles', $_POST['vier_show_profiles']);
		set_pconfig(local_user(), 'vier', 'show_helpers', $_POST['vier_show_helpers']);
		set_pconfig(local_user(), 'vier', 'show_services', $_POST['vier_show_services']);
		set_pconfig(local_user(), 'vier', 'show_friends', $_POST['vier_show_friends']);
		set_pconfig(local_user(), 'vier', 'show_lastusers', $_POST['vier_show_lastusers']);
	}
}


function theme_admin(&$a){

	if (!function_exists('get_vier_config'))
		return;

	$style = get_config('vier', 'style');

	$helperlist = get_config('vier', 'helperlist');

	if ($helperlist == "")
		$helperlist = "https://helpers.pyxis.uberspace.de/profile/helpers";

	$t = get_markup_template("theme_admin_settings.tpl");
	$o .= replace_macros($t, array(
		'$helperlist' => array('vier_helperlist', t('Comma separated list of helper forums'), $helperlist, '', ''),
		));

	$show_pages = get_vier_config('show_pages', true, true);
	$show_profiles = get_vier_config('show_profiles', true, true);
	$show_helpers = get_vier_config('show_helpers', true, true);
	$show_services = get_vier_config('show_services', true, true);
	$show_friends = get_vier_config('show_friends', true, true);
	$show_lastusers = get_vier_config('show_lastusers', true, true);
	$o .= vier_form($a,$style, $show_pages, $show_profiles, $show_helpers, $show_services,
			$show_friends, $show_lastusers);

	return $o;
}

function theme_admin_post(&$a){
	if (isset($_POST['vier-settings-submit'])){
		set_config('vier', 'style', $_POST['vier_style']);
		set_config('vier', 'show_pages', $_POST['vier_show_pages']);
		set_config('vier', 'show_profiles', $_POST['vier_show_profiles']);
		set_config('vier', 'show_helpers', $_POST['vier_show_helpers']);
		set_config('vier', 'show_services', $_POST['vier_show_services']);
		set_config('vier', 'show_friends', $_POST['vier_show_friends']);
		set_config('vier', 'show_lastusers', $_POST['vier_show_lastusers']);
		set_config('vier', 'helperlist', $_POST['vier_helperlist']);
	}
}

/// @TODO $a is no longer used
function vier_form(&$a, $style, $show_pages, $show_profiles, $show_helpers, $show_services, $show_friends, $show_lastusers){
	$styles = array(
		"plus"=>"Plus",
		"breathe"=>"Breathe",
		"dark"=>"Dark",
		"shadow"=>"Shadow",
		"netcolour"=>"Coloured Networks",
		"flat"=>"Flat"
	);

	$show_or_not = array('0'=>t("don't show"),     '1'=>t("show"),);

	$t = get_markup_template("theme_settings.tpl");
	$o .= replace_macros($t, array(
		'$submit' => t('Submit'),
		'$baseurl' => App::get_baseurl(),
		'$title' => t("Theme settings"),
		'$style' => array('vier_style',t ('Set style'),$style,'',$styles),
		'$show_pages' => array('vier_show_pages', t('Community Pages'), $show_pages, '', $show_or_not),
		'$show_profiles' => array('vier_show_profiles', t('Community Profiles'), $show_profiles, '', $show_or_not),
		'$show_helpers' => array('vier_show_helpers', t('Help or @NewHere ?'), $show_helpers, '', $show_or_not),
		'$show_services' => array('vier_show_services', t('Connect Services'), $show_services, '', $show_or_not),
		'$show_friends' => array('vier_show_friends', t('Find Friends'), $show_friends, '', $show_or_not),
		'$show_lastusers' => array('vier_show_lastusers', t('Last users'), $show_lastusers, '', $show_or_not)
	));
	return $o;
}
