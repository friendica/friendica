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
use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Event;
use Friendica\Model\FContact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Mail;
use Friendica\Domain\Model\Notify\Type;
use Friendica\Domain\Model\PermissionSet;
use Friendica\Model\Post\Category;
use Friendica\Model\Profile;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Network\Probe;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * This class contain functions to create and send DFRN XML files
 */
class DFRN
{

	const TOP_LEVEL = 0;	// Top level posting
	const REPLY = 1;		// Regular reply that is stored locally
	const REPLY_RC = 2;	// Reply that will be relayed

	/**
	 * Generates an array of contact and user for DFRN imports
	 *
	 * This array contains not only the receiver but also the sender of the message.
	 *
	 * @param integer $cid Contact id
	 * @param integer $uid User id
	 *
	 * @return array importer
	 * @throws \Exception
	 */
	public static function getImporter($cid, $uid = 0)
	{
		$condition = ['id' => $cid, 'blocked' => false, 'pending' => false];
		$contact = DBA::selectFirst('contact', [], $condition);
		if (!DBA::isResult($contact)) {
			return [];
		}

		$contact['cpubkey'] = $contact['pubkey'];
		$contact['cprvkey'] = $contact['prvkey'];
		$contact['senderName'] = $contact['name'];

		if ($uid != 0) {
			$condition = ['uid' => $uid, 'account_expired' => false, 'account_removed' => false];
			$user = DBA::selectFirst('user', [], $condition);
			if (!DBA::isResult($user)) {
				return [];
			}

			$user['importer_uid'] = $user['uid'];
			$user['uprvkey'] = $user['prvkey'];
		} else {
			$user = ['importer_uid' => 0, 'uprvkey' => '', 'timezone' => 'UTC',
				'nickname' => '', 'sprvkey' => '', 'spubkey' => '',
				'page-flags' => 0, 'account-type' => 0, 'prvnets' => 0];
		}

		return array_merge($contact, $user);
	}

	/**
	 * Generates the atom entries for delivery.php
	 *
	 * This function is used whenever content is transmitted via DFRN.
	 *
	 * @param array $items Item elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN entries
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find proper type-hints
	 */
	public static function entries($items, $owner)
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, "dfrn:owner", "", false);

		if (! count($items)) {
			return trim($doc->saveXML());
		}

		foreach ($items as $item) {
			// These values aren't sent when sending from the queue.
			/// @todo Check if we can set these values from the queue or if they are needed at all.
			$item["entry:comment-allow"] = ($item["entry:comment-allow"] ?? '') ?: true;
			$item["entry:cid"] = $item["entry:cid"] ?? 0;

			$entry = self::entry($doc, "text", $item, $owner, $item["entry:comment-allow"], $item["entry:cid"]);
			if (isset($entry)) {
				$root->appendChild($entry);
			}
		}

		return trim($doc->saveXML());
	}

	/**
	 * Generate an atom feed for the given user
	 *
	 * This function is called when another server is pulling data from the user feed.
	 *
	 * @param string  $dfrn_id     DFRN ID from the requesting party
	 * @param string  $owner_nick  Owner nick name
	 * @param string  $last_update Date of the last update
	 * @param int     $direction   Can be -1, 0 or 1.
	 * @param boolean $onlyheader  Output only the header without content? (Default is "no")
	 *
	 * @return string DFRN feed entries
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function feed($dfrn_id, $owner_nick, $last_update, $direction = 0, $onlyheader = false)
	{
		$a = DI::app();

		$sitefeed    = ((strlen($owner_nick)) ? false : true); // not yet implemented, need to rewrite huge chunks of following logic
		$public_feed = (($dfrn_id) ? false : true);
		$starred     = false;   // not yet implemented, possible security issues
		$converse    = false;

		if ($public_feed && $a->argc > 2) {
			for ($x = 2; $x < $a->argc; $x++) {
				if ($a->argv[$x] == 'converse') {
					$converse = true;
				}
				if ($a->argv[$x] == 'starred') {
					$starred = true;
				}
				if ($a->argv[$x] == 'category' && $a->argc > ($x + 1) && strlen($a->argv[$x+1])) {
					$category = $a->argv[$x+1];
				}
			}
		}

		// default permissions - anonymous user

		$sql_extra = sprintf(" AND `item`.`private` != %s ", Item::PRIVATE);

		$owner = DBA::selectFirst('owner-view', [], ['nickname' => $owner_nick]);
		if (!DBA::isResult($owner)) {
			Logger::log(sprintf('No contact found for nickname=%d', $owner_nick), Logger::WARNING);
			exit();
		}

		$owner_id = $owner['uid'];

		$sql_post_table = "";

		if (! $public_feed) {
			switch ($direction) {
				case (-1):
					$sql_extra = sprintf(" AND `issued-id` = '%s' ", DBA::escape($dfrn_id));
					break;
				case 0:
					$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
					break;
				case 1:
					$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
					break;
				default:
					return false;
					break; // NOTREACHED
			}

			$r = q(
				"SELECT * FROM `contact` WHERE NOT `blocked` AND `contact`.`uid` = %d $sql_extra LIMIT 1",
				intval($owner_id)
			);

			if (! DBA::isResult($r)) {
				Logger::log(sprintf('No contact found for uid=%d', $owner_id), Logger::WARNING);
				exit();
			}

			$contact = $r[0];

			$set = PermissionSet::get($owner_id, $contact['id']);

			if (!empty($set)) {
				$sql_extra = " AND `item`.`psid` IN (" . implode(',', $set) .")";
			} else {
				$sql_extra = sprintf(" AND `item`.`private` != %s", Item::PRIVATE);
			}
		}

		if ($public_feed) {
			$sort = 'DESC';
		} else {
			$sort = 'ASC';
		}

		if (! strlen($last_update)) {
			$last_update = 'now -30 days';
		}

		if (isset($category)) {
			$sql_post_table = sprintf("INNER JOIN (SELECT `uri-id` FROM `category-view` WHERE `name` = '%s' AND `type` = %d AND `uid` = %d ORDER BY `uri-id` DESC) AS `category` ON `item`.`uri-id` = `category`.`uri-id` ",
				DBA::escape(Strings::protectSprintf($category)), intval(Category::CATEGORY), intval($owner_id));
		}

		if ($public_feed && ! $converse) {
			$sql_extra .= " AND `contact`.`self` = 1 ";
		}

		$check_date = DateTimeFormat::utc($last_update);

		$r = q(
			"SELECT `item`.`id`
			FROM `item` USE INDEX (`uid_wall_changed`) $sql_post_table
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`wall` AND `item`.`changed` > '%s'
			AND `vid` != %d AND `item`.`visible` $sql_extra
			ORDER BY `item`.`parent` ".$sort.", `item`.`received` ASC LIMIT 0, 300",
			intval($owner_id),
			DBA::escape($check_date),
			Verb::getID(Activity::ANNOUNCE),
			DBA::escape($sort)
		);

		$ids = [];
		foreach ($r as $item) {
			$ids[] = $item['id'];
		}

		if (!empty($ids)) {
			$ret = Item::select(Item::DELIVER_FIELDLIST, ['id' => $ids]);
			$items = Item::inArray($ret);
		} else {
			$items = [];
		}

		/*
		 * Will check further below if this actually returned results.
		 * We will provide an empty feed if that is the case.
		 */

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$alternatelink = $owner['url'];

		if (isset($category)) {
			$alternatelink .= "/category/".$category;
		}

		if ($public_feed) {
			$author = "dfrn:owner";
		} else {
			$author = "author";
		}

		$root = self::addHeader($doc, $owner, $author, $alternatelink, true);

		/// @TODO This hook can't work anymore
		//	\Friendica\Core\Hook::callAll('atom_feed', $atom);

		if (!DBA::isResult($items) || $onlyheader) {
			$atom = trim($doc->saveXML());

			Hook::callAll('atom_feed_end', $atom);

			return $atom;
		}

		foreach ($items as $item) {
			// prevent private email from leaking.
			if ($item['network'] == Protocol::MAIL) {
				continue;
			}

			// public feeds get html, our own nodes use bbcode

			if ($public_feed) {
				$type = 'html';
				// catch any email that's in a public conversation and make sure it doesn't leak
				if ($item['private'] == Item::PRIVATE) {
					continue;
				}
			} else {
				$type = 'text';
			}

			$entry = self::entry($doc, $type, $item, $owner, true);
			if (isset($entry)) {
				$root->appendChild($entry);
			}
		}

		$atom = trim($doc->saveXML());

		Hook::callAll('atom_feed_end', $atom);

		return $atom;
	}

	/**
	 * Generate an atom entry for a given item id
	 *
	 * @param int     $item_id      The item id
	 * @param boolean $conversation Show the conversation. If false show the single post.
	 *
	 * @return string DFRN feed entry
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function itemFeed($item_id, $conversation = false)
	{
		if ($conversation) {
			$condition = ['parent' => $item_id];
		} else {
			$condition = ['id' => $item_id];
		}

		$ret = Item::select(Item::DELIVER_FIELDLIST, $condition);
		$items = Item::inArray($ret);
		if (!DBA::isResult($items)) {
			return '';
		}

		$item = $items[0];

		if ($item['uid'] != 0) {
			$owner = User::getOwnerDataById($item['uid']);
			if (!$owner) {
				return '';
			}
		} else {
			$owner = ['uid' => 0, 'nick' => 'feed-item'];
		}

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;
		$type = 'html';

		if ($conversation) {
			$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
			$doc->appendChild($root);

			$root->setAttribute("xmlns:thr", ActivityNamespace::THREAD);
			$root->setAttribute("xmlns:at", ActivityNamespace::TOMB);
			$root->setAttribute("xmlns:media", ActivityNamespace::MEDIA);
			$root->setAttribute("xmlns:dfrn", ActivityNamespace::DFRN);
			$root->setAttribute("xmlns:activity", ActivityNamespace::ACTIVITY);
			$root->setAttribute("xmlns:georss", ActivityNamespace::GEORSS);
			$root->setAttribute("xmlns:poco", ActivityNamespace::POCO);
			$root->setAttribute("xmlns:ostatus", ActivityNamespace::OSTATUS);
			$root->setAttribute("xmlns:statusnet", ActivityNamespace::STATUSNET);

			//$root = self::addHeader($doc, $owner, "dfrn:owner", "", false);

			foreach ($items as $item) {
				$entry = self::entry($doc, $type, $item, $owner, true, 0);
				if (isset($entry)) {
					$root->appendChild($entry);
				}
			}
		} else {
			self::entry($doc, $type, $item, $owner, true, 0, true);
		}

		$atom = trim($doc->saveXML());
		return $atom;
	}

	/**
	 * Create XML text for DFRN mails
	 *
	 * @param array $item  message elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN mail
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	public static function mail($item, $owner)
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, "dfrn:owner", "", false);

		$mail = $doc->createElement("dfrn:mail");
		$sender = $doc->createElement("dfrn:sender");

		XML::addElement($doc, $sender, "dfrn:name", $owner['name']);
		XML::addElement($doc, $sender, "dfrn:uri", $owner['url']);
		XML::addElement($doc, $sender, "dfrn:avatar", $owner['thumb']);

		$mail->appendChild($sender);

		XML::addElement($doc, $mail, "dfrn:id", $item['uri']);
		XML::addElement($doc, $mail, "dfrn:in-reply-to", $item['parent-uri']);
		XML::addElement($doc, $mail, "dfrn:sentdate", DateTimeFormat::utc($item['created'] . '+00:00', DateTimeFormat::ATOM));
		XML::addElement($doc, $mail, "dfrn:subject", $item['title']);
		XML::addElement($doc, $mail, "dfrn:content", $item['body']);

		$root->appendChild($mail);

		return trim($doc->saveXML());
	}

	/**
	 * Create XML text for DFRN friend suggestions
	 *
	 * @param array $item  suggestion elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN suggestions
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	public static function fsuggest($item, $owner)
	{
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, "dfrn:owner", "", false);

		$suggest = $doc->createElement("dfrn:suggest");

		XML::addElement($doc, $suggest, "dfrn:url", $item['url']);
		XML::addElement($doc, $suggest, "dfrn:name", $item['name']);
		XML::addElement($doc, $suggest, "dfrn:photo", $item['photo']);
		XML::addElement($doc, $suggest, "dfrn:request", $item['request']);
		XML::addElement($doc, $suggest, "dfrn:note", $item['note']);

		$root->appendChild($suggest);

		return trim($doc->saveXML());
	}

	/**
	 * Create XML text for DFRN relocations
	 *
	 * @param array $owner Owner record
	 * @param int   $uid   User ID
	 *
	 * @return string DFRN relocations
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	public static function relocate($owner, $uid)
	{

		/* get site pubkey. this could be a new installation with no site keys*/
		$pubkey = DI::config()->get('system', 'site_pubkey');
		if (! $pubkey) {
			$res = Crypto::newKeypair(1024);
			DI::config()->set('system', 'site_prvkey', $res['prvkey']);
			DI::config()->set('system', 'site_pubkey', $res['pubkey']);
		}

		$rp = q(
			"SELECT `resource-id` , `scale`, type FROM `photo`
				WHERE `profile` = 1 AND `uid` = %d ORDER BY scale;",
			$uid
		);
		$photos = [];
		$ext = Images::supportedTypes();

		foreach ($rp as $p) {
			$photos[$p['scale']] = DI::baseUrl().'/photo/'.$p['resource-id'].'-'.$p['scale'].'.'.$ext[$p['type']];
		}


		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, "dfrn:owner", "", false);

		$relocate = $doc->createElement("dfrn:relocate");

		XML::addElement($doc, $relocate, "dfrn:url", $owner['url']);
		XML::addElement($doc, $relocate, "dfrn:name", $owner['name']);
		XML::addElement($doc, $relocate, "dfrn:addr", $owner['addr']);
		XML::addElement($doc, $relocate, "dfrn:avatar", $owner['avatar']);
		XML::addElement($doc, $relocate, "dfrn:photo", $photos[4]);
		XML::addElement($doc, $relocate, "dfrn:thumb", $photos[5]);
		XML::addElement($doc, $relocate, "dfrn:micro", $photos[6]);
		XML::addElement($doc, $relocate, "dfrn:request", $owner['request']);
		XML::addElement($doc, $relocate, "dfrn:confirm", $owner['confirm']);
		XML::addElement($doc, $relocate, "dfrn:notify", $owner['notify']);
		XML::addElement($doc, $relocate, "dfrn:poll", $owner['poll']);
		XML::addElement($doc, $relocate, "dfrn:sitepubkey", DI::config()->get('system', 'site_pubkey'));

		$root->appendChild($relocate);

		return trim($doc->saveXML());
	}

	/**
	 * Adds the header elements for the DFRN protocol
	 *
	 * @param DOMDocument $doc           XML document
	 * @param array       $owner         Owner record
	 * @param string      $authorelement Element name for the author
	 * @param string      $alternatelink link to profile or category
	 * @param bool        $public        Is it a header for public posts?
	 *
	 * @return object XML root object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function addHeader(DOMDocument $doc, $owner, $authorelement, $alternatelink = "", $public = false)
	{

		if ($alternatelink == "") {
			$alternatelink = $owner['url'];
		}

		$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
		$doc->appendChild($root);

		$root->setAttribute("xmlns:thr", ActivityNamespace::THREAD);
		$root->setAttribute("xmlns:at", ActivityNamespace::TOMB);
		$root->setAttribute("xmlns:media", ActivityNamespace::MEDIA);
		$root->setAttribute("xmlns:dfrn", ActivityNamespace::DFRN);
		$root->setAttribute("xmlns:activity", ActivityNamespace::ACTIVITY);
		$root->setAttribute("xmlns:georss", ActivityNamespace::GEORSS);
		$root->setAttribute("xmlns:poco", ActivityNamespace::POCO);
		$root->setAttribute("xmlns:ostatus", ActivityNamespace::OSTATUS);
		$root->setAttribute("xmlns:statusnet", ActivityNamespace::STATUSNET);

		XML::addElement($doc, $root, "id", DI::baseUrl()."/profile/".$owner["nick"]);
		XML::addElement($doc, $root, "title", $owner["name"]);

		$attributes = ["uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION];
		XML::addElement($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);

		$attributes = ["rel" => "license", "href" => "http://creativecommons.org/licenses/by/3.0/"];
		XML::addElement($doc, $root, "link", "", $attributes);

		$attributes = ["rel" => "alternate", "type" => "text/html", "href" => $alternatelink];
		XML::addElement($doc, $root, "link", "", $attributes);


		if ($public) {
			// DFRN itself doesn't uses this. But maybe someone else wants to subscribe to the public feed.
			OStatus::hublinks($doc, $root, $owner["nick"]);

			$attributes = ["rel" => "salmon", "href" => DI::baseUrl()."/salmon/".$owner["nick"]];
			XML::addElement($doc, $root, "link", "", $attributes);

			$attributes = ["rel" => "http://salmon-protocol.org/ns/salmon-replies", "href" => DI::baseUrl()."/salmon/".$owner["nick"]];
			XML::addElement($doc, $root, "link", "", $attributes);

			$attributes = ["rel" => "http://salmon-protocol.org/ns/salmon-mention", "href" => DI::baseUrl()."/salmon/".$owner["nick"]];
			XML::addElement($doc, $root, "link", "", $attributes);
		}

		// For backward compatibility we keep this element
		if ($owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
			XML::addElement($doc, $root, "dfrn:community", 1);
		}

		// The former element is replaced by this one
		XML::addElement($doc, $root, "dfrn:account_type", $owner["account-type"]);

		/// @todo We need a way to transmit the different page flags like "User::PAGE_FLAGS_PRVGROUP"

		XML::addElement($doc, $root, "updated", DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner, $authorelement, $public);
		$root->appendChild($author);

		return $root;
	}

	/**
	 * Adds the author element in the header for the DFRN protocol
	 *
	 * @param DOMDocument $doc           XML document
	 * @param array       $owner         Owner record
	 * @param string      $authorelement Element name for the author
	 * @param boolean     $public        boolean
	 *
	 * @return \DOMElement XML author object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function addAuthor(DOMDocument $doc, array $owner, $authorelement, $public)
	{
		// Should the profile be "unsearchable" in the net? Then add the "hide" element
		$hide = DBA::exists('profile', ['uid' => $owner['uid'], 'net-publish' => false]);

		$author = $doc->createElement($authorelement);

		$namdate = DateTimeFormat::utc($owner['name-date'].'+00:00', DateTimeFormat::ATOM);
		$picdate = DateTimeFormat::utc($owner['avatar-date'].'+00:00', DateTimeFormat::ATOM);

		$attributes = [];

		if (!$public || !$hide) {
			$attributes = ["dfrn:updated" => $namdate];
		}

		XML::addElement($doc, $author, "name", $owner["name"], $attributes);
		XML::addElement($doc, $author, "uri", DI::baseUrl().'/profile/'.$owner["nickname"], $attributes);
		XML::addElement($doc, $author, "dfrn:handle", $owner["addr"], $attributes);

		$attributes = ["rel" => "photo", "type" => "image/jpeg",
					"media:width" => 300, "media:height" => 300, "href" => $owner['photo']];

		if (!$public || !$hide) {
			$attributes["dfrn:updated"] = $picdate;
		}

		XML::addElement($doc, $author, "link", "", $attributes);

		$attributes["rel"] = "avatar";
		XML::addElement($doc, $author, "link", "", $attributes);

		if ($hide) {
			XML::addElement($doc, $author, "dfrn:hide", "true");
		}

		// The following fields will only be generated if the data isn't meant for a public feed
		if ($public) {
			return $author;
		}

		$birthday = feed_birthday($owner['uid'], $owner['timezone']);

		if ($birthday) {
			XML::addElement($doc, $author, "dfrn:birthday", $birthday);
		}

		// Only show contact details when we are allowed to
		$profile = DBA::selectFirst('owner-view',
			['about', 'name', 'homepage', 'nickname', 'timezone', 'locality', 'region', 'country-name', 'pub_keywords', 'xmpp', 'dob'],
			['uid' => $owner['uid'], 'hidewall' => false]);
		if (DBA::isResult($profile)) {
			XML::addElement($doc, $author, "poco:displayName", $profile["name"]);
			XML::addElement($doc, $author, "poco:updated", $namdate);

			if (trim($profile["dob"]) > DBA::NULL_DATE) {
				XML::addElement($doc, $author, "poco:birthday", "0000-".date("m-d", strtotime($profile["dob"])));
			}

			XML::addElement($doc, $author, "poco:note", $profile["about"]);
			XML::addElement($doc, $author, "poco:preferredUsername", $profile["nickname"]);

			$savetz = date_default_timezone_get();
			date_default_timezone_set($profile["timezone"]);
			XML::addElement($doc, $author, "poco:utcOffset", date("P"));
			date_default_timezone_set($savetz);

			if (trim($profile["homepage"]) != "") {
				$urls = $doc->createElement("poco:urls");
				XML::addElement($doc, $urls, "poco:type", "homepage");
				XML::addElement($doc, $urls, "poco:value", $profile["homepage"]);
				XML::addElement($doc, $urls, "poco:primary", "true");
				$author->appendChild($urls);
			}

			if (trim($profile["pub_keywords"]) != "") {
				$keywords = explode(",", $profile["pub_keywords"]);

				foreach ($keywords as $keyword) {
					XML::addElement($doc, $author, "poco:tags", trim($keyword));
				}
			}

			if (trim($profile["xmpp"]) != "") {
				$ims = $doc->createElement("poco:ims");
				XML::addElement($doc, $ims, "poco:type", "xmpp");
				XML::addElement($doc, $ims, "poco:value", $profile["xmpp"]);
				XML::addElement($doc, $ims, "poco:primary", "true");
				$author->appendChild($ims);
			}

			if (trim($profile["locality"].$profile["region"].$profile["country-name"]) != "") {
				$element = $doc->createElement("poco:address");

				XML::addElement($doc, $element, "poco:formatted", Profile::formatLocation($profile));

				if (trim($profile["locality"]) != "") {
					XML::addElement($doc, $element, "poco:locality", $profile["locality"]);
				}

				if (trim($profile["region"]) != "") {
					XML::addElement($doc, $element, "poco:region", $profile["region"]);
				}

				if (trim($profile["country-name"]) != "") {
					XML::addElement($doc, $element, "poco:country", $profile["country-name"]);
				}

				$author->appendChild($element);
			}
		}

		return $author;
	}

	/**
	 * Adds the author elements in the "entry" elements of the DFRN protocol
	 *
	 * @param DOMDocument $doc         XML document
	 * @param string $element     Element name for the author
	 * @param string $contact_url Link of the contact
	 * @param array  $item        Item elements
	 *
	 * @return \DOMElement XML author object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function addEntryAuthor(DOMDocument $doc, $element, $contact_url, $item)
	{
		$author = $doc->createElement($element);

		$contact = Contact::getByURLForUser($contact_url, $item["uid"], false, ['url', 'name', 'addr', 'photo']);
		if (!empty($contact)) {
			XML::addElement($doc, $author, "name", $contact["name"]);
			XML::addElement($doc, $author, "uri", $contact["url"]);
			XML::addElement($doc, $author, "dfrn:handle", $contact["addr"]);

			/// @Todo
			/// - Check real image type and image size
			/// - Check which of these boths elements we should use
			$attributes = [
				"rel" => "photo",
				"type" => "image/jpeg",
				"media:width" => 80,
				"media:height" => 80,
				"href" => $contact["photo"]];
			XML::addElement($doc, $author, "link", "", $attributes);

			$attributes = [
				"rel" => "avatar",
				"type" => "image/jpeg",
				"media:width" => 80,
				"media:height" => 80,
				"href" => $contact["photo"]];
			XML::addElement($doc, $author, "link", "", $attributes);
		}

		return $author;
	}

	/**
	 * Adds the activity elements
	 *
	 * @param DOMDocument $doc      XML document
	 * @param string      $element  Element name for the activity
	 * @param string      $activity activity value
	 *
	 * @return \DOMElement XML activity object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find proper type-hints
	 */
	private static function createActivity(DOMDocument $doc, $element, $activity)
	{
		if ($activity) {
			$entry = $doc->createElement($element);

			$r = XML::parseString($activity);
			if (!$r) {
				return false;
			}

			if ($r->type) {
				XML::addElement($doc, $entry, "activity:object-type", $r->type);
			}

			if ($r->id) {
				XML::addElement($doc, $entry, "id", $r->id);
			}

			if ($r->title) {
				XML::addElement($doc, $entry, "title", $r->title);
			}

			if ($r->link) {
				if (substr($r->link, 0, 1) == '<') {
					if (strstr($r->link, '&') && (! strstr($r->link, '&amp;'))) {
						$r->link = str_replace('&', '&amp;', $r->link);
					}

					$r->link = preg_replace('/\<link(.*?)\"\>/', '<link$1"/>', $r->link);

					// XML does need a single element as root element so we add a dummy element here
					$data = XML::parseString("<dummy>" . $r->link . "</dummy>");
					if (is_object($data)) {
						foreach ($data->link as $link) {
							$attributes = [];
							foreach ($link->attributes() as $parameter => $value) {
								$attributes[$parameter] = $value;
							}
							XML::addElement($doc, $entry, "link", "", $attributes);
						}
					}
				} else {
					$attributes = ["rel" => "alternate", "type" => "text/html", "href" => $r->link];
					XML::addElement($doc, $entry, "link", "", $attributes);
				}
			}
			if ($r->content) {
				XML::addElement($doc, $entry, "content", BBCode::convert($r->content), ["type" => "html"]);
			}

			return $entry;
		}

		return false;
	}

	/**
	 * Adds the elements for attachments
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root
	 * @param array  $item Item element
	 *
	 * @return void XML attachment object
	 * @todo  Find proper type-hints
	 */
	private static function getAttachment($doc, $root, $item)
	{
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
	 * Adds the "entry" elements for the DFRN protocol
	 *
	 * @param DOMDocument $doc     XML document
	 * @param string      $type    "text" or "html"
	 * @param array       $item    Item element
	 * @param array       $owner   Owner record
	 * @param bool        $comment Trigger the sending of the "comment" element
	 * @param int         $cid     Contact ID of the recipient
	 * @param bool        $single  If set, the entry is created as an XML document with a single "entry" element
	 *
	 * @return null|\DOMElement XML entry object
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find proper type-hints
	 */
	private static function entry(DOMDocument $doc, $type, array $item, array $owner, $comment = false, $cid = 0, $single = false)
	{
		$mentioned = [];

		if (!$item['parent']) {
			Logger::notice('Item without parent found.', ['type' => $type, 'item' => $item]);
			return null;
		}

		if ($item['deleted']) {
			$attributes = ["ref" => $item['uri'], "when" => DateTimeFormat::utc($item['edited'] . '+00:00', DateTimeFormat::ATOM)];
			return XML::createElement($doc, "at:deleted-entry", "", $attributes);
		}

		if (!$single) {
			$entry = $doc->createElement("entry");
		} else {
			$entry = $doc->createElementNS(ActivityNamespace::ATOM1, 'entry');
			$doc->appendChild($entry);

			$entry->setAttribute("xmlns:thr", ActivityNamespace::THREAD);
			$entry->setAttribute("xmlns:at", ActivityNamespace::TOMB);
			$entry->setAttribute("xmlns:media", ActivityNamespace::MEDIA);
			$entry->setAttribute("xmlns:dfrn", ActivityNamespace::DFRN);
			$entry->setAttribute("xmlns:activity", ActivityNamespace::ACTIVITY);
			$entry->setAttribute("xmlns:georss", ActivityNamespace::GEORSS);
			$entry->setAttribute("xmlns:poco", ActivityNamespace::POCO);
			$entry->setAttribute("xmlns:ostatus", ActivityNamespace::OSTATUS);
			$entry->setAttribute("xmlns:statusnet", ActivityNamespace::STATUSNET);
		}

		if ($item['private'] == Item::PRIVATE) {
			$body = Item::fixPrivatePhotos($item['body'], $owner['uid'], $item, $cid);
		} else {
			$body = $item['body'];
		}

		// Remove the abstract element. It is only locally important.
		$body = BBCode::stripAbstract($body);

		$htmlbody = '';
		if ($type == 'html') {
			$htmlbody = $body;

			if ($item['title'] != "") {
				$htmlbody = "[b]" . $item['title'] . "[/b]\n\n" . $htmlbody;
			}

			$htmlbody = BBCode::convert($htmlbody, false, BBCode::OSTATUS);
		}

		$author = self::addEntryAuthor($doc, "author", $item["author-link"], $item);
		$entry->appendChild($author);

		$dfrnowner = self::addEntryAuthor($doc, "dfrn:owner", $item["owner-link"], $item);
		$entry->appendChild($dfrnowner);

		if ($item['gravity'] != GRAVITY_PARENT) {
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);
			$parent = Item::selectFirst(['guid', 'plink'], ['uri' => $parent_item, 'uid' => $item['uid']]);
			if (DBA::isResult($parent)) {
				$attributes = ["ref" => $parent_item, "type" => "text/html",
					"href" => $parent['plink'],
					"dfrn:diaspora_guid" => $parent['guid']];
				XML::addElement($doc, $entry, "thr:in-reply-to", "", $attributes);
			}
		}

		// Add conversation data. This is used for OStatus
		$conversation_href = DI::baseUrl()."/display/".$item["parent-guid"];
		$conversation_uri = $conversation_href;

		if (isset($parent_item)) {
			$conversation = DBA::selectFirst('conversation', ['conversation-uri', 'conversation-href'], ['item-uri' => $item['parent-uri']]);
			if (DBA::isResult($conversation)) {
				if ($conversation['conversation-uri'] != '') {
					$conversation_uri = $conversation['conversation-uri'];
				}
				if ($conversation['conversation-href'] != '') {
					$conversation_href = $conversation['conversation-href'];
				}
			}
		}

		$attributes = [
				"href" => $conversation_href,
				"ref" => $conversation_uri];

		XML::addElement($doc, $entry, "ostatus:conversation", $conversation_uri, $attributes);

		XML::addElement($doc, $entry, "id", $item["uri"]);
		XML::addElement($doc, $entry, "title", $item["title"]);

		XML::addElement($doc, $entry, "published", DateTimeFormat::utc($item["created"] . "+00:00", DateTimeFormat::ATOM));
		XML::addElement($doc, $entry, "updated", DateTimeFormat::utc($item["edited"] . "+00:00", DateTimeFormat::ATOM));

		// "dfrn:env" is used to read the content
		XML::addElement($doc, $entry, "dfrn:env", Strings::base64UrlEncode($body, true));

		// The "content" field is not read by the receiver. We could remove it when the type is "text"
		// We keep it at the moment, maybe there is some old version that doesn't read "dfrn:env"
		XML::addElement($doc, $entry, "content", (($type == 'html') ? $htmlbody : $body), ["type" => $type]);

		// We save this value in "plink". Maybe we should read it from there as well?
		XML::addElement(
			$doc,
			$entry,
			"link",
			"",
			["rel" => "alternate", "type" => "text/html",
				 "href" => DI::baseUrl() . "/display/" . $item["guid"]]
		);

		// "comment-allow" is some old fashioned stuff for old Friendica versions.
		// It is included in the rewritten code for completeness
		if ($comment) {
			XML::addElement($doc, $entry, "dfrn:comment-allow", 1);
		}

		if ($item['location']) {
			XML::addElement($doc, $entry, "dfrn:location", $item['location']);
		}

		if ($item['coord']) {
			XML::addElement($doc, $entry, "georss:point", $item['coord']);
		}

		if ($item['private']) {
			// Friendica versions prior to 2020.3 can't handle "unlisted" properly. So we can only transmit public and private
			XML::addElement($doc, $entry, "dfrn:private", ($item['private'] == Item::PRIVATE ? Item::PRIVATE : Item::PUBLIC));
			XML::addElement($doc, $entry, "dfrn:unlisted", $item['private'] == Item::UNLISTED);
		}

		if ($item['extid']) {
			XML::addElement($doc, $entry, "dfrn:extid", $item['extid']);
		}

		if ($item['post-type'] == Item::PT_PAGE) {
			XML::addElement($doc, $entry, "dfrn:bookmark", "true");
		}

		if ($item['app']) {
			XML::addElement($doc, $entry, "statusnet:notice_info", "", ["local_id" => $item['id'], "source" => $item['app']]);
		}

		XML::addElement($doc, $entry, "dfrn:diaspora_guid", $item["guid"]);

		// The signed text contains the content in Markdown, the sender handle and the signatur for the content
		// It is needed for relayed comments to Diaspora.
		if ($item['signed_text']) {
			$sign = base64_encode(json_encode(['signed_text' => $item['signed_text'],'signature' => '','signer' => '']));
			XML::addElement($doc, $entry, "dfrn:diaspora_signature", $sign);
		}

		XML::addElement($doc, $entry, "activity:verb", self::constructVerb($item));

		if ($item['object-type'] != "") {
			XML::addElement($doc, $entry, "activity:object-type", $item['object-type']);
		} elseif ($item['gravity'] == GRAVITY_PARENT) {
			XML::addElement($doc, $entry, "activity:object-type", Activity\ObjectType::NOTE);
		} else {
			XML::addElement($doc, $entry, "activity:object-type", Activity\ObjectType::COMMENT);
		}

		$actobj = self::createActivity($doc, "activity:object", $item['object']);
		if ($actobj) {
			$entry->appendChild($actobj);
		}

		$actarg = self::createActivity($doc, "activity:target", $item['target']);
		if ($actarg) {
			$entry->appendChild($actarg);
		}

		$tags = Tag::getByURIId($item['uri-id']);

		if (count($tags)) {
			foreach ($tags as $tag) {
				if (($type != 'html') || ($tag['type'] == Tag::HASHTAG)) {
					XML::addElement($doc, $entry, "category", "", ["scheme" => "X-DFRN:" . Tag::TAG_CHARACTER[$tag['type']] . ":" . $tag['url'], "term" => $tag['name']]);
				}
				if ($tag['type'] != Tag::HASHTAG) {
					$mentioned[$tag['url']] = $tag['url'];
				}
			}
		}

		foreach ($mentioned as $mention) {
			$condition = ['uid' => $owner["uid"], 'nurl' => Strings::normaliseLink($mention)];
			$contact = DBA::selectFirst('contact', ['forum', 'prv'], $condition);

			if (DBA::isResult($contact) && ($contact["forum"] || $contact["prv"])) {
				XML::addElement(
					$doc,
					$entry,
					"link",
					"",
					["rel" => "mentioned",
							"ostatus:object-type" => Activity\ObjectType::GROUP,
							"href" => $mention]
				);
			} else {
				XML::addElement(
					$doc,
					$entry,
					"link",
					"",
					["rel" => "mentioned",
							"ostatus:object-type" => Activity\ObjectType::PERSON,
							"href" => $mention]
				);
			}
		}

		self::getAttachment($doc, $entry, $item);

		return $entry;
	}

	/**
	 * encrypts data via AES
	 *
	 * @param string $data The data that is to be encrypted
	 * @param string $key  The AES key
	 *
	 * @return string encrypted data
	 */
	private static function aesEncrypt($data, $key)
	{
		return openssl_encrypt($data, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
	}

	/**
	 * decrypts data via AES
	 *
	 * @param string $encrypted The encrypted data
	 * @param string $key       The AES key
	 *
	 * @return string decrypted data
	 */
	public static function aesDecrypt($encrypted, $key)
	{
		return openssl_decrypt($encrypted, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
	}

	/**
	 * Delivers the atom content to the contacts
	 *
	 * @param array  $owner    Owner record
	 * @param array  $contact  Contact record of the receiver
	 * @param string $atom     Content that will be transmitted
	 * @param bool   $dissolve (to be documented)
	 *
	 * @return int Deliver status. Negative values mean an error.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Add array type-hint for $owner, $contact
	 */
	public static function deliver($owner, $contact, $atom, $dissolve = false)
	{
		$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

		if ($contact['duplex'] && $contact['dfrn-id']) {
			$idtosend = '0:' . $orig_id;
		}
		if ($contact['duplex'] && $contact['issued-id']) {
			$idtosend = '1:' . $orig_id;
		}

		$rino = DI::config()->get('system', 'rino_encrypt');
		$rino = intval($rino);

		Logger::log("Local rino version: ". $rino, Logger::DEBUG);

		$ssl_val = intval(DI::config()->get('system', 'ssl_policy'));

		switch ($ssl_val) {
			case BaseURL::SSL_POLICY_FULL:
				$ssl_policy = 'full';
				break;
			case BaseURL::SSL_POLICY_SELFSIGN:
				$ssl_policy = 'self';
				break;
			case BaseURL::SSL_POLICY_NONE:
			default:
				$ssl_policy = 'none';
				break;
		}

		$url = $contact['notify'] . '&dfrn_id=' . $idtosend . '&dfrn_version=' . DFRN_PROTOCOL_VERSION . (($rino) ? '&rino='.$rino : '');

		Logger::log('dfrn_deliver: ' . $url);

		$curlResult = DI::httpRequest()->get($url);

		if ($curlResult->isTimeout()) {
			return -2; // timed out
		}

		$xml = $curlResult->getBody();

		$curl_stat = $curlResult->getReturnCode();
		if (empty($curl_stat)) {
			return -3; // timed out
		}

		Logger::log('dfrn_deliver: ' . $xml, Logger::DATA);

		if (empty($xml)) {
			return 3;
		}

		if (strpos($xml, '<?xml') === false) {
			Logger::log('dfrn_deliver: no valid XML returned');
			Logger::log('dfrn_deliver: returned XML: ' . $xml, Logger::DATA);
			return 3;
		}

		$res = XML::parseString($xml);

		if (!is_object($res) || (intval($res->status) != 0) || !strlen($res->challenge) || !strlen($res->dfrn_id)) {
			if (empty($res->status)) {
				$status = 3;
			} else {
				$status = $res->status;
			}

			return $status;
		}

		$postvars     = [];
		$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
		$challenge    = hex2bin((string) $res->challenge);
		$perm         = (($res->perm) ? $res->perm : null);
		$dfrn_version = floatval($res->dfrn_version ?: 2.0);
		$rino_remote_version = intval($res->rino);
		$page         = (($owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? 1 : 0);

		Logger::log("Remote rino version: ".$rino_remote_version." for ".$contact["url"], Logger::DEBUG);

		if ($owner['page-flags'] == User::PAGE_FLAGS_PRVGROUP) {
			$page = 2;
		}

		$final_dfrn_id = '';

		if ($perm) {
			if ((($perm == 'rw') && !intval($contact['writable']))
				|| (($perm == 'r') && intval($contact['writable']))
			) {
				DBA::update('contact', ['writable' => ($perm == 'rw')], ['id' => $contact['id']]);

				$contact['writable'] = (string) 1 - intval($contact['writable']);
			}
		}

		if (($contact['duplex'] && strlen($contact['pubkey']))
			|| ($owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY && strlen($contact['pubkey']))
			|| ($contact['rel'] == Contact::SHARING && strlen($contact['pubkey']))
		) {
			openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
			openssl_public_decrypt($challenge, $postvars['challenge'], $contact['pubkey']);
		} else {
			openssl_private_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
			openssl_private_decrypt($challenge, $postvars['challenge'], $contact['prvkey']);
		}

		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

		if (strpos($final_dfrn_id, ':') == 1) {
			$final_dfrn_id = substr($final_dfrn_id, 2);
		}

		if ($final_dfrn_id != $orig_id) {
			Logger::log('dfrn_deliver: wrong dfrn_id.');
			// did not decode properly - cannot trust this site
			return 3;
		}

		$postvars['dfrn_id']      = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		if ($dissolve) {
			$postvars['dissolve'] = '1';
		}

		if ((($contact['rel']) && ($contact['rel'] != Contact::SHARING) && (! $contact['blocked'])) || ($owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
			$postvars['data'] = $atom;
			$postvars['perm'] = 'rw';
		} else {
			$postvars['data'] = str_replace('<dfrn:comment-allow>1', '<dfrn:comment-allow>0', $atom);
			$postvars['perm'] = 'r';
		}

		$postvars['ssl_policy'] = $ssl_policy;

		if ($page) {
			$postvars['page'] = $page;
		}


		if ($rino > 0 && $rino_remote_version > 0 && (! $dissolve)) {
			Logger::log('rino version: '. $rino_remote_version);

			switch ($rino_remote_version) {
				case 1:
					$key = openssl_random_pseudo_bytes(16);
					$data = self::aesEncrypt($postvars['data'], $key);
					break;

				default:
					Logger::log("rino: invalid requested version '$rino_remote_version'");
					return -8;
			}

			$postvars['rino'] = $rino_remote_version;
			$postvars['data'] = bin2hex($data);

			if ($dfrn_version >= 2.1) {
				if (($contact['duplex'] && strlen($contact['pubkey']))
					|| ($owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY && strlen($contact['pubkey']))
					|| ($contact['rel'] == Contact::SHARING && strlen($contact['pubkey']))
				) {
					openssl_public_encrypt($key, $postvars['key'], $contact['pubkey']);
				} else {
					openssl_private_encrypt($key, $postvars['key'], $contact['prvkey']);
				}
			} else {
				if (($contact['duplex'] && strlen($contact['prvkey'])) || ($owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
					openssl_private_encrypt($key, $postvars['key'], $contact['prvkey']);
				} else {
					openssl_public_encrypt($key, $postvars['key'], $contact['pubkey']);
				}
			}

			Logger::log('md5 rawkey ' . md5($postvars['key']));

			$postvars['key'] = bin2hex($postvars['key']);
		}


		Logger::debug('dfrn_deliver', ['post' => $postvars]);

		$postResult = DI::httpRequest()->post($contact['notify'], $postvars);

		$xml = $postResult->getBody();

		Logger::log('dfrn_deliver: ' . "RECEIVED: " . $xml, Logger::DATA);

		$curl_stat = $postResult->getReturnCode();
		if (empty($curl_stat) || empty($xml)) {
			return -9; // timed out
		}

		if (($curl_stat == 503) && stristr($postResult->getHeader(), 'retry-after')) {
			return -10;
		}

		if (strpos($xml, '<?xml') === false) {
			Logger::log('dfrn_deliver: phase 2: no valid XML returned');
			Logger::log('dfrn_deliver: phase 2: returned XML: ' . $xml, Logger::DATA);
			return 3;
		}

		$res = XML::parseString($xml);

		if (!isset($res->status)) {
			return -11;
		}

		// Possibly old servers had returned an empty value when everything was okay
		if (empty($res->status)) {
			$res->status = 200;
		}

		if (!empty($res->message)) {
			Logger::log('Delivery returned status '.$res->status.' - '.$res->message, Logger::DEBUG);
		}

		return intval($res->status);
	}

	/**
	 * Transmits atom content to the contacts via the Diaspora transport layer
	 *
	 * @param array  $owner   Owner record
	 * @param array  $contact Contact record of the receiver
	 * @param string $atom    Content that will be transmitted
	 *
	 * @param bool   $public_batch
	 * @return int Deliver status. Negative values mean an error.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function transmit($owner, $contact, $atom, $public_batch = false)
	{
		if (!$public_batch) {
			if (empty($contact['addr'])) {
				Logger::log('Empty contact handle for ' . $contact['id'] . ' - ' . $contact['url'] . ' - trying to update it.');
				if (Contact::updateFromProbe($contact['id'])) {
					$new_contact = DBA::selectFirst('contact', ['addr'], ['id' => $contact['id']]);
					$contact['addr'] = $new_contact['addr'];
				}

				if (empty($contact['addr'])) {
					Logger::log('Unable to find contact handle for ' . $contact['id'] . ' - ' . $contact['url']);
					return -21;
				}
			}

			$fcontact = FContact::getByURL($contact['addr']);
			if (empty($fcontact)) {
				Logger::log('Unable to find contact details for ' . $contact['id'] . ' - ' . $contact['addr']);
				return -22;
			}
			$pubkey = $fcontact['pubkey'];
		} else {
			$pubkey = '';
		}

		$envelope = Diaspora::buildMessage($atom, $owner, $contact, $owner['uprvkey'], $pubkey, $public_batch);

		// Create the endpoint for public posts. This is some WIP and should later be added to the probing
		if ($public_batch && empty($contact["batch"])) {
			$parts = parse_url($contact["notify"]);
			$path_parts = explode('/', $parts['path']);
			array_pop($path_parts);
			$parts['path'] =  implode('/', $path_parts);
			$contact["batch"] = Network::unparseURL($parts);
		}

		$dest_url = ($public_batch ? $contact["batch"] : $contact["notify"]);

		if (empty($dest_url)) {
			Logger::info('Empty destination', ['public' => $public_batch, 'contact' => $contact]);
			return -24;
		}

		$content_type = ($public_batch ? "application/magic-envelope+xml" : "application/json");

		$postResult = DI::httpRequest()->post($dest_url, $envelope, ["Content-Type: " . $content_type]);
		$xml = $postResult->getBody();

		$curl_stat = $postResult->getReturnCode();
		if (empty($curl_stat) || empty($xml)) {
			Logger::log('Empty answer from ' . $contact['id'] . ' - ' . $dest_url);
			return -9; // timed out
		}

		if (($curl_stat == 503) && (stristr($postResult->getHeader(), 'retry-after'))) {
			return -10;
		}

		if (strpos($xml, '<?xml') === false) {
			Logger::log('No valid XML returned from ' . $contact['id'] . ' - ' . $dest_url);
			Logger::log('Returned XML: ' . $xml, Logger::DATA);
			return 3;
		}

		$res = XML::parseString($xml);

		if (empty($res->status)) {
			return -23;
		}

		if (!empty($res->message)) {
			Logger::log('Transmit to ' . $dest_url . ' returned status '.$res->status.' - '.$res->message, Logger::DEBUG);
		}

		return intval($res->status);
	}

	/**
	 * Fetch the author data from head or entry items
	 *
	 * @param object $xpath     XPath object
	 * @param object $context   In which context should the data be searched
	 * @param array  $importer  Record of the importer user mixed with contact of the content
	 * @param string $element   Element name from which the data is fetched
	 * @param bool   $onlyfetch Should the data only be fetched or should it update the contact record as well
	 * @param string $xml       optional, default empty
	 *
	 * @return array Relevant data of the author
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find good type-hints for all parameter
	 */
	private static function fetchauthor($xpath, $context, $importer, $element, $onlyfetch, $xml = "")
	{
		$author = [];
		$author["name"] = XML::getFirstNodeValue($xpath, $element."/atom:name/text()", $context);
		$author["link"] = XML::getFirstNodeValue($xpath, $element."/atom:uri/text()", $context);

		$fields = ['id', 'uid', 'url', 'network', 'avatar-date', 'avatar', 'name-date', 'uri-date', 'addr',
			'name', 'nick', 'about', 'location', 'keywords', 'xmpp', 'bdyear', 'bd', 'hidden', 'contact-type'];
		$condition = ["`uid` = ? AND `nurl` = ? AND `network` != ?",
			$importer["importer_uid"], Strings::normaliseLink($author["link"]), Protocol::STATUSNET];
		$contact_old = DBA::selectFirst('contact', $fields, $condition);

		if (DBA::isResult($contact_old)) {
			$author["contact-id"] = $contact_old["id"];
			$author["network"] = $contact_old["network"];
		} else {
			if (!$onlyfetch) {
				Logger::debug("Contact ".$author["link"]." wasn't found for user ".$importer["importer_uid"]." XML: ".$xml);
			}

			$author["contact-unknown"] = true;
			$author["contact-id"] = $importer["id"];
			$author["network"] = $importer["network"];
			$onlyfetch = true;
		}

		// Until now we aren't serving different sizes - but maybe later
		$avatarlist = [];
		/// @todo check if "avatar" or "photo" would be the best field in the specification
		$avatars = $xpath->query($element . "/atom:link[@rel='avatar']", $context);
		foreach ($avatars as $avatar) {
			$href = "";
			$width = 0;
			foreach ($avatar->attributes as $attributes) {
				/// @TODO Rewrite these similar if() to one switch
				if ($attributes->name == "href") {
					$href = $attributes->textContent;
				}
				if ($attributes->name == "width") {
					$width = $attributes->textContent;
				}
				if ($attributes->name == "updated") {
					$author["avatar-date"] = $attributes->textContent;
				}
			}
			if (($width > 0) && ($href != "")) {
				$avatarlist[$width] = $href;
			}
		}

		if (count($avatarlist) > 0) {
			krsort($avatarlist);
			$author["avatar"] = current($avatarlist);
		}

		if (empty($author['avatar']) && !empty($author['link'])) {
			$cid = Contact::getIdForURL($author['link'], 0);
			if (!empty($cid)) {
				$contact = DBA::selectFirst('contact', ['avatar'], ['id' => $cid]);
				if (DBA::isResult($contact)) {
					$author['avatar'] = $contact['avatar'];
				}
			}
		}

		if (empty($author['avatar'])) {
			Logger::log('Empty author: ' . $xml);
			$author['avatar'] = '';
		}

		if (DBA::isResult($contact_old) && !$onlyfetch) {
			Logger::log("Check if contact details for contact " . $contact_old["id"] . " (" . $contact_old["nick"] . ") have to be updated.", Logger::DEBUG);

			$poco = ["url" => $contact_old["url"], "network" => $contact_old["network"]];

			// When was the last change to name or uri?
			$name_element = $xpath->query($element . "/atom:name", $context)->item(0);
			foreach ($name_element->attributes as $attributes) {
				if ($attributes->name == "updated") {
					$poco["name-date"] = $attributes->textContent;
				}
			}

			$link_element = $xpath->query($element . "/atom:link", $context)->item(0);
			foreach ($link_element->attributes as $attributes) {
				if ($attributes->name == "updated") {
					$poco["uri-date"] = $attributes->textContent;
				}
			}

			// Update contact data
			$value = XML::getFirstNodeValue($xpath, $element . "/dfrn:handle/text()", $context);
			if ($value != "") {
				$poco["addr"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:displayName/text()", $context);
			if ($value != "") {
				$poco["name"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:preferredUsername/text()", $context);
			if ($value != "") {
				$poco["nick"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:note/text()", $context);
			if ($value != "") {
				$poco["about"] = $value;
			}

			$value = XML::getFirstNodeValue($xpath, $element . "/poco:address/poco:formatted/text()", $context);
			if ($value != "") {
				$poco["location"] = $value;
			}

			/// @todo Only search for elements with "poco:type" = "xmpp"
			$value = XML::getFirstNodeValue($xpath, $element . "/poco:ims/poco:value/text()", $context);
			if ($value != "") {
				$poco["xmpp"] = $value;
			}

			/// @todo Add support for the following fields that we don't support by now in the contact table:
			/// - poco:utcOffset
			/// - poco:urls
			/// - poco:locality
			/// - poco:region
			/// - poco:country

			// If the "hide" element is present then the profile isn't searchable.
			$hide = intval(XML::getFirstNodeValue($xpath, $element . "/dfrn:hide/text()", $context) == "true");

			Logger::log("Hidden status for contact " . $contact_old["url"] . ": " . $hide, Logger::DEBUG);

			// If the contact isn't searchable then set the contact to "hidden".
			// Problem: This can be manually overridden by the user.
			if ($hide) {
				$contact_old["hidden"] = true;
			}

			// Save the keywords into the contact table
			$tags = [];
			$tagelements = $xpath->evaluate($element . "/poco:tags/text()", $context);
			foreach ($tagelements as $tag) {
				$tags[$tag->nodeValue] = $tag->nodeValue;
			}

			if (count($tags)) {
				$poco["keywords"] = implode(", ", $tags);
			}

			// "dfrn:birthday" contains the birthday converted to UTC
			$birthday = XML::getFirstNodeValue($xpath, $element . "/poco:birthday/text()", $context);

			if (strtotime($birthday) > time()) {
				$bd_timestamp = strtotime($birthday);

				$poco["bdyear"] = date("Y", $bd_timestamp);
			}

			// "poco:birthday" is the birthday in the format "yyyy-mm-dd"
			$value = XML::getFirstNodeValue($xpath, $element . "/poco:birthday/text()", $context);

			if (!in_array($value, ["", "0000-00-00", DBA::NULL_DATE])) {
				$bdyear = date("Y");
				$value = str_replace(["0000", "0001"], $bdyear, $value);

				if (strtotime($value) < time()) {
					$value = str_replace($bdyear, $bdyear + 1, $value);
				}

				$poco["bd"] = $value;
			}

			$contact = array_merge($contact_old, $poco);

			if ($contact_old["bdyear"] != $contact["bdyear"]) {
				Event::createBirthday($contact, $birthday);
			}

			$fields = ['name' => $contact['name'], 'nick' => $contact['nick'], 'about' => $contact['about'],
				'location' => $contact['location'], 'addr' => $contact['addr'], 'keywords' => $contact['keywords'],
				'bdyear' => $contact['bdyear'], 'bd' => $contact['bd'], 'hidden' => $contact['hidden'],
				'xmpp' => $contact['xmpp'], 'name-date' => DateTimeFormat::utc($contact['name-date']),
				'unsearchable' => $contact['hidden'], 'uri-date' => DateTimeFormat::utc($contact['uri-date'])];

			DBA::update('contact', $fields, ['id' => $contact['id'], 'network' => $contact['network']], $contact_old);

			// Update the public contact. Don't set the "hidden" value, this is used differently for public contacts
			unset($fields['hidden']);
			$condition = ['uid' => 0, 'nurl' => Strings::normaliseLink($contact_old['url'])];
			DBA::update('contact', $fields, $condition, true);

			Contact::updateAvatar($contact['id'], $author['avatar']);

			$pcid = Contact::getIdForURL($contact_old['url']);
			if (!empty($pcid)) {
				Contact::updateAvatar($pcid, $author['avatar']);
			}
		}

		return $author;
	}

	/**
	 * Transforms activity objects into an XML string
	 *
	 * @param object $xpath    XPath object
	 * @param object $activity Activity object
	 * @param string $element  element name
	 *
	 * @return string XML string
	 * @todo Find good type-hints for all parameter
	 */
	private static function transformActivity($xpath, $activity, $element)
	{
		if (!is_object($activity)) {
			return "";
		}

		$obj_doc = new DOMDocument("1.0", "utf-8");
		$obj_doc->formatOutput = true;

		$obj_element = $obj_doc->createElementNS( ActivityNamespace::ATOM1, $element);

		$activity_type = $xpath->query("activity:object-type/text()", $activity)->item(0)->nodeValue;
		XML::addElement($obj_doc, $obj_element, "type", $activity_type);

		$id = $xpath->query("atom:id", $activity)->item(0);
		if (is_object($id)) {
			$obj_element->appendChild($obj_doc->importNode($id, true));
		}

		$title = $xpath->query("atom:title", $activity)->item(0);
		if (is_object($title)) {
			$obj_element->appendChild($obj_doc->importNode($title, true));
		}

		$links = $xpath->query("atom:link", $activity);
		if (is_object($links)) {
			foreach ($links as $link) {
				$obj_element->appendChild($obj_doc->importNode($link, true));
			}
		}

		$content = $xpath->query("atom:content", $activity)->item(0);
		if (is_object($content)) {
			$obj_element->appendChild($obj_doc->importNode($content, true));
		}

		$obj_doc->appendChild($obj_element);

		$objxml = $obj_doc->saveXML($obj_element);

		/// @todo This isn't totally clean. We should find a way to transform the namespaces
		$objxml = str_replace("<".$element.' xmlns="http://www.w3.org/2005/Atom">', "<".$element.">", $objxml);
		return($objxml);
	}

	/**
	 * Processes the mail elements
	 *
	 * @param object $xpath    XPath object
	 * @param object $mail     mail elements
	 * @param array  $importer Record of the importer user mixed with contact of the content
	 * @return void
	 * @throws \Exception
	 * @todo  Find good type-hints for all parameter
	 */
	private static function processMail($xpath, $mail, $importer)
	{
		Logger::log("Processing mails");

		$msg = [];
		$msg["uid"] = $importer["importer_uid"];
		$msg["from-name"] = $xpath->query("dfrn:sender/dfrn:name/text()", $mail)->item(0)->nodeValue;
		$msg["from-url"] = $xpath->query("dfrn:sender/dfrn:uri/text()", $mail)->item(0)->nodeValue;
		$msg["from-photo"] = $xpath->query("dfrn:sender/dfrn:avatar/text()", $mail)->item(0)->nodeValue;
		$msg["contact-id"] = $importer["id"];
		$msg["uri"] = $xpath->query("dfrn:id/text()", $mail)->item(0)->nodeValue;
		$msg["parent-uri"] = $xpath->query("dfrn:in-reply-to/text()", $mail)->item(0)->nodeValue;
		$msg["created"] = DateTimeFormat::utc($xpath->query("dfrn:sentdate/text()", $mail)->item(0)->nodeValue);
		$msg["title"] = $xpath->query("dfrn:subject/text()", $mail)->item(0)->nodeValue;
		$msg["body"] = $xpath->query("dfrn:content/text()", $mail)->item(0)->nodeValue;

		Mail::insert($msg);
	}

	/**
	 * Processes the suggestion elements
	 *
	 * @param object $xpath      XPath object
	 * @param object $suggestion suggestion elements
	 * @param array  $importer   Record of the importer user mixed with contact of the content
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  Find good type-hints for all parameter
	 */
	private static function processSuggestion($xpath, $suggestion, $importer)
	{
		Logger::log('Processing suggestions');

		/// @TODO Rewrite this to one statement
		$suggest = [];
		$suggest['uid'] = $importer['importer_uid'];
		$suggest['cid'] = $importer['id'];
		$suggest['url'] = $xpath->query('dfrn:url/text()', $suggestion)->item(0)->nodeValue;
		$suggest['name'] = $xpath->query('dfrn:name/text()', $suggestion)->item(0)->nodeValue;
		$suggest['photo'] = $xpath->query('dfrn:photo/text()', $suggestion)->item(0)->nodeValue;
		$suggest['request'] = $xpath->query('dfrn:request/text()', $suggestion)->item(0)->nodeValue;
		$suggest['body'] = $xpath->query('dfrn:note/text()', $suggestion)->item(0)->nodeValue;

		// Does our member already have a friend matching this description?

		/*
		 * The valid result means the friend we're about to send a friend
		 * suggestion already has them in their contact, which means no further
		 * action is required.
		 *
		 * @see https://github.com/friendica/friendica/pull/3254#discussion_r107315246
		 */
		$condition = ['nurl' => Strings::normaliseLink($suggest['url']), 'uid' => $suggest['uid']];
		if (DBA::exists('contact', $condition)) {
			return false;
		}
		// Do we already have an fcontact record for this person?

		$fid = 0;
		$fcontact = DBA::selectFirst('fcontact', ['id'], ['url' => $suggest['url']]);
		if (DBA::isResult($fcontact)) {
			$fid = $fcontact['id'];

			// OK, we do. Do we already have an introduction for this person?
			if (DBA::exists('intro', ['uid' => $suggest['uid'], 'fid' => $fid])) {
				/*
				 * The valid result means the friend we're about to send a friend
				 * suggestion already has them in their contact, which means no further
				 * action is required.
				 *
				 * @see https://github.com/friendica/friendica/pull/3254#discussion_r107315246
				 */
				return false;
			}
		}

		if (!$fid) {
			$fields = ['name' => $suggest['name'], 'url' => $suggest['url'],
				'photo' => $suggest['photo'], 'request' => $suggest['request']];
			DBA::insert('fcontact', $fields);
			$fid = DBA::lastInsertId();
		}

		/*
		 * If no record in fcontact is found, below INSERT statement will not
		 * link an introduction to it.
		 */
		if (empty($fid)) {
			// Database record did not get created. Quietly give up.
			exit();
		}

		$hash = Strings::getRandomHex();

		$fields = ['uid' => $suggest['uid'], 'fid' => $fid, 'contact-id' => $suggest['cid'],
			'note' => $suggest['body'], 'hash' => $hash, 'datetime' => DateTimeFormat::utcNow(), 'blocked' => false];
		DBA::insert('intro', $fields);

		notification(
			[
				'type'         => Type::SUGGEST,
				'notify_flags' => $importer['notify-flags'],
				'language'     => $importer['language'],
				'to_name'      => $importer['username'],
				'to_email'     => $importer['email'],
				'uid'          => $importer['importer_uid'],
				'item'         => $suggest,
				'link'         => DI::baseUrl().'/notifications/intros',
				'source_name'  => $importer['name'],
				'source_link'  => $importer['url'],
				'source_photo' => $importer['photo'],
				'verb'         => Activity::REQ_FRIEND,
				'otype'        => 'intro']
		);

		return true;
	}

	/**
	 * Processes the relocation elements
	 *
	 * @param object $xpath      XPath object
	 * @param object $relocation relocation elements
	 * @param array  $importer   Record of the importer user mixed with contact of the content
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Find good type-hints for all parameter
	 */
	private static function processRelocation($xpath, $relocation, $importer)
	{
		Logger::log("Processing relocations");

		/// @TODO Rewrite this to one statement
		$relocate = [];
		$relocate["uid"] = $importer["importer_uid"];
		$relocate["cid"] = $importer["id"];
		$relocate["url"] = $xpath->query("dfrn:url/text()", $relocation)->item(0)->nodeValue;
		$relocate["addr"] = $xpath->query("dfrn:addr/text()", $relocation)->item(0)->nodeValue;
		$relocate["name"] = $xpath->query("dfrn:name/text()", $relocation)->item(0)->nodeValue;
		$relocate["avatar"] = $xpath->query("dfrn:avatar/text()", $relocation)->item(0)->nodeValue;
		$relocate["photo"] = $xpath->query("dfrn:photo/text()", $relocation)->item(0)->nodeValue;
		$relocate["thumb"] = $xpath->query("dfrn:thumb/text()", $relocation)->item(0)->nodeValue;
		$relocate["micro"] = $xpath->query("dfrn:micro/text()", $relocation)->item(0)->nodeValue;
		$relocate["request"] = $xpath->query("dfrn:request/text()", $relocation)->item(0)->nodeValue;
		$relocate["confirm"] = $xpath->query("dfrn:confirm/text()", $relocation)->item(0)->nodeValue;
		$relocate["notify"] = $xpath->query("dfrn:notify/text()", $relocation)->item(0)->nodeValue;
		$relocate["poll"] = $xpath->query("dfrn:poll/text()", $relocation)->item(0)->nodeValue;
		$relocate["sitepubkey"] = $xpath->query("dfrn:sitepubkey/text()", $relocation)->item(0)->nodeValue;

		if (($relocate["avatar"] == "") && ($relocate["photo"] != "")) {
			$relocate["avatar"] = $relocate["photo"];
		}

		if ($relocate["addr"] == "") {
			$relocate["addr"] = preg_replace("=(https?://)(.*)/profile/(.*)=ism", "$3@$2", $relocate["url"]);
		}

		// update contact
		$r = q(
			"SELECT `photo`, `url` FROM `contact` WHERE `id` = %d AND `uid` = %d",
			intval($importer["id"]),
			intval($importer["importer_uid"])
		);

		if (!DBA::isResult($r)) {
			Logger::log("Query failed to execute, no result returned in " . __FUNCTION__);
			return false;
		}

		$old = $r[0];

		// Update the contact table. We try to find every entry.
		$fields = ['name' => $relocate["name"], 'avatar' => $relocate["avatar"],
			'url' => $relocate["url"], 'nurl' => Strings::normaliseLink($relocate["url"]),
			'addr' => $relocate["addr"], 'request' => $relocate["request"],
			'confirm' => $relocate["confirm"], 'notify' => $relocate["notify"],
			'poll' => $relocate["poll"], 'site-pubkey' => $relocate["sitepubkey"]];
		$condition = ["(`id` = ?) OR (`nurl` = ?)", $importer["id"], Strings::normaliseLink($old["url"])];

		DBA::update('contact', $fields, $condition);

		Contact::updateAvatar($importer["id"], $relocate["avatar"], true);

		Logger::log('Contacts are updated.');

		/// @TODO
		/// merge with current record, current contents have priority
		/// update record, set url-updated
		/// update profile photos
		/// schedule a scan?
		return true;
	}

	/**
	 * Updates an item
	 *
	 * @param array $current   the current item record
	 * @param array $item      the new item record
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param int   $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @return mixed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  set proper type-hints (array?)
	 */
	private static function updateContent($current, $item, $importer, $entrytype)
	{
		$changed = false;

		if (self::isEditedTimestampNewer($current, $item)) {
			// do not accept (ignore) an earlier edit than one we currently have.
			if (DateTimeFormat::utc($item["edited"]) < $current["edited"]) {
				return false;
			}

			$fields = ['title' => $item['title'] ?? '', 'body' => $item['body'] ?? '',
					'changed' => DateTimeFormat::utcNow(),
					'edited' => DateTimeFormat::utc($item["edited"])];

			$condition = ["`uri` = ? AND `uid` IN (0, ?)", $item["uri"], $importer["importer_uid"]];
			Item::update($fields, $condition);

			$changed = true;
		}
		return $changed;
	}

	/**
	 * Detects the entry type of the item
	 *
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param array $item     the new item record
	 *
	 * @return int Is it a toplevel entry, a comment or a relayed comment?
	 * @throws \Exception
	 * @todo  set proper type-hints (array?)
	 */
	private static function getEntryType($importer, $item)
	{
		if ($item["parent-uri"] != $item["uri"]) {
			$community = false;

			if ($importer["page-flags"] == User::PAGE_FLAGS_COMMUNITY || $importer["page-flags"] == User::PAGE_FLAGS_PRVGROUP) {
				$sql_extra = "";
				$community = true;
				Logger::log("possible community action");
			} else {
				$sql_extra = " AND `contact`.`self` AND `item`.`wall` ";
			}

			// was the top-level post for this action written by somebody on this site?
			// Specifically, the recipient?

			$is_a_remote_action = false;

			$parent = Item::selectFirst(['parent-uri'], ['uri' => $item["parent-uri"]]);
			if (DBA::isResult($parent)) {
				$r = q(
					"SELECT `item`.`forum_mode`, `item`.`wall` FROM `item`
					INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
					WHERE `item`.`uri` = '%s' AND (`item`.`parent-uri` = '%s' OR `item`.`thr-parent` = '%s')
					AND `item`.`uid` = %d
					$sql_extra
					LIMIT 1",
					DBA::escape($parent["parent-uri"]),
					DBA::escape($parent["parent-uri"]),
					DBA::escape($parent["parent-uri"]),
					intval($importer["importer_uid"])
				);
				if (DBA::isResult($r)) {
					$is_a_remote_action = true;
				}
			}

			/*
			 * Does this have the characteristics of a community or private group action?
			 * If it's an action to a wall post on a community/prvgroup page it's a
			 * valid community action. Also forum_mode makes it valid for sure.
			 * If neither, it's not.
			 */
			if ($is_a_remote_action && $community && (!$r[0]["forum_mode"]) && (!$r[0]["wall"])) {
				$is_a_remote_action = false;
				Logger::log("not a community action");
			}

			if ($is_a_remote_action) {
				return DFRN::REPLY_RC;
			} else {
				return DFRN::REPLY;
			}
		} else {
			return DFRN::TOP_LEVEL;
		}
	}

	/**
	 * Send a "poke"
	 *
	 * @param array $item      The new item record
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  set proper type-hints (array?)
	 */
	private static function doPoke(array $item, array $importer)
	{
		$verb = urldecode(substr($item["verb"], strpos($item["verb"], "#")+1));
		if (!$verb) {
			return;
		}
		$xo = XML::parseString($item["object"]);

		if (($xo->type == Activity\ObjectType::PERSON) && ($xo->id)) {
			// somebody was poked/prodded. Was it me?
			$Blink = '';
			foreach ($xo->link as $l) {
				$atts = $l->attributes();
				switch ($atts["rel"]) {
					case "alternate":
						$Blink = $atts["href"];
						break;
					default:
						break;
				}
			}

			if ($Blink && Strings::compareLink($Blink, DI::baseUrl() . "/profile/" . $importer["nickname"])) {
				$author = DBA::selectFirst('contact', ['name', 'thumb', 'url'], ['id' => $item['author-id']]);

				$parent = Item::selectFirst(['id'], ['uri' => $item['parent-uri'], 'uid' => $importer["importer_uid"]]);
				$item['parent'] = $parent['id'];

				// send a notification
				notification(
					[
					"type"         => Type::POKE,
					"notify_flags" => $importer["notify-flags"],
					"language"     => $importer["language"],
					"to_name"      => $importer["username"],
					"to_email"     => $importer["email"],
					"uid"          => $importer["importer_uid"],
					"item"         => $item,
					"link"         => DI::baseUrl()."/display/".urlencode($item['guid']),
					"source_name"  => $author["name"],
					"source_link"  => $author["url"],
					"source_photo" => $author["thumb"],
					"verb"         => $item["verb"],
					"otype"        => "person",
					"activity"     => $verb,
					"parent"       => $item['parent']]
				);
			}
		}
	}

	/**
	 * Processes several actions, depending on the verb
	 *
	 * @param int   $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param array $item      the new item record
	 * @param bool  $is_like   Is the verb a "like"?
	 *
	 * @return bool Should the processing of the entries be continued?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo  set proper type-hints (array?)
	 */
	private static function processVerbs($entrytype, $importer, &$item, &$is_like)
	{
		Logger::log("Process verb ".$item["verb"]." and object-type ".$item["object-type"]." for entrytype ".$entrytype, Logger::DEBUG);

		if (($entrytype == DFRN::TOP_LEVEL) && !empty($importer['id'])) {
			// The filling of the the "contact" variable is done for legcy reasons
			// The functions below are partly used by ostatus.php as well - where we have this variable
			$contact = Contact::selectFirst([], ['id' => $importer['id']]);

			$activity = DI::activity();

			// Big question: Do we need these functions? They were part of the "consume_feed" function.
			// This function once was responsible for DFRN and OStatus.
			if ($activity->match($item["verb"], Activity::FOLLOW)) {
				Logger::log("New follower");
				Contact::addRelationship($importer, $contact, $item);
				return false;
			}
			if ($activity->match($item["verb"], Activity::UNFOLLOW)) {
				Logger::log("Lost follower");
				Contact::removeFollower($importer, $contact, $item);
				return false;
			}
			if ($activity->match($item["verb"], Activity::REQ_FRIEND)) {
				Logger::log("New friend request");
				Contact::addRelationship($importer, $contact, $item, true);
				return false;
			}
			if ($activity->match($item["verb"], Activity::UNFRIEND)) {
				Logger::log("Lost sharer");
				Contact::removeSharer($importer, $contact, $item);
				return false;
			}
		} else {
			if (($item["verb"] == Activity::LIKE)
				|| ($item["verb"] == Activity::DISLIKE)
				|| ($item["verb"] == Activity::ATTEND)
				|| ($item["verb"] == Activity::ATTENDNO)
				|| ($item["verb"] == Activity::ATTENDMAYBE)
				|| ($item["verb"] == Activity::ANNOUNCE)
			) {
				$is_like = true;
				$item["gravity"] = GRAVITY_ACTIVITY;
				// only one like or dislike per person
				// splitted into two queries for performance issues
				$condition = ['uid' => $item["uid"], 'author-id' => $item["author-id"], 'gravity' => GRAVITY_ACTIVITY,
					'verb' => $item["verb"], 'parent-uri' => $item["parent-uri"]];
				if (Item::exists($condition)) {
					return false;
				}

				$condition = ['uid' => $item["uid"], 'author-id' => $item["author-id"], 'gravity' => GRAVITY_ACTIVITY,
					'verb' => $item["verb"], 'thr-parent' => $item["parent-uri"]];
				if (Item::exists($condition)) {
					return false;
				}

				// The owner of an activity must be the author
				$item["owner-name"] = $item["author-name"];
				$item["owner-link"] = $item["author-link"];
				$item["owner-avatar"] = $item["author-avatar"];
				$item["owner-id"] = $item["author-id"];
			} else {
				$is_like = false;
			}

			if (($item["verb"] == Activity::TAG) && ($item["object-type"] == Activity\ObjectType::TAGTERM)) {
				$xo = XML::parseString($item["object"]);
				$xt = XML::parseString($item["target"]);

				if ($xt->type == Activity\ObjectType::NOTE) {
					$item_tag = Item::selectFirst(['id', 'uri-id', 'tag'], ['uri' => $xt->id, 'uid' => $importer["importer_uid"]]);

					if (!DBA::isResult($item_tag)) {
						Logger::log("Query failed to execute, no result returned in " . __FUNCTION__);
						return false;
					}

					// extract tag, if not duplicate, add to parent item
					if ($xo->content) {
						Tag::store($item_tag['uri-id'], Tag::HASHTAG, $xo->content);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Processes the link elements
	 *
	 * @param object $links link elements
	 * @param array  $item  the item record
	 * @return void
	 * @todo set proper type-hints
	 */
	private static function parseLinks($links, &$item)
	{
		$rel = "";
		$href = "";
		$type = "";
		$length = "0";
		$title = "";
		foreach ($links as $link) {
			foreach ($link->attributes as $attributes) {
				switch ($attributes->name) {
					case "href"  : $href   = $attributes->textContent; break;
					case "rel"   : $rel    = $attributes->textContent; break;
					case "type"  : $type   = $attributes->textContent; break;
					case "length": $length = $attributes->textContent; break;
					case "title" : $title  = $attributes->textContent; break;
				}
			}
			if (($rel != "") && ($href != "")) {
				switch ($rel) {
					case "alternate":
						$item["plink"] = $href;
						break;
					case "enclosure":
						if (!empty($item["attach"])) {
							$item["attach"] .= ",";
						} else {
							$item["attach"] = "";
						}

						$item["attach"] .= '[attach]href="' . $href . '" length="' . $length . '" type="' . $type . '" title="' . $title . '"[/attach]';
						break;
				}
			}
		}
	}

	/**
	 * Processes the entry elements which contain the items and comments
	 *
	 * @param array  $header   Array of the header elements that always stay the same
	 * @param object $xpath    XPath object
	 * @param object $entry    entry elements
	 * @param array  $importer Record of the importer user mixed with contact of the content
	 * @param string $xml      xml
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  Add type-hints
	 */
	private static function processEntry($header, $xpath, $entry, $importer, $xml)
	{
		Logger::log("Processing entries");

		$item = $header;

		$item["protocol"] = Conversation::PARCEL_DFRN;

		$item["source"] = $xml;

		// Get the uri
		$item["uri"] = XML::getFirstNodeValue($xpath, "atom:id/text()", $entry);

		$item["edited"] = XML::getFirstNodeValue($xpath, "atom:updated/text()", $entry);

		$current = Item::selectFirst(['id', 'uid', 'edited', 'body'],
			['uri' => $item["uri"], 'uid' => $importer["importer_uid"]]
		);
		// Is there an existing item?
		if (DBA::isResult($current) && !self::isEditedTimestampNewer($current, $item)) {
			Logger::log("Item ".$item["uri"]." (".$item['edited'].") already existed.", Logger::DEBUG);
			return;
		}

		// Fetch the owner
		$owner = self::fetchauthor($xpath, $entry, $importer, "dfrn:owner", true, $xml);

		$owner_unknown = (isset($owner["contact-unknown"]) && $owner["contact-unknown"]);

		$item["owner-name"] = $owner["name"];
		$item["owner-link"] = $owner["link"];
		$item["owner-avatar"] = $owner["avatar"];
		$item["owner-id"] = Contact::getIdForURL($owner["link"], 0);

		// fetch the author
		$author = self::fetchauthor($xpath, $entry, $importer, "atom:author", true, $xml);

		$item["author-name"] = $author["name"];
		$item["author-link"] = $author["link"];
		$item["author-avatar"] = $author["avatar"];
		$item["author-id"] = Contact::getIdForURL($author["link"], 0);

		$item["title"] = XML::getFirstNodeValue($xpath, "atom:title/text()", $entry);

		$item["created"] = XML::getFirstNodeValue($xpath, "atom:published/text()", $entry);

		$item["body"] = XML::getFirstNodeValue($xpath, "dfrn:env/text()", $entry);
		$item["body"] = str_replace([' ',"\t","\r","\n"], ['','','',''], $item["body"]);

		$item["body"] = Strings::base64UrlDecode($item["body"]);

		$item["body"] = BBCode::limitBodySize($item["body"]);

		/// @todo We should check for a repeated post and if we know the repeated author.

		// We don't need the content element since "dfrn:env" is always present
		//$item["body"] = $xpath->query("atom:content/text()", $entry)->item(0)->nodeValue;

		$item["location"] = XML::getFirstNodeValue($xpath, "dfrn:location/text()", $entry);

		$item["coord"] = XML::getFirstNodeValue($xpath, "georss:point", $entry);

		$item["private"] = XML::getFirstNodeValue($xpath, "dfrn:private/text()", $entry);

		$unlisted = XML::getFirstNodeValue($xpath, "dfrn:unlisted/text()", $entry);
		if (!empty($unlisted) && ($item['private'] != Item::PRIVATE)) {
			$item['private'] = Item::UNLISTED;
		}

		$item["extid"] = XML::getFirstNodeValue($xpath, "dfrn:extid/text()", $entry);

		if (XML::getFirstNodeValue($xpath, "dfrn:bookmark/text()", $entry) == "true") {
			$item["post-type"] = Item::PT_PAGE;
		}

		$notice_info = $xpath->query("statusnet:notice_info", $entry);
		if ($notice_info && ($notice_info->length > 0)) {
			foreach ($notice_info->item(0)->attributes as $attributes) {
				if ($attributes->name == "source") {
					$item["app"] = strip_tags($attributes->textContent);
				}
			}
		}

		$item["guid"] = XML::getFirstNodeValue($xpath, "dfrn:diaspora_guid/text()", $entry);

		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);

		Tag::storeFromBody($item['uri-id'], $item["body"]);

		// We store the data from "dfrn:diaspora_signature" in a different table, this is done in "Item::insert"
		$dsprsig = XML::unescape(XML::getFirstNodeValue($xpath, "dfrn:diaspora_signature/text()", $entry));
		if ($dsprsig != "") {
			$signature = json_decode(base64_decode($dsprsig));
			// We don't store the old style signatures anymore that also contained the "signature" and "signer"
			if (!empty($signature->signed_text) && empty($signature->signature) && empty($signature->signer)) {
				$item["diaspora_signed_text"] = $signature->signed_text;
			}
		}

		$item["verb"] = XML::getFirstNodeValue($xpath, "activity:verb/text()", $entry);

		if (XML::getFirstNodeValue($xpath, "activity:object-type/text()", $entry) != "") {
			$item["object-type"] = XML::getFirstNodeValue($xpath, "activity:object-type/text()", $entry);
		}

		$object = $xpath->query("activity:object", $entry)->item(0);
		$item["object"] = self::transformActivity($xpath, $object, "object");

		if (trim($item["object"]) != "") {
			$r = XML::parseString($item["object"]);
			if (isset($r->type)) {
				$item["object-type"] = $r->type;
			}
		}

		$target = $xpath->query("activity:target", $entry)->item(0);
		$item["target"] = self::transformActivity($xpath, $target, "target");

		$categories = $xpath->query("atom:category", $entry);
		if ($categories) {
			foreach ($categories as $category) {
				$term = "";
				$scheme = "";
				foreach ($category->attributes as $attributes) {
					if ($attributes->name == "term") {
						$term = $attributes->textContent;
					}

					if ($attributes->name == "scheme") {
						$scheme = $attributes->textContent;
					}
				}

				if (($term != "") && ($scheme != "")) {
					$parts = explode(":", $scheme);
					if ((count($parts) >= 4) && (array_shift($parts) == "X-DFRN")) {
						$termurl = array_pop($parts);
						$termurl = array_pop($parts) . ':' . $termurl;
						Tag::store($item['uri-id'], Tag::IMPLICIT_MENTION, $term, $termurl);
					}
				}
			}
		}

		$links = $xpath->query("atom:link", $entry);
		if ($links) {
			self::parseLinks($links, $item);
		}

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

		// Is it a reply or a top level posting?
		$item["parent-uri"] = $item["uri"];

		$inreplyto = $xpath->query("thr:in-reply-to", $entry);
		if (is_object($inreplyto->item(0))) {
			foreach ($inreplyto->item(0)->attributes as $attributes) {
				if ($attributes->name == "ref") {
					$item["parent-uri"] = $attributes->textContent;
				}
			}
		}

		// Get the type of the item (Top level post, reply or remote reply)
		$entrytype = self::getEntryType($importer, $item);

		// Now assign the rest of the values that depend on the type of the message
		if (in_array($entrytype, [DFRN::REPLY, DFRN::REPLY_RC])) {
			if (!isset($item["object-type"])) {
				$item["object-type"] = Activity\ObjectType::COMMENT;
			}

			if ($item["contact-id"] != $owner["contact-id"]) {
				$item["contact-id"] = $owner["contact-id"];
			}

			if (($item["network"] != $owner["network"]) && ($owner["network"] != "")) {
				$item["network"] = $owner["network"];
			}

			if ($item["contact-id"] != $author["contact-id"]) {
				$item["contact-id"] = $author["contact-id"];
			}

			if (($item["network"] != $author["network"]) && ($author["network"] != "")) {
				$item["network"] = $author["network"];
			}
		}

		// Ensure to have the correct share data
		$item = Item::addShareDataFromOriginal($item);

		if ($entrytype == DFRN::REPLY_RC) {
			$item["wall"] = 1;
		} elseif ($entrytype == DFRN::TOP_LEVEL) {
			if (!isset($item["object-type"])) {
				$item["object-type"] = Activity\ObjectType::NOTE;
			}

			// Is it an event?
			if (($item["object-type"] == Activity\ObjectType::EVENT) && !$owner_unknown) {
				Logger::log("Item ".$item["uri"]." seems to contain an event.", Logger::DEBUG);
				$ev = Event::fromBBCode($item["body"]);
				if ((!empty($ev['desc']) || !empty($ev['summary'])) && !empty($ev['start'])) {
					Logger::log("Event in item ".$item["uri"]." was found.", Logger::DEBUG);
					$ev["cid"]     = $importer["id"];
					$ev["uid"]     = $importer["importer_uid"];
					$ev["uri"]     = $item["uri"];
					$ev["edited"]  = $item["edited"];
					$ev["private"] = $item["private"];
					$ev["guid"]    = $item["guid"];
					$ev["plink"]   = $item["plink"];

					$condition = ['uri' => $item["uri"], 'uid' => $importer["importer_uid"]];
					$event = DBA::selectFirst('event', ['id'], $condition);
					if (DBA::isResult($event)) {
						$ev["id"] = $event["id"];
					}

					$event_id = Event::store($ev);
					Logger::log("Event ".$event_id." was stored", Logger::DEBUG);
					return;
				}
			}
		}

		if (!self::processVerbs($entrytype, $importer, $item, $is_like)) {
			Logger::log("Exiting because 'processVerbs' told us so", Logger::DEBUG);
			return;
		}

		// This check is done here to be able to receive connection requests in "processVerbs"
		if (($entrytype == DFRN::TOP_LEVEL) && $owner_unknown) {
			Logger::log("Item won't be stored because user " . $importer["importer_uid"] . " doesn't follow " . $item["owner-link"] . ".", Logger::DEBUG);
			return;
		}


		// Update content if 'updated' changes
		if (DBA::isResult($current)) {
			if (self::updateContent($current, $item, $importer, $entrytype)) {
				Logger::log("Item ".$item["uri"]." was updated.", Logger::DEBUG);
			} else {
				Logger::log("Item " . $item["uri"] . " already existed.", Logger::DEBUG);
			}
			return;
		}

		if (in_array($entrytype, [DFRN::REPLY, DFRN::REPLY_RC])) {
			$posted_id = Item::insert($item);
			if ($posted_id) {
				Logger::log("Reply from contact ".$item["contact-id"]." was stored with id ".$posted_id, Logger::DEBUG);

				if ($item['uid'] == 0) {
					Item::distribute($posted_id);
				}

				return true;
			}
		} else { // $entrytype == DFRN::TOP_LEVEL
			if (($importer["uid"] == 0) && ($importer["importer_uid"] != 0)) {
				Logger::log("Contact ".$importer["id"]." isn't known to user ".$importer["importer_uid"].". The post will be ignored.", Logger::DEBUG);
				return;
			}
			if (!Strings::compareLink($item["owner-link"], $importer["url"])) {
				/*
				 * The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery,
				 * but otherwise there's a possible data mixup on the sender's system.
				 * the tgroup delivery code called from Item::insert will correct it if it's a forum,
				 * but we're going to unconditionally correct it here so that the post will always be owned by our contact.
				 */
				Logger::log('Correcting item owner.', Logger::DEBUG);
				$item["owner-link"] = $importer["url"];
				$item["owner-id"] = Contact::getIdForURL($importer["url"], 0);
			}

			if (($importer["rel"] == Contact::FOLLOWER) && (!self::tgroupCheck($importer["importer_uid"], $item))) {
				Logger::log("Contact ".$importer["id"]." is only follower and tgroup check was negative.", Logger::DEBUG);
				return;
			}

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.
			$notify = Item::isRemoteSelf($importer, $item);

			$posted_id = Item::insert($item, $notify);

			if ($notify) {
				$posted_id = $notify;
			}

			Logger::log("Item was stored with id ".$posted_id, Logger::DEBUG);

			if ($item['uid'] == 0) {
				Item::distribute($posted_id);
			}

			if (stristr($item["verb"], Activity::POKE)) {
				$item['id'] = $posted_id;
				self::doPoke($item, $importer);
			}
		}
	}

	/**
	 * Deletes items
	 *
	 * @param object $xpath    XPath object
	 * @param object $deletion deletion elements
	 * @param array  $importer Record of the importer user mixed with contact of the content
	 * @return void
	 * @throws \Exception
	 * @todo  set proper type-hints
	 */
	private static function processDeletion($xpath, $deletion, $importer)
	{
		Logger::log("Processing deletions");
		$uri = null;

		foreach ($deletion->attributes as $attributes) {
			if ($attributes->name == "ref") {
				$uri = $attributes->textContent;
			}
		}

		if (!$uri || !$importer["id"]) {
			return false;
		}

		$condition = ['uri' => $uri, 'uid' => $importer["importer_uid"]];
		$item = Item::selectFirst(['id', 'parent', 'contact-id', 'file', 'deleted', 'gravity'], $condition);
		if (!DBA::isResult($item)) {
			Logger::log("Item with uri " . $uri . " for user " . $importer["importer_uid"] . " wasn't found.", Logger::DEBUG);
			return;
		}

		if (strstr($item['file'], '[')) {
			Logger::log("Item with uri " . $uri . " for user " . $importer["importer_uid"] . " is filed. So it won't be deleted.", Logger::DEBUG);
			return;
		}

		// When it is a starting post it has to belong to the person that wants to delete it
		if (($item['gravity'] == GRAVITY_PARENT) && ($item['contact-id'] != $importer["id"])) {
			Logger::log("Item with uri " . $uri . " don't belong to contact " . $importer["id"] . " - ignoring deletion.", Logger::DEBUG);
			return;
		}

		// Comments can be deleted by the thread owner or comment owner
		if (($item['gravity'] != GRAVITY_PARENT) && ($item['contact-id'] != $importer["id"])) {
			$condition = ['id' => $item['parent'], 'contact-id' => $importer["id"]];
			if (!Item::exists($condition)) {
				Logger::log("Item with uri " . $uri . " wasn't found or mustn't be deleted by contact " . $importer["id"] . " - ignoring deletion.", Logger::DEBUG);
				return;
			}
		}

		if ($item["deleted"]) {
			return;
		}

		Logger::log('deleting item '.$item['id'].' uri='.$uri, Logger::DEBUG);

		Item::markForDeletion(['id' => $item['id']]);
	}

	/**
	 * Imports a DFRN message
	 *
	 * @param string $xml          The DFRN message
	 * @param array  $importer     Record of the importer user mixed with contact of the content
	 * @param bool   $sort_by_date Is used when feeds are polled
	 * @return integer Import status
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo  set proper type-hints
	 */
	public static function import($xml, $importer, $sort_by_date = false)
	{
		if ($xml == "") {
			return 400;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace("atom", ActivityNamespace::ATOM1);
		$xpath->registerNamespace("thr", ActivityNamespace::THREAD);
		$xpath->registerNamespace("at", ActivityNamespace::TOMB);
		$xpath->registerNamespace("media", ActivityNamespace::MEDIA);
		$xpath->registerNamespace("dfrn", ActivityNamespace::DFRN);
		$xpath->registerNamespace("activity", ActivityNamespace::ACTIVITY);
		$xpath->registerNamespace("georss", ActivityNamespace::GEORSS);
		$xpath->registerNamespace("poco", ActivityNamespace::POCO);
		$xpath->registerNamespace("ostatus", ActivityNamespace::OSTATUS);
		$xpath->registerNamespace("statusnet", ActivityNamespace::STATUSNET);

		$header = [];
		$header["uid"] = $importer["importer_uid"];
		$header["network"] = Protocol::DFRN;
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["contact-id"] = $importer["id"];

		// Update the contact table if the data has changed

		// The "atom:author" is only present in feeds
		if ($xpath->query("/atom:feed/atom:author")->length > 0) {
			self::fetchauthor($xpath, $doc->firstChild, $importer, "atom:author", false, $xml);
		}

		// Only the "dfrn:owner" in the head section contains all data
		if ($xpath->query("/atom:feed/dfrn:owner")->length > 0) {
			self::fetchauthor($xpath, $doc->firstChild, $importer, "dfrn:owner", false, $xml);
		}

		Logger::log("Import DFRN message for user " . $importer["importer_uid"] . " from contact " . $importer["id"], Logger::DEBUG);

		// is it a public forum? Private forums aren't exposed with this method
		$forum = intval(XML::getFirstNodeValue($xpath, "/atom:feed/dfrn:community/text()"));

		// The account type is new since 3.5.1
		if ($xpath->query("/atom:feed/dfrn:account_type")->length > 0) {
			// Hint: We are using separate update calls for uid=0 and uid!=0 since a combined call is bad for the database performance

			$accounttype = intval(XML::getFirstNodeValue($xpath, "/atom:feed/dfrn:account_type/text()"));

			if ($accounttype != $importer["contact-type"]) {
				DBA::update('contact', ['contact-type' => $accounttype], ['id' => $importer['id']]);

				// Updating the public contact as well
				DBA::update('contact', ['contact-type' => $accounttype], ['uid' => 0, 'nurl' => $importer['nurl']]);
			}
			// A forum contact can either have set "forum" or "prv" - but not both
			if ($accounttype == User::ACCOUNT_TYPE_COMMUNITY) {
				// It's a forum, so either set the public or private forum flag
				$condition = ['(`forum` != ? OR `prv` != ?) AND `id` = ?', $forum, !$forum, $importer['id']];
				DBA::update('contact', ['forum' => $forum, 'prv' => !$forum], $condition);

				// Updating the public contact as well
				$condition = ['(`forum` != ? OR `prv` != ?) AND `uid` = 0 AND `nurl` = ?', $forum, !$forum, $importer['nurl']];
				DBA::update('contact', ['forum' => $forum, 'prv' => !$forum], $condition);
			} else {
				// It's not a forum, so remove the flags
				$condition = ['(`forum` OR `prv`) AND `id` = ?', $importer['id']];
				DBA::update('contact', ['forum' => false, 'prv' => false], $condition);

				// Updating the public contact as well
				$condition = ['(`forum` OR `prv`) AND `uid` = 0 AND `nurl` = ?', $importer['nurl']];
				DBA::update('contact', ['forum' => false, 'prv' => false], $condition);
			}
		} elseif ($forum != $importer["forum"]) { // Deprecated since 3.5.1
			$condition = ['`forum` != ? AND `id` = ?', $forum, $importer["id"]];
			DBA::update('contact', ['forum' => $forum], $condition);

			// Updating the public contact as well
			$condition = ['`forum` != ? AND `uid` = 0 AND `nurl` = ?', $forum, $importer['nurl']];
			DBA::update('contact', ['forum' => $forum], $condition);
		}


		// We are processing relocations even if we are ignoring a contact
		$relocations = $xpath->query("/atom:feed/dfrn:relocate");
		foreach ($relocations as $relocation) {
			self::processRelocation($xpath, $relocation, $importer);
		}

		if (($importer["uid"] != 0) && !$importer["readonly"]) {
			$mails = $xpath->query("/atom:feed/dfrn:mail");
			foreach ($mails as $mail) {
				self::processMail($xpath, $mail, $importer);
			}

			$suggestions = $xpath->query("/atom:feed/dfrn:suggest");
			foreach ($suggestions as $suggestion) {
				self::processSuggestion($xpath, $suggestion, $importer);
			}
		}

		$deletions = $xpath->query("/atom:feed/at:deleted-entry");
		foreach ($deletions as $deletion) {
			self::processDeletion($xpath, $deletion, $importer);
		}

		if (!$sort_by_date) {
			$entries = $xpath->query("/atom:feed/atom:entry");
			foreach ($entries as $entry) {
				self::processEntry($header, $xpath, $entry, $importer, $xml);
			}
		} else {
			$newentries = [];
			$entries = $xpath->query("/atom:feed/atom:entry");
			foreach ($entries as $entry) {
				$created = XML::getFirstNodeValue($xpath, "atom:published/text()", $entry);
				$newentries[strtotime($created)] = $entry;
			}

			// Now sort after the publishing date
			ksort($newentries);

			foreach ($newentries as $entry) {
				self::processEntry($header, $xpath, $entry, $importer, $xml);
			}
		}
		Logger::log("Import done for user " . $importer["importer_uid"] . " from contact " . $importer["id"], Logger::DEBUG);
		return 200;
	}

	/**
	 * Returns the activity verb
	 *
	 * @param array $item Item array
	 *
	 * @return string activity verb
	 */
	private static function constructVerb(array $item)
	{
		if ($item['verb']) {
			return $item['verb'];
		}
		return Activity::POST;
	}

	private static function tgroupCheck($uid, $item)
	{
		$mention = false;

		// check that the message originated elsewhere and is a top-level post

		if ($item['wall'] || $item['origin'] || ($item['uri'] != $item['parent-uri'])) {
			return false;
		}

		$user = DBA::selectFirst('user', ['page-flags', 'nickname'], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$community_page = ($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY);
		$prvgroup = ($user['page-flags'] == User::PAGE_FLAGS_PRVGROUP);

		$link = Strings::normaliseLink(DI::baseUrl() . '/profile/' . $user['nickname']);

		/*
		 * Diaspora uses their own hardwired link URL in @-tags
		 * instead of the one we supply with webfinger
		 */
		$dlink = Strings::normaliseLink(DI::baseUrl() . '/u/' . $user['nickname']);

		$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (Strings::compareLink($link, $mtch[1]) || Strings::compareLink($dlink, $mtch[1])) {
					$mention = true;
					Logger::log('mention found: ' . $mtch[2]);
				}
			}
		}

		if (!$mention) {
			return false;
		}

		return $community_page || $prvgroup;
	}

	/**
	 * This function returns true if $update has an edited timestamp newer
	 * than $existing, i.e. $update contains new data which should override
	 * what's already there.  If there is no timestamp yet, the update is
	 * assumed to be newer.  If the update has no timestamp, the existing
	 * item is assumed to be up-to-date.  If the timestamps are equal it
	 * assumes the update has been seen before and should be ignored.
	 *
	 * @param $existing
	 * @param $update
	 * @return bool
	 * @throws \Exception
	 */
	private static function isEditedTimestampNewer($existing, $update)
	{
		if (empty($existing['edited'])) {
			return true;
		}
		if (empty($update['edited'])) {
			return false;
		}

		$existing_edited = DateTimeFormat::utc($existing['edited']);
		$update_edited = DateTimeFormat::utc($update['edited']);

		return (strcmp($existing_edited, $update_edited) < 0);
	}

	/**
	 * Checks if the given contact url does support DFRN
	 *
	 * @param string  $url    profile url
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSupportedByContactUrl($url)
	{
		$probe = Probe::uri($url, Protocol::DFRN);
		return $probe['network'] == Protocol::DFRN;
	}
}
