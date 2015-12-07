<?php


// Included here for completeness, but this is a very dangerous operation.
// It is the caller's responsibility to confirm the requestor's intent and
// authorisation to do this.

function user_remove($uid) {
	if(! $uid)
		return;
	$a = get_app();
	logger('Removing user: ' . $uid);

	$r = q("select * from user where uid = %d limit 1", intval($uid));

	call_hooks('remove_user',$r[0]);

	// save username (actually the nickname as it is guaranteed
	// unique), so it cannot be re-registered in the future.

	q("insert into userd ( username ) values ( '%s' )",
		$r[0]['nickname']
	);

	// don't delete yet, will be done later when contacts have deleted my stuff
	// q("DELETE FROM `contact` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `gcign` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `group` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `group_member` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `intro` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `event` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `item` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `item_id` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `mail` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `mailacct` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `manage` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `notify` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `photo` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `attach` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `profile` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `profile_check` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `pconfig` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `search` WHERE `uid` = %d", intval($uid));
	q("DELETE FROM `spam` WHERE `uid` = %d", intval($uid));
	// don't delete yet, will be done later when contacts have deleted my stuff
	// q("DELETE FROM `user` WHERE `uid` = %d", intval($uid));
	q("UPDATE `user` SET `account_removed` = 1, `account_expires_on` = UTC_TIMESTAMP() WHERE `uid` = %d", intval($uid));
	proc_run('php', "include/notifier.php", "removeme", $uid);

	// Send an update to the directory
	proc_run('php', "include/directory.php", $r[0]['url']);

	if($uid == local_user()) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		goaway($a->get_baseurl());
	}
}


function contact_remove($id) {

	$r = q("select uid from contact where id = %d limit 1",
		intval($id)
	);
	if((! count($r)) || (! intval($r[0]['uid'])))
		return;

	$archive = get_pconfig($r[0]['uid'], 'system','archive_removed_contacts');
	if($archive) {
		q("update contact set `archive` = 1, `network` = 'none', `writable` = 0 where id = %d",
			intval($id)
		);
		return;
	}

	q("DELETE FROM `contact` WHERE `id` = %d",
		intval($id)
	);
	q("DELETE FROM `item` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `photo` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `mail` WHERE `contact-id` = %d ",
		intval($id)
	);
	q("DELETE FROM `event` WHERE `cid` = %d ",
		intval($id)
	);
	q("DELETE FROM `queue` WHERE `cid` = %d ",
		intval($id)
	);

}


// sends an unfriend message. Does not remove the contact

function terminate_friendship($user,$self,$contact) {


	$a = get_app();

	require_once('include/datetime.php');

	if($contact['network'] === NETWORK_OSTATUS) {

		$slap = replace_macros(get_markup_template('follow_slap.tpl'), array(
			'$name' => $user['username'],
			'$profile_page' => $a->get_baseurl() . '/profile/' . $user['nickname'],
			'$photo' => $self['photo'],
			'$thumb' => $self['thumb'],
			'$published' => datetime_convert('UTC','UTC', 'now', ATOM_TIME),
			'$item_id' => 'urn:X-dfrn:' . $a->get_hostname() . ':unfollow:' . get_guid(32),
			'$title' => '',
			'$type' => 'text',
			'$content' => t('stopped following'),
			'$nick' => $user['nickname'],
			'$verb' => 'http://ostatus.org/schema/1.0/unfollow', // ACTIVITY_UNFOLLOW,
			'$ostat_follow' => '' // '<as:verb>http://ostatus.org/schema/1.0/unfollow</as:verb>' . "\r\n"
		));

		if((x($contact,'notify')) && (strlen($contact['notify']))) {
			require_once('include/salmon.php');
			slapper($user,$contact['notify'],$slap);
		}
	}
	elseif($contact['network'] === NETWORK_DIASPORA) {
		require_once('include/diaspora.php');
		diaspora_unshare($user,$contact);
	}
	elseif($contact['network'] === NETWORK_DFRN) {
		require_once('include/items.php');
		dfrn_deliver($user,$contact,'placeholder', 1);
	}

}


// Contact has refused to recognise us as a friend. We will start a countdown.
// If they still don't recognise us in 32 days, the relationship is over,
// and we won't waste any more time trying to communicate with them.
// This provides for the possibility that their database is temporarily messed
// up or some other transient event and that there's a possibility we could recover from it.

if(! function_exists('mark_for_death')) {
function mark_for_death($contact) {

	if($contact['archive'])
		return;

	if($contact['term-date'] == '0000-00-00 00:00:00') {
		q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				intval($contact['id'])
		);
	}
	else {

		// TODO: We really should send a notification to the owner after 2-3 weeks
		// so they won't be surprised when the contact vanishes and can take
		// remedial action if this was a serious mistake or glitch

		$expiry = $contact['term-date'] . ' + 32 days ';
		if(datetime_convert() > datetime_convert('UTC','UTC',$expiry)) {

			// relationship is really truly dead.
			// archive them rather than delete
			// though if the owner tries to unarchive them we'll start the whole process over again

			q("update contact set `archive` = 1 where id = %d",
				intval($contact['id'])
			);
			q("UPDATE `item` SET `private` = 2 WHERE `contact-id` = %d AND `uid` = %d", intval($contact['id']), intval($contact['uid']));

			//contact_remove($contact['id']);

		}
	}

}}

if(! function_exists('unmark_for_death')) {
function unmark_for_death($contact) {
	// It's a miracle. Our dead contact has inexplicably come back to life.
	q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d",
		dbesc('0000-00-00 00:00:00'),
		intval($contact['id'])
	);
}}

function get_contact_details_by_url($url, $uid = -1) {
	require_once("mod/proxy.php");
	require_once("include/bbcode.php");

	if ($uid == -1)
		$uid = local_user();

	$r = q("SELECT `id` AS `gid`, `url`, `name`, `nick`, `addr`, `photo`, `location`, `about`, `keywords`, `gender`, `community`, `network` FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
		dbesc(normalise_link($url)));

	if ($r) {
		$profile = $r[0];

		if ((($profile["addr"] == "") OR ($profile["name"] == "")) AND
			in_array($profile["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS)))
			proc_run('php',"include/update_gcontact.php", $profile["gid"]);

	} else {
		$r = q("SELECT `url`, `name`, `nick`, `avatar` AS `photo`, `location`, `about` FROM `unique_contacts` WHERE `url` = '%s'",
			dbesc(normalise_link($url)));

		if (count($r)) {
			$profile = $r[0];
			$profile["keywords"] = "";
			$profile["gender"] = "";
			$profile["community"] = false;
			$profile["network"] = "";
			$profile["addr"] = "";
		}
	}

	// Fetching further contact data from the contact table
	$r = q("SELECT `id`, `uid`, `url`, `network`, `name`, `nick`, `addr`, `location`, `about`, `keywords`, `gender`, `photo`, `addr`, `forum`, `prv`, `bd` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d AND `network` = '%s'",
		dbesc(normalise_link($url)), intval($uid), dbesc($profile["network"]));

	if (!count($r))
		$r = q("SELECT `id`, `uid`, `url`, `network`, `name`, `nick`, `addr`, `location`, `about`, `keywords`, `gender`, `photo`, `addr`, `forum`, `prv`, `bd` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
			dbesc(normalise_link($url)), intval($uid));

	if (!count($r))
		$r = q("SELECT `id`, `uid`, `url`, `network`, `name`, `nick`, `addr`, `location`, `about`, `keywords`, `gender`, `photo`, `addr`, `forum`, `prv`, `bd` FROM `contact` WHERE `nurl` = '%s' AND `uid` = 0",
			dbesc(normalise_link($url)));

	if ($r) {
		if (isset($r[0]["url"]) AND $r[0]["url"])
			$profile["url"] = $r[0]["url"];
		if (isset($r[0]["name"]) AND $r[0]["name"])
			$profile["name"] = $r[0]["name"];
		if (isset($r[0]["nick"]) AND $r[0]["nick"] AND ($profile["nick"] == ""))
			$profile["nick"] = $r[0]["nick"];
		if (isset($r[0]["addr"]) AND $r[0]["addr"] AND ($profile["addr"] == ""))
			$profile["addr"] = $r[0]["addr"];
		if (isset($r[0]["photo"]) AND $r[0]["photo"])
			$profile["photo"] = $r[0]["photo"];
		if (isset($r[0]["location"]) AND $r[0]["location"])
			$profile["location"] = $r[0]["location"];
		if (isset($r[0]["about"]) AND $r[0]["about"])
			$profile["about"] = $r[0]["about"];
		if (isset($r[0]["keywords"]) AND $r[0]["keywords"])
			$profile["keywords"] = $r[0]["keywords"];
		if (isset($r[0]["gender"]) AND $r[0]["gender"])
			$profile["gender"] = $r[0]["gender"];
		if (isset($r[0]["forum"]) OR isset($r[0]["prv"]))
			$profile["community"] = ($r[0]["forum"] OR $r[0]["prv"]);
		if (isset($r[0]["network"]) AND $r[0]["network"])
			$profile["network"] = $r[0]["network"];
		if (isset($r[0]["addr"]) AND $r[0]["addr"])
			$profile["addr"] = $r[0]["addr"];
		if (isset($r[0]["bd"]) AND $r[0]["bd"])
			$profile["bd"] = $r[0]["bd"];
		if ($r[0]["uid"] == 0)
			$profile["cid"] = 0;
		else
			$profile["cid"] = $r[0]["id"];
	} else
		$profile["cid"] = 0;

	if (isset($profile["photo"]))
		$profile["photo"] = proxy_url($profile["photo"], false, PROXY_SIZE_SMALL);

	if (isset($profile["location"]))
		$profile["location"] = bbcode($profile["location"]);

	if (isset($profile["about"]))
		$profile["about"] = bbcode($profile["about"]);

	if (($profile["cid"] == 0) AND ($profile["network"] == NETWORK_DIASPORA)) {
		$profile["location"] = "";
		$profile["about"] = "";
	}

	return($profile);
}

if(! function_exists('contact_photo_menu')){
function contact_photo_menu($contact) {

	$a = get_app();

	$contact_url="";
	$pm_url="";
	$status_link="";
	$photos_link="";
	$posts_link="";
	$contact_drop_link = "";
	$poke_link="";

	$sparkle = false;
	if($contact['network'] === NETWORK_DFRN) {
		$sparkle = true;
		$profile_link = $a->get_baseurl() . '/redir/' . $contact['id'];
	}
	else
		$profile_link = $contact['url'];

	if($profile_link === 'mailbox')
		$profile_link = '';

	if($sparkle) {
		$status_link = $profile_link . "?url=status";
		$photos_link = $profile_link . "?url=photos";
		$profile_link = $profile_link . "?url=profile";
	}

	if (in_array($contact["network"], array(NETWORK_DFRN, NETWORK_DIASPORA)))
		$pm_url = $a->get_baseurl() . '/message/new/' . $contact['id'];

	if ($contact["network"] == NETWORK_DFRN)
		$poke_link = $a->get_baseurl() . '/poke/?f=&c=' . $contact['id'];

	$contact_url = $a->get_baseurl() . '/contacts/' . $contact['id'];
	$posts_link = $a->get_baseurl() . '/network/0?nets=all&cid=' . $contact['id'];
	$contact_drop_link = $a->get_baseurl() . "/contacts/" . $contact['id'] . '/drop?confirm=1';


	/**
	 * menu array:
	 * "name" => [ "Label", "link", (bool)Should the link opened in a new tab? ]
	 */
	$menu = Array(
		'status' => array(t("View Status"), $status_link, true),
		'profile' => array(t("View Profile"), $profile_link, true),
		'photos' => array(t("View Photos"), $photos_link,true),
		'network' => array(t("Network Posts"), $posts_link,false),
		'edit' => array(t("Edit Contact"), $contact_url, false),
		'drop' => array(t("Drop Contact"), $contact_drop_link, false),
		'pm' => array(t("Send PM"), $pm_url, false),
		'poke' => array(t("Poke"), $poke_link, false),
	);


	$args = array('contact' => $contact, 'menu' => &$menu);

	call_hooks('contact_photo_menu', $args);

	$menucondensed = array();

	foreach ($menu AS $menuname=>$menuitem)
		if ($menuitem[1] != "")
			$menucondensed[$menuname] = $menuitem;

	return $menucondensed;
}}


function random_profile() {
	$r = q("SELECT `url` FROM `gcontact` WHERE `network` = '%s'
				AND `last_contact` >= `last_failure`
				AND `updated` > UTC_TIMESTAMP - INTERVAL 1 MONTH
			ORDER BY rand() LIMIT 1",
		dbesc(NETWORK_DFRN));

	if(count($r))
		return dirname($r[0]['url']);
	return '';
}


function contacts_not_grouped($uid,$start = 0,$count = 0) {

	if(! $count) {
		$r = q("select count(*) as total from contact where uid = %d and self = 0 and id not in (select distinct(`contact-id`) from group_member where uid = %d) ",
			intval($uid),
			intval($uid)
		);

		return $r;


	}

	$r = q("select * from contact where uid = %d and self = 0 and id not in (select distinct(`contact-id`) from group_member where uid = %d) and blocked = 0 and pending = 0 limit %d, %d",
		intval($uid),
		intval($uid),
		intval($start),
		intval($count)
	);

	return $r;
}

function get_contact($url, $uid = 0) {
	require_once("include/Scrape.php");

	$data = array();
	$contactid = 0;

	// is it an address in the format user@server.tld?
	if (!strstr($url, "http") OR strstr($url, "@")) {
		$data = probe_url($url);
		$url = $data["url"];
		if ($url == "")
			return 0;
	}

	$contact = q("SELECT `id`, `avatar-date` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
			dbesc(normalise_link($url)),
			intval($uid));

	if (!$contact)
		$contact = q("SELECT `id`, `avatar-date` FROM `contact` WHERE `alias` IN ('%s', '%s') AND `uid` = %d",
				dbesc($url),
				dbesc(normalise_link($url)),
				intval($uid));

	if ($contact) {
		$contactid = $contact[0]["id"];

		// Update the contact every 7 days
		$update_photo = ($contact[0]['avatar-date'] < datetime_convert('','','now -7 days'));
		//$update_photo = ($contact[0]['avatar-date'] < datetime_convert('','','now -12 hours'));

		if (!$update_photo)
			return($contactid);
	} elseif ($uid != 0)
		return 0;

	if (!count($data))
		$data = probe_url($url);

	// Does this address belongs to a valid network?
	if (!in_array($data["network"], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA)))
		return 0;

	// tempory programming. Can be deleted after 2015-02-07
	if (($data["alias"] == "") AND (normalise_link($data["url"]) != normalise_link($url)))
		$data["alias"] = normalise_link($url);

	if ($contactid == 0) {
		q("INSERT INTO `contact` (`uid`, `created`, `url`, `nurl`, `addr`, `alias`, `notify`, `poll`,
					`name`, `nick`, `photo`, `network`, `pubkey`, `rel`, `priority`,
					`batch`, `request`, `confirm`, `poco`,
					`writable`, `blocked`, `readonly`, `pending`)
					VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', 1, 0, 0, 0)",
			intval($uid),
			dbesc(datetime_convert()),
			dbesc($data["url"]),
			dbesc(normalise_link($data["url"])),
			dbesc($data["addr"]),
			dbesc($data["alias"]),
			dbesc($data["notify"]),
			dbesc($data["poll"]),
			dbesc($data["name"]),
			dbesc($data["nick"]),
			dbesc($data["photo"]),
			dbesc($data["network"]),
			dbesc($data["pubkey"]),
			intval(CONTACT_IS_SHARING),
			intval($data["priority"]),
			dbesc($data["batch"]),
			dbesc($data["request"]),
			dbesc($data["confirm"]),
			dbesc($data["poco"])
		);

		$contact = q("SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
				dbesc(normalise_link($data["url"])),
				intval($uid));
		if (!$contact)
			return 0;

		$contactid = $contact[0]["id"];
	}

	require_once("Photo.php");

	$photos = import_profile_photo($data["photo"],$uid,$contactid);

	q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s',
		`addr` = '%s', `alias` = '%s', `name` = '%s', `nick` = '%s',
		`name-date` = '%s', `uri-date` = '%s', `avatar-date` = '%s' WHERE `id` = %d",
		dbesc($photos[0]),
		dbesc($photos[1]),
		dbesc($photos[2]),
		dbesc($data["addr"]),
		dbesc($data["alias"]),
		dbesc($data["name"]),
		dbesc($data["nick"]),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		intval($contactid)
	);

	return $contactid;
}
