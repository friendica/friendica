<?php


// Included here for completeness, but this is a very dangerous operation.
// It is the caller's responsibility to confirm the requestor's intent and
// authorisation to do this.

function user_remove($uid) {
	if(! $uid)
		return;
	logger('Removing user: ' . $uid);

	$r = q("select * from user where uid = %d limit 1", intval($uid));

	call_hooks('remove_user',$r[0]);

	// save username (actually the nickname as it is guaranteed
	// unique), so it cannot be re-registered in the future.

	q("insert into userd ( username ) values ( '%s' )",
		$r[0]['nickname']
	);

	/// @todo Should be done in a background job since this likely will run into a time out
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
	proc_run(PRIORITY_HIGH, "include/notifier.php", "removeme", $uid);

	// Send an update to the directory
	proc_run(PRIORITY_LOW, "include/directory.php", $r[0]['url']);

	if($uid == local_user()) {
		unset($_SESSION['authenticated']);
		unset($_SESSION['uid']);
		goaway(App::get_baseurl());
	}
}


function contact_remove($id) {

	$r = q("select uid from contact where id = %d limit 1",
		intval($id)
	);
	if((! dbm::is_result($r)) || (! intval($r[0]['uid'])))
		return;

	$archive = get_pconfig($r[0]['uid'], 'system','archive_removed_contacts');
	if($archive) {
		q("update contact set `archive` = 1, `network` = 'none', `writable` = 0 where id = %d",
			intval($id)
		);
		return;
	}

	q("DELETE FROM `contact` WHERE `id` = %d", intval($id));

	// Delete the rest in the background
	proc_run(PRIORITY_LOW, 'include/remove_contact.php', $id);
}


// sends an unfriend message. Does not remove the contact

function terminate_friendship($user,$self,$contact) {

	/// @TODO Get rid of this, include/datetime.php should care about it by itself
	$a = get_app();

	require_once('include/datetime.php');

	if ($contact['network'] === NETWORK_OSTATUS) {

		require_once('include/ostatus.php');

		// create an unfollow slap
		$item = array();
		$item['verb'] = NAMESPACE_OSTATUS."/unfollow";
		$item['follow'] = $contact["url"];
		$slap = ostatus::salmon($item, $user);

		if ((x($contact,'notify')) && (strlen($contact['notify']))) {
			require_once('include/salmon.php');
			slapper($user,$contact['notify'],$slap);
		}
	} elseif ($contact['network'] === NETWORK_DIASPORA) {
		require_once('include/diaspora.php');
		Diaspora::send_unshare($user,$contact);
	} elseif ($contact['network'] === NETWORK_DFRN) {
		require_once('include/dfrn.php');
		dfrn::deliver($user,$contact,'placeholder', 1);
	}

}


// Contact has refused to recognise us as a friend. We will start a countdown.
// If they still don't recognise us in 32 days, the relationship is over,
// and we won't waste any more time trying to communicate with them.
// This provides for the possibility that their database is temporarily messed
// up or some other transient event and that there's a possibility we could recover from it.

function mark_for_death($contact) {

	if($contact['archive'])
		return;

	if($contact['term-date'] == '0000-00-00 00:00:00') {
		q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				intval($contact['id'])
		);

		if ($contact['url'] != '') {
			q("UPDATE `contact` SET `term-date` = '%s'
				WHERE `nurl` = '%s' AND `term-date` <= '1000-00-00'",
					dbesc(datetime_convert()),
					dbesc(normalise_link($contact['url']))
			);
		}
	} else {

		/// @todo
		/// We really should send a notification to the owner after 2-3 weeks
		/// so they won't be surprised when the contact vanishes and can take
		/// remedial action if this was a serious mistake or glitch

		/// @todo
		/// Check for contact vitality via probing

		$expiry = $contact['term-date'] . ' + 32 days ';
		if(datetime_convert() > datetime_convert('UTC','UTC',$expiry)) {

			// relationship is really truly dead.
			// archive them rather than delete
			// though if the owner tries to unarchive them we'll start the whole process over again

			q("UPDATE `contact` SET `archive` = 1 WHERE `id` = %d",
				intval($contact['id'])
			);

			if ($contact['url'] != '') {
				q("UPDATE `contact` SET `archive` = 1 WHERE `nurl` = '%s'",
					dbesc(normalise_link($contact['url']))
				);
			}
		}
	}

}

function unmark_for_death($contact) {

	$r = q("SELECT `term-date` FROM `contact` WHERE `id` = %d AND `term-date` > '%s'",
		intval($contact['id']),
		dbesc('1000-00-00 00:00:00')
	);

	// We don't need to update, we never marked this contact as dead
	if (!dbm::is_result($r)) {
		return;
	}

	// It's a miracle. Our dead contact has inexplicably come back to life.
	q("UPDATE `contact` SET `term-date` = '%s' WHERE `id` = %d",
		dbesc('0000-00-00 00:00:00'),
		intval($contact['id'])
	);

	if ($contact['url'] != '') {
		q("UPDATE `contact` SET `term-date` = '%s' WHERE `nurl` = '%s'",
			dbesc('0000-00-00 00:00:00'),
			dbesc(normalise_link($contact['url']))
		);
	}
}

/**
 * @brief Get contact data for a given profile link
 *
 * The function looks at several places (contact table and gcontact table) for the contact
 * It caches its result for the same script execution to prevent duplicate calls
 *
 * @param string $url The profile link
 * @param int $uid User id
 * @param array $default If not data was found take this data as default value
 *
 * @return array Contact data
 */
function get_contact_details_by_url($url, $uid = -1, $default = array()) {
	static $cache = array();

	if ($uid == -1) {
		$uid = local_user();
	}

	if (isset($cache[$url][$uid])) {
		return $cache[$url][$uid];
	}

	// Fetch contact data from the contact table for the given user
	$r = q("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`
		FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d",
			dbesc(normalise_link($url)), intval($uid));

	// Fetch the data from the contact table with "uid=0" (which is filled automatically)
	if (!dbm::is_result($r))
		$r = q("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`
			FROM `contact` WHERE `nurl` = '%s' AND `uid` = 0",
				dbesc(normalise_link($url)));

	// Fetch the data from the gcontact table
	if (!dbm::is_result($r))
		$r = q("SELECT 0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, '' AS `xmpp`,
			`keywords`, `gender`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, `community` AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`
			FROM `gcontact` WHERE `nurl` = '%s'",
				dbesc(normalise_link($url)));

	if (dbm::is_result($r)) {
		// If there is more than one entry we filter out the connector networks
		if (count($r) > 1) {
			foreach ($r AS $id => $result) {
				if ($result["network"] == NETWORK_STATUSNET) {
					unset($r[$id]);
				}
			}
		}

		$profile = array_shift($r);

		// "bd" always contains the upcoming birthday of a contact.
		// "birthday" might contain the birthday including the year of birth.
		if ($profile["birthday"] != "0000-00-00") {
			$bd_timestamp = strtotime($profile["birthday"]);
			$month = date("m", $bd_timestamp);
			$day = date("d", $bd_timestamp);

			$current_timestamp = time();
			$current_year = date("Y", $current_timestamp);
			$current_month = date("m", $current_timestamp);
			$current_day = date("d", $current_timestamp);

			$profile["bd"] = $current_year."-".$month."-".$day;
			$current = $current_year."-".$current_month."-".$current_day;

			if ($profile["bd"] < $current) {
				$profile["bd"] = (++$current_year)."-".$month."-".$day;
			}
		} else {
			$profile["bd"] = "0000-00-00";
		}
	} else {
		$profile = $default;
	}

	if (($profile["photo"] == "") AND isset($default["photo"])) {
		$profile["photo"] = $default["photo"];
	}

	if (($profile["name"] == "") AND isset($default["name"])) {
		$profile["name"] = $default["name"];
	}

	if (($profile["network"] == "") AND isset($default["network"])) {
		$profile["network"] = $default["network"];
	}

	if (($profile["thumb"] == "") AND isset($profile["photo"])) {
		$profile["thumb"] = $profile["photo"];
	}

	if (($profile["micro"] == "") AND isset($profile["thumb"])) {
		$profile["micro"] = $profile["thumb"];
	}

	if ((($profile["addr"] == "") OR ($profile["name"] == "")) AND ($profile["gid"] != 0) AND
		in_array($profile["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS))) {
		proc_run(PRIORITY_LOW, "include/update_gcontact.php", $profile["gid"]);
	}

	// Show contact details of Diaspora contacts only if connected
	if (($profile["cid"] == 0) AND ($profile["network"] == NETWORK_DIASPORA)) {
		$profile["location"] = "";
		$profile["about"] = "";
		$profile["gender"] = "";
		$profile["birthday"] = "0000-00-00";
	}

	$cache[$url][$uid] = $profile;

	return $profile;
}

if (! function_exists('contact_photo_menu')) {
function contact_photo_menu($contact, $uid = 0)
{
	$a = get_app();

	$contact_url = '';
	$pm_url = '';
	$status_link = '';
	$photos_link = '';
	$posts_link = '';
	$contact_drop_link = '';
	$poke_link = '';

	if ($uid == 0) {
		$uid = local_user();
	}

	if ($contact['uid'] != $uid) {
		if ($uid == 0) {
			$profile_link = zrl($contact['url']);
			$menu = Array('profile' => array(t('View Profile'), $profile_link, true));

			return $menu;
		}

		$r = q("SELECT * FROM `contact` WHERE `nurl` = '%s' AND `network` = '%s' AND `uid` = %d",
			dbesc($contact['nurl']), dbesc($contact['network']), intval($uid));
		if ($r) {
			return contact_photo_menu($r[0], $uid);
		} else {
			$profile_link = zrl($contact['url']);
			$connlnk = 'follow/?url='.$contact['url'];
			$menu = array(
				'profile' => array(t('View Profile'), $profile_link, true),
				'follow' => array(t('Connect/Follow'), $connlnk, true)
			);

			return $menu;
		}
	}

	$sparkle = false;
	if ($contact['network'] === NETWORK_DFRN) {
		$sparkle = true;
		$profile_link = App::get_baseurl() . '/redir/' . $contact['id'];
	} else {
		$profile_link = $contact['url'];
	}

	if ($profile_link === 'mailbox') {
		$profile_link = '';
	}

	if ($sparkle) {
		$status_link = $profile_link . '?url=status';
		$photos_link = $profile_link . '?url=photos';
		$profile_link = $profile_link . '?url=profile';
	}

	if (in_array($contact['network'], array(NETWORK_DFRN, NETWORK_DIASPORA))) {
		$pm_url = App::get_baseurl() . '/message/new/' . $contact['id'];
	}

	if ($contact['network'] == NETWORK_DFRN) {
		$poke_link = App::get_baseurl() . '/poke/?f=&c=' . $contact['id'];
	}

	$contact_url = App::get_baseurl() . '/contacts/' . $contact['id'];

	$posts_link = App::get_baseurl() . '/contacts/' . $contact['id'] . '/posts';
	$contact_drop_link = App::get_baseurl() . '/contacts/' . $contact['id'] . '/drop?confirm=1';

	/**
	 * menu array:
	 * "name" => [ "Label", "link", (bool)Should the link opened in a new tab? ]
	 */
	$menu = array(
		'status' => array(t("View Status"), $status_link, true),
		'profile' => array(t("View Profile"), $profile_link, true),
		'photos' => array(t("View Photos"), $photos_link, true),
		'network' => array(t("Network Posts"), $posts_link, false),
		'edit' => array(t("View Contact"), $contact_url, false),
		'drop' => array(t("Drop Contact"), $contact_drop_link, false),
		'pm' => array(t("Send PM"), $pm_url, false),
		'poke' => array(t("Poke"), $poke_link, false),
	);


	$args = array('contact' => $contact, 'menu' => &$menu);

	call_hooks('contact_photo_menu', $args);

	$menucondensed = array();

	foreach ($menu AS $menuname => $menuitem) {
		if ($menuitem[1] != '') {
			$menucondensed[$menuname] = $menuitem;
		}
	}

	return $menucondensed;
}}


function random_profile() {
	$r = q("SELECT `url` FROM `gcontact` WHERE `network` = '%s'
				AND `last_contact` >= `last_failure`
				AND `updated` > UTC_TIMESTAMP - INTERVAL 1 MONTH
			ORDER BY rand() LIMIT 1",
		dbesc(NETWORK_DFRN));

	if (dbm::is_result($r))
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

/**
 * @brief Fetch the contact id for a given url and user
 *
 * @param string $url Contact URL
 * @param integer $uid The user id for the contact
 * @param boolean $no_update Don't update the contact
 *
 * @return integer Contact ID
 */
function get_contact($url, $uid = 0, $no_update = false) {
	require_once("include/Scrape.php");

	logger("Get contact data for url ".$url." and user ".$uid." - ".App::callstack(), LOGGER_DEBUG);;

	$data = array();
	$contactid = 0;

	// is it an address in the format user@server.tld?
	/// @todo use gcontact and/or the addr field for a lookup
	if (!strstr($url, "http") OR strstr($url, "@")) {
		$data = probe_url($url);
		$url = $data["url"];
		if ($url == "")
			return 0;
	}

	$contact = q("SELECT `id`, `avatar-date` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d ORDER BY `id` LIMIT 2",
			dbesc(normalise_link($url)),
			intval($uid));

	if (!$contact)
		$contact = q("SELECT `id`, `avatar-date` FROM `contact` WHERE `alias` IN ('%s', '%s') AND `uid` = %d ORDER BY `id` LIMIT 1",
				dbesc($url),
				dbesc(normalise_link($url)),
				intval($uid));

	if ($contact) {
		$contactid = $contact[0]["id"];

		// Update the contact every 7 days
		$update_photo = ($contact[0]['avatar-date'] < datetime_convert('','','now -7 days'));
		//$update_photo = ($contact[0]['avatar-date'] < datetime_convert('','','now -12 hours'));

		if (!$update_photo OR $no_update) {
			return($contactid);
		}
	} elseif ($uid != 0)
		return 0;

	if (!count($data))
		$data = probe_url($url);

	// Does this address belongs to a valid network?
	if (!in_array($data["network"], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA))) {
		if ($uid != 0)
			return 0;

		// Get data from the gcontact table
		$r = q("SELECT `name`, `nick`, `url`, `photo`, `addr`, `alias`, `network` FROM `gcontact` WHERE `nurl` = '%s'",
			 dbesc(normalise_link($url)));
		if (!$r)
			return 0;

		$data = $r[0];
	}

	$url = $data["url"];

	if ($contactid == 0) {
		q("INSERT INTO `contact` (`uid`, `created`, `url`, `nurl`, `addr`, `alias`, `notify`, `poll`,
					`name`, `nick`, `photo`, `network`, `pubkey`, `rel`, `priority`,
					`batch`, `request`, `confirm`, `poco`, `name-date`, `uri-date`,
					`writable`, `blocked`, `readonly`, `pending`)
					VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', 1, 0, 0, 0)",
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
			dbesc($data["poco"]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert())
		);

		$contact = q("SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d ORDER BY `id` LIMIT 2",
				dbesc(normalise_link($data["url"])),
				intval($uid));
		if (!$contact)
			return 0;

		$contactid = $contact[0]["id"];

		// Update the newly created contact from data in the gcontact table
		$r = q("SELECT `location`, `about`, `keywords`, `gender` FROM `gcontact` WHERE `nurl` = '%s'",
			 dbesc(normalise_link($data["url"])));
		if ($r) {
			logger("Update contact ".$data["url"]);
			q("UPDATE `contact` SET `location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s' WHERE `id` = %d",
				dbesc($r["location"]), dbesc($r["about"]), dbesc($r["keywords"]),
				dbesc($r["gender"]), intval($contactid));
		}
	}

	if ((count($contact) > 1) AND ($uid == 0) AND ($contactid != 0) AND ($url != ""))
		q("DELETE FROM `contact` WHERE `nurl` = '%s' AND `id` != %d",
			dbesc(normalise_link($url)),
			intval($contactid));

	require_once("Photo.php");

	update_contact_avatar($data["photo"],$uid,$contactid);

	$r = q("SELECT `addr`, `alias`, `name`, `nick` FROM `contact`  WHERE `id` = %d", intval($contactid));

	// This condition should always be true
	if (!dbm::is_result($r))
		return $contactid;

	// Only update if there had something been changed
	if (($data["addr"] != $r[0]["addr"]) OR
		($data["alias"] != $r[0]["alias"]) OR
		($data["name"] != $r[0]["name"]) OR
		($data["nick"] != $r[0]["nick"]))
		q("UPDATE `contact` SET `addr` = '%s', `alias` = '%s', `name` = '%s', `nick` = '%s',
			`name-date` = '%s', `uri-date` = '%s' WHERE `id` = %d",
			dbesc($data["addr"]),
			dbesc($data["alias"]),
			dbesc($data["name"]),
			dbesc($data["nick"]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contactid)
		);

	return $contactid;
}

/**
 * @brief Returns posts from a given gcontact
 *
 * @param App $a argv application class
 * @param int $gcontact_id Global contact
 *
 * @return string posts in HTML
 */
function posts_from_gcontact($a, $gcontact_id) {

	require_once('include/conversation.php');

	// There are no posts with "uid = 0" with connector networks
	// This speeds up the query a lot
	$r = q("SELECT `network` FROM `gcontact` WHERE `id` = %d", dbesc($gcontact_id));
	if (in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, "")))
		$sql = "(`item`.`uid` = 0 OR  (`item`.`uid` = %d AND `item`.`private`))";
	else
		$sql = "`item`.`uid` = %d";

	if(get_config('system', 'old_pager')) {
		$r = q("SELECT COUNT(*) AS `total` FROM `item`
			WHERE `gcontact-id` = %d and $sql",
			intval($gcontact_id),
			intval(local_user()));

		$a->set_pager_total($r[0]['total']);
	}

	$r = q("SELECT `item`.`uri`, `item`.*, `item`.`id` AS `item_id`,
			`author-name` AS `name`, `owner-avatar` AS `photo`,
			`owner-link` AS `url`, `owner-avatar` AS `thumb`
		FROM `item` FORCE INDEX (`gcontactid_uid_created`)
		WHERE `gcontact-id` = %d AND $sql AND
			NOT `deleted` AND NOT `moderated` AND `visible`
		ORDER BY `item`.`created` DESC LIMIT %d, %d",
		intval($gcontact_id),
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$o = conversation($a,$r,'community',false);

	if(!get_config('system', 'old_pager')) {
		$o .= alt_pager($a,count($r));
	} else {
		$o .= paginate($a);
	}

	return $o;
}
/**
 * @brief Returns posts from a given contact url
 *
 * @param App $a argv application class
 * @param string $contact_url Contact URL
 *
 * @return string posts in HTML
 */
function posts_from_contact_url($a, $contact_url) {

	require_once('include/conversation.php');

	// There are no posts with "uid = 0" with connector networks
	// This speeds up the query a lot
	$r = q("SELECT `network`, `id` AS `author-id` FROM `contact`
		WHERE `contact`.`nurl` = '%s' AND `contact`.`uid` = 0",
		dbesc(normalise_link($contact_url)));
	if (in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""))) {
		$sql = "(`item`.`uid` = 0 OR (`item`.`uid` = %d AND `item`.`private`))";
	} else {
		$sql = "`item`.`uid` = %d";
	}

	$author_id = intval($r[0]["author-id"]);

	if (get_config('system', 'old_pager')) {
		$r = q("SELECT COUNT(*) AS `total` FROM `item`
			WHERE `author-id` = %d and $sql",
			intval($author_id),
			intval(local_user()));

		$a->set_pager_total($r[0]['total']);
	}

	$r = q(item_query()." AND `item`.`author-id` = %d AND ".$sql.
		" ORDER BY `item`.`created` DESC LIMIT %d, %d",
		intval($author_id),
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$o = conversation($a,$r,'community',false);

	if (!get_config('system', 'old_pager')) {
		$o .= alt_pager($a,count($r));
	} else {
		$o .= paginate($a);
	}

	return $o;
}

/**
 * @brief Returns a formatted location string from the given profile array
 *
 * @param array $profile Profile array (Generated from the "profile" table)
 *
 * @return string Location string
 */
function formatted_location($profile) {
	$location = '';

	if($profile['locality'])
		$location .= $profile['locality'];

	if($profile['region'] AND ($profile['locality'] != $profile['region'])) {
		if($location)
			$location .= ', ';

		$location .= $profile['region'];
	}

	if($profile['country-name']) {
		if($location)
			$location .= ', ';

		$location .= $profile['country-name'];
	}

	return $location;
}

/**
 * @brief Returns the account type name
 *
 * The function can be called with either the user or the contact array
 *
 * @param array $contact contact or user array
 */
function account_type($contact) {

	// There are several fields that indicate that the contact or user is a forum
	// "page-flags" is a field in the user table,
	// "forum" and "prv" are used in the contact table. They stand for PAGE_COMMUNITY and PAGE_PRVGROUP.
	// "community" is used in the gcontact table and is true if the contact is PAGE_COMMUNITY or PAGE_PRVGROUP.
	if((isset($contact['page-flags']) && (intval($contact['page-flags']) == PAGE_COMMUNITY))
		|| (isset($contact['page-flags']) && (intval($contact['page-flags']) == PAGE_PRVGROUP))
		|| (isset($contact['forum']) && intval($contact['forum']))
		|| (isset($contact['prv']) && intval($contact['prv']))
		|| (isset($contact['community']) && intval($contact['community'])))
		$type = ACCOUNT_TYPE_COMMUNITY;
	else
		$type = ACCOUNT_TYPE_PERSON;

	// The "contact-type" (contact table) and "account-type" (user table) are more general then the chaos from above.
	if (isset($contact["contact-type"]))
		$type = $contact["contact-type"];
	if (isset($contact["account-type"]))
		$type = $contact["account-type"];

	switch($type) {
		case ACCOUNT_TYPE_ORGANISATION:
			$account_type = t("Organisation");
			break;
		case ACCOUNT_TYPE_NEWS:
			$account_type = t('News');
			break;
		case ACCOUNT_TYPE_COMMUNITY:
			$account_type = t("Forum");
			break;
		default:
			$account_type = "";
			break;
	}

	return $account_type;
}
?>
