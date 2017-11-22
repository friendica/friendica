<?php

/**
 * @file src/Object/Contact.php
 */

namespace Friendica\Object;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Network\Probe;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\OStatus;
use dba;

require_once 'boot.php';
require_once 'include/text.php';

/**
 * @brief functions for interacting with a contact
 */
class Contact extends BaseObject
{
	/**
	 * @brief Marks a contact for removal
	 *
	 * @param int $id contact id
	 * @return null
	 */
	public static function remove($id)
	{
		// We want just to make sure that we don't delete our "self" contact
		$condition = array('`id` = ? AND NOT `self`', $id);
		$r = dba::select('contact', array('uid'), $condition, array('limit' => 1));

		if (!DBM::is_result($r) || !intval($r[0]['uid'])) {
			return;
		}

		$archive = PConfig::get($r[0]['uid'], 'system', 'archive_removed_contacts');
		if ($archive) {
			dba::update('contact', array('archive' => 1, 'network' => 'none', 'writable' => 0), array('id' => $id));
			return;
		}

		dba::delete('contact', array('id' => $id));

		// Delete the rest in the background
		Worker::add(PRIORITY_LOW, 'RemoveContact', $id);
	}

	/**
	 * @brief Sends an unfriend message. Does not remove the contact
	 *
	 * @param array $user    User unfriending
	 * @param array $contact Contact unfriended
	 */
	public static function terminateFriendship(array $user, array $contact)
	{
		if ($contact['network'] === NETWORK_OSTATUS) {
			// create an unfollow slap
			$item = array();
			$item['verb'] = NAMESPACE_OSTATUS . "/unfollow";
			$item['follow'] = $contact["url"];
			$slap = OStatus::salmon($item, $user);

			if ((x($contact, 'notify')) && (strlen($contact['notify']))) {
				require_once 'include/salmon.php';
				slapper($user, $contact['notify'], $slap);
			}
		} elseif ($contact['network'] === NETWORK_DIASPORA) {
			Diaspora::sendUnshare($user, $contact);
		} elseif ($contact['network'] === NETWORK_DFRN) {
			DFRN::deliver($user, $contact, 'placeholder', 1);
		}
	}

	/**
	 * @brief Marks a contact for archival after a communication issue delay
	 *
	 * Contact has refused to recognise us as a friend. We will start a countdown.
	 * If they still don't recognise us in 32 days, the relationship is over,
	 * and we won't waste any more time trying to communicate with them.
	 * This provides for the possibility that their database is temporarily messed
	 * up or some other transient event and that there's a possibility we could recover from it.
	 *
	 * @param array $contact contact to mark for archival
	 * @return type
	 */
	public static function markForArchival(array $contact)
	{
		// Contact already archived, nothing to do
		if ($contact['archive']) {
			return;
		}

		if ($contact['term-date'] <= NULL_DATE) {
			dba::update('contact', array('term-date' => datetime_convert()), array('id' => $contact['id']));

			if ($contact['url'] != '') {
				dba::update('contact', array('term-date' => datetime_convert()), array('nurl' => normalise_link($contact['url']), 'term-date' <= '1000-00-00'));
			}
		} else {
			/* @todo
			 * We really should send a notification to the owner after 2-3 weeks
			 * so they won't be surprised when the contact vanishes and can take
			 * remedial action if this was a serious mistake or glitch
			 */

			/// @todo Check for contact vitality via probing
			$expiry = $contact['term-date'] . ' + 32 days ';
			if (datetime_convert() > datetime_convert('UTC', 'UTC', $expiry)) {
				/* Relationship is really truly dead. archive them rather than
				 * delete, though if the owner tries to unarchive them we'll start
				 * the whole process over again.
				 */
				dba::update('contact', array('archive' => 1), array('id' => $contact['id']));

				if ($contact['url'] != '') {
					dba::update('contact', array('archive' => 1), array('nurl' => normalise_link($contact['url'])));
				}
			}
		}
	}

	/**
	 * @brief Cancels the archival countdown
	 *
	 * @see Contact::markForArchival()
	 *
	 * @param array $contact contact to be unmarked for archival
	 * @return null
	 */
	public static function unmarkForArchival(array $contact)
	{
		$condition = array('`id` => ? AND (`term-date` > `1000-00-00 00:00:00` OR `archive`)', $contact[`id`]);
		$r = dba::select('contact', array('term-date'), $condition);

		// We don't need to update, we never marked this contact for archival
		if (!DBM::is_result($r)) {
			return;
		}

		// It's a miracle. Our dead contact has inexplicably come back to life.
		$fields = array('term-date' => NULL_DATE, 'archive' => false);
		dba::update('contact', $fields, array('id' => $contact['id']));

		if ($contact['url'] != '') {
			dba::update('contact', $fields, array('nurl' => normalise_link($contact['url'])));
		}
	}

	/**
	 * @brief Get contact data for a given profile link
	 *
	 * The function looks at several places (contact table and gcontact table) for the contact
	 * It caches its result for the same script execution to prevent duplicate calls
	 *
	 * @param string $url     The profile link
	 * @param int    $uid     User id
	 * @param array  $default If not data was found take this data as default value
	 *
	 * @return array Contact data
	 */
	public static function getDetailsByURL($url, $uid = -1, array $default = [])
	{
		static $cache = array();

		if ($url == '') {
			return $default;
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		if (isset($cache[$url][$uid])) {
			return $cache[$url][$uid];
		}

		$ssl_url = str_replace('http://', 'https://', $url);

		// Fetch contact data from the contact table for the given user
		$fields = array('`id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
		`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`');
		$s = dba::select('contact', $fields, array('nurl' => normalise_link($url), 'uid' => $uid));
		$r = dba::inArray($s);

		// Fetch contact data from the contact table for the given user, checking with the alias
		if (!DBM::is_result($r)) {
			$fields = array('`id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`');
			dba::select('contact', $fields, array('`alias` IN (?, ?, ?) AND `uid` = ?', normalise_link($url), $url, $ssl_url, $uid));
			$r = dba::inArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBM::is_result($r)) {
			$fields = array('`id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`');
			$s = dba::select('contact', $fields, array('nurl' => normalise_link($url), 'uid' => 0));
			$r = dba::inArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically) - checked with the alias
		if (!DBM::is_result($r)) {
			$fields = array('`id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`');
			$s = dba::select('contact', $fields, array('`alias` IN (?, ?, ?) AND `uid` = 0', normalise_link($url), $url, $ssl_url));
			$r = dba::inArray($s);
		}

		// Fetch the data from the gcontact table
		if (!DBM::is_result($r)) {
			$fields = array('0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `` AS `xmpp`,
			`keywords`, `gender`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, `community` AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`');
			$s = dba::select('gcontact', $fields, array('nurl' => normalise_link($url)));
			$r = dba::inArray($s);
		}

		if (DBM::is_result($r)) {
			// If there is more than one entry we filter out the connector networks
			if (count($r) > 1) {
				foreach ($r as $id => $result) {
					if ($result["network"] == NETWORK_STATUSNET) {
						unset($r[$id]);
					}
				}
			}

			$profile = array_shift($r);

			// "bd" always contains the upcoming birthday of a contact.
			// "birthday" might contain the birthday including the year of birth.
			if ($profile["birthday"] > '0001-01-01') {
				$bd_timestamp = strtotime($profile["birthday"]);
				$month = date("m", $bd_timestamp);
				$day = date("d", $bd_timestamp);

				$current_timestamp = time();
				$current_year = date("Y", $current_timestamp);
				$current_month = date("m", $current_timestamp);
				$current_day = date("d", $current_timestamp);

				$profile["bd"] = $current_year . "-" . $month . "-" . $day;
				$current = $current_year . "-" . $current_month . "-" . $current_day;

				if ($profile["bd"] < $current) {
					$profile["bd"] = ( ++$current_year) . "-" . $month . "-" . $day;
				}
			} else {
				$profile["bd"] = '0001-01-01';
			}
		} else {
			$profile = $default;
		}

		if (($profile["photo"] == "") && isset($default["photo"])) {
			$profile["photo"] = $default["photo"];
		}

		if (($profile["name"] == "") && isset($default["name"])) {
			$profile["name"] = $default["name"];
		}

		if (($profile["network"] == "") && isset($default["network"])) {
			$profile["network"] = $default["network"];
		}

		if (($profile["thumb"] == "") && isset($profile["photo"])) {
			$profile["thumb"] = $profile["photo"];
		}

		if (($profile["micro"] == "") && isset($profile["thumb"])) {
			$profile["micro"] = $profile["thumb"];
		}

		if ((($profile["addr"] == "") || ($profile["name"] == "")) && ($profile["gid"] != 0)
			&& in_array($profile["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS))
		) {
			Worker::add(PRIORITY_LOW, "UpdateGContact", $profile["gid"]);
		}

		// Show contact details of Diaspora contacts only if connected
		if (($profile["cid"] == 0) && ($profile["network"] == NETWORK_DIASPORA)) {
			$profile["location"] = "";
			$profile["about"] = "";
			$profile["gender"] = "";
			$profile["birthday"] = '0001-01-01';
		}

		$cache[$url][$uid] = $profile;

		return $profile;
	}

	/**
	 * @brief Get contact data for a given address
	 *
	 * The function looks at several places (contact table and gcontact table) for the contact
	 *
	 * @param string $addr The profile link
	 * @param int    $uid  User id
	 *
	 * @return array Contact data
	 */
	public static function getDetailsByAddr($addr, $uid = -1)
	{
		static $cache = array();

		if ($addr == '') {
			return array();
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		// Fetch contact data from the contact table for the given user
		$fields = array('`id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
		`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`');
		$r = dba::select('contact', $fields, array('addr' => $addr, 'uid' => $uid));

		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBM::is_result($r)) {
			$fields = array('`id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`');
			$r = dba::select('contact', $fields, array('addr' => $addr, 'uid' => 0));
		}

		// Fetch the data from the gcontact table
		if (!DBM::is_result($r)) {
			$fields = array('0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `` AS `xmpp`,
			`keywords`, `gender`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, `community` AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`');
			$r = dba::select('gcontact', $fields, array('addr' => $addr));
		}

		if (!DBM::is_result($r)) {
			$data = Probe::uri($addr);

			$profile = self::getDetailsByURL($data['url'], $uid);
		} else {
			$profile = $r[0];
		}

		return $profile;
	}

	/**
	 * @brief Returns the data array for the photo menu of a given contact
	 *
	 * @param array $contact contact
	 * @param int   $uid     optional, default 0
	 * @return array
	 */
	public static function photoMenu(array $contact, $uid = 0)
	{
		// @todo Unused, to be removed
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
				$menu = array('profile' => array(t('View Profile'), $profile_link, true));

				return $menu;
			}

			$r = dba::select('contact', array(), array('nurl' => $contact['nurl'], 'network' => $contact['network'], 'uid' => $uid));
			if ($r) {
				return self::photoMenu($r[0], $uid);
			} else {
				$profile_link = zrl($contact['url']);
				$connlnk = 'follow/?url=' . $contact['url'];
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
			$profile_link = System::baseUrl() . '/redir/' . $contact['id'];
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
			$pm_url = System::baseUrl() . '/message/new/' . $contact['id'];
		}

		if ($contact['network'] == NETWORK_DFRN) {
			$poke_link = System::baseUrl() . '/poke/?f=&c=' . $contact['id'];
		}

		$contact_url = System::baseUrl() . '/contacts/' . $contact['id'];

		$posts_link = System::baseUrl() . '/contacts/' . $contact['id'] . '/posts';
		$contact_drop_link = System::baseUrl() . '/contacts/' . $contact['id'] . '/drop?confirm=1';

		/**
		 * Menu array:
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

		foreach ($menu as $menuname => $menuitem) {
			if ($menuitem[1] != '') {
				$menucondensed[$menuname] = $menuitem;
			}
		}

		return $menucondensed;
	}

	/**
	 * @brief Returns ungrouped contact count or list for user
	 *
	 * Returns either the total number of ungrouped contacts for the given user
	 * id or a paginated list of ungrouped contacts.
	 *
	 * @param int $uid   uid
	 * @param int $start optional, default 0
	 * @param int $count optional, default 0
	 *
	 * @return array
	 */
	public static function getUngroupedList($uid, $start = 0, $count = 0)
	{
		if (!$count) {
			$fields = array('COUNT(*) AS `total`');
			$condition = array('`uid` = ? AND `self` = 0 AND `id` NOT IN (SELECT DISTINCT(`contact-id`)	FROM `group_member`	WHERE `uid` = ?', $uid, $uid);
			$r = dba::select('contact', $fields, $condition);

			return $r;
		}

		$innerCondition = array('`id` NOT IN (SELECT DISTINCT(`contact-id`) FROM `group_member` WHERE `uid` = ?', $uid);
		$r = dba::select('contact', array(), array('uid' => $uid, 'self' => 0, $innerCondition, 'blocked' => 0, 'pending' => 0), array('limit ?, ?', $start, $count));

		return $r;
	}

	/**
	 * @brief Fetch the contact id for a given url and user
	 *
	 * First lookup in the contact table to find a record matching either `url`, `nurl`,
	 * `addr` or `alias`.
	 *
	 * If there's no record and we aren't looking for a public contact, we quit.
	 * If there's one, we check that it isn't time to update the picture else we
	 * directly return the found contact id.
	 *
	 * Second, we probe the provided $url wether it's http://server.tld/profile or
	 * nick@server.tld. We quit if we can't get any info back.
	 *
	 * Third, we create the contact record if it doesn't exist
	 *
	 * Fourth, we update the existing record with the new data (avatar, alias, nick)
	 * if there's any updates
	 *
	 * @param string  $url       Contact URL
	 * @param integer $uid       The user id for the contact (0 = public contact)
	 * @param boolean $no_update Don't update the contact
	 *
	 * @return integer Contact ID
	 */
	public static function getIdForURL($url, $uid = 0, $no_update = false)
	{
		logger("Get contact data for url " . $url . " and user " . $uid . " - " . System::callstack(), LOGGER_DEBUG);

		$contact_id = 0;

		if ($url == '') {
			return 0;
		}

		/// @todo Verify if we can't use Contact::getDetailsByUrl instead of the following
		// We first try the nurl (http://server.tld/nick), most common case
		$contact = dba::select('contact', array('id', 'avatar-date'), array('nurl' => normalise_link($url), 'uid' => $uid), array('limit' => 1));

		// Then the addr (nick@server.tld)
		if (!DBM::is_result($contact)) {
			$contact = dba::select('contact', array('id', 'avatar-date'), array('addr' => $url, 'uid' => $uid), array('limit' => 1));
		}

		// Then the alias (which could be anything)
		if (!DBM::is_result($contact)) {
			// The link could be provided as http although we stored it as https
			$ssl_url = str_replace('http://', 'https://', $url);
			$r = dba::select('contact', array('id', 'avatar-date'), array('`alias` IN (?, ?, ?) AND `uid` = ?', $url, normalise_link($url), $ssl_url, $uid), array('limit' => 1));
			$contact = dba::fetch($r);
			dba::close($r);
		}

		if (DBM::is_result($contact)) {
			$contact_id = $contact["id"];

			// Update the contact every 7 days
			$update_contact = ($contact['avatar-date'] < datetime_convert('', '', 'now -7 days'));

			// We force the update if the avatar is empty
			if ($contact['avatar'] == '') {
				$update_contact = true;
			}

			if (!$update_contact || $no_update) {
				return $contact_id;
			}
		} elseif ($uid != 0) {
			// Non-existing user-specific contact, exiting
			return 0;
		}

		$data = Probe::uri($url, "", $uid);

		// Last try in gcontact for unsupported networks
		if (!in_array($data["network"], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA, NETWORK_PUMPIO, NETWORK_MAIL))) {
			if ($uid != 0) {
				return 0;
			}

			// Get data from the gcontact table
			$gcontacts = dba::select('gcontact', array('name', 'nick', 'url', 'photo', 'addr', 'alias', 'network'), array('nurl' => normalise_link($url)), array('limit' => 1));
			if (!DBM::is_result($gcontacts)) {
				return 0;
			}

			$data = array_merge($data, $gcontacts);
		}

		if (!$contact_id && ($data["alias"] != '') && ($data["alias"] != $url)) {
			$contact_id = self::getIdForURL($data["alias"], $uid, true);
		}

		$url = $data["url"];
		if (!$contact_id) {
			dba::insert(
				'contact', array('uid' => $uid, 'created' => datetime_convert(), 'url' => $data["url"],
				'nurl' => normalise_link($data["url"]), 'addr' => $data["addr"],
				'alias' => $data["alias"], 'notify' => $data["notify"], 'poll' => $data["poll"],
				'name' => $data["name"], 'nick' => $data["nick"], 'photo' => $data["photo"],
				'keywords' => $data["keywords"], 'location' => $data["location"], 'about' => $data["about"],
				'network' => $data["network"], 'pubkey' => $data["pubkey"],
				'rel' => CONTACT_IS_SHARING, 'priority' => $data["priority"],
				'batch' => $data["batch"], 'request' => $data["request"],
				'confirm' => $data["confirm"], 'poco' => $data["poco"],
				'name-date' => datetime_convert(), 'uri-date' => datetime_convert(),
				'avatar-date' => datetime_convert(), 'writable' => 1, 'blocked' => 0,
				'readonly' => 0, 'pending' => 0)
			);

			$contacts = dba::select('contact', array('id'), array('nurl' => normalise_link($data["url"]), 'uid' => $uid), array('order' => 'id', 'limit' => 2));
			if (!DBM::is_result($contacts)) {
				return 0;
			}

			$contact_id = $contacts[0]["id"];

			// Update the newly created contact from data in the gcontact table
			$gcontact = dba::select('gcontact', array('location', 'about', 'keywords', 'gender'), array('nurl' => normalise_link($data["url"])), array('limit' => 1));
			if (DBM::is_result($gcontact)) {
				// Only use the information when the probing hadn't fetched these values
				if ($data['keywords'] != '') {
					unset($gcontact['keywords']);
				}
				if ($data['location'] != '') {
					unset($gcontact['location']);
				}
				if ($data['about'] != '') {
					unset($gcontact['about']);
				}
				dba::update('contact', $gcontact, array('id' => $contact_id));
			}

			if (count($contacts) > 1 && $uid == 0 && $contact_id != 0 && $data["url"] != "") {
				dba::delete('contact', array("`nurl` = ? AND `uid` = 0 AND `id` != ? AND NOT `self`",
					normalise_link($data["url"]), $contact_id));
			}
		}

		require_once 'include/Photo.php';

		update_contact_avatar($data["photo"], $uid, $contact_id);

		$contact = dba::select('contact', array('url', 'nurl', 'addr', 'alias', 'name', 'nick', 'keywords', 'location', 'about', 'avatar-date'), array('id' => $contact_id), array('limit' => 1));

		// This condition should always be true
		if (!DBM::is_result($contact)) {
			return $contact_id;
		}

		$updated = array('addr' => $data['addr'],
			'alias' => $data['alias'],
			'url' => $data['url'],
			'nurl' => normalise_link($data['url']),
			'name' => $data['name'],
			'nick' => $data['nick']);

		if ($data['keywords'] != '') {
			$updated['keywords'] = $data['keywords'];
		}
		if ($data['location'] != '') {
			$updated['location'] = $data['location'];
		}
		if ($data['about'] != '') {
			$updated['about'] = $data['about'];
		}

		if (($data["addr"] != $contact["addr"]) || ($data["alias"] != $contact["alias"])) {
			$updated['uri-date'] = datetime_convert();
		}
		if (($data["name"] != $contact["name"]) || ($data["nick"] != $contact["nick"])) {
			$updated['name-date'] = datetime_convert();
		}

		$updated['avatar-date'] = datetime_convert();

		dba::update('contact', $updated, array('id' => $contact_id), $contact);

		return $contact_id;
	}

	/**
	 * @brief Checks if the contact is blocked
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact blocked?
	 */
	public static function isBlocked($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$blocked = dba::select('contact', array('blocked'), array('id' => $cid), array('limit' => 1));
		if (!DBM::is_result($blocked)) {
			return false;
		}
		return (bool) $blocked['blocked'];
	}

	/**
	 * @brief Checks if the contact is hidden
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact hidden?
	 */
	public static function isHidden($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$hidden = dba::select('contact', array('hidden'), array('id' => $cid), array('limit' => 1));
		if (!DBM::is_result($hidden)) {
			return false;
		}
		return (bool) $hidden['hidden'];
	}

	/**
	 * @brief Returns posts from a given contact url
	 *
	 * @param string $contact_url Contact URL
	 *
	 * @return string posts in HTML
	 */
	public static function getPostsFromUrl($contact_url)
	{
		require_once 'include/conversation.php';

		// There are no posts with "uid = 0" with connector networks
		// This speeds up the query a lot
		$r = dba::select('contact', array('network', 'id AS author-id', 'contact-type'), array('contact.nurl' => normalise_link($contact_url), 'contact.uid' => 0));

		if (!DBM::is_result($r)) {
			return '';
		}

		if (in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""))) {
			$sql = "(`item`.`uid` = 0 OR (`item`.`uid` = %d AND NOT `item`.`global`))";
		} else {
			$sql = "`item`.`uid` = %d";
		}

		$author_id = intval($r[0]["author-id"]);

		$contact = ($r[0]["contact-type"] == ACCOUNT_TYPE_COMMUNITY ? 'owner-id' : 'author-id');

		$r = q(item_query() . " AND `item`.`" . $contact . "` = %d AND " . $sql .
			" ORDER BY `item`.`created` DESC LIMIT %d, %d", intval($author_id), intval(local_user()), intval($a->pager['start']), intval($a->pager['itemspage'])
		);

		$a = self::getApp();

		$o = conversation($a, $r, 'community', false);

		$o .= alt_pager($a, count($r));

		return $o;
	}

	/**
	 * @brief Returns the account type name
	 *
	 * The function can be called with either the user or the contact array
	 *
	 * @param array $contact contact or user array
	 * @return string
	 */
	public static function getAccountType(array $contact)
	{
		// There are several fields that indicate that the contact or user is a forum
		// "page-flags" is a field in the user table,
		// "forum" and "prv" are used in the contact table. They stand for PAGE_COMMUNITY and PAGE_PRVGROUP.
		// "community" is used in the gcontact table and is true if the contact is PAGE_COMMUNITY or PAGE_PRVGROUP.
		if ((isset($contact['page-flags']) && (intval($contact['page-flags']) == PAGE_COMMUNITY))
			|| (isset($contact['page-flags']) && (intval($contact['page-flags']) == PAGE_PRVGROUP))
			|| (isset($contact['forum']) && intval($contact['forum']))
			|| (isset($contact['prv']) && intval($contact['prv']))
			|| (isset($contact['community']) && intval($contact['community']))
		) {
			$type = ACCOUNT_TYPE_COMMUNITY;
		} else {
			$type = ACCOUNT_TYPE_PERSON;
		}

		// The "contact-type" (contact table) and "account-type" (user table) are more general then the chaos from above.
		if (isset($contact["contact-type"])) {
			$type = $contact["contact-type"];
		}
		if (isset($contact["account-type"])) {
			$type = $contact["account-type"];
		}

		switch ($type) {
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
}
