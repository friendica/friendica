<?php

/**
 * @file src/Model/Item.php
 */

namespace Friendica\Model;

use Friendica\Core\Worker;
use Friendica\Model\Term;
use Friendica\Model\Contact;
use Friendica\Database\DBM;
use dba;
use Text_LanguageDetect;

require_once 'include/tags.php';
require_once 'include/threads.php';
require_once 'include/items.php';

class Item
{
	/**
	 * @brief Update existing item entries
	 *
	 * @param array $fields The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		$success = dba::update('item', $fields, $condition);

		if (!$success) {
			return false;
		}

		$rows = dba::affected_rows();

		// We cannot simply expand the condition to check for origin entries
		// The condition needn't to be a simple array but could be a complex condition.
		$items = dba::select('item', ['id', 'origin'], $condition);
		while ($item = dba::fetch($items)) {
			// We only need to notfiy others when it is an original entry from us
			if (!$item['origin']) {
				continue;
			}

			create_tags_from_item($item['id']);
			Term::createFromItem($item['id']);
			update_thread($item['id']);

			Worker::add(PRIORITY_HIGH, "Notifier", 'edit_post', $item['id']);
		}

		return $rows;
	}

	/**
	 * @brief Delete an item and notify others about it - if it was ours
	 *
	 * @param integer $item_id Item ID that should be delete
	 *
	 * @return $boolean success
	 */
	public static function delete($item_id, $priority = PRIORITY_HIGH)
	{
		// locate item to be deleted
		$fields = ['id', 'uid', 'parent', 'parent-uri', 'origin', 'deleted', 'file', 'resource-id', 'event-id', 'attach'];
		$item = dba::selectFirst('item', $fields, ['id' => $item_id]);
		if (!DBM::isResult($item)) {
			return false;
		}

		if ($item['deleted']) {
			return false;
		}

		$parent = dba::selectFirst('item', ['origin'], ['id' => $item['parent']]);
		if (!DBM::isResult($parent)) {
			$parent = ['origin' => false];
		}

		logger('delete item: ' . $item['id'], LOGGER_DEBUG);

		// clean up categories and tags so they don't end up as orphans

		$matches = false;
		$cnt = preg_match_all('/<(.*?)>/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				file_tag_unsave_file($item['uid'], $item['id'], $mtch[1],true);
			}
		}

		$matches = false;

		$cnt = preg_match_all('/\[(.*?)\]/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				file_tag_unsave_file($item['uid'], $item['id'], $mtch[1],false);
			}
		}

		/*
		 * If item is a link to a photo resource, nuke all the associated photos
		 * (visitors will not have photo resources)
		 * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		 * generate a resource-id and therefore aren't intimately linked to the item.
		 */
		if (strlen($item['resource-id'])) {
			dba::delete('photo', ['resource-id' => $item['resource-id'], 'uid' => $item['uid']]);
		}

		// If item is a link to an event, nuke the event record.
		if (intval($item['event-id'])) {
			dba::delete('event', ['id' => $item['event-id'], 'uid' => $item['uid']]);
		}

		// If item has attachments, drop them
		foreach (explode(", ", $item['attach']) as $attach) {
			preg_match("|attach/(\d+)|", $attach, $matches);
			dba::delete('attach', ['id' => $matches[1], 'uid' => $item['uid']]);
		}

		// Set the item to "deleted"
		dba::update('item', ['deleted' => true, 'title' => '', 'body' => '',
					'edited' => datetime_convert(), 'changed' => datetime_convert()],
				['id' => $item['id']]);

		create_tags_from_item($item['id']);
		Term::createFromItem($item['id']);
		delete_thread($item['id'], $item['parent-uri']);

		// If it's the parent of a comment thread, kill all the kids
		if ($item['id'] == $item['parent']) {
			$items = dba::select('item', ['id'], ['parent' => $item['parent']]);
			while ($row = dba::fetch($items)) {
				self::delete($row['id'], $priority);
			}
		}

		// send the notification upstream/downstream
		if ($item['origin'] || $parent['origin']) {
			Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", "drop", intval($item['id']));
		}

		return true;
	}

	/**
	 * @brief Add a shadow entry for a given item id that is a thread starter
	 *
	 * We store every public item entry additionally with the user id "0".
	 * This is used for the community page and for the search.
	 * It is planned that in the future we will store public item entries only once.
	 *
	 * @param integer $itemid Item ID that should be added
	 */
	public static function addShadow($itemid)
	{
		$fields = ['uid', 'wall', 'private', 'moderated', 'visible', 'contact-id', 'deleted', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];
		$item = dba::selectFirst('item', $fields, $condition);

		if (!DBM::isResult($item)) {
			return;
		}

		// is it already a copy?
		if (($itemid == 0) || ($item['uid'] == 0)) {
			return;
		}

		// Is it a visible public post?
		if (!$item["visible"] || $item["deleted"] || $item["moderated"] || $item["private"]) {
			return;
		}

		// is it an entry from a connector? Only add an entry for natively connected networks
		if (!in_array($item["network"], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""])) {
			return;
		}

		// Is the public contact configured as hidden?
		if (Contact::isHidden($item["owner-id"]) || Contact::isHidden($item["author-id"])) {
			return;
		}

		// Only do these checks if the post isn't a wall post
		if (!$item["wall"]) {
			// Check, if hide-friends is activated - then don't do a shadow entry
			if (dba::exists('profile', ['is-default' => true, 'uid' => $item['uid'], 'hide-friends' => true])) {
				return;
			}

			// Check if the contact is hidden or blocked
			if (!dba::exists('contact', ['hidden' => false, 'blocked' => false, 'id' => $item['contact-id']])) {
				return;
			}
		}

		// Only add a shadow, if the profile isn't hidden
		if (dba::exists('user', ['uid' => $item['uid'], 'hidewall' => true])) {
			return;
		}

		$item = dba::selectFirst('item', [], ['id' => $itemid]);

		if (DBM::isResult($item) && ($item["allow_cid"] == '')  && ($item["allow_gid"] == '') &&
			($item["deny_cid"] == '') && ($item["deny_gid"] == '')) {

			if (!dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
				// Preparing public shadow (removing user specific data)
				unset($item['id']);
				$item['uid'] = 0;
				$item['origin'] = 0;
				$item['wall'] = 0;
				$item['contact-id'] = Contact::getIdForURL($item['author-link'], 0);

				if (in_array($item['type'], ["net-comment", "wall-comment"])) {
					$item['type'] = 'remote-comment';
				} elseif ($item['type'] == 'wall') {
					$item['type'] = 'remote';
				}

				$public_shadow = item_store($item, false, false, true);

				logger("Stored public shadow for thread ".$itemid." under id ".$public_shadow, LOGGER_DEBUG);
			}
		}
	}

	/**
	 * @brief Add a shadow entry for a given item id that is a comment
	 *
	 * This function does the same like the function above - but for comments
	 *
	 * @param integer $itemid Item ID that should be added
	 */
	public static function addShadowPost($itemid)
	{
		$item = dba::selectFirst('item', [], ['id' => $itemid]);
		if (!DBM::isResult($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['id'] == $item['parent']) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0)
			return;

		// Is there a shadow parent?
		if (!dba::exists('item', ['uri' => $item['parent-uri'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		// Preparing public shadow (removing user specific data)
		unset($item['id']);
		$item['uid'] = 0;
		$item['origin'] = 0;
		$item['wall'] = 0;
		$item['contact-id'] = Contact::getIdForURL($item['author-link'], 0);

		if (in_array($item['type'], ["net-comment", "wall-comment"])) {
			$item['type'] = 'remote-comment';
		} elseif ($item['type'] == 'wall') {
			$item['type'] = 'remote';
		}

		$public_shadow = item_store($item, false, false, true);

		logger("Stored public shadow for comment ".$item['uri']." under id ".$public_shadow, LOGGER_DEBUG);
	}

	 /**
	 * Adds a "lang" specification in a "postopts" element of given $arr,
	 * if possible and not already present.
	 * Expects "body" element to exist in $arr.
	 *
	 * @todo change to "local", once the "Item::insert" is in this class
	 */
	public static function addLanguageInPostopts(&$arr)
	{
		if (x($arr, 'postopts')) {
			if (strstr($arr['postopts'], 'lang=')) {
				// do not override
				return;
			}
			$postopts = $arr['postopts'];
		} else {
			$postopts = "";
		}

		$naked_body = preg_replace('/\[(.+?)\]/','', $arr['body']);
		$l = new Text_LanguageDetect();
		$lng = $l->detect($naked_body, 3);

		if (sizeof($lng) > 0) {
			if ($postopts != "") {
				$postopts .= '&'; // arbitrary separator, to be reviewed
			}

			$postopts .= 'lang=';
			$sep = "";

			foreach ($lng as $language => $score) {
				$postopts .= $sep . $language . ";" . $score;
				$sep = ':';
			}
			$arr['postopts'] = $postopts;
		}
	}

	/**
	 * @brief Creates an unique guid out of a given uri
	 *
	 * @param string $uri uri of an item entry
	 * @param string $host (Optional) hostname for the GUID prefix
	 * @return string unique guid
	 */
	public static function guidFromUri($uri, $host = "")
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		$parsed = parse_url($uri);

		// When the hostname isn't given, we take it from the uri
		if ($host == "") {
			// Is it in the format data@host.tld?
			if ((count($parsed) == 1) && strstr($uri, '@')) {
				$mailparts = explode('@', $uri);
				$host = array_pop($mailparts);
			} else {
				$host = $parsed["host"];
			}
		}

		// We use a hash of the hostname as prefix for the guid
		$guid_prefix = hash("crc32", $host);

		// Remove the scheme to make sure that "https" and "http" doesn't make a difference
		unset($parsed["scheme"]);

		// Glue it together to be able to make a hash from it
		$host_id = implode("/", $parsed);

		// We could use any hash algorithm since it isn't a security issue
		$host_hash = hash("ripemd128", $host_id);

		return $guid_prefix.$host_hash;
	}

	/**
	 * @brief Set "success_update" and "last-item" to the date of the last time we heard from this contact
	 *
	 * This can be used to filter for inactive contacts.
	 * Only do this for public postings to avoid privacy problems, since poco data is public.
	 * Don't set this value if it isn't from the owner (could be an author that we don't know)
	 *
	 * @Todo Set this to private, once Item::insert is there
	 *
	 * @param array $arr Contains the just posted item record
	 */
	public static function updateContact($arr) {
		// Unarchive the author
		$contact = dba::selectFirst('contact', [], ['id' => $arr["author-link"]]);
		if ($contact['term-date'] > NULL_DATE) {
			 Contact::unmarkForArchival($contact);
		}

		// Unarchive the contact if it is a toplevel posting
		if ($arr["parent-uri"] === $arr["uri"]) {
			$contact = dba::selectFirst('contact', [], ['id' => $arr["contact-id"]]);
			if ($contact['term-date'] > NULL_DATE) {
				 Contact::unmarkForArchival($contact);
			}
		}

		$update = (!$arr['private'] && (($arr["author-link"] === $arr["owner-link"]) || ($arr["parent-uri"] === $arr["uri"])));

		// Is it a forum? Then we don't care about the rules from above
		if (!$update && ($arr["network"] == NETWORK_DFRN) && ($arr["parent-uri"] === $arr["uri"])) {
			$isforum = q("SELECT `forum` FROM `contact` WHERE `id` = %d AND `forum`",
					intval($arr['contact-id']));
			if (DBM::isResult($isforum)) {
				$update = true;
			}
		}

		if ($update) {
			dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['contact-id']]);
		}
		// Now do the same for the system wide contacts with uid=0
		if (!$arr['private']) {
			dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['owner-id']]);

			if ($arr['owner-id'] != $arr['author-id']) {
				dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
					['id' => $arr['author-id']]);
			}
		}
	}
}
