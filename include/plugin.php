<?php


// install and uninstall plugin
if (! function_exists('uninstall_plugin')){
function uninstall_plugin($plugin){
	logger("Addons: uninstalling " . $plugin);
	q("DELETE FROM `addon` WHERE `name` = '%s' LIMIT 1",
		dbesc($plugin)
	);

	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_uninstall')) {
		$func = $plugin . '_uninstall';
		$func();
	}
}}

if (! function_exists('install_plugin')){
function install_plugin($plugin){
	logger("Addons: installing " . $plugin);
	$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_install')) {
		$func = $plugin . '_install';
		$func();
		
		$plugin_admin = (function_exists($plugin."_plugin_admin")?1:0);
		
		$r = q("INSERT INTO `addon` (`name`, `installed`, `timestamp`, `plugin_admin`) VALUES ( '%s', 1, %d , %d ) ",
			dbesc($plugin),
			intval($t),
			$plugin_admin
		);
	}
	else {
		logger("Addons: FAILED installing " . $plugin);
	}

}}

// reload all updated plugins

if(! function_exists('reload_plugins')) {
function reload_plugins() {
	$plugins = get_config('system','addon');
	if(strlen($plugins)) {

		$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
		if(count($r))
			$installed = $r;
		else
			$installed = array();

		$parr = explode(',',$plugins);
		if(count($parr)) {
			foreach($parr as $pl) {
				$pl = trim($pl);

				$fname = 'addon/' . $pl . '/' . $pl . '.php';
				
				if(file_exists($fname)) {
					$t = @filemtime($fname);
					foreach($installed as $i) {
						if(($i['name'] == $pl) && ($i['timestamp'] != $t)) {	
							logger('Reloading plugin: ' . $i['name']);
							@include_once($fname);

							if(function_exists($pl . '_uninstall')) {
								$func = $pl . '_uninstall';
								$func();
							}
							if(function_exists($pl . '_install')) {
								$func = $pl . '_install';
								$func();
							}
							q("UPDATE `addon` SET `timestamp` = %d WHERE `id` = %d LIMIT 1",
								intval($t),
								intval($i['id'])
							);
						}
					}
				}
			}
		}
	}
}}
				




if(! function_exists('register_hook')) {
function register_hook($hook,$file,$function) {

	$r = q("SELECT * FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	if(count($r))
		return true;

	$r = q("INSERT INTO `hook` (`hook`, `file`, `function`) VALUES ( '%s', '%s', '%s' ) ",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	return $r;
}}

if(! function_exists('unregister_hook')) {
function unregister_hook($hook,$file,$function) {

	$r = q("DELETE FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	return $r;
}}


if(! function_exists('load_hooks')) {
function load_hooks() {
	$a = get_app();
	$a->hooks = array();
	$r = q("SELECT * FROM `hook` WHERE 1");
	if(count($r)) {
		foreach($r as $rr) {
			$a->hooks[] = array($rr['hook'], $rr['file'], $rr['function']);
		}
	}
}}


if(! function_exists('call_hooks')) {
function call_hooks($name, &$data = null) {
	$a = get_app();

	if(count($a->hooks)) {
		foreach($a->hooks as $hook) {
			if($hook[HOOK_HOOK] === $name) {
				@include_once($hook[HOOK_FILE]);
				if(function_exists($hook[HOOK_FUNCTION])) {
					$func = $hook[HOOK_FUNCTION];
					$func($a,$data);
				}
			}
		}
	}
}}


/*
 * parse plugin comment in search of plugin infos.
 * like
 * 	
 * 	 * Name: Plugin
 *   * Description: A plugin which plugs in
 * 	 * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Author: Jane <email>
 *   *
 */

if (! function_exists('get_plugin_info')){
function get_plugin_info($plugin){
	$info=Array(
		'name' => $plugin,
		'description' => "",
		'author' => array(),
		'version' => ""
	);
	
	if (!is_file("addon/$plugin/$plugin.php")) return $info;
	
	$f = file_get_contents("addon/$plugin/$plugin.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);
	
	if ($r){
		$ll = explode("\n", $m[0]);
		foreach( $ll as $l ) {
			$l = trim($l,"\t\n\r */");
			if ($l!=""){
				list($k,$v) = array_map("trim", explode(":",$l,2));
				$k= strtolower($k);
				if ($k=="author"){
					$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['author'][] = array('name'=>$m[1], 'link'=>$m[2]);
					} else {
						$info['author'][] = array('name'=>$v);
					}
				} else {
					if (array_key_exists($k,$info)){
						$info[$k]=$v;
					}
				}
				
			}
		}
		
	}
	return $info;
}}


/*
 * parse theme comment in search of theme infos.
 * like
 * 	
 * 	 * Name: My Theme
 *   * Description: My Cool Theme
 * 	 * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Maintainer: Jane <profile url>
 *   *
 */

if (! function_exists('get_theme_info')){
function get_theme_info($theme){
	$info=Array(
		'name' => $theme,
		'description' => "",
		'author' => array(),
		'maintainer' => array(),
		'version' => "",
		'experimental' => false,
		'unsupported' => false
	);

	if(file_exists("view/theme/$theme/experimental"))
		$info['experimental'] = true;
	if(file_exists("view/theme/$theme/unsupported"))
		$info['unsupported'] = true;

	if (!is_file("view/theme/$theme/theme.php")) return $info;
	
	$f = file_get_contents("view/theme/$theme/theme.php");
	$r = preg_match("|/\*.*\*/|msU", $f, $m);
	
	
	if ($r){
		$ll = explode("\n", $m[0]);
		foreach( $ll as $l ) {
			$l = trim($l,"\t\n\r */");
			if ($l!=""){
				list($k,$v) = array_map("trim", explode(":",$l,2));
				$k= strtolower($k);
				if ($k=="author"){

					$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['author'][] = array('name'=>$m[1], 'link'=>$m[2]);
					} else {
						$info['author'][] = array('name'=>$v);
					}
				}
				elseif ($k=="maintainer"){
					$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r) {
						$info['maintainer'][] = array('name'=>$m[1], 'link'=>$m[2]);
					} else {
						$info['maintainer'][] = array('name'=>$v);
					}
				} else {
					if (array_key_exists($k,$info)){
						$info[$k]=$v;
					}
				}
				
			}
		}
		
	}
	return $info;
}}


function get_theme_screenshot($theme) {
	$a = get_app();
	$exts = array('.png','.jpg');
	foreach($exts as $ext) {
		if(file_exists('view/theme/' . $theme . '/screenshot' . $ext))
			return($a->get_baseurl() . '/view/theme/' . $theme . '/screenshot' . $ext);
	}
	return($a->get_baseurl() . '/images/blank.png');
}
