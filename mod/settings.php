<?php

require_once('include/group.php');
require_once('include/socgraph.php');

function get_theme_config_file($theme){
	$a = get_app();
	$base_theme = $a->theme_info['extends'];

	if (file_exists("view/theme/$theme/config.php")){
		return "view/theme/$theme/config.php";
	}
	if (file_exists("view/theme/$base_theme/config.php")){
		return "view/theme/$base_theme/config.php";
	}
	return null;
}

function settings_init(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	// APC deactivated, since there are problems with PHP 5.5
	//if (function_exists("apc_delete")) {
	//	$toDelete = new APCIterator('user', APC_ITER_VALUE);
	//	apc_delete($toDelete);
	//}

	// These lines provide the javascript needed by the acl selector

	$tpl = get_markup_template("settings-head.tpl");
	$a->page['htmlhead'] .= replace_macros($tpl,array(
		'$ispublic' => t('everybody')
	));



	$tabs = array(
		array(
			'label'	=> t('Account'),
			'url' 	=> 'settings',
			'selected'	=>  (($a->argc == 1) && ($a->argv[0] === 'settings')?'active':''),
			'accesskey' => 'o',
		),
	);

	if(get_features()) {
		$tabs[] =	array(
					'label'	=> t('Additional features'),
					'url' 	=> 'settings/features',
					'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'features') ? 'active' : ''),
					'accesskey' => 't',
				);
	}

	$tabs[] =	array(
		'label'	=> t('Display'),
		'url' 	=> 'settings/display',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'display')?'active':''),
		'accesskey' => 'i',
	);

	$tabs[] =	array(
		'label'	=> t('Social Networks'),
		'url' 	=> 'settings/connectors',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'connectors')?'active':''),
		'accesskey' => 'w',
	);

	$tabs[] =	array(
		'label'	=> t('Plugins'),
		'url' 	=> 'settings/addon',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'addon')?'active':''),
		'accesskey' => 'l',
	);

	$tabs[] =	array(
		'label'	=> t('Delegations'),
		'url' 	=> 'delegate',
		'selected'	=> (($a->argc == 1) && ($a->argv[0] === 'delegate')?'active':''),
		'accesskey' => 'd',
	);

	$tabs[] =	array(
		'label' => t('Connected apps'),
		'url' => 'settings/oauth',
		'selected' => (($a->argc > 1) && ($a->argv[1] === 'oauth')?'active':''),
		'accesskey' => 'b',
	);

	$tabs[] =	array(
		'label' => t('Export personal data'),
		'url' => 'uexport',
		'selected' => (($a->argc == 1) && ($a->argv[0] === 'uexport')?'active':''),
		'accesskey' => 'e',
	);

	$tabs[] =	array(
		'label' => t('Remove account'),
		'url' => 'removeme',
		'selected' => (($a->argc == 1) && ($a->argv[0] === 'removeme')?'active':''),
		'accesskey' => 'r',
	);


	$tabtpl = get_markup_template("generic_links_widget.tpl");
	$a->page['aside'] = replace_macros($tabtpl, array(
		'$title' => t('Settings'),
		'$class' => 'settings-widget',
		'$items' => $tabs,
	));

}


function settings_post(App $a) {

	if (! local_user()) {
		return;
	}

	if (x($_SESSION,'submanage') && intval($_SESSION['submanage'])) {
		return;
	}

	if (count($a->user) && x($a->user,'uid') && $a->user['uid'] != local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$old_page_flags = $a->user['page-flags'];

	if (($a->argc > 1) && ($a->argv[1] === 'oauth') && x($_POST,'remove')) {
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');

		$key = $_POST['remove'];
		q("DELETE FROM tokens WHERE id='%s' AND uid=%d",
			dbesc($key),
			local_user());
		goaway(App::get_baseurl(true)."/settings/oauth/");
		return;
	}

	if (($a->argc > 2) && ($a->argv[1] === 'oauth')  && ($a->argv[2] === 'edit'||($a->argv[2] === 'add')) && x($_POST,'submit')) {

		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');

		$name   	= ((x($_POST,'name')) ? $_POST['name'] : '');
		$key		= ((x($_POST,'key')) ? $_POST['key'] : '');
		$secret		= ((x($_POST,'secret')) ? $_POST['secret'] : '');
		$redirect	= ((x($_POST,'redirect')) ? $_POST['redirect'] : '');
		$icon		= ((x($_POST,'icon')) ? $_POST['icon'] : '');
		if ($name=="" || $key=="" || $secret==""){
			notice(t("Missing some important data!"));

		} else {
			if ($_POST['submit']==t("Update")){
				$r = q("UPDATE clients SET
							client_id='%s',
							pw='%s',
							name='%s',
							redirect_uri='%s',
							icon='%s',
							uid=%d
						WHERE client_id='%s'",
						dbesc($key),
						dbesc($secret),
						dbesc($name),
						dbesc($redirect),
						dbesc($icon),
						local_user(),
						dbesc($key));
			} else {
				$r = q("INSERT INTO clients
							(client_id, pw, name, redirect_uri, icon, uid)
						VALUES ('%s','%s','%s','%s','%s',%d)",
						dbesc($key),
						dbesc($secret),
						dbesc($name),
						dbesc($redirect),
						dbesc($icon),
						local_user());
			}
		}
		goaway(App::get_baseurl(true)."/settings/oauth/");
		return;
	}

	if(($a->argc > 1) && ($a->argv[1] == 'addon')) {
		check_form_security_token_redirectOnErr('/settings/addon', 'settings_addon');

		call_hooks('plugin_settings_post', $_POST);
		return;
	}

	if(($a->argc > 1) && ($a->argv[1] == 'connectors')) {

		check_form_security_token_redirectOnErr('/settings/connectors', 'settings_connectors');

		if(x($_POST, 'general-submit')) {
			set_pconfig(local_user(), 'system', 'no_intelligent_shortening', intval($_POST['no_intelligent_shortening']));
			set_pconfig(local_user(), 'system', 'ostatus_autofriend', intval($_POST['snautofollow']));
			set_pconfig(local_user(), 'ostatus', 'default_group', $_POST['group-selection']);
			set_pconfig(local_user(), 'ostatus', 'legacy_contact', $_POST['legacy_contact']);
		} elseif(x($_POST, 'imap-submit')) {

			$mail_server       = ((x($_POST,'mail_server')) ? $_POST['mail_server'] : '');
			$mail_port         = ((x($_POST,'mail_port')) ? $_POST['mail_port'] : '');
			$mail_ssl          = ((x($_POST,'mail_ssl')) ? strtolower(trim($_POST['mail_ssl'])) : '');
			$mail_user         = ((x($_POST,'mail_user')) ? $_POST['mail_user'] : '');
			$mail_pass         = ((x($_POST,'mail_pass')) ? trim($_POST['mail_pass']) : '');
			$mail_action       = ((x($_POST,'mail_action')) ? trim($_POST['mail_action']) : '');
			$mail_movetofolder = ((x($_POST,'mail_movetofolder')) ? trim($_POST['mail_movetofolder']) : '');
			$mail_replyto      = ((x($_POST,'mail_replyto')) ? $_POST['mail_replyto'] : '');
			$mail_pubmail      = ((x($_POST,'mail_pubmail')) ? $_POST['mail_pubmail'] : '');


			$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);
			if(get_config('system','dfrn_only'))
				$mail_disabled = 1;

			if(! $mail_disabled) {
				$failed = false;
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if (! dbm::is_result($r)) {
					q("INSERT INTO `mailacct` (`uid`) VALUES (%d)",
						intval(local_user())
					);
				}
				if(strlen($mail_pass)) {
					$pass = '';
					openssl_public_encrypt($mail_pass,$pass,$a->user['pubkey']);
					q("UPDATE `mailacct` SET `pass` = '%s' WHERE `uid` = %d",
						dbesc(bin2hex($pass)),
						intval(local_user())
					);
				}
				$r = q("UPDATE `mailacct` SET `server` = '%s', `port` = %d, `ssltype` = '%s', `user` = '%s',
					`action` = %d, `movetofolder` = '%s',
					`mailbox` = 'INBOX', `reply_to` = '%s', `pubmail` = %d WHERE `uid` = %d",
					dbesc($mail_server),
					intval($mail_port),
					dbesc($mail_ssl),
					dbesc($mail_user),
					intval($mail_action),
					dbesc($mail_movetofolder),
					dbesc($mail_replyto),
					intval($mail_pubmail),
					intval(local_user())
				);
				logger("mail: updating mailaccount. Response: ".print_r($r, true));
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if (dbm::is_result($r)) {
					$eacct = $r[0];
					require_once('include/email.php');
					$mb = construct_mailbox_name($eacct);
					if(strlen($eacct['server'])) {
						$dcrpass = '';
						openssl_private_decrypt(hex2bin($eacct['pass']),$dcrpass,$a->user['prvkey']);
						$mbox = email_connect($mb,$mail_user,$dcrpass);
						unset($dcrpass);
						if(! $mbox) {
							$failed = true;
							notice( t('Failed to connect with email account using the settings provided.') . EOL);
						}
					}
				}
				if(! $failed)
					info( t('Email settings updated.') . EOL);
			}
		}

		call_hooks('connector_settings_post', $_POST);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'features')) {
		check_form_security_token_redirectOnErr('/settings/features', 'settings_features');
		foreach($_POST as $k => $v) {
			if(strpos($k,'feature_') === 0) {
				set_pconfig(local_user(),'feature',substr($k,8),((intval($v)) ? 1 : 0));
			}
		}
		info( t('Features updated') . EOL);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'display')) {
		check_form_security_token_redirectOnErr('/settings/display', 'settings_display');

		$theme             = x($_POST, 'theme')             ? notags(trim($_POST['theme']))        : $a->user['theme'];
		$mobile_theme      = x($_POST, 'mobile_theme')      ? notags(trim($_POST['mobile_theme'])) : '';
		$nosmile           = x($_POST, 'nosmile')           ? intval($_POST['nosmile'])            : 0;
		$first_day_of_week = x($_POST, 'first_day_of_week') ? intval($_POST['first_day_of_week'])  : 0;
		$noinfo            = x($_POST, 'noinfo')            ? intval($_POST['noinfo'])             : 0;
		$infinite_scroll   = x($_POST, 'infinite_scroll')   ? intval($_POST['infinite_scroll'])    : 0;
		$no_auto_update    = x($_POST, 'no_auto_update')    ? intval($_POST['no_auto_update'])     : 0;
		$bandwidth_saver   = x($_POST, 'bandwidth_saver')   ? intval($_POST['bandwidth_saver'])    : 0;
		$nowarn_insecure   = x($_POST, 'nowarn_insecure')   ? intval($_POST['nowarn_insecure'])    : 0;
		$browser_update    = x($_POST, 'browser_update')    ? intval($_POST['browser_update'])     : 0;
		if ($browser_update != -1) {
			$browser_update = $browser_update * 1000;
			if ($browser_update < 10000)
				$browser_update = 10000;
		}

		$itemspage_network = x($_POST,'itemspage_network')  ? intval($_POST['itemspage_network'])  : 40;
		if ($itemspage_network > 100) {
			$itemspage_network = 100;
		}
		$itemspage_mobile_network = x($_POST,'itemspage_mobile_network') ? intval($_POST['itemspage_mobile_network']) : 20;
		if ($itemspage_mobile_network > 100) {
			$itemspage_mobile_network = 100;
		}

		if($mobile_theme !== '') {
			set_pconfig(local_user(),'system','mobile_theme',$mobile_theme);
		}

		set_pconfig(local_user(), 'system', 'nowarn_insecure'         , $nowarn_insecure);
		set_pconfig(local_user(), 'system', 'update_interval'         , $browser_update);
		set_pconfig(local_user(), 'system', 'itemspage_network'       , $itemspage_network);
		set_pconfig(local_user(), 'system', 'itemspage_mobile_network', $itemspage_mobile_network);
		set_pconfig(local_user(), 'system', 'no_smilies'              , $nosmile);
		set_pconfig(local_user(), 'system', 'first_day_of_week'       , $first_day_of_week);
		set_pconfig(local_user(), 'system', 'ignore_info'             , $noinfo);
		set_pconfig(local_user(), 'system', 'infinite_scroll'         , $infinite_scroll);
		set_pconfig(local_user(), 'system', 'no_auto_update'          , $no_auto_update);
		set_pconfig(local_user(), 'system', 'bandwidth_saver'         , $bandwidth_saver);

		if ($theme == $a->user['theme']) {
			// call theme_post only if theme has not been changed
			if (($themeconfigfile = get_theme_config_file($theme)) != null) {
				require_once($themeconfigfile);
				theme_post($a);
			}
		}


		$r = q("UPDATE `user` SET `theme` = '%s' WHERE `uid` = %d",
				dbesc($theme),
				intval(local_user())
		);

		call_hooks('display_settings_post', $_POST);
		goaway('settings/display' );
		return; // NOTREACHED
	}

	check_form_security_token_redirectOnErr('/settings', 'settings');

	if (x($_POST,'resend_relocate')) {
		proc_run(PRIORITY_HIGH, 'include/notifier.php', 'relocate', local_user());
		info(t("Relocate message has been send to your contacts"));
		goaway('settings');
	}

	call_hooks('settings_post', $_POST);

	if((x($_POST,'password')) || (x($_POST,'confirm'))) {

		$newpass = $_POST['password'];
		$confirm = $_POST['confirm'];
		$oldpass = hash('whirlpool', $_POST['opassword']);

		$err = false;
		if($newpass != $confirm ) {
			notice( t('Passwords do not match. Password unchanged.') . EOL);
			$err = true;
		}

		if((! x($newpass)) || (! x($confirm))) {
			notice( t('Empty passwords are not allowed. Password unchanged.') . EOL);
			$err = true;
        }

        //  check if the old password was supplied correctly before
        //  changing it to the new value
        $r = q("SELECT `password` FROM `user`WHERE `uid` = %d LIMIT 1", intval(local_user()));
        if( $oldpass != $r[0]['password'] ) {
            notice( t('Wrong password.') . EOL);
            $err = true;
        }

		if(! $err) {
			$password = hash('whirlpool',$newpass);
			$r = q("UPDATE `user` SET `password` = '%s' WHERE `uid` = %d",
				dbesc($password),
				intval(local_user())
			);
			if($r)
				info( t('Password changed.') . EOL);
			else
				notice( t('Password update failed. Please try again.') . EOL);
		}
	}


	$username         = ((x($_POST,'username'))   ? notags(trim($_POST['username']))     : '');
	$email            = ((x($_POST,'email'))      ? notags(trim($_POST['email']))        : '');
	$timezone         = ((x($_POST,'timezone'))   ? notags(trim($_POST['timezone']))     : '');
	$language         = ((x($_POST,'language'))   ? notags(trim($_POST['language']))     : '');

	$defloc           = ((x($_POST,'defloc'))     ? notags(trim($_POST['defloc']))       : '');
	$openid           = ((x($_POST,'openid_url')) ? notags(trim($_POST['openid_url']))   : '');
	$maxreq           = ((x($_POST,'maxreq'))     ? intval($_POST['maxreq'])             : 0);
	$expire           = ((x($_POST,'expire'))     ? intval($_POST['expire'])             : 0);
	$def_gid          = ((x($_POST,'group-selection')) ? intval($_POST['group-selection']) : 0);


	$expire_items     = ((x($_POST,'expire_items')) ? intval($_POST['expire_items'])	 : 0);
	$expire_notes     = ((x($_POST,'expire_notes')) ? intval($_POST['expire_notes'])	 : 0);
	$expire_starred   = ((x($_POST,'expire_starred')) ? intval($_POST['expire_starred']) : 0);
	$expire_photos    = ((x($_POST,'expire_photos'))? intval($_POST['expire_photos'])	 : 0);
	$expire_network_only    = ((x($_POST,'expire_network_only'))? intval($_POST['expire_network_only'])	 : 0);

	$allow_location   = (((x($_POST,'allow_location')) && (intval($_POST['allow_location']) == 1)) ? 1: 0);
	$publish          = (((x($_POST,'profile_in_directory')) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
	$net_publish      = (((x($_POST,'profile_in_netdirectory')) && (intval($_POST['profile_in_netdirectory']) == 1)) ? 1: 0);
	$old_visibility   = (((x($_POST,'visibility')) && (intval($_POST['visibility']) == 1)) ? 1 : 0);
	$account_type     = (((x($_POST,'account-type')) && (intval($_POST['account-type']))) ? intval($_POST['account-type']) : 0);
	$page_flags       = (((x($_POST,'page-flags')) && (intval($_POST['page-flags']))) ? intval($_POST['page-flags']) : 0);
	$blockwall        = (((x($_POST,'blockwall')) && (intval($_POST['blockwall']) == 1)) ? 0: 1); // this setting is inverted!
	$blocktags        = (((x($_POST,'blocktags')) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
	$unkmail          = (((x($_POST,'unkmail')) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
	$cntunkmail       = ((x($_POST,'cntunkmail')) ? intval($_POST['cntunkmail']) : 0);
	$suggestme        = ((x($_POST,'suggestme')) ? intval($_POST['suggestme'])  : 0);
	$hide_friends     = (($_POST['hide-friends'] == 1) ? 1: 0);
	$hidewall         = (($_POST['hidewall'] == 1) ? 1: 0);
	$post_newfriend   = (($_POST['post_newfriend'] == 1) ? 1: 0);
	$post_joingroup   = (($_POST['post_joingroup'] == 1) ? 1: 0);
	$post_profilechange   = (($_POST['post_profilechange'] == 1) ? 1: 0);

	$email_textonly   = (($_POST['email_textonly'] == 1) ? 1 : 0);

	$notify = 0;

	if(x($_POST,'notify1'))
		$notify += intval($_POST['notify1']);
	if(x($_POST,'notify2'))
		$notify += intval($_POST['notify2']);
	if(x($_POST,'notify3'))
		$notify += intval($_POST['notify3']);
	if(x($_POST,'notify4'))
		$notify += intval($_POST['notify4']);
	if(x($_POST,'notify5'))
		$notify += intval($_POST['notify5']);
	if(x($_POST,'notify6'))
		$notify += intval($_POST['notify6']);
	if(x($_POST,'notify7'))
		$notify += intval($_POST['notify7']);
	if(x($_POST,'notify8'))
		$notify += intval($_POST['notify8']);

	// Adjust the page flag if the account type doesn't fit to the page flag.
	if (($account_type == ACCOUNT_TYPE_PERSON) AND !in_array($page_flags, array(PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE)))
		$page_flags = PAGE_NORMAL;
	elseif (($account_type == ACCOUNT_TYPE_ORGANISATION) AND !in_array($page_flags, array(PAGE_SOAPBOX)))
		$page_flags = PAGE_SOAPBOX;
	elseif (($account_type == ACCOUNT_TYPE_NEWS) AND !in_array($page_flags, array(PAGE_SOAPBOX)))
		$page_flags = PAGE_SOAPBOX;
	elseif (($account_type == ACCOUNT_TYPE_COMMUNITY) AND !in_array($page_flags, array(PAGE_COMMUNITY, PAGE_PRVGROUP)))
		$page_flags = PAGE_COMMUNITY;

	$email_changed = false;

	$err = '';

	$name_change = false;

	if($username != $a->user['username']) {
		$name_change = true;
		if(strlen($username) > 40)
			$err .= t(' Please use a shorter name.');
		if(strlen($username) < 3)
			$err .= t(' Name too short.');
	}

	if($email != $a->user['email']) {
		$email_changed = true;
		//  check for the correct password
		$r = q("SELECT `password` FROM `user`WHERE `uid` = %d LIMIT 1", intval(local_user()));
		$password = hash('whirlpool', $_POST['mpassword']);
		if ($password != $r[0]['password']) {
			$err .= t('Wrong Password') . EOL;
			$email = $a->user['email'];
		}
		//  check the email is valid
		if(! valid_email($email))
			$err .= t(' Not valid email.');
		//  ensure new email is not the admin mail
		//if((x($a->config,'admin_email')) && (strcasecmp($email,$a->config['admin_email']) == 0)) {
		if(x($a->config,'admin_email')) {
			$adminlist = explode(",", str_replace(" ", "", strtolower($a->config['admin_email'])));
			if (in_array(strtolower($email), $adminlist)) {
				$err .= t(' Cannot change to that email.');
				$email = $a->user['email'];
			}
		}
	}

	if(strlen($err)) {
		notice($err . EOL);
		return;
	}

	if($timezone != $a->user['timezone']) {
		if(strlen($timezone))
			date_default_timezone_set($timezone);
	}

	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);

	$openidserver = $a->user['openidserver'];
	//$openid = normalise_openid($openid);

	// If openid has changed or if there's an openid but no openidserver, try and discover it.

	if($openid != $a->user['openid'] || (strlen($openid) && (! strlen($openidserver)))) {
		$tmp_str = $openid;
		if(strlen($tmp_str) && validate_url($tmp_str)) {
			logger('updating openidserver');
			require_once('library/openid.php');
			$open_id_obj = new LightOpenID;
			$open_id_obj->identity = $openid;
			$openidserver = $open_id_obj->discover($open_id_obj->identity);
		}
		else
			$openidserver = '';
	}

	set_pconfig(local_user(),'expire','items', $expire_items);
	set_pconfig(local_user(),'expire','notes', $expire_notes);
	set_pconfig(local_user(),'expire','starred', $expire_starred);
	set_pconfig(local_user(),'expire','photos', $expire_photos);
	set_pconfig(local_user(),'expire','network_only', $expire_network_only);

	set_pconfig(local_user(),'system','suggestme', $suggestme);
	set_pconfig(local_user(),'system','post_newfriend', $post_newfriend);
	set_pconfig(local_user(),'system','post_joingroup', $post_joingroup);
	set_pconfig(local_user(),'system','post_profilechange', $post_profilechange);

	set_pconfig(local_user(),'system','email_textonly', $email_textonly);

	if($page_flags == PAGE_PRVGROUP) {
		$hidewall = 1;
		if((! $str_contact_allow) && (! $str_group_allow) && (! $str_contact_deny) && (! $str_group_deny)) {
			if($def_gid) {
				info( t('Private forum has no privacy permissions. Using default privacy group.'). EOL);
				$str_group_allow = '<' . $def_gid . '>';
			}
			else {
				notice( t('Private forum has no privacy permissions and no default privacy group.') . EOL);
			}
		}
	}


	$r = q("UPDATE `user` SET `username` = '%s', `email` = '%s',
				`openid` = '%s', `timezone` = '%s',
				`allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s',
				`notify-flags` = %d, `page-flags` = %d, `account-type` = %d, `default-location` = '%s',
				`allow_location` = %d, `maxreq` = %d, `expire` = %d, `openidserver` = '%s',
				`def_gid` = %d, `blockwall` = %d, `hidewall` = %d, `blocktags` = %d,
				`unkmail` = %d, `cntunkmail` = %d, `language` = '%s'
			WHERE `uid` = %d",
			dbesc($username),
			dbesc($email),
			dbesc($openid),
			dbesc($timezone),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny),
			intval($notify),
			intval($page_flags),
			intval($account_type),
			dbesc($defloc),
			intval($allow_location),
			intval($maxreq),
			intval($expire),
			dbesc($openidserver),
			intval($def_gid),
			intval($blockwall),
			intval($hidewall),
			intval($blocktags),
			intval($unkmail),
			intval($cntunkmail),
			dbesc($language),
			intval(local_user())
	);
	if($r)
		info( t('Settings updated.') . EOL);

	// clear session language
	unset($_SESSION['language']);

	$r = q("UPDATE `profile`
		SET `publish` = %d,
		`name` = '%s',
		`net-publish` = %d,
		`hide-friends` = %d
		WHERE `is-default` = 1 AND `uid` = %d",
		intval($publish),
		dbesc($username),
		intval($net_publish),
		intval($hide_friends),
		intval(local_user())
	);


	if($name_change) {
		q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `self`",
			dbesc($username),
			dbesc(datetime_convert()),
			intval(local_user())
		);
	}

	if (($old_visibility != $net_publish) || ($page_flags != $old_page_flags)) {
		// Update global directory in background
		$url = $_SESSION['my_url'];
		if ($url && strlen(get_config('system','directory'))) {
			proc_run(PRIORITY_LOW, "include/directory.php", $url);
		}
	}

	require_once('include/profile_update.php');
	profile_change();

	// Update the global contact for the user
	update_gcontact_for_user(local_user());

	//$_SESSION['theme'] = $theme;
	if ($email_changed && $a->config['register_policy'] == REGISTER_VERIFY) {

		/// @TODO set to un-verified, blocked and redirect to logout
		/// @TODO Why? Are we verifying people or email addresses?

	}

	goaway('settings');
	return; // NOTREACHED
}


function settings_content(App $a) {

	$o = '';
	nav_set_selected('settings');

	if (! local_user()) {
		#notice( t('Permission denied.') . EOL );
		return;
	}

	if (x($_SESSION,'submanage') && intval($_SESSION['submanage'])) {
		notice( t('Permission denied.') . EOL );
		return;
	}



	if (($a->argc > 1) && ($a->argv[1] === 'oauth')) {

		if (($a->argc > 2) && ($a->argv[2] === 'add')) {
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Save Settings'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), '', ''),
				'$key'		=> array('key', t('Consumer Key'), '', ''),
				'$secret'	=> array('secret', t('Consumer Secret'), '', ''),
				'$redirect'	=> array('redirect', t('Redirect'), '', ''),
				'$icon'		=> array('icon', t('Icon url'), '', ''),
			));
			return $o;
		}

		if (($a->argc > 3) && ($a->argv[2] === 'edit')) {
			$r = q("SELECT * FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc($a->argv[3]),
					local_user());

			if (!dbm::is_result($r)){
				notice(t("You can't edit this application."));
				return;
			}
			$app = $r[0];

			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Update'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), $app['name'] , ''),
				'$key'		=> array('key', t('Consumer Key'), $app['client_id'], ''),
				'$secret'	=> array('secret', t('Consumer Secret'), $app['pw'], ''),
				'$redirect'	=> array('redirect', t('Redirect'), $app['redirect_uri'], ''),
				'$icon'		=> array('icon', t('Icon url'), $app['icon'], ''),
			));
			return $o;
		}

		if(($a->argc > 3) && ($a->argv[2] === 'delete')) {
			check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth', 't');

			$r = q("DELETE FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc($a->argv[3]),
					local_user());
			goaway(App::get_baseurl(true)."/settings/oauth/");
			return;
		}

		/// @TODO validate result with dbm::is_result()
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d,0)",
				local_user(),
				local_user());


		$tpl = get_markup_template("settings_oauth.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_oauth"),
			'$baseurl'	=> App::get_baseurl(true),
			'$title'	=> t('Connected Apps'),
			'$add'		=> t('Add application'),
			'$edit'		=> t('Edit'),
			'$delete'		=> t('Delete'),
			'$consumerkey' => t('Client key starts with'),
			'$noname'	=> t('No name'),
			'$remove'	=> t('Remove authorization'),
			'$apps'		=> $r,
		));
		return $o;

	}

	if (($a->argc > 1) && ($a->argv[1] === 'addon')) {
		$settings_addons = "";

		$r = q("SELECT * FROM `hook` WHERE `hook` = 'plugin_settings' ");
		if (! dbm::is_result($r)) {
			$settings_addons = t('No Plugin settings configured');
		}

		call_hooks('plugin_settings', $settings_addons);


		$tpl = get_markup_template("settings_addons.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_addon"),
			'$title'	=> t('Plugin Settings'),
			'$settings_addons' => $settings_addons
		));
		return $o;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'features')) {

		$arr = array();
		$features = get_features();
		foreach ($features as $fname => $fdata) {
			$arr[$fname] = array();
			$arr[$fname][0] = $fdata[0];
			foreach (array_slice($fdata,1) as $f) {
				$arr[$fname][1][] = array('feature_' .$f[0],$f[1],((intval(feature_enabled(local_user(),$f[0]))) ? "1" : ''),$f[2],array(t('Off'),t('On')));
			}
		}


		$tpl = get_markup_template("settings_features.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_features"),
			'$title'               => t('Additional Features'),
			'$features'            => $arr,
			'$submit'              => t('Save Settings'),
		));
		return $o;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'connectors')) {

		$settings_connectors = '<span id="settings_general_inflated" class="settings-block fakelink" style="display: block;" onclick="openClose(\'settings_general_expanded\'); openClose(\'settings_general_inflated\');">';
		$settings_connectors .= '<h3 class="connector">'. t('General Social Media Settings').'</h3>';
		$settings_connectors .= '</span>';
		$settings_connectors .= '<div id="settings_general_expanded" class="settings-block" style="display: none;">';
		$settings_connectors .= '<span class="fakelink" onclick="openClose(\'settings_general_expanded\'); openClose(\'settings_general_inflated\');">';
		$settings_connectors .= '<h3 class="connector">'. t('General Social Media Settings').'</h3>';
		$settings_connectors .= '</span>';

		$checked = ((get_pconfig(local_user(), 'system', 'no_intelligent_shortening')) ? ' checked="checked" ' : '');

		$settings_connectors .= '<div id="no_intelligent_shortening" class="field checkbox">';
		$settings_connectors .= '<label id="no_intelligent_shortening-label" for="shortening-checkbox">'. t('Disable intelligent shortening'). '</label>';
		$settings_connectors .= '<input id="shortening-checkbox" type="checkbox" name="no_intelligent_shortening" value="1" ' . $checked . '/>';
		$settings_connectors .= '<span class="field_help">'.t('Normally the system tries to find the best link to add to shortened posts. If this option is enabled then every shortened post will always point to the original friendica post.').'</span>';
		$settings_connectors .= '</div>';

		$checked = ((get_pconfig(local_user(), 'system', 'ostatus_autofriend')) ? ' checked="checked" ' : '');

		$settings_connectors .= '<div id="snautofollow-wrapper" class="field checkbox">';
		$settings_connectors .= '<label id="snautofollow-label" for="snautofollow-checkbox">'. t('Automatically follow any GNU Social (OStatus) followers/mentioners'). '</label>';
		$settings_connectors .= '<input id="snautofollow-checkbox" type="checkbox" name="snautofollow" value="1" ' . $checked . '/>';
		$settings_connectors .= '<span class="field_help">'.t('If you receive a message from an unknown OStatus user, this option decides what to do. If it is checked, a new contact will be created for every unknown user.').'</span>';
		$settings_connectors .= '</div>';

		$default_group = get_pconfig(local_user(), 'ostatus', 'default_group');
		$legacy_contact = get_pconfig(local_user(), 'ostatus', 'legacy_contact');

		$settings_connectors .= mini_group_select(local_user(), $default_group, t("Default group for OStatus contacts"));

		/// @TODO Found to much different usage to test empty/non-empty strings (e.g. empty(), trim() == '' ) which is wanted?
		if ($legacy_contact != "") {
			$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL='.App::get_baseurl().'/ostatus_subscribe?url='.urlencode($legacy_contact).'">';
		}

		$settings_connectors .= '<div id="legacy-contact-wrapper" class="field input">';
		$settings_connectors .= '<label id="legacy-contact-label" for="snautofollow-checkbox">'. t('Your legacy GNU Social account'). '</label>';
		$settings_connectors .= '<input id="legacy-contact-checkbox" name="legacy_contact" value="'.$legacy_contact.'"/>';
		$settings_connectors .= '<span class="field_help">'.t('If you enter your old GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.').'</span>';
		$settings_connectors .= '</div>';

		$settings_connectors .= '<p><a href="'.App::get_baseurl().'/repair_ostatus">'.t("Repair OStatus subscriptions").'</a></p>';

		$settings_connectors .= '<div class="settings-submit-wrapper" ><input type="submit" name="general-submit" class="settings-submit" value="' . t('Save Settings') . '" /></div>';

		$settings_connectors .= '</div><div class="clear"></div>';

		call_hooks('connector_settings', $settings_connectors);

		if (is_site_admin()) {
			$diasp_enabled = sprintf( t('Built-in support for %s connectivity is %s'), t('Diaspora'), ((get_config('system','diaspora_enabled')) ? t('enabled') : t('disabled')));
			$ostat_enabled = sprintf( t('Built-in support for %s connectivity is %s'), t('GNU Social (OStatus)'), ((get_config('system','ostatus_disabled')) ? t('disabled') : t('enabled')));
		} else {
			$diasp_enabled = "";
			$ostat_enabled = "";
		}

		$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);
		if(get_config('system','dfrn_only'))
			$mail_disabled = 1;

		if(! $mail_disabled) {
			$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
				local_user()
			);
		} else {
			$r = null;
		}

		$mail_server       = ((dbm::is_result($r)) ? $r[0]['server'] : '');
		$mail_port         = ((dbm::is_result($r) && intval($r[0]['port'])) ? intval($r[0]['port']) : '');
		$mail_ssl          = ((dbm::is_result($r)) ? $r[0]['ssltype'] : '');
		$mail_user         = ((dbm::is_result($r)) ? $r[0]['user'] : '');
		$mail_replyto      = ((dbm::is_result($r)) ? $r[0]['reply_to'] : '');
		$mail_pubmail      = ((dbm::is_result($r)) ? $r[0]['pubmail'] : 0);
		$mail_action       = ((dbm::is_result($r)) ? $r[0]['action'] : 0);
		$mail_movetofolder = ((dbm::is_result($r)) ? $r[0]['movetofolder'] : '');
		$mail_chk          = ((dbm::is_result($r)) ? $r[0]['last_check'] : NULL_DATE);


		$tpl = get_markup_template("settings_connectors.tpl");

		if (! service_class_allows(local_user(),'email_connect')) {
			$mail_disabled_message = upgrade_bool_message();
		} else {
			$mail_disabled_message = (($mail_disabled) ? t('Email access is disabled on this site.') : '');
		}


		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_connectors"),

			'$title'	=> t('Social Networks'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$h_imap' => t('Email/Mailbox Setup'),
			'$imap_desc' => t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$imap_lastcheck' => array('imap_lastcheck', t('Last successful email check:'), $mail_chk,''),
			'$mail_disabled' => $mail_disabled_message,
			'$mail_server'	=> array('mail_server',  t('IMAP server name:'), $mail_server, ''),
			'$mail_port'	=> array('mail_port', 	 t('IMAP port:'), $mail_port, ''),
			'$mail_ssl'		=> array('mail_ssl', 	 t('Security:'), strtoupper($mail_ssl), '', array( 'notls'=>t('None'), 'TLS'=>'TLS', 'SSL'=>'SSL')),
			'$mail_user'	=> array('mail_user',    t('Email login name:'), $mail_user, ''),
			'$mail_pass'	=> array('mail_pass', 	 t('Email password:'), '', ''),
			'$mail_replyto'	=> array('mail_replyto', t('Reply-to address:'), $mail_replyto, 'Optional'),
			'$mail_pubmail'	=> array('mail_pubmail', t('Send public posts to all email contacts:'), $mail_pubmail, ''),
			'$mail_action'	=> array('mail_action',	 t('Action after import:'), $mail_action, '', array(0=>t('None'), /*1=>t('Delete'),*/ 2=>t('Mark as seen'), 3=>t('Move to folder'))),
			'$mail_movetofolder'	=> array('mail_movetofolder',	 t('Move to folder:'), $mail_movetofolder, ''),
			'$submit' => t('Save Settings'),

			'$settings_connectors' => $settings_connectors
		));

		call_hooks('display_settings', $o);
		return $o;
	}

	/*
	 * DISPLAY SETTINGS
	 */
	if (($a->argc > 1) && ($a->argv[1] === 'display')) {
		$default_theme = get_config('system','theme');
		if (! $default_theme) {
			$default_theme = 'default';
		}
		$default_mobile_theme = get_config('system','mobile-theme');
		if (! $mobile_default_theme) {
			$mobile_default_theme = 'none';
		}

		$allowed_themes_str = get_config('system','allowed_themes');
		$allowed_themes_raw = explode(',',$allowed_themes_str);
		$allowed_themes = array();
		if (count($allowed_themes_raw)) {
			foreach ($allowed_themes_raw as $x) {
				if (strlen(trim($x)) && is_dir("view/theme/$x")) {
					$allowed_themes[] = trim($x);
				}
			}
		}


		$themes = array();
		$mobile_themes = array("---" => t('No special theme for mobile devices'));
		$files = glob('view/theme/*'); /* */
		if ($allowed_themes) {
			foreach ($allowed_themes as $th) {
				$f = $th;
				$is_experimental = file_exists('view/theme/' . $th . '/experimental');
				$unsupported = file_exists('view/theme/' . $th . '/unsupported');
				$is_mobile = file_exists('view/theme/' . $th . '/mobile');
				if (!$is_experimental or ($is_experimental && (get_config('experimentals','exp_themes')==1 or get_config('experimentals','exp_themes')===false))){
					$theme_name = (($is_experimental) ?  sprintf("%s - \x28Experimental\x29", $f) : $f);
					if ($is_mobile) {
						$mobile_themes[$f]=$theme_name;
					} else {
						$themes[$f]=$theme_name;
					}
				}
			}
		}
		$theme_selected = (!x($_SESSION,'theme')? $default_theme : $_SESSION['theme']);
		$mobile_theme_selected = (!x($_SESSION,'mobile-theme')? $default_mobile_theme : $_SESSION['mobile-theme']);

		$nowarn_insecure = intval(get_pconfig(local_user(), 'system', 'nowarn_insecure'));

		$browser_update = intval(get_pconfig(local_user(), 'system','update_interval'));
		if (intval($browser_update) != -1) {
			$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds
		}

		$itemspage_network = intval(get_pconfig(local_user(), 'system','itemspage_network'));
		$itemspage_network = (($itemspage_network > 0 && $itemspage_network < 101) ? $itemspage_network : 40); // default if not set: 40 items
		$itemspage_mobile_network = intval(get_pconfig(local_user(), 'system','itemspage_mobile_network'));
		$itemspage_mobile_network = (($itemspage_mobile_network > 0 && $itemspage_mobile_network < 101) ? $itemspage_mobile_network : 20); // default if not set: 20 items

		$nosmile = get_pconfig(local_user(),'system','no_smilies');
		$nosmile = (($nosmile===false)? '0': $nosmile); // default if not set: 0

		$first_day_of_week = get_pconfig(local_user(),'system','first_day_of_week');
		$first_day_of_week = (($first_day_of_week===false)? '0': $first_day_of_week); // default if not set: 0
		$weekdays = array(0 => t("Sunday"), 1 => t("Monday"));

		$noinfo = get_pconfig(local_user(),'system','ignore_info');
		$noinfo = (($noinfo===false)? '0': $noinfo); // default if not set: 0

		$infinite_scroll = get_pconfig(local_user(),'system','infinite_scroll');
		$infinite_scroll = (($infinite_scroll===false)? '0': $infinite_scroll); // default if not set: 0

		$no_auto_update = get_pconfig(local_user(),'system','no_auto_update');
		$no_auto_update = (($no_auto_update===false)? '0': $no_auto_update); // default if not set: 0

		$bandwidth_saver = get_pconfig(local_user(), 'system', 'bandwidth_saver');
		$bandwidth_saver = (($bandwidth_saver === false) ? '0' : $bandwidth_saver); // default if not set: 0

		$theme_config = "";
		if (($themeconfigfile = get_theme_config_file($theme_selected)) != null) {
			require_once($themeconfigfile);
			$theme_config = theme_content($a);
		}

		$tpl = get_markup_template("settings_display.tpl");
		$o = replace_macros($tpl, array(
			'$ptitle' 	=> t('Display Settings'),
			'$form_security_token' => get_form_security_token("settings_display"),
			'$submit' 	=> t('Save Settings'),
			'$baseurl' => App::get_baseurl(true),
			'$uid' => local_user(),

			'$theme'	=> array('theme', t('Display Theme:'), $theme_selected, '', $themes, true),
			'$mobile_theme'	=> array('mobile_theme', t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, false),
			'$nowarn_insecure' => array('nowarn_insecure',  t('Suppress warning of insecure networks'), $nowarn_insecure, t("Should the system suppress the warning that the current group contains members of networks that can't receive non public postings.")),
			'$ajaxint'   => array('browser_update',  t("Update browser every xx seconds"), $browser_update, t('Minimum of 10 seconds. Enter -1 to disable it.')),
			'$itemspage_network'   => array('itemspage_network',  t("Number of items to display per page:"), $itemspage_network, t('Maximum of 100 items')),
			'$itemspage_mobile_network'   => array('itemspage_mobile_network',  t("Number of items to display per page when viewed from mobile device:"), $itemspage_mobile_network, t('Maximum of 100 items')),
			'$nosmile'	=> array('nosmile', t("Don't show emoticons"), $nosmile, ''),
			'$calendar_title' => t('Calendar'),
			'$first_day_of_week'	=> array('first_day_of_week', t('Beginning of week:'), $first_day_of_week, '', $weekdays, false),
			'$noinfo'	=> array('noinfo', t("Don't show notices"), $noinfo, ''),
			'$infinite_scroll'	=> array('infinite_scroll', t("Infinite scroll"), $infinite_scroll, ''),
			'$no_auto_update'	=> array('no_auto_update', t("Automatic updates only at the top of the network page"), $no_auto_update, 'When disabled, the network page is updated all the time, which could be confusing while reading.'),
			'$bandwidth_saver' => array('bandwidth_saver', t('Bandwith Saver Mode'), $bandwidth_saver, t('When enabled, embedded content is not displayed on automatic updates, they only show on page reload.')),

			'$d_tset' => t('General Theme Settings'),
			'$d_ctset' => t('Custom Theme Settings'),
			'$d_cset' => t('Content Settings'),
			'stitle' => t('Theme settings'),
			'$theme_config' => $theme_config,
		));

		$tpl = get_markup_template("settings_display_end.tpl");
		$a->page['end'] .= replace_macros($tpl, array(
			'$theme'	=> array('theme', t('Display Theme:'), $theme_selected, '', $themes)
		));

		return $o;
	}


	/*
	 * ACCOUNT SETTINGS
	 */

	require_once('include/acl_selectors.php');

	$p = q("SELECT * FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);
	if (count($p)) {
		$profile = $p[0];
	}

	$username   = $a->user['username'];
	$email      = $a->user['email'];
	$nickname   = $a->user['nickname'];
	$timezone   = $a->user['timezone'];
	$language   = $a->user['language'];
	$notify     = $a->user['notify-flags'];
	$defloc     = $a->user['default-location'];
	$openid     = $a->user['openid'];
	$maxreq     = $a->user['maxreq'];
	$expire     = ((intval($a->user['expire'])) ? $a->user['expire'] : '');
	$blockwall  = $a->user['blockwall'];
	$blocktags  = $a->user['blocktags'];
	$unkmail    = $a->user['unkmail'];
	$cntunkmail = $a->user['cntunkmail'];

	$expire_items = get_pconfig(local_user(), 'expire','items');
	$expire_items = (($expire_items===false)? '1' : $expire_items); // default if not set: 1

	$expire_notes = get_pconfig(local_user(), 'expire','notes');
	$expire_notes = (($expire_notes===false)? '1' : $expire_notes); // default if not set: 1

	$expire_starred = get_pconfig(local_user(), 'expire','starred');
	$expire_starred = (($expire_starred===false)? '1' : $expire_starred); // default if not set: 1

	$expire_photos = get_pconfig(local_user(), 'expire','photos');
	$expire_photos = (($expire_photos===false)? '0' : $expire_photos); // default if not set: 0

	$expire_network_only = get_pconfig(local_user(), 'expire','network_only');
	$expire_network_only = (($expire_network_only===false)? '0' : $expire_network_only); // default if not set: 0


	$suggestme = get_pconfig(local_user(), 'system','suggestme');
	$suggestme = (($suggestme===false)? '0': $suggestme); // default if not set: 0

	$post_newfriend = get_pconfig(local_user(), 'system','post_newfriend');
	$post_newfriend = (($post_newfriend===false)? '0': $post_newfriend); // default if not set: 0

	$post_joingroup = get_pconfig(local_user(), 'system','post_joingroup');
	$post_joingroup = (($post_joingroup===false)? '0': $post_joingroup); // default if not set: 0

	$post_profilechange = get_pconfig(local_user(), 'system','post_profilechange');
	$post_profilechange = (($post_profilechange===false)? '0': $post_profilechange); // default if not set: 0

	// nowarn_insecure

	if (! strlen($a->user['timezone'])) {
		$timezone = date_default_timezone_get();
	}

	// Set the account type to "Community" when the page is a community page but the account type doesn't fit
	// This is only happening on the first visit after the update
	if (in_array($a->user['page-flags'], array(PAGE_COMMUNITY, PAGE_PRVGROUP)) AND
		($a->user['account-type'] != ACCOUNT_TYPE_COMMUNITY))
		$a->user['account-type'] = ACCOUNT_TYPE_COMMUNITY;

	$pageset_tpl = get_markup_template('settings_pagetypes.tpl');

	$pagetype = replace_macros($pageset_tpl, array(
		'$account_types'	=> t("Account Types"),
		'$user' 		=> t("Personal Page Subtypes"),
		'$community'		=> t("Community Forum Subtypes"),
		'$account_type'		=> $a->user['account-type'],
		'$type_person'		=> ACCOUNT_TYPE_PERSON,
		'$type_organisation' 	=> ACCOUNT_TYPE_ORGANISATION,
		'$type_news'		=> ACCOUNT_TYPE_NEWS,
		'$type_community' 	=> ACCOUNT_TYPE_COMMUNITY,

		'$account_person' 	=> array('account-type', t('Personal Page'), ACCOUNT_TYPE_PERSON,
									t('This account is a regular personal profile'),
									($a->user['account-type'] == ACCOUNT_TYPE_PERSON)),

		'$account_organisation'	=> array('account-type', t('Organisation Page'), ACCOUNT_TYPE_ORGANISATION,
									t('This account is a profile for an organisation'),
									($a->user['account-type'] == ACCOUNT_TYPE_ORGANISATION)),

		'$account_news'		=> array('account-type', t('News Page'), ACCOUNT_TYPE_NEWS,
									t('This account is a news account/reflector'),
									($a->user['account-type'] == ACCOUNT_TYPE_NEWS)),

		'$account_community' 	=> array('account-type', t('Community Forum'), ACCOUNT_TYPE_COMMUNITY,
									t('This account is a community forum where people can discuss with each other'),
									($a->user['account-type'] == ACCOUNT_TYPE_COMMUNITY)),

		'$page_normal'		=> array('page-flags', t('Normal Account Page'), PAGE_NORMAL,
									t('This account is a normal personal profile'),
									($a->user['page-flags'] == PAGE_NORMAL)),

		'$page_soapbox' 	=> array('page-flags', t('Soapbox Page'), PAGE_SOAPBOX,
									t('Automatically approve all connection/friend requests as read-only fans'),
									($a->user['page-flags'] == PAGE_SOAPBOX)),

		'$page_community'	=> array('page-flags', t('Public Forum'), PAGE_COMMUNITY,
									t('Automatically approve all contact requests'),
									($a->user['page-flags'] == PAGE_COMMUNITY)),

		'$page_freelove' 	=> array('page-flags', t('Automatic Friend Page'), PAGE_FREELOVE,
									t('Automatically approve all connection/friend requests as friends'),
									($a->user['page-flags'] == PAGE_FREELOVE)),

		'$page_prvgroup' 	=> array('page-flags', t('Private Forum [Experimental]'), PAGE_PRVGROUP,
									t('Private forum - approved members only'),
									($a->user['page-flags'] == PAGE_PRVGROUP)),


	));

	$noid = get_config('system','no_openid');

	if ($noid) {
		$openid_field = false;
	} else {
		$openid_field = array('openid_url', t('OpenID:'),$openid, t("\x28Optional\x29 Allow this OpenID to login to this account."), "", "", "url");
	}

	$opt_tpl = get_markup_template("field_yesno.tpl");
	if(get_config('system','publish_all')) {
		$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
	} else {
		$profile_in_dir = replace_macros($opt_tpl,array(
			'$field' 	=> array('profile_in_directory', t('Publish your default profile in your local site directory?'), $profile['publish'], t("Depending on the server settings your profile may be visible in public."), array(t('No'),t('Yes')))));
	}

	if (strlen(get_config('system','directory'))) {
		$profile_in_net_dir = replace_macros($opt_tpl,array(
		'$field'        => array('profile_in_netdirectory', t('Publish your default profile in the global social directory?'), $profile['net-publish'], '', array(t('No'),t('Yes'))),));
	} else {
		$profile_in_net_dir = '';
	}

	$hide_friends = replace_macros($opt_tpl,array(
			'$field' 	=> array('hide-friends', t('Hide your contact/friend list from viewers of your default profile?'), $profile['hide-friends'], '', array(t('No'),t('Yes'))),
	));

	$hide_wall = replace_macros($opt_tpl,array(
			'$field' 	=> array('hidewall',  t('Hide your profile details from unknown viewers?'), $a->user['hidewall'], t("If enabled, posting public messages to Diaspora and other networks isn't possible."), array(t('No'),t('Yes'))),

	));

	$blockwall = replace_macros($opt_tpl,array(
			'$field' 	=> array('blockwall',  t('Allow friends to post to your profile page?'), (intval($a->user['blockwall']) ? '0' : '1'), '', array(t('No'),t('Yes'))),

	));

	$blocktags = replace_macros($opt_tpl,array(
			'$field' 	=> array('blocktags',  t('Allow friends to tag your posts?'), (intval($a->user['blocktags']) ? '0' : '1'), '', array(t('No'),t('Yes'))),

	));

	$suggestme = replace_macros($opt_tpl,array(
			'$field' 	=> array('suggestme',  t('Allow us to suggest you as a potential friend to new members?'), $suggestme, '', array(t('No'),t('Yes'))),

	));

	$unkmail = replace_macros($opt_tpl,array(
			'$field' 	=> array('unkmail',  t('Permit unknown people to send you private mail?'), $unkmail, '', array(t('No'),t('Yes'))),

	));

	$invisible = (((! $profile['publish']) && (! $profile['net-publish']))
		? true : false);

	if ($invisible) {
		info( t('Profile is <strong>not published</strong>.') . EOL );
	}

	//$subdir = ((strlen($a->get_path())) ? '<br />' . t('or') . ' ' . 'profile/' . $nickname : '');

	$tpl_addr = get_markup_template("settings_nick_set.tpl");

	$prof_addr = replace_macros($tpl_addr,array(
		'$desc' => sprintf(t("Your Identity Address is <strong>'%s'</strong> or '%s'."), $nickname.'@'.$a->get_hostname().$a->get_path(), App::get_baseurl().'/profile/'.$nickname),
		'$basepath' => $a->get_hostname()
	));

	$stpl = get_markup_template('settings.tpl');

	$expire_arr = array(
		'days' => array('expire',  t("Automatically expire posts after this many days:"), $expire, t('If empty, posts will not expire. Expired posts will be deleted')),
		'advanced' => t('Advanced expiration settings'),
		'label' => t('Advanced Expiration'),
		'items' => array('expire_items',  t("Expire posts:"), $expire_items, '', array(t('No'),t('Yes'))),
		'notes' => array('expire_notes',  t("Expire personal notes:"), $expire_notes, '', array(t('No'),t('Yes'))),
		'starred' => array('expire_starred',  t("Expire starred posts:"), $expire_starred, '', array(t('No'),t('Yes'))),
		'photos' => array('expire_photos',  t("Expire photos:"), $expire_photos, '', array(t('No'),t('Yes'))),
		'network_only' => array('expire_network_only',  t("Only expire posts by others:"), $expire_network_only, '', array(t('No'),t('Yes'))),
	);

	require_once('include/group.php');
	$group_select = mini_group_select(local_user(),$a->user['def_gid']);

	// Private/public post links for the non-JS ACL form
	$private_post = 1;
	if ($_REQUEST['public']) {
		$private_post = 0;
	}

	$query_str = $a->query_string;
	if (strpos($query_str, 'public=1') !== false) {
		$query_str = str_replace(array('?public=1', '&public=1'), array('', ''), $query_str);
	}

	// I think $a->query_string may never have ? in it, but I could be wrong
	// It looks like it's from the index.php?q=[etc] rewrite that the web
	// server does, which converts any ? to &, e.g. suggest&ignore=61 for suggest?ignore=61
	if (strpos($query_str, '?') === false) {
		$public_post_link = '?public=1';
	} else {
		$public_post_link = '&public=1';
	}

	/* Installed langs */
	$lang_choices = get_available_languages();

	/// @TODO Fix indending (or so)
	$o .= replace_macros($stpl, array(
		'$ptitle' 	=> t('Account Settings'),

		'$submit' 	=> t('Save Settings'),
		'$baseurl' => App::get_baseurl(true),
		'$uid' => local_user(),
		'$form_security_token' => get_form_security_token("settings"),
		'$nickname_block' => $prof_addr,

		'$h_pass' 	=> t('Password Settings'),
		'$password1'=> array('password', t('New Password:'), '', ''),
		'$password2'=> array('confirm', t('Confirm:'), '', t('Leave password fields blank unless changing')),
		'$password3'=> array('opassword', t('Current Password:'), '', t('Your current password to confirm the changes')),
		'$password4'=> array('mpassword', t('Password:'), '', t('Your current password to confirm the changes')),
		'$oid_enable' => (! get_config('system','no_openid')),
		'$openid'	=> $openid_field,

		'$h_basic' 	=> t('Basic Settings'),
		'$username' => array('username',  t('Full Name:'), $username,''),
		'$email' 	=> array('email', t('Email Address:'), $email, '', '', '', 'email'),
		'$timezone' => array('timezone_select' , t('Your Timezone:'), select_timezone($timezone), ''),
		'$language' => array('language', t('Your Language:'), $language, t('Set the language we use to show you friendica interface and to send you emails'), $lang_choices),
		'$defloc'	=> array('defloc', t('Default Post Location:'), $defloc, ''),
		'$allowloc' => array('allow_location', t('Use Browser Location:'), ($a->user['allow_location'] == 1), ''),


		'$h_prv' 	=> t('Security and Privacy Settings'),

		'$maxreq' 	=> array('maxreq', t('Maximum Friend Requests/Day:'), $maxreq ,t("\x28to prevent spam abuse\x29")),
		'$permissions' => t('Default Post Permissions'),
		'$permdesc' => t("\x28click to open/close\x29"),
		'$visibility' => $profile['net-publish'],
		'$aclselect' => populate_acl($a->user),
		'$suggestme' => $suggestme,
		'$blockwall'=> $blockwall, // array('blockwall', t('Allow friends to post to your profile page:'), !$blockwall, ''),
		'$blocktags'=> $blocktags, // array('blocktags', t('Allow friends to tag your posts:'), !$blocktags, ''),

		// ACL permissions box
		'$acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
		'$group_perms' => t('Show to Groups'),
		'$contact_perms' => t('Show to Contacts'),
		'$private' => t('Default Private Post'),
		'$public' => t('Default Public Post'),
		'$is_private' => $private_post,
		'$return_path' => $query_str,
		'$public_link' => $public_post_link,
		'$settings_perms' => t('Default Permissions for New Posts'),

		'$group_select' => $group_select,


		'$expire'	=> $expire_arr,

		'$profile_in_dir' => $profile_in_dir,
		'$profile_in_net_dir' => $profile_in_net_dir,
		'$hide_friends' => $hide_friends,
		'$hide_wall' => $hide_wall,
		'$unkmail' => $unkmail,
		'$cntunkmail' 	=> array('cntunkmail', t('Maximum private messages per day from unknown people:'), $cntunkmail ,t("\x28to prevent spam abuse\x29")),


		'$h_not' 	=> t('Notification Settings'),
		'$activity_options' => t('By default post a status message when:'),
		'$post_newfriend' => array('post_newfriend',  t('accepting a friend request'), $post_newfriend, ''),
		'$post_joingroup' => array('post_joingroup',  t('joining a forum/community'), $post_joingroup, ''),
		'$post_profilechange' => array('post_profilechange',  t('making an <em>interesting</em> profile change'), $post_profilechange, ''),
		'$lbl_not' 	=> t('Send a notification email when:'),
		'$notify1'	=> array('notify1', t('You receive an introduction'), ($notify & NOTIFY_INTRO), NOTIFY_INTRO, ''),
		'$notify2'	=> array('notify2', t('Your introductions are confirmed'), ($notify & NOTIFY_CONFIRM), NOTIFY_CONFIRM, ''),
		'$notify3'	=> array('notify3', t('Someone writes on your profile wall'), ($notify & NOTIFY_WALL), NOTIFY_WALL, ''),
		'$notify4'	=> array('notify4', t('Someone writes a followup comment'), ($notify & NOTIFY_COMMENT), NOTIFY_COMMENT, ''),
		'$notify5'	=> array('notify5', t('You receive a private message'), ($notify & NOTIFY_MAIL), NOTIFY_MAIL, ''),
		'$notify6'  => array('notify6', t('You receive a friend suggestion'), ($notify & NOTIFY_SUGGEST), NOTIFY_SUGGEST, ''),
		'$notify7'  => array('notify7', t('You are tagged in a post'), ($notify & NOTIFY_TAGSELF), NOTIFY_TAGSELF, ''),
		'$notify8'  => array('notify8', t('You are poked/prodded/etc. in a post'), ($notify & NOTIFY_POKE), NOTIFY_POKE, ''),

		'$desktop_notifications' => array('desktop_notifications', t('Activate desktop notifications') , false, t('Show desktop popup on new notifications')),

		'$email_textonly' => array('email_textonly', t('Text-only notification emails'),
									get_pconfig(local_user(),'system','email_textonly'),
									t('Send text only notification emails, without the html part')),

		'$h_advn' => t('Advanced Account/Page Type Settings'),
		'$h_descadvn' => t('Change the behaviour of this account for special situations'),
		'$pagetype' => $pagetype,

		'$relocate' => t('Relocate'),
		'$relocate_text' => t("If you have moved this profile from another server, and some of your contacts don't receive your updates, try pushing this button."),
		'$relocate_button' => t("Resend relocate message to contacts"),

	));

	call_hooks('settings_form',$o);

	$o .= '</form>' . "\r\n";

	return $o;

}
