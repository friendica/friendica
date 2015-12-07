<?php

 /**
  * Friendica admin
  */
require_once("include/remoteupdate.php");
require_once("include/enotify.php");
require_once("include/text.php");


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
				if ($a->argc < 2) {
					if(is_ajax()) return;
					goaway($a->get_baseurl(true) . '/admin/' );
					return;
				}

				$theme = $a->argv[2];
				if (is_file("view/theme/$theme/config.php")){
					function __call_theme_admin_post(&$a, $theme) {
						$orig_theme = $a->theme;
						$orig_page = $a->page;
						$orig_session_theme = $_SESSION['theme'];
						require_once("view/theme/$theme/theme.php");
						require_once("view/theme/$theme/config.php");
						$_SESSION['theme'] = $theme;


						$init = $theme."_init";
						if(function_exists($init)) $init($a);
						if(function_exists("theme_admin_post")){
							$admin_form = theme_admin_post($a);
						}

						$_SESSION['theme'] = $orig_session_theme;
						$a->theme = $orig_theme;
						$a->page = $orig_page;
						return $admin_form;
					}
					__call_theme_admin_post($a, $theme);
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

	// APC deactivated, since there are problems with PHP 5.5
	//if (function_exists("apc_delete")) {
	//	$toDelete = new APCIterator('user', APC_ITER_VALUE);
	//	apc_delete($toDelete);
	//}

	/**
	 * Side bar links
	 */

	// array( url, name, extra css classes )
	$aside = Array(
		'site'	 =>	Array($a->get_baseurl(true)."/admin/site/", t("Site") , "site"),
		'users'	 =>	Array($a->get_baseurl(true)."/admin/users/", t("Users") , "users"),
		'plugins'=>	Array($a->get_baseurl(true)."/admin/plugins/", t("Plugins") , "plugins"),
		'themes' =>	Array($a->get_baseurl(true)."/admin/themes/", t("Themes") , "themes"),
		'dbsync' => 	Array($a->get_baseurl(true)."/admin/dbsync/", t('DB updates'), "dbsync"),
		'queue'	 =>	Array($a->get_baseurl(true)."/admin/queue/", t('Inspect Queue'), "queue"),
		//'update' =>	Array($a->get_baseurl(true)."/admin/update/", t("Software Update") , "update")
	);

	/* get plugins admin page */

	$r = q("SELECT `name` FROM `addon` WHERE `plugin_admin`=1 ORDER BY `name`");
	$aside['plugins_admin']=Array();
	foreach ($r as $h){
		$plugin =$h['name'];
		$aside['plugins_admin'][] = Array($a->get_baseurl(true)."/admin/plugins/".$plugin, $plugin, "plugin");
		// temp plugins with admin
		$a->plugins_admin[] = $plugin;
	}

	$aside['logs'] = Array($a->get_baseurl(true)."/admin/logs/", t("Logs"), "logs");
	$aside['diagnostics_probe'] = Array($a->get_baseurl(true).'/probe/', t('probe address'), 'probe');
	$aside['diagnostics_webfinger'] = Array($a->get_baseurl(true).'/webfinger/', t('check webfinger'), 'webfinger');

	$t = get_markup_template("admin_aside.tpl");
	$a->page['aside'] .= replace_macros( $t, array(
			'$admin' => $aside,
			'$admtxt' => t('Admin'),
			'$plugadmtxt' => t('Plugin Features'),
			'$logtxt' => t('Logs'),
			'$diagnosticstxt' => t('diagnostics'),
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
			case 'queue':
			    	$o = admin_page_queue($a);
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
 * Admin Inspect Queue Page
 * @param App $a
 * return string
 */
function admin_page_queue(&$a) {
    	// get content from the queue table
    	$r = q("SELECT c.name,c.nurl,q.id,q.network,q.created,q.last from queue as q, contact as c where c.id=q.cid order by q.cid, q.created;");

	$t = get_markup_template("admin_queue.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Inspect Queue'),
		'$count' => sizeof($r),
		'id_header' => t('ID'),
		'$to_header' => t('Recipient Name'),
		'$url_header' => t('Recipient Profile'),
		'$network_header' => t('Network'),
		'$created_header' => t('Created'),
		'$last_header' => t('Last Tried'),
		'$info' => t('This page lists the content of the queue for outgoing postings. These are postings the initial delivery failed for. They will be resend later and eventually deleted if the delivery fails permanently.'),
		'$entries' => $r,
	));
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
		Array( t('Automatic Friend Account'), 0),
		Array( t('Blog Account'), 0),
		Array( t('Private Forum'), 0)
	);

	$users=0;
	foreach ($r as $u){ $accounts[$u['page-flags']][1] = $u['count']; $users+= $u['count']; }

	logger('accounts: ' . print_r($accounts,true),LOGGER_DATA);

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
		'$baseurl' => $a->get_baseurl(),
		'$platform' => FRIENDICA_PLATFORM,
		'$codename' => FRIENDICA_CODENAME,
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

	// relocate
	if (x($_POST,'relocate') && x($_POST,'relocate_url') && $_POST['relocate_url']!=""){
		$new_url = $_POST['relocate_url'];
		$new_url = rtrim($new_url,"/");

		$parsed = @parse_url($new_url);
		if (!$parsed || (!x($parsed,'host') || !x($parsed,'scheme'))) {
			notice(t("Can not parse base url. Must have at least <scheme>://<domain>"));
			goaway($a->get_baseurl(true) . '/admin/site' );
		}

		/* steps:
		 * replace all "baseurl" to "new_url" in config, profile, term, items and contacts
		 * send relocate for every local user
		 * */

		$old_url = $a->get_baseurl(true);

		function update_table($table_name, $fields, $old_url, $new_url) {
			global $db, $a;

			$dbold = dbesc($old_url);
			$dbnew = dbesc($new_url);

			$upd = array();
			foreach ($fields as $f) {
				$upd[] = "`$f` = REPLACE(`$f`, '$dbold', '$dbnew')";
			}

			$upds = implode(", ", $upd);



			$q = sprintf("UPDATE %s SET %s;", $table_name, $upds);
			$r = q($q);
			if (!$r) {
				notice( "Failed updating '$table_name': " . $db->error );
				goaway($a->get_baseurl(true) . '/admin/site' );
			}
		}

		// update tables
		update_table("profile", array('photo', 'thumb'), $old_url, $new_url);
		update_table("term", array('url'), $old_url, $new_url);
		update_table("contact", array('photo','thumb','micro','url','nurl','request','notify','poll','confirm','poco'), $old_url, $new_url);
		update_table("unique_contacts", array('url'), $old_url, $new_url);
		update_table("item", array('owner-link','owner-avatar','author-name','author-link','author-avatar','body','plink','tag'), $old_url, $new_url);

		// update config
		$a->set_baseurl($new_url);
	 	set_config('system','url',$new_url);

		// send relocate
		$users = q("SELECT uid FROM user WHERE account_removed = 0 AND account_expired = 0");

		foreach ($users as $user) {
			proc_run('php', 'include/notifier.php', 'relocate', $user['uid']);
		}

		info("Relocation started. Could take a while to complete.");

		goaway($a->get_baseurl(true) . '/admin/site' );
	}
	// end relocate

	$sitename 		=	((x($_POST,'sitename'))			? notags(trim($_POST['sitename']))		: '');
	$hostname 		=	((x($_POST,'hostname'))			? notags(trim($_POST['hostname']))		: '');
	$sender_email		=	((x($_POST,'sender_email'))		? notags(trim($_POST['sender_email']))		: '');
	$banner			=	((x($_POST,'banner'))      		? trim($_POST['banner'])			: false);
	$shortcut_icon 		=	((x($_POST,'shortcut_icon'))		? notags(trim($_POST['shortcut_icon']))		: '');
	$touch_icon 		=	((x($_POST,'touch_icon'))		? notags(trim($_POST['touch_icon']))		: '');
	$info			=	((x($_POST,'info'))      		? trim($_POST['info'])			: false);
	$language		=	((x($_POST,'language'))			? notags(trim($_POST['language']))		: '');
	$theme			=	((x($_POST,'theme'))			? notags(trim($_POST['theme']))			: '');
	$theme_mobile		=	((x($_POST,'theme_mobile'))		? notags(trim($_POST['theme_mobile']))		: '');
	$maximagesize		=	((x($_POST,'maximagesize'))		? intval(trim($_POST['maximagesize']))		:  0);
	$maximagelength		=	((x($_POST,'maximagelength'))		? intval(trim($_POST['maximagelength']))	:  MAX_IMAGE_LENGTH);
	$jpegimagequality	=	((x($_POST,'jpegimagequality'))		? intval(trim($_POST['jpegimagequality']))	:  JPEG_QUALITY);


	$register_policy	=	((x($_POST,'register_policy'))		? intval(trim($_POST['register_policy']))	:  0);
	$daily_registrations	=	((x($_POST,'max_daily_registrations'))	? intval(trim($_POST['max_daily_registrations']))	:0);
	$abandon_days	    	=	((x($_POST,'abandon_days'))		? intval(trim($_POST['abandon_days']))		:  0);

	$register_text		=	((x($_POST,'register_text'))		? notags(trim($_POST['register_text']))		: '');

	$allowed_sites		=	((x($_POST,'allowed_sites'))		? notags(trim($_POST['allowed_sites']))		: '');
	$allowed_email		=	((x($_POST,'allowed_email'))		? notags(trim($_POST['allowed_email']))		: '');
	$block_public		=	((x($_POST,'block_public'))		? True						: False);
	$force_publish		=	((x($_POST,'publish_all'))		? True						: False);
	$global_directory	=	((x($_POST,'directory'))		? notags(trim($_POST['directory']))	: '');
	$thread_allow		=	((x($_POST,'thread_allow'))		? True						: False);
	$newuser_private		=	((x($_POST,'newuser_private'))		? True						: False);
	$enotify_no_content		=	((x($_POST,'enotify_no_content'))	? True						: False);
	$private_addons			=	((x($_POST,'private_addons'))		? True						: False);
	$disable_embedded		=	((x($_POST,'disable_embedded'))		? True						: False);
	$allow_users_remote_self	=	((x($_POST,'allow_users_remote_self'))		? True						: False);

	$no_multi_reg		=	((x($_POST,'no_multi_reg'))		? True						: False);
	$no_openid		=	!((x($_POST,'no_openid'))		? True						: False);
	$no_regfullname		=	!((x($_POST,'no_regfullname'))		? True						: False);
	$no_utf			=	!((x($_POST,'no_utf'))			? True						: False);
	$community_page_style	=	((x($_POST,'community_page_style'))	? intval(trim($_POST['community_page_style']))	: 0);
	$max_author_posts_community_page	=	((x($_POST,'max_author_posts_community_page'))	? intval(trim($_POST['max_author_posts_community_page']))	: 0);

	$verifyssl		=	((x($_POST,'verifyssl'))		? True						: False);
	$proxyuser		=	((x($_POST,'proxyuser'))		? notags(trim($_POST['proxyuser']))		: '');
	$proxy			=	((x($_POST,'proxy'))			? notags(trim($_POST['proxy']))			: '');
	$timeout		=	((x($_POST,'timeout'))			? intval(trim($_POST['timeout']))		: 60);
	$delivery_interval	=	((x($_POST,'delivery_interval'))	? intval(trim($_POST['delivery_interval']))	: 0);
	$poll_interval		=	((x($_POST,'poll_interval'))		? intval(trim($_POST['poll_interval']))		: 0);
	$maxloadavg		=	((x($_POST,'maxloadavg'))		? intval(trim($_POST['maxloadavg']))		: 50);
	$maxloadavg_frontend	=	((x($_POST,'maxloadavg_frontend'))	? intval(trim($_POST['maxloadavg_frontend']))	: 50);
	$poco_completion	=	((x($_POST,'poco_completion'))		? intval(trim($_POST['poco_completion']))	: false);
	$poco_requery_days	=	((x($_POST,'poco_requery_days'))	? intval(trim($_POST['poco_requery_days']))	: 7);
	$poco_discovery		=	((x($_POST,'poco_discovery'))		? intval(trim($_POST['poco_discovery']))	: 0);
	$poco_discovery_since	=	((x($_POST,'poco_discovery_since'))	? intval(trim($_POST['poco_discovery_since']))	: 30);
	$poco_local_search	=	((x($_POST,'poco_local_search'))	? intval(trim($_POST['poco_local_search']))	: false);
	$nodeinfo		=	((x($_POST,'nodeinfo'))			? intval(trim($_POST['nodeinfo']))		: false);
	$dfrn_only		=	((x($_POST,'dfrn_only'))		? True						: False);
	$ostatus_disabled	=	!((x($_POST,'ostatus_disabled'))	? True  					: False);
	$ostatus_poll_interval	=	((x($_POST,'ostatus_poll_interval'))	? intval(trim($_POST['ostatus_poll_interval']))	:  0);
	$diaspora_enabled	=	((x($_POST,'diaspora_enabled'))		? True   					: False);
	$ssl_policy		=	((x($_POST,'ssl_policy'))		? intval($_POST['ssl_policy']) 			: 0);
	$force_ssl		=	((x($_POST,'force_ssl'))		? True   					: False);
	$old_share		=	((x($_POST,'old_share'))		? True   					: False);
	$hide_help		=	((x($_POST,'hide_help'))		? True   					: False);
	$suppress_language	=	((x($_POST,'suppress_language'))	? True   					: False);
	$suppress_tags		=	((x($_POST,'suppress_tags'))		? True   					: False);
	$use_fulltext_engine	=	((x($_POST,'use_fulltext_engine'))	? True   					: False);
	$itemcache		=	((x($_POST,'itemcache'))		? notags(trim($_POST['itemcache']))		: '');
	$itemcache_duration	=	((x($_POST,'itemcache_duration'))	? intval($_POST['itemcache_duration'])		: 0);
	$max_comments		=	((x($_POST,'max_comments'))		? intval($_POST['max_comments'])		: 0);
	$lockpath		=	((x($_POST,'lockpath'))			? notags(trim($_POST['lockpath']))		: '');
	$temppath		=	((x($_POST,'temppath'))			? notags(trim($_POST['temppath']))		: '');
	$basepath		=	((x($_POST,'basepath'))			? notags(trim($_POST['basepath']))		: '');
	$singleuser		=	((x($_POST,'singleuser'))		? notags(trim($_POST['singleuser']))		: '');
	$proxy_disabled		=	((x($_POST,'proxy_disabled'))		? True						: False);
	$old_pager		=	((x($_POST,'old_pager'))		? True						: False);
	$only_tag_search	=	((x($_POST,'only_tag_search'))		? True						: False);
	$rino			=	((x($_POST,'rino'))				? intval($_POST['rino'])				: 0);
	$embedly		=	((x($_POST,'embedly'))			? notags(trim($_POST['embedly']))		: '');

	if ($a->get_path() != "")
		$diaspora_enabled = false;

	if (!$thread_allow)
		$ostatus_disabled = true;

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
	set_config('system','maxloadavg_frontend',$maxloadavg_frontend);
	set_config('system','poco_completion',$poco_completion);
	set_config('system','poco_requery_days',$poco_requery_days);
	set_config('system','poco_discovery',$poco_discovery);
	set_config('system','poco_discovery_since',$poco_discovery_since);
	set_config('system','poco_local_search',$poco_local_search);
	set_config('system','nodeinfo',$nodeinfo);
	set_config('config','sitename',$sitename);
	set_config('config','hostname',$hostname);
	set_config('config','sender_email', $sender_email);
	set_config('system','suppress_language',$suppress_language);
	set_config('system','suppress_tags',$suppress_tags);
	set_config('system','shortcut_icon',$shortcut_icon);
	set_config('system','touch_icon',$touch_icon);

	if ($banner==""){
		// don't know why, but del_config doesn't work...
		q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
			dbesc("system"),
			dbesc("banner")
		);
	} else {
		set_config('system','banner', $banner);
	}
	if ($info=="") {
		del_config('config','info');
	} else {
		set_config('config','info',$info);
	}
	set_config('system','language', $language);
	set_config('system','theme', $theme);
	if ( $theme_mobile === '---' ) {
		del_config('system','mobile-theme');
	} else {
		set_config('system','mobile-theme', $theme_mobile);
		}
		if ( $singleuser === '---' ) {
			del_config('system','singleuser');
		} else {
			set_config('system','singleuser', $singleuser);
		}
	set_config('system','maximagesize', $maximagesize);
	set_config('system','max_image_length', $maximagelength);
	set_config('system','jpeg_quality', $jpegimagequality);

	set_config('config','register_policy', $register_policy);
	set_config('system','max_daily_registrations', $daily_registrations);
	set_config('system','account_abandon_days', $abandon_days);
	set_config('config','register_text', $register_text);
	set_config('system','allowed_sites', $allowed_sites);
	set_config('system','allowed_email', $allowed_email);
	set_config('system','block_public', $block_public);
	set_config('system','publish_all', $force_publish);
	set_config('system','directory', $global_directory);
	set_config('system','thread_allow', $thread_allow);
	set_config('system','newuser_private', $newuser_private);
	set_config('system','enotify_no_content', $enotify_no_content);
	set_config('system','disable_embedded', $disable_embedded);
	set_config('system','allow_users_remote_self', $allow_users_remote_self);

	set_config('system','block_extended_register', $no_multi_reg);
	set_config('system','no_openid', $no_openid);
	set_config('system','no_regfullname', $no_regfullname);
	set_config('system','community_page_style', $community_page_style);
	set_config('system','max_author_posts_community_page', $max_author_posts_community_page);
	set_config('system','no_utf', $no_utf);
	set_config('system','verifyssl', $verifyssl);
	set_config('system','proxyuser', $proxyuser);
	set_config('system','proxy', $proxy);
	set_config('system','curl_timeout', $timeout);
	set_config('system','dfrn_only', $dfrn_only);
	set_config('system','ostatus_disabled', $ostatus_disabled);
	set_config('system','ostatus_poll_interval', $ostatus_poll_interval);
	set_config('system','diaspora_enabled', $diaspora_enabled);

	set_config('config','private_addons', $private_addons);

	set_config('system','force_ssl', $force_ssl);
	set_config('system','old_share', $old_share);
	set_config('system','hide_help', $hide_help);
	set_config('system','use_fulltext_engine', $use_fulltext_engine);
	set_config('system','itemcache', $itemcache);
	set_config('system','itemcache_duration', $itemcache_duration);
	set_config('system','max_comments', $max_comments);
	set_config('system','lockpath', $lockpath);
	set_config('system','temppath', $temppath);
	set_config('system','basepath', $basepath);
	set_config('system','proxy_disabled', $proxy_disabled);
	set_config('system','old_pager', $old_pager);
	set_config('system','only_tag_search', $only_tag_search);


	if ($rino==2 and !function_exists('mcrypt_create_iv')){
		notice(t("RINO2 needs mcrypt php extension to work."));
	} else {
		set_config('system','rino_encrypt', $rino);
	}

	set_config('system','embedly', $embedly);


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
	$lang_choices = get_avaiable_languages();

	if (strlen(get_config('system','directory_submit_url')) AND
		!strlen(get_config('system','directory'))) {
		set_config('system','directory', dirname(get_config('system','directory_submit_url')));
		del_config('system','directory_submit_url');
	}

	/* Installed themes */
	$theme_choices = array();
	$theme_choices_mobile = array();
	$theme_choices_mobile["---"] = t("No special theme for mobile devices");
	$files = glob('view/theme/*'); /**/
	if($files) {
		foreach($files as $file) {
			if (intval(file_exists($file . '/unsupported')))
				continue;

			$f = basename($file);
			$theme_name = ((file_exists($file . '/experimental')) ?  sprintf("%s - \x28Experimental\x29", $f) : $f);
			if (file_exists($file . '/mobile')) {
				$theme_choices_mobile[$f] = $theme_name;
			} else {
				$theme_choices[$f] = $theme_name;
			}
		}
	}

	/* Community page style */
	$community_page_style_choices = array(
		CP_NO_COMMUNITY_PAGE => t("No community page"),
		CP_USERS_ON_SERVER => t("Public postings from users of this site"),
		CP_GLOBAL_COMMUNITY => t("Global community page")
		);

	/* OStatus conversation poll choices */
	$ostatus_poll_choices = array(
		"-2" => t("Never"),
		"-1" => t("At post arrival"),
		"0" => t("Frequently"),
		"60" => t("Hourly"),
		"720" => t("Twice daily"),
		"1440" => t("Daily")
		);

	$poco_discovery_choices = array(
		"0" => t("Disabled"),
		"1" => t("Users"),
		"2" => t("Users, Global Contacts"),
		"3" => t("Users, Global Contacts/fallback"),
		);

	$poco_discovery_since_choices = array(
		"30" => t("One month"),
		"91" => t("Three months"),
		"182" => t("Half a year"),
		"365" => t("One year"),
		);

	/* get user names to make the install a personal install of X */
	$user_names = array();
	$user_names['---'] = t('Multi user instance');
	$users = q("SELECT username, nickname FROM `user`");
	foreach ($users as $user) {
		$user_names[$user['nickname']] = $user['username'];
	}

	/* Banner */
	$banner = get_config('system','banner');
	if($banner == false)
		$banner = '<a href="http://friendica.com"><img id="logo-img" src="images/friendica-32.png" alt="logo" /></a><span id="logo-text"><a href="http://friendica.com">Friendica</a></span>';
	$banner = htmlspecialchars($banner);
	$info = get_config('config','info');
	$info = htmlspecialchars($info);

	// Automatically create temporary paths
	get_temppath();
	get_lockpath();
	get_itemcachepath();

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

	if ($a->config['hostname'] == "")
		$a->config['hostname'] = $a->get_hostname();

	$diaspora_able = ($a->get_path() == "");

	$t = get_markup_template("admin_site.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Site'),
		'$submit' => t('Save Settings'),
		'$registration' => t('Registration'),
		'$upload' => t('File upload'),
		'$corporate' => t('Policies'),
		'$advanced' => t('Advanced'),
		'$portable_contacts' => t('Auto Discovered Contact Directory'),
		'$performance' => t('Performance'),
		'$relocate'=> t('Relocate - WARNING: advanced function. Could make this server unreachable.'),
		'$baseurl' => $a->get_baseurl(true),
		// name, label, value, help string, extra data...
		'$sitename' 		=> array('sitename', t("Site name"), $a->config['sitename'],''),
		'$hostname' 		=> array('hostname', t("Host name"), $a->config['hostname'], ""),
		'$sender_email'		=> array('sender_email', t("Sender Email"), $a->config['sender_email'], t("The email address your server shall use to send notification emails from."), "", "", "email"),
		'$banner'		=> array('banner', t("Banner/Logo"), $banner, ""),
		'$shortcut_icon'	=> array('shortcut_icon', t("Shortcut icon"), get_config('system','shortcut_icon'),  t("Link to an icon that will be used for browsers.")),
		'$touch_icon'		=> array('touch_icon', t("Touch icon"), get_config('system','touch_icon'),  t("Link to an icon that will be used for tablets and mobiles.")),
		'$info'	=> array('info',t('Additional Info'), $info, sprintf(t('For public servers: you can add additional information here that will be listed at %s/siteinfo.'), get_server())),
		'$language' 		=> array('language', t("System language"), get_config('system','language'), "", $lang_choices),
		'$theme' 		=> array('theme', t("System theme"), get_config('system','theme'), t("Default system theme - may be over-ridden by user profiles - <a href='#' id='cnftheme'>change theme settings</a>"), $theme_choices),
		'$theme_mobile' 	=> array('theme_mobile', t("Mobile system theme"), get_config('system','mobile-theme'), t("Theme for mobile devices"), $theme_choices_mobile),
		'$ssl_policy'		=> array('ssl_policy', t("SSL link policy"), (string) intval(get_config('system','ssl_policy')), t("Determines whether generated links should be forced to use SSL"), $ssl_choices),
		'$force_ssl'		=> array('force_ssl', t("Force SSL"), get_config('system','force_ssl'), t("Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.")),
		'$old_share'		=> array('old_share', t("Old style 'Share'"), get_config('system','old_share'), t("Deactivates the bbcode element 'share' for repeating items.")),
		'$hide_help'		=> array('hide_help', t("Hide help entry from navigation menu"), get_config('system','hide_help'), t("Hides the menu entry for the Help pages from the navigation menu. You can still access it calling /help directly.")),
		'$singleuser' 		=> array('singleuser', t("Single user instance"), get_config('system','singleuser'), t("Make this instance multi-user or single-user for the named user"), $user_names),
		'$maximagesize'		=> array('maximagesize', t("Maximum image size"), get_config('system','maximagesize'), t("Maximum size in bytes of uploaded images. Default is 0, which means no limits.")),
		'$maximagelength'		=> array('maximagelength', t("Maximum image length"), get_config('system','max_image_length'), t("Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.")),
		'$jpegimagequality'		=> array('jpegimagequality', t("JPEG image quality"), get_config('system','jpeg_quality'), t("Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.")),

		'$register_policy'	=> array('register_policy', t("Register policy"), $a->config['register_policy'], "", $register_choices),
		'$daily_registrations'	=> array('max_daily_registrations', t("Maximum Daily Registrations"), get_config('system', 'max_daily_registrations'), t("If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.")),
		'$register_text'	=> array('register_text', t("Register text"), $a->config['register_text'], t("Will be displayed prominently on the registration page.")),
		'$abandon_days'		=> array('abandon_days', t('Accounts abandoned after x days'), get_config('system','account_abandon_days'), t('Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.')),
		'$allowed_sites'	=> array('allowed_sites', t("Allowed friend domains"), get_config('system','allowed_sites'), t("Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains")),
		'$allowed_email'	=> array('allowed_email', t("Allowed email domains"), get_config('system','allowed_email'), t("Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains")),
		'$block_public'		=> array('block_public', t("Block public"), get_config('system','block_public'), t("Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.")),
		'$force_publish'	=> array('publish_all', t("Force publish"), get_config('system','publish_all'), t("Check to force all profiles on this site to be listed in the site directory.")),
		'$global_directory'	=> array('directory', t("Global directory URL"), get_config('system','directory'), t("URL to the global directory. If this is not set, the global directory is completely unavailable to the application.")),
		'$thread_allow'		=> array('thread_allow', t("Allow threaded items"), get_config('system','thread_allow'), t("Allow infinite level threading for items on this site.")),
		'$newuser_private'	=> array('newuser_private', t("Private posts by default for new users"), get_config('system','newuser_private'), t("Set default post permissions for all new members to the default privacy group rather than public.")),
		'$enotify_no_content'	=> array('enotify_no_content', t("Don't include post content in email notifications"), get_config('system','enotify_no_content'), t("Don't include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.")),
		'$private_addons'	=> array('private_addons', t("Disallow public access to addons listed in the apps menu."), get_config('config','private_addons'), t("Checking this box will restrict addons listed in the apps menu to members only.")),
		'$disable_embedded'	=> array('disable_embedded', t("Don't embed private images in posts"), get_config('system','disable_embedded'), t("Don't replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.")),
		'$allow_users_remote_self'	=> array('allow_users_remote_self', t('Allow Users to set remote_self'), get_config('system','allow_users_remote_self'), t('With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.')),
		'$no_multi_reg'		=> array('no_multi_reg', t("Block multiple registrations"),  get_config('system','block_extended_register'), t("Disallow users to register additional accounts for use as pages.")),
		'$no_openid'		=> array('no_openid', t("OpenID support"), !get_config('system','no_openid'), t("OpenID support for registration and logins.")),
		'$no_regfullname'	=> array('no_regfullname', t("Fullname check"), !get_config('system','no_regfullname'), t("Force users to register with a space between firstname and lastname in Full name, as an antispam measure")),
		'$no_utf'		=> array('no_utf', t("UTF-8 Regular expressions"), !get_config('system','no_utf'), t("Use PHP UTF8 regular expressions")),
		'$community_page_style' => array('community_page_style', t("Community Page Style"), get_config('system','community_page_style'), t("Type of community page to show. 'Global community' shows every public posting from an open distributed network that arrived on this server."), $community_page_style_choices),
		'$max_author_posts_community_page' => array('max_author_posts_community_page', t("Posts per user on community page"), get_config('system','max_author_posts_community_page'), t("The maximum number of posts per user on the community page. (Not valid for 'Global Community')")),
		'$ostatus_disabled' 	=> array('ostatus_disabled', t("Enable OStatus support"), !get_config('system','ostatus_disabled'), t("Provide built-in OStatus \x28StatusNet, GNU Social etc.\x29 compatibility. All communications in OStatus are public, so privacy warnings will be occasionally displayed.")),
		'$ostatus_poll_interval'	=> array('ostatus_poll_interval', t("OStatus conversation completion interval"), (string) intval(get_config('system','ostatus_poll_interval')), t("How often shall the poller check for new entries in OStatus conversations? This can be a very ressource task."), $ostatus_poll_choices),
		'$ostatus_not_able'	=> t("OStatus support can only be enabled if threading is enabled."),
		'$diaspora_able'	=> $diaspora_able,
		'$diaspora_not_able'	=> t("Diaspora support can't be enabled because Friendica was installed into a sub directory."),
		'$diaspora_enabled' 	=> array('diaspora_enabled', t("Enable Diaspora support"), get_config('system','diaspora_enabled'), t("Provide built-in Diaspora network compatibility.")),
		'$dfrn_only'        	=> array('dfrn_only', t('Only allow Friendica contacts'), get_config('system','dfrn_only'), t("All contacts must use Friendica protocols. All other built-in communication protocols disabled.")),
		'$verifyssl' 		=> array('verifyssl', t("Verify SSL"), get_config('system','verifyssl'), t("If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.")),
		'$proxyuser'		=> array('proxyuser', t("Proxy user"), get_config('system','proxyuser'), ""),
		'$proxy'		=> array('proxy', t("Proxy URL"), get_config('system','proxy'), ""),
		'$timeout'		=> array('timeout', t("Network timeout"), (x(get_config('system','curl_timeout'))?get_config('system','curl_timeout'):60), t("Value is in seconds. Set to 0 for unlimited (not recommended).")),
		'$delivery_interval'	=> array('delivery_interval', t("Delivery interval"), (x(get_config('system','delivery_interval'))?get_config('system','delivery_interval'):2), t("Delay background delivery processes by this many seconds to reduce system load. Recommend: 4-5 for shared hosts, 2-3 for virtual private servers. 0-1 for large dedicated servers.")),
		'$poll_interval'	=> array('poll_interval', t("Poll interval"), (x(get_config('system','poll_interval'))?get_config('system','poll_interval'):2), t("Delay background polling processes by this many seconds to reduce system load. If 0, use delivery interval.")),
		'$maxloadavg'		=> array('maxloadavg', t("Maximum Load Average"), ((intval(get_config('system','maxloadavg')) > 0)?get_config('system','maxloadavg'):50), t("Maximum system load before delivery and poll processes are deferred - default 50.")),
		'$maxloadavg_frontend'	=> array('maxloadavg_frontend', t("Maximum Load Average (Frontend)"), ((intval(get_config('system','maxloadavg_frontend')) > 0)?get_config('system','maxloadavg_frontend'):50), t("Maximum system load before the frontend quits service - default 50.")),

		'$poco_completion'	=> array('poco_completion', t("Periodical check of global contacts"), get_config('system','poco_completion'), t("If enabled, the global contacts are checked periodically for missing or outdated data and the vitality of the contacts and servers.")),
		'$poco_requery_days'	=> array('poco_requery_days', t("Days between requery"), get_config('system','poco_requery_days'), t("Number of days after which a server is requeried for his contacts.")),
		'$poco_discovery'	=> array('poco_discovery', t("Discover contacts from other servers"), (string) intval(get_config('system','poco_discovery')), t("Periodically query other servers for contacts. You can choose between 'users': the users on the remote system, 'Global Contacts': active contacts that are known on the system. The fallback is meant for Redmatrix servers and older friendica servers, where global contacts weren't available. The fallback increases the server load, so the recommened setting is 'Users, Global Contacts'."), $poco_discovery_choices),
		'$poco_discovery_since'	=> array('poco_discovery_since', t("Timeframe for fetching global contacts"), (string) intval(get_config('system','poco_discovery_since')), t("When the discovery is activated, this value defines the timeframe for the activity of the global contacts that are fetched from other servers."), $poco_discovery_since_choices),
		'$poco_local_search'	=> array('poco_local_search', t("Search the local directory"), get_config('system','poco_local_search'), t("Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.")),

		'$nodeinfo'		=> array('nodeinfo', t("Publish server information"), get_config('system','nodeinfo'), t("If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href='http://the-federation.info/'>the-federation.info</a> for details.")),

		'$use_fulltext_engine'	=> array('use_fulltext_engine', t("Use MySQL full text engine"), get_config('system','use_fulltext_engine'), t("Activates the full text engine. Speeds up search - but can only search for four and more characters.")),
		'$suppress_language'	=> array('suppress_language', t("Suppress Language"), get_config('system','suppress_language'), t("Suppress language information in meta information about a posting.")),
		'$suppress_tags'	=> array('suppress_tags', t("Suppress Tags"), get_config('system','suppress_tags'), t("Suppress showing a list of hashtags at the end of the posting.")),
		'$itemcache'		=> array('itemcache', t("Path to item cache"), get_config('system','itemcache'), t("The item caches buffers generated bbcode and external images.")),
		'$itemcache_duration' 	=> array('itemcache_duration', t("Cache duration in seconds"), get_config('system','itemcache_duration'), t("How long should the cache files be hold? Default value is 86400 seconds (One day). To disable the item cache, set the value to -1.")),
		'$max_comments' 	=> array('max_comments', t("Maximum numbers of comments per post"), get_config('system','max_comments'), t("How much comments should be shown for each post? Default value is 100.")),
		'$lockpath'		=> array('lockpath', t("Path for lock file"), get_config('system','lockpath'), t("The lock file is used to avoid multiple pollers at one time. Only define a folder here.")),
		'$temppath'		=> array('temppath', t("Temp path"), get_config('system','temppath'), t("If you have a restricted system where the webserver can't access the system temp path, enter another path here.")),
		'$basepath'		=> array('basepath', t("Base path to installation"), get_config('system','basepath'), t("If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.")),
		'$proxy_disabled'	=> array('proxy_disabled', t("Disable picture proxy"), get_config('system','proxy_disabled'), t("The picture proxy increases performance and privacy. It shouldn't be used on systems with very low bandwith.")),
		'$old_pager'		=> array('old_pager', t("Enable old style pager"), get_config('system','old_pager'), t("The old style pager has page numbers but slows down massively the page speed.")),
		'$only_tag_search'	=> array('only_tag_search', t("Only search in tags"), get_config('system','only_tag_search'), t("On large systems the text search can slow down the system extremely.")),

		'$relocate_url'		=> array('relocate_url', t("New base url"), $a->get_baseurl(), t("Change base url for this server. Sends relocate message to all DFRN contacts of all users.")),

		'$rino' 		=> array('rino', t("RINO Encryption"), intval(get_config('system','rino_encrypt')), t("Encryption layer between nodes."), array("Disabled", "RINO1 (deprecated)", "RINO2")),
		'$embedly' 		=> array('embedly', t("Embedly API key"), get_config('system','embedly'), t("<a href='http://embed.ly'>Embedly</a> is used to fetch additional data for web pages. This is an optional parameter.")),

		'$form_security_token' => get_form_security_token("admin_site")

	));

}


function admin_page_dbsync(&$a) {

	$o = '';

	if($a->argc > 3 && intval($a->argv[3]) && $a->argv[2] === 'mark') {
		set_config('database', 'update_' . intval($a->argv[3]), 'success');
		$curr = get_config('system','build');
		if(intval($curr) == intval($a->argv[3]))
			set_config('system','build',intval($curr) + 1);
		info( t('Update has been marked successful') . EOL);
		goaway($a->get_baseurl(true) . '/admin/dbsync');
	}

	if(($a->argc > 2) AND (intval($a->argv[2]) OR ($a->argv[2] === 'check'))) {
		require_once("include/dbstructure.php");
		$retval = update_structure(false, true);
		if (!$retval) {
			$o .= sprintf(t("Database structure update %s was successfully applied."), DB_UPDATE_VERSION)."<br />";
			set_config('database', 'dbupdate_'.DB_UPDATE_VERSION, 'success');
		} else
			$o .= sprintf(t("Executing of database structure update %s failed with error: %s"),
					DB_UPDATE_VERSION, $retval)."<br />";
		if ($a->argv[2] === 'check')
			return $o;
	}

	if ($a->argc > 2 && intval($a->argv[2])) {
		require_once('update.php');
		$func = 'update_' . intval($a->argv[2]);
		if(function_exists($func)) {
			$retval = $func();
			if($retval === UPDATE_FAILED) {
				$o .= sprintf(t("Executing %s failed with error: %s"), $func, $retval);
			}
			elseif($retval === UPDATE_SUCCESS) {
				$o .= sprintf(t('Update %s was successfully applied.', $func));
				set_config('database',$func, 'success');
			}
			else
				$o .= sprintf(t('Update %s did not return a status. Unknown if it succeeded.'), $func);
		} else {
			$o .= sprintf(t('There was no additional update function %s that needed to be called.'), $func)."<br />";
			set_config('database',$func, 'success');
		}
		return $o;
	}

	$failed = array();
	$r = q("select k, v from config where `cat` = 'database' ");
	if(count($r)) {
		foreach($r as $rr) {
			$upd = intval(substr($rr['k'],7));
			if($upd < 1139 || $rr['v'] === 'success')
				continue;
			$failed[] = $upd;
		}
	}
	if(! count($failed)) {
		$o = replace_macros(get_markup_template('structure_check.tpl'),array(
			'$base' => $a->get_baseurl(true),
			'$banner' => t('No failed updates.'),
			'$check' => t('Check database structure'),
		));
	} else {
		$o = replace_macros(get_markup_template('failed_updates.tpl'),array(
			'$base' => $a->get_baseurl(true),
			'$banner' => t('Failed Updates'),
			'$desc' => t('This does not include updates prior to 1139, which did not return a status.'),
			'$mark' => t('Mark success (if update was manually applied)'),
			'$apply' => t('Attempt to execute this update step automatically'),
			'$failed' => $failed
		));
	}

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
	$nu_name = ( x($_POST, 'new_user_name') ? $_POST['new_user_name'] : '');
	$nu_nickname = ( x($_POST, 'new_user_nickname') ? $_POST['new_user_nickname'] : '');
	$nu_email = ( x($_POST, 'new_user_email') ? $_POST['new_user_email'] : '');

	check_form_security_token_redirectOnErr('/admin/users', 'admin_users');

	if (!($nu_name==="") && !($nu_email==="") && !($nu_nickname==="")) {
		require_once('include/user.php');

		$result = create_user( array('username'=>$nu_name, 'email'=>$nu_email, 'nickname'=>$nu_nickname, 'verified'=>1)  );
		if(! $result['success']) {
			notice($result['message']);
			return;
		}
		$nu = $result['user'];
		$preamble = deindent(t('
			Dear %1$s,
				the administrator of %2$s has set up an account for you.'));
		$body = deindent(t('
			The login details are as follows:

			Site Location:	%1$s
			Login Name:		%2$s
			Password:		%3$s

			You may change your password from your account "Settings" page after logging
			in.

			Please take a few moments to review the other account settings on that page.

			You may also wish to add some basic information to your default profile
			(on the "Profiles" page) so that other people can easily find you.

			We recommend setting your full name, adding a profile photo,
			adding some profile "keywords" (very useful in making new friends) - and
			perhaps what country you live in; if you do not wish to be more specific
			than that.

			We fully respect your right to privacy, and none of these items are necessary.
			If you are new and do not know anybody here, they may help
			you to make some new and interesting friends.

			Thank you and welcome to %4$s.'));

		$preamble = sprintf($preamble, $nu['username'], $a->config['sitename']);
		$body = sprintf($body, $a->get_baseurl(), $nu['email'], $result['password'], $a->config['sitename']);

		notification(array(
			'type' => "SYSTEM_EMAIL",
			'to_email' => $nu['email'],
			'subject'=> sprintf( t('Registration details for %s'), $a->config['sitename']),
			'preamble'=> $preamble,
			'body' => $body));

	}

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
		$user = q("SELECT username, blocked FROM `user` WHERE `uid`=%d", intval($uid));
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


	$users = q("SELECT `user` . * , `contact`.`name` , `contact`.`url` , `contact`.`micro`, `lastitem`.`lastitem_date`, `user`.`account_expired`
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

	$adminlist = explode(",", str_replace(" ", "", $a->config['admin_email']));
	$_setup_users = function ($e) use ($adminlist){
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
		//$e['is_admin'] = ($e['email'] === $a->config['admin_email']);
		$e['is_admin'] = in_array($e['email'], $adminlist);
		$e['is_deletable'] = (intval($e['uid']) != local_user());
		$e['deleted'] = ($e['account_removed']?relative_date($e['account_expires_on']):False);
		return $e;
	};
	$users = array_map($_setup_users, $users);


	// Get rid of dashes in key names, Smarty3 can't handle them
	// and extracting deleted users

	$tmp_users = Array();
	$deleted = Array();

	while(count($users)) {
		$new_user = Array();
		foreach( array_pop($users) as $k => $v) {
			$k = str_replace('-','_',$k);
			$new_user[$k] = $v;
		}
		if($new_user['deleted']) {
			array_push($deleted, $new_user);
		}
		else {
			array_push($tmp_users, $new_user);
		}
	}
	//Reversing the two array, and moving $tmp_users to $users
	array_reverse($deleted);
	while(count($tmp_users)) {
		array_push($users, array_pop($tmp_users));
	}

	$t = get_markup_template("admin_users.tpl");
	$o = replace_macros($t, array(
		// strings //
		'$title' => t('Administration'),
		'$page' => t('Users'),
		'$submit' => t('Add User'),
		'$select_all' => t('select all'),
		'$h_pending' => t('User registrations waiting for confirm'),
		'$h_deleted' => t('User waiting for permanent deletion'),
		'$th_pending' => array( t('Request date'), t('Name'), t('Email') ),
		'$no_pending' =>  t('No registrations.'),
		'$approve' => t('Approve'),
		'$deny' => t('Deny'),
		'$delete' => t('Delete'),
		'$block' => t('Block'),
		'$unblock' => t('Unblock'),
		'$siteadmin' => t('Site admin'),
		'$accountexpired' => t('Account expired'),

		'$h_users' => t('Users'),
		'$h_newuser' => t('New User'),
		'$th_deleted' => array( t('Name'), t('Email'), t('Register date'), t('Last login'), t('Last item'), t('Deleted since') ),
		'$th_users' => array( t('Name'), t('Email'), t('Register date'), t('Last login'), t('Last item'),  t('Account') ),

		'$confirm_delete_multi' => t('Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'),
		'$confirm_delete' => t('The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'),

		'$form_security_token' => get_form_security_token("admin_users"),

		// values //
		'$baseurl' => $a->get_baseurl(true),

		'$pending' => $pending,
		'deleted' => $deleted,
		'$users' => $users,
		'$newusername'  => array('new_user_name', t("Name"), '', t("Name of the new user.")),
		'$newusernickname'  => array('new_user_nickname', t("Nickname"), '', t("Nickname of the new user.")),
		'$newuseremail'  => array('new_user_email', t("Email"), '', t("Email address of the new user."), '', '', 'email'),
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

	if (x($_GET,"a") && $_GET['a']=="r"){
		check_form_security_token_redirectOnErr($a->get_baseurl().'/admin/plugins', 'admin_themes', 't');
		reload_plugins();
		info("Plugins reloaded");
		goaway($a->get_baseurl().'/admin/plugins');
	}

	$plugins = array();
	$files = glob("addon/*/"); /* */
	if($files) {
		foreach($files as $file) {
			if (is_dir($file)){
				list($tmp, $id)=array_map("trim", explode("/",$file));
				$info = get_plugin_info($id);
				$show_plugin = true;

				// If the addon is unsupported, then only show it, when it is enabled
				if ((strtolower($info["status"]) == "unsupported") AND !in_array($id,  $a->plugins))
					$show_plugin = false;

				// Override the above szenario, when the admin really wants to see outdated stuff
				if (get_config("system", "show_unsupported_addons"))
					$show_plugin = true;

				if ($show_plugin)
					$plugins[] = array($id, (in_array($id,  $a->plugins)?"on":"off") , $info);
			}
		}
	}

	$t = get_markup_template("admin_plugins.tpl");
	return replace_macros($t, array(
		'$title' => t('Administration'),
		'$page' => t('Plugins'),
		'$submit' => t('Save Settings'),
		'$reload' => t('Reload active plugins'),
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
	$files = glob('view/theme/*'); /* */
	if($files) {
		foreach($files as $file) {
			$f = basename($file);
			$is_experimental = intval(file_exists($file . '/experimental'));
			$is_supported = 1-(intval(file_exists($file . '/unsupported')));
			$is_allowed = intval(in_array($f,$allowed_themes));

			if ($is_allowed OR $is_supported OR get_config("system", "show_unsupported_themes"))
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
			if($result) {
				install_theme($theme);
				info( sprintf('Theme %s enabled.',$theme));
			}
			else {
				uninstall_theme($theme);
				info( sprintf('Theme %s disabled.',$theme));
			}

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
			function __get_theme_admin_form(&$a, $theme) {
				$orig_theme = $a->theme;
				$orig_page = $a->page;
				$orig_session_theme = $_SESSION['theme'];
				require_once("view/theme/$theme/theme.php");
				require_once("view/theme/$theme/config.php");
				$_SESSION['theme'] = $theme;


				$init = $theme."_init";
				if(function_exists($init)) $init($a);
				if(function_exists("theme_admin")){
					$admin_form = theme_admin($a);
				}

				$_SESSION['theme'] = $orig_session_theme;
				$a->theme = $orig_theme;
				$a->page = $orig_page;
				return $admin_form;
			}
			$admin_form = __get_theme_admin_form($a, $theme);
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


	// reload active themes
	if (x($_GET,"a") && $_GET['a']=="r"){
		check_form_security_token_redirectOnErr($a->get_baseurl().'/admin/themes', 'admin_themes', 't');
		if ($themes) {
			foreach($themes as $th) {
				if ($th['allowed']) {
					uninstall_theme($th['name']);
					install_theme($th['name']);
				}
			}
		}
		info("Themes reloaded");
		goaway($a->get_baseurl().'/admin/themes');
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
		'$submit' => t('Save Settings'),
		'$reload' => t('Reload active themes'),
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
		'$submit' => t('Save Settings'),
		'$clear' => t('Clear'),
		'$data' => $data,
		'$baseurl' => $a->get_baseurl(true),
		'$logname' =>  get_config('system','logfile'),

									// name, label, value, help string, extra data...
		'$debugging' 		=> array('debugging', t("Enable Debugging"),get_config('system','debugging'), ""),
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
		'$remotefile'=>array('remotefile','', $u['2'],''),
	));

}
