<?php

 /**
  * Friendica admin
  */
require_once("include/remoteupdate.php");


/**
 * @param App $a
 */
function admin_post(&$a){


	if(!is_site_admin()) {
		return;
	}

	// do not allow a page manager to access the admin panel at all.

	if(x($_SESSION,'submanage') && intval($_SESSION['submanage']))
		return;
	


	// urls
	if ($a->argc > 1){
		switch ($a->argv[1]){
			case 'site':
				admin_page_site_post($a);
				break;
			case 'users':
				admin_page_users_post($a);
				break;
			case 'plugins':
				if ($a->argc > 2 && 
					is_file("addon/".$a->argv[2]."/".$a->argv[2].".php")){
						@include_once("addon/".$a->argv[2]."/".$a->argv[2].".php");
						if(function_exists($a->argv[2].'_plugin_admin_post')) {
							$func = $a->argv[2].'_plugin_admin_post';
							$func($a);
						}
				}
				goaway($a->get_baseurl(true) . '/admin/plugins/' . $a->argv[2] );
				return; // NOTREACHED
				break;
			case 'themes':
				$theme = $a->argv[2];
				if (is_file("view/theme/$theme/config.php")){
					require_once("view/theme/$theme/config.php");
					if (function_exists("theme_admin_post")){
						theme_admin_post($a);
					}
				}
				info(t('Theme settings updated.'));
				if(is_ajax()) return;
				
				goaway($a->get_baseurl(true) . '/admin/themes/' . $theme );
				return;
				break;
			case 'logs':
				admin_page_logs_post($a);
				break;
			case 'dbsync':
				admin_page_dbsync_post($a);
				break;
			case 'update':
				admin_page_remoteupdate_post($a);
				break;
		}
	}

	goaway($a->get_baseurl(true) . '/admin' );
	return; // NOTREACHED	
}

/**
 * @param App $a
 * @return string
 */
function admin_content(&$a) {

	if(!is_site_admin()) {
		return login(false);
	}

	if(x($_SESSION,'submanage') && intval($_SESSION['submanage']))
		return "";

	/**
	 * Side bar links
	 */

	// array( url, name, extra css classes )
	$aside = Array(
		'site'	 =>	Array($a->get_baseurl(true)."/admin/site/", t("Site") , "site"),
		'users'	 =>	Array($a->get_baseurl(true)."/admin/users/", t("Users") , "users"),
		'plugins'=>	Array($a->get_baseurl(true)."/admin/plugins/", t("Plugins") , "plugins"),
		'themes' =>	Array($a->get_baseurl(true)."/admin/themes/", t("Themes") , "themes"),
		'dbsync' => Array($a->get_baseurl(true)."/admin/dbsync/", t('DB updates'), "dbsync"),
		//'update' =>	Array($a->get_baseurl(true)."/admin/update/", t("Software Update") , "update")
	);
	
	/* get plugins admin page */
	
	$r = q("SELECT * FROM `addon` WHERE `plugin_admin`=1");
	$aside['plugins_admin']=Array();
	foreach ($r as $h){
		$plugin =$h['name'];
		$aside['plugins_admin'][] = Array($a->get_baseurl(true)."/admin/plugins/".$plugin, $plugin, "plugin");
		// temp plugins with admin
		$a->plugins_admin[] = $plugin;
	}
		
	$aside['logs'] = Array($a->get_baseurl(true)."/admin/logs/", t("Logs"), "logs");

	$t = get_markup_template("admin_aside.tpl");
	$a->page['aside'] .= replace_macros( $t, array(
			'$admin' => $aside, 
			'$h_pending' => t('User registrations waiting for confirmation'),
			'$admurl'=> $a->get_baseurl(true)."/admin/"
	));



	/**
	 * Page content
	 */
	$o = '';
	
	// urls
	if ($a->argc > 1){
		switch ($a->argv[1]){
			case 'site':
				$o = admin_page_site($a);
				break;
			case 'users':
				$o = admin_page_users($a);
				break;
			case 'plugins':
				$o = admin_page_plugins($a);
				break;
			case 'themes':
				$o = admin_page_themes($a);
				break;
			case 'logs':
				$o = admin_page_logs($a);
				break;
			case 'dbsync':
				$o = admin_page_dbsync($a);
				break;
			case 'update':
				$o = admin_page_remoteupdate($a);
				break;
			default:
				notice( t("Item not found.") );
		}
	} else {
		$o = admin_page_summary($a);
	}
	
	if(is_ajax()) {
		echo $o; 
		killme();
		return '';
	} else {
		return $o;
	}
} 


/**
 * Admin Summary Page
 * @param App $a
 * @return string
 */
function admin_page_summary(&$a) {
	$r = q("SELECT `page-flags`, COUNT(uid) as `count` FROM `user` GROUP BY `page-flags`");
	$accounts = Array(
		Array( t('Normal Account'), 0),
		Array( t('Soapbox Account'), 0),
		Array( t('Community/Celebrity Account'), 0),
		Array( t('Automatic Friend Account'), 0)
	);

	$users=0;
	foreach ($r as $u){ $accounts[$u['page-flags']][1] = $u['count']; $users+= $u['count']; }

	logger('accounts: ' . print_r($accounts,true));

	$r = q("SELECT COUNT(id) as `count` FROM `register`");
	$pending = $r[0]['count'];
		
	$r = q("select count(*) as total from deliverq where 1");
	$deliverq = (($r) ? $r[0]['total'] : 0);

	$r = q("select count(*) as total from queue where 1");
	$queue = (($r) ? $r[0]['total'] : 0);

	// We can do better, but this is a quick queue status

	$queues = array( 'label' => t('Message queues'), 'deliverq' => $deliverq, 'queue' => $queue );


	$t = get_markup_template("admin_summary.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Summary'),
		'$queues' => $queues,
		'$users' => Array( t('Registered users'), $users),
		'$accounts' => $accounts,
		'$pending' => Array( t('Pending registrations'), $pending),
		'$version' => Array( t('Version'), FRIENDICA_VERSION),
		'$build' =>  get_config('system','build'),
		'$plugins' => Array( t('Active plugins'), $a->plugins )
	));
}


/**
 * Admin Site Page
 *  @param App $a
 */
function admin_page_site_post(&$a){
	if (!x($_POST,"page_site")){
		return;
	}

    check_form_security_token_redirectOnErr('/admin/site', 'admin_site');

	$sitename 			=	((x($_POST,'sitename'))			? notags(trim($_POST['sitename']))			: '');
	$banner				=	((x($_POST,'banner'))      		? trim($_POST['banner'])					: false);
	$language			=	((x($_POST,'language'))			? notags(trim($_POST['language']))			: '');
	$theme				=	((x($_POST,'theme'))			? notags(trim($_POST['theme']))				: '');
	$maximagesize		=	((x($_POST,'maximagesize'))		? intval(trim($_POST['maximagesize']))		:  0);
	
	
	$register_policy	=	((x($_POST,'register_policy'))	? intval(trim($_POST['register_policy']))	:  0);
	$abandon_days	    =	((x($_POST,'abandon_days'))	    ? intval(trim($_POST['abandon_days']))	    :  0);

	$register_text		=	((x($_POST,'register_text'))	? notags(trim($_POST['register_text']))		: '');	
	
	$allowed_sites		=	((x($_POST,'allowed_sites'))	? notags(trim($_POST['allowed_sites']))		: '');
	$allowed_email		=	((x($_POST,'allowed_email'))	? notags(trim($_POST['allowed_email']))		: '');
	$block_public		=	((x($_POST,'block_public'))		? True	:	False);
	$force_publish		=	((x($_POST,'publish_all'))		? True	:	False);
	$global_directory	=	((x($_POST,'directory_submit_url'))	? notags(trim($_POST['directory_submit_url']))	: '');
	$no_multi_reg		=	((x($_POST,'no_multi_reg'))		? True	:	False);
	$no_openid			=	!((x($_POST,'no_openid'))		? True	:	False);
	$no_regfullname		=	!((x($_POST,'no_regfullname'))	? True	:	False);
	$no_utf				=	!((x($_POST,'no_utf'))			? True	:	False);
	$no_community_page	=	!((x($_POST,'no_community_page'))	? True	:	False);

	$verifyssl			=	((x($_POST,'verifyssl'))		? True	:	False);
	$proxyuser			=	((x($_POST,'proxyuser'))		? notags(trim($_POST['proxyuser']))	: '');
	$proxy				=	((x($_POST,'proxy'))			? notags(trim($_POST['proxy']))	: '');
	$timeout			=	((x($_POST,'timeout'))			? intval(trim($_POST['timeout']))		: 60);
	$delivery_interval	=	((x($_POST,'delivery_interval'))? intval(trim($_POST['delivery_interval']))		: 0);
	$poll_interval	=	((x($_POST,'poll_interval'))? intval(trim($_POST['poll_interval']))		: 0);
	$maxloadavg	        =	((x($_POST,'maxloadavg'))       ? intval(trim($_POST['maxloadavg']))		: 50);
	$dfrn_only          =	((x($_POST,'dfrn_only'))	    ? True	:	False);
	$ostatus_disabled   =   !((x($_POST,'ostatus_disabled')) ? True  :   False);
	$diaspora_enabled   =   ((x($_POST,'diaspora_enabled')) ? True   :  False);
	$ssl_policy         =   ((x($_POST,'ssl_policy')) ? intval($_POST['ssl_policy']) : 0);

	if($ssl_policy != intval(get_config('system','ssl_policy'))) {
		if($ssl_policy == SSL_POLICY_FULL) {
			q("update `contact` set 
				`url`     = replace(`url`    , 'http:' , 'https:'),
				`photo`   = replace(`photo`  , 'http:' , 'https:'),
				`thumb`   = replace(`thumb`  , 'http:' , 'https:'),
				`micro`   = replace(`micro`  , 'http:' , 'https:'),
				`request` = replace(`request`, 'http:' , 'https:'),
				`notify`  = replace(`notify` , 'http:' , 'https:'),
				`poll`    = replace(`poll`   , 'http:' , 'https:'),
				`confirm` = replace(`confirm`, 'http:' , 'https:'),
				`poco`    = replace(`poco`   , 'http:' , 'https:')
				where `self` = 1"
			);
			q("update `profile` set 
				`photo`   = replace(`photo`  , 'http:' , 'https:'),
				`thumb`   = replace(`thumb`  , 'http:' , 'https:')
				where 1 "
			);
		}
		elseif($ssl_policy == SSL_POLICY_SELFSIGN) {
			q("update `contact` set 
				`url`     = replace(`url`    , 'https:' , 'http:'),
				`photo`   = replace(`photo`  , 'https:' , 'http:'),
				`thumb`   = replace(`thumb`  , 'https:' , 'http:'),
				`micro`   = replace(`micro`  , 'https:' , 'http:'),
				`request` = replace(`request`, 'https:' , 'http:'),
				`notify`  = replace(`notify` , 'https:' , 'http:'),
				`poll`    = replace(`poll`   , 'https:' , 'http:'),
				`confirm` = replace(`confirm`, 'https:' , 'http:'),
				`poco`    = replace(`poco`   , 'https:' , 'http:')
				where `self` = 1"
			);
			q("update `profile` set 
				`photo`   = replace(`photo`  , 'https:' , 'http:'),
				`thumb`   = replace(`thumb`  , 'https:' , 'http:')
				where 1 "
			);
		}
	}
	set_config('system','ssl_policy',$ssl_policy);
	set_config('system','delivery_interval',$delivery_interval);
	set_config('system','poll_interval',$poll_interval);
	set_config('system','maxloadavg',$maxloadavg);
	set_config('config','sitename',$sitename);
	if ($banner==""){
		// don't know why, but del_config doesn't work...
		q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
			dbesc("system"),
			dbesc("banner")
		);
	} else {
		set_config('system','banner', $banner);
	}
	set_config('system','language', $language);
	set_config('system','theme', $theme);
	set_config('system','maximagesize', $maximagesize);
	
	set_config('config','register_policy', $register_policy);
	set_config('system','account_abandon_days', $abandon_days);
	set_config('config','register_text', $register_text);
	set_config('system','allowed_sites', $allowed_sites);
	set_config('system','allowed_email', $allowed_email);
	set_config('system','block_public', $block_public);
	set_config('system','publish_all', $force_publish);
	if ($global_directory==""){
		// don't know why, but del_config doesn't work...
		q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
			dbesc("system"),
			dbesc("directory_submit_url")
		);
	} else {
		set_config('system','directory_submit_url', $global_directory);
	}

	set_config('system','block_extended_register', $no_multi_reg);
	set_config('system','no_openid', $no_openid);
	set_config('system','no_regfullname', $no_regfullname);
	set_config('system','no_community_page', $no_community_page);
	set_config('system','no_utf', $no_utf);
	set_config('system','verifyssl', $verifyssl);
	set_config('system','proxyuser', $proxyuser);
	set_config('system','proxy', $proxy);
	set_config('system','curl_timeout', $timeout);
	set_config('system','dfrn_only', $dfrn_only);
	set_config('system','ostatus_disabled', $ostatus_disabled);
	set_config('system','diaspora_enabled', $diaspora_enabled);

	info( t('Site settings updated.') . EOL);
	goaway($a->get_baseurl(true) . '/admin/site' );
	return; // NOTREACHED	
	
}

/**
 * @param  App $a
 * @return string
 */
function admin_page_site(&$a) {
	
	/* Installed langs */
	$lang_choices = array();
	$langs = glob('view/*/strings.php');
	
	if(is_array($langs) && count($langs)) {
		if(! in_array('view/en/strings.php',$langs))
			$langs[] = 'view/en/';
		asort($langs);
		foreach($langs as $l) {
			$t = explode("/",$l);
			$lang_choices[$t[1]] = $t[1];
		}
	}
	
	/* Installed themes */
	$theme_choices = array();
	$files = glob('view/theme/*');
	if($files) {
		foreach($files as $file) {
			$f = basename($file);
			$theme_name = ((file_exists($file . '/experimental')) ?  sprintf("%s - \x28Experimental\x29", $f) : $f);
			$theme_choices[$f] = $theme_name;
		}
	}
	
	
	/* Banner */
	$banner = get_config('system','banner');
	if($banner == false) 
		$banner = '<a href="http://friendica.com"><img id="logo-img" src="images/friendica-32.png" alt="logo" /></a><span id="logo-text"><a href="http://friendica.com">Friendica</a></span>';
	$banner = htmlspecialchars($banner);
	
	//echo "<pre>"; var_dump($lang_choices); die("</pre>");

	/* Register policy */
	$register_choices = Array(
		REGISTER_CLOSED => t("Closed"),
		REGISTER_APPROVE => t("Requires approval"),
		REGISTER_OPEN => t("Open")
	); 

	$ssl_choices = array(
		SSL_POLICY_NONE => t("No SSL policy, links will track page SSL state"),
		SSL_POLICY_FULL => t("Force all links to use SSL"),
		SSL_POLICY_SELFSIGN => t("Self-signed certificate, use SSL for local links only (discouraged)")
	);

	$t = get_markup_template("admin_site.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Site'),
		'$submit' => t('Submit'),
		'$registration' => t('Registration'),
		'$upload' => t('File upload'),
		'$corporate' => t('Policies'),
		'$advanced' => t('Advanced'),
		
		'$baseurl' => $a->get_baseurl(true),
									// name, label, value, help string, extra data...
		'$sitename' 		=> array('sitename', t("Site name"), htmlentities($a->config['sitename'], ENT_QUOTES), ""),
		'$banner'			=> array('banner', t("Banner/Logo"), $banner, ""),
		'$language' 		=> array('language', t("System language"), get_config('system','language'), "", $lang_choices),
		'$theme' 			=> array('theme', t("System theme"), get_config('system','theme'), t("Default system theme - may be over-ridden by user profiles - <a href='#' id='cnftheme'>change theme settings</a>"), $theme_choices),
		'$ssl_policy'       => array('ssl_policy', t("SSL link policy"), (string) intval(get_config('system','ssl_policy')), t("Determines whether generated links should be forced to use SSL"), $ssl_choices),
		'$maximagesize'		=> array('maximagesize', t("Maximum image size"), get_config('system','maximagesize'), t("Maximum size in bytes of uploaded images. Default is 0, which means no limits.")),

		'$register_policy'	=> array('register_policy', t("Register policy"), $a->config['register_policy'], "", $register_choices),
		'$register_text'	=> array('register_text', t("Register text"), htmlentities($a->config['register_text'], ENT_QUOTES, 'UTF-8'), t("Will be displayed prominently on the registration page.")),
		'$abandon_days'     => array('abandon_days', t('Accounts abandoned after x days'), get_config('system','account_abandon_days'), t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')),
		'$allowed_sites'	=> array('allowed_sites', t("Allowed friend domains"), get_config('system','allowed_sites'), t("Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains")),
		'$allowed_email'	=> array('allowed_email', t("Allowed email domains"), get_config('system','allowed_email'), t("Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains")),
		'$block_public'		=> array('block_public', t("Block public"), get_config('system','block_public'), t("Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.")),
		'$force_publish'	=> array('publish_all', t("Force publish"), get_config('system','publish_all'), t("Check to force all profiles on this site to be listed in the site directory.")),
		'$global_directory'	=> array('directory_submit_url', t("Global directory update URL"), get_config('system','directory_submit_url'), t("URL to update the global directory. If this is not set, the global directory is completely unavailable to the application.")),
			
		'$no_multi_reg'		=> array('no_multi_reg', t("Block multiple registrations"),  get_config('system','block_extended_register'), t("Disallow users to register additional accounts for use as pages.")),
		'$no_openid'		=> array('no_openid', t("OpenID support"), !get_config('system','no_openid'), t("OpenID support for registration and logins.")),
		'$no_regfullname'	=> array('no_regfullname', t("Fullname check"), !get_config('system','no_regfullname'), t("Force users to register with a space between firstname and lastname in Full name, as an antispam measure")),
		'$no_utf'			=> array('no_utf', t("UTF-8 Regular expressions"), !get_config('system','no_utf'), t("Use PHP UTF8 regular expressions")),
		'$no_community_page' => array('no_community_page', t("Show Community Page"), !get_config('system','no_community_page'), t("Display a Community page showing all recent public postings on this site.")),
		'$ostatus_disabled' => array('ostatus_disabled', t("Enable OStatus support"), !get_config('system','ostatus_disable'), t("Provide built-in OStatus \x28identi.ca, status.net, etc.\x29 compatibility. All communications in OStatus are public, so privacy warnings will be occasionally displayed.")),	
		'$diaspora_enabled' => array('diaspora_enabled', t("Enable Diaspora support"), get_config('system','diaspora_enabled'), t("Provide built-in Diaspora network compatibility.")),	
		'$dfrn_only'        => array('dfrn_only', t('Only allow Friendica contacts'), get_config('system','dfrn_only'), t("All contacts must use Friendica protocols. All other built-in communication protocols disabled.")),
		'$verifyssl' 		=> array('verifyssl', t("Verify SSL"), get_config('system','verifyssl'), t("If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.")),
		'$proxyuser'		=> array('proxyuser', t("Proxy user"), get_config('system','proxyuser'), ""),
		'$proxy'			=> array('proxy', t("Proxy URL"), get_config('system','proxy'), ""),
		'$timeout'			=> array('timeout', t("Network timeout"), (x(get_config('system','curl_timeout'))?get_config('system','curl_timeout'):60), t("Value is in seconds. Set to 0 for unlimited (not recommended).")),
		'$delivery_interval'			=> array('delivery_interval', t("Delivery interval"), (x(get_config('system','delivery_interval'))?get_config('system','delivery_interval'):2), t("Delay background delivery processes by this many seconds to reduce system load. Recommend: 4-5 for shared hosts, 2-3 for virtual private servers. 0-1 for large dedicated servers.")),
		'$poll_interval'			=> array('poll_interval', t("Poll interval"), (x(get_config('system','poll_interval'))?get_config('system','poll_interval'):2), t("Delay background polling processes by this many seconds to reduce system load. If 0, use delivery interval.")),
		'$maxloadavg'			=> array('maxloadavg', t("Maximum Load Average"), ((intval(get_config('system','maxloadavg')) > 0)?get_config('system','maxloadavg'):50), t("Maximum system load before delivery and poll processes are deferred - default 50.")),
        '$form_security_token' => get_form_security_token("admin_site"),
			
	));

}


function admin_page_dbsync(&$a) {

	$o = '';

	if($a->argc > 3 && intval($a->argv[3]) && $a->argv[2] === 'mark') {
		set_config('database', 'update_' . intval($a->argv[3]), 'success');
		info( t('Update has been marked successful') . EOL);
		goaway($a->get_baseurl(true) . '/admin/dbsync');
	}

	if($a->argc > 2 && intval($a->argv[2])) {
		require_once('update.php');
		$func = 'update_' . intval($a->argv[2]);
		if(function_exists($func)) {
			$retval = $func();
			if($retval === UPDATE_FAILED) {
				$o .= sprintf( t('Executing %s failed. Check system logs.'), $func); 
			}
			elseif($retval === UPDATE_SUCCESS) {
				$o .= sprintf( t('Update %s was successfully applied.', $func));
				set_config('database',$func, 'success');
			}
			else
				$o .= sprintf( t('Update %s did not return a status. Unknown if it succeeded.'), $func);
		}
		else
			$o .= sprintf( t('Update function %s could not be found.'), $func);
		return $o;
	}

	$failed = array();
	$r = q("select * from config where `cat` = 'database' ");
	if(count($r)) {
		foreach($r as $rr) {
			$upd = intval(substr($rr['k'],7));
			if($upd < 1139 || $rr['v'] === 'success')
				continue;
			$failed[] = $upd;
		}
	}
	if(! count($failed))
		return '<h3>' . t('No failed updates.') . '</h3>';

	$o = replace_macros(get_markup_template('failed_updates.tpl'),array(
		'$base' => $a->get_baseurl(true),
		'$banner' => t('Failed Updates'),
		'$desc' => t('This does not include updates prior to 1139, which did not return a status.'),
		'$mark' => t('Mark success (if update was manually applied)'),
		'$apply' => t('Attempt to execute this update step automatically'),
		'$failed' => $failed
	));	

	return $o;

}

/**
 * Users admin page
 *
 * @param App $a
 */
function admin_page_users_post(&$a){
	$pending = ( x($_POST, 'pending') ? $_POST['pending'] : Array() );
	$users = ( x($_POST, 'user') ? $_POST['user'] : Array() );

    check_form_security_token_redirectOnErr('/admin/users', 'admin_users');

	if (x($_POST,'page_users_block')){
		foreach($users as $uid){
			q("UPDATE `user` SET `blocked`=1-`blocked` WHERE `uid`=%s",
				intval( $uid )
			);
		}
		notice( sprintf( tt("%s user blocked/unblocked", "%s users blocked/unblocked", count($users)), count($users)) );
	}
	if (x($_POST,'page_users_delete')){
		require_once("include/Contact.php");
		foreach($users as $uid){
			user_remove($uid);
		}
		notice( sprintf( tt("%s user deleted", "%s users deleted", count($users)), count($users)) );
	}
	
	if (x($_POST,'page_users_approve')){
		require_once("mod/regmod.php");
		foreach($pending as $hash){
			user_allow($hash);
		}
	}
	if (x($_POST,'page_users_deny')){
		require_once("mod/regmod.php");
		foreach($pending as $hash){
			user_deny($hash);
		}
	}
	goaway($a->get_baseurl(true) . '/admin/users' );
	return; // NOTREACHED	
}

/**
 * @param App $a
 * @return string
 */
function admin_page_users(&$a){
	if ($a->argc>2) {
		$uid = $a->argv[3];
		$user = q("SELECT * FROM `user` WHERE `uid`=%d", intval($uid));
		if (count($user)==0){
			notice( 'User not found' . EOL);
			goaway($a->get_baseurl(true) . '/admin/users' );
			return ''; // NOTREACHED
		}		
		switch($a->argv[2]){
			case "delete":{
                check_form_security_token_redirectOnErr('/admin/users', 'admin_users', 't');
				// delete user
				require_once("include/Contact.php");
				user_remove($uid);
				
				notice( sprintf(t("User '%s' deleted"), $user[0]['username']) . EOL);
			}; break;
			case "block":{
                check_form_security_token_redirectOnErr('/admin/users', 'admin_users', 't');
				q("UPDATE `user` SET `blocked`=%d WHERE `uid`=%s",
					intval( 1-$user[0]['blocked'] ),
					intval( $uid )
				);
				notice( sprintf( ($user[0]['blocked']?t("User '%s' unblocked"):t("User '%s' blocked")) , $user[0]['username']) . EOL);
			}; break;
		}
		goaway($a->get_baseurl(true) . '/admin/users' );
		return ''; // NOTREACHED
		
	}
	
	/* get pending */
	$pending = q("SELECT `register`.*, `contact`.`name`, `user`.`email`
				 FROM `register`
				 LEFT JOIN `contact` ON `register`.`uid` = `contact`.`uid`
				 LEFT JOIN `user` ON `register`.`uid` = `user`.`uid`;");
	
	
	/* get users */

	$total = q("SELECT count(*) as total FROM `user` where 1");
	if(count($total)) {
		$a->set_pager_total($total[0]['total']);
		$a->set_pager_itemspage(100);
	}
	
	
	$users = q("SELECT `user` . * , `contact`.`name` , `contact`.`url` , `contact`.`micro`, `lastitem`.`lastitem_date`
				FROM
					(SELECT MAX(`item`.`changed`) as `lastitem_date`, `item`.`uid`
					FROM `item`
					WHERE `item`.`type` = 'wall'
					GROUP BY `item`.`uid`) AS `lastitem`
						 RIGHT OUTER JOIN `user` ON `user`.`uid` = `lastitem`.`uid`,
					   `contact`
				WHERE
					   `user`.`uid` = `contact`.`uid`
						AND `user`.`verified` =1
					AND `contact`.`self` =1
				ORDER BY `contact`.`name` LIMIT %d, %d
				",
				intval($a->pager['start']),
				intval($a->pager['itemspage'])
				);
					
	function _setup_users($e){
		$accounts = Array(
			t('Normal Account'), 
			t('Soapbox Account'),
			t('Community/Celebrity Account'),
			t('Automatic Friend Account')
		);
		$e['page-flags'] = $accounts[$e['page-flags']];
		$e['register_date'] = relative_date($e['register_date']);
		$e['login_date'] = relative_date($e['login_date']);
		$e['lastitem_date'] = relative_date($e['lastitem_date']);
		return $e;
	}
	$users = array_map("_setup_users", $users);
	
	
	$t = get_markup_template("admin_users.tpl");
	$o = replace_macros($t, array(
		// strings //
		'$title' => t('Administration'),
		'$page' => t('Users'),
		'$submit' => t('Submit'),
		'$select_all' => t('select all'),
		'$h_pending' => t('User registrations waiting for confirm'),
		'$th_pending' => array( t('Request date'), t('Name'), t('Email') ),
		'$no_pending' =>  t('No registrations.'),
		'$approve' => t('Approve'),
		'$deny' => t('Deny'),
		'$delete' => t('Delete'),
		'$block' => t('Block'),
		'$unblock' => t('Unblock'),
		
		'$h_users' => t('Users'),
		'$th_users' => array( t('Name'), t('Email'), t('Register date'), t('Last login'), t('Last item'),  t('Account') ),

		'$confirm_delete_multi' => t('Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'),
		'$confirm_delete' => t('The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'),

        '$form_security_token' => get_form_security_token("admin_users"),

		// values //
		'$baseurl' => $a->get_baseurl(true),

		'$pending' => $pending,
		'$users' => $users,
	));
	$o .= paginate($a);
	return $o;
}


/**
 * Plugins admin page
 *
 * @param App $a
 * @return string
 */
function admin_page_plugins(&$a){
	
	/**
	 * Single plugin
	 */
	if ($a->argc == 3){
		$plugin = $a->argv[2];
		if (!is_file("addon/$plugin/$plugin.php")){
			notice( t("Item not found.") );
			return '';
		}
		
		if (x($_GET,"a") && $_GET['a']=="t"){
            check_form_security_token_redirectOnErr('/admin/plugins', 'admin_themes', 't');

			// Toggle plugin status
			$idx = array_search($plugin, $a->plugins);
			if ($idx !== false){
				unset($a->plugins[$idx]);
				uninstall_plugin($plugin);
				info( sprintf( t("Plugin %s disabled."), $plugin ) );
			} else {
				$a->plugins[] = $plugin;
				install_plugin($plugin);
				info( sprintf( t("Plugin %s enabled."), $plugin ) );
			}
			set_config("system","addon", implode(", ",$a->plugins));
			goaway($a->get_baseurl(true) . '/admin/plugins' );
			return ''; // NOTREACHED
		}
		// display plugin details
		require_once('library/markdown.php');

		if (in_array($plugin, $a->plugins)){
			$status="on"; $action= t("Disable");
		} else {
			$status="off"; $action= t("Enable");
		}
		
		$readme=Null;
		if (is_file("addon/$plugin/README.md")){
			$readme = file_get_contents("addon/$plugin/README.md");
			$readme = Markdown($readme);
		} else if (is_file("addon/$plugin/README")){
			$readme = "<pre>". file_get_contents("addon/$plugin/README") ."</pre>";
		} 
		
		$admin_form="";
		if (is_array($a->plugins_admin) && in_array($plugin, $a->plugins_admin)){
			@require_once("addon/$plugin/$plugin.php");
			$func = $plugin.'_plugin_admin';
			$func($a, $admin_form);
		}
		
		$t = get_markup_template("admin_plugins_details.tpl");
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Plugins'),
			'$toggle' => t('Toggle'),
			'$settings' => t('Settings'),
			'$baseurl' => $a->get_baseurl(true),
		
			'$plugin' => $plugin,
			'$status' => $status,
			'$action' => $action,
			'$info' => get_plugin_info($plugin),
			'$str_author' => t('Author: '),
			'$str_maintainer' => t('Maintainer: '),			
		
			'$admin_form' => $admin_form,
			'$function' => 'plugins',
			'$screenshot' => '',
			'$readme' => $readme,

            '$form_security_token' => get_form_security_token("admin_themes"),
		));
	} 
	 
	 
	
	/**
	 * List plugins
	 */
	
	$plugins = array();
	$files = glob("addon/*/");
	if($files) {
		foreach($files as $file) {	
			if (is_dir($file)){
				list($tmp, $id)=array_map("trim", explode("/",$file));
				$info = get_plugin_info($id);
				$plugins[] = array( $id, (in_array($id,  $a->plugins)?"on":"off") , $info);
			}
		}
	}
	
	$t = get_markup_template("admin_plugins.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Plugins'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$function' => 'plugins',	
		'$plugins' => $plugins,
        '$form_security_token' => get_form_security_token("admin_themes"),
	));
}

/**
 * @param array $themes
 * @param string $th
 * @param int $result
 */
function toggle_theme(&$themes,$th,&$result) {
	for($x = 0; $x < count($themes); $x ++) {
		if($themes[$x]['name'] === $th) {
			if($themes[$x]['allowed']) {
				$themes[$x]['allowed'] = 0;
				$result = 0;
			}
			else {
				$themes[$x]['allowed'] = 1;
				$result = 1;
			}
		}
	}
}

/**
 * @param array $themes
 * @param string $th
 * @return int
 */
function theme_status($themes,$th) {
	for($x = 0; $x < count($themes); $x ++) {
		if($themes[$x]['name'] === $th) {
			if($themes[$x]['allowed']) {
				return 1;
			}
			else {
				return 0;
			}
		}
	}
	return 0;
}


/**
 * @param array $themes
 * @return string
 */
function rebuild_theme_table($themes) {
	$o = '';
	if(count($themes)) {
		foreach($themes as $th) {
			if($th['allowed']) {
				if(strlen($o))
					$o .= ',';
				$o .= $th['name'];
			}
		}
	}
	return $o;
}

	
/**
 * Themes admin page
 *
 * @param App $a
 * @return string
 */
function admin_page_themes(&$a){
	
	$allowed_themes_str = get_config('system','allowed_themes');
	$allowed_themes_raw = explode(',',$allowed_themes_str);
	$allowed_themes = array();
	if(count($allowed_themes_raw))
		foreach($allowed_themes_raw as $x)
			if(strlen(trim($x)))
				$allowed_themes[] = trim($x);

	$themes = array();
    $files = glob('view/theme/*');
    if($files) {
        foreach($files as $file) {
            $f = basename($file);
            $is_experimental = intval(file_exists($file . '/experimental'));
			$is_supported = 1-(intval(file_exists($file . '/unsupported'))); // Is not used yet
			$is_allowed = intval(in_array($f,$allowed_themes));
			$themes[] = array('name' => $f, 'experimental' => $is_experimental, 'supported' => $is_supported, 'allowed' => $is_allowed);
        }
    }

	if(! count($themes)) {
		notice( t('No themes found.'));
		return '';
	}

	/**
	 * Single theme
	 */

	if ($a->argc == 3){
		$theme = $a->argv[2];
		if(! is_dir("view/theme/$theme")){
			notice( t("Item not found.") );
			return '';
		}
		
		if (x($_GET,"a") && $_GET['a']=="t"){
            check_form_security_token_redirectOnErr('/admin/themes', 'admin_themes', 't');

			// Toggle theme status

			toggle_theme($themes,$theme,$result);
			$s = rebuild_theme_table($themes);
			if($result)
				info( sprintf('Theme %s enabled.',$theme));
			else
				info( sprintf('Theme %s disabled.',$theme));

			set_config('system','allowed_themes',$s);
			goaway($a->get_baseurl(true) . '/admin/themes' );
			return ''; // NOTREACHED
		}

		// display theme details
		require_once('library/markdown.php');

		if (theme_status($themes,$theme)) {
			$status="on"; $action= t("Disable");
		} else {
			$status="off"; $action= t("Enable");
		}
		
		$readme=Null;
		if (is_file("view/theme/$theme/README.md")){
			$readme = file_get_contents("view/theme/$theme/README.md");
			$readme = Markdown($readme);
		} else if (is_file("view/theme/$theme/README")){
			$readme = "<pre>". file_get_contents("view/theme/$theme/README") ."</pre>";
		} 
		
		$admin_form="";
		if (is_file("view/theme/$theme/config.php")){
			require_once("view/theme/$theme/config.php");
			if(function_exists("theme_admin")){
				$admin_form = theme_admin($a);
			}
			
		}
		

		$screenshot = array( get_theme_screenshot($theme), t('Screenshot'));
		if(! stristr($screenshot[0],$theme))
			$screenshot = null;		

		$t = get_markup_template("admin_plugins_details.tpl");
		return replace_macros($t, array(
			'$title' => t('Administration'),
			'$page' => t('Themes'),
			'$toggle' => t('Toggle'),
			'$settings' => t('Settings'),
			'$baseurl' => $a->get_baseurl(true),
		
			'$plugin' => $theme,
			'$status' => $status,
			'$action' => $action,
			'$info' => get_theme_info($theme),
			'$function' => 'themes',
			'$admin_form' => $admin_form,
			'$str_author' => t('Author: '),
			'$str_maintainer' => t('Maintainer: '),
			'$screenshot' => $screenshot,
			'$readme' => $readme,

			'$form_security_token' => get_form_security_token("admin_themes"),
		));
	} 
	 
	 
	
	/**
	 * List themes
	 */
	
	$xthemes = array();
	if($themes) {
		foreach($themes as $th) {
			$xthemes[] = array($th['name'],(($th['allowed']) ? "on" : "off"), get_theme_info($th['name']));
		}
	}
	
	$t = get_markup_template("admin_plugins.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Themes'),
		'$submit' => t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$function' => 'themes',
		'$plugins' => $xthemes,
		'$experimental' => t('[Experimental]'),
		'$unsupported' => t('[Unsupported]'),
        '$form_security_token' => get_form_security_token("admin_themes"),
	));
}


/**
 * Logs admin page
 *
 * @param App $a
 */
 
function admin_page_logs_post(&$a) {
	if (x($_POST,"page_logs")) {
        check_form_security_token_redirectOnErr('/admin/logs', 'admin_logs');

		$logfile 		=	((x($_POST,'logfile'))		? notags(trim($_POST['logfile']))	: '');
		$debugging		=	((x($_POST,'debugging'))	? true								: false);
		$loglevel 		=	((x($_POST,'loglevel'))		? intval(trim($_POST['loglevel']))	: 0);

		set_config('system','logfile', $logfile);
		set_config('system','debugging',  $debugging);
		set_config('system','loglevel', $loglevel);

		
	}

	info( t("Log settings updated.") );
	goaway($a->get_baseurl(true) . '/admin/logs' );
	return; // NOTREACHED	
}

/**
 * @param App $a
 * @return string
 */
function admin_page_logs(&$a){
	
	$log_choices = Array(
		LOGGER_NORMAL => 'Normal',
		LOGGER_TRACE => 'Trace',
		LOGGER_DEBUG => 'Debug',
		LOGGER_DATA => 'Data',
		LOGGER_ALL => 'All'
	);
	
	$t = get_markup_template("admin_logs.tpl");

	$f = get_config('system','logfile');

	$data = '';

	if(!file_exists($f)) {
		$data = t("Error trying to open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f exist and is 
readable.");
	}
	else {
		$fp = fopen($f, 'r');
		if(!$fp) {
			$data = t("Couldn't open <strong>$f</strong> log file.\r\n<br/>Check to see if file $f is readable.");
		}
		else {
			$fstat = fstat($fp);
			$size = $fstat['size'];
			if($size != 0)
			{
				if($size > 5000000 || $size < 0)
					$size = 5000000;
				$seek = fseek($fp,0-$size,SEEK_END);
				if($seek === 0) {
					$data = escape_tags(fread($fp,$size));
					while(! feof($fp))
						$data .= escape_tags(fread($fp,4096));
				}
			}
			fclose($fp);
		}
	}			

	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Logs'),
		'$submit' => t('Submit'),
		'$clear' => t('Clear'),
		'$data' => $data,
		'$baseurl' => $a->get_baseurl(true),
		'$logname' =>  get_config('system','logfile'),
		
									// name, label, value, help string, extra data...
		'$debugging' 		=> array('debugging', t("Debugging"),get_config('system','debugging'), ""),
		'$logfile'			=> array('logfile', t("Log file"), get_config('system','logfile'), t("Must be writable by web server. Relative to your Friendica top-level directory.")),
		'$loglevel' 		=> array('loglevel', t("Log level"), get_config('system','loglevel'), "", $log_choices),

        '$form_security_token' => get_form_security_token("admin_logs"),
	));
}

/**
 * @param App $a
 */
function admin_page_remoteupdate_post(&$a) {
	// this function should be called via ajax post
	if(!is_site_admin()) {
		return;
	}

	
	if (x($_POST,'remotefile') && $_POST['remotefile']!=""){
		$remotefile = $_POST['remotefile'];
		$ftpdata = (x($_POST['ftphost'])?$_POST:false);
		doUpdate($remotefile, $ftpdata);
	} else {
		echo "No remote file to download. Abort!";
	}

	killme();
}

/**
 * @param App $a
 * @return string
 */
function admin_page_remoteupdate(&$a) {
	if(!is_site_admin()) {
		return login(false);
	}

	$canwrite = canWeWrite();
	$canftp = function_exists('ftp_connect');
	
	$needupdate = true;
	$u = checkUpdate();
	if (!is_array($u)){
		$needupdate = false;
		$u = array('','','');
	}
	
	$tpl = get_markup_template("admin_remoteupdate.tpl");
	return replace_macros($tpl, array(
		'$baseurl' => $a->get_baseurl(true),
		'$submit' => t("Update now"),
		'$close' => t("Close"),
		'$localversion' => FRIENDICA_VERSION,
		'$remoteversion' => $u[1],
		'$needupdate' => $needupdate,
		'$canwrite' => $canwrite,
		'$canftp'	=> $canftp,
		'$ftphost'	=> array('ftphost', t("FTP Host"), '',''),
		'$ftppath'	=> array('ftppath', t("FTP Path"), '/',''),
		'$ftpuser'	=> array('ftpuser', t("FTP User"), '',''),
		'$ftppwd'	=> array('ftppwd', t("FTP Password"), '',''),
		'$remotefile'=>array('remotefile','', $u['2'],'')
	));
	
}
