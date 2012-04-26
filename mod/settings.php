<?php


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

function settings_init(&$a) {
	// These lines provide the javascript needed by the acl selector

	$a->page['htmlhead'] .= "<script> var ispublic = '" . t('everybody') . "';" ;

	$a->page['htmlhead'] .= <<< EOT

	$(document).ready(function() {

		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
			});
			if(selstr == null) { 
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
			}

		}).trigger('change');

	});

	</script>
EOT;



	$tabs = array(
		array(
			'label'	=> t('Account settings'),
			'url' 	=> $a->get_baseurl(true).'/settings',
			'selected'	=> (($a->argc == 1)?'active':''),
		),	
		array(
			'label'	=> t('Display settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/display',
			'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'display')?'active':''),
		),	
		
		array(
			'label'	=> t('Connector settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/connectors',
			'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'connectors')?'active':''),
		),
		array(
			'label'	=> t('Plugin settings'),
			'url' 	=> $a->get_baseurl(true).'/settings/addon',
			'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'addon')?'active':''),
		),
		array(
			'label' => t('Connected apps'),
			'url' => $a->get_baseurl(true) . '/settings/oauth',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'oauth')?'active':''),
		),
		array(
			'label' => t('Export personal data'),
			'url' => $a->get_baseurl(true) . '/uexport',
			'selected' => ''
		),
		array(
			'label' => t('Remove account'),
			'url' => $a->get_baseurl(true) . '/removeme',
			'selected' => ''
		)
	);
	
	$tabtpl = get_markup_template("generic_links_widget.tpl");
	$a->page['aside'] = replace_macros($tabtpl, array(
		'$title' => t('Settings'),
		'$items' => $tabs,
	));

}


function settings_post(&$a) {

	if(! local_user())
		return;

	if(x($_SESSION,'submanage') && intval($_SESSION['submanage']))
		return;

	if(count($a->user) && x($a->user,'uid') && $a->user['uid'] != local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$old_page_flags = $a->user['page-flags'];

	if(($a->argc > 1) && ($a->argv[1] === 'oauth') && x($_POST,'remove')){
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');
		
		$key = $_POST['remove'];
		q("DELETE FROM tokens WHERE id='%s' AND uid=%d",
			dbesc($key),
			local_user());
		goaway($a->get_baseurl(true)."/settings/oauth/");
		return;			
	}

	if(($a->argc > 2) && ($a->argv[1] === 'oauth')  && ($a->argv[2] === 'edit'||($a->argv[2] === 'add')) && x($_POST,'submit')) {
		
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
		goaway($a->get_baseurl(true)."/settings/oauth/");
		return;
	}

	if(($a->argc > 1) && ($a->argv[1] == 'addon')) {
		check_form_security_token_redirectOnErr('/settings/addon', 'settings_addon');
		
		call_hooks('plugin_settings_post', $_POST);
		return;
	}

	if(($a->argc > 1) && ($a->argv[1] == 'connectors')) {
		
		check_form_security_token_redirectOnErr('/settings/connectors', 'settings_connectors');
		
		if(x($_POST, 'imap-submit')) {
			
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
				if(! count($r)) {
					q("INSERT INTO `mailacct` (`uid`) VALUES (%d)",
						intval(local_user())
					);
				}
				if(strlen($mail_pass)) {
					$pass = '';
					openssl_public_encrypt($mail_pass,$pass,$a->user['pubkey']);
					q("UPDATE `mailacct` SET `pass` = '%s' WHERE `uid` = %d LIMIT 1",
						dbesc(bin2hex($pass)),
						intval(local_user())
					);
				}
				$r = q("UPDATE `mailacct` SET `server` = '%s', `port` = %d, `ssltype` = '%s', `user` = '%s',
					`action` = %d, `movetofolder` = '%s',
					`mailbox` = 'INBOX', `reply_to` = '%s', `pubmail` = %d WHERE `uid` = %d LIMIT 1",
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
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if(count($r)) {
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
	
	if(($a->argc > 1) && ($a->argv[1] == 'display')) {
		
		check_form_security_token_redirectOnErr('/settings/display', 'settings_display');

		$theme = ((x($_POST,'theme')) ? notags(trim($_POST['theme']))  : $a->user['theme']);
		$nosmile = ((x($_POST,'nosmile')) ? intval($_POST['nosmile'])  : 0);  
		$browser_update   = ((x($_POST,'browser_update')) ? intval($_POST['browser_update']) : 0);
		$browser_update   = $browser_update * 1000;
		if($browser_update < 10000)
			$browser_update = 40000;

		$itemspage_network   = ((x($_POST,'itemspage_network')) ? intval($_POST['itemspage_network']) : 40);
		if($itemspage_network > 100)
					$itemspage_network = 40;


		set_pconfig(local_user(),'system','update_interval', $browser_update);
		set_pconfig(local_user(),'system','itemspage_network', $itemspage_network);
		set_pconfig(local_user(),'system','no_smilies',$nosmile);


		if ($theme == $a->user['theme']){
			// call theme_post only if theme has not been changed
			if( ($themeconfigfile = get_theme_config_file($theme)) != null){
				require_once($themeconfigfile);
				theme_post($a);
			}
		}


		$r = q("UPDATE `user` SET `theme` = '%s' WHERE `uid` = %d LIMIT 1",
				dbesc($theme),
				intval(local_user())
		);
	
		call_hooks('display_settings_post', $_POST);
		goaway($a->get_baseurl(true) . '/settings/display' );
		return; // NOTREACHED
	}

	check_form_security_token_redirectOnErr('/settings', 'settings');
	
	call_hooks('settings_post', $_POST);

	if((x($_POST,'npassword')) || (x($_POST,'confirm'))) {

		$newpass = $_POST['npassword'];
		$confirm = $_POST['confirm'];

		$err = false;
		if($newpass != $confirm ) {
			notice( t('Passwords do not match. Password unchanged.') . EOL);
			$err = true;
		}

		if((! x($newpass)) || (! x($confirm))) {
			notice( t('Empty passwords are not allowed. Password unchanged.') . EOL);
			$err = true;
		}

		if(! $err) {
			$password = hash('whirlpool',$newpass);
			$r = q("UPDATE `user` SET `password` = '%s' WHERE `uid` = %d LIMIT 1",
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
	$defloc           = ((x($_POST,'defloc'))     ? notags(trim($_POST['defloc']))       : '');
	$openid           = ((x($_POST,'openid_url')) ? notags(trim($_POST['openid_url']))   : '');
	$maxreq           = ((x($_POST,'maxreq'))     ? intval($_POST['maxreq'])             : 0);
	$expire           = ((x($_POST,'expire'))     ? intval($_POST['expire'])             : 0);


	$expire_items     = ((x($_POST,'expire_items')) ? intval($_POST['expire_items'])	 : 0);
	$expire_notes     = ((x($_POST,'expire_notes')) ? intval($_POST['expire_notes'])	 : 0);
	$expire_starred   = ((x($_POST,'expire_starred')) ? intval($_POST['expire_starred']) : 0);
	$expire_photos    = ((x($_POST,'expire_photos'))? intval($_POST['expire_photos'])	 : 0);



	$allow_location   = (((x($_POST,'allow_location')) && (intval($_POST['allow_location']) == 1)) ? 1: 0);
	$publish          = (((x($_POST,'profile_in_directory')) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
	$net_publish      = (((x($_POST,'profile_in_netdirectory')) && (intval($_POST['profile_in_netdirectory']) == 1)) ? 1: 0);
	$old_visibility   = (((x($_POST,'visibility')) && (intval($_POST['visibility']) == 1)) ? 1 : 0);
	$page_flags       = (((x($_POST,'page-flags')) && (intval($_POST['page-flags']))) ? intval($_POST['page-flags']) : 0);
	$blockwall        = (((x($_POST,'blockwall')) && (intval($_POST['blockwall']) == 1)) ? 0: 1); // this setting is inverted!
	$blocktags        = (((x($_POST,'blocktags')) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
	$unkmail          = (((x($_POST,'unkmail')) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
	$cntunkmail       = ((x($_POST,'cntunkmail')) ? intval($_POST['cntunkmail']) : 0);
	$suggestme        = ((x($_POST,'suggestme')) ? intval($_POST['suggestme'])  : 0);  
	$hide_friends     = (($_POST['hide-friends'] == 1) ? 1: 0);
	$hidewall         = (($_POST['hidewall'] == 1) ? 1: 0);
	$post_newfriend   = (($_POST['post_newfriend'] == 1) ? 1: 0);
	$post_profilechange   = (($_POST['post_profilechange'] == 1) ? 1: 0);


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
        if(! valid_email($email))
			$err .= t(' Not valid email.');
		if((x($a->config,'admin_email')) && (strcasecmp($email,$a->config['admin_email']) == 0)) {
			$err .= t(' Cannot change to that email.');
			$email = $a->user['email'];
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
	$openid = normalise_openid($openid);

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

	set_pconfig(local_user(),'system','suggestme', $suggestme);
	set_pconfig(local_user(),'system','post_newfriend', $post_newfriend);
	set_pconfig(local_user(),'system','post_profilechange', $post_profilechange);


	$r = q("UPDATE `user` SET `username` = '%s', `email` = '%s', `openid` = '%s', `timezone` = '%s',  `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s', `notify-flags` = %d, `page-flags` = %d, `default-location` = '%s', `allow_location` = %d, `maxreq` = %d, `expire` = %d, `openidserver` = '%s', `blockwall` = %d, `hidewall` = %d, `blocktags` = %d, `unkmail` = %d, `cntunkmail` = %d  WHERE `uid` = %d LIMIT 1",
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
			dbesc($defloc),
			intval($allow_location),
			intval($maxreq),
			intval($expire),
			dbesc($openidserver),
			intval($blockwall),
			intval($hidewall),
			intval($blocktags),
			intval($unkmail),
			intval($cntunkmail),
			intval(local_user())
	);
	if($r)
		info( t('Settings updated.') . EOL);

	$r = q("UPDATE `profile` 
		SET `publish` = %d, 
		`net-publish` = %d,
		`hide-friends` = %d
		WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval($publish),
		intval($net_publish),
		intval($hide_friends),
		intval(local_user())
	);


	if($name_change) {
		q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			dbesc($username),
			dbesc(datetime_convert()),
			intval(local_user())
		);
	}		

	if(($old_visibility != $net_publish) || ($page_flags != $old_page_flags)) {
		// Update global directory in background
		$url = $_SESSION['my_url'];
		if($url && strlen(get_config('system','directory_submit_url')))
			proc_run('php',"include/directory.php","$url");

	}


	require_once('include/profile_update.php');
	profile_change();

	$_SESSION['theme'] = $theme;
	if($email_changed && $a->config['register_policy'] == REGISTER_VERIFY) {

		// FIXME - set to un-verified, blocked and redirect to logout

	}

	goaway($a->get_baseurl(true) . '/settings' );
	return; // NOTREACHED
}
		

if(! function_exists('settings_content')) {
function settings_content(&$a) {

	$o = '';
	nav_set_selected('settings');

	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	if(x($_SESSION,'submanage') && intval($_SESSION['submanage'])) {
		notice( t('Permission denied.') . EOL );
		return;
	}
	

		
	if(($a->argc > 1) && ($a->argv[1] === 'oauth')) {
		
		if(($a->argc > 2) && ($a->argv[2] === 'add')) {
			$tpl = get_markup_template("settings_oauth_edit.tpl");
			$o .= replace_macros($tpl, array(
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> t('Add application'),
				'$submit'	=> t('Submit'),
				'$cancel'	=> t('Cancel'),
				'$name'		=> array('name', t('Name'), '', ''),
				'$key'		=> array('key', t('Consumer Key'), '', ''),
				'$secret'	=> array('secret', t('Consumer Secret'), '', ''),
				'$redirect'	=> array('redirect', t('Redirect'), '', ''),
				'$icon'		=> array('icon', t('Icon url'), '', ''),
			));
			return $o;
		}
		
		if(($a->argc > 3) && ($a->argv[2] === 'edit')) {
			$r = q("SELECT * FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc($a->argv[3]),
					local_user());
			
			if (!count($r)){
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
			goaway($a->get_baseurl(true)."/settings/oauth/");
			return;			
		}
		
		
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my 
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d,0)",
				local_user(),
				local_user());
		
		
		$tpl = get_markup_template("settings_oauth.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_oauth"),
			'$baseurl'	=> $a->get_baseurl(true),
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
	if(($a->argc > 1) && ($a->argv[1] === 'addon')) {
		$settings_addons = "";
		
		$r = q("SELECT * FROM `hook` WHERE `hook` = 'plugin_settings' ");
		if(! count($r))
			$settings_addons = t('No Plugin settings configured');

		call_hooks('plugin_settings', $settings_addons);
		
		
		$tpl = get_markup_template("settings_addons.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_addon"),
			'$title'	=> t('Plugin Settings'),
			'$settings_addons' => $settings_addons
		));
		return $o;
	}

	if(($a->argc > 1) && ($a->argv[1] === 'connectors')) {

		$settings_connectors = "";
		
		call_hooks('connector_settings', $settings_connectors);

		$diasp_enabled = sprintf( t('Built-in support for %s connectivity is %s'), t('Diaspora'), ((get_config('system','diaspora_enabled')) ? t('enabled') : t('disabled')));
		$ostat_enabled = sprintf( t('Built-in support for %s connectivity is %s'), t('StatusNet'), ((get_config('system','ostatus_disabled')) ? t('disabled') : t('enabled')));

		$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);
		if(get_config('system','dfrn_only'))
			$mail_disabled = 1;

		if(! $mail_disabled) {
			$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
				local_user()
			);
		}
		else {
			$r = null;
		}

		$mail_server       = ((count($r)) ? $r[0]['server'] : '');
		$mail_port         = ((count($r) && intval($r[0]['port'])) ? intval($r[0]['port']) : '');
		$mail_ssl          = ((count($r)) ? $r[0]['ssltype'] : '');
		$mail_user         = ((count($r)) ? $r[0]['user'] : '');
		$mail_replyto      = ((count($r)) ? $r[0]['reply_to'] : '');
		$mail_pubmail      = ((count($r)) ? $r[0]['pubmail'] : 0);
		$mail_action       = ((count($r)) ? $r[0]['action'] : 0);
		$mail_movetofolder = ((count($r)) ? $r[0]['movetofolder'] : '');
		$mail_chk          = ((count($r)) ? $r[0]['last_check'] : '0000-00-00 00:00:00');


		$tpl = get_markup_template("settings_connectors.tpl");
		$o .= replace_macros($tpl, array(
			'$form_security_token' => get_form_security_token("settings_connectors"),
			
			'$title'	=> t('Connector Settings'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$h_imap' => t('Email/Mailbox Setup'),
			'$imap_desc' => t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$imap_lastcheck' => array('imap_lastcheck', t('Last successful email check:'), $mail_chk,''),
			'$mail_disabled' => (($mail_disabled) ? t('Email access is disabled on this site.') : ''),
			'$mail_server'	=> array('mail_server',  t('IMAP server name:'), $mail_server, ''),
			'$mail_port'	=> array('mail_port', 	 t('IMAP port:'), $mail_port, ''),
			'$mail_ssl'		=> array('mail_ssl', 	 t('Security:'), strtoupper($mail_ssl), '', array( 'notls'=>t('None'), 'TLS'=>'TLS', 'SSL'=>'SSL')),
			'$mail_user'	=> array('mail_user',    t('Email login name:'), $mail_user, ''),
			'$mail_pass'	=> array('mail_pass', 	 t('Email password:'), '', ''),
			'$mail_replyto'	=> array('mail_replyto', t('Reply-to address:'), '', 'Optional'),
			'$mail_pubmail'	=> array('mail_pubmail', t('Send public posts to all email contacts:'), $mail_pubmail, ''),
			'$mail_action'	=> array('mail_action',	 t('Action after import:'), $mail_action, '', array(0=>t('None'), 1=>t('Delete'), 2=>t('Mark as seen'), 3=>t('Move to folder'))),
			'$mail_movetofolder'	=> array('mail_movetofolder',	 t('Move to folder:'), $mail_movetofolder, ''),
			'$submit' => t('Submit'),

			'$settings_connectors' => $settings_connectors
		));

		call_hooks('display_settings', $o);
		return $o;
	}

	/*
	 * DISPLAY SETTINGS
	 */
	if(($a->argc > 1) && ($a->argv[1] === 'display')) {
		$default_theme = get_config('system','theme');
		if(! $default_theme)
			$default_theme = 'default';

		$allowed_themes_str = get_config('system','allowed_themes');
		$allowed_themes_raw = explode(',',$allowed_themes_str);
		$allowed_themes = array();
		if(count($allowed_themes_raw))
			foreach($allowed_themes_raw as $x) 
				if(strlen(trim($x)) && is_dir("view/theme/$x"))
					$allowed_themes[] = trim($x);

		
		$themes = array();
		$files = glob('view/theme/*');
		if($allowed_themes) {
			foreach($allowed_themes as $th) {
				$f = $th;
				$is_experimental = file_exists('view/theme/' . $th . '/experimental');
				$unsupported = file_exists('view/theme/' . $th . '/unsupported');
				if (!$is_experimental or ($is_experimental && (get_config('experimentals','exp_themes')==1 or get_config('experimentals','exp_themes')===false))){ 
					$theme_name = (($is_experimental) ?  sprintf("%s - \x28Experimental\x29", $f) : $f);
					$themes[$f]=$theme_name;
				}
			}
		}
		$theme_selected = (!x($_SESSION,'theme')? $default_theme : $_SESSION['theme']);
		
		$browser_update = intval(get_pconfig(local_user(), 'system','update_interval'));
		$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds

		$itemspage_network = intval(get_pconfig(local_user(), 'system','itemspage_network'));
		$itemspage_network = (($itemspage_network > 0 && $itemspage_network < 101) ? $itemspage_network : 40); // default if not set: 40 items
		
		$nosmile = get_pconfig(local_user(),'system','no_smilies');
		$nosmile = (($nosmile===false)? '0': $nosmile); // default if not set: 0


		$theme_config = "";
		if( ($themeconfigfile = get_theme_config_file($theme_selected)) != null){
			require_once($themeconfigfile);
			$theme_config = theme_content($a);
		}
		
		$tpl = get_markup_template("settings_display.tpl");
		$o = replace_macros($tpl, array(
			'$ptitle' 	=> t('Display Settings'),
			'$form_security_token' => get_form_security_token("settings_display"),
			'$submit' 	=> t('Submit'),
			'$baseurl' => $a->get_baseurl(true),
			'$uid' => local_user(),
		
			'$theme'	=> array('theme', t('Display Theme:'), $theme_selected, '', $themes),
			'$ajaxint'   => array('browser_update',  t("Update browser every xx seconds"), $browser_update, t('Minimum of 10 seconds, no maximum')),
			'$itemspage_network'   => array('itemspage_network',  t("Number of items to display on the network page:"), $itemspage_network, t('Maximum of 100 items')),
			'$nosmile'	=> array('nosmile', t("Don't show emoticons"), $nosmile, ''),
			
			'$theme_config' => $theme_config,
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
	if(count($p))
		$profile = $p[0];

	$username   = $a->user['username'];
	$email      = $a->user['email'];
	$nickname   = $a->user['nickname'];
	$timezone   = $a->user['timezone'];
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


	$suggestme = get_pconfig(local_user(), 'system','suggestme');
	$suggestme = (($suggestme===false)? '0': $suggestme); // default if not set: 0

	$post_newfriend = get_pconfig(local_user(), 'system','post_newfriend');
	$post_newfriend = (($post_newfriend===false)? '0': $post_newfriend); // default if not set: 0

	$post_profilechange = get_pconfig(local_user(), 'system','post_profilechange');
	$post_profilechange = (($post_profilechange===false)? '0': $post_profilechange); // default if not set: 0

	
	if(! strlen($a->user['timezone']))
		$timezone = date_default_timezone_get();



	$pageset_tpl = get_markup_template('pagetypes.tpl');
	$pagetype = replace_macros($pageset_tpl,array(
		'$page_normal' 	=> array('page-flags', t('Normal Account'), PAGE_NORMAL, 
									t('This account is a normal personal profile'), 
									($a->user['page-flags'] == PAGE_NORMAL)),
								
		'$page_soapbox' 	=> array('page-flags', t('Soapbox Account'), PAGE_SOAPBOX, 
									t('Automatically approve all connection/friend requests as read-only fans'), 
									($a->user['page-flags'] == PAGE_SOAPBOX)),
									
		'$page_community'	=> array('page-flags', t('Community/Celebrity Account'), PAGE_COMMUNITY, 
									t('Automatically approve all connection/friend requests as read-write fans'), 
									($a->user['page-flags'] == PAGE_COMMUNITY)),
									
		'$page_freelove' 	=> array('page-flags', t('Automatic Friend Account'), PAGE_FREELOVE, 
									t('Automatically approve all connection/friend requests as friends'), 
									($a->user['page-flags'] == PAGE_FREELOVE)),
	));

	$noid = get_config('system','no_openid');

	if($noid) {
		$openid_field = false;
	}
	else {
		$openid_field = array('openid_url', t('OpenID:'),$openid, t("\x28Optional\x29 Allow this OpenID to login to this account."));
	}


	$opt_tpl = get_markup_template("field_yesno.tpl");
	if(get_config('system','publish_all')) {
		$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
	}
	else {
		$profile_in_dir = replace_macros($opt_tpl,array(
			'$field' 	=> array('profile_in_directory', t('Publish your default profile in your local site directory?'), $profile['publish'], '', array(t('No'),t('Yes'))),
		));
	}

	if(strlen(get_config('system','directory_submit_url'))) {
		$profile_in_net_dir = replace_macros($opt_tpl,array(
			'$field' 	=> array('profile_in_netdirectory', t('Publish your default profile in the global social directory?'), $profile['net-publish'], '', array(t('No'),t('Yes'))),
		));
	}
	else
		$profile_in_net_dir = '';


	$hide_friends = replace_macros($opt_tpl,array(
			'$field' 	=> array('hide-friends', t('Hide your contact/friend list from viewers of your default profile?'), $profile['hide-friends'], '', array(t('No'),t('Yes'))),
	));

	$hide_wall = replace_macros($opt_tpl,array(
			'$field' 	=> array('hidewall',  t('Hide your profile details from unknown viewers?'), $a->user['hidewall'], '', array(t('No'),t('Yes'))),

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

	if($invisible)
		info( t('Profile is <strong>not published</strong>.') . EOL );

	



	$subdir = ((strlen($a->get_path())) ? '<br />' . t('or') . ' ' . $a->get_baseurl(true) . '/profile/' . $nickname : '');

	$tpl_addr = get_markup_template("settings_nick_set.tpl");

	$prof_addr = replace_macros($tpl_addr,array(
		'$desc' => t('Your Identity Address is'),
		'$nickname' => $nickname,
		'$subdir' => $subdir,
		'$basepath' => $a->get_hostname()
	));

	$stpl = get_markup_template('settings.tpl');

	$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

	$expire_arr = array(
		'days' => array('expire',  t("Automatically expire posts after this many days:"), $expire, t('If empty, posts will not expire. Expired posts will be deleted')),
		'advanced' => t('Advanced expiration settings'),
		'label' => t('Advanced Expiration'),
		'items' => array('expire_items',  t("Expire posts:"), $expire_items, '', array(t('No'),t('Yes'))),
		'notes' => array('expire_notes',  t("Expire personal notes:"), $expire_notes, '', array(t('No'),t('Yes'))),
		'starred' => array('expire_starred',  t("Expire starred posts:"), $expire_starred, '', array(t('No'),t('Yes'))),
		'photos' => array('expire_photos',  t("Expire photos:"), $expire_photos, '', array(t('No'),t('Yes'))),		
	);

	$o .= replace_macros($stpl,array(
		'$ptitle' 	=> t('Account Settings'),

		'$submit' 	=> t('Submit'),
		'$baseurl' => $a->get_baseurl(true),
		'$uid' => local_user(),
		'$form_security_token' => get_form_security_token("settings"),
		
		'$nickname_block' => $prof_addr,
		
		'$h_pass' 	=> t('Password Settings'),
		'$password1'=> array('npassword', t('New Password:'), '', ''),
		'$password2'=> array('confirm', t('Confirm:'), '', t('Leave password fields blank unless changing')),
		'$oid_enable' => (! get_config('system','no_openid')),
		'$openid'	=> $openid_field,
		
		'$h_basic' 	=> t('Basic Settings'),
		'$username' => array('username',  t('Full Name:'), $username,''),
		'$email' 	=> array('email', t('Email Address:'), $email, ''),
		'$timezone' => array('timezone_select' , t('Your Timezone:'), select_timezone($timezone), ''),
		'$defloc'	=> array('defloc', t('Default Post Location:'), $defloc, ''),
		'$allowloc' => array('allow_location', t('Use Browser Location:'), ($a->user['allow_location'] == 1), ''),
		

		'$h_prv' 	=> t('Security and Privacy Settings'),

		'$maxreq' 	=> array('maxreq', t('Maximum Friend Requests/Day:'), $maxreq ,t("\x28to prevent spam abuse\x29")),
		'$permissions' => t('Default Post Permissions'),
		'$permdesc' => t("\x28click to open/close\x29"),
		'$visibility' => $profile['net-publish'],
		'$aclselect' => populate_acl($a->user,$celeb),
		'$suggestme' => $suggestme,
		'$blockwall'=> $blockwall, // array('blockwall', t('Allow friends to post to your profile page:'), !$blockwall, ''),
		'$blocktags'=> $blocktags, // array('blocktags', t('Allow friends to tag your posts:'), !$blocktags, ''),
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
		'$post_profilechange' => array('post_profilechange',  t('making an <em>interesting</em> profile change'), $post_profilechange, ''),
		'$lbl_not' 	=> t('Send a notification email when:'),
		'$notify1'	=> array('notify1', t('You receive an introduction'), ($notify & NOTIFY_INTRO), NOTIFY_INTRO, ''),
		'$notify2'	=> array('notify2', t('Your introductions are confirmed'), ($notify & NOTIFY_CONFIRM), NOTIFY_CONFIRM, ''),
		'$notify3'	=> array('notify3', t('Someone writes on your profile wall'), ($notify & NOTIFY_WALL), NOTIFY_WALL, ''),
		'$notify4'	=> array('notify4', t('Someone writes a followup comment'), ($notify & NOTIFY_COMMENT), NOTIFY_COMMENT, ''),
		'$notify5'	=> array('notify5', t('You receive a private message'), ($notify & NOTIFY_MAIL), NOTIFY_MAIL, ''),
		'$notify6'  => array('notify6', t('You receive a friend suggestion'), ($notify & NOTIFY_SUGGEST), NOTIFY_SUGGEST, ''),		
		'$notify7'  => array('notify7', t('You are tagged in a post'), ($notify & NOTIFY_TAGSELF), NOTIFY_TAGSELF, ''),		
		
		
		'$h_advn' => t('Advanced Page Settings'),
		'$pagetype' => $pagetype,
		

		
		

		




		

	));

	call_hooks('settings_form',$o);

	$o .= '</form>' . "\r\n";

	return $o;

}}

