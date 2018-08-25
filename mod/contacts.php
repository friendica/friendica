<?php
/**
 * @file mod/contacts.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Group;
use Friendica\Model\Profile;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;

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
		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);

		if (!DBA::isResult($contact)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => 0]);
		}
	}

	if (DBA::isResult($contact)) {
		if ($contact['self']) {
			if (($a->argc == 3) && intval($a->argv[1]) && ($a->argv[2] == "posts")) {
				goaway('profile/' . $contact['nick']);
			} else {
				goaway('profile/' . $contact['nick'] . '?tab=profile');
			}
		}

		$a->data['contact'] = $contact;

		if (($a->data['contact']['network'] != "") && ($a->data['contact']['network'] != Protocol::DFRN)) {
			$networkname = format_network_name($a->data['contact']['network'], $a->data['contact']['url']);
		} else {
			$networkname = '';
		}

		/// @TODO Add nice spaces
		$vcard_widget = replace_macros(get_markup_template("vcard-widget.tpl"), [
			'$name' => htmlentities($a->data['contact']['name']),
			'$photo' => $a->data['contact']['photo'],
			'$url' => Contact::MagicLink($a->data['contact']['url']),
			'$addr' => (($a->data['contact']['addr'] != "") ? ($a->data['contact']['addr']) : ""),
			'$network_name' => $networkname,
			'$network' => L10n::t('Network:'),
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

	if ($contact['uid'] != 0) {
		$groups_widget = Group::sidebarWidget('contacts', 'group', 'full', 'everyone', $contact_id);
	} else {
		$groups_widget = null;
	}

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
			_contact_block($contact_id);
			$count_actions++;
		}
		if (x($_POST, 'contacts_batch_ignore')) {
			_contact_ignore($contact_id);
			$count_actions++;
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
		info(L10n::tt("%d contact edited.", "%d contacts edited.", $count_actions));
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

	if (!DBA::exists('contact', ['id' => $contact_id, 'uid' => local_user()])) {
		notice(L10n::t('Could not access contact record.') . EOL);
		goaway('contacts');
		return; // NOTREACHED
	}

	Addon::callHooks('contact_edit_post', $_POST);

	$profile_id = intval(defaults($_POST, 'profile-assign', 0));
	if ($profile_id) {
		if (!DBA::exists('profile', ['id' => $profile_id, 'uid' => local_user()])) {
			notice(L10n::t('Could not locate selected profile.') . EOL);
			return;
		}
	}

	$hidden = intval($_POST['hidden']);

	$notify = intval($_POST['notify']);

	$fetch_further_information = intval(defaults($_POST, 'fetch_further_information', 0));

	$ffi_keyword_blacklist = escape_tags(trim(defaults($_POST, 'ffi_keyword_blacklist', '')));

	$priority = intval(defaults($_POST, 'poll', 0));
	if ($priority > 5 || $priority < 0) {
		$priority = 0;
	}

	$info = escape_tags(trim($_POST['info']));

	$r = q("UPDATE `contact` SET `profile-id` = %d, `priority` = %d , `info` = '%s',
		`hidden` = %d, `notify_new_posts` = %d, `fetch_further_information` = %d,
		`ffi_keyword_blacklist` = '%s' WHERE `id` = %d AND `uid` = %d",
		intval($profile_id),
		intval($priority),
		DBA::escape($info),
		intval($hidden),
		intval($notify),
		intval($fetch_further_information),
		DBA::escape($ffi_keyword_blacklist),
		intval($contact_id),
		intval(local_user())
	);
	if (DBA::isResult($r)) {
		info(L10n::t('Contact updated.') . EOL);
	} else {
		notice(L10n::t('Failed to update contact record.') . EOL);
	}

	$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);
	if (DBA::isResult($contact)) {
		$a->data['contact'] = $contact;
	}

	return;
}

/* contact actions */

function _contact_update($contact_id)
{
	$contact = DBA::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => local_user()]);
	if (!DBA::isResult($contact)) {
		return;
	}

	$uid = $contact["uid"];

	if ($contact["network"] == Protocol::OSTATUS) {
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
	$contact = DBA::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => local_user()]);
	if (!DBA::isResult($contact)) {
		return;
	}

	$uid = $contact["uid"];

	$data = Probe::uri($contact["url"], "", 0, false);

	// "Feed" or "Unknown" is mostly a sign of communication problems
	if ((in_array($data["network"], [Protocol::FEED, Protocol::PHANTOM])) && ($data["network"] != $contact["network"])) {
		return;
	}

	$updatefields = ["name", "nick", "url", "addr", "batch", "notify", "poll", "request", "confirm",
		"poco", "network", "alias"];
	$update = [];

	if ($data["network"] == Protocol::OSTATUS) {
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

		$query .= "`" . $key . "` = '" . DBA::escape($value) . "'";
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

function _contact_block($contact_id)
{
	$blocked = !Contact::isBlockedByUser($contact_id, local_user());
	Contact::setBlockedForUser($contact_id, local_user(), $blocked);
}

function _contact_ignore($contact_id)
{
	$ignored = !Contact::isIgnoredByUser($contact_id, local_user());
	Contact::setIgnoredForUser($contact_id, local_user(), $ignored);
}

function _contact_archive($contact_id, $orig_record)
{
	$archived = (($orig_record['archive']) ? 0 : 1);
	$r = q("UPDATE `contact` SET `archive` = %d WHERE `id` = %d AND `uid` = %d",
		intval($archived),
		intval($contact_id),
		intval(local_user())
	);
	return DBA::isResult($r);
}

function _contact_drop($orig_record)
{
	$a = get_app();

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
		WHERE `user`.`uid` = %d AND `contact`.`self` LIMIT 1",
		intval($a->user['uid'])
	);
	if (!DBA::isResult($r)) {
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
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if ($a->argc == 3) {
		$contact_id = intval($a->argv[1]);
		if (!$contact_id) {
			return;
		}

		$cmd = $a->argv[2];

		$orig_record = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => [0, local_user()], 'self' => false]);
		if (!DBA::isResult($orig_record)) {
			notice(L10n::t('Could not access contact record.') . EOL);
			goaway('contacts');
			return; // NOTREACHED
		}

		if ($cmd === 'update' && ($orig_record['uid'] != 0)) {
			_contact_update($contact_id);
			goaway('contacts/' . $contact_id);
			// NOTREACHED
		}

		if ($cmd === 'updateprofile' && ($orig_record['uid'] != 0)) {
			_contact_update_profile($contact_id);
			goaway('crepair/' . $contact_id);
			// NOTREACHED
		}

		if ($cmd === 'block') {
			_contact_block($contact_id);

			$blocked = Contact::isBlockedByUser($contact_id, local_user());
			info(($blocked ? L10n::t('Contact has been blocked') : L10n::t('Contact has been unblocked')) . EOL);

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if ($cmd === 'ignore') {
			_contact_ignore($contact_id);

			$ignored = Contact::isIgnoredByUser($contact_id, local_user());
			info(($ignored ? L10n::t('Contact has been ignored') : L10n::t('Contact has been unignored')) . EOL);

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if ($cmd === 'archive' && ($orig_record['uid'] != 0)) {
			$r = _contact_archive($contact_id, $orig_record);
			if ($r) {
				$archived = (($orig_record['archive']) ? 0 : 1);
				info((($archived) ? L10n::t('Contact has been archived') : L10n::t('Contact has been unarchived')) . EOL);
			}

			goaway('contacts/' . $contact_id);
			return; // NOTREACHED
		}

		if ($cmd === 'drop' && ($orig_record['uid'] != 0)) {
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
					'$header' => L10n::t('Drop contact'),
					'$contact' => _contact_detail_for_template($orig_record),
					'$method' => 'get',
					'$message' => L10n::t('Do you really want to delete this contact?'),
					'$extra_inputs' => $inputs,
					'$confirm' => L10n::t('Yes'),
					'$confirm_url' => $query['base'],
					'$confirm_name' => 'confirmed',
					'$cancel' => L10n::t('Cancel'),
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
			info(L10n::t('Contact has been removed.') . EOL);
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

		$contact['blocked'] = Contact::isBlockedByUser($contact['id'], local_user());
		$contact['readonly'] = Contact::isIgnoredByUser($contact['id'], local_user());

		$dir_icon = '';
		$relation_text = '';
		switch ($contact['rel']) {
			case Contact::FRIEND:
				$dir_icon = 'images/lrarrow.gif';
				$relation_text = L10n::t('You are mutual friends with %s');
				break;

			case Contact::FOLLOWER;
				$dir_icon = 'images/larrow.gif';
				$relation_text = L10n::t('You are sharing with %s');
				break;

			case Contact::SHARING;
				$dir_icon = 'images/rarrow.gif';
				$relation_text = L10n::t('%s is sharing with you');
				break;

			default:
				break;
		}

		if ($contact['uid'] == 0) {
			$relation_text = '';
		}

		if (!in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::DIASPORA])) {
			$relation_text = "";
		}

		$relation_text = sprintf($relation_text, htmlentities($contact['name']));

		$url = Contact::magicLink($contact['url']);
		if (strpos($url, 'redir/') === 0) {
			$sparkle = ' class="sparkle" ';
		} else {
			$sparkle = '';
		}

		$insecure = L10n::t('Private communications are not available for this contact.');

		$last_update = (($contact['last-update'] <= NULL_DATE) ? L10n::t('Never') : DateTimeFormat::local($contact['last-update'], 'D, j M Y, g:i A'));

		if ($contact['last-update'] > NULL_DATE) {
			$last_update .= ' ' . (($contact['last-update'] <= $contact['success_update']) ? L10n::t("\x28Update was successful\x29") : L10n::t("\x28Update was not successful\x29"));
		}
		$lblsuggest = (($contact['network'] === Protocol::DFRN) ? L10n::t('Suggest friends') : '');

		$poll_enabled = in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);

		$nettype = L10n::t('Network type: %s', ContactSelector::networkToName($contact['network'], $contact["url"]));

		// tabs
		$tab_str = contacts_tab($a, $contact_id, 2, $contact);

		$lost_contact = (($contact['archive'] && $contact['term-date'] > NULL_DATE && $contact['term-date'] < DateTimeFormat::utcNow()) ? L10n::t('Communications lost with this contact!') : '');

		$fetch_further_information = null;
		if ($contact['network'] == Protocol::FEED) {
			$fetch_further_information = [
				'fetch_further_information',
				L10n::t('Fetch further information for feeds'),
				$contact['fetch_further_information'],
				L10n::t("Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn't contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags."),
				['0' => L10n::t('Disabled'),
					'1' => L10n::t('Fetch information'),
					'3' => L10n::t('Fetch keywords'),
					'2' => L10n::t('Fetch information and keywords')
				]
			];
		}

		$poll_interval = null;
		if (in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			$poll_interval = ContactSelector::pollInterval($contact['priority'], (!$poll_enabled));
		}

		$profile_select = null;
		if ($contact['network'] == Protocol::DFRN) {
			$profile_select = ContactSelector::profileAssign($contact['profile-id'], (($contact['network'] !== Protocol::DFRN) ? true : false));
		}

		/// @todo Only show the following link with DFRN when the remote version supports it
		$follow = '';
		$follow_text = '';
		if (in_array($contact['network'], [Protocol::DIASPORA, Protocol::OSTATUS, Protocol::DFRN])) {
			if (in_array($contact['rel'], [Contact::FRIEND, Contact::SHARING])) {
				$follow = System::baseUrl(true) . "/unfollow?url=" . urlencode($contact["url"]);
				$follow_text = L10n::t("Disconnect/Unfollow");
			} else {
				$follow = System::baseUrl(true) . "/follow?url=" . urlencode($contact["url"]);
				$follow_text = L10n::t("Connect/Follow");
			}
		}

		if ($contact['uid'] == 0) {
			$follow = System::baseUrl(true) . "/follow?url=" . urlencode($contact["url"]);
			$follow_text = L10n::t("Connect/Follow");
		}

		// Load contactact related actions like hide, suggest, delete and others
		$contact_actions = contact_actions($contact);

		if ($contact['uid'] != 0) {
			$lbl_vis1 = L10n::t('Profile Visibility');
			$lbl_info1 = L10n::t('Contact Information / Notes');
			$contact_settings_label = L10n::t('Contact Settings');
		} else {
			$lbl_vis1 = null;
			$lbl_info1 = null;
			$contact_settings_label = null;
		}

		$tpl = get_markup_template("contact_edit.tpl");
		$o .= replace_macros($tpl, [
			'$header' => L10n::t("Contact"),
			'$tab_str' => $tab_str,
			'$submit' => L10n::t('Submit'),
			'$lbl_vis1' => $lbl_vis1,
			'$lbl_vis2' => L10n::t('Please choose the profile you would like to display to %s when viewing your profile securely.', $contact['name']),
			'$lbl_info1' => $lbl_info1,
			'$lbl_info2' => L10n::t('Their personal note'),
			'$reason' => trim(notags($contact['reason'])),
			'$infedit' => L10n::t('Edit contact notes'),
			'$common_link' => 'common/loc/' . local_user() . '/' . $contact['id'],
			'$relation_text' => $relation_text,
			'$visit' => L10n::t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
			'$blockunblock' => L10n::t('Block/Unblock contact'),
			'$ignorecont' => L10n::t('Ignore contact'),
			'$lblcrepair' => L10n::t("Repair URL settings"),
			'$lblrecent' => L10n::t('View conversations'),
			'$lblsuggest' => $lblsuggest,
			'$nettype' => $nettype,
			'$poll_interval' => $poll_interval,
			'$poll_enabled' => $poll_enabled,
			'$lastupdtext' => L10n::t('Last update:'),
			'$lost_contact' => $lost_contact,
			'$updpub' => L10n::t('Update public posts'),
			'$last_update' => $last_update,
			'$udnow' => L10n::t('Update now'),
			'$follow' => $follow,
			'$follow_text' => $follow_text,
			'$profile_select' => $profile_select,
			'$contact_id' => $contact['id'],
			'$block_text' => (($contact['blocked']) ? L10n::t('Unblock') : L10n::t('Block') ),
			'$ignore_text' => (($contact['readonly']) ? L10n::t('Unignore') : L10n::t('Ignore') ),
			'$insecure' => (($contact['network'] !== Protocol::DFRN && $contact['network'] !== Protocol::MAIL && $contact['network'] !== Protocol::DIASPORA) ? $insecure : ''),
			'$info' => $contact['info'],
			'$cinfo' => ['info', '', $contact['info'], ''],
			'$blocked' => (($contact['blocked']) ? L10n::t('Currently blocked') : ''),
			'$ignored' => (($contact['readonly']) ? L10n::t('Currently ignored') : ''),
			'$archived' => (($contact['archive']) ? L10n::t('Currently archived') : ''),
			'$pending' => (($contact['pending']) ? L10n::t('Awaiting connection acknowledge') : ''),
			'$hidden' => ['hidden', L10n::t('Hide this contact from others'), ($contact['hidden'] == 1), L10n::t('Replies/likes to your public posts <strong>may</strong> still be visible')],
			'$notify' => ['notify', L10n::t('Notification for new posts'), ($contact['notify_new_posts'] == 1), L10n::t('Send a notification of every new post of this contact')],
			'$fetch_further_information' => $fetch_further_information,
			'$ffi_keyword_blacklist' => $contact['ffi_keyword_blacklist'],
			'$ffi_keyword_blacklist' => ['ffi_keyword_blacklist', L10n::t('Blacklisted keywords'), $contact['ffi_keyword_blacklist'], L10n::t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')],
			'$photo' => $contact['photo'],
			'$name' => htmlentities($contact['name']),
			'$dir_icon' => $dir_icon,
			'$sparkle' => $sparkle,
			'$url' => $url,
			'$profileurllabel' => L10n::t('Profile URL'),
			'$profileurl' => $contact['url'],
			'$account_type' => Contact::getAccountType($contact),
			'$location' => BBCode::convert($contact["location"]),
			'$location_label' => L10n::t("Location:"),
			'$xmpp' => BBCode::convert($contact["xmpp"]),
			'$xmpp_label' => L10n::t("XMPP:"),
			'$about' => BBCode::convert($contact["about"], false),
			'$about_label' => L10n::t("About:"),
			'$keywords' => $contact["keywords"],
			'$keywords_label' => L10n::t("Tags:"),
			'$contact_action_button' => L10n::t("Actions"),
			'$contact_actions' => $contact_actions,
			'$contact_status' => L10n::t("Status"),
			'$contact_settings_label' => $contact_settings_label,
			'$contact_profile_label' => L10n::t("Profile"),
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
			'label' => L10n::t('Suggestions'),
			'url'   => 'suggest',
			'sel'   => '',
			'title' => L10n::t('Suggest potential friends'),
			'id'    => 'suggestions-tab',
			'accesskey' => 'g',
		],
		[
			'label' => L10n::t('All Contacts'),
			'url'   => 'contacts/all',
			'sel'   => ($all) ? 'active' : '',
			'title' => L10n::t('Show all contacts'),
			'id'    => 'showall-tab',
			'accesskey' => 'l',
		],
		[
			'label' => L10n::t('Unblocked'),
			'url'   => 'contacts',
			'sel'   => ((!$all) && (!$blocked) && (!$hidden) && (!$search) && (!$nets) && (!$ignored) && (!$archived)) ? 'active' : '',
			'title' => L10n::t('Only show unblocked contacts'),
			'id'    => 'showunblocked-tab',
			'accesskey' => 'o',
		],
		[
			'label' => L10n::t('Blocked'),
			'url'   => 'contacts/blocked',
			'sel'   => ($blocked) ? 'active' : '',
			'title' => L10n::t('Only show blocked contacts'),
			'id'    => 'showblocked-tab',
			'accesskey' => 'b',
		],
		[
			'label' => L10n::t('Ignored'),
			'url'   => 'contacts/ignored',
			'sel'   => ($ignored) ? 'active' : '',
			'title' => L10n::t('Only show ignored contacts'),
			'id'    => 'showignored-tab',
			'accesskey' => 'i',
		],
		[
			'label' => L10n::t('Archived'),
			'url'   => 'contacts/archived',
			'sel'   => ($archived) ? 'active' : '',
			'title' => L10n::t('Only show archived contacts'),
			'id'    => 'showarchived-tab',
			'accesskey' => 'y',
		],
		[
			'label' => L10n::t('Hidden'),
			'url'   => 'contacts/hidden',
			'sel'   => ($hidden) ? 'active' : '',
			'title' => L10n::t('Only show hidden contacts'),
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
		$search_txt = DBA::escape(protect_sprintf(preg_quote($search)));
		$sql_extra .= " AND (name REGEXP '$search_txt' OR url REGEXP '$search_txt'  OR nick REGEXP '$search_txt') ";
	}

	if ($nets) {
		$sql_extra .= sprintf(" AND network = '%s' ", DBA::escape($nets));
	}

	$sql_extra2 = ((($sort_type > 0) && ($sort_type <= Contact::FRIEND)) ? sprintf(" AND `rel` = %d ", intval($sort_type)) : '');

	$r = q("SELECT COUNT(*) AS `total` FROM `contact`
		WHERE `uid` = %d AND `self` = 0 AND `pending` = 0 $sql_extra $sql_extra2 ",
		intval($_SESSION['uid'])
	);
	if (DBA::isResult($r)) {
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
	if (DBA::isResult($r)) {
		foreach ($r as $rr) {
			$rr['blocked'] = Contact::isBlockedByUser($rr['id'], local_user());
			$rr['readonly'] = Contact::isIgnoredByUser($rr['id'], local_user());
			$contacts[] = _contact_detail_for_template($rr);
		}
	}

	$tpl = get_markup_template("contacts-template.tpl");
	$o .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$header' => L10n::t('Contacts') . (($nets) ? ' - ' . ContactSelector::networkToName($nets) : ''),
		'$tabs' => $t,
		'$total' => $total,
		'$search' => $search_hdr,
		'$desc' => L10n::t('Search your contacts'),
		'$finding' => $searching ? L10n::t('Results for: %s', $search) : "",
		'$submit' => L10n::t('Find'),
		'$cmd' => $a->cmd,
		'$contacts' => $contacts,
		'$contact_drop_confirm' => L10n::t('Do you really want to delete this contact?'),
		'multiselect' => 1,
		'$batch_actions' => [
			'contacts_batch_update'  => L10n::t('Update'),
			'contacts_batch_block'   => L10n::t('Block') . "/" . L10n::t("Unblock"),
			"contacts_batch_ignore"  => L10n::t('Ignore') . "/" . L10n::t("Unignore"),
			"contacts_batch_archive" => L10n::t('Archive') . "/" . L10n::t("Unarchive"),
			"contacts_batch_drop"    => L10n::t('Delete'),
		],
		'$h_batch_actions' => L10n::t('Batch Actions'),
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
function contacts_tab($a, $contact_id, $active_tab, $contact)
{
	// tabs
	$tabs = [
		[
			'label' => L10n::t('Status'),
			'url'   => "contacts/" . $contact_id . "/posts",
			'sel'   => (($active_tab == 1) ? 'active' : ''),
			'title' => L10n::t('Status Messages and Posts'),
			'id'    => 'status-tab',
			'accesskey' => 'm',
		],
		[
			'label' => L10n::t('Profile'),
			'url'   => "contacts/" . $contact_id,
			'sel'   => (($active_tab == 2) ? 'active' : ''),
			'title' => L10n::t('Profile Details'),
			'id'    => 'profile-tab',
			'accesskey' => 'o',
		]
	];

	// Show this tab only if there is visible friend list
	$x = GContact::countAllFriends(local_user(), $contact_id);
	if ($x) {
		$tabs[] = ['label' => L10n::t('Contacts'),
			'url'   => "allfriends/" . $contact_id,
			'sel'   => (($active_tab == 3) ? 'active' : ''),
			'title' => L10n::t('View all contacts'),
			'id'    => 'allfriends-tab',
			'accesskey' => 't'];
	}

	// Show this tab only if there is visible common friend list
	$common = GContact::countCommonFriends(local_user(), $contact_id);
	if ($common) {
		$tabs[] = ['label' => L10n::t('Common Friends'),
			'url'   => "common/loc/" . local_user() . "/" . $contact_id,
			'sel'   => (($active_tab == 4) ? 'active' : ''),
			'title' => L10n::t('View all common friends'),
			'id'    => 'common-loc-tab',
			'accesskey' => 'd'
		];
	}

	if (!empty($contact['uid'])) {
		$tabs[] = ['label' => L10n::t('Advanced'),
			'url'   => 'crepair/' . $contact_id,
			'sel'   => (($active_tab == 5) ? 'active' : ''),
			'title' => L10n::t('Advanced Contact Settings'),
			'id'    => 'advanced-tab',
			'accesskey' => 'r'
		];
	}

	$tab_tpl = get_markup_template('common_tabs.tpl');
	$tab_str = replace_macros($tab_tpl, ['$tabs' => $tabs]);

	return $tab_str;
}

function contact_posts(App $a, $contact_id)
{
	$contact = DBA::selectFirst('contact', ['uid', 'url'], ['id' => $contact_id]);

	$o = contacts_tab($a, $contact_id, 1, $contact);

	if (DBA::isResult($contact)) {
		$a->page['aside'] = "";
		Profile::load($a, "", 0, Contact::getDetailsByURL($contact["url"]));
		$o .= Contact::getPostsFromUrl($contact["url"]);
	}

	return $o;
}

function _contact_detail_for_template(array $rr)
{
	$dir_icon = '';
	$alt_text = '';

	switch ($rr['rel']) {
		case Contact::FRIEND:
			$dir_icon = 'images/lrarrow.gif';
			$alt_text = L10n::t('Mutual Friendship');
			break;

		case Contact::FOLLOWER;
			$dir_icon = 'images/larrow.gif';
			$alt_text = L10n::t('is a fan of yours');
			break;

		case Contact::SHARING;
			$dir_icon = 'images/rarrow.gif';
			$alt_text = L10n::t('you are a fan of');
			break;

		default:
			break;
	}

	$url = Contact::magicLink($rr['url']);

	if (strpos($url, 'redir/') === 0) {
		$sparkle = ' class="sparkle" ';
	} else {
		$sparkle = '';
	}

	if ($rr['self']) {
		$dir_icon = 'images/larrow.gif';
		$alt_text = L10n::t('This is you');
		$url = $rr['url'];
		$sparkle = '';
	}

	return [
		'img_hover' => L10n::t('Visit %s\'s profile [%s]', $rr['name'], $rr['url']),
		'edit_hover' => L10n::t('Edit contact'),
		'photo_menu' => Contact::photoMenu($rr),
		'id' => $rr['id'],
		'alt_text' => $alt_text,
		'dir_icon' => $dir_icon,
		'thumb' => ProxyUtils::proxifyUrl($rr['thumb'], false, ProxyUtils::SIZE_THUMB),
		'name' => htmlentities($rr['name']),
		'username' => htmlentities($rr['name']),
		'account_type' => Contact::getAccountType($rr),
		'sparkle' => $sparkle,
		'itemurl' => (($rr['addr'] != "") ? $rr['addr'] : $rr['url']),
		'url' => $url,
		'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
		'nick' => htmlentities($rr['nick']),
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
	$poll_enabled = in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);
	$contact_actions = [];

	// Provide friend suggestion only for Friendica contacts
	if ($contact['network'] === Protocol::DFRN) {
		$contact_actions['suggest'] = [
			'label' => L10n::t('Suggest friends'),
			'url'   => 'fsuggest/' . $contact['id'],
			'title' => '',
			'sel'   => '',
			'id'    => 'suggest',
		];
	}

	if ($poll_enabled) {
		$contact_actions['update'] = [
			'label' => L10n::t('Update now'),
			'url'   => 'contacts/' . $contact['id'] . '/update',
			'title' => '',
			'sel'   => '',
			'id'    => 'update',
		];
	}

	$contact_actions['block'] = [
		'label' => (intval($contact['blocked']) ? L10n::t('Unblock') : L10n::t('Block') ),
		'url'   => 'contacts/' . $contact['id'] . '/block',
		'title' => L10n::t('Toggle Blocked status'),
		'sel'   => (intval($contact['blocked']) ? 'active' : ''),
		'id'    => 'toggle-block',
	];

	$contact_actions['ignore'] = [
		'label' => (intval($contact['readonly']) ? L10n::t('Unignore') : L10n::t('Ignore') ),
		'url'   => 'contacts/' . $contact['id'] . '/ignore',
		'title' => L10n::t('Toggle Ignored status'),
		'sel'   => (intval($contact['readonly']) ? 'active' : ''),
		'id'    => 'toggle-ignore',
	];

	if ($contact['uid'] != 0) {
		$contact_actions['archive'] = [
			'label' => (intval($contact['archive']) ? L10n::t('Unarchive') : L10n::t('Archive') ),
			'url'   => 'contacts/' . $contact['id'] . '/archive',
			'title' => L10n::t('Toggle Archive status'),
			'sel'   => (intval($contact['archive']) ? 'active' : ''),
			'id'    => 'toggle-archive',
		];

		$contact_actions['delete'] = [
			'label' => L10n::t('Delete'),
			'url'   => 'contacts/' . $contact['id'] . '/drop',
			'title' => L10n::t('Delete contact'),
			'sel'   => '',
			'id'    => 'delete',
		];
	}

	return $contact_actions;
}
