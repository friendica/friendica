<?php
/**
 * @file mod/contacts.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\Addon;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Group;
use Friendica\Model\Profile;
use Friendica\Network\Probe;

require_once 'mod/proxy.php';

function contacts_init(App $a)
{
	if (!local_user()) {
		return;
	}

	$nets = defaults($_GET, 'nets', '');
	if ($nets == "all") {
		$nets = "";
	}

	if (!x($a->page, 'aside')) {
		$a->page['aside'] = '';
	}

	$contact_id = null;
	$contact = null;
	if ((($a->argc == 2) && intval($a->argv[1])) || (($a->argc == 3) && intval($a->argv[1]) && ($a->argv[2] == "posts"))) {
		$contact_id = intval($a->argv[1]);
		$contact = dba::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);
	}

	if (DBM::is_result($contact)) {
		$a->data['contact'] = $contact;

		if (($a->data['contact']['network'] != "") && ($a->data['contact']['network'] != NETWORK_DFRN)) {
			$networkname = format_network_name($a->data['contact']['network'], $a->data['contact']['url']);
		} else {
			$networkname = '';
		}

		/// @TODO Add nice spaces
		$vcard_widget = replace_macros(get_markup_template("vcard-widget.tpl"), [
			'$name' => htmlentities($a->data['contact']['name']),
			'$photo' => $a->data['contact']['photo'],
			'$url' => ($a->data['contact']['network'] == NETWORK_DFRN) ? "redir/" . $a->data['contact']['id'] : $a->data['contact']['url'],
			'$addr' => (($a->data['contact']['addr'] != "") ? ($a->data['contact']['addr']) : ""),
			'$network_name' => $networkname,
			'$network' => t('Network:'),
			'$account_type' => Contact::getAccountType($a->data['contact'])
		]);

		$findpeople_widget = '';
		$follow_widget = '';
		$networks_widget = '';
	} else {
		$vcard_widget = '';
		$networks_widget = Widget::networks('contacts', $nets);
		if (isset($_GET['add'])) {
			$follow_widget = Widget::follow($_GET['add']);
		} else {
			$follow_widget = Widget::follow();
		}

		$findpeople_widget = Widget::findPeople();
	}

	$groups_widget = Group::sidebarWidget('contacts', 'group', 'full', 0, $contact_id);

	$a->page['aside'] .= replace_macros(get_markup_template("contacts-widget-sidebar.tpl"), [
		'$vcard_widget' => $vcard_widget,
		'$findpeople_widget' => $findpeople_widget,
		'$follow_widget' => $follow_widget,
		'$groups_widget' => $groups_widget,
		'$networks_widget' => $networks_widget
	]);

	$base = System::baseUrl();
	$tpl = get_markup_template("contacts-head.tpl");
	$a->page['htmlhead'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(true),
		'$base' => $base
	]);

	$tpl = get_markup_template("contacts-end.tpl");
	$a->page['end'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(true),
		'$base' => $base
	]);
}

function contacts_batch_actions(App $a)
{
	$contacts_id = $_POST['contact_batch'];
	if (!is_array($contacts_id)) {
		return;
	}

	$orig_records = q("SELECT * FROM `contact` WHERE `id` IN (%s) AND `uid` = %d AND `self` = 0",
		implode(",", $contacts_id),
		intval(local_user())
	);

	$count_actions = 0;
	foreach ($orig_records as $orig_record) {
		$contact_id = $orig_record['id'];
		if (x($_POST, 'contacts_batch_update')) {
			_contact_update($contact_id);
			$count_actions++;
		}
		if (x($_POST, 'contacts_batch_block')) {
			$r = _contact_block($contact_id, $orig_record);
			if ($r) {
				$count_actions++;
			}
		}
		if (x($_POST, 'contacts_batch_ignore')) {
			$r = _contact_ignore($contact_id, $orig_record);
			if ($r) {
				$count_actions++;
			}
		}
		if (x($_POST, 'contacts_batch_archive')) {
			$r = _contact_archive($contact_id, $orig_record);
			if ($r) {
				$count_actions++;
			}
		}
		if (x($_POST, 'contacts_batch_drop')) {
			_contact_drop($orig_record);
			$count_actions++;
		}
	}
	if ($count_actions > 0) {
		info(tt("%d contact edited.", "%d contacts edited.", $count_actions));
	}

	if (x($_SESSION, 'return_url')) {
		goaway('' . $_SESSION['return_url']);
	} else {
		goaway('contacts');
	}
}

function contacts_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if ($a->argv[1] === "batch") {
		contacts_batch_actions($a);
		return;
	}

	$contact_id = intval($a->argv[1]);
	if (!$contact_id) {
		return;
	}

	if (!dba::exists('contact', ['id' => $contact_id, 'uid' => local_user()])) {
		notice(t('Could not access contact record.') . EOL);
		goaway('contacts');
		return; // NOTREACHED
	}

	Addon::callHooks('contact_edit_post', $_POST);

	$profile_id = intval($_POST['profile-assign']);
	if ($profile_id) {
		if (!dba::exists('profile', ['id' => $profile_id, 'uid' => local_user()])) {
			notice(t('Could not locate selected profile.') . EOL);
			return;
		}
	}

	$hidden = intval($_POST['hidden']);

	$notify = intval($_POST['notify']);

	$fetch_further_information = intval($_POST['fetch_further_information']);

	$ffi_keyword_blacklist = escape_tags(trim($_POST['ffi_keyword_blacklist']));

	$priority = intval($_POST['poll']);
	if ($priority > 5 || $priority < 0) {
		$priority = 0;
	}

	$info = escape_tags(trim($_POST['info']));

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
	if (DBM::is_result($r)) {
		info(t('Contact updated.') . EOL);
	} else {
		notice(t('Failed to update contact record.') . EOL);
	}

	$contact = dba::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);
	if (DBM::is_result($contact)) {
		$a->data['contact'] = $contact;
	}

	return;
}

/* contact actions */

function _contact_update($contact_id)
{
	$contact = dba::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => local_user()]);
	if (!DBM::is_result($contact)) {
		return;
	}

	$uid = $contact["uid"];

	if ($contact["network"] == NETWORK_OSTATUS) {
		$result = Contact::createFromProbe($uid, $contact["url"], false, $contact["network"]);

		if ($result['success']) {
			q("UPDATE `contact` SET `subhub` = 1 WHERE `id` = %d", intval($contact_id));
		}
	} else {
		// pull feed and consume it, which should subscribe to the hub.
		Worker::add(PRIORITY_HIGH, "OnePoll", $contact_id, "force");
	}
}

function _contact_update_profile($contact_id)
{
	$contact = dba::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => local_user()]);
	if (!DBM::is_result($contact)) {
		return;
	}

	$uid = $contact["uid"];

	$data = Probe::uri($contact["url"], "", 0, false);

	// "Feed" or "Unknown" is mostly a sign of communication problems
	if ((in_array($data["network"], [NETWORK_FEED, NETWORK_PHANTOM])) && ($data["network"] != $contact["network"])) {
		return;
	}

	$updatefields = ["name", "nick", "url", "addr", "batch", "notify", "poll", "request", "confirm",
		"poco", "network", "alias"];
	$update = [];

	if ($data["network"] == NETWORK_OSTATUS) {
		$result = Contact::createFromProbe($uid, $data["url"], false);

		if ($result['success']) {
			$update["subhub"] = true;
		}
	}

	foreach ($updatefields AS $field) {
		if (isset($data[$field]) && ($data[$field] != "")) {
			$update[$field] = $data[$field];
		}
	}

	$update["nurl"] = normalise_link($data["url"]);

	$query = "";

	if (isset($data["priority"]) && ($data["priority"] != 0)) {
		$query = "`priority` = " . intval($data["priority"]);
	}

	foreach ($update AS $key => $value) {
		if ($query != "") {
			$query .= ", ";
		}

		$query .= "`" . $key . "` = '" . dbesc($value) . "'";
	}

	if ($query == "") {
		return;
	}

	$r = q("UPDATE `contact` SET $query WHERE `id` = %d AND `uid` = %d",
		intval($contact_id),
		intval(local_user())
	);

	// Update the entry in the contact table
	Contact::updateAvatar($data['photo'], local_user(), $contact_id, true);

	// Update the entry in the gcontact table
	GContact::updateFromProbe($data["url"]);
}

function _contact_block($contact_id, $orig_record)
{
	$blocked = (($orig_record['blocked']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `blocked` = %d WHERE `id` = %d AND `uid` = %d",
		intval($blocked),
		intval($contact_id),
		intval(local_user())
	);
	return DBM::is_result($r);
}

function _contact_ignore($contact_id, $orig_record)
{
	$readonly = (($orig_record['readonly']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `readonly` = %d WHERE `id` = %d AND `uid` = %d",
		intval($readonly),
		intval($contact_id),
		intval(local_user())
	);
	return DBM::is_result($r);
}

function _contact_archive($contact_id, $orig_record)
{
	$archived = (($orig_record['archive']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `archive` = %d WHERE `id` = %d AND `uid` = %d",
		intval($archived),
		intval($contact_id),
		intval(local_user())
	);
	if ($archived) {
		q("UPDATE `item` SET `private` = 2 WHERE `contact-id` = %d AND `uid` = %d", intval($contact_id), intval(local_user()));
	}
	return DBM::is_result($r);
}

function _contact_drop($orig_record)
{
	$a = get_app();

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
		WHERE `user`.`uid` = %d AND `contact`.`self` LIMIT 1",
		intval($a->user['uid'])
	);
	if (!DBM::is_result($r)) {
		return;
	}

	Contact::terminateFriendship($r[0], $orig_record);
	Contact::remove($orig_record['id']);
}

function contacts_content(App $a)
{
	$sort_type = 0;
	$o = '';
	Nav::setSelected('contacts');

	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	if ($a->argc == 3) {
		$contact_id = intval($a->argv[1]);
		if (!$contact_id) {
			return;
		}

		$cmd = $a->argv[2];

		$orig_record = dba::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user(), 'self' => false]);
		if (!DBM::is_result($orig_record)) {
			notice(t('Could not access contact record.') . EOL);
			goaway('contacts');
			return; // NOTREACHED
		}

		if ($cmd === 'update') {
			_contact_update($contact_id);
			goaway('contacts/' . $contact_id);
			// NOTREACHED
		}

		if ($cmd === 'updateprofile') {
			_contact_update_profile($contact_id);
			goaway('crepair/' . $contact_id);
			// NOTREACHED
		}

		if ($cmd === 'block') {
			$r = _contact_block($contact_id, $orig_record);
			if ($r) {
				$blocked = (($orig_record['blocked']) ? 0 : 1);
				info((($blocked) ? t('Contact has been blocked') : t('Contact has been unblocked')) . EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if ($cmd === 'ignore') {
			$r = _contact_ignore($contact_id, $orig_record);
			if ($r) {
				$readonly = (($orig_record['readonly']) ? 0 : 1);
				info((($readonly) ? t('Contact has been ignored') : t('Contact has been unignored')) . EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if ($cmd === 'archive') {
			$r = _contact_archive($contact_id, $orig_record);
			if ($r) {
				$archived = (($orig_record['archive']) ? 0 : 1);
				info((($archived) ? t('Contact has been archived') : t('Contact has been unarchived')) . EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if ($cmd === 'drop') {
			// Check if we should do HTML-based delete confirmation
			if (x($_REQUEST, 'confirm')) {
				// <form> can't take arguments in its "action" parameter
				// so add any arguments as hidden inputs
				$query = explode_querystring($a->query_string);
				$inputs = [];
				foreach ($query['args'] as $arg) {
					if (strpos($arg, 'confirm=') === false) {
						$arg_parts = explode('=', $arg);
						$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
					}
				}

				$a->page['aside'] = '';

				return replace_macros(get_markup_template('contact_drop_confirm.tpl'), [
					'$header' => t('Drop contact'),
					'$contact' => _contact_detail_for_template($orig_record),
					'$method' => 'get',
					'$message' => t('Do you really want to delete this contact?'),
					'$extra_inputs' => $inputs,
					'$confirm' => t('Yes'),
					'$confirm_url' => $query['base'],
					'$confirm_name' => 'confirmed',
					'$cancel' => t('Cancel'),
				]);
			}
			// Now check how the user responded to the confirmation query
			if (x($_REQUEST, 'canceled')) {
				if (x($_SESSION, 'return_url')) {
					goaway('' . $_SESSION['return_url']);
				} else {
					goaway('contacts');
				}
			}

			_contact_drop($orig_record);
			info(t('Contact has been removed.') . EOL);
			if (x($_SESSION, 'return_url')) {
				goaway('' . $_SESSION['return_url']);
			} else {
				goaway('contacts');
			}
			return; // NOTREACHED
		}
		if ($cmd === 'posts') {
			return contact_posts($a, $contact_id);
		}
	}

	$_SESSION['return_url'] = $a->query_string;

	if ((x($a->data, 'contact')) && (is_array($a->data['contact']))) {
		$contact_id = $a->data['contact']['id'];
		$contact = $a->data['contact'];

		$a->page['htmlhead'] .= replace_macros(get_markup_template('contact_head.tpl'), [
			'$baseurl' => System::baseUrl(true),
		]);
		$a->page['end'] .= replace_macros(get_markup_template('contact_end.tpl'), [
			'$baseurl' => System::baseUrl(true),
		]);

		$dir_icon = '';
		$relation_text = '';
		switch ($contact['rel']) {
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

		if (!in_array($contact['network'], [NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA])) {
			$relation_text = "";
		}

		$relation_text = sprintf($relation_text, htmlentities($contact['name']));

		if (($contact['network'] === NETWORK_DFRN) && ($contact['rel'])) {
			$url = "redir/{$contact['id']}";
			$sparkle = ' class="sparkle" ';
		} else {
			$url = $contact['url'];
			$sparkle = '';
		}

		$insecure = t('Private communications are not available for this contact.');

		$last_update = (($contact['last-update'] <= NULL_DATE) ? t('Never') : datetime_convert('UTC', date_default_timezone_get(), $contact['last-update'], 'D, j M Y, g:i A'));

		if ($contact['last-update'] > NULL_DATE) {
			$last_update .= ' ' . (($contact['last-update'] <= $contact['success_update']) ? t("\x28Update was successful\x29") : t("\x28Update was not successful\x29"));
		}
		$lblsuggest = (($contact['network'] === NETWORK_DFRN) ? t('Suggest friends') : '');

		$poll_enabled = in_array($contact['network'], [NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_FEED, NETWORK_MAIL]);

		$nettype = t('Network type: %s', ContactSelector::networkToName($contact['network'], $contact["url"]));

		// tabs
		$tab_str = contacts_tab($a, $contact_id, 2);

		$lost_contact = (($contact['archive'] && $contact['term-date'] > NULL_DATE && $contact['term-date'] < datetime_convert('', '', 'now')) ? t('Communications lost with this contact!') : '');

		$fetch_further_information = null;
		if ($contact['network'] == NETWORK_FEED) {
			$fetch_further_information = [
				'fetch_further_information',
				t('Fetch further information for feeds'),
				$contact['fetch_further_information'],
				t("Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn't contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags."),
				['0' => t('Disabled'),
					'1' => t('Fetch information'),
					'3' => t('Fetch keywords'),
					'2' => t('Fetch information and keywords')
				]
			];
		}

		$poll_interval = null;
		if (in_array($contact['network'], [NETWORK_FEED, NETWORK_MAIL])) {
			$poll_interval = ContactSelector::pollInterval($contact['priority'], (!$poll_enabled));
		}

		$profile_select = null;
		if ($contact['network'] == NETWORK_DFRN) {
			$profile_select = ContactSelector::profileAssign($contact['profile-id'], (($contact['network'] !== NETWORK_DFRN) ? true : false));
		}

		$follow = '';
		$follow_text = '';
		if (in_array($contact['network'], [NETWORK_DIASPORA, NETWORK_OSTATUS])) {
			if ($contact['rel'] == CONTACT_IS_FOLLOWER) {
				$follow = System::baseUrl(true) . "/follow?url=" . urlencode($contact["url"]);
				$follow_text = t("Connect/Follow");
			} elseif ($contact['rel'] == CONTACT_IS_FRIEND) {
				$follow = System::baseUrl(true) . "/unfollow?url=" . urlencode($contact["url"]);
				$follow_text = t("Disconnect/Unfollow");
			}
		}

		// Load contactact related actions like hide, suggest, delete and others
		$contact_actions = contact_actions($contact);

		$tpl = get_markup_template("contact_edit.tpl");
		$o .= replace_macros($tpl, [
			'$header' => t("Contact"),
			'$tab_str' => $tab_str,
			'$submit' => t('Submit'),
			'$lbl_vis1' => t('Profile Visibility'),
			'$lbl_vis2' => t('Please choose the profile you would like to display to %s when viewing your profile securely.', $contact['name']),
			'$lbl_info1' => t('Contact Information / Notes'),
			'$lbl_info2' => t('Their personal note'),
			'$reason' => trim(notags($contact['reason'])),
			'$infedit' => t('Edit contact notes'),
			'$common_link' => 'common/loc/' . local_user() . '/' . $contact['id'],
			'$relation_text' => $relation_text,
			'$visit' => t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
			'$blockunblock' => t('Block/Unblock contact'),
			'$ignorecont' => t('Ignore contact'),
			'$lblcrepair' => t("Repair URL settings"),
			'$lblrecent' => t('View conversations'),
			'$lblsuggest' => $lblsuggest,
			'$nettype' => $nettype,
			'$poll_interval' => $poll_interval,
			'$poll_enabled' => $poll_enabled,
			'$lastupdtext' => t('Last update:'),
			'$lost_contact' => $lost_contact,
			'$updpub' => t('Update public posts'),
			'$last_update' => $last_update,
			'$udnow' => t('Update now'),
			'$follow' => $follow,
			'$follow_text' => $follow_text,
			'$profile_select' => $profile_select,
			'$contact_id' => $contact['id'],
			'$block_text' => (($contact['blocked']) ? t('Unblock') : t('Block') ),
			'$ignore_text' => (($contact['readonly']) ? t('Unignore') : t('Ignore') ),
			'$insecure' => (($contact['network'] !== NETWORK_DFRN && $contact['network'] !== NETWORK_MAIL && $contact['network'] !== NETWORK_FACEBOOK && $contact['network'] !== NETWORK_DIASPORA) ? $insecure : ''),
			'$info' => $contact['info'],
			'$cinfo' => ['info', '', $contact['info'], ''],
			'$blocked' => (($contact['blocked']) ? t('Currently blocked') : ''),
			'$ignored' => (($contact['readonly']) ? t('Currently ignored') : ''),
			'$archived' => (($contact['archive']) ? t('Currently archived') : ''),
			'$pending' => (($contact['pending']) ? t('Awaiting connection acknowledge') : ''),
			'$hidden' => ['hidden', t('Hide this contact from others'), ($contact['hidden'] == 1), t('Replies/likes to your public posts <strong>may</strong> still be visible')],
			'$notify' => ['notify', t('Notification for new posts'), ($contact['notify_new_posts'] == 1), t('Send a notification of every new post of this contact')],
			'$fetch_further_information' => $fetch_further_information,
			'$ffi_keyword_blacklist' => $contact['ffi_keyword_blacklist'],
			'$ffi_keyword_blacklist' => ['ffi_keyword_blacklist', t('Blacklisted keywords'), $contact['ffi_keyword_blacklist'], t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')],
			'$photo' => $contact['photo'],
			'$name' => htmlentities($contact['name']),
			'$dir_icon' => $dir_icon,
			'$sparkle' => $sparkle,
			'$url' => $url,
			'$profileurllabel' => t('Profile URL'),
			'$profileurl' => $contact['url'],
			'$account_type' => Contact::getAccountType($contact),
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
		]);

		$arr = ['contact' => $contact, 'output' => $o];

		Addon::callHooks('contact_edit', $arr);

		return $arr['output'];
	}

	$blocked = false;
	$hidden = false;
	$ignored = false;
	$archived = false;
	$all = false;

	if (($a->argc == 2) && ($a->argv[1] === 'all')) {
		$sql_extra = '';
		$all = true;
	} elseif (($a->argc == 2) && ($a->argv[1] === 'blocked')) {
		$sql_extra = " AND `blocked` = 1 ";
		$blocked = true;
	} elseif (($a->argc == 2) && ($a->argv[1] === 'hidden')) {
		$sql_extra = " AND `hidden` = 1 ";
		$hidden = true;
	} elseif (($a->argc == 2) && ($a->argv[1] === 'ignored')) {
		$sql_extra = " AND `readonly` = 1 ";
		$ignored = true;
	} elseif (($a->argc == 2) && ($a->argv[1] === 'archived')) {
		$sql_extra = " AND `archive` = 1 ";
		$archived = true;
	} else {
		$sql_extra = " AND `blocked` = 0 ";
	}

	$search = x($_GET, 'search') ? notags(trim($_GET['search'])) : '';
	$nets   = x($_GET, 'nets'  ) ? notags(trim($_GET['nets']))   : '';

	$tabs = [
		[
			'label' => t('Suggestions'),
			'url'   => 'suggest',
			'sel'   => '',
			'title' => t('Suggest potential friends'),
			'id'    => 'suggestions-tab',
			'accesskey' => 'g',
		],
		[
			'label' => t('All Contacts'),
			'url'   => 'contacts/all',
			'sel'   => ($all) ? 'active' : '',
			'title' => t('Show all contacts'),
			'id'    => 'showall-tab',
			'accesskey' => 'l',
		],
		[
			'label' => t('Unblocked'),
			'url'   => 'contacts',
			'sel'   => ((!$all) && (!$blocked) && (!$hidden) && (!$search) && (!$nets) && (!$ignored) && (!$archived)) ? 'active' : '',
			'title' => t('Only show unblocked contacts'),
			'id'    => 'showunblocked-tab',
			'accesskey' => 'o',
		],
		[
			'label' => t('Blocked'),
			'url'   => 'contacts/blocked',
			'sel'   => ($blocked) ? 'active' : '',
			'title' => t('Only show blocked contacts'),
			'id'    => 'showblocked-tab',
			'accesskey' => 'b',
		],
		[
			'label' => t('Ignored'),
			'url'   => 'contacts/ignored',
			'sel'   => ($ignored) ? 'active' : '',
			'title' => t('Only show ignored contacts'),
			'id'    => 'showignored-tab',
			'accesskey' => 'i',
		],
		[
			'label' => t('Archived'),
			'url'   => 'contacts/archived',
			'sel'   => ($archived) ? 'active' : '',
			'title' => t('Only show archived contacts'),
			'id'    => 'showarchived-tab',
			'accesskey' => 'y',
		],
		[
			'label' => t('Hidden'),
			'url'   => 'contacts/hidden',
			'sel'   => ($hidden) ? 'active' : '',
			'title' => t('Only show hidden contacts'),
			'id'    => 'showhidden-tab',
			'accesskey' => 'h',
		],
	];

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$t = replace_macros($tab_tpl, ['$tabs' => $tabs]);

	$total = 0;
	$searching = false;
	$search_hdr = null;
	if ($search) {
		$searching = true;
		$search_hdr = $search;
		$search_txt = dbesc(protect_sprintf(preg_quote($search)));
		$sql_extra .= " AND (name REGEXP '$search_txt' OR url REGEXP '$search_txt'  OR nick REGEXP '$search_txt') ";
	}

	if ($nets) {
		$sql_extra .= sprintf(" AND network = '%s' ", dbesc($nets));
	}

	$sql_extra2 = ((($sort_type > 0) && ($sort_type <= CONTACT_IS_FRIEND)) ? sprintf(" AND `rel` = %d ", intval($sort_type)) : '');

	$r = q("SELECT COUNT(*) AS `total` FROM `contact`
		WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 ",
		intval($_SESSION['uid'])
	);
	if (DBM::is_result($r)) {
		$a->set_pager_total($r[0]['total']);
		$total = $r[0]['total'];
	}

	$sql_extra3 = Widget::unavailableNetworks();

	$contacts = [];

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 $sql_extra3 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($_SESSION['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			$contacts[] = _contact_detail_for_template($rr);
		}
	}

	$tpl = get_markup_template("contacts-template.tpl");
	$o .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$header' => t('Contacts') . (($nets) ? ' - ' . ContactSelector::networkToName($nets) : ''),
		'$tabs' => $t,
		'$total' => $total,
		'$search' => $search_hdr,
		'$desc' => t('Search your contacts'),
		'$finding' => $searching ? t('Results for: %s', $search) : "",
		'$submit' => t('Find'),
		'$cmd' => $a->cmd,
		'$contacts' => $contacts,
		'$contact_drop_confirm' => t('Do you really want to delete this contact?'),
		'multiselect' => 1,
		'$batch_actions' => [
			'contacts_batch_update'  => t('Update'),
			'contacts_batch_block'   => t('Block') . "/" . t("Unblock"),
			"contacts_batch_ignore"  => t('Ignore') . "/" . t("Unignore"),
			"contacts_batch_archive" => t('Archive') . "/" . t("Unarchive"),
			"contacts_batch_drop"    => t('Delete'),
		],
		'$h_batch_actions' => t('Batch Actions'),
		'$paginate' => paginate($a),
	]);

	return $o;
}

/**
 * @brief List of pages for the Contact TabBar
 *
 * Available Pages are 'Status', 'Profile', 'Contacts' and 'Common Friends'
 *
 * @param App $a
 * @param int $contact_id The ID of the contact
 * @param int $active_tab 1 if tab should be marked as active
 *
 * @return string
 */
function contacts_tab($a, $contact_id, $active_tab)
{
	// tabs
	$tabs = [
		[
			'label' => t('Status'),
			'url'   => "contacts/" . $contact_id . "/posts",
			'sel'   => (($active_tab == 1) ? 'active' : ''),
			'title' => t('Status Messages and Posts'),
			'id'    => 'status-tab',
			'accesskey' => 'm',
		],
		[
			'label' => t('Profile'),
			'url'   => "contacts/" . $contact_id,
			'sel'   => (($active_tab == 2) ? 'active' : ''),
			'title' => t('Profile Details'),
			'id'    => 'profile-tab',
			'accesskey' => 'o',
		]
	];

	// Show this tab only if there is visible friend list
	$x = GContact::countAllFriends(local_user(), $contact_id);
	if ($x) {
		$tabs[] = ['label' => t('Contacts'),
			'url'   => "allfriends/" . $contact_id,
			'sel'   => (($active_tab == 3) ? 'active' : ''),
			'title' => t('View all contacts'),
			'id'    => 'allfriends-tab',
			'accesskey' => 't'];
	}

	// Show this tab only if there is visible common friend list
	$common = GContact::countCommonFriends(local_user(), $contact_id);
	if ($common) {
		$tabs[] = ['label' => t('Common Friends'),
			'url'   => "common/loc/" . local_user() . "/" . $contact_id,
			'sel'   => (($active_tab == 4) ? 'active' : ''),
			'title' => t('View all common friends'),
			'id'    => 'common-loc-tab',
			'accesskey' => 'd'
		];
	}

	$tabs[] = ['label' => t('Advanced'),
		'url'   => 'crepair/' . $contact_id,
		'sel'   => (($active_tab == 5) ? 'active' : ''),
		'title' => t('Advanced Contact Settings'),
		'id'    => 'advanced-tab',
		'accesskey' => 'r'
	];

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$tab_str = replace_macros($tab_tpl, ['$tabs' => $tabs]);

	return $tab_str;
}

function contact_posts($a, $contact_id)
{
	$o = contacts_tab($a, $contact_id, 1);

	$contact = dba::selectFirst('contact', ['url'], ['id' => $contact_id]);
	if (DBM::is_result($contact)) {
		$a->page['aside'] = "";
		Profile::load($a, "", 0, Contact::getDetailsByURL($contact["url"]));
		$o .= Contact::getPostsFromUrl($contact["url"]);
	}

	return $o;
}

function _contact_detail_for_template($rr)
{
	$dir_icon = '';
	$alt_text = '';
	switch ($rr['rel']) {
		case CONTACT_IS_FRIEND:
			$dir_icon = 'images/lrarrow.gif';
			$alt_text = t('Mutual Friendship');
			break;
		case CONTACT_IS_FOLLOWER;
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
	if (($rr['network'] === NETWORK_DFRN) && ($rr['rel'])) {
		$url = "redir/{$rr['id']}";
		$sparkle = ' class="sparkle" ';
	} else {
		$url = $rr['url'];
		$sparkle = '';
	}

	return [
		'img_hover' => t('Visit %s\'s profile [%s]', $rr['name'], $rr['url']),
		'edit_hover' => t('Edit contact'),
		'photo_menu' => Contact::photoMenu($rr),
		'id' => $rr['id'],
		'alt_text' => $alt_text,
		'dir_icon' => $dir_icon,
		'thumb' => proxy_url($rr['thumb'], false, PROXY_SIZE_THUMB),
		'name' => htmlentities($rr['name']),
		'username' => htmlentities($rr['name']),
		'account_type' => Contact::getAccountType($rr),
		'sparkle' => $sparkle,
		'itemurl' => (($rr['addr'] != "") ? $rr['addr'] : $rr['url']),
		'url' => $url,
		'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
	];
}

/**
 * @brief Gives a array with actions which can performed to a given contact
 *
 * This includes actions like e.g. 'block', 'hide', 'archive', 'delete' and others
 *
 * @param array $contact Data about the Contact
 * @return array with contact related actions
 */
function contact_actions($contact)
{
	$poll_enabled = in_array($contact['network'], [NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_FEED, NETWORK_MAIL]);
	$contact_actions = [];

	// Provide friend suggestion only for Friendica contacts
	if ($contact['network'] === NETWORK_DFRN) {
		$contact_actions['suggest'] = [
			'label' => t('Suggest friends'),
			'url'   => 'fsuggest/' . $contact['id'],
			'title' => '',
			'sel'   => '',
			'id'    => 'suggest',
		];
	}

	if ($poll_enabled) {
		$contact_actions['update'] = [
			'label' => t('Update now'),
			'url'   => 'contacts/' . $contact['id'] . '/update',
			'title' => '',
			'sel'   => '',
			'id'    => 'update',
		];
	}

	$contact_actions['block'] = [
		'label' => (intval($contact['blocked']) ? t('Unblock') : t('Block') ),
		'url'   => 'contacts/' . $contact['id'] . '/block',
		'title' => t('Toggle Blocked status'),
		'sel'   => (intval($contact['blocked']) ? 'active' : ''),
		'id'    => 'toggle-block',
	];

	$contact_actions['ignore'] = [
		'label' => (intval($contact['readonly']) ? t('Unignore') : t('Ignore') ),
		'url'   => 'contacts/' . $contact['id'] . '/ignore',
		'title' => t('Toggle Ignored status'),
		'sel'   => (intval($contact['readonly']) ? 'active' : ''),
		'id'    => 'toggle-ignore',
	];

	$contact_actions['archive'] = [
		'label' => (intval($contact['archive']) ? t('Unarchive') : t('Archive') ),
		'url'   => 'contacts/' . $contact['id'] . '/archive',
		'title' => t('Toggle Archive status'),
		'sel'   => (intval($contact['archive']) ? 'active' : ''),
		'id'    => 'toggle-archive',
	];

	$contact_actions['delete'] = [
		'label' => t('Delete'),
		'url'   => 'contacts/' . $contact['id'] . '/drop',
		'title' => t('Delete contact'),
		'sel'   => '',
		'id'    => 'delete',
	];

	return $contact_actions;
}
