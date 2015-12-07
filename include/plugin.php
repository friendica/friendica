<?php
/**
 * @file include/identity.php
 * 
 * @brief Some functions to handle addons and themes.
 */


/**
 * @brief uninstalls an addon.
 *
 * @param string $plugin name of the addon
 * @return boolean
 */
if (! function_exists('uninstall_plugin')){
function uninstall_plugin($plugin){
	logger("Addons: uninstalling " . $plugin);
	q("DELETE FROM `addon` WHERE `name` = '%s' ",
		dbesc($plugin)
	);

	@include_once('addon/' . $plugin . '/' . $plugin . '.php');
	if(function_exists($plugin . '_uninstall')) {
		$func = $plugin . '_uninstall';
		$func();
	}
}}

/**
 * @brief installs an addon.
 *
 * @param string $plugin name of the addon
 * @return bool
 */
if (! function_exists('install_plugin')){
function install_plugin($plugin) {
	// silently fail if plugin was removed

	if(! file_exists('addon/' . $plugin . '/' . $plugin . '.php'))
		return false;
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

		// we can add the following with the previous SQL
		// once most site tables have been updated.
		// This way the system won't fall over dead during the update.

		if(file_exists('addon/' . $plugin . '/.hidden')) {
			q("UPDATE `addon` SET `hidden` = 1 WHERE `name` = '%s'",
				dbesc($plugin)
			);
		}
		return true;
	}
	else {
		logger("Addons: FAILED installing " . $plugin);
		return false;
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
							q("UPDATE `addon` SET `timestamp` = %d WHERE `id` = %d",
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

/**
 * @brief check if addon is enabled
 *
 * @param string $plugin
 * @return boolean
 */
function plugin_enabled($plugin) {
	$r = q("SELECT * FROM `addon` WHERE `installed` = 1 AND `name` = '%s'", $plugin);
	return((bool)(count($r) > 0));
}


/**
 * @brief registers a hook.
 *
 * @param string $hook the name of the hook
 * @param string $file the name of the file that hooks into
 * @param string $function the name of the function that the hook will call
 * @param int $priority A priority (defaults to 0)
 * @return mixed|bool
 */
if(! function_exists('register_hook')) {
function register_hook($hook,$file,$function,$priority=0) {

	$r = q("SELECT * FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s' LIMIT 1",
		dbesc($hook),
		dbesc($file),
		dbesc($function)
	);
	if(count($r))
		return true;

	$r = q("INSERT INTO `hook` (`hook`, `file`, `function`, `priority`) VALUES ( '%s', '%s', '%s', '%s' ) ",
		dbesc($hook),
		dbesc($file),
		dbesc($function),
		dbesc($priority)
	);
	return $r;
}}

/**
 * @brief unregisters a hook.
 * 
 * @param string $hook the name of the hook
 * @param string $file the name of the file that hooks into
 * @param string $function the name of the function that the hook called
 * @return array
 */
if(! function_exists('unregister_hook')) {
function unregister_hook($hook,$file,$function) {

	$r = q("DELETE FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s'",
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
	$r = q("SELECT * FROM `hook` WHERE 1 ORDER BY `priority` DESC, `file`");
	if(count($r)) {
		foreach($r as $rr) {
			if(! array_key_exists($rr['hook'],$a->hooks))
				$a->hooks[$rr['hook']] = array();
			$a->hooks[$rr['hook']][] = array($rr['file'],$rr['function']);
		}
	}
}}

/**
 * @brief Calls a hook.
 *
 * Use this function when you want to be able to allow a hook to manipulate
 * the provided data.
 *
 * @param string $name of the hook to call
 * @param string|array &$data to transmit to the callback handler
 */
if(! function_exists('call_hooks')) {
function call_hooks($name, &$data = null) {
	$stamp1 = microtime(true);

	$a = get_app();

	#logger($name, LOGGER_ALL);

	if((is_array($a->hooks)) && (array_key_exists($name,$a->hooks))) {
		foreach($a->hooks[$name] as $hook) {
			// Don't run a theme's hook if the user isn't using the theme
			if(strpos($hook[0], 'view/theme/') !== false && strpos($hook[0], 'view/theme/'.current_theme()) === false)
				continue;

			@include_once($hook[0]);
			if(function_exists($hook[1])) {
				$func = $hook[1];
				//logger($name." => ".$hook[0].":".$func."()", LOGGER_DEBUG);
				$func($a,$data);
			}
			else {
				// remove orphan hooks
				q("DELETE FROM `hook` WHERE `hook` = '%s' AND `file` = '%s' AND `function` = '%s'",
					dbesc($name),
					dbesc($hook[0]),
					dbesc($hook[1])
				);
			}
		}
	}
}}

//check if an app_menu hook exist for plugin $name.
//Return true if the plugin is an app
if(! function_exists('plugin_is_app')) {
function plugin_is_app($name) {
	$a = get_app();

	if(is_array($a->hooks) && (array_key_exists('app_menu',$a->hooks))) {
		foreach($a->hooks['app_menu'] as $hook) {
			if($hook[0] == 'addon/'.$name.'/'.$name.'.php')
				return true;
		}
	}

	return false;
}}

/**
 * @brief Parse plugin comment in search of plugin infos.
 *
 * like
 * \code
 *...* Name: Plugin
 *   * Description: A plugin which plugs in
 * . * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Author: Jane <email>
 *   *
 *  *\endcode
 * @param string $plugin the name of the plugin
 * @return array with the plugin information
 */

if (! function_exists('get_plugin_info')){
function get_plugin_info($plugin){

	$a = get_app();

	$info=Array(
		'name' => $plugin,
		'description' => "",
		'author' => array(),
		'version' => "",
		'status' => ""
	);

	if (!is_file("addon/$plugin/$plugin.php")) return $info;

	$stamp1 = microtime(true);
	$f = file_get_contents("addon/$plugin/$plugin.php");
	$a->save_timestamp($stamp1, "file");

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


/**
 * @brief Parse theme comment in search of theme infos.
 * 
 * like
 * \code
 * ..* Name: My Theme
 *   * Description: My Cool Theme
 * . * Version: 1.2.3
 *   * Author: John <profile url>
 *   * Maintainer: Jane <profile url>
 *   *
 * \endcode
 * @param string $theme the name of the theme
 * @return array
 */

if (! function_exists('get_theme_info')){
function get_theme_info($theme){
	$info=Array(
		'name' => $theme,
		'description' => "",
		'author' => array(),
		'maintainer' => array(),
		'version' => "",
		'credits' => "",
		'experimental' => false,
		'unsupported' => false
	);

	if(file_exists("view/theme/$theme/experimental"))
		$info['experimental'] = true;
	if(file_exists("view/theme/$theme/unsupported"))
		$info['unsupported'] = true;

	if (!is_file("view/theme/$theme/theme.php")) return $info;

	$a = get_app();
	$stamp1 = microtime(true);
	$f = file_get_contents("view/theme/$theme/theme.php");
	$a->save_timestamp($stamp1, "file");

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

/**
 * @brief Returns the theme's screenshot.
 *
 * The screenshot is expected as view/theme/$theme/screenshot.[png|jpg].
 *
 * @param sring $theme The name of the theme
 * @return string
 */
function get_theme_screenshot($theme) {
	$a = get_app();
	$exts = array('.png','.jpg');
	foreach($exts as $ext) {
		if(file_exists('view/theme/' . $theme . '/screenshot' . $ext))
			return($a->get_baseurl() . '/view/theme/' . $theme . '/screenshot' . $ext);
	}
	return($a->get_baseurl() . '/images/blank.png');
}

// install and uninstall theme
if (! function_exists('uninstall_theme')){
function uninstall_theme($theme){
	logger("Addons: uninstalling theme " . $theme);

	@include_once("view/theme/$theme/theme.php");
	if(function_exists("{$theme}_uninstall")) {
		$func = "{$theme}_uninstall";
		$func();
	}
}}

if (! function_exists('install_theme')){
function install_theme($theme) {
	// silently fail if theme was removed

	if(! file_exists("view/theme/$theme/theme.php"))
		return false;

	logger("Addons: installing theme $theme");

	@include_once("view/theme/$theme/theme.php");

	if(function_exists("{$theme}_install")) {
		$func = "{$theme}_install";
		$func();
		return true;
	}
	else {
		logger("Addons: FAILED installing theme $theme");
		return false;
	}

}}



// check service_class restrictions. If there are no service_classes defined, everything is allowed.
// if $usage is supplied, we check against a maximum count and return true if the current usage is
// less than the subscriber plan allows. Otherwise we return boolean true or false if the property
// is allowed (or not) in this subscriber plan. An unset property for this service plan means
// the property is allowed, so it is only necessary to provide negative properties for each plan,
// or what the subscriber is not allowed to do.


function service_class_allows($uid,$property,$usage = false) {

	if($uid == local_user()) {
		$service_class = $a->user['service_class'];
	}
	else {
		$r = q("SELECT `service_class` FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($uid)
		);
		if($r !== false and count($r)) {
			$service_class = $r[0]['service_class'];
		}
	}
	if(! x($service_class))
		return true; // everything is allowed

	$arr = get_config('service_class',$service_class);
	if(! is_array($arr) || (! count($arr)))
		return true;

	if($usage === false)
		return ((x($arr[$property])) ? (bool) $arr['property'] : true);
	else {
		if(! array_key_exists($property,$arr))
			return true;
		return (((intval($usage)) < intval($arr[$property])) ? true : false);
	}
}


function service_class_fetch($uid,$property) {

	if($uid == local_user()) {
		$service_class = $a->user['service_class'];
	}
	else {
		$r = q("SELECT `service_class` FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($uid)
		);
		if($r !== false and count($r)) {
			$service_class = $r[0]['service_class'];
		}
	}
	if(! x($service_class))
		return false; // everything is allowed

	$arr = get_config('service_class',$service_class);
	if(! is_array($arr) || (! count($arr)))
		return false;

	return((array_key_exists($property,$arr)) ? $arr[$property] : false);

}

function upgrade_link($bbcode = false) {
	$l = get_config('service_class','upgrade_link');
	if(! $l)
		return '';
	if($bbcode)
		$t = sprintf('[url=%s]' . t('Click here to upgrade.') . '[/url]', $l);
	else
		$t = sprintf('<a href="%s">' . t('Click here to upgrade.') . '</div>', $l);
	return $t;
}

function upgrade_message($bbcode = false) {
	$x = upgrade_link($bbcode);
	return t('This action exceeds the limits set by your subscription plan.') . (($x) ? ' ' . $x : '') ;
}

function upgrade_bool_message($bbcode = false) {
	$x = upgrade_link($bbcode);
	return t('This action is not available under your subscription plan.') . (($x) ? ' ' . $x : '') ;
}
