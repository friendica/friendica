<?php
/**
 * @file src/Protocol/OStatus.php
 */
namespace Friendica\Protocol;

use DOMDocument;
use DOMXPath;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Lock;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\GContact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\XML;

require_once 'include/dba.php';
require_once 'include/items.php';
require_once 'mod/share.php';
require_once 'include/enotify.php';
require_once 'include/api.php';
require_once 'mod/proxy.php';

/**
 * @brief This class contain functions for the OStatus protocol
 */
class OStatus
{
	private static $itemlist;
	private static $conv_list = [];

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
	private static function fetchAuthor(DOMXPath $xpath, $context, array $importer, array &$contact = null, $onlyfetch)
	{
		$author = [];
		$author["author-link"] = XML::getFirstNodeValue($xpath, 'atom:author/atom:uri/text()', $context);
		$author["author-name"] = XML::getFirstNodeValue($xpath, 'atom:author/atom:name/text()', $context);
		$addr = XML::getFirstNodeValue($xpath, 'atom:author/atom:email/text()', $context);

		$aliaslink = $author["author-link"];

		$alternate_item = $xpath->query("atom:author/atom:link[@rel='alternate']", $context)->item(0);
		if (is_object($alternate_item)) {
			foreach ($alternate_item->attributes as $attributes) {
				if (($attributes->name == "href") && ($attributes->textContent != "")) {
					$author["author-link"] = $attributes->textContent;
				}
			}
		}
		$author["author-id"] = Contact::getIdForURL($author["author-link"]);

		$author["contact-id"] = $contact["id"];

		$contact = null;
		if ($aliaslink != '') {
			$condition = ["`uid` = ? AND `alias` = ? AND `network` != ? AND `rel` IN (?, ?)",
					$importer["uid"], $aliaslink, NETWORK_STATUSNET,
					CONTACT_IS_SHARING, CONTACT_IS_FRIEND];
			$contact = DBA::selectFirst('contact', [], $condition);
		}

		if (!DBM::is_result($contact) && $author["author-link"] != '') {
			if ($aliaslink == "") {
				$aliaslink = $author["author-link"];
			}

			$condition = ["`uid` = ? AND `nurl` IN (?, ?) AND `network` != ? AND `rel` IN (?, ?)",
					$importer["uid"], normalise_link($author["author-link"]), normalise_link($aliaslink),
					NETWORK_STATUSNET, CONTACT_IS_SHARING, CONTACT_IS_FRIEND];
			$contact = DBA::selectFirst('contact', [], $condition);
		}

		if (!DBM::is_result($contact) && ($addr != '')) {
			$condition = ["`uid` = ? AND `addr` = ? AND `network` != ? AND `rel` IN (?, ?)",
					$importer["uid"], $addr, NETWORK_STATUSNET,
					CONTACT_IS_SHARING, CONTACT_IS_FRIEND];
			$contact = DBA::selectFirst('contact', [], $condition);
		}

		if (DBM::is_result($contact)) {
			if ($contact['blocked']) {
				$contact['id'] = -1;
			}
			$author["contact-id"] = $contact["id"];
		}

		$avatarlist = [];
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

		$displayname = XML::getFirstNodeValue($xpath, 'atom:author/poco:displayName/text()', $context);
		if ($displayname != "") {
			$author["author-name"] = $displayname;
		}

		$author["owner-id"] = $author["author-id"];

		// Only update the contacts if it is an OStatus contact
		if (DBM::is_result($contact) && ($contact['id'] > 0) && !$onlyfetch && ($contact["network"] == NETWORK_OSTATUS)) {

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

			$value = XML::getFirstNodeValue($xpath, 'atom:author/atom:uri/text()', $context);
			if ($value != "") {
				$contact["alias"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:displayName/text()', $context);
			if ($value != "") {
				$contact["name"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:preferredUsername/text()', $context);
			if ($value != "") {
				$contact["nick"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:note/text()', $context);
			if ($value != "") {
				$contact["about"] = HTML::toBBCode($value);
			}

			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:address/poco:formatted/text()', $context);
			if ($value != "") {
				$contact["location"] = $value;
			}

			$contact['name-date'] = DateTimeFormat::utcNow();

			DBA::update('contact', $contact, ['id' => $contact["id"]], $current);

			if (!empty($author["author-avatar"]) && ($author["author-avatar"] != $current['avatar'])) {
				logger("Update profile picture for contact ".$contact["id"], LOGGER_DEBUG);
				Contact::updateAvatar($author["author-avatar"], $importer["uid"], $contact["id"]);
			}

			// Ensure that we are having this contact (with uid=0)
			$cid = Contact::getIdForURL($aliaslink, 0, true);

			if ($cid) {
				$fields = ['url', 'nurl', 'name', 'nick', 'alias', 'about', 'location'];
				$old_contact = DBA::selectFirst('contact', $fields, ['id' => $cid]);

				// Update it with the current values
				$fields = ['url' => $author["author-link"], 'name' => $contact["name"],
						'nurl' => normalise_link($author["author-link"]),
						'nick' => $contact["nick"], 'alias' => $contact["alias"],
						'about' => $contact["about"], 'location' => $contact["location"],
						'success_update' => DateTimeFormat::utcNow(), 'last-update' => DateTimeFormat::utcNow()];

				DBA::update('contact', $fields, ['id' => $cid], $old_contact);

				// Update the avatar
				if (!empty($author["author-avatar"])) {
					Contact::updateAvatar($author["author-avatar"], 0, $cid);
				}
			}

			$contact["generation"] = 2;
			$contact["hide"] = false; // OStatus contacts are never hidden
			if (!empty($author["author-avatar"])) {
				$contact["photo"] = $author["author-avatar"];
			}
			$gcid = GContact::update($contact);

			GContact::link($gcid, $contact["uid"], $contact["id"]);
		} else {
			$contact = null;
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
	public static function salmonAuthor($xml, array $importer)
	{
		if ($xml == "") {
			return;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
		$xpath->registerNamespace('thr', NAMESPACE_THREAD);
		$xpath->registerNamespace('georss', NAMESPACE_GEORSS);
		$xpath->registerNamespace('activity', NAMESPACE_ACTIVITY);
		$xpath->registerNamespace('media', NAMESPACE_MEDIA);
		$xpath->registerNamespace('poco', NAMESPACE_POCO);
		$xpath->registerNamespace('ostatus', NAMESPACE_OSTATUS);
		$xpath->registerNamespace('statusnet', NAMESPACE_STATUSNET);

		$contact = ["id" => 0];

		// Fetch the first author
		$authordata = $xpath->query('//author')->item(0);
		$author = self::fetchAuthor($xpath, $authordata, $importer, $contact, true);
		return $author;
	}

	/**
	 * @brief Read attributes from element
	 *
	 * @param object $element Element object
	 *
	 * @return array attributes
	 */
	private static function readAttributes($element)
	{
		$attribute = [];

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
	 * @return void
	 */
	public static function import($xml, array $importer, array &$contact, &$hub)
	{
		self::process($xml, $importer, $contact, $hub);
	}

	/**
	 * @brief Internal feed processing
	 *
	 * @param string  $xml        The XML
	 * @param array   $importer   user record of the importing user
	 * @param array   $contact    contact
	 * @param string  $hub        Called by reference, returns the fetched hub data
	 * @param boolean $stored     Is the post fresh imported or from the database?
	 * @param boolean $initialize Is it the leading post so that data has to be initialized?
	 *
	 * @return boolean Could the XML be processed?
	 */
	private static function process($xml, array $importer, array &$contact = null, &$hub, $stored = false, $initialize = true)
	{
		if ($initialize) {
			self::$itemlist = [];
			self::$conv_list = [];
		}

		logger("Import OStatus message", LOGGER_DEBUG);

		if ($xml == "") {
			return false;
		}
		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
		$xpath->registerNamespace('thr', NAMESPACE_THREAD);
		$xpath->registerNamespace('georss', NAMESPACE_GEORSS);
		$xpath->registerNamespace('activity', NAMESPACE_ACTIVITY);
		$xpath->registerNamespace('media', NAMESPACE_MEDIA);
		$xpath->registerNamespace('poco', NAMESPACE_POCO);
		$xpath->registerNamespace('ostatus', NAMESPACE_OSTATUS);
		$xpath->registerNamespace('statusnet', NAMESPACE_STATUSNET);

		$hub = "";
		$hub_items = $xpath->query("/atom:feed/atom:link[@rel='hub']")->item(0);
		if (is_object($hub_items)) {
			$hub_attributes = $hub_items->attributes;
			if (is_object($hub_attributes)) {
				foreach ($hub_attributes as $hub_attribute) {
					if ($hub_attribute->name == "href") {
						$hub = $hub_attribute->textContent;
						logger("Found hub ".$hub, LOGGER_DEBUG);
					}
				}
			}
		}

		$header = [];
		$header["uid"] = $importer["uid"];
		$header["network"] = NETWORK_OSTATUS;
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["gravity"] = GRAVITY_COMMENT;

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
		$author = self::fetchAuthor($xpath, $authordata, $importer, $contact, $stored);

		$entry = $xpath->query('/atom:entry');

		// Reverse the order of the entries
		$entrylist = [];

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
				$author = self::fetchAuthor($xpath, $entry, $importer, $contact, $stored);
			}

			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:preferredUsername/text()', $entry);
			if ($value != "") {
				$nickname = $value;
			} else {
				$nickname = $author["author-name"];
			}

			$item = array_merge($header, $author);

			$item["uri"] = XML::getFirstNodeValue($xpath, 'atom:id/text()', $entry);

			$item["verb"] = XML::getFirstNodeValue($xpath, 'activity:verb/text()', $entry);

			// Delete a message
			if (in_array($item["verb"], ['qvitter-delete-notice', ACTIVITY_DELETE, 'delete'])) {
				self::deleteNotice($item);
				continue;
			}

			if (in_array($item["verb"], [NAMESPACE_OSTATUS."/unfavorite", ACTIVITY_UNFAVORITE])) {
				// Ignore "Unfavorite" message
				logger("Ignore unfavorite message ".print_r($item, true), LOGGER_DEBUG);
				continue;
			}

			// Deletions come with the same uri, so we check for duplicates after processing deletions
			if (Item::exists(['uid' => $importer["uid"], 'uri' => $item["uri"]])) {
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
				Contact::addRelationship($importer, $contact, $item, $nickname);
				continue;
			}

			if ($item["verb"] == NAMESPACE_OSTATUS."/unfollow") {
				$dummy = null;
				Contact::removeFollower($importer, $contact, $item, $dummy);
				continue;
			}

			if ($item["verb"] == ACTIVITY_FAVORITE) {
				$orig_uri = $xpath->query("activity:object/atom:id", $entry)->item(0)->nodeValue;
				logger("Favorite ".$orig_uri." ".print_r($item, true));

				$item["verb"] = ACTIVITY_LIKE;
				$item["parent-uri"] = $orig_uri;
				$item["gravity"] = GRAVITY_ACTIVITY;
				$item["object-type"] = ACTIVITY_OBJ_NOTE;
			}

			// http://activitystrea.ms/schema/1.0/rsvp-yes
			if (!in_array($item["verb"], [ACTIVITY_POST, ACTIVITY_LIKE, ACTIVITY_SHARE])) {
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
						if ($valid) {
							logger("Item with uri ".self::$itemlist[0]['uri']." will be imported due to the system settings.", LOGGER_DEBUG);
						}
					} else {
						logger("Item with uri ".self::$itemlist[0]['uri']." belongs to a contact (".self::$itemlist[0]['contact-id']."). It will be imported.", LOGGER_DEBUG);
					}
					if ($valid) {
						// Never post a thread when the only interaction by our contact was a like
						$valid = false;
						$verbs = [ACTIVITY_POST, ACTIVITY_SHARE];
						foreach (self::$itemlist as $item) {
							if (!empty($item['contact-id']) && in_array($item['verb'], $verbs)) {
								$valid = true;
							}
						}
						if ($valid) {
							logger("Item with uri ".self::$itemlist[0]['uri']." will be imported since the thread contains posts or shares.", LOGGER_DEBUG);
						}
					}
				} else {
					// But we will only import complete threads
					$valid = Item::exists(['uid' => $importer["uid"], 'uri' => self::$itemlist[0]['parent-uri']]);
					if ($valid) {
						logger("Item with uri ".self::$itemlist[0]["uri"]." belongs to parent ".self::$itemlist[0]['parent-uri']." of user ".$importer["uid"].". It will be imported.", LOGGER_DEBUG);
					}
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
						$found = Item::exists(['uid' => $importer["uid"], 'uri' => $item["uri"]]);
						if ($found) {
							logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already exists.", LOGGER_DEBUG);
						} elseif ($item['contact-id'] < 0) {
							logger("Item with uri ".$item["uri"]." is from a blocked contact.", LOGGER_DEBUG);
						} else {
							// We are having duplicated entries. Hopefully this solves it.
							if (Lock::acquire('ostatus_process_item_insert')) {
								$ret = Item::insert($item);
								Lock::release('ostatus_process_item_insert');
								logger("Item with uri ".$item["uri"]." for user ".$importer["uid"].' stored. Return value: '.$ret);
							} else {
								$ret = Item::insert($item);
								logger("We couldn't lock - but tried to store the item anyway. Return value is ".$ret);
							}
						}
					}
				}
				self::$itemlist = [];
			}
			logger('Processing done for post with URI '.$item["uri"].' for user '.$importer["uid"].'.', LOGGER_DEBUG);
		}
		return true;
	}

	/**
	 * Removes notice item from database
	 * @param array $item item
	 * @return void
	 */
	private static function deleteNotice(array $item)
	{
		$condition = ['uid' => $item['uid'], 'author-id' => $item['author-id'], 'uri' => $item['uri']];
		if (!Item::exists($condition)) {
			logger('Item from '.$item['author-link'].' with uri '.$item['uri'].' for user '.$item['uid']." wasn't found. We don't delete it.");
			return;
		}

		Item::delete($condition);

		logger('Deleted item with uri '.$item['uri'].' for user '.$item['uid']);
	}

	/**
	 * @brief Processes the XML for a post
	 *
	 * @param object $xpath    The xpath object
	 * @param object $entry    The xml entry that is processed
	 * @param array  $item     The item array
	 * @param array  $importer user record of the importing user
	 * @return void
	 */
	private static function processPost(DOMXPath $xpath, $entry, array &$item, array $importer)
	{
		$item["body"] = HTML::toBBCode(XML::getFirstNodeValue($xpath, 'atom:content/text()', $entry));
		$item["object-type"] = XML::getFirstNodeValue($xpath, 'activity:object-type/text()', $entry);
		if (($item["object-type"] == ACTIVITY_OBJ_BOOKMARK) || ($item["object-type"] == ACTIVITY_OBJ_EVENT)) {
			$item["title"] = XML::getFirstNodeValue($xpath, 'atom:title/text()', $entry);
			$item["body"] = XML::getFirstNodeValue($xpath, 'atom:summary/text()', $entry);
		} elseif ($item["object-type"] == ACTIVITY_OBJ_QUESTION) {
			$item["title"] = XML::getFirstNodeValue($xpath, 'atom:title/text()', $entry);
		}

		$item["created"] = XML::getFirstNodeValue($xpath, 'atom:published/text()', $entry);
		$item["edited"] = XML::getFirstNodeValue($xpath, 'atom:updated/text()', $entry);
		$item['conversation-uri'] = XML::getFirstNodeValue($xpath, 'ostatus:conversation/text()', $entry);

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
						if (!empty($item["tag"])) {
							$item["tag"] .= ',';
						} else {
							$item["tag"] = '';
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
			$clear_text = XML::getFirstNodeValue($xpath, 'atom:summary/text()', $entry);
			if (!empty($clear_text)) {
				$item['content-warning'] = HTML::toBBCode($clear_text);
			}
		}

		if (($self != '') && empty($item['protocol'])) {
			self::fetchSelf($self, $item);
		}

		if (!empty($item["conversation-href"])) {
			self::fetchConversation($item['conversation-href'], $item['conversation-uri']);
		}

		if (isset($item["parent-uri"])) {
			if (!Item::exists(['uid' => $importer["uid"], 'uri' => $item['parent-uri']])) {
				if ($related != '') {
					self::fetchRelated($related, $item["parent-uri"], $importer);
				}
			} else {
				logger('Reply with URI '.$item["uri"].' already existed for user '.$importer["uid"].'.', LOGGER_DEBUG);
			}
		} else {
			$item["parent-uri"] = $item["uri"];
			$item["gravity"] = GRAVITY_PARENT;
		}

		if (($item['author-link'] != '') && !empty($item['protocol'])) {
			$item = Conversation::insert($item);
		}

		self::$itemlist[] = $item;
	}

	/**
	 * @brief Fetch the conversation for posts
	 *
	 * @param string $conversation     The link to the conversation
	 * @param string $conversation_uri The conversation in "uri" format
	 * @return void
	 */
	private static function fetchConversation($conversation, $conversation_uri)
	{
		// Ensure that we only store a conversation once in a process
		if (isset(self::$conv_list[$conversation])) {
			return;
		}

		self::$conv_list[$conversation] = true;

		$conversation_data = Network::curl($conversation, false, $redirects, ['accept_content' => 'application/atom+xml, text/html']);

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
			$xpath = new DOMXPath($doc);

			$links = $xpath->query('//link');
			if ($links) {
				$file = '';
				foreach ($links as $link) {
					$attribute = self::readAttributes($link);
					if (($attribute['rel'] == 'alternate') && ($attribute['type'] == 'application/atom+xml')) {
						$file = $attribute['href'];
					}
				}
				if ($file != '') {
					$conversation_atom = Network::curl($attribute['href']);

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
	 * @return void
	 */
	private static function storeConversation($xml, $conversation = '', $conversation_uri = '')
	{
		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
		$xpath->registerNamespace('thr', NAMESPACE_THREAD);
		$xpath->registerNamespace('ostatus', NAMESPACE_OSTATUS);

		$entries = $xpath->query('/atom:feed/atom:entry');

		// Now store the entries
		foreach ($entries as $entry) {
			$doc2 = new DOMDocument();
			$doc2->preserveWhiteSpace = false;
			$doc2->formatOutput = true;

			$conv_data = [];

			$conv_data['protocol'] = PROTOCOL_SPLITTED_CONV;
			$conv_data['network'] = NETWORK_OSTATUS;
			$conv_data['uri'] = XML::getFirstNodeValue($xpath, 'atom:id/text()', $entry);

			$inreplyto = $xpath->query('thr:in-reply-to', $entry);
			if (is_object($inreplyto->item(0))) {
				foreach ($inreplyto->item(0)->attributes as $attributes) {
					if ($attributes->name == "ref") {
						$conv_data['reply-to-uri'] = $attributes->textContent;
					}
				}
			}

			$conv_data['conversation-uri'] = XML::getFirstNodeValue($xpath, 'ostatus:conversation/text()', $entry);

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

			$condition = ['item-uri' => $conv_data['uri'],'protocol' => PROTOCOL_OSTATUS_FEED];
			if (DBA::exists('conversation', $condition)) {
				logger('Delete deprecated entry for URI '.$conv_data['uri'], LOGGER_DEBUG);
				DBA::delete('conversation', ['item-uri' => $conv_data['uri']]);
			}

			logger('Store conversation data for uri '.$conv_data['uri'], LOGGER_DEBUG);
			Conversation::insert($conv_data);
		}
	}

	/**
	 * @brief Fetch the own post so that it can be stored later
	 *
	 * We want to store the original data for later processing.
	 * This function is meant for cases where we process a feed with multiple entries.
	 * In that case we need to fetch the single posts here.
	 *
	 * @param string $self The link to the self item
	 * @param array  $item The item array
	 * @return void
	 */
	private static function fetchSelf($self, array &$item)
	{
		$condition = ['`item-uri` = ? AND `protocol` IN (?, ?)', $self, PROTOCOL_DFRN, PROTOCOL_OSTATUS_SALMON];
		if (DBA::exists('conversation', $condition)) {
			logger('Conversation '.$item['uri'].' is already stored.', LOGGER_DEBUG);
			return;
		}

		$self_data = Network::curl($self);

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
	 * @return void
	 */
	private static function fetchRelated($related, $related_uri, $importer)
	{
		$condition = ['`item-uri` = ? AND `protocol` IN (?, ?)', $related_uri, PROTOCOL_DFRN, PROTOCOL_OSTATUS_SALMON];
		$conversation = DBA::selectFirst('conversation', ['source', 'protocol'], $condition);
		if (DBM::is_result($conversation)) {
			$stored = true;
			$xml = $conversation['source'];
			if (self::process($xml, $importer, $contact, $hub, $stored, false)) {
				logger('Got valid cached XML for URI '.$related_uri, LOGGER_DEBUG);
				return;
			}
			if ($conversation['protocol'] == PROTOCOL_OSTATUS_SALMON) {
				logger('Delete invalid cached XML for URI '.$related_uri, LOGGER_DEBUG);
				DBA::delete('conversation', ['item-uri' => $related_uri]);
			}
		}

		$stored = false;
		$related_data = Network::curl($related, false, $redirects, ['accept_content' => 'application/atom+xml, text/html']);

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
			$xpath = new DOMXPath($doc);

			$atom_file = '';

			$links = $xpath->query('//link');
			if ($links) {
				foreach ($links as $link) {
					$attribute = self::readAttributes($link);
					if (($attribute['rel'] == 'alternate') && ($attribute['type'] == 'application/atom+xml')) {
						$atom_file = $attribute['href'];
					}
				}
				if ($atom_file != '') {
					$related_atom = Network::curl($atom_file);

					if ($related_atom['success']) {
						logger('Fetched XML for URI '.$related_uri, LOGGER_DEBUG);
						$xml = $related_atom['body'];
					}
				}
			}
		}

		// Workaround for older GNU Social servers
		if (($xml == '') && strstr($related, '/notice/')) {
			$related_atom = Network::curl(str_replace('/notice/', '/api/statuses/show/', $related).'.atom');

			if ($related_atom['success']) {
				logger('GNU Social workaround to fetch XML for URI '.$related_uri, LOGGER_DEBUG);
				$xml = $related_atom['body'];
			}
		}

		// Even more worse workaround for GNU Social ;-)
		if ($xml == '') {
			$related_guess = OStatus::convertHref($related_uri);
			$related_atom = Network::curl(str_replace('/notice/', '/api/statuses/show/', $related_guess).'.atom');

			if ($related_atom['success']) {
				logger('GNU Social workaround 2 to fetch XML for URI '.$related_uri, LOGGER_DEBUG);
				$xml = $related_atom['body'];
			}
		}

		// Finally we take the data that we fetched from "ostatus:conversation"
		if ($xml == '') {
			$condition = ['item-uri' => $related_uri, 'protocol' => PROTOCOL_SPLITTED_CONV];
			$conversation = DBA::selectFirst('conversation', ['source'], $condition);
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
	private static function processRepeatedItem(DOMXPath $xpath, $entry, array &$item, array $importer)
	{
		$activityobjects = $xpath->query('activity:object', $entry)->item(0);

		if (!is_object($activityobjects)) {
			return [];
		}

		$link_data = [];

		$orig_uri = XML::getFirstNodeValue($xpath, 'atom:id/text()', $activityobjects);

		$links = $xpath->query("atom:link", $activityobjects);
		if ($links) {
			$link_data = self::processLinks($links, $item);
		}

		$orig_body = XML::getFirstNodeValue($xpath, 'atom:content/text()', $activityobjects);
		$orig_created = XML::getFirstNodeValue($xpath, 'atom:published/text()', $activityobjects);
		$orig_edited = XML::getFirstNodeValue($xpath, 'atom:updated/text()', $activityobjects);

		$orig_author = self::fetchAuthor($xpath, $activityobjects, $importer, $dummy, false);

		$item["author-name"] = $orig_author["author-name"];
		$item["author-link"] = $orig_author["author-link"];
		$item["author-id"] = $orig_author["author-id"];

		$item["body"] = HTML::toBBCode($orig_body);
		$item["created"] = $orig_created;
		$item["edited"] = $orig_edited;

		$item["uri"] = $orig_uri;

		$item["verb"] = XML::getFirstNodeValue($xpath, 'activity:verb/text()', $activityobjects);

		$item["object-type"] = XML::getFirstNodeValue($xpath, 'activity:object-type/text()', $activityobjects);

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
	private static function processLinks($links, array &$item)
	{
		$link_data = ['add_body' => '', 'self' => ''];

		foreach ($links as $link) {
			$attribute = self::readAttributes($link);

			if (!empty($attribute['rel']) && !empty($attribute['href'])) {
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
							if (!empty($item["attach"])) {
								$item["attach"] .= ',';
							} else {
								$item["attach"] = '';
							}
							if (!isset($attribute['length'])) {
								$attribute['length'] = "0";
							}
							$item["attach"] .= '[attach]href="'.$attribute['href'].'" length="'.$attribute['length'].'" type="'.$attribute['type'].'" title="'.defaults($attribute, 'title', '').'"[/attach]';
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
						if (empty($item["plink"])) {
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
	public static function convertHref($href)
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
	private static function getResharedGuid(array $item)
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
		if (!empty($matches[1])) {
			$guid = $matches[1];
		}

		preg_match('/guid="(.*?)"/ism', $attributes, $matches);
		if (!empty($matches[1])) {
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
	private static function formatPicturePost($body)
	{
		$siteinfo = BBCode::getAttachedData($body);

		if (($siteinfo["type"] == "photo")) {
			if (isset($siteinfo["preview"])) {
				$preview = $siteinfo["preview"];
			} else {
				$preview = $siteinfo["image"];
			}

			// Is it a remote picture? Then make a smaller preview here
			$preview = proxy_url($preview, false, PROXY_SIZE_SMALL);

			// Is it a local picture? Then make it smaller here
			$preview = str_replace(["-0.jpg", "-0.png"], ["-2.jpg", "-2.png"], $preview);
			$preview = str_replace(["-1.jpg", "-1.png"], ["-2.jpg", "-2.png"], $preview);

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
	 * @param object $doc    XML document
	 * @param array  $owner  Contact data of the poster
	 * @param string $filter The related feed filter (activity, posts or comments)
	 *
	 * @return object header root element
	 */
	private static function addHeader(DOMDocument $doc, array $owner, $filter)
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

		$title = '';
		switch ($filter) {
			case 'activity': $title = L10n::t('%s\'s timeline', $owner['name']); break;
			case 'posts'   : $title = L10n::t('%s\'s posts'   , $owner['name']); break;
			case 'comments': $title = L10n::t('%s\'s comments', $owner['name']); break;
		}

		$attributes = ["uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION . "-" . DB_UPDATE_VERSION];
		XML::addElement($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);
		XML::addElement($doc, $root, "id", System::baseUrl() . "/profile/" . $owner["nick"]);
		XML::addElement($doc, $root, "title", $title);
		XML::addElement($doc, $root, "subtitle", sprintf("Updates from %s on %s", $owner["name"], Config::get('config', 'sitename')));
		XML::addElement($doc, $root, "logo", $owner["photo"]);
		XML::addElement($doc, $root, "updated", DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner);
		$root->appendChild($author);

		$attributes = ["href" => $owner["url"], "rel" => "alternate", "type" => "text/html"];
		XML::addElement($doc, $root, "link", "", $attributes);

		/// @TODO We have to find out what this is
		/// $attributes = array("href" => System::baseUrl()."/sup",
		///		"rel" => "http://api.friendfeed.com/2008/03#sup",
		///		"type" => "application/json");
		/// XML::addElement($doc, $root, "link", "", $attributes);

		self::hublinks($doc, $root, $owner["nick"]);

		$attributes = ["href" => System::baseUrl() . "/salmon/" . $owner["nick"], "rel" => "salmon"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["href" => System::baseUrl() . "/salmon/" . $owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-replies"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["href" => System::baseUrl() . "/salmon/" . $owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-mention"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["href" => System::baseUrl() . "/api/statuses/user_timeline/" . $owner["nick"] . ".atom",
			"rel" => "self", "type" => "application/atom+xml"];
		XML::addElement($doc, $root, "link", "", $attributes);

		if ($owner['account-type'] == ACCOUNT_TYPE_COMMUNITY) {
			$condition = ['uid' => $owner['uid'], 'self' => false, 'pending' => false,
					'archive' => false, 'hidden' => false, 'blocked' => false];
			$members = DBA::count('contact', $condition);
			XML::addElement($doc, $root, "statusnet:group_info", "", ["member_count" => $members]);
		}

		return $root;
	}

	/**
	 * @brief Add the link to the push hubs to the XML document
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root element where the hub links are added
	 * @param object $nick nick
	 * @return void
	 */
	public static function hublinks(DOMDocument $doc, $root, $nick)
	{
		$h = System::baseUrl() . '/pubsubhubbub/'.$nick;
		XML::addElement($doc, $root, "link", "", ["href" => $h, "rel" => "hub"]);
	}

	/**
	 * @brief Adds attachment data to the XML document
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root element where the hub links are added
	 * @param array  $item Data of the item that is to be posted
	 * @return void
	 */
	private static function getAttachment(DOMDocument $doc, $root, $item)
	{
		$o = "";
		$siteinfo = BBCode::getAttachedData($item["body"]);

		switch ($siteinfo["type"]) {
			case 'photo':
				$imgdata = Image::getInfoFromURL($siteinfo["image"]);
				if ($imgdata) {
					$attributes = ["rel" => "enclosure",
							"href" => $siteinfo["image"],
							"type" => $imgdata["mime"],
							"length" => intval($imgdata["size"])];
					XML::addElement($doc, $root, "link", "", $attributes);
				}
				break;
			case 'video':
				$attributes = ["rel" => "enclosure",
						"href" => $siteinfo["url"],
						"type" => "text/html; charset=UTF-8",
						"length" => "",
						"title" => $siteinfo["title"]];
				XML::addElement($doc, $root, "link", "", $attributes);
				break;
			default:
				break;
		}

		if (!Config::get('system', 'ostatus_not_attach_preview') && ($siteinfo["type"] != "photo") && isset($siteinfo["image"])) {
			$imgdata = Image::getInfoFromURL($siteinfo["image"]);
			if ($imgdata) {
				$attributes = ["rel" => "enclosure",
						"href" => $siteinfo["image"],
						"type" => $imgdata["mime"],
						"length" => intval($imgdata["size"])];

				XML::addElement($doc, $root, "link", "", $attributes);
			}
		}

		$arr = explode('[/attach],', $item['attach']);
		if (count($arr)) {
			foreach ($arr as $r) {
				$matches = false;
				$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"|', $r, $matches);
				if ($cnt) {
					$attributes = ["rel" => "enclosure",
							"href" => $matches[1],
							"type" => $matches[3]];

					if (intval($matches[2])) {
						$attributes["length"] = intval($matches[2]);
					}
					if (trim($matches[4]) != "") {
						$attributes["title"] = trim($matches[4]);
					}
					XML::addElement($doc, $root, "link", "", $attributes);
				}
			}
		}
	}

	/**
	 * @brief Adds the author element to the XML document
	 *
	 * @param object $doc   XML document
	 * @param array  $owner Contact data of the poster
	 * @param bool   $show_profile Whether to show profile
	 *
	 * @return object author element
	 */
	private static function addAuthor(DOMDocument $doc, array $owner, $show_profile = true)
	{
		$profile = DBA::selectFirst('profile', ['homepage', 'publish'], ['uid' => $owner['uid'], 'is-default' => true]);
		$author = $doc->createElement("author");
		XML::addElement($doc, $author, "id", $owner["url"]);
		if ($owner['account-type'] == ACCOUNT_TYPE_COMMUNITY) {
			XML::addElement($doc, $author, "activity:object-type", ACTIVITY_OBJ_GROUP);
		} else {
			XML::addElement($doc, $author, "activity:object-type", ACTIVITY_OBJ_PERSON);
		}
		XML::addElement($doc, $author, "uri", $owner["url"]);
		XML::addElement($doc, $author, "name", $owner["nick"]);
		XML::addElement($doc, $author, "email", $owner["addr"]);
		if ($show_profile) {
			XML::addElement($doc, $author, "summary", BBCode::convert($owner["about"], false, 7));
		}

		$attributes = ["rel" => "alternate", "type" => "text/html", "href" => $owner["url"]];
		XML::addElement($doc, $author, "link", "", $attributes);

		$attributes = [
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 175,
				"media:height" => 175,
				"href" => $owner["photo"]];
		XML::addElement($doc, $author, "link", "", $attributes);

		if (isset($owner["thumb"])) {
			$attributes = [
					"rel" => "avatar",
					"type" => "image/jpeg", // To-Do?
					"media:width" => 80,
					"media:height" => 80,
					"href" => $owner["thumb"]];
			XML::addElement($doc, $author, "link", "", $attributes);
		}

		XML::addElement($doc, $author, "poco:preferredUsername", $owner["nick"]);
		XML::addElement($doc, $author, "poco:displayName", $owner["name"]);
		if ($show_profile) {
			XML::addElement($doc, $author, "poco:note", BBCode::convert($owner["about"], false, 7));

			if (trim($owner["location"]) != "") {
				$element = $doc->createElement("poco:address");
				XML::addElement($doc, $element, "poco:formatted", $owner["location"]);
				$author->appendChild($element);
			}
		}

		if (DBM::is_result($profile) && !$show_profile) {
			if (trim($profile["homepage"]) != "") {
				$urls = $doc->createElement("poco:urls");
				XML::addElement($doc, $urls, "poco:type", "homepage");
				XML::addElement($doc, $urls, "poco:value", $profile["homepage"]);
				XML::addElement($doc, $urls, "poco:primary", "true");
				$author->appendChild($urls);
			}

			XML::addElement($doc, $author, "followers", "", ["url" => System::baseUrl()."/viewcontacts/".$owner["nick"]]);
			XML::addElement($doc, $author, "statusnet:profile_info", "", ["local_id" => $owner["uid"]]);

			if ($profile["publish"]) {
				XML::addElement($doc, $author, "mastodon:scope", "public");
			}
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
	private static function constructVerb(array $item)
	{
		if (!empty($item['verb'])) {
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
	private static function constructObjecttype(array $item)
	{
		if (in_array($item['object-type'], [ACTIVITY_OBJ_NOTE, ACTIVITY_OBJ_COMMENT]))
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
	private static function entry(DOMDocument $doc, array $item, array $owner, $toplevel = false)
	{
		$xml = null;

		$repeated_guid = self::getResharedGuid($item);
		if ($repeated_guid != "") {
			$xml = self::reshareEntry($doc, $item, $owner, $repeated_guid, $toplevel);
		}

		if ($xml) {
			return $xml;
		}

		if ($item["verb"] == ACTIVITY_LIKE) {
			return self::likeEntry($doc, $item, $owner, $toplevel);
		} elseif (in_array($item["verb"], [ACTIVITY_FOLLOW, NAMESPACE_OSTATUS."/unfollow"])) {
			return self::followEntry($doc, $item, $owner, $toplevel);
		} else {
			return self::noteEntry($doc, $item, $owner, $toplevel);
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
	private static function sourceEntry(DOMDocument $doc, array $contact)
	{
		$source = $doc->createElement("source");
		XML::addElement($doc, $source, "id", $contact["poll"]);
		XML::addElement($doc, $source, "title", $contact["name"]);
		XML::addElement($doc, $source, "link", "", ["rel" => "alternate", "type" => "text/html", "href" => $contact["alias"]]);
		XML::addElement($doc, $source, "link", "", ["rel" => "self", "type" => "application/atom+xml", "href" => $contact["poll"]]);
		XML::addElement($doc, $source, "icon", $contact["photo"]);
		XML::addElement($doc, $source, "updated", DateTimeFormat::utc($contact["success_update"]."+00:00", DateTimeFormat::ATOM));

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
	private static function contactEntry($url, array $owner)
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
	 * @param string $repeated_guid guid
	 * @param bool   $toplevel      Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private static function reshareEntry(DOMDocument $doc, array $item, array $owner, $repeated_guid, $toplevel)
	{
		if (($item["id"] != $item["parent"]) && (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entryHeader($doc, $entry, $owner, $item, $toplevel);

		$condition = ['uid' => $owner["uid"], 'guid' => $repeated_guid, 'private' => false,
			'network' => [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS]];
		$repeated_item = Item::selectFirst([], $condition);
		if (!DBM::is_result($repeated_item)) {
			return false;
		}

		$contact = self::contactEntry($repeated_item['author-link'], $owner);
		$contact['account-type'] = $contact['contact-type'];

		$title = $owner["nick"]." repeated a notice by ".$contact["nick"];

		self::entryContent($doc, $entry, $item, $owner, $title, ACTIVITY_SHARE, false);

		$as_object = $doc->createElement("activity:object");

		XML::addElement($doc, $as_object, "activity:object-type", NAMESPACE_ACTIVITY_SCHEMA."activity");

		self::entryContent($doc, $as_object, $repeated_item, $owner, "", "", false);

		$author = self::addAuthor($doc, $contact, false);
		$as_object->appendChild($author);

		$as_object2 = $doc->createElement("activity:object");

		XML::addElement($doc, $as_object2, "activity:object-type", self::constructObjecttype($repeated_item));

		$title = sprintf("New comment by %s", $contact["nick"]);

		self::entryContent($doc, $as_object2, $repeated_item, $owner, $title);

		$as_object->appendChild($as_object2);

		self::entryFooter($doc, $as_object, $item, $owner, false);

		$source = self::sourceEntry($doc, $contact);

		$as_object->appendChild($source);

		$entry->appendChild($as_object);

		self::entryFooter($doc, $entry, $item, $owner);

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
	private static function likeEntry(DOMDocument $doc, array $item, array $owner, $toplevel)
	{
		if (($item["id"] != $item["parent"]) && (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entryHeader($doc, $entry, $owner, $item, $toplevel);

		$verb = NAMESPACE_ACTIVITY_SCHEMA."favorite";
		self::entryContent($doc, $entry, $item, $owner, "Favorite", $verb, false);

		$as_object = $doc->createElement("activity:object");

		$parent = Item::selectFirst([], ['uri' => $item["thr-parent"], 'uid' => $item["uid"]]);

		XML::addElement($doc, $as_object, "activity:object-type", self::constructObjecttype($parent));

		self::entryContent($doc, $as_object, $parent, $owner, "New entry");

		$entry->appendChild($as_object);

		self::entryFooter($doc, $entry, $item, $owner);

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
	private static function addPersonObject(DOMDocument $doc, array $owner, array $contact)
	{
		$object = $doc->createElement("activity:object");
		XML::addElement($doc, $object, "activity:object-type", ACTIVITY_OBJ_PERSON);

		if ($contact['network'] == NETWORK_PHANTOM) {
			XML::addElement($doc, $object, "id", $contact['url']);
			return $object;
		}

		XML::addElement($doc, $object, "id", $contact["alias"]);
		XML::addElement($doc, $object, "title", $contact["nick"]);

		$attributes = ["rel" => "alternate", "type" => "text/html", "href" => $contact["url"]];
		XML::addElement($doc, $object, "link", "", $attributes);

		$attributes = [
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 175,
				"media:height" => 175,
				"href" => $contact["photo"]];
		XML::addElement($doc, $object, "link", "", $attributes);

		XML::addElement($doc, $object, "poco:preferredUsername", $contact["nick"]);
		XML::addElement($doc, $object, "poco:displayName", $contact["name"]);

		if (trim($contact["location"]) != "") {
			$element = $doc->createElement("poco:address");
			XML::addElement($doc, $element, "poco:formatted", $contact["location"]);
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
	private static function followEntry(DOMDocument $doc, array $item, array $owner, $toplevel)
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
			$message = L10n::t('%s is now following %s.');
			$title = L10n::t('following');
			$action = "subscription";
		} else {
			$message = L10n::t('%s stopped following %s.');
			$title = L10n::t('stopped following');
			$action = "unfollow";
		}

		$item["uri"] = $item['parent-uri'] = $item['thr-parent']
				= 'tag:'.get_app()->get_hostname().
				','.date('Y-m-d').':'.$action.':'.$owner['uid'].
				':person:'.$connect_id.':'.$item['created'];

		$item["body"] = sprintf($message, $owner["nick"], $contact["nick"]);

		self::entryHeader($doc, $entry, $owner, $item, $toplevel);

		self::entryContent($doc, $entry, $item, $owner, $title);

		$object = self::addPersonObject($doc, $owner, $contact);
		$entry->appendChild($object);

		self::entryFooter($doc, $entry, $item, $owner);

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
	private static function noteEntry(DOMDocument $doc, array $item, array $owner, $toplevel)
	{
		if (($item["id"] != $item["parent"]) && (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entryHeader($doc, $entry, $owner, $item, $toplevel);

		XML::addElement($doc, $entry, "activity:object-type", ACTIVITY_OBJ_NOTE);

		self::entryContent($doc, $entry, $item, $owner, $title);

		self::entryFooter($doc, $entry, $item, $owner);

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
	private static function entryHeader(DOMDocument $doc, &$entry, array $owner, array $item, $toplevel)
	{
		/// @todo Check if this title stuff is really needed (I guess not)
		if (!$toplevel) {
			$entry = $doc->createElement("entry");
			$title = sprintf("New note by %s", $owner["nick"]);

			if ($owner['account-type'] == ACCOUNT_TYPE_COMMUNITY) {
				$contact = self::contactEntry($item['author-link'], $owner);
				$author = self::addAuthor($doc, $contact, false);
				$entry->appendChild($author);
			}
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

			$author = self::addAuthor($doc, $owner);
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
	 * @return void
	 */
	private static function entryContent(DOMDocument $doc, $entry, array $item, array $owner, $title, $verb = "", $complete = true)
	{
		if ($verb == "") {
			$verb = self::constructVerb($item);
		}

		XML::addElement($doc, $entry, "id", $item["uri"]);
		XML::addElement($doc, $entry, "title", $title);

		$body = self::formatPicturePost($item['body']);

		if ($item['title'] != "") {
			$body = "[b]".$item['title']."[/b]\n\n".$body;
		}

		$body = BBCode::convert($body, false, 7);

		XML::addElement($doc, $entry, "content", $body, ["type" => "html"]);

		XML::addElement($doc, $entry, "link", "", ["rel" => "alternate", "type" => "text/html",
								"href" => System::baseUrl()."/display/".$item["guid"]]
		);

		if ($complete && ($item["id"] > 0)) {
			XML::addElement($doc, $entry, "status_net", "", ["notice_id" => $item["id"]]);
		}

		XML::addElement($doc, $entry, "activity:verb", $verb);

		XML::addElement($doc, $entry, "published", DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM));
		XML::addElement($doc, $entry, "updated", DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM));
	}

	/**
	 * @brief Adds the elements at the foot of an entry to the XML document
	 *
	 * @param object $doc      XML document
	 * @param object $entry    The entry element where the elements are added
	 * @param array  $item     Data of the item that is to be posted
	 * @param array  $owner    Contact data of the poster
	 * @param bool   $complete default true
	 * @return void
	 */
	private static function entryFooter(DOMDocument $doc, $entry, array $item, array $owner, $complete = true)
	{
		$mentioned = [];

		if (($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
			$parent = Item::selectFirst(['guid', 'author-link', 'owner-link'], ['id' => $item["parent"]]);
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

			$thrparent = Item::selectFirst(['guid', 'author-link', 'owner-link', 'plink'], ['uid' => $owner["uid"], 'uri' => $parent_item]);

			if (DBM::is_result($thrparent)) {
				$mentioned[$thrparent["author-link"]] = $thrparent["author-link"];
				$mentioned[$thrparent["owner-link"]] = $thrparent["owner-link"];
				$parent_plink = $thrparent["plink"];
			} else {
				$mentioned[$parent["author-link"]] = $parent["author-link"];
				$mentioned[$parent["owner-link"]] = $parent["owner-link"];
				$parent_plink = System::baseUrl()."/display/".$parent["guid"];
			}

			$attributes = [
					"ref" => $parent_item,
					"href" => $parent_plink];
			XML::addElement($doc, $entry, "thr:in-reply-to", "", $attributes);

			$attributes = [
					"rel" => "related",
					"href" => $parent_plink];
			XML::addElement($doc, $entry, "link", "", $attributes);
		}

		if (intval($item["parent"]) > 0) {
			$conversation_href = System::baseUrl()."/display/".$owner["nick"]."/".$item["parent"];
			$conversation_uri = $conversation_href;

			if (isset($parent_item)) {
				$conversation = DBA::selectFirst('conversation', ['conversation-uri', 'conversation-href'], ['item-uri' => $parent_item]);
				if (DBM::is_result($conversation)) {
					if ($conversation['conversation-uri'] != '') {
						$conversation_uri = $conversation['conversation-uri'];
					}
					if ($conversation['conversation-href'] != '') {
						$conversation_href = $conversation['conversation-href'];
					}
				}
			}

			XML::addElement($doc, $entry, "link", "", ["rel" => "ostatus:conversation", "href" => $conversation_href]);

			$attributes = [
					"href" => $conversation_href,
					"local_id" => $item["parent"],
					"ref" => $conversation_uri];

			XML::addElement($doc, $entry, "ostatus:conversation", $conversation_uri, $attributes);
		}

		$tags = item::getFeedTags($item);

		if (count($tags)) {
			foreach ($tags as $t) {
				if ($t[0] == "@") {
					$mentioned[$t[1]] = $t[1];
				}
			}
		}

		// Make sure that mentions are accepted (GNU Social has problems with mixing HTTP and HTTPS)
		$newmentions = [];
		foreach ($mentioned as $mention) {
			$newmentions[str_replace("http://", "https://", $mention)] = str_replace("http://", "https://", $mention);
			$newmentions[str_replace("https://", "http://", $mention)] = str_replace("https://", "http://", $mention);
		}
		$mentioned = $newmentions;

		foreach ($mentioned as $mention) {
			$condition = ['uid' => $owner['uid'], 'nurl' => normalise_link($mention)];
			$contact = DBA::selectFirst('contact', ['forum', 'prv', 'self', 'contact-type'], $condition);
			if ($contact["forum"] || $contact["prv"] || ($owner['contact-type'] == ACCOUNT_TYPE_COMMUNITY) ||
				($contact['self'] && ($owner['account-type'] == ACCOUNT_TYPE_COMMUNITY))) {
				XML::addElement($doc, $entry, "link", "",
					[
						"rel" => "mentioned",
						"ostatus:object-type" => ACTIVITY_OBJ_GROUP,
						"href" => $mention]
				);
			} else {
				XML::addElement($doc, $entry, "link", "",
					[
						"rel" => "mentioned",
						"ostatus:object-type" => ACTIVITY_OBJ_PERSON,
						"href" => $mention]
				);
			}
		}

		if ($owner['account-type'] == ACCOUNT_TYPE_COMMUNITY) {
			XML::addElement($doc, $entry, "link", "", [
				"rel" => "mentioned",
				"ostatus:object-type" => "http://activitystrea.ms/schema/1.0/group",
				"href" => $owner['url']
			]);
		}

		if (!$item["private"]) {
			XML::addElement($doc, $entry, "link", "", ["rel" => "ostatus:attention",
									"href" => "http://activityschema.org/collection/public"]);
			XML::addElement($doc, $entry, "link", "", ["rel" => "mentioned",
									"ostatus:object-type" => "http://activitystrea.ms/schema/1.0/collection",
									"href" => "http://activityschema.org/collection/public"]);
			XML::addElement($doc, $entry, "mastodon:scope", "public");
		}

		if (count($tags)) {
			foreach ($tags as $t) {
				if ($t[0] != "@") {
					XML::addElement($doc, $entry, "category", "", ["term" => $t[2]]);
				}
			}
		}

		self::getAttachment($doc, $entry, $item);

		if ($complete && ($item["id"] > 0)) {
			$app = $item["app"];
			if ($app == "") {
				$app = "web";
			}

			$attributes = ["local_id" => $item["id"], "source" => $app];

			if (isset($parent["id"])) {
				$attributes["repeat_of"] = $parent["id"];
			}

			if ($item["coord"] != "") {
				XML::addElement($doc, $entry, "georss:point", $item["coord"]);
			}

			XML::addElement($doc, $entry, "statusnet:notice_info", "", $attributes);
		}
	}

	/**
	 * Creates the XML feed for a given nickname
	 *
	 * Supported filters:
	 * - activity (default): all the public posts
	 * - posts: all the public top-level posts
	 * - comments: all the public replies
	 *
	 * Updates the provided last_update parameter if the result comes from the
	 * cache or it is empty
	 *
	 * @brief Creates the XML feed for a given nickname
	 *
	 * @param string  $owner_nick  Nickname of the feed owner
	 * @param string  $last_update Date of the last update
	 * @param integer $max_items   Number of maximum items to fetch
	 * @param string  $filter      Feed items filter (activity, posts or comments)
	 * @param boolean $nocache     Wether to bypass caching
	 *
	 * @return string XML feed
	 */
	public static function feed($owner_nick, &$last_update, $max_items = 300, $filter = 'activity', $nocache = false)
	{
		$stamp = microtime(true);

		$owner = User::getOwnerDataByNick($owner_nick);
		if (!$owner) {
			return;
		}

		$cachekey = "ostatus:feed:" . $owner_nick . ":" . $filter . ":" . $last_update;

		$previous_created = $last_update;

		// Don't cache when the last item was posted less then 15 minutes ago (Cache duration)
		if ((time() - strtotime($owner['last-item'])) < 15*60) {
			$result = Cache::get($cachekey);
			if (!$nocache && !is_null($result)) {
				logger('Feed duration: ' . number_format(microtime(true) - $stamp, 3) . ' - ' . $owner_nick . ' - ' . $filter . ' - ' . $previous_created . ' (cached)', LOGGER_DEBUG);
				$last_update = $result['last_update'];
				return $result['feed'];
			}
		}

		if (!strlen($last_update)) {
			$last_update = 'now -30 days';
		}

		$check_date = DateTimeFormat::utc($last_update);
		$authorid = Contact::getIdForURL($owner["url"], 0, true);

		$condition = ["`uid` = ? AND `created` > ? AND NOT `deleted`
			AND NOT `private` AND `visible` AND `wall` AND `parent-network` IN (?, ?)",
			$owner["uid"], $check_date, NETWORK_OSTATUS, NETWORK_DFRN];

		if ($filter === 'comments') {
			$condition[0] .= " AND `object-type` = ? ";
			$condition[] = ACTIVITY_OBJ_COMMENT;
		}

		if ($owner['account-type'] != ACCOUNT_TYPE_COMMUNITY) {
			$condition[0] .= " AND `contact-id` = ? AND `author-id` = ?";
			$condition[] = $owner["id"];
			$condition[] = $authorid;
		}

		$params = ['order' => ['created' => true], 'limit' => $max_items];

		if ($filter === 'posts') {
			$ret = Item::selectThread([], $condition, $params);
		} else {
			$ret = Item::select([], $condition, $params);
		}

		$items = Item::inArray($ret);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, $filter);

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

		$msg = ['feed' => $feeddata, 'last_update' => $last_update];
		Cache::set($cachekey, $msg, CACHE_QUARTER_HOUR);

		logger('Feed duration: ' . number_format(microtime(true) - $stamp, 3) . ' - ' . $owner_nick . ' - ' . $filter . ' - ' . $previous_created, LOGGER_DEBUG);

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
	public static function salmon(array $item, array $owner)
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
