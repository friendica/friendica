<?php
/**
 * @file include/ostatus.php
 */

use Friendica\App;
use Friendica\Core\Cache;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\GContact;
use Friendica\Network\Probe;
use Friendica\Util\Lock;
use Friendica\Util\XML;

require_once 'include/Contact.php';
require_once 'include/threads.php';
require_once 'include/html2bbcode.php';
require_once 'include/bbcode.php';
require_once 'include/items.php';
require_once 'mod/share.php';
require_once 'include/enotify.php';
require_once 'include/Photo.php';
require_once 'include/follow.php';
require_once 'include/api.php';
require_once 'mod/proxy.php';

/**
 * @brief This class contain functions for the OStatus protocol
 */
class ostatus
{
	private static $itemlist;
	private static $conv_list = array();

	/**
	 * @brief Fetches author data
	 *
	 * @param object $xpath     The xpath object
	 * @param object $context   The xml context of the author details
	 * @param array  $importer  user record of the importing user
	 * @param array  $contact   Called by reference, will contain the fetched contact
	 * @param bool   $onlyfetch Only fetch the header without updating the contact entries
	 *
	 * @return array Array of author related entries for the item
	 */
	private static function fetchauthor($xpath, $context, $importer, &$contact, $onlyfetch)
	{
		$author = array();
		$author["author-link"] = $xpath->evaluate('atom:author/atom:uri/text()', $context)->item(0)->nodeValue;
		$author["author-name"] = $xpath->evaluate('atom:author/atom:name/text()', $context)->item(0)->nodeValue;
		$addr = $xpath->evaluate('atom:author/atom:email/text()', $context)->item(0)->nodeValue;

		$aliaslink = $author["author-link"];

		$alternate = $xpath->query("atom:author/atom:link[@rel='alternate']", $context)->item(0)->attributes;
		if (is_object($alternate)) {
			foreach ($alternate as $attributes) {
				if (($attributes->name == "href") && ($attributes->textContent != "")) {
					$author["author-link"] = $attributes->textContent;
				}
			}
		}
		$author["contact-id"] = $contact["id"];

		$found = false;

		if ($aliaslink != '') {
			$condition = array("`uid` = ? AND `alias` = ? AND `network` != ?",
					$importer["uid"], $aliaslink, NETWORK_STATUSNET);
			$r = dba::select('contact', array(), $condition, array('limit' => 1));

			if (DBM::is_result($r)) {
				$found = true;
				if ($r['blocked']) {
					$r['id'] = -1;
				}
				$contact = $r;
				$author["contact-id"] = $r["id"];
			}
		}

		if (!$found && ($author["author-link"] != "")) {
			if ($aliaslink == "") {
				$aliaslink = $author["author-link"];
			}

			$condition = array("`uid` = ? AND `nurl` IN (?, ?) AND `network` != ?", $importer["uid"],
					normalise_link($author["author-link"]), normalise_link($aliaslink), NETWORK_STATUSNET);
			$r = dba::select('contact', array(), $condition, array('limit' => 1));

			if (DBM::is_result($r)) {
				$found = true;
				if ($r['blocked']) {
					$r['id'] = -1;
				}
				$contact = $r;
				$author["contact-id"] = $r["id"];
			}
		}

		if (!$found && ($addr != "")) {
			$condition = array("`uid` = ? AND `addr` = ? AND `network` != ?",
					$importer["uid"], $addr, NETWORK_STATUSNET);
			$r = dba::select('contact', array(), $condition, array('limit' => 1));

			if (DBM::is_result($r)) {
				$found = true;
				if ($r['blocked']) {
					$r['id'] = -1;
				}
				$contact = $r;
				$author["contact-id"] = $r["id"];
			}
		}

		$avatarlist = array();
		$avatars = $xpath->query("atom:author/atom:link[@rel='avatar']", $context);
		foreach ($avatars as $avatar) {
			$href = "";
			$width = 0;
			foreach ($avatar->attributes as $attributes) {
				if ($attributes->name == "href") {
					$href = $attributes->textContent;
				}
				if ($attributes->name == "width") {
					$width = $attributes->textContent;
				}
			}
			if ($href != "") {
				$avatarlist[$width] = $href;
			}
		}
		if (count($avatarlist) > 0) {
			krsort($avatarlist);
			$author["author-avatar"] = Probe::fixAvatar(current($avatarlist), $author["author-link"]);
		}

		$displayname = $xpath->evaluate('atom:author/poco:displayName/text()', $context)->item(0)->nodeValue;
		if ($displayname != "") {
			$author["author-name"] = $displayname;
		}

		$author["owner-name"] = $author["author-name"];
		$author["owner-link"] = $author["author-link"];
		$author["owner-avatar"] = $author["author-avatar"];

		// Only update the contacts if it is an OStatus contact
		if ($r && ($r['id'] > 0) && !$onlyfetch && ($contact["network"] == NETWORK_OSTATUS)) {
			// This contact is vital, so we awake it from the dead
			unmark_for_death($contact);

			// Update contact data

			$current = $contact;
			unset($current['name-date']);

			// This query doesn't seem to work
			// $value = $xpath->query("atom:link[@rel='salmon']", $context)->item(0)->nodeValue;
			// if ($value != "")
			//	$contact["notify"] = $value;

			// This query doesn't seem to work as well - I hate these queries
			// $value = $xpath->query("atom:link[@rel='self' and @type='application/atom+xml']", $context)->item(0)->nodeValue;
			// if ($value != "")
			//	$contact["poll"] = $value;

			$contact['url'] = $author["author-link"];
			$contact['nurl'] = normalise_link($contact['url']);

			$value = $xpath->evaluate('atom:author/atom:uri/text()', $context)->item(0)->nodeValue;
			if ($value != "") {
				$contact["alias"] = $value;
			}

			$value = $xpath->evaluate('atom:author/poco:displayName/text()', $context)->item(0)->nodeValue;
			if ($value != "") {
				$contact["name"] = $value;
			}

			$value = $xpath->evaluate('atom:author/poco:preferredUsername/text()', $context)->item(0)->nodeValue;
			if ($value != "") {
				$contact["nick"] = $value;
			}

			$value = $xpath->evaluate('atom:author/poco:note/text()', $context)->item(0)->nodeValue;
			if ($value != "") {
				$contact["about"] = html2bbcode($value);
			}

			$value = $xpath->evaluate('atom:author/poco:address/poco:formatted/text()', $context)->item(0)->nodeValue;
			if ($value != "") {
				$contact["location"] = $value;
			}

			$contact['name-date'] = datetime_convert();

			dba::update('contact', $contact, array('id' => $contact["id"]), $current);

			if (!empty($author["author-avatar"]) && ($author["author-avatar"] != $current['avatar'])) {
				logger("Update profile picture for contact ".$contact["id"], LOGGER_DEBUG);
				update_contact_avatar($author["author-avatar"], $importer["uid"], $contact["id"]);
			}

			// Ensure that we are having this contact (with uid=0)
			$cid = get_contact($aliaslink, 0);

			if ($cid) {
				$fields = array('url', 'nurl', 'name', 'nick', 'alias', 'about', 'location');
				$old_contact = dba::select('contact', $fields, array('id' => $cid), array('limit' => 1));

				// Update it with the current values
				$fields = array('url' => $author["author-link"], 'name' => $contact["name"],
						'nurl' => normalise_link($author["author-link"]),
						'nick' => $contact["nick"], 'alias' => $contact["alias"],
						'about' => $contact["about"], 'location' => $contact["location"],
						'success_update' => datetime_convert(), 'last-update' => datetime_convert());

				dba::update('contact', $fields, array('id' => $cid), $old_contact);

				// Update the avatar
				update_contact_avatar($author["author-avatar"], 0, $cid);
			}

			$contact["generation"] = 2;
			$contact["hide"] = false; // OStatus contacts are never hidden
			$contact["photo"] = $author["author-avatar"];
			$gcid = GContact::update($contact);

			GContact::link($gcid, $contact["uid"], $contact["id"]);
		}

		return $author;
	}

	/**
	 * @brief Fetches author data from a given XML string
	 *
	 * @param string $xml      The XML
	 * @param array  $importer user record of the importing user
	 *
	 * @return array Array of author related entries for the item
	 */
	public static function salmon_author($xml, $importer)
	{
		if ($xml == "") {
			return;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DomXPath($doc);
		$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
		$xpath->registerNamespace('thr', NAMESPACE_THREAD);
		$xpath->registerNamespace('georss', NAMESPACE_GEORSS);
		$xpath->registerNamespace('activity', NAMESPACE_ACTIVITY);
		$xpath->registerNamespace('media', NAMESPACE_MEDIA);
		$xpath->registerNamespace('poco', NAMESPACE_POCO);
		$xpath->registerNamespace('ostatus', NAMESPACE_OSTATUS);
		$xpath->registerNamespace('statusnet', NAMESPACE_STATUSNET);

		$entries = $xpath->query('/atom:entry');

		foreach ($entries as $entry) {
			// fetch the author
			$author = self::fetchauthor($xpath, $entry, $importer, $contact, true);
			return $author;
		}
	}

	/**
	 * @brief Read attributes from element
	 *
	 * @param object $element Element object
	 *
	 * @return array attributes
	 */
	private static function read_attributes($element)
	{
		$attribute = array();

		foreach ($element->attributes as $attributes) {
			$attribute[$attributes->name] = $attributes->textContent;
		}

		return $attribute;
	}

	/**
	 * @brief Imports an XML string containing OStatus elements
	 *
	 * @param string $xml      The XML
	 * @param array  $importer user record of the importing user
	 * @param array  $contact  contact
	 * @param string $hub      Called by reference, returns the fetched hub data
	 */
	public static function import($xml, $importer, &$contact, &$hub)
	{
		self::process($xml, $importer, $contact, $hub);
	}

	/**
	 * @brief Internal feed processing
	 *
	 * @param string  $xml        The XML
	 * @param array   $importer   user record of the importing user
	 * @param array   $contact
	 * @param string  $hub        Called by reference, returns the fetched hub data
	 * @param boolean $stored     Is the post fresh imported or from the database?
	 * @param boolean $initialize Is it the leading post so that data has to be initialized?
	 *
	 * @return boolean Could the XML be processed?
	 */
	private static function process($xml, $importer, &$contact, &$hub, $stored = false, $initialize = true)
	{
		if ($initialize) {
			self::$itemlist = array();
			self::$conv_list = array();
		}

		logger("Import OStatus message", LOGGER_DEBUG);

		if ($xml == "") {
			return false;
		}
		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DomXPath($doc);
		$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
		$xpath->registerNamespace('thr', NAMESPACE_THREAD);
		$xpath->registerNamespace('georss', NAMESPACE_GEORSS);
		$xpath->registerNamespace('activity', NAMESPACE_ACTIVITY);
		$xpath->registerNamespace('media', NAMESPACE_MEDIA);
		$xpath->registerNamespace('poco', NAMESPACE_POCO);
		$xpath->registerNamespace('ostatus', NAMESPACE_OSTATUS);
		$xpath->registerNamespace('statusnet', NAMESPACE_STATUSNET);

		$hub = "";
		$hub_attributes = $xpath->query("/atom:feed/atom:link[@rel='hub']")->item(0)->attributes;
		if (is_object($hub_attributes)) {
			foreach ($hub_attributes as $hub_attribute) {
				if ($hub_attribute->name == "href") {
					$hub = $hub_attribute->textContent;
					logger("Found hub ".$hub, LOGGER_DEBUG);
				}
			}
		}

		$header = array();
		$header["uid"] = $importer["uid"];
		$header["network"] = NETWORK_OSTATUS;
		$header["type"] = "remote";
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["gravity"] = GRAVITY_PARENT;

		$first_child = $doc->firstChild->tagName;

		if ($first_child == "feed") {
			$entries = $xpath->query('/atom:feed/atom:entry');
		} else {
			$entries = $xpath->query('/atom:entry');
		}

		if ($entries->length == 1) {
			// We reformat the XML to make it better readable
			$doc2 = new DOMDocument();
			$doc2->loadXML($xml);
			$doc2->preserveWhiteSpace = false;
			$doc2->formatOutput = true;
			$xml2 = $doc2->saveXML();

			$header["protocol"] = PROTOCOL_OSTATUS_SALMON;
			$header["source"] = $xml2;
		} elseif (!$initialize) {
			return false;
		}

		// Fetch the first author
		$authordata = $xpath->query('//author')->item(0);
		$author = self::fetchauthor($xpath, $authordata, $importer, $contact, $stored);

		$entry = $xpath->query('/atom:entry');

		// Reverse the order of the entries
		$entrylist = array();

		foreach ($entries as $entry) {
			$entrylist[] = $entry;
		}

		foreach (array_reverse($entrylist) as $entry) {
			// fetch the author
			$authorelement = $xpath->query('/atom:entry/atom:author', $entry);

			if ($authorelement->length == 0) {
				$authorelement = $xpath->query('atom:author', $entry);
			}

			if ($authorelement->length > 0) {
				$author = self::fetchauthor($xpath, $entry, $importer, $contact, $stored);
			}

			$value = $xpath->evaluate('atom:author/poco:preferredUsername/text()', $entry)->item(0)->nodeValue;
			if ($value != "") {
				$nickname = $value;
			} else {
				$nickname = $author["author-name"];
			}

			$item = array_merge($header, $author);

			$item["uri"] = $xpath->query('atom:id/text()', $entry)->item(0)->nodeValue;

			$item["verb"] = $xpath->query('activity:verb/text()', $entry)->item(0)->nodeValue;

			// Delete a message
			if (in_array($item["verb"], array('qvitter-delete-notice', ACTIVITY_DELETE, 'delete'))) {
				self::deleteNotice($item);
				continue;
			}

			if (in_array($item["verb"], array(NAMESPACE_OSTATUS."/unfavorite", ACTIVITY_UNFAVORITE))) {
				// Ignore "Unfavorite" message
				logger("Ignore unfavorite message ".print_r($item, true), LOGGER_DEBUG);
				continue;
			}

			// Deletions come with the same uri, so we check for duplicates after processing deletions
			if (dba::exists('item', array('uid' => $importer["uid"], 'uri' => $item["uri"]))) {
				logger('Post with URI '.$item["uri"].' already existed for user '.$importer["uid"].'.', LOGGER_DEBUG);
				continue;
			} else {
				logger('Processing post with URI '.$item["uri"].' for user '.$importer["uid"].'.', LOGGER_DEBUG);
			}

			if ($item["verb"] == ACTIVITY_JOIN) {
				// ignore "Join" messages
				logger("Ignore join message ".print_r($item, true), LOGGER_DEBUG);
				continue;
			}

			if ($item["verb"] == "http://mastodon.social/schema/1.0/block") {
				// ignore mastodon "block" messages
				logger("Ignore block message ".print_r($item, true), LOGGER_DEBUG);
				continue;
			}

			if ($item["verb"] == ACTIVITY_FOLLOW) {
				new_follower($importer, $contact, $item, $nickname);
				continue;
			}

			if ($item["verb"] == NAMESPACE_OSTATUS."/unfollow") {
				lose_follower($importer, $contact, $item, $dummy);
				continue;
			}

			if ($item["verb"] == ACTIVITY_FAVORITE) {
				$orig_uri = $xpath->query("activity:object/atom:id", $entry)->item(0)->nodeValue;
				logger("Favorite ".$orig_uri." ".print_r($item, true));

				$item["verb"] = ACTIVITY_LIKE;
				$item["parent-uri"] = $orig_uri;
				$item["gravity"] = GRAVITY_LIKE;
			}

			// http://activitystrea.ms/schema/1.0/rsvp-yes
			if (!in_array($item["verb"], array(ACTIVITY_POST, ACTIVITY_LIKE, ACTIVITY_SHARE))) {
				logger("Unhandled verb ".$item["verb"]." ".print_r($item, true), LOGGER_DEBUG);
			}

			self::processPost($xpath, $entry, $item, $importer);

			if ($initialize && (count(self::$itemlist) > 0)) {
				if (self::$itemlist[0]['uri'] == self::$itemlist[0]['parent-uri']) {
					// We will import it everytime, when it is started by our contacts
					$valid = !empty(self::$itemlist[0]['contact-id']);
					if (!$valid) {
						// If not, then it depends on this setting
						$valid = !Config::get('system', 'ostatus_full_threads');
					}
					if ($valid) {
						// Never post a thread when the only interaction by our contact was a like
						$valid = false;
						$verbs = array(ACTIVITY_POST, ACTIVITY_SHARE);
						foreach (self::$itemlist as $item) {
							if (!empty($item['contact-id']) && in_array($item['verb'], $verbs)) {
								$valid = true;
							}
						}
					}
				} else {
					// But we will only import complete threads
					$valid = dba::exists('item', array('uid' => $importer["uid"], 'uri' => self::$itemlist[0]['parent-uri']));
				}

				if ($valid) {
					$default_contact = 0;
					$key = count(self::$itemlist);
					for ($key = count(self::$itemlist) - 1; $key >= 0; $key--) {
						if (empty(self::$itemlist[$key]['contact-id'])) {
							self::$itemlist[$key]['contact-id'] = $default_contact;
						} else {
							$default_contact = $item['contact-id'];
						}
					}
					foreach (self::$itemlist as $item) {
						$found = dba::exists('item', array('uid' => $importer["uid"], 'uri' => $item["uri"]));
						if ($found) {
							logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already exists.", LOGGER_DEBUG);
						} elseif ($item['contact-id'] < 0) {
							logger("Item with uri ".$item["uri"]." is from a blocked contact.", LOGGER_DEBUG);
						} else {
							// We are having duplicated entries. Hopefully this solves it.
							if (Lock::set('ostatus_process_item_store')) {
								$ret = item_store($item);
								Lock::remove('ostatus_process_item_store');
								logger("Item with uri ".$item["uri"]." for user ".$importer["uid"].' stored. Return value: '.$ret);
							} else {
								$ret = item_store($item);
								logger("We couldn't lock - but tried to store the item anyway. Return value is ".$ret);
							}
						}
					}
				}
				self::$itemlist = array();
			}
			logger('Processing done for post with URI '.$item["uri"].' for user '.$importer["uid"].'.', LOGGER_DEBUG);
		}
		return true;
	}

	private static function deleteNotice($item)
	{
		$condition = array('uid' => $item['uid'], 'author-link' => $item['author-link'], 'uri' => $item['uri']);
		$deleted = dba::select('item', array('id', 'parent-uri'), $condition, array('limit' => 1));
		if (!DBM::is_result($deleted)) {
			logger('Item from '.$item['author-link'].' with uri '.$item['uri'].' for user '.$item['uid']." wasn't found. We don't delete it. ");
			return;
		}

		// Currently we don't have a central deletion function that we could use in this case. The function "item_drop" doesn't work for that case
		dba::update(
			'item',
			array('deleted' => true, 'title' => '', 'body' => '',
					'edited' => datetime_convert(), 'changed' => datetime_convert()),
			array('id' => $deleted["id"])
		);

		delete_thread($deleted["id"], $deleted["parent-uri"]);

		logger('Deleted item with uri '.$item['uri'].' for user '.$item['uid']);
	}

	/**
	 * @brief Processes the XML for a post
	 *
	 * @param object $xpath    The xpath object
	 * @param object $entry    The xml entry that is processed
	 * @param array  $item     The item array
	 * @param array  $importer user record of the importing user
	 */
	private static function processPost($xpath, $entry, &$item, $importer)
	{
		$item["body"] = html2bbcode($xpath->query('atom:content/text()', $entry)->item(0)->nodeValue);
		$item["object-type"] = $xpath->query('activity:object-type/text()', $entry)->item(0)->nodeValue;
		if (($item["object-type"] == ACTIVITY_OBJ_BOOKMARK) || ($item["object-type"] == ACTIVITY_OBJ_EVENT)) {
			$item["title"] = $xpath->query('atom:title/text()', $entry)->item(0)->nodeValue;
			$item["body"] = $xpath->query('atom:summary/text()', $entry)->item(0)->nodeValue;
		} elseif ($item["object-type"] == ACTIVITY_OBJ_QUESTION) {
			$item["title"] = $xpath->query('atom:title/text()', $entry)->item(0)->nodeValue;
		}

		$item["created"] = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;
		$item["edited"] = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;
		$conversation = $xpath->query('ostatus:conversation/text()', $entry)->item(0)->nodeValue;
		$item['conversation-uri'] = $conversation;

		$conv = $xpath->query('ostatus:conversation', $entry);
		if (is_object($conv->item(0))) {
			foreach ($conv->item(0)->attributes as $attributes) {
				if ($attributes->name == "ref") {
					$item['conversation-uri'] = $attributes->textContent;
				}
				if ($attributes->name == "href") {
					$item['conversation-href'] = $attributes->textContent;
				}
			}
		}

		$related = "";

		$inreplyto = $xpath->query('thr:in-reply-to', $entry);
		if (is_object($inreplyto->item(0))) {
			foreach ($inreplyto->item(0)->attributes as $attributes) {
				if ($attributes->name == "ref") {
					$item["parent-uri"] = $attributes->textContent;
				}
				if ($attributes->name == "href") {
					$related = $attributes->textContent;
				}
			}
		}

		$georsspoint = $xpath->query('georss:point', $entry);
		if (!empty($georsspoint) && ($georsspoint->length > 0)) {
			$item["coord"] = $georsspoint->item(0)->nodeValue;
		}

		$categories = $xpath->query('atom:category', $entry);
		if ($categories) {
			foreach ($categories as $category) {
				foreach ($category->attributes as $attributes) {
					if ($attributes->name == "term") {
						$term = $attributes->textContent;
						if (strlen($item["tag"])) {
							$item["tag"] .= ',';
						}
						$item["tag"] .= "#[url=".System::baseUrl()."/search?tag=".$term."]".$term."[/url]";
					}
				}
			}
		}

		$self = '';
		$add_body = '';

		$links = $xpath->query('atom:link', $entry);
		if ($links) {
			$link_data = self::processLinks($links, $item);
			$self = $link_data['self'];
			$add_body = $link_data['add_body'];
		}

		$repeat_of = "";

		$notice_info = $xpath->query('statusnet:notice_info', $entry);
		if ($notice_info && ($notice_info->length > 0)) {
			foreach ($notice_info->item(0)->attributes as $attributes) {
				if ($attributes->name == "source") {
					$item["app"] = strip_tags($attributes->textContent);
				}
				if ($attributes->name == "repeat_of") {
					$repeat_of = $attributes->textContent;
				}
			}
		}
		// Is it a repeated post?
		if (($repeat_of != "") || ($item["verb"] == ACTIVITY_SHARE)) {
			$link_data = self::processRepeatedItem($xpath, $entry, $item, $importer);
			if (!empty($link_data['add_body'])) {
				$add_body .= $link_data['add_body'];
			}
		}

		$item["body"] .= $add_body;

		// Only add additional data when there is no picture in the post
		if (!strstr($item["body"], '[/img]')) {
			$item["body"] = add_page_info_to_body($item["body"]);
		}

		// Mastodon Content Warning
		if (($item["verb"] == ACTIVITY_POST) && $xpath->evaluate('boolean(atom:summary)', $entry)) {
			$clear_text = $xpath->query('atom:summary/text()', $entry)->item(0)->nodeValue;

			$item["body"] = html2bbcode($clear_text) . '[spoiler]' . $item["body"] . '[/spoiler]';
		}

		if (($self != '') && empty($item['protocol'])) {
			self::fetchSelf($self, $item);
		}

		if (!empty($item["conversation-href"])) {
			self::fetchConversation($item['conversation-href'], $item['conversation-uri']);
		}

		if (isset($item["parent-uri"]) && ($related != '')) {
			if (!dba::exists('item', array('uid' => $importer["uid"], 'uri' => $item['parent-uri']))) {
				self::fetchRelated($related, $item["parent-uri"], $importer);
			} else {
				logger('Reply with URI '.$item["uri"].' already existed for user '.$importer["uid"].'.', LOGGER_DEBUG);
			}

			$item["type"] = 'remote-comment';
			$item["gravity"] = GRAVITY_COMMENT;
		} else {
			$item["parent-uri"] = $item["uri"];
		}

		if (($item['author-link'] != '') && !empty($item['protocol'])) {
			$item = store_conversation($item);
		}

		self::$itemlist[] = $item;
	}

	/**
	 * @brief Fetch the conversation for posts
	 *
	 * @param string $conversation     The link to the conversation
	 * @param string $conversation_uri The conversation in "uri" format
	 */
	private static function fetchConversation($conversation, $conversation_uri)
	{
		// Ensure that we only store a conversation once in a process
		if (isset(self::$conv_list[$conversation])) {
			return;
		}

		self::$conv_list[$conversation] = true;

		$conversation_data = z_fetch_url($conversation, false, $redirects, array('accept_content' => 'application/atom+xml, text/html'));

		if (!$conversation_data['success']) {
			return;
		}

		$xml = '';

		if (stristr($conversation_data['header'], 'Content-Type: application/atom+xml')) {
			$xml = $conversation_data['body'];
		}

		if ($xml == '') {
			$doc = new DOMDocument();
			if (!@$doc->loadHTML($conversation_data['body'])) {
				return;
			}
			$xpath = new DomXPath($doc);

			$links = $xpath->query('//link');
			if ($links) {
				foreach ($links as $link) {
					$attribute = ostatus::read_attributes($link);
					if (($attribute['rel'] == 'alternate') && ($attribute['type'] == 'application/atom+xml')) {
						$file = $attribute['href'];
					}
				}
				if ($file != '') {
					$conversation_atom = z_fetch_url($attribute['href']);

					if ($conversation_atom['success']) {
						$xml = $conversation_atom['body'];
					}
				}
			}
		}

		if ($xml == '') {
			return;
		}

		self::storeConversation($xml, $conversation, $conversation_uri);
	}

	/**
	 * @brief Store a feed in several conversation entries
	 *
	 * @param string $xml              The feed
	 * @param string $conversation     conversation
	 * @param string $conversation_uri conversation uri
	 */
	private static function storeConversation($xml, $conversation = '', $conversation_uri = '')
	{
		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DomXPath($doc);
		$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
		$xpath->registerNamespace('thr', NAMESPACE_THREAD);
		$xpath->registerNamespace('ostatus', NAMESPACE_OSTATUS);

		$entries = $xpath->query('/atom:feed/atom:entry');

		// Now store the entries
		foreach ($entries as $entry) {
			$doc2 = new DOMDocument();
			$doc2->preserveWhiteSpace = false;
			$doc2->formatOutput = true;

			$conv_data = array();

			$conv_data['protocol'] = PROTOCOL_SPLITTED_CONV;
			$conv_data['network'] = NETWORK_OSTATUS;
			$conv_data['uri'] = $xpath->query('atom:id/text()', $entry)->item(0)->nodeValue;

			$inreplyto = $xpath->query('thr:in-reply-to', $entry);
			if (is_object($inreplyto->item(0))) {
				foreach ($inreplyto->item(0)->attributes as $attributes) {
					if ($attributes->name == "ref") {
						$conv_data['reply-to-uri'] = $attributes->textContent;
					}
				}
			}

			$conv = $xpath->query('ostatus:conversation/text()', $entry)->item(0)->nodeValue;
			$conv_data['conversation-uri'] = $conv;

			$conv = $xpath->query('ostatus:conversation', $entry);
			if (is_object($conv->item(0))) {
				foreach ($conv->item(0)->attributes as $attributes) {
					if ($attributes->name == "ref") {
						$conv_data['conversation-uri'] = $attributes->textContent;
					}
					if ($attributes->name == "href") {
						$conv_data['conversation-href'] = $attributes->textContent;
					}
				}
			}

			if ($conversation != '') {
				$conv_data['conversation-uri'] = $conversation;
			}

			if ($conversation_uri != '') {
				$conv_data['conversation-uri'] = $conversation_uri;
			}

			$entry = $doc2->importNode($entry, true);

			$doc2->appendChild($entry);

			$conv_data['source'] = $doc2->saveXML();

			$condition = array('item-uri' => $conv_data['uri'],'protocol' => PROTOCOL_OSTATUS_FEED);
			if (dba::exists('conversation', $condition)) {
				logger('Delete deprecated entry for URI '.$conv_data['uri'], LOGGER_DEBUG);
				dba::delete('conversation', array('item-uri' => $conv_data['uri']));
			}

			logger('Store conversation data for uri '.$conv_data['uri'], LOGGER_DEBUG);
			store_conversation($conv_data);
		}
	}

	/**
	 * @brief Fetch the own post so that it can be stored later
	 * @param array  $item The item array
	 *
	 * We want to store the original data for later processing.
	 * This function is meant for cases where we process a feed with multiple entries.
	 * In that case we need to fetch the single posts here.
	 *
	 * @param string $self The link to the self item
	 */
	private static function fetchSelf($self, &$item)
	{
		$condition = array('`item-uri` = ? AND `protocol` IN (?, ?)', $self, PROTOCOL_DFRN, PROTOCOL_OSTATUS_SALMON);
		if (dba::exists('conversation', $condition)) {
			logger('Conversation '.$item['uri'].' is already stored.', LOGGER_DEBUG);
			return;
		}

		$self_data = z_fetch_url($self);

		if (!$self_data['success']) {
			return;
		}

		// We reformat the XML to make it better readable
		$doc = new DOMDocument();
		$doc->loadXML($self_data['body']);
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$xml = $doc->saveXML();

		$item["protocol"] = PROTOCOL_OSTATUS_SALMON;
		$item["source"] = $xml;

		logger('Conversation '.$item['uri'].' is now fetched.', LOGGER_DEBUG);
	}

	/**
	 * @brief Fetch related posts and processes them
	 *
	 * @param string $related     The link to the related item
	 * @param string $related_uri The related item in "uri" format
	 * @param array  $importer    user record of the importing user
	 */
	private static function fetchRelated($related, $related_uri, $importer)
	{
		$condition = array('`item-uri` = ? AND `protocol` IN (?, ?)', $related_uri, PROTOCOL_DFRN, PROTOCOL_OSTATUS_SALMON);
		$conversation = dba::select('conversation', array('source', 'protocol'), $condition,  array('limit' => 1));
		if (DBM::is_result($conversation)) {
			$stored = true;
			$xml = $conversation['source'];
			if (self::process($xml, $importer, $contact, $hub, $stored, false)) {
				logger('Got valid cached XML for URI '.$related_uri, LOGGER_DEBUG);
				return;
			}
			if ($conversation['protocol'] == PROTOCOL_OSTATUS_SALMON) {
				logger('Delete invalid cached XML for URI '.$related_uri, LOGGER_DEBUG);
				dba::delete('conversation', array('item-uri' => $related_uri));
			}
		}

		$stored = false;
		$related_data = z_fetch_url($related, false, $redirects, array('accept_content' => 'application/atom+xml, text/html'));

		if (!$related_data['success']) {
			return;
		}

		$xml = '';

		if (stristr($related_data['header'], 'Content-Type: application/atom+xml')) {
			logger('Directly fetched XML for URI '.$related_uri, LOGGER_DEBUG);
			$xml = $related_data['body'];
		}

		if ($xml == '') {
			$doc = new DOMDocument();
			if (!@$doc->loadHTML($related_data['body'])) {
				return;
			}
			$xpath = new DomXPath($doc);

			$atom_file = '';

			$links = $xpath->query('//link');
			if ($links) {
				foreach ($links as $link) {
					$attribute = self::read_attributes($link);
					if (($attribute['rel'] == 'alternate') && ($attribute['type'] == 'application/atom+xml')) {
						$atom_file = $attribute['href'];
					}
				}
				if ($atom_file != '') {
					$related_atom = z_fetch_url($atom_file);

					if ($related_atom['success']) {
						logger('Fetched XML for URI '.$related_uri, LOGGER_DEBUG);
						$xml = $related_atom['body'];
					}
				}
			}
		}

		// Workaround for older GNU Social servers
		if (($xml == '') && strstr($related, '/notice/')) {
			$related_atom = z_fetch_url(str_replace('/notice/', '/api/statuses/show/', $related).'.atom');

			if ($related_atom['success']) {
				logger('GNU Social workaround to fetch XML for URI '.$related_uri, LOGGER_DEBUG);
				$xml = $related_atom['body'];
			}
		}

		// Even more worse workaround for GNU Social ;-)
		if ($xml == '') {
			$related_guess = ostatus::convert_href($related_uri);
			$related_atom = z_fetch_url(str_replace('/notice/', '/api/statuses/show/', $related_guess).'.atom');

			if ($related_atom['success']) {
				logger('GNU Social workaround 2 to fetch XML for URI '.$related_uri, LOGGER_DEBUG);
				$xml = $related_atom['body'];
			}
		}

		// Finally we take the data that we fetched from "ostatus:conversation"
		if ($xml == '') {
			$condition = array('item-uri' => $related_uri, 'protocol' => PROTOCOL_SPLITTED_CONV);
			$conversation = dba::select('conversation', array('source'), $condition,  array('limit' => 1));
			if (DBM::is_result($conversation)) {
				$stored = true;
				logger('Got cached XML from conversation for URI '.$related_uri, LOGGER_DEBUG);
				$xml = $conversation['source'];
			}
		}

		if ($xml != '') {
			self::process($xml, $importer, $contact, $hub, $stored, false);
		} else {
			logger("XML couldn't be fetched for URI: ".$related_uri." - href: ".$related, LOGGER_DEBUG);
		}
		return;
	}

	/**
	 * @brief Processes the XML for a repeated post
	 *
	 * @param object $xpath    The xpath object
	 * @param object $entry    The xml entry that is processed
	 * @param array  $item     The item array
	 * @param array  $importer user record of the importing user
	 *
	 * @return array with data from links
	 */
	private static function processRepeatedItem($xpath, $entry, &$item, $importer)
	{
		$activityobjects = $xpath->query('activity:object', $entry)->item(0);

		if (!is_object($activityobjects)) {
			return array();
		}

		$link_data = array();

		$orig_uri = $xpath->query('atom:id/text()', $activityobjects)->item(0)->nodeValue;

		$links = $xpath->query("atom:link", $activityobjects);
		if ($links) {
			$link_data = self::processLinks($links, $item);
		}

		$orig_body = $xpath->query('atom:content/text()', $activityobjects)->item(0)->nodeValue;
		$orig_created = $xpath->query('atom:published/text()', $activityobjects)->item(0)->nodeValue;
		$orig_edited = $xpath->query('atom:updated/text()', $activityobjects)->item(0)->nodeValue;

		$orig_contact = $contact;
		$orig_author = self::fetchauthor($xpath, $activityobjects, $importer, $orig_contact, false);

		$item["author-name"] = $orig_author["author-name"];
		$item["author-link"] = $orig_author["author-link"];
		$item["author-avatar"] = $orig_author["author-avatar"];

		$item["body"] = html2bbcode($orig_body);
		$item["created"] = $orig_created;
		$item["edited"] = $orig_edited;

		$item["uri"] = $orig_uri;

		$item["verb"] = $xpath->query('activity:verb/text()', $activityobjects)->item(0)->nodeValue;

		$item["object-type"] = $xpath->query('activity:object-type/text()', $activityobjects)->item(0)->nodeValue;

		$inreplyto = $xpath->query('thr:in-reply-to', $activityobjects);
		if (is_object($inreplyto->item(0))) {
			foreach ($inreplyto->item(0)->attributes as $attributes) {
				if ($attributes->name == "ref") {
					$item["parent-uri"] = $attributes->textContent;
				}
			}
		}

		return $link_data;
	}

	/**
	 * @brief Processes links in the XML
	 *
	 * @param object $links The xml data that contain links
	 * @param array  $item  The item array
	 *
	 * @return array with data from the links
	 */
	private static function processLinks($links, &$item)
	{
		$link_data = array('add_body' => '', 'self' => '');

		foreach ($links as $link) {
			$attribute = self::read_attributes($link);

			if (($attribute['rel'] != "") && ($attribute['href'] != "")) {
				switch ($attribute['rel']) {
					case "alternate":
						$item["plink"] = $attribute['href'];
						if (($item["object-type"] == ACTIVITY_OBJ_QUESTION)
							|| ($item["object-type"] == ACTIVITY_OBJ_EVENT)
						) {
							$item["body"] .= add_page_info($attribute['href']);
						}
						break;
					case "ostatus:conversation":
						$link_data['conversation'] = $attribute['href'];
						$item['conversation-href'] = $link_data['conversation'];
						if (!isset($item['conversation-uri'])) {
							$item['conversation-uri'] = $item['conversation-href'];
						}
						break;
					case "enclosure":
						$filetype = strtolower(substr($attribute['type'], 0, strpos($attribute['type'], '/')));
						if ($filetype == 'image') {
							$link_data['add_body'] .= "\n[img]".$attribute['href'].'[/img]';
						} else {
							if (strlen($item["attach"])) {
								$item["attach"] .= ',';
							}
							if (!isset($attribute['length'])) {
								$attribute['length'] = "0";
							}
							$item["attach"] .= '[attach]href="'.$attribute['href'].'" length="'.$attribute['length'].'" type="'.$attribute['type'].'" title="'.$attribute['title'].'"[/attach]';
						}
						break;
					case "related":
						if ($item["object-type"] != ACTIVITY_OBJ_BOOKMARK) {
							if (!isset($item["parent-uri"])) {
								$item["parent-uri"] = $attribute['href'];
							}
							$link_data['related'] = $attribute['href'];
						} else {
							$item["body"] .= add_page_info($attribute['href']);
						}
						break;
					case "self":
						if ($item["plink"] == '') {
							$item["plink"] = $attribute['href'];
						}
						$link_data['self'] = $attribute['href'];
						break;
				}
			}
		}
		return $link_data;
	}

	/**
	 * @brief Create an url out of an uri
	 *
	 * @param string $href URI in the format "parameter1:parameter1:..."
	 *
	 * @return string URL in the format http(s)://....
	 */
	public static function convert_href($href)
	{
		$elements = explode(":", $href);

		if ((count($elements) <= 2) || ($elements[0] != "tag")) {
			return $href;
		}

		$server = explode(",", $elements[1]);
		$conversation = explode("=", $elements[2]);

		if ((count($elements) == 4) && ($elements[2] == "post")) {
			return "http://".$server[0]."/notice/".$elements[3];
		}

		if ((count($conversation) != 2) || ($conversation[1] =="")) {
			return $href;
		}
		if ($elements[3] == "objectType=thread") {
			return "http://".$server[0]."/conversation/".$conversation[1];
		} else {
			return "http://".$server[0]."/notice/".$conversation[1];
		}
		return $href;
	}

	/**
	 * @brief Checks if the current post is a reshare
	 *
	 * @param array $item The item array of thw post
	 *
	 * @return string The guid if the post is a reshare
	 */
	private static function get_reshared_guid($item)
	{
		$body = trim($item["body"]);

		// Skip if it isn't a pure repeated messages
		// Does it start with a share?
		if (strpos($body, "[share") > 0) {
			return "";
		}

		// Does it end with a share?
		if (strlen($body) > (strrpos($body, "[/share]") + 8)) {
			return "";
		}

		$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "$1", $body);
		// Skip if there is no shared message in there
		if ($body == $attributes) {
			return false;
		}

		$guid = "";
		preg_match("/guid='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "") {
			$guid = $matches[1];
		}

		preg_match('/guid="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "") {
			$guid = $matches[1];
		}

		return $guid;
	}

	/**
	 * @brief Cleans the body of a post if it contains picture links
	 *
	 * @param string $body The body
	 *
	 * @return string The cleaned body
	 */
	private static function format_picture_post($body)
	{
		$siteinfo = get_attached_data($body);

		if (($siteinfo["type"] == "photo")) {
			if (isset($siteinfo["preview"])) {
				$preview = $siteinfo["preview"];
			} else {
				$preview = $siteinfo["image"];
			}

			// Is it a remote picture? Then make a smaller preview here
			$preview = proxy_url($preview, false, PROXY_SIZE_SMALL);

			// Is it a local picture? Then make it smaller here
			$preview = str_replace(array("-0.jpg", "-0.png"), array("-2.jpg", "-2.png"), $preview);
			$preview = str_replace(array("-1.jpg", "-1.png"), array("-2.jpg", "-2.png"), $preview);

			if (isset($siteinfo["url"])) {
				$url = $siteinfo["url"];
			} else {
				$url = $siteinfo["image"];
			}

			$body = trim($siteinfo["text"])." [url]".$url."[/url]\n[img]".$preview."[/img]";
		}

		return $body;
	}

	/**
	 * @brief Adds the header elements to the XML document
	 *
	 * @param object $doc   XML document
	 * @param array  $owner Contact data of the poster
	 *
	 * @return object header root element
	 */
	private static function add_header($doc, $owner)
	{
		$a = get_app();

		$root = $doc->createElementNS(NAMESPACE_ATOM1, 'feed');
		$doc->appendChild($root);

		$root->setAttribute("xmlns:thr", NAMESPACE_THREAD);
		$root->setAttribute("xmlns:georss", NAMESPACE_GEORSS);
		$root->setAttribute("xmlns:activity", NAMESPACE_ACTIVITY);
		$root->setAttribute("xmlns:media", NAMESPACE_MEDIA);
		$root->setAttribute("xmlns:poco", NAMESPACE_POCO);
		$root->setAttribute("xmlns:ostatus", NAMESPACE_OSTATUS);
		$root->setAttribute("xmlns:statusnet", NAMESPACE_STATUSNET);
		$root->setAttribute("xmlns:mastodon", NAMESPACE_MASTODON);

		$attributes = array("uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION);
		XML::add_element($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);
		XML::add_element($doc, $root, "id", System::baseUrl()."/profile/".$owner["nick"]);
		XML::add_element($doc, $root, "title", sprintf("%s timeline", $owner["name"]));
		XML::add_element($doc, $root, "subtitle", sprintf("Updates from %s on %s", $owner["name"], $a->config["sitename"]));
		XML::add_element($doc, $root, "logo", $owner["photo"]);
		XML::add_element($doc, $root, "updated", datetime_convert("UTC", "UTC", "now", ATOM_TIME));

		$author = self::add_author($doc, $owner);
		$root->appendChild($author);

		$attributes = array("href" => $owner["url"], "rel" => "alternate", "type" => "text/html");
		XML::add_element($doc, $root, "link", "", $attributes);

		/// @TODO We have to find out what this is
		/// $attributes = array("href" => System::baseUrl()."/sup",
		///		"rel" => "http://api.friendfeed.com/2008/03#sup",
		///		"type" => "application/json");
		/// XML::add_element($doc, $root, "link", "", $attributes);

		self::hublinks($doc, $root, $owner["nick"]);

		$attributes = array("href" => System::baseUrl()."/salmon/".$owner["nick"], "rel" => "salmon");
		XML::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("href" => System::baseUrl()."/salmon/".$owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-replies");
		XML::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("href" => System::baseUrl()."/salmon/".$owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-mention");
		XML::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("href" => System::baseUrl()."/api/statuses/user_timeline/".$owner["nick"].".atom",
				"rel" => "self", "type" => "application/atom+xml");
		XML::add_element($doc, $root, "link", "", $attributes);

		return $root;
	}

	/**
	 * @brief Add the link to the push hubs to the XML document
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root element where the hub links are added
	 * @param object $nick nick
	 */
	public static function hublinks($doc, $root, $nick)
	{
		$h = System::baseUrl() . '/pubsubhubbub/'.$nick;
		XML::add_element($doc, $root, "link", "", array("href" => $h, "rel" => "hub"));
	}

	/**
	 * @brief Adds attachement data to the XML document
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root element where the hub links are added
	 * @param array  $item Data of the item that is to be posted
	 */
	private static function get_attachment($doc, $root, $item)
	{
		$o = "";
		$siteinfo = get_attached_data($item["body"]);

		switch ($siteinfo["type"]) {
			case 'photo':
				$imgdata = get_photo_info($siteinfo["image"]);
				$attributes = array("rel" => "enclosure",
						"href" => $siteinfo["image"],
						"type" => $imgdata["mime"],
						"length" => intval($imgdata["size"]));
				XML::add_element($doc, $root, "link", "", $attributes);
				break;
			case 'video':
				$attributes = array("rel" => "enclosure",
						"href" => $siteinfo["url"],
						"type" => "text/html; charset=UTF-8",
						"length" => "",
						"title" => $siteinfo["title"]);
				XML::add_element($doc, $root, "link", "", $attributes);
				break;
			default:
				break;
		}

		if (!Config::get('system', 'ostatus_not_attach_preview') && ($siteinfo["type"] != "photo") && isset($siteinfo["image"])) {
			$imgdata = get_photo_info($siteinfo["image"]);
			$attributes = array("rel" => "enclosure",
					"href" => $siteinfo["image"],
					"type" => $imgdata["mime"],
					"length" => intval($imgdata["size"]));

			XML::add_element($doc, $root, "link", "", $attributes);
		}

		$arr = explode('[/attach],', $item['attach']);
		if (count($arr)) {
			foreach ($arr as $r) {
				$matches = false;
				$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"|', $r, $matches);
				if ($cnt) {
					$attributes = array("rel" => "enclosure",
							"href" => $matches[1],
							"type" => $matches[3]);

					if (intval($matches[2])) {
						$attributes["length"] = intval($matches[2]);
					}
					if (trim($matches[4]) != "") {
						$attributes["title"] = trim($matches[4]);
					}
					XML::add_element($doc, $root, "link", "", $attributes);
				}
			}
		}
	}

	/**
	 * @brief Adds the author element to the XML document
	 *
	 * @param object $doc   XML document
	 * @param array  $owner Contact data of the poster
	 *
	 * @return object author element
	 */
	private static function add_author($doc, $owner)
	{
		$r = q("SELECT `homepage`, `publish` FROM `profile` WHERE `uid` = %d AND `is-default` LIMIT 1", intval($owner["uid"]));
		if (DBM::is_result($r)) {
			$profile = $r[0];
		}
		$author = $doc->createElement("author");
		XML::add_element($doc, $author, "id", $owner["url"]);
		XML::add_element($doc, $author, "activity:object-type", ACTIVITY_OBJ_PERSON);
		XML::add_element($doc, $author, "uri", $owner["url"]);
		XML::add_element($doc, $author, "name", $owner["nick"]);
		XML::add_element($doc, $author, "email", $owner["addr"]);
		XML::add_element($doc, $author, "summary", bbcode($owner["about"], false, false, 7));

		$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $owner["url"]);
		XML::add_element($doc, $author, "link", "", $attributes);

		$attributes = array(
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 175,
				"media:height" => 175,
				"href" => $owner["photo"]);
		XML::add_element($doc, $author, "link", "", $attributes);

		if (isset($owner["thumb"])) {
			$attributes = array(
					"rel" => "avatar",
					"type" => "image/jpeg", // To-Do?
					"media:width" => 80,
					"media:height" => 80,
					"href" => $owner["thumb"]);
			XML::add_element($doc, $author, "link", "", $attributes);
		}

		XML::add_element($doc, $author, "poco:preferredUsername", $owner["nick"]);
		XML::add_element($doc, $author, "poco:displayName", $owner["name"]);
		XML::add_element($doc, $author, "poco:note", bbcode($owner["about"], false, false, 7));

		if (trim($owner["location"]) != "") {
			$element = $doc->createElement("poco:address");
			XML::add_element($doc, $element, "poco:formatted", $owner["location"]);
			$author->appendChild($element);
		}

		if (trim($profile["homepage"]) != "") {
			$urls = $doc->createElement("poco:urls");
			XML::add_element($doc, $urls, "poco:type", "homepage");
			XML::add_element($doc, $urls, "poco:value", $profile["homepage"]);
			XML::add_element($doc, $urls, "poco:primary", "true");
			$author->appendChild($urls);
		}

		if (count($profile)) {
			XML::add_element($doc, $author, "followers", "", array("url" => System::baseUrl()."/viewcontacts/".$owner["nick"]));
			XML::add_element($doc, $author, "statusnet:profile_info", "", array("local_id" => $owner["uid"]));
		}

		if ($profile["publish"]) {
			XML::add_element($doc, $author, "mastodon:scope", "public");
		}
		return $author;
	}

	/**
	 * @TODO Picture attachments should look like this:
	 *	<a href="https://status.pirati.ca/attachment/572819" title="https://status.pirati.ca/file/heluecht-20151202T222602-rd3u49p.gif"
	 *	class="attachment thumbnail" id="attachment-572819" rel="nofollow external">https://status.pirati.ca/attachment/572819</a>
	 */

	/**
	 * @brief Returns the given activity if present - otherwise returns the "post" activity
	 *
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return string activity
	 */
	private static function construct_verb($item)
	{
		if ($item['verb']) {
			return $item['verb'];
		}

		return ACTIVITY_POST;
	}

	/**
	 * @brief Returns the given object type if present - otherwise returns the "note" object type
	 *
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return string Object type
	 */
	private static function construct_objecttype($item)
	{
		if (in_array($item['object-type'], array(ACTIVITY_OBJ_NOTE, ACTIVITY_OBJ_COMMENT)))
			return $item['object-type'];
		return ACTIVITY_OBJ_NOTE;
	}

	/**
	 * @brief Adds an entry element to the XML document
	 *
	 * @param object $doc      XML document
	 * @param array  $item     Data of the item that is to be posted
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $toplevel optional default false
	 *
	 * @return object Entry element
	 */
	private static function entry($doc, $item, $owner, $toplevel = false)
	{
		$repeated_guid = self::get_reshared_guid($item);
		if ($repeated_guid != "") {
			$xml = self::reshare_entry($doc, $item, $owner, $repeated_guid, $toplevel);
		}

		if ($xml) {
			return $xml;
		}

		if ($item["verb"] == ACTIVITY_LIKE) {
			return self::like_entry($doc, $item, $owner, $toplevel);
		} elseif (in_array($item["verb"], array(ACTIVITY_FOLLOW, NAMESPACE_OSTATUS."/unfollow"))) {
			return self::follow_entry($doc, $item, $owner, $toplevel);
		} else {
			return self::note_entry($doc, $item, $owner, $toplevel);
		}
	}

	/**
	 * @brief Adds a source entry to the XML document
	 *
	 * @param object $doc     XML document
	 * @param array  $contact Array of the contact that is added
	 *
	 * @return object Source element
	 */
	private static function source_entry($doc, $contact)
	{
		$source = $doc->createElement("source");
		XML::add_element($doc, $source, "id", $contact["poll"]);
		XML::add_element($doc, $source, "title", $contact["name"]);
		XML::add_element($doc, $source, "link", "", array("rel" => "alternate", "type" => "text/html", "href" => $contact["alias"]));
		XML::add_element($doc, $source, "link", "", array("rel" => "self", "type" => "application/atom+xml", "href" => $contact["poll"]));
		XML::add_element($doc, $source, "icon", $contact["photo"]);
		XML::add_element($doc, $source, "updated", datetime_convert("UTC", "UTC", $contact["success_update"]."+00:00", ATOM_TIME));

		return $source;
	}

	/**
	 * @brief Fetches contact data from the contact or the gcontact table
	 *
	 * @param string $url   URL of the contact
	 * @param array  $owner Contact data of the poster
	 *
	 * @return array Contact array
	 */
	private static function contact_entry($url, $owner)
	{
		$r = q(
			"SELECT * FROM `contact` WHERE `nurl` = '%s' AND `uid` IN (0, %d) ORDER BY `uid` DESC LIMIT 1",
			dbesc(normalise_link($url)),
			intval($owner["uid"])
		);
		if (DBM::is_result($r)) {
			$contact = $r[0];
			$contact["uid"] = -1;
		}

		if (!DBM::is_result($r)) {
			$r = q(
				"SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
				dbesc(normalise_link($url))
			);
			if (DBM::is_result($r)) {
				$contact = $r[0];
				$contact["uid"] = -1;
				$contact["success_update"] = $contact["updated"];
			}
		}

		if (!DBM::is_result($r)) {
			$contact = owner;
		}

		if (!isset($contact["poll"])) {
			$data = Probe::uri($url);
			$contact["poll"] = $data["poll"];

			if (!$contact["alias"]) {
				$contact["alias"] = $data["alias"];
			}
		}

		if (!isset($contact["alias"])) {
			$contact["alias"] = $contact["url"];
		}

		return $contact;
	}

	/**
	 * @brief Adds an entry element with reshared content
	 *
	 * @param object $doc           XML document
	 * @param array  $item          Data of the item that is to be posted
	 * @param array  $owner         Contact data of the poster
	 * @param        $repeated_guid
	 * @param bool   $toplevel      Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private static function reshare_entry($doc, $item, $owner, $repeated_guid, $toplevel)
	{
		if (($item["id"] != $item["parent"]) && (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entry_header($doc, $entry, $owner, $toplevel);

		$r = q(
			"SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' AND NOT `private` AND `network` IN ('%s', '%s', '%s') LIMIT 1",
			intval($owner["uid"]),
			dbesc($repeated_guid),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS)
		);
		if (DBM::is_result($r)) {
			$repeated_item = $r[0];
		} else {
			return false;
		}
		$contact = self::contact_entry($repeated_item['author-link'], $owner);

		$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

		$title = $owner["nick"]." repeated a notice by ".$contact["nick"];

		self::entry_content($doc, $entry, $item, $owner, $title, ACTIVITY_SHARE, false);

		$as_object = $doc->createElement("activity:object");

		XML::add_element($doc, $as_object, "activity:object-type", NAMESPACE_ACTIVITY_SCHEMA."activity");

		self::entry_content($doc, $as_object, $repeated_item, $owner, "", "", false);

		$author = self::add_author($doc, $contact);
		$as_object->appendChild($author);

		$as_object2 = $doc->createElement("activity:object");

		XML::add_element($doc, $as_object2, "activity:object-type", self::construct_objecttype($repeated_item));

		$title = sprintf("New comment by %s", $contact["nick"]);

		self::entry_content($doc, $as_object2, $repeated_item, $owner, $title);

		$as_object->appendChild($as_object2);

		self::entry_footer($doc, $as_object, $item, $owner, false);

		$source = self::source_entry($doc, $contact);

		$as_object->appendChild($source);

		$entry->appendChild($as_object);

		self::entry_footer($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * @brief Adds an entry element with a "like"
	 *
	 * @param object $doc      XML document
	 * @param array  $item     Data of the item that is to be posted
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element with "like"
	 */
	private static function like_entry($doc, $item, $owner, $toplevel)
	{
		if (($item["id"] != $item["parent"]) && (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entry_header($doc, $entry, $owner, $toplevel);

		$verb = NAMESPACE_ACTIVITY_SCHEMA."favorite";
		self::entry_content($doc, $entry, $item, $owner, "Favorite", $verb, false);

		$as_object = $doc->createElement("activity:object");

		$parent = q(
			"SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d",
			dbesc($item["thr-parent"]),
			intval($item["uid"])
		);
		$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

		XML::add_element($doc, $as_object, "activity:object-type", self::construct_objecttype($parent[0]));

		self::entry_content($doc, $as_object, $parent[0], $owner, "New entry");

		$entry->appendChild($as_object);

		self::entry_footer($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * @brief Adds the person object element to the XML document
	 *
	 * @param object $doc     XML document
	 * @param array  $owner   Contact data of the poster
	 * @param array  $contact Contact data of the target
	 *
	 * @return object author element
	 */
	private static function add_person_object($doc, $owner, $contact)
	{
		$object = $doc->createElement("activity:object");
		XML::add_element($doc, $object, "activity:object-type", ACTIVITY_OBJ_PERSON);

		if ($contact['network'] == NETWORK_PHANTOM) {
			XML::add_element($doc, $object, "id", $contact['url']);
			return $object;
		}

		XML::add_element($doc, $object, "id", $contact["alias"]);
		XML::add_element($doc, $object, "title", $contact["nick"]);

		$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $contact["url"]);
		XML::add_element($doc, $object, "link", "", $attributes);

		$attributes = array(
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 175,
				"media:height" => 175,
				"href" => $contact["photo"]);
		XML::add_element($doc, $object, "link", "", $attributes);

		XML::add_element($doc, $object, "poco:preferredUsername", $contact["nick"]);
		XML::add_element($doc, $object, "poco:displayName", $contact["name"]);

		if (trim($contact["location"]) != "") {
			$element = $doc->createElement("poco:address");
			XML::add_element($doc, $element, "poco:formatted", $contact["location"]);
			$object->appendChild($element);
		}

		return $object;
	}

	/**
	 * @brief Adds a follow/unfollow entry element
	 *
	 * @param object $doc      XML document
	 * @param array  $item     Data of the follow/unfollow message
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private static function follow_entry($doc, $item, $owner, $toplevel)
	{
		$item["id"] = $item["parent"] = 0;
		$item["created"] = $item["edited"] = date("c");
		$item["private"] = true;

		$contact = Probe::uri($item['follow']);

		if ($contact['alias'] == '') {
			$contact['alias'] = $contact["url"];
		} else {
			$item['follow'] = $contact['alias'];
		}

		$r = q(
			"SELECT `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s'",
			intval($owner['uid']),
			dbesc(normalise_link($contact["url"]))
		);

		if (DBM::is_result($r)) {
			$connect_id = $r[0]['id'];
		} else {
			$connect_id = 0;
		}

		if ($item['verb'] == ACTIVITY_FOLLOW) {
			$message = t('%s is now following %s.');
			$title = t('following');
			$action = "subscription";
		} else {
			$message = t('%s stopped following %s.');
			$title = t('stopped following');
			$action = "unfollow";
		}

		$item["uri"] = $item['parent-uri'] = $item['thr-parent']
				= 'tag:'.get_app()->get_hostname().
				','.date('Y-m-d').':'.$action.':'.$owner['uid'].
				':person:'.$connect_id.':'.$item['created'];

		$item["body"] = sprintf($message, $owner["nick"], $contact["nick"]);

		self::entry_header($doc, $entry, $owner, $toplevel);

		self::entry_content($doc, $entry, $item, $owner, $title);

		$object = self::add_person_object($doc, $owner, $contact);
		$entry->appendChild($object);

		self::entry_footer($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * @brief Adds a regular entry element
	 *
	 * @param object $doc      XML document
	 * @param array  $item     Data of the item that is to be posted
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private static function note_entry($doc, $item, $owner, $toplevel)
	{
		if (($item["id"] != $item["parent"]) && (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entry_header($doc, $entry, $owner, $toplevel);

		XML::add_element($doc, $entry, "activity:object-type", ACTIVITY_OBJ_NOTE);

		self::entry_content($doc, $entry, $item, $owner, $title);

		self::entry_footer($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * @brief Adds a header element to the XML document
	 *
	 * @param object $doc      XML document
	 * @param object $entry    The entry element where the elements are added
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return string The title for the element
	 */
	private static function entry_header($doc, &$entry, $owner, $toplevel)
	{
		/// @todo Check if this title stuff is really needed (I guess not)
		if (!$toplevel) {
			$entry = $doc->createElement("entry");
			$title = sprintf("New note by %s", $owner["nick"]);
		} else {
			$entry = $doc->createElementNS(NAMESPACE_ATOM1, "entry");

			$entry->setAttribute("xmlns:thr", NAMESPACE_THREAD);
			$entry->setAttribute("xmlns:georss", NAMESPACE_GEORSS);
			$entry->setAttribute("xmlns:activity", NAMESPACE_ACTIVITY);
			$entry->setAttribute("xmlns:media", NAMESPACE_MEDIA);
			$entry->setAttribute("xmlns:poco", NAMESPACE_POCO);
			$entry->setAttribute("xmlns:ostatus", NAMESPACE_OSTATUS);
			$entry->setAttribute("xmlns:statusnet", NAMESPACE_STATUSNET);
			$entry->setAttribute("xmlns:mastodon", NAMESPACE_MASTODON);

			$author = self::add_author($doc, $owner);
			$entry->appendChild($author);

			$title = sprintf("New comment by %s", $owner["nick"]);
		}
		return $title;
	}

	/**
	 * @brief Adds elements to the XML document
	 *
	 * @param object $doc      XML document
	 * @param object $entry    Entry element where the content is added
	 * @param array  $item     Data of the item that is to be posted
	 * @param array  $owner    Contact data of the poster
	 * @param string $title    Title for the post
	 * @param string $verb     The activity verb
	 * @param bool   $complete Add the "status_net" element?
	 */
	private static function entry_content($doc, $entry, $item, $owner, $title, $verb = "", $complete = true)
	{
		if ($verb == "") {
			$verb = self::construct_verb($item);
		}

		XML::add_element($doc, $entry, "id", $item["uri"]);
		XML::add_element($doc, $entry, "title", $title);

		$body = self::format_picture_post($item['body']);

		if ($item['title'] != "") {
			$body = "[b]".$item['title']."[/b]\n\n".$body;
		}

		$body = bbcode($body, false, false, 7);

		XML::add_element($doc, $entry, "content", $body, array("type" => "html"));

		XML::add_element($doc, $entry, "link", "", array("rel" => "alternate", "type" => "text/html",
			"href" => System::baseUrl()."/display/".$item["guid"])
		);

		if ($complete && ($item["id"] > 0)) {
			XML::add_element($doc, $entry, "status_net", "", array("notice_id" => $item["id"]));
		}

		XML::add_element($doc, $entry, "activity:verb", $verb);

		XML::add_element($doc, $entry, "published", datetime_convert("UTC", "UTC", $item["created"]."+00:00", ATOM_TIME));
		XML::add_element($doc, $entry, "updated", datetime_convert("UTC", "UTC", $item["edited"]."+00:00", ATOM_TIME));
	}

	/**
	 * @brief Adds the elements at the foot of an entry to the XML document
	 *
	 * @param object $doc      XML document
	 * @param object $entry    The entry element where the elements are added
	 * @param array  $item     Data of the item that is to be posted
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $complete default true
	 */
	private static function entry_footer($doc, $entry, $item, $owner, $complete = true)
	{
		$mentioned = array();

		if (($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
			$parent = q("SELECT `guid`, `author-link`, `owner-link` FROM `item` WHERE `id` = %d", intval($item["parent"]));
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

			$thrparent = q(
				"SELECT `guid`, `author-link`, `owner-link`, `plink` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
				intval($owner["uid"]),
				dbesc($parent_item)
			);
			if ($thrparent) {
				$mentioned[$thrparent[0]["author-link"]] = $thrparent[0]["author-link"];
				$mentioned[$thrparent[0]["owner-link"]] = $thrparent[0]["owner-link"];
				$parent_plink = $thrparent[0]["plink"];
			} else {
				$mentioned[$parent[0]["author-link"]] = $parent[0]["author-link"];
				$mentioned[$parent[0]["owner-link"]] = $parent[0]["owner-link"];
				$parent_plink = System::baseUrl()."/display/".$parent[0]["guid"];
			}

			$attributes = array(
					"ref" => $parent_item,
					"href" => $parent_plink);
			XML::add_element($doc, $entry, "thr:in-reply-to", "", $attributes);

			$attributes = array(
					"rel" => "related",
					"href" => $parent_plink);
			XML::add_element($doc, $entry, "link", "", $attributes);
		}

		if (intval($item["parent"]) > 0) {
			$conversation_href = System::baseUrl()."/display/".$owner["nick"]."/".$item["parent"];
			$conversation_uri = $conversation_href;

			if (isset($parent_item)) {
				$r = dba::fetch_first("SELECT `conversation-uri`, `conversation-href` FROM `conversation` WHERE `item-uri` = ?", $parent_item);
				if (DBM::is_result($r)) {
					if ($r['conversation-uri'] != '') {
						$conversation_uri = $r['conversation-uri'];
					}
					if ($r['conversation-href'] != '') {
						$conversation_href = $r['conversation-href'];
					}
				}
			}

			XML::add_element($doc, $entry, "link", "", array("rel" => "ostatus:conversation", "href" => $conversation_href));

			$attributes = array(
					"href" => $conversation_href,
					"local_id" => $item["parent"],
					"ref" => $conversation_uri);

			XML::add_element($doc, $entry, "ostatus:conversation", $conversation_uri, $attributes);
		}

		$tags = item_getfeedtags($item);

		if (count($tags)) {
			foreach ($tags as $t) {
				if ($t[0] == "@") {
					$mentioned[$t[1]] = $t[1];
				}
			}
		}

		// Make sure that mentions are accepted (GNU Social has problems with mixing HTTP and HTTPS)
		$newmentions = array();
		foreach ($mentioned as $mention) {
			$newmentions[str_replace("http://", "https://", $mention)] = str_replace("http://", "https://", $mention);
			$newmentions[str_replace("https://", "http://", $mention)] = str_replace("https://", "http://", $mention);
		}
		$mentioned = $newmentions;

		foreach ($mentioned as $mention) {
			$r = q(
				"SELECT `forum`, `prv` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s'",
				intval($owner["uid"]),
				dbesc(normalise_link($mention))
			);
			if ($r[0]["forum"] || $r[0]["prv"]) {
				XML::add_element($doc, $entry, "link", "",
					array(
						"rel" => "mentioned",
						"ostatus:object-type" => ACTIVITY_OBJ_GROUP,
						"href" => $mention)
				);
			} else {
				XML::add_element($doc, $entry, "link", "",
					array(
						"rel" => "mentioned",
						"ostatus:object-type" => ACTIVITY_OBJ_PERSON,
						"href" => $mention)
				);
			}
		}

		if (!$item["private"]) {
			XML::add_element($doc, $entry, "link", "", array("rel" => "ostatus:attention",
									"href" => "http://activityschema.org/collection/public"));
			XML::add_element($doc, $entry, "link", "", array("rel" => "mentioned",
									"ostatus:object-type" => "http://activitystrea.ms/schema/1.0/collection",
									"href" => "http://activityschema.org/collection/public"));
			XML::add_element($doc, $entry, "mastodon:scope", "public");
		}

		if (count($tags)) {
			foreach ($tags as $t) {
				if ($t[0] != "@") {
					XML::add_element($doc, $entry, "category", "", array("term" => $t[2]));
				}
			}
		}

		self::get_attachment($doc, $entry, $item);

		if ($complete && ($item["id"] > 0)) {
			$app = $item["app"];
			if ($app == "") {
				$app = "web";
			}

			$attributes = array("local_id" => $item["id"], "source" => $app);

			if (isset($parent["id"])) {
				$attributes["repeat_of"] = $parent["id"];
			}

			if ($item["coord"] != "") {
				XML::add_element($doc, $entry, "georss:point", $item["coord"]);
			}

			XML::add_element($doc, $entry, "statusnet:notice_info", "", $attributes);
		}
	}

	/**
	 * @brief Creates the XML feed for a given nickname
	 *
	 * @param object  $a           The application class
	 * @param string  $owner_nick  Nickname of the feed owner
	 * @param string  $last_update Date of the last update
	 * @param integer $max_items   Number of maximum items to fetch
	 *
	 * @return string XML feed
	 */
	public static function feed(App $a, $owner_nick, &$last_update, $max_items = 300)
	{
		$stamp = microtime(true);

		$cachekey = "ostatus:feed:".$owner_nick.":".$last_update;

		$previous_created = $last_update;

		$result = Cache::get($cachekey);
		if (!is_null($result)) {
			logger('Feed duration: '.number_format(microtime(true) - $stamp, 3).' - '.$owner_nick.' - '.$previous_created.' (cached)', LOGGER_DEBUG);
			$last_update = $result['last_update'];
			return $result['feed'];
		}

		$r = q(
			"SELECT `contact`.*, `user`.`nickname`, `user`.`timezone`, `user`.`page-flags`
				FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`self` AND `user`.`nickname` = '%s' LIMIT 1",
			dbesc($owner_nick)
		);
		if (!DBM::is_result($r)) {
			return;
		}

		$owner = $r[0];

		if (!strlen($last_update)) {
			$last_update = 'now -30 days';
		}

		$check_date = datetime_convert('UTC', 'UTC', $last_update, 'Y-m-d H:i:s');
		$authorid = get_contact($owner["url"], 0);

		$items = q(
			"SELECT `item`.*, `item`.`id` AS `item_id` FROM `item` USE INDEX (`uid_contactid_created`)
				STRAIGHT_JOIN `thread` ON `thread`.`iid` = `item`.`parent`
				WHERE `item`.`uid` = %d AND `item`.`contact-id` = %d AND
					`item`.`author-id` = %d AND `item`.`created` > '%s' AND
					NOT `item`.`deleted` AND NOT `item`.`private` AND
					`thread`.`network` IN ('%s', '%s')
				ORDER BY `item`.`created` DESC LIMIT %d",
			intval($owner["uid"]), intval($owner["id"]),
			intval($authorid), dbesc($check_date),
			dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN), intval($max_items)
		);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner);

		foreach ($items as $item) {
			if (Config::get('system', 'ostatus_debug')) {
				$item['body'] .= '🍼';
			}
			$entry = self::entry($doc, $item, $owner);
			$root->appendChild($entry);

			if ($last_update < $item['created']) {
				$last_update = $item['created'];
			}
		}

		$feeddata = trim($doc->saveXML());

		$msg = array('feed' => $feeddata, 'last_update' => $last_update);
		Cache::set($cachekey, $msg, CACHE_QUARTER_HOUR);

		logger('Feed duration: '.number_format(microtime(true) - $stamp, 3).' - '.$owner_nick.' - '.$previous_created, LOGGER_DEBUG);

		return $feeddata;
	}

	/**
	 * @brief Creates the XML for a salmon message
	 *
	 * @param array $item  Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 *
	 * @return string XML for the salmon
	 */
	public static function salmon($item, $owner)
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		if (Config::get('system', 'ostatus_debug')) {
			$item['body'] .= '🐟';
		}

		$entry = self::entry($doc, $item, $owner, true);

		$doc->appendChild($entry);

		return trim($doc->saveXML());
	}
}
