<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Protocol;

use DOMDocument;
use DOMXPath;
use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * This class contain functions for the OStatus protocol
 */
class OStatus
{
	private static $itemlist;
	private static $conv_list = [];

	/**
	 * Fetches author data
	 *
	 * @param DOMXPath $xpath     The xpath object
	 * @param object   $context   The xml context of the author details
	 * @param array    $importer  user record of the importing user
	 * @param array    $contact   Called by reference, will contain the fetched contact
	 * @param bool     $onlyfetch Only fetch the header without updating the contact entries
	 *
	 * @return array Array of author related entries for the item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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

		$author['contact-id'] = ($contact['id'] ?? 0) ?: $author['author-id'];

		$contact = [];

/*
		This here would be better, but we would get problems with contacts from the statusnet addon
		This is kept here as a reminder for the future

		$cid = Contact::getIdForURL($author["author-link"], $importer["uid"]);
		if ($cid) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		}
*/
		if ($aliaslink != '') {
			$condition = ["`uid` = ? AND `alias` = ? AND `network` != ? AND `rel` IN (?, ?)",
					$importer["uid"], $aliaslink, Protocol::STATUSNET,
					Contact::SHARING, Contact::FRIEND];
			$contact = DBA::selectFirst('contact', [], $condition);
		}

		if (!DBA::isResult($contact) && $author["author-link"] != '') {
			if ($aliaslink == "") {
				$aliaslink = $author["author-link"];
			}

			$condition = ["`uid` = ? AND `nurl` IN (?, ?) AND `network` != ? AND `rel` IN (?, ?)",
					$importer["uid"], Strings::normaliseLink($author["author-link"]), Strings::normaliseLink($aliaslink),
					Protocol::STATUSNET, Contact::SHARING, Contact::FRIEND];
			$contact = DBA::selectFirst('contact', [], $condition);
		}

		if (!DBA::isResult($contact) && ($addr != '')) {
			$condition = ["`uid` = ? AND `addr` = ? AND `network` != ? AND `rel` IN (?, ?)",
					$importer["uid"], $addr, Protocol::STATUSNET,
					Contact::SHARING, Contact::FRIEND];
			$contact = DBA::selectFirst('contact', [], $condition);
		}

		if (DBA::isResult($contact)) {
			if ($contact['blocked']) {
				$contact['id'] = -1;
			} elseif (!empty(APContact::getByURL($contact['url'], false))) {
				ActivityPub\Receiver::switchContact($contact['id'], $importer['uid'], $contact['url']);
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
		if (DBA::isResult($contact) && ($contact['id'] > 0) && !$onlyfetch && ($contact["network"] == Protocol::OSTATUS)) {

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
			$contact['nurl'] = Strings::normaliseLink($contact['url']);

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
				Logger::log("Update profile picture for contact ".$contact["id"], Logger::DEBUG);
				Contact::updateAvatar($contact["id"], $author["author-avatar"]);
			}

			// Ensure that we are having this contact (with uid=0)
			$cid = Contact::getIdForURL($aliaslink);

			if ($cid) {
				$fields = ['url', 'nurl', 'name', 'nick', 'alias', 'about', 'location'];
				$old_contact = DBA::selectFirst('contact', $fields, ['id' => $cid]);

				// Update it with the current values
				$fields = ['url' => $author["author-link"], 'name' => $contact["name"],
						'nurl' => Strings::normaliseLink($author["author-link"]),
						'nick' => $contact["nick"], 'alias' => $contact["alias"],
						'about' => $contact["about"], 'location' => $contact["location"],
						'success_update' => DateTimeFormat::utcNow(), 'last-update' => DateTimeFormat::utcNow()];

				DBA::update('contact', $fields, ['id' => $cid], $old_contact);

				// Update the avatar
				if (!empty($author["author-avatar"])) {
					Contact::updateAvatar($cid, $author["author-avatar"]);
				}
			}
		} elseif (empty($contact["network"]) || ($contact["network"] != Protocol::DFRN)) {
			$contact = [];
		}

		return $author;
	}

	/**
	 * Fetches author data from a given XML string
	 *
	 * @param string $xml      The XML
	 * @param array  $importer user record of the importing user
	 *
	 * @return array Array of author related entries for the item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function salmonAuthor($xml, array $importer)
	{
		if ($xml == "") {
			return;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('thr', ActivityNamespace::THREAD);
		$xpath->registerNamespace('georss', ActivityNamespace::GEORSS);
		$xpath->registerNamespace('activity', ActivityNamespace::ACTIVITY);
		$xpath->registerNamespace('media', ActivityNamespace::MEDIA);
		$xpath->registerNamespace('poco', ActivityNamespace::POCO);
		$xpath->registerNamespace('ostatus', ActivityNamespace::OSTATUS);
		$xpath->registerNamespace('statusnet', ActivityNamespace::STATUSNET);

		$contact = ["id" => 0];

		// Fetch the first author
		$authordata = $xpath->query('//author')->item(0);
		$author = self::fetchAuthor($xpath, $authordata, $importer, $contact, true);
		return $author;
	}

	/**
	 * Read attributes from element
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
	 * Imports an XML string containing OStatus elements
	 *
	 * @param string $xml      The XML
	 * @param array  $importer user record of the importing user
	 * @param array  $contact  contact
	 * @param string $hub      Called by reference, returns the fetched hub data
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function import($xml, array $importer, array &$contact, &$hub)
	{
		self::process($xml, $importer, $contact, $hub, false, true, Conversation::PUSH);
	}

	/**
	 * Internal feed processing
	 *
	 * @param string  $xml        The XML
	 * @param array   $importer   user record of the importing user
	 * @param array   $contact    contact
	 * @param string  $hub        Called by reference, returns the fetched hub data
	 * @param boolean $stored     Is the post fresh imported or from the database?
	 * @param boolean $initialize Is it the leading post so that data has to be initialized?
	 *
	 * @return boolean Could the XML be processed?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function process($xml, array $importer, array &$contact = null, &$hub, $stored = false, $initialize = true, $direction = Conversation::UNKNOWN)
	{
		if ($initialize) {
			self::$itemlist = [];
			self::$conv_list = [];
		}

		Logger::log('Import OStatus message for user ' . $importer['uid'], Logger::DEBUG);

		if ($xml == "") {
			return false;
		}
		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('thr', ActivityNamespace::THREAD);
		$xpath->registerNamespace('georss', ActivityNamespace::GEORSS);
		$xpath->registerNamespace('activity', ActivityNamespace::ACTIVITY);
		$xpath->registerNamespace('media', ActivityNamespace::MEDIA);
		$xpath->registerNamespace('poco', ActivityNamespace::POCO);
		$xpath->registerNamespace('ostatus', ActivityNamespace::OSTATUS);
		$xpath->registerNamespace('statusnet', ActivityNamespace::STATUSNET);

		$hub = "";
		$hub_items = $xpath->query("/atom:feed/atom:link[@rel='hub']")->item(0);
		if (is_object($hub_items)) {
			$hub_attributes = $hub_items->attributes;
			if (is_object($hub_attributes)) {
				foreach ($hub_attributes as $hub_attribute) {
					if ($hub_attribute->name == "href") {
						$hub = $hub_attribute->textContent;
						Logger::log("Found hub ".$hub, Logger::DEBUG);
					}
				}
			}
		}

		$header = [];
		$header["uid"] = $importer["uid"];
		$header["network"] = Protocol::OSTATUS;
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["gravity"] = GRAVITY_COMMENT;

		if (!is_object($doc->firstChild) || empty($doc->firstChild->tagName)) {
			return false;
		}

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

			$header["protocol"] = Conversation::PARCEL_SALMON;
			$header["source"] = $xml2;
			$header["direction"] = $direction;
		} elseif (!$initialize) {
			return false;
		}

		// Fetch the first author
		$authordata = $xpath->query('//author')->item(0);
		$author = self::fetchAuthor($xpath, $authordata, $importer, $contact, $stored);

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

			$item = array_merge($header, $author);

			$item["uri"] = XML::getFirstNodeValue($xpath, 'atom:id/text()', $entry);
			$item['uri-id'] = ItemURI::insert(['uri' => $item['uri']]);

			$item["verb"] = XML::getFirstNodeValue($xpath, 'activity:verb/text()', $entry);

			// Delete a message
			if (in_array($item["verb"], ['qvitter-delete-notice', Activity::DELETE, 'delete'])) {
				self::deleteNotice($item);
				continue;
			}

			if (in_array($item["verb"], [Activity::O_UNFAVOURITE, Activity::UNFAVORITE])) {
				// Ignore "Unfavorite" message
				Logger::log("Ignore unfavorite message ".print_r($item, true), Logger::DEBUG);
				continue;
			}

			// Deletions come with the same uri, so we check for duplicates after processing deletions
			if (Post::exists(['uid' => $importer["uid"], 'uri' => $item["uri"]])) {
				Logger::log('Post with URI '.$item["uri"].' already existed for user '.$importer["uid"].'.', Logger::DEBUG);
				continue;
			} else {
				Logger::log('Processing post with URI '.$item["uri"].' for user '.$importer["uid"].'.', Logger::DEBUG);
			}

			if ($item["verb"] == Activity::JOIN) {
				// ignore "Join" messages
				Logger::log("Ignore join message ".print_r($item, true), Logger::DEBUG);
				continue;
			}

			if ($item["verb"] == "http://mastodon.social/schema/1.0/block") {
				// ignore mastodon "block" messages
				Logger::log("Ignore block message ".print_r($item, true), Logger::DEBUG);
				continue;
			}

			if ($item["verb"] == Activity::FOLLOW) {
				Contact::addRelationship($importer, $contact, $item);
				continue;
			}

			if ($item["verb"] == Activity::O_UNFOLLOW) {
				$dummy = null;
				Contact::removeFollower($importer, $contact, $item, $dummy);
				continue;
			}

			if ($item["verb"] == Activity::FAVORITE) {
				$orig_uri = $xpath->query("activity:object/atom:id", $entry)->item(0)->nodeValue;
				Logger::log("Favorite ".$orig_uri." ".print_r($item, true));

				$item["verb"] = Activity::LIKE;
				$item["thr-parent"] = $orig_uri;
				$item["gravity"] = GRAVITY_ACTIVITY;
				$item["object-type"] = Activity\ObjectType::NOTE;
			}

			// http://activitystrea.ms/schema/1.0/rsvp-yes
			if (!in_array($item["verb"], [Activity::POST, Activity::LIKE, Activity::SHARE])) {
				Logger::log("Unhandled verb ".$item["verb"]." ".print_r($item, true), Logger::DEBUG);
			}

			self::processPost($xpath, $entry, $item, $importer);

			if ($initialize && (count(self::$itemlist) > 0)) {
				if (self::$itemlist[0]['uri'] == self::$itemlist[0]['thr-parent']) {
					// We will import it everytime, when it is started by our contacts
					$valid = Contact::isSharingByURL(self::$itemlist[0]['author-link'], self::$itemlist[0]['uid']);

					if (!$valid) {
						// If not, then it depends on this setting
						$valid = ((self::$itemlist[0]['uid'] == 0) || !DI::pConfig()->get(self::$itemlist[0]['uid'], 'system', 'accept_only_sharer', false));
						if ($valid) {
							Logger::log("Item with uri ".self::$itemlist[0]['uri']." will be imported due to the system settings.", Logger::DEBUG);
						}
					} else {
						Logger::log("Item with uri ".self::$itemlist[0]['uri']." belongs to a contact (".self::$itemlist[0]['contact-id']."). It will be imported.", Logger::DEBUG);
					}
					if ($valid) {
						// Never post a thread when the only interaction by our contact was a like
						$valid = false;
						$verbs = [Activity::POST, Activity::SHARE];
						foreach (self::$itemlist as $item) {
							if (in_array($item['verb'], $verbs) && Contact::isSharingByURL($item['author-link'], $item['uid'])) {
								$valid = true;
							}
						}
						if ($valid) {
							Logger::log("Item with uri ".self::$itemlist[0]['uri']." will be imported since the thread contains posts or shares.", Logger::DEBUG);
						}
					}
				} else {
					$valid = true;
				}

				if ($valid) {
					$default_contact = 0;
					for ($key = count(self::$itemlist) - 1; $key >= 0; $key--) {
						if (empty(self::$itemlist[$key]['contact-id'])) {
							self::$itemlist[$key]['contact-id'] = $default_contact;
						} else {
							$default_contact = $item['contact-id'];
						}
					}
					foreach (self::$itemlist as $item) {
						$found = Post::exists(['uid' => $importer["uid"], 'uri' => $item["uri"]]);
						if ($found) {
							Logger::log("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already exists.", Logger::DEBUG);
						} elseif ($item['contact-id'] < 0) {
							Logger::log("Item with uri ".$item["uri"]." is from a blocked contact.", Logger::DEBUG);
						} else {
							$ret = Item::insert($item);
							Logger::log("Item with uri ".$item["uri"]." for user ".$importer["uid"].' stored. Return value: '.$ret);
						}
					}
				}
				self::$itemlist = [];
			}
			Logger::log('Processing done for post with URI '.$item["uri"].' for user '.$importer["uid"].'.', Logger::DEBUG);
		}
		return true;
	}

	/**
	 * Removes notice item from database
	 *
	 * @param array $item item
	 * @return void
	 * @throws \Exception
	 */
	private static function deleteNotice(array $item)
	{
		$condition = ['uid' => $item['uid'], 'author-id' => $item['author-id'], 'uri' => $item['uri']];
		if (!Post::exists($condition)) {
			Logger::log('Item from '.$item['author-link'].' with uri '.$item['uri'].' for user '.$item['uid']." wasn't found. We don't delete it.");
			return;
		}

		Item::markForDeletion($condition);

		Logger::log('Deleted item with uri '.$item['uri'].' for user '.$item['uid']);
	}

	/**
	 * Processes the XML for a post
	 *
	 * @param DOMXPath $xpath    The xpath object
	 * @param object   $entry    The xml entry that is processed
	 * @param array    $item     The item array
	 * @param array    $importer user record of the importing user
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function processPost(DOMXPath $xpath, $entry, array &$item, array $importer)
	{
		$item["body"] = HTML::toBBCode(XML::getFirstNodeValue($xpath, 'atom:content/text()', $entry));
		$item["object-type"] = XML::getFirstNodeValue($xpath, 'activity:object-type/text()', $entry);
		if (($item["object-type"] == Activity\ObjectType::BOOKMARK) || ($item["object-type"] == Activity\ObjectType::EVENT)) {
			$item["title"] = XML::getFirstNodeValue($xpath, 'atom:title/text()', $entry);
			$item["body"] = XML::getFirstNodeValue($xpath, 'atom:summary/text()', $entry);
		} elseif ($item["object-type"] == Activity\ObjectType::QUESTION) {
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
					$item["thr-parent"] = $attributes->textContent;
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
					if ($attributes->name == 'term') {
						// Store the hashtag
						Tag::store($item['uri-id'], Tag::HASHTAG, $attributes->textContent);
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
		if (($repeat_of != "") || ($item["verb"] == Activity::SHARE)) {
			$link_data = self::processRepeatedItem($xpath, $entry, $item, $importer);
			if (!empty($link_data['add_body'])) {
				$add_body .= $link_data['add_body'];
			}
		}

		$item["body"] .= $add_body;

		// Only add additional data when there is no picture in the post
		if (!strstr($item["body"], '[/img]')) {
			$item["body"] = PageInfo::searchAndAppendToBody($item["body"]);
		}

		Tag::storeFromBody($item['uri-id'], $item['body']);

		// Mastodon Content Warning
		if (($item["verb"] == Activity::POST) && $xpath->evaluate('boolean(atom:summary)', $entry)) {
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

		if (isset($item["thr-parent"])) {
			if (!Post::exists(['uid' => $importer["uid"], 'uri' => $item['thr-parent']])) {
				if ($related != '') {
					self::fetchRelated($related, $item["thr-parent"], $importer);
				}
			} else {
				Logger::log('Reply with URI '.$item["uri"].' already existed for user '.$importer["uid"].'.', Logger::DEBUG);
			}
		} else {
			$item["thr-parent"] = $item["uri"];
			$item["gravity"] = GRAVITY_PARENT;
		}

		if (($item['author-link'] != '') && !empty($item['protocol'])) {
			$item = Conversation::insert($item);
		}

		self::$itemlist[] = $item;
	}

	/**
	 * Fetch the conversation for posts
	 *
	 * @param string $conversation     The link to the conversation
	 * @param string $conversation_uri The conversation in "uri" format
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function fetchConversation($conversation, $conversation_uri)
	{
		// Ensure that we only store a conversation once in a process
		if (isset(self::$conv_list[$conversation])) {
			return;
		}

		self::$conv_list[$conversation] = true;

		$curlResult = DI::httpRequest()->get($conversation, ['accept_content' => 'application/atom+xml, text/html']);

		if (!$curlResult->isSuccess()) {
			return;
		}

		$xml = '';

		if (stristr($curlResult->getHeader(), 'Content-Type: application/atom+xml')) {
			$xml = $curlResult->getBody();
		}

		if ($xml == '') {
			$doc = new DOMDocument();
			if (!@$doc->loadHTML($curlResult->getBody())) {
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
					$conversation_atom = DI::httpRequest()->get($attribute['href']);

					if ($conversation_atom->isSuccess()) {
						$xml = $conversation_atom->getBody();
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
	 * Store a feed in several conversation entries
	 *
	 * @param string $xml              The feed
	 * @param string $conversation     conversation
	 * @param string $conversation_uri conversation uri
	 * @return void
	 * @throws \Exception
	 */
	private static function storeConversation($xml, $conversation = '', $conversation_uri = '')
	{
		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('thr', ActivityNamespace::THREAD);
		$xpath->registerNamespace('ostatus', ActivityNamespace::OSTATUS);

		$entries = $xpath->query('/atom:feed/atom:entry');

		// Now store the entries
		foreach ($entries as $entry) {
			$doc2 = new DOMDocument();
			$doc2->preserveWhiteSpace = false;
			$doc2->formatOutput = true;

			$conv_data = [];

			$conv_data['protocol'] = Conversation::PARCEL_SPLIT_CONVERSATION;
			$conv_data['direction'] = Conversation::PULL;
			$conv_data['network'] = Protocol::OSTATUS;
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

			Logger::log('Store conversation data for uri '.$conv_data['uri'], Logger::DEBUG);
			Conversation::insert($conv_data);
		}
	}

	/**
	 * Fetch the own post so that it can be stored later
	 *
	 * We want to store the original data for later processing.
	 * This function is meant for cases where we process a feed with multiple entries.
	 * In that case we need to fetch the single posts here.
	 *
	 * @param string $self The link to the self item
	 * @param array  $item The item array
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function fetchSelf($self, array &$item)
	{
		$condition = ['item-uri' => $self, 'protocol' => [Conversation::PARCEL_DFRN,
			Conversation::PARCEL_DIASPORA_DFRN, Conversation::PARCEL_LEGACY_DFRN,
			Conversation::PARCEL_LOCAL_DFRN, Conversation::PARCEL_DIRECT, Conversation::PARCEL_SALMON]];
		if (DBA::exists('conversation', $condition)) {
			Logger::log('Conversation '.$item['uri'].' is already stored.', Logger::DEBUG);
			return;
		}

		$curlResult = DI::httpRequest()->get($self);

		if (!$curlResult->isSuccess()) {
			return;
		}

		// We reformat the XML to make it better readable
		$doc = new DOMDocument();
		$doc->loadXML($curlResult->getBody());
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$xml = $doc->saveXML();

		$item["protocol"] = Conversation::PARCEL_SALMON;
		$item["source"] = $xml;
		$item["direction"] = Conversation::PULL;

		Logger::log('Conversation '.$item['uri'].' is now fetched.', Logger::DEBUG);
	}

	/**
	 * Fetch related posts and processes them
	 *
	 * @param string $related     The link to the related item
	 * @param string $related_uri The related item in "uri" format
	 * @param array  $importer    user record of the importing user
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchRelated($related, $related_uri, $importer)
	{
		$condition = ['item-uri' => $related_uri, 'protocol' => [Conversation::PARCEL_DFRN,
			Conversation::PARCEL_DIASPORA_DFRN, Conversation::PARCEL_LEGACY_DFRN,
			Conversation::PARCEL_LOCAL_DFRN, Conversation::PARCEL_DIRECT, Conversation::PARCEL_SALMON]];
		$conversation = DBA::selectFirst('conversation', ['source', 'protocol'], $condition);
		if (DBA::isResult($conversation)) {
			$stored = true;
			$xml = $conversation['source'];
			if (self::process($xml, $importer, $contact, $hub, $stored, false, Conversation::PULL)) {
				Logger::log('Got valid cached XML for URI '.$related_uri, Logger::DEBUG);
				return;
			}
			if ($conversation['protocol'] == Conversation::PARCEL_SALMON) {
				Logger::log('Delete invalid cached XML for URI '.$related_uri, Logger::DEBUG);
				DBA::delete('conversation', ['item-uri' => $related_uri]);
			}
		}

		$stored = false;
		$curlResult = DI::httpRequest()->get($related, ['accept_content' => 'application/atom+xml, text/html']);

		if (!$curlResult->isSuccess()) {
			return;
		}

		$xml = '';

		if (stristr($curlResult->getHeader(), 'Content-Type: application/atom+xml')) {
			Logger::log('Directly fetched XML for URI ' . $related_uri, Logger::DEBUG);
			$xml = $curlResult->getBody();
		}

		if ($xml == '') {
			$doc = new DOMDocument();
			if (!@$doc->loadHTML($curlResult->getBody())) {
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
					$curlResult = DI::httpRequest()->get($atom_file);

					if ($curlResult->isSuccess()) {
						Logger::log('Fetched XML for URI ' . $related_uri, Logger::DEBUG);
						$xml = $curlResult->getBody();
					}
				}
			}
		}

		// Workaround for older GNU Social servers
		if (($xml == '') && strstr($related, '/notice/')) {
			$curlResult = DI::httpRequest()->get(str_replace('/notice/', '/api/statuses/show/', $related) . '.atom');

			if ($curlResult->isSuccess()) {
				Logger::log('GNU Social workaround to fetch XML for URI ' . $related_uri, Logger::DEBUG);
				$xml = $curlResult->getBody();
			}
		}

		// Even more worse workaround for GNU Social ;-)
		if ($xml == '') {
			$related_guess = self::convertHref($related_uri);
			$curlResult = DI::httpRequest()->get(str_replace('/notice/', '/api/statuses/show/', $related_guess) . '.atom');

			if ($curlResult->isSuccess()) {
				Logger::log('GNU Social workaround 2 to fetch XML for URI ' . $related_uri, Logger::DEBUG);
				$xml = $curlResult->getBody();
			}
		}

		// Finally we take the data that we fetched from "ostatus:conversation"
		if ($xml == '') {
			$condition = ['item-uri' => $related_uri, 'protocol' => Conversation::PARCEL_SPLIT_CONVERSATION];
			$conversation = DBA::selectFirst('conversation', ['source'], $condition);
			if (DBA::isResult($conversation)) {
				$stored = true;
				Logger::log('Got cached XML from conversation for URI '.$related_uri, Logger::DEBUG);
				$xml = $conversation['source'];
			}
		}

		if ($xml != '') {
			self::process($xml, $importer, $contact, $hub, $stored, false, Conversation::PULL);
		} else {
			Logger::log("XML couldn't be fetched for URI: ".$related_uri." - href: ".$related, Logger::DEBUG);
		}
		return;
	}

	/**
	 * Processes the XML for a repeated post
	 *
	 * @param DOMXPath $xpath    The xpath object
	 * @param object   $entry    The xml entry that is processed
	 * @param array    $item     The item array
	 * @param array    $importer user record of the importing user
	 *
	 * @return array with data from links
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function processRepeatedItem(DOMXPath $xpath, $entry, array &$item, array $importer)
	{
		$activityobject = $xpath->query('activity:object', $entry)->item(0);

		if (!is_object($activityobject)) {
			return [];
		}

		$link_data = [];

		$orig_uri = XML::getFirstNodeValue($xpath, 'atom:id/text()', $activityobject);

		$links = $xpath->query("atom:link", $activityobject);
		if ($links) {
			$link_data = self::processLinks($links, $item);
		}

		$orig_body = XML::getFirstNodeValue($xpath, 'atom:content/text()', $activityobject);
		$orig_created = XML::getFirstNodeValue($xpath, 'atom:published/text()', $activityobject);
		$orig_edited = XML::getFirstNodeValue($xpath, 'atom:updated/text()', $activityobject);

		$orig_author = self::fetchAuthor($xpath, $activityobject, $importer, $dummy, false);

		$item["author-name"] = $orig_author["author-name"];
		$item["author-link"] = $orig_author["author-link"];
		$item["author-id"] = $orig_author["author-id"];

		$item["body"] = HTML::toBBCode($orig_body);
		$item["created"] = $orig_created;
		$item["edited"] = $orig_edited;

		$item["uri"] = $orig_uri;

		$item["verb"] = XML::getFirstNodeValue($xpath, 'activity:verb/text()', $activityobject);

		$item["object-type"] = XML::getFirstNodeValue($xpath, 'activity:object-type/text()', $activityobject);

		// Mastodon Content Warning
		if (($item["verb"] == Activity::POST) && $xpath->evaluate('boolean(atom:summary)', $activityobject)) {
			$clear_text = XML::getFirstNodeValue($xpath, 'atom:summary/text()', $activityobject);
			if (!empty($clear_text)) {
				$item['content-warning'] = HTML::toBBCode($clear_text);
			}
		}

		$inreplyto = $xpath->query('thr:in-reply-to', $activityobject);
		if (is_object($inreplyto->item(0))) {
			foreach ($inreplyto->item(0)->attributes as $attributes) {
				if ($attributes->name == "ref") {
					$item["thr-parent"] = $attributes->textContent;
				}
			}
		}

		return $link_data;
	}

	/**
	 * Processes links in the XML
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
						if (($item["object-type"] == Activity\ObjectType::QUESTION)
							|| ($item["object-type"] == Activity\ObjectType::EVENT)
						) {
							$item["body"] .= "\n" . PageInfo::getFooterFromUrl($attribute['href']);
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
							Post\Media::insert(['uri-id' => $item['uri-id'], 'type' => Post\Media::DOCUMENT,
								'url' => $attribute['href'], 'mimetype' => $attribute['type'],
								'size' => $attribute['length'] ?? null, 'description' => $attribute['title'] ?? null]);
						}
						break;
					case "related":
						if ($item["object-type"] != Activity\ObjectType::BOOKMARK) {
							if (!isset($item["thr-parent"])) {
								$item["thr-parent"] = $attribute['href'];
							}
							$link_data['related'] = $attribute['href'];
						} else {
							$item["body"] .= "\n" . PageInfo::getFooterFromUrl($attribute['href']);
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
	 * Create an url out of an uri
	 *
	 * @param string $href URI in the format "parameter1:parameter1:..."
	 *
	 * @return string URL in the format http(s)://....
	 */
	private static function convertHref($href)
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
	}

	/**
	 * Checks if the current post is a reshare
	 *
	 * @param array $item The item array of thw post
	 *
	 * @return string The guid if the post is a reshare
	 */
	public static function getResharedGuid(array $item)
	{
		$reshared = Item::getShareArray($item);
		if (empty($reshared['guid']) || !empty($reshared['comment'])) {
			return '';
		}

		return $reshared['guid'];
	}

	/**
	 * Cleans the body of a post if it contains picture links
	 *
	 * @param string $body The body
	 *
	 * @return string The cleaned body
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function formatPicturePost($body)
	{
		$siteinfo = BBCode::getAttachedData($body);

		if (($siteinfo["type"] == "photo") && (!empty($siteinfo["preview"]) || !empty($siteinfo["image"]))) {
			if (isset($siteinfo["preview"])) {
				$preview = $siteinfo["preview"];
			} else {
				$preview = $siteinfo["image"];
			}

			// Is it a remote picture? Then make a smaller preview here
			$preview = ProxyUtils::proxifyUrl($preview, false, ProxyUtils::SIZE_SMALL);

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
	 * Adds the header elements to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $owner     Contact data of the poster
	 * @param string      $filter    The related feed filter (activity, posts or comments)
	 *
	 * @return object header root element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addHeader(DOMDocument $doc, array $owner, $filter)
	{
		$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
		$doc->appendChild($root);

		$root->setAttribute("xmlns:thr", ActivityNamespace::THREAD);
		$root->setAttribute("xmlns:georss", ActivityNamespace::GEORSS);
		$root->setAttribute("xmlns:activity", ActivityNamespace::ACTIVITY);
		$root->setAttribute("xmlns:media", ActivityNamespace::MEDIA);
		$root->setAttribute("xmlns:poco", ActivityNamespace::POCO);
		$root->setAttribute("xmlns:ostatus", ActivityNamespace::OSTATUS);
		$root->setAttribute("xmlns:statusnet", ActivityNamespace::STATUSNET);
		$root->setAttribute("xmlns:mastodon", ActivityNamespace::MASTODON);

		$title = '';
		$selfUri = '/feed/' . $owner["nick"] . '/';
		switch ($filter) {
			case 'activity':
				$title = DI::l10n()->t('%s\'s timeline', $owner['name']);
				$selfUri .= $filter;
				break;
			case 'posts':
				$title = DI::l10n()->t('%s\'s posts', $owner['name']);
				break;
			case 'comments':
				$title = DI::l10n()->t('%s\'s comments', $owner['name']);
				$selfUri .= $filter;
				break;
		}

		$selfUri = "/dfrn_poll/" . $owner["nick"];

		$attributes = ["uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION . "-" . DB_UPDATE_VERSION];
		XML::addElement($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);
		XML::addElement($doc, $root, "id", DI::baseUrl() . "/profile/" . $owner["nick"]);
		XML::addElement($doc, $root, "title", $title);
		XML::addElement($doc, $root, "subtitle", sprintf("Updates from %s on %s", $owner["name"], DI::config()->get('config', 'sitename')));
		XML::addElement($doc, $root, "logo", $owner["photo"]);
		XML::addElement($doc, $root, "updated", DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner, true);
		$root->appendChild($author);

		$attributes = ["href" => $owner["url"], "rel" => "alternate", "type" => "text/html"];
		XML::addElement($doc, $root, "link", "", $attributes);

		/// @TODO We have to find out what this is
		/// $attributes = array("href" => DI::baseUrl()."/sup",
		///		"rel" => "http://api.friendfeed.com/2008/03#sup",
		///		"type" => "application/json");
		/// XML::addElement($doc, $root, "link", "", $attributes);

		self::hublinks($doc, $root, $owner["nick"]);

		$attributes = ["href" => DI::baseUrl() . "/salmon/" . $owner["nick"], "rel" => "salmon"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["href" => DI::baseUrl() . "/salmon/" . $owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-replies"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["href" => DI::baseUrl() . "/salmon/" . $owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-mention"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["href" => DI::baseUrl() . $selfUri, "rel" => "self", "type" => "application/atom+xml"];
		XML::addElement($doc, $root, "link", "", $attributes);

		if ($owner['contact-type'] == Contact::TYPE_COMMUNITY) {
			$condition = ['uid' => $owner['uid'], 'self' => false, 'pending' => false,
					'archive' => false, 'hidden' => false, 'blocked' => false];
			$members = DBA::count('contact', $condition);
			XML::addElement($doc, $root, "statusnet:group_info", "", ["member_count" => $members]);
		}

		return $root;
	}

	/**
	 * Add the link to the push hubs to the XML document
	 *
	 * @param DOMDocument $doc  XML document
	 * @param object      $root XML root element where the hub links are added
	 * @param object      $nick nick
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function hublinks(DOMDocument $doc, $root, $nick)
	{
		$h = DI::baseUrl() . '/pubsubhubbub/'.$nick;
		XML::addElement($doc, $root, "link", "", ["href" => $h, "rel" => "hub"]);
	}

	/**
	 * Adds attachment data to the XML document
	 *
	 * @param DOMDocument $doc  XML document
	 * @param object      $root XML root element where the hub links are added
	 * @param array       $item Data of the item that is to be posted
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getAttachment(DOMDocument $doc, $root, $item)
	{
		$siteinfo = BBCode::getAttachedData($item["body"]);

		switch ($siteinfo["type"]) {
			case 'photo':
				if (!empty($siteinfo["image"])) {
					$imgdata = Images::getInfoFromURLCached($siteinfo["image"]);
					if ($imgdata) {
						$attributes = ["rel" => "enclosure",
								"href" => $siteinfo["image"],
								"type" => $imgdata["mime"],
								"length" => intval($imgdata["size"])];
						XML::addElement($doc, $root, "link", "", $attributes);
					}
				}
				break;
			case 'video':
				$attributes = ["rel" => "enclosure",
						"href" => $siteinfo["url"],
						"type" => "text/html; charset=UTF-8",
						"length" => "0",
						"title" => ($siteinfo["title"] ?? '') ?: $siteinfo["url"],
				];
				XML::addElement($doc, $root, "link", "", $attributes);
				break;
			default:
				break;
		}

		if (!DI::config()->get('system', 'ostatus_not_attach_preview') && ($siteinfo["type"] != "photo") && isset($siteinfo["image"])) {
			$imgdata = Images::getInfoFromURLCached($siteinfo["image"]);
			if ($imgdata) {
				$attributes = ["rel" => "enclosure",
						"href" => $siteinfo["image"],
						"type" => $imgdata["mime"],
						"length" => intval($imgdata["size"])];

				XML::addElement($doc, $root, "link", "", $attributes);
			}
		}

		foreach (Post\Media::getByURIId($item['uri-id'], [Post\Media::DOCUMENT, Post\Media::TORRENT, Post\Media::UNKNOWN]) as $attachment) {
			$attributes = ['rel' => 'enclosure',
				'href' => $attachment['url'],
				'type' => $attachment['mimetype']];

			if (!empty($attachment['size'])) {
				$attributes['length'] = intval($attachment['size']);
			}
			if (!empty($attachment['description'])) {
				$attributes['title'] = $attachment['description'];
			}

			XML::addElement($doc, $root, 'link', '', $attributes);
		}
	}

	/**
	 * Adds the author element to the XML document
	 *
	 * @param DOMDocument $doc          XML document
	 * @param array       $owner        Contact data of the poster
	 * @param bool        $show_profile Whether to show profile
	 *
	 * @return \DOMElement author element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addAuthor(DOMDocument $doc, array $owner, $show_profile = true)
	{
		$profile = DBA::selectFirst('profile', ['homepage', 'publish'], ['uid' => $owner['uid']]);
		$author = $doc->createElement("author");
		XML::addElement($doc, $author, "id", $owner["url"]);
		if ($owner['contact-type'] == Contact::TYPE_COMMUNITY) {
			XML::addElement($doc, $author, "activity:object-type", Activity\ObjectType::GROUP);
		} else {
			XML::addElement($doc, $author, "activity:object-type", Activity\ObjectType::PERSON);
		}

		XML::addElement($doc, $author, "uri", $owner["url"]);
		XML::addElement($doc, $author, "name", $owner["nick"]);
		XML::addElement($doc, $author, "email", $owner["addr"]);
		if ($show_profile) {
			XML::addElement($doc, $author, "summary", BBCode::convert($owner["about"], false, BBCode::OSTATUS));
		}

		$attributes = ["rel" => "alternate", "type" => "text/html", "href" => $owner["url"]];
		XML::addElement($doc, $author, "link", "", $attributes);

		$attributes = [
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 300,
				"media:height" => 300,
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
			XML::addElement($doc, $author, "poco:note", BBCode::convert($owner["about"], false, BBCode::OSTATUS));

			if (trim($owner["location"]) != "") {
				$element = $doc->createElement("poco:address");
				XML::addElement($doc, $element, "poco:formatted", $owner["location"]);
				$author->appendChild($element);
			}
		}

		if (DBA::isResult($profile) && !$show_profile) {
			if (trim($profile["homepage"]) != "") {
				$urls = $doc->createElement("poco:urls");
				XML::addElement($doc, $urls, "poco:type", "homepage");
				XML::addElement($doc, $urls, "poco:value", $profile["homepage"]);
				XML::addElement($doc, $urls, "poco:primary", "true");
				$author->appendChild($urls);
			}

			XML::addElement($doc, $author, "followers", "", ["url" => DI::baseUrl() . "/profile/" . $owner["nick"] . "/contacts/followers"]);
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
	 * Returns the given activity if present - otherwise returns the "post" activity
	 *
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return string activity
	 */
	public static function constructVerb(array $item)
	{
		if (!empty($item['verb'])) {
			return $item['verb'];
		}

		return Activity::POST;
	}

	/**
	 * Returns the given object type if present - otherwise returns the "note" object type
	 *
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return string Object type
	 */
	private static function constructObjecttype(array $item)
	{
		if (!empty($item['object-type']) && in_array($item['object-type'], [Activity\ObjectType::NOTE, Activity\ObjectType::COMMENT])) {
			return $item['object-type'];
		}

		return Activity\ObjectType::NOTE;
	}

	/**
	 * Adds an entry element to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param bool        $toplevel  optional default false
	 *
	 * @return \DOMElement Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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

		if ($item["verb"] == Activity::LIKE) {
			return self::likeEntry($doc, $item, $owner, $toplevel);
		} elseif (in_array($item["verb"], [Activity::FOLLOW, Activity::O_UNFOLLOW])) {
			return self::followEntry($doc, $item, $owner, $toplevel);
		} else {
			return self::noteEntry($doc, $item, $owner, $toplevel);
		}
	}

	/**
	 * Adds a source entry to the XML document
	 *
	 * @param DOMDocument $doc     XML document
	 * @param array       $contact Array of the contact that is added
	 *
	 * @return \DOMElement Source element
	 * @throws \Exception
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
	 * Adds an entry element with reshared content
	 *
	 * @param DOMDocument $doc           XML document
	 * @param array       $item          Data of the item that is to be posted
	 * @param array       $owner         Contact data of the poster
	 * @param string      $repeated_guid guid
	 * @param bool        $toplevel      Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return bool Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function reshareEntry(DOMDocument $doc, array $item, array $owner, $repeated_guid, $toplevel)
	{
		if (($item['gravity'] != GRAVITY_PARENT) && (Strings::normaliseLink($item["author-link"]) != Strings::normaliseLink($owner["url"]))) {
			Logger::log("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", Logger::DEBUG);
		}

		$entry = self::entryHeader($doc, $owner, $item, $toplevel);

		$condition = ['uid' => $owner["uid"], 'guid' => $repeated_guid, 'private' => [Item::PUBLIC, Item::UNLISTED],
			'network' => [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS]];
		$repeated_item = Post::selectFirst([], $condition);
		if (!DBA::isResult($repeated_item)) {
			return false;
		}

		$contact = Contact::getByURL($repeated_item['author-link']) ?: $owner;

		$title = $owner["nick"]." repeated a notice by ".$contact["nick"];

		self::entryContent($doc, $entry, $item, $owner, $title, Activity::SHARE, false);

		$as_object = $doc->createElement("activity:object");

		XML::addElement($doc, $as_object, "activity:object-type", ActivityNamespace::ACTIVITY_SCHEMA . "activity");

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

		self::entryFooter($doc, $entry, $item, $owner, true);

		return $entry;
	}

	/**
	 * Adds an entry element with a "like"
	 *
	 * @param DOMDocument $doc      XML document
	 * @param array       $item     Data of the item that is to be posted
	 * @param array       $owner    Contact data of the poster
	 * @param bool        $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return \DOMElement Entry element with "like"
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function likeEntry(DOMDocument $doc, array $item, array $owner, $toplevel)
	{
		if (($item['gravity'] != GRAVITY_PARENT) && (Strings::normaliseLink($item["author-link"]) != Strings::normaliseLink($owner["url"]))) {
			Logger::log("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", Logger::DEBUG);
		}

		$entry = self::entryHeader($doc, $owner, $item, $toplevel);

		$verb = ActivityNamespace::ACTIVITY_SCHEMA . "favorite";
		self::entryContent($doc, $entry, $item, $owner, "Favorite", $verb, false);

		$parent = Post::selectFirst([], ['uri' => $item["thr-parent"], 'uid' => $item["uid"]]);
		if (DBA::isResult($parent)) {
			$as_object = $doc->createElement("activity:object");

			XML::addElement($doc, $as_object, "activity:object-type", self::constructObjecttype($parent));

			self::entryContent($doc, $as_object, $parent, $owner, "New entry");

			$entry->appendChild($as_object);
		}

		self::entryFooter($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * Adds the person object element to the XML document
	 *
	 * @param DOMDocument $doc     XML document
	 * @param array       $owner   Contact data of the poster
	 * @param array       $contact Contact data of the target
	 *
	 * @return object author element
	 */
	private static function addPersonObject(DOMDocument $doc, array $owner, array $contact)
	{
		$object = $doc->createElement("activity:object");
		XML::addElement($doc, $object, "activity:object-type", Activity\ObjectType::PERSON);

		if ($contact['network'] == Protocol::PHANTOM) {
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
				"media:width" => 300,
				"media:height" => 300,
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
	 * Adds a follow/unfollow entry element
	 *
	 * @param DOMDocument $doc      XML document
	 * @param array       $item     Data of the follow/unfollow message
	 * @param array       $owner    Contact data of the poster
	 * @param bool        $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return \DOMElement Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function followEntry(DOMDocument $doc, array $item, array $owner, $toplevel)
	{
		$item["id"] = $item['parent'] = 0;
		$item["created"] = $item["edited"] = date("c");
		$item["private"] = Item::PRIVATE;

		$contact = Contact::getByURL($item['follow']);
		$item['follow'] = $contact['url'];

		if ($contact['alias']) {
			$item['follow'] = $contact['alias'];
		} else {
			$contact['alias'] = $contact['url'];
		}

		$condition = ['uid' => $owner['uid'], 'nurl' => Strings::normaliseLink($contact["url"])];
		$user_contact = DBA::selectFirst('contact', ['id'], $condition);

		if (DBA::isResult($user_contact)) {
			$connect_id = $user_contact['id'];
		} else {
			$connect_id = 0;
		}

		if ($item['verb'] == Activity::FOLLOW) {
			$message = DI::l10n()->t('%s is now following %s.');
			$title = DI::l10n()->t('following');
			$action = "subscription";
		} else {
			$message = DI::l10n()->t('%s stopped following %s.');
			$title = DI::l10n()->t('stopped following');
			$action = "unfollow";
		}

		$item["uri"] = $item['parent-uri'] = $item['thr-parent']
				= 'tag:' . DI::baseUrl()->getHostname().
				','.date('Y-m-d').':'.$action.':'.$owner['uid'].
				':person:'.$connect_id.':'.$item['created'];

		$item["body"] = sprintf($message, $owner["nick"], $contact["nick"]);

		$entry = self::entryHeader($doc, $owner, $item, $toplevel);

		self::entryContent($doc, $entry, $item, $owner, $title);

		$object = self::addPersonObject($doc, $owner, $contact);
		$entry->appendChild($object);

		self::entryFooter($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * Adds a regular entry element
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param bool        $toplevel  Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return \DOMElement Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function noteEntry(DOMDocument $doc, array $item, array $owner, $toplevel)
	{
		if (($item['gravity'] != GRAVITY_PARENT) && (Strings::normaliseLink($item["author-link"]) != Strings::normaliseLink($owner["url"]))) {
			Logger::log("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", Logger::DEBUG);
		}

		if (!$toplevel) {
			if (!empty($item['title'])) {
				$title = BBCode::convert($item['title'], false, BBCode::OSTATUS);
			} else {
				$title = sprintf("New note by %s", $owner["nick"]);
			}
		} else {
			$title = sprintf("New comment by %s", $owner["nick"]);
		}

		$entry = self::entryHeader($doc, $owner, $item, $toplevel);

		XML::addElement($doc, $entry, "activity:object-type", Activity\ObjectType::NOTE);

		self::entryContent($doc, $entry, $item, $owner, $title, '', true);

		self::entryFooter($doc, $entry, $item, $owner, true);

		return $entry;
	}

	/**
	 * Adds a header element to the XML document
	 *
	 * @param DOMDocument $doc      XML document
	 * @param array       $owner    Contact data of the poster
	 * @param array       $item
	 * @param bool        $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return \DOMElement The entry element where the elements are added
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function entryHeader(DOMDocument $doc, array $owner, array $item, $toplevel)
	{
		if (!$toplevel) {
			$entry = $doc->createElement("entry");

			if ($owner['contact-type'] == Contact::TYPE_COMMUNITY) {
				$contact = Contact::getByURL($item['author-link']) ?: $owner;
				$author = self::addAuthor($doc, $contact, false);
				$entry->appendChild($author);
			}
		} else {
			$entry = $doc->createElementNS(ActivityNamespace::ATOM1, "entry");

			$entry->setAttribute("xmlns:thr", ActivityNamespace::THREAD);
			$entry->setAttribute("xmlns:georss", ActivityNamespace::GEORSS);
			$entry->setAttribute("xmlns:activity", ActivityNamespace::ACTIVITY);
			$entry->setAttribute("xmlns:media", ActivityNamespace::MEDIA);
			$entry->setAttribute("xmlns:poco", ActivityNamespace::POCO);
			$entry->setAttribute("xmlns:ostatus", ActivityNamespace::OSTATUS);
			$entry->setAttribute("xmlns:statusnet", ActivityNamespace::STATUSNET);
			$entry->setAttribute("xmlns:mastodon", ActivityNamespace::MASTODON);

			$author = self::addAuthor($doc, $owner);
			$entry->appendChild($author);
		}

		return $entry;
	}

	/**
	 * Adds elements to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param \DOMElement $entry     Entry element where the content is added
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param string      $title     Title for the post
	 * @param string      $verb      The activity verb
	 * @param bool        $complete  Add the "status_net" element?
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function entryContent(DOMDocument $doc, \DOMElement $entry, array $item, array $owner, $title, $verb = "", $complete = true)
	{
		if ($verb == "") {
			$verb = self::constructVerb($item);
		}

		XML::addElement($doc, $entry, "id", $item["uri"]);
		XML::addElement($doc, $entry, "title", html_entity_decode($title, ENT_QUOTES, 'UTF-8'));

		$body = self::formatPicturePost($item['body']);

		if (!empty($item['title'])) {
			$body = "[b]".$item['title']."[/b]\n\n".$body;
		}

		$body = BBCode::convert($body, false, BBCode::OSTATUS);

		XML::addElement($doc, $entry, "content", $body, ["type" => "html"]);

		XML::addElement($doc, $entry, "link", "", ["rel" => "alternate", "type" => "text/html",
								"href" => DI::baseUrl()."/display/".$item["guid"]]
		);

		if ($complete && ($item["id"] > 0)) {
			XML::addElement($doc, $entry, "status_net", "", ["notice_id" => $item["id"]]);
		}

		XML::addElement($doc, $entry, "activity:verb", $verb);

		XML::addElement($doc, $entry, "published", DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM));
		XML::addElement($doc, $entry, "updated", DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM));
	}

	/**
	 * Adds the elements at the foot of an entry to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param object      $entry     The entry element where the elements are added
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @param bool        $complete  default true
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function entryFooter(DOMDocument $doc, $entry, array $item, array $owner, $complete = true)
	{
		$mentioned = [];

		if ($item['gravity'] != GRAVITY_PARENT) {
			$parent = Post::selectFirst(['guid', 'author-link', 'owner-link'], ['id' => $item['parent']]);

			$thrparent = Post::selectFirst(['guid', 'author-link', 'owner-link', 'plink'], ['uid' => $owner["uid"], 'uri' => $item['thr-parent']]);

			if (DBA::isResult($thrparent)) {
				$mentioned[$thrparent["author-link"]] = $thrparent["author-link"];
				$mentioned[$thrparent["owner-link"]] = $thrparent["owner-link"];
				$parent_plink = $thrparent["plink"];
			} else {
				$mentioned[$parent["author-link"]] = $parent["author-link"];
				$mentioned[$parent["owner-link"]] = $parent["owner-link"];
				$parent_plink = DI::baseUrl()."/display/".$parent["guid"];
			}

			$attributes = [
					"ref" => $item['thr-parent'],
					"href" => $parent_plink];
			XML::addElement($doc, $entry, "thr:in-reply-to", "", $attributes);

			$attributes = [
					"rel" => "related",
					"href" => $parent_plink];
			XML::addElement($doc, $entry, "link", "", $attributes);
		}

		if (intval($item['parent']) > 0) {
			$conversation_href = $conversation_uri = str_replace('/objects/', '/context/', $item['thr-parent']);

			if (isset($parent_item)) {
				$conversation = DBA::selectFirst('conversation', ['conversation-uri', 'conversation-href'], ['item-uri' => $parent_item]);
				if (DBA::isResult($conversation)) {
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
					"local_id" => $item['parent'],
					"ref" => $conversation_uri];

			XML::addElement($doc, $entry, "ostatus:conversation", $conversation_uri, $attributes);
		}

		// uri-id isn't present for follow entry pseudo-items
		$tags = Tag::getByURIId($item['uri-id'] ?? 0);
		foreach ($tags as $tag) {
			$mentioned[$tag['url']] = $tag['url'];
		}

		// Make sure that mentions are accepted (GNU Social has problems with mixing HTTP and HTTPS)
		$newmentions = [];
		foreach ($mentioned as $mention) {
			$newmentions[str_replace("http://", "https://", $mention)] = str_replace("http://", "https://", $mention);
			$newmentions[str_replace("https://", "http://", $mention)] = str_replace("https://", "http://", $mention);
		}
		$mentioned = $newmentions;

		foreach ($mentioned as $mention) {
			$contact = Contact::getByURL($mention, false, ['contact-type']);
			if (!empty($contact) && ($contact['contact-type'] == Contact::TYPE_COMMUNITY)) {
				XML::addElement($doc, $entry, "link", "",
					[
						"rel" => "mentioned",
						"ostatus:object-type" => Activity\ObjectType::GROUP,
						"href" => $mention]
				);
			} else {
				XML::addElement($doc, $entry, "link", "",
					[
						"rel" => "mentioned",
						"ostatus:object-type" => Activity\ObjectType::PERSON,
						"href" => $mention]
				);
			}
		}

		if ($owner['contact-type'] == Contact::TYPE_COMMUNITY) {
			XML::addElement($doc, $entry, "link", "", [
				"rel" => "mentioned",
				"ostatus:object-type" => "http://activitystrea.ms/schema/1.0/group",
				"href" => $owner['url']
			]);
		}

		if ($item['private'] != Item::PRIVATE) {
			XML::addElement($doc, $entry, "link", "", ["rel" => "ostatus:attention",
									"href" => "http://activityschema.org/collection/public"]);
			XML::addElement($doc, $entry, "link", "", ["rel" => "mentioned",
									"ostatus:object-type" => "http://activitystrea.ms/schema/1.0/collection",
									"href" => "http://activityschema.org/collection/public"]);
			XML::addElement($doc, $entry, "mastodon:scope", "public");
		}

		foreach ($tags as $tag) {
			if ($tag['type'] == Tag::HASHTAG) {
				XML::addElement($doc, $entry, "category", "", ["term" => $tag['name']]);
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
	 * @param string  $owner_nick  Nickname of the feed owner
	 * @param string  $last_update Date of the last update
	 * @param integer $max_items   Number of maximum items to fetch
	 * @param string  $filter      Feed items filter (activity, posts or comments)
	 * @param boolean $nocache     Wether to bypass caching
	 *
	 * @return string XML feed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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
			$result = DI::cache()->get($cachekey);
			if (!$nocache && !is_null($result)) {
				Logger::log('Feed duration: ' . number_format(microtime(true) - $stamp, 3) . ' - ' . $owner_nick . ' - ' . $filter . ' - ' . $previous_created . ' (cached)', Logger::DEBUG);
				$last_update = $result['last_update'];
				return $result['feed'];
			}
		}

		if (!strlen($last_update)) {
			$last_update = 'now -30 days';
		}

		$check_date = DateTimeFormat::utc($last_update);
		$authorid = Contact::getIdForURL($owner["url"]);

		$condition = ["`uid` = ? AND `received` > ? AND NOT `deleted`
			AND `private` != ? AND `visible` AND `wall` AND `parent-network` IN (?, ?)",
			$owner["uid"], $check_date, Item::PRIVATE, Protocol::OSTATUS, Protocol::DFRN];

		if ($filter === 'comments') {
			$condition[0] .= " AND `object-type` = ? ";
			$condition[] = Activity\ObjectType::COMMENT;
		}

		if ($owner['contact-type'] != Contact::TYPE_COMMUNITY) {
			$condition[0] .= " AND `contact-id` = ? AND `author-id` = ?";
			$condition[] = $owner["id"];
			$condition[] = $authorid;
		}

		$params = ['order' => ['received' => true], 'limit' => $max_items];

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
			if (DI::config()->get('system', 'ostatus_debug')) {
				$item['body'] .= '🍼';
			}

			if (in_array($item["verb"], [Activity::FOLLOW, Activity::O_UNFOLLOW, Activity::LIKE])) {
				continue;
			}

			$entry = self::entry($doc, $item, $owner, false);
			$root->appendChild($entry);

			if ($last_update < $item['created']) {
				$last_update = $item['created'];
			}
		}

		$feeddata = trim($doc->saveXML());

		$msg = ['feed' => $feeddata, 'last_update' => $last_update];
		DI::cache()->set($cachekey, $msg, Duration::QUARTER_HOUR);

		Logger::log('Feed duration: ' . number_format(microtime(true) - $stamp, 3) . ' - ' . $owner_nick . ' - ' . $filter . ' - ' . $previous_created, Logger::DEBUG);

		return $feeddata;
	}

	/**
	 * Creates the XML for a salmon message
	 *
	 * @param array $item  Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 *
	 * @return string XML for the salmon
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function salmon(array $item, array $owner)
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		if (DI::config()->get('system', 'ostatus_debug')) {
			$item['body'] .= '🐟';
		}

		$entry = self::entry($doc, $item, $owner, true);

		$doc->appendChild($entry);

		return trim($doc->saveXML());
	}

	/**
	 * Checks if the given contact url does support OStatus
	 *
	 * @param string  $url    profile url
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSupportedByContactUrl($url)
	{
		$probe = Probe::uri($url, Protocol::OSTATUS);
		return $probe['network'] == Protocol::OSTATUS;
	}
}
