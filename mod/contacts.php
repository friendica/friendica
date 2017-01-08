<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');
require_once('include/Scrape.php');
require_once('mod/proxy.php');
require_once('include/Photo.php');

function contacts_init(App &$a) {
	if (! local_user()) {
		return;
	}

	$contact_id = 0;

	if((($a->argc == 2) && intval($a->argv[1])) OR (($a->argc == 3) && intval($a->argv[1]) && ($a->argv[2] == "posts"))) {
		$contact_id = intval($a->argv[1]);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d and `id` = %d LIMIT 1",
			intval(local_user()),
			intval($contact_id)
		);
		if (! dbm::is_result($r)) {
			$contact_id = 0;
		}
	}

	require_once('include/group.php');
	require_once('include/contact_widgets.php');

	if ($_GET['nets'] == "all") {
		$_GET['nets'] = "";
	}

	if (! x($a->page,'aside')) {
		$a->page['aside'] = '';
	}

	if ($contact_id) {
		$a->data['contact'] = $r[0];

		if (($a->data['contact']['network'] != "") AND ($a->data['contact']['network'] != NETWORK_DFRN)) {
			$networkname = format_network_name($a->data['contact']['network'],$a->data['contact']['url']);
		} else {
			$networkname = '';
		}

		/// @TODO Add nice spaces
		$vcard_widget = replace_macros(get_markup_template("vcard-widget.tpl"),array(
			'$name' => htmlentities($a->data['contact']['name']),
			'$photo' => $a->data['contact']['photo'],
			'$url' => ($a->data['contact']['network'] == NETWORK_DFRN) ? "redir/".$a->data['contact']['id'] : $a->data['contact']['url'],
			'$addr' => (($a->data['contact']['addr'] != "") ? ($a->data['contact']['addr']) : ""),
			'$network_name' => $networkname,
			'$network' => t('Network:'),
			'$account_type' => account_type($a->data['contact'])
		));

		$finpeople_widget = '';
		$follow_widget = '';
		$networks_widget = '';
	} else {
		$vcard_widget = '';
		$networks_widget .= networks_widget('contacts',$_GET['nets']);
		if (isset($_GET['add'])) {
			$follow_widget = follow_widget($_GET['add']);
		} else {
			$follow_widget = follow_widget();
		}

		$findpeople_widget .= findpeople_widget();
	}

	$groups_widget .= group_side('contacts','group','full',0,$contact_id);

	$a->page['aside'] .= replace_macros(get_markup_template("contacts-widget-sidebar.tpl"),array(
		'$vcard_widget' => $vcard_widget,
		'$findpeople_widget' => $findpeople_widget,
		'$follow_widget' => $follow_widget,
		'$groups_widget' => $groups_widget,
		'$networks_widget' => $networks_widget
	));

	$base = z_root();
	$tpl = get_markup_template("contacts-head.tpl");
	$a->page['htmlhead'] .= replace_macros($tpl,array(
		'$baseurl' => App::get_baseurl(true),
		'$base' => $base
	));

	$tpl = get_markup_template("contacts-end.tpl");
	$a->page['end'] .= replace_macros($tpl,array(
		'$baseurl' => App::get_baseurl(true),
		'$base' => $base
	));


}

function contacts_batch_actions(App &$a){
	$contacts_id = $_POST['contact_batch'];
	if (!is_array($contacts_id)) return;

	$orig_records = q("SELECT * FROM `contact` WHERE `id` IN (%s) AND `uid` = %d AND `self` = 0",
		implode(",", $contacts_id),
		intval(local_user())
	);

	$count_actions=0;
	foreach($orig_records as $orig_record) {
		$contact_id = $orig_record['id'];
		if (x($_POST, 'contacts_batch_update')) {
			_contact_update($contact_id);
			$count_actions++;
		}
		if (x($_POST, 'contacts_batch_block')) {
			$r  = _contact_block($contact_id, $orig_record);
			if ($r) $count_actions++;
		}
		if (x($_POST, 'contacts_batch_ignore')) {
			$r = _contact_ignore($contact_id, $orig_record);
			if ($r) $count_actions++;
		}
		if (x($_POST, 'contacts_batch_archive')) {
			$r = _contact_archive($contact_id, $orig_record);
			if ($r) $count_actions++;
		}
		if (x($_POST, 'contacts_batch_drop')) {
			_contact_drop($contact_id, $orig_record);
			$count_actions++;
		}
	}
	if ($count_actions>0) {
		info ( sprintf( tt("%d contact edited.", "%d contacts edited.", $count_actions), $count_actions) );
	}

	if (x($_SESSION,'return_url')) {
		goaway('' . $_SESSION['return_url']);
	}
	else {
		goaway('contacts');
	}

}


function contacts_post(App &$a) {

	if (! local_user()) {
		return;
	}

	if ($a->argv[1]==="batch") {
		contacts_batch_actions($a);
		return;
	}

	$contact_id = intval($a->argv[1]);
	if (! $contact_id) {
		return;
	}

	$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval(local_user())
	);

	if (! count($orig_record)) {
		notice( t('Could not access contact record.') . EOL);
		goaway('contacts');
		return; // NOTREACHED
	}

	call_hooks('contact_edit_post', $_POST);

	$profile_id = intval($_POST['profile-assign']);
	if ($profile_id) {
		$r = q("SELECT `id` FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($profile_id),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice( t('Could not locate selected profile.') . EOL);
			return;
		}
	}

	$hidden = intval($_POST['hidden']);

	$notify = intval($_POST['notify']);

	$fetch_further_information = intval($_POST['fetch_further_information']);

	$ffi_keyword_blacklist = fix_mce_lf(escape_tags(trim($_POST['ffi_keyword_blacklist'])));

	$priority = intval($_POST['poll']);
	if($priority > 5 || $priority < 0)
		$priority = 0;

	$info = fix_mce_lf(escape_tags(trim($_POST['info'])));

	$r = q("UPDATE `contact` SET `profile-id` = %d, `priority` = %d , `info` = '%s',
		`hidden` = %d, `notify_new_posts` = %d, `fetch_further_information` = %d,
		`ffi_keyword_blacklist` = '%s' WHERE `id` = %d AND `uid` = %d",
		intval($profile_id),
		intval($priority),
		dbesc($info),
		intval($hidden),
		intval($notify),
		intval($fetch_further_information),
		dbesc($ffi_keyword_blacklist),
		intval($contact_id),
		intval(local_user())
	);
	if($r)
		info( t('Contact updated.') . EOL);
	else
		notice( t('Failed to update contact record.') . EOL);

	$r = q("select * from contact where id = %d and uid = %d limit 1",
		intval($contact_id),
		intval(local_user())
	);
	if($r && dbm::is_result($r))
		$a->data['contact'] = $r[0];

	return;

}

/*contact actions*/
function _contact_update($contact_id) {
	$r = q("SELECT `uid`, `url`, `network` FROM `contact` WHERE `id` = %d", intval($contact_id));
	if (!$r)
		return;

	$uid = $r[0]["uid"];

	if ($uid != local_user())
		return;

	if ($r[0]["network"] == NETWORK_OSTATUS) {
		$result = new_contact($uid, $r[0]["url"], false);

		if ($result['success'])
			$r = q("UPDATE `contact` SET `subhub` = 1 WHERE `id` = %d",
				intval($contact_id));
	} else
		// pull feed and consume it, which should subscribe to the hub.
		proc_run(PRIORITY_HIGH, "include/onepoll.php", $contact_id, "force");
}

function _contact_update_profile($contact_id) {
	$r = q("SELECT `uid`, `url`, `network` FROM `contact` WHERE `id` = %d", intval($contact_id));
	if (!$r)
		return;

	$uid = $r[0]["uid"];

	if ($uid != local_user())
		return;

	$data = probe_url($r[0]["url"]);

	// "Feed" or "Unknown" is mostly a sign of communication problems
	if ((in_array($data["network"], array(NETWORK_FEED, NETWORK_PHANTOM))) AND ($data["network"] != $r[0]["network"]))
		return;

	$updatefields = array("name", "nick", "url", "addr", "batch", "notify", "poll", "request", "confirm",
				"poco", "network", "alias");
	$update = array();

	if ($data["network"] == NETWORK_OSTATUS) {
		$result = new_contact($uid, $data["url"], false);

		if ($result['success'])
			$update["subhub"] = true;
	}

	foreach($updatefields AS $field)
		if (isset($data[$field]) AND ($data[$field] != ""))
			$update[$field] = $data[$field];

	$update["nurl"] = normalise_link($data["url"]);

	$query = "";

	if (isset($data["priority"]) AND ($data["priority"] != 0))
		$query = "`priority` = ".intval($data["priority"]);

	foreach($update AS $key => $value) {
		if ($query != "")
			$query .= ", ";

		$query .= "`".$key."` = '".dbesc($value)."'";
	}

	if ($query == "")
		return;

	$r = q("UPDATE `contact` SET $query WHERE `id` = %d AND `uid` = %d",
		intval($contact_id),
		intval(local_user())
	);

	// Update the entry in the contact table
	update_contact_avatar($data['photo'], local_user(), $contact_id);

	// Update the entry in the gcontact table
	update_gcontact_from_probe($data["url"]);
}

function _contact_block($contact_id, $orig_record) {
	$blocked = (($orig_record['blocked']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `blocked` = %d WHERE `id` = %d AND `uid` = %d",
		intval($blocked),
		intval($contact_id),
		intval(local_user())
	);
	return $r;

}
function _contact_ignore($contact_id, $orig_record) {
	$readonly = (($orig_record['readonly']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `readonly` = %d WHERE `id` = %d AND `uid` = %d",
		intval($readonly),
		intval($contact_id),
		intval(local_user())
	);
	return $r;
}
function _contact_archive($contact_id, $orig_record) {
	$archived = (($orig_record['archive']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `archive` = %d WHERE `id` = %d AND `uid` = %d",
		intval($archived),
		intval($contact_id),
		intval(local_user())
	);
	if ($archived) {
		q("UPDATE `item` SET `private` = 2 WHERE `contact-id` = %d AND `uid` = %d", intval($contact_id), intval(local_user()));
	}
	return $r;
}
function _contact_drop($contact_id, $orig_record) {
	$a = get_app();

	terminate_friendship($a->user,$a->contact,$orig_record);
	contact_remove($orig_record['id']);
}


function contacts_content(App &$a) {

	$sort_type = 0;
	$o = '';
	nav_set_selected('contacts');


	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc == 3) {

		$contact_id = intval($a->argv[1]);
		if(! $contact_id)
			return;

		$cmd = $a->argv[2];

		$orig_record = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 LIMIT 1",
			intval($contact_id),
			intval(local_user())
		);

		if(! count($orig_record)) {
			notice( t('Could not access contact record.') . EOL);
			goaway('contacts');
			return; // NOTREACHED
		}

		if($cmd === 'update') {
			_contact_update($contact_id);
			goaway('contacts/' . $contact_id);
			// NOTREACHED
		}

		if($cmd === 'updateprofile') {
			_contact_update_profile($contact_id);
			goaway('crepair/' . $contact_id);
			// NOTREACHED
		}

		if($cmd === 'block') {
			$r = _contact_block($contact_id, $orig_record[0]);
			if ($r) {
				$blocked = (($orig_record[0]['blocked']) ? 0 : 1);
				info((($blocked) ? t('Contact has been blocked') : t('Contact has been unblocked')).EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if($cmd === 'ignore') {
			$r = _contact_ignore($contact_id, $orig_record[0]);
			if ($r) {
				$readonly = (($orig_record[0]['readonly']) ? 0 : 1);
				info((($readonly) ? t('Contact has been ignored') : t('Contact has been unignored')).EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}


		if($cmd === 'archive') {
			$r = _contact_archive($contact_id, $orig_record[0]);
			if ($r) {
				$archived = (($orig_record[0]['archive']) ? 0 : 1);
				info((($archived) ? t('Contact has been archived') : t('Contact has been unarchived')).EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if($cmd === 'drop') {

			// Check if we should do HTML-based delete confirmation
			if($_REQUEST['confirm']) {
				// <form> can't take arguments in its "action" parameter
				// so add any arguments as hidden inputs
				$query = explode_querystring($a->query_string);
				$inputs = array();
				foreach($query['args'] as $arg) {
					if(strpos($arg, 'confirm=') === false) {
						$arg_parts = explode('=', $arg);
						$inputs[] = array('name' => $arg_parts[0], 'value' => $arg_parts[1]);
					}
				}

				$a->page['aside'] = '';

				return replace_macros(get_markup_template('contact_drop_confirm.tpl'), array(
					'$header' => t('Drop contact'),
					'$contact' => _contact_detail_for_template($orig_record[0]),
					'$method' => 'get',
					'$message' => t('Do you really want to delete this contact?'),
					'$extra_inputs' => $inputs,
					'$confirm' => t('Yes'),
					'$confirm_url' => $query['base'],
					'$confirm_name' => 'confirmed',
					'$cancel' => t('Cancel'),
				));
			}
			// Now check how the user responded to the confirmation query
			if ($_REQUEST['canceled']) {
				if (x($_SESSION,'return_url')) {
					goaway('' . $_SESSION['return_url']);
				}
				else {
					goaway('contacts');
				}
			}

			_contact_drop($contact_id, $orig_record[0]);
			info( t('Contact has been removed.') . EOL );
			if (x($_SESSION,'return_url')) {
				goaway('' . $_SESSION['return_url']);
			}
			else {
				goaway('contacts');
			}
			return; // NOTREACHED
		}
		if ($cmd === 'posts') {
			return contact_posts($a, $contact_id);
		}
	}



	$_SESSION['return_url'] = $a->query_string;

	if((x($a->data,'contact')) && (is_array($a->data['contact']))) {

		$contact_id = $a->data['contact']['id'];
		$contact = $a->data['contact'];

		$editselect = 'none';
		if( feature_enabled(local_user(),'richtext') )
			$editselect = 'exact';

		$a->page['htmlhead'] .= replace_macros(get_markup_template('contact_head.tpl'), array(
			'$baseurl' => App::get_baseurl(true),
			'$editselect' => $editselect,
		));
		$a->page['end'] .= replace_macros(get_markup_template('contact_end.tpl'), array(
			'$baseurl' => App::get_baseurl(true),
			'$editselect' => $editselect,
		));

		require_once('include/contact_selectors.php');

		$tpl = get_markup_template("contact_edit.tpl");

		switch($contact['rel']) {
			case CONTACT_IS_FRIEND:
				$dir_icon = 'images/lrarrow.gif';
				$relation_text = t('You are mutual friends with %s');
				break;
			case CONTACT_IS_FOLLOWER;
				$dir_icon = 'images/larrow.gif';
				$relation_text = t('You are sharing with %s');
				break;

			case CONTACT_IS_SHARING;
				$dir_icon = 'images/rarrow.gif';
				$relation_text = t('%s is sharing with you');
				break;
			default:
				break;
		}

		if(!in_array($contact['network'], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA)))
				$relation_text = "";

		$relation_text = sprintf($relation_text,htmlentities($contact['name']));

		if(($contact['network'] === NETWORK_DFRN) && ($contact['rel'])) {
			$url = "redir/{$contact['id']}";
			$sparkle = ' class="sparkle" ';
		}
		else {
			$url = $contact['url'];
			$sparkle = '';
		}

		$insecure = t('Private communications are not available for this contact.');

		$last_update = (($contact['last-update'] == '0000-00-00 00:00:00')
				? t('Never')
				: datetime_convert('UTC',date_default_timezone_get(),$contact['last-update'],'D, j M Y, g:i A'));

		if($contact['last-update'] !== '0000-00-00 00:00:00')
			$last_update .= ' ' . (($contact['last-update'] <= $contact['success_update']) ? t("\x28Update was successful\x29") : t("\x28Update was not successful\x29"));

		$lblsuggest = (($contact['network'] === NETWORK_DFRN) ? t('Suggest friends') : '');

		$poll_enabled = in_array($contact['network'], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_FEED, NETWORK_MAIL, NETWORK_MAIL2));

		$nettype = sprintf( t('Network type: %s'),network_to_name($contact['network'], $contact["url"]));

		//$common = count_common_friends(local_user(),$contact['id']);
		//$common_text = (($common) ? sprintf( tt('%d contact in common','%d contacts in common', $common),$common) : '');

		$polling = (($contact['network'] === NETWORK_MAIL | $contact['network'] === NETWORK_FEED) ? 'polling' : '');

		//$x = count_all_friends(local_user(), $contact['id']);
		//$all_friends = (($x) ? t('View all contacts') : '');

		// tabs
		$tab_str = contacts_tab($a, $contact_id, 2);

		$lost_contact = (($contact['archive'] && $contact['term-date'] != '0000-00-00 00:00:00' && $contact['term-date'] < datetime_convert('','','now')) ? t('Communications lost with this contact!') : '');

		if ($contact['network'] == NETWORK_FEED)
			$fetch_further_information = array('fetch_further_information', t('Fetch further information for feeds'), $contact['fetch_further_information'], t('Fetch further information for feeds'),
									array('0'=>t('Disabled'), '1'=>t('Fetch information'), '2'=>t('Fetch information and keywords')));

		if (in_array($contact['network'], array(NETWORK_FEED, NETWORK_MAIL, NETWORK_MAIL2)))
			$poll_interval = contact_poll_interval($contact['priority'],(! $poll_enabled));

		if ($contact['network'] == NETWORK_DFRN)
			$profile_select = contact_profile_assign($contact['profile-id'],(($contact['network'] !== NETWORK_DFRN) ? true : false));

		if (in_array($contact['network'], array(NETWORK_DIASPORA, NETWORK_OSTATUS)) AND
			($contact['rel'] == CONTACT_IS_FOLLOWER))
			$follow = App::get_baseurl(true)."/follow?url=".urlencode($contact["url"]);

		// Load contactact related actions like hide, suggest, delete and others
		$contact_actions = contact_actions($contact);


		$o .= replace_macros($tpl, array(
			//'$header' => t('Contact Editor'),
			'$header' => t("Contact"),
			'$tab_str' => $tab_str,
			'$submit' => t('Submit'),
			'$lbl_vis1' => t('Profile Visibility'),
			'$lbl_vis2' => sprintf( t('Please choose the profile you would like to display to %s when viewing your profile securely.'), $contact['name']),
			'$lbl_info1' => t('Contact Information / Notes'),
			'$infedit' => t('Edit contact notes'),
			'$common_text' => $common_text,
			'$common_link' => 'common/loc/' . local_user() . '/' . $contact['id'],
			'$all_friends' => $all_friends,
			'$relation_text' => $relation_text,
			'$visit' => sprintf( t('Visit %s\'s profile [%s]'),$contact['name'],$contact['url']),
			'$blockunblock' => t('Block/Unblock contact'),
			'$ignorecont' => t('Ignore contact'),
			'$lblcrepair' => t("Repair URL settings"),
			'$lblrecent' => t('View conversations'),
			'$lblsuggest' => $lblsuggest,
			//'$delete' => t('Delete contact'),
			'$nettype' => $nettype,
			'$poll_interval' => $poll_interval,
			'$poll_enabled' => $poll_enabled,
			'$lastupdtext' => t('Last update:'),
			'$lost_contact' => $lost_contact,
			'$updpub' => t('Update public posts'),
			'$last_update' => $last_update,
			'$udnow' => t('Update now'),
			'$follow' => $follow,
			'$follow_text' => t("Connect/Follow"),
			'$profile_select' => $profile_select,
			'$contact_id' => $contact['id'],
			'$block_text' => (($contact['blocked']) ? t('Unblock') : t('Block') ),
			'$ignore_text' => (($contact['readonly']) ? t('Unignore') : t('Ignore') ),
			'$insecure' => (($contact['network'] !== NETWORK_DFRN && $contact['network'] !== NETWORK_MAIL && $contact['network'] !== NETWORK_FACEBOOK && $contact['network'] !== NETWORK_DIASPORA) ? $insecure : ''),
			'$info' => $contact['info'],
			'$cinfo' => array('info', '', $contact['info'], ''),
			'$blocked' => (($contact['blocked']) ? t('Currently blocked') : ''),
			'$ignored' => (($contact['readonly']) ? t('Currently ignored') : ''),
			'$archived' => (($contact['archive']) ? t('Currently archived') : ''),
			'$hidden' => array('hidden', t('Hide this contact from others'), ($contact['hidden'] == 1), t('Replies/likes to your public posts <strong>may</strong> still be visible')),
			'$notify' => array('notify', t('Notification for new posts'), ($contact['notify_new_posts'] == 1), t('Send a notification of every new post of this contact')),
			'$fetch_further_information' => $fetch_further_information,
			'$ffi_keyword_blacklist' => $contact['ffi_keyword_blacklist'],
			'$ffi_keyword_blacklist' => array('ffi_keyword_blacklist', t('Blacklisted keywords'), $contact['ffi_keyword_blacklist'], t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')),
			'$photo' => $contact['photo'],
			'$name' => htmlentities($contact['name']),
			'$dir_icon' => $dir_icon,
			'$alt_text' => $alt_text,
			'$sparkle' => $sparkle,
			'$url' => $url,
			'$profileurllabel' => t('Profile URL'),
			'$profileurl' => $contact['url'],
			'$account_type' => account_type($contact),
			'$location' => bbcode($contact["location"]),
			'$location_label' => t("Location:"),
			'$xmpp' => bbcode($contact["xmpp"]),
			'$xmpp_label' => t("XMPP:"),
			'$about' => bbcode($contact["about"], false, false),
			'$about_label' => t("About:"),
			'$keywords' => $contact["keywords"],
			'$keywords_label' => t("Tags:"),
			'$contact_action_button' => t("Actions"),
			'$contact_actions' => $contact_actions,
			'$contact_status' => t("Status"),
			'$contact_settings_label' => t('Contact Settings'),
			'$contact_profile_label' => t("Profile"),

		));

		$arr = array('contact' => $contact,'output' => $o);

		call_hooks('contact_edit', $arr);

		return $arr['output'];

	}

	$blocked = false;
	$hidden = false;
	$ignored = false;
	$all = false;

	if(($a->argc == 2) && ($a->argv[1] === 'all')) {
		$sql_extra = '';
		$all = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'blocked')) {
		$sql_extra = " AND `blocked` = 1 ";
		$blocked = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'hidden')) {
		$sql_extra = " AND `hidden` = 1 ";
		$hidden = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'ignored')) {
		$sql_extra = " AND `readonly` = 1 ";
		$ignored = true;
	}
	elseif(($a->argc == 2) && ($a->argv[1] === 'archived')) {
		$sql_extra = " AND `archive` = 1 ";
		$archived = true;
	}
	else
		$sql_extra = " AND `blocked` = 0 ";

	$search = ((x($_GET,'search')) ? notags(trim($_GET['search'])) : '');
	$nets = ((x($_GET,'nets')) ? notags(trim($_GET['nets'])) : '');

	$tabs = array(
		array(
			'label' => t('Suggestions'),
			'url'   => 'suggest',
			'sel'   => '',
			'title' => t('Suggest potential friends'),
			'id'	=> 'suggestions-tab',
			'accesskey' => 'g',
		),
		array(
			'label' => t('All Contacts'),
			'url'   => 'contacts/all',
			'sel'   => ($all) ? 'active' : '',
			'title' => t('Show all contacts'),
			'id'	=> 'showall-tab',
			'accesskey' => 'l',
		),
		array(
			'label' => t('Unblocked'),
			'url'   => 'contacts',
			'sel'   => ((! $all) && (! $blocked) && (! $hidden) && (! $search) && (! $nets) && (! $ignored) && (! $archived)) ? 'active' : '',
			'title' => t('Only show unblocked contacts'),
			'id'	=> 'showunblocked-tab',
			'accesskey' => 'o',
		),

		array(
			'label' => t('Blocked'),
			'url'   => 'contacts/blocked',
			'sel'   => ($blocked) ? 'active' : '',
			'title' => t('Only show blocked contacts'),
			'id'	=> 'showblocked-tab',
			'accesskey' => 'b',
		),

		array(
			'label' => t('Ignored'),
			'url'   => 'contacts/ignored',
			'sel'   => ($ignored) ? 'active' : '',
			'title' => t('Only show ignored contacts'),
			'id'	=> 'showignored-tab',
			'accesskey' => 'i',
		),

		array(
			'label' => t('Archived'),
			'url'   => 'contacts/archived',
			'sel'   => ($archived) ? 'active' : '',
			'title' => t('Only show archived contacts'),
			'id'	=> 'showarchived-tab',
			'accesskey' => 'y',
		),

		array(
			'label' => t('Hidden'),
			'url'   => 'contacts/hidden',
			'sel'   => ($hidden) ? 'active' : '',
			'title' => t('Only show hidden contacts'),
			'id'	=> 'showhidden-tab',
			'accesskey' => 'h',
		),

	);

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));



	$searching = false;
	if($search) {
		$search_hdr = $search;
		$search_txt = dbesc(protect_sprintf(preg_quote($search)));
		$searching = true;
	}
	$sql_extra .= (($searching) ? " AND (name REGEXP '$search_txt' OR url REGEXP '$search_txt'  OR nick REGEXP '$search_txt') " : "");

	if($nets)
		$sql_extra .= sprintf(" AND network = '%s' ", dbesc($nets));

	$sql_extra2 = ((($sort_type > 0) && ($sort_type <= CONTACT_IS_FRIEND)) ? sprintf(" AND `rel` = %d ",intval($sort_type)) : '');


	$r = q("SELECT COUNT(*) AS `total` FROM `contact`
		WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 ",
		intval($_SESSION['uid']));
	if (dbm::is_result($r)) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}

	$sql_extra3 = unavailable_networks();

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 $sql_extra3 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($_SESSION['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$contacts = array();

	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$contacts[] = _contact_detail_for_template($rr);
		}
	}

	$tpl = get_markup_template("contacts-template.tpl");
	$o .= replace_macros($tpl, array(
		'$baseurl' => z_root(),
		'$header' => t('Contacts') . (($nets) ? ' - ' . network_to_name($nets) : ''),
		'$tabs' => $t,
		'$total' => $total,
		'$search' => $search_hdr,
		'$desc' => t('Search your contacts'),
		'$finding' => (($searching) ? sprintf(t('Results for: %s'),$search) : ""),
		'$submit' => t('Find'),
		'$cmd' => $a->cmd,
		'$contacts' => $contacts,
		'$contact_drop_confirm' => t('Do you really want to delete this contact?'),
		'multiselect' => 1,
		'$batch_actions' => array(
			'contacts_batch_update' => t('Update'),
			'contacts_batch_block' => t('Block')."/".t("Unblock"),
			"contacts_batch_ignore" => t('Ignore')."/".t("Unignore"),
			"contacts_batch_archive" => t('Archive')."/".t("Unarchive"),
			"contacts_batch_drop" => t('Delete'),
		),
		'$h_batch_actions' => t('Batch Actions'),
		'$paginate' => paginate($a),

	));

	return $o;
}

/**
 * @brief List of pages for the Contact TabBar
 * 
 * Available Pages are 'Status', 'Profile', 'Contacts' and 'Common Friends'
 * 
 * @param app $a
 * @param int $contact_id The ID of the contact
 * @param int $active_tab 1 if tab should be marked as active
 * 
 * @return array with with contact TabBar data
 */
function contacts_tab($a, $contact_id, $active_tab) {
	// tabs
	$tabs = array(
		array(
			'label'=>t('Status'),
			'url' => "contacts/".$contact_id."/posts",
			'sel' => (($active_tab == 1)?'active':''),
			'title' => t('Status Messages and Posts'),
			'id' => 'status-tab',
			'accesskey' => 'm',
		),
		array(
			'label'=>t('Profile'),
			'url' => "contacts/".$contact_id,
			'sel' => (($active_tab == 2)?'active':''),
			'title' => t('Profile Details'),
			'id' => 'status-tab',
			'accesskey' => 'o',
		)
	);

	// Show this tab only if there is visible friend list
	$x = count_all_friends(local_user(), $contact_id);
	if ($x)
		$tabs[] = array('label'=>t('Contacts'),
				'url' => "allfriends/".$contact_id,
				'sel' => (($active_tab == 3)?'active':''),
				'title' => t('View all contacts'),
				'id' => 'allfriends-tab',
				'accesskey' => 't');

	// Show this tab only if there is visible common friend list
	$common = count_common_friends(local_user(),$contact_id);
	if ($common)
		$tabs[] = array('label'=>t('Common Friends'),
				'url' => "common/loc/".local_user()."/".$contact_id,
				'sel' => (($active_tab == 4)?'active':''),
				'title' => t('View all common friends'),
				'id' => 'common-loc-tab',
				'accesskey' => 'd');

	$tabs[] = array('label' => t('Advanced'),
			'url'   => 'crepair/' . $contact_id,
			'sel' => (($active_tab == 5)?'active':''),
			'title' => t('Advanced Contact Settings'),
			'id'	=> 'advanced-tab',
			'accesskey' => 'r');

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$tab_str = replace_macros($tab_tpl, array('$tabs' => $tabs));

	return $tab_str;
}

function contact_posts($a, $contact_id) {

	$r = q("SELECT `url` FROM `contact` WHERE `id` = %d", intval($contact_id));
	if ($r) {
		$contact = $r[0];
		$a->page['aside'] = "";
		profile_load($a, "", 0, get_contact_details_by_url($contact["url"]));
	} else
		$profile = "";

	$tab_str = contacts_tab($a, $contact_id, 1);

	$o .= $tab_str;

	$o .= posts_from_contact_url($a, $contact["url"]);

	return $o;
}

function _contact_detail_for_template($rr){

	switch($rr['rel']) {
		case CONTACT_IS_FRIEND:
			$dir_icon = 'images/lrarrow.gif';
			$alt_text = t('Mutual Friendship');
			break;
		case  CONTACT_IS_FOLLOWER;
			$dir_icon = 'images/larrow.gif';
			$alt_text = t('is a fan of yours');
			break;
		case CONTACT_IS_SHARING;
			$dir_icon = 'images/rarrow.gif';
			$alt_text = t('you are a fan of');
			break;
		default:
			break;
	}
	if(($rr['network'] === NETWORK_DFRN) && ($rr['rel'])) {
		$url = "redir/{$rr['id']}";
		$sparkle = ' class="sparkle" ';
	}
	else {
		$url = $rr['url'];
		$sparkle = '';
	}

	return array(
		'img_hover' => sprintf( t('Visit %s\'s profile [%s]'),$rr['name'],$rr['url']),
		'edit_hover' => t('Edit contact'),
		'photo_menu' => contact_photo_menu($rr),
		'id' => $rr['id'],
		'alt_text' => $alt_text,
		'dir_icon' => $dir_icon,
		'thumb' => proxy_url($rr['thumb'], false, PROXY_SIZE_THUMB),
		'name' => htmlentities($rr['name']),
		'username' => htmlentities($rr['name']),
		'account_type' => account_type($rr),
		'sparkle' => $sparkle,
		'itemurl' => (($rr['addr'] != "") ? $rr['addr'] : $rr['url']),
		'url' => $url,
		'network' => network_to_name($rr['network'], $rr['url']),
	);

}

/**
 * @brief Gives a array with actions which can performed to a given contact
 * 
 * This includes actions like e.g. 'block', 'hide', 'archive', 'delete' and others
 * 
 * @param array $contact Data about the Contact
 * @return array with contact related actions
 */
function contact_actions($contact) {

	$poll_enabled = in_array($contact['network'], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_FEED, NETWORK_MAIL, NETWORK_MAIL2));
	$contact_action = array();

	// Provide friend suggestion only for Friendica contacts
	if($contact['network'] === NETWORK_DFRN) {
		$contact_actions['suggest'] = array(
							'label' => t('Suggest friends'),
							'url'	=> 'fsuggest/' . $contact['id'],
							'title'	=> '',
							'sel'	=> '',
							'id'	=>  'suggest',
					);
	}

	if($poll_enabled) {
		$contact_actions['update'] = array(
							'label'	=> t('Update now'),
							'url'	=> 'contacts/' . $contact['id'] . '/update',
							'title'	=> '',
							'sel'	=> '',
							'id'	=> 'update',
					);
	}

	$contact_actions['block'] = array(
						'label'	=> (intval($contact['blocked']) ? t('Unblock') : t('Block') ),
						'url'	=> 'contacts/' . $contact['id'] . '/block',
						'title' => t('Toggle Blocked status'),
						'sel'	=> (intval($contact['blocked']) ? 'active' : ''),
						'id'	=> 'toggle-block',
				);

	$contact_actions['ignore'] = array(
						'label'	=> (intval($contact['readonly']) ? t('Unignore') : t('Ignore') ),
						'url'	=> 'contacts/' . $contact['id'] . '/ignore',
						'title' => t('Toggle Ignored status'),
						'sel'	=> (intval($contact['readonly']) ? 'active' : ''),
						'id'	=> 'toggle-ignore',
				);

	$contact_actions['archive'] = array(
						'label'	=> (intval($contact['archive']) ? t('Unarchive') : t('Archive') ),
						'url'	=> 'contacts/' . $contact['id'] . '/archive',
						'title' => t('Toggle Archive status'),
						'sel'	=> (intval($contact['archive']) ? 'active' : ''),
						'id'	=> 'toggle-archive',
				);

	$contact_actions['delete'] = array(
						'label'	=> t('Delete'),
						'url'	=> 'contacts/' . $contact['id'] . '/drop', 
						'title'	=> t('Delete contact'),
						'sel'	=> '',
						'id'	=> 'delete',
				);

	return $contact_actions;
}
