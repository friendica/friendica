<?php
/**
 * @file include/dfrn.php
 * @brief The implementation of the dfrn protocol
 *
 * @see https://github.com/friendica/friendica/wiki/Protocol and
 * https://github.com/friendica/friendica/blob/master/spec/dfrn2.pdf
 */
namespace Friendica\Protocol;

use DOMDocument;
use DOMXPath;
use Friendica\App;
use Friendica\Content\OEmbed;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\GContact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\PermissionSet;
use Friendica\Model\User;
use Friendica\Object\Image;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\XML;
use HTMLPurifier;
use HTMLPurifier_Config;

require_once 'boot.php';
require_once 'include/dba.php';
require_once "include/enotify.php";
require_once "include/items.php";
require_once "include/text.php";

/**
 * @brief This class contain functions to create and send DFRN XML files
 */
class DFRN
{

	const TOP_LEVEL = 0;	// Top level posting
	const REPLY = 1;		// Regular reply that is stored locally
	const REPLY_RC = 2;	// Reply that will be relayed

	/**
	 * @brief Generates the atom entries for delivery.php
	 *
	 * This function is used whenever content is transmitted via DFRN.
	 *
	 * @param array $items Item elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN entries
	 * @todo Find proper type-hints
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
			$item["entry:comment-allow"] = defaults($item, "entry:comment-allow", true);
			$item["entry:cid"] = defaults($item, "entry:cid", 0);

			$entry = self::entry($doc, "text", $item, $owner, $item["entry:comment-allow"], $item["entry:cid"]);
			$root->appendChild($entry);
		}

		return trim($doc->saveXML());
	}

	/**
	 * @brief Generate an atom feed for the given user
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
	 */
	public static function feed($dfrn_id, $owner_nick, $last_update, $direction = 0, $onlyheader = false)
	{
		$a = get_app();

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

		$sql_extra = " AND NOT `item`.`private` ";

		$r = q(
			"SELECT `contact`.*, `user`.`nickname`, `user`.`timezone`, `user`.`page-flags`, `user`.`account-type`
			FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `contact`.`self` AND `user`.`nickname` = '%s' LIMIT 1",
			DBA::escape($owner_nick)
		);

		if (! DBA::isResult($r)) {
			logger(sprintf('No contact found for nickname=%d', $owner_nick), LOGGER_WARNING);
			killme();
		}

		$owner = $r[0];
		$owner_id = $owner['uid'];
		$owner_nick = $owner['nickname'];

		$sql_post_table = "";

		if (! $public_feed) {
			$sql_extra = '';
			switch ($direction) {
				case (-1):
					$sql_extra = sprintf(" AND `issued-id` = '%s' ", DBA::escape($dfrn_id));
					$my_id = $dfrn_id;
					break;
				case 0:
					$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
					$my_id = '1:' . $dfrn_id;
					break;
				case 1:
					$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
					$my_id = '0:' . $dfrn_id;
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
				logger(sprintf('No contact found for uid=%d', $owner_id), LOGGER_WARNING);
				killme();
			}

			$contact = $r[0];
			include_once 'include/security.php';

			$set = PermissionSet::get($owner_id, $contact['id']);

			if (!empty($set)) {
				$sql_extra = " AND `item`.`psid` IN (" . implode(',', $set) .")";
			} else {
				$sql_extra = " AND NOT `item`.`private`";
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
			$sql_post_table = sprintf(
				"INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				DBA::escape(protect_sprintf($category)),
				intval(TERM_OBJ_POST),
				intval(TERM_CATEGORY),
				intval($owner_id)
			);
			//$sql_extra .= file_tag_file_query('item',$category,'category');
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
			$sql_extra
			ORDER BY `item`.`parent` ".$sort.", `item`.`created` ASC LIMIT 0, 300",
			intval($owner_id),
			DBA::escape($check_date),
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
		//	Addon::callHooks('atom_feed', $atom);

		if (!DBA::isResult($items) || $onlyheader) {
			$atom = trim($doc->saveXML());

			Addon::callHooks('atom_feed_end', $atom);

			return $atom;
		}

		foreach ($items as $item) {
			// prevent private email from leaking.
			if ($item['network'] == NETWORK_MAIL) {
				continue;
			}

			// public feeds get html, our own nodes use bbcode

			if ($public_feed) {
				$type = 'html';
				// catch any email that's in a public conversation and make sure it doesn't leak
				if ($item['private']) {
					continue;
				}
			} else {
				$type = 'text';
			}

			$entry = self::entry($doc, $type, $item, $owner, true);
			$root->appendChild($entry);
		}

		$atom = trim($doc->saveXML());

		Addon::callHooks('atom_feed_end', $atom);

		return $atom;
	}

	/**
	 * @brief Generate an atom entry for a given item id
	 *
	 * @param int     $item_id      The item id
	 * @param boolean $conversation Show the conversation. If false show the single post.
	 *
	 * @return string DFRN feed entry
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
			killme();
		}

		$item = $items[0];

		if ($item['uid'] != 0) {
			$owner = User::getOwnerDataById($item['uid']);
			if (!$owner) {
				killme();
			}
		} else {
			$owner = ['uid' => 0, 'nick' => 'feed-item'];
		}

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;
		$type = 'html';

		if ($conversation) {
			$root = $doc->createElementNS(NAMESPACE_ATOM1, 'feed');
			$doc->appendChild($root);

			$root->setAttribute("xmlns:thr", NAMESPACE_THREAD);
			$root->setAttribute("xmlns:at", NAMESPACE_TOMB);
			$root->setAttribute("xmlns:media", NAMESPACE_MEDIA);
			$root->setAttribute("xmlns:dfrn", NAMESPACE_DFRN);
			$root->setAttribute("xmlns:activity", NAMESPACE_ACTIVITY);
			$root->setAttribute("xmlns:georss", NAMESPACE_GEORSS);
			$root->setAttribute("xmlns:poco", NAMESPACE_POCO);
			$root->setAttribute("xmlns:ostatus", NAMESPACE_OSTATUS);
			$root->setAttribute("xmlns:statusnet", NAMESPACE_STATUSNET);

			//$root = self::addHeader($doc, $owner, "dfrn:owner", "", false);

			foreach ($items as $item) {
				$entry = self::entry($doc, $type, $item, $owner, true, 0);
				$root->appendChild($entry);
			}
		} else {
			$root = self::entry($doc, $type, $item, $owner, true, 0, true);
		}

		$atom = trim($doc->saveXML());
		return $atom;
	}

	/**
	 * @brief Create XML text for DFRN mails
	 *
	 * @param array $item  message elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN mail
	 * @todo Find proper type-hints
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
	 * @brief Create XML text for DFRN friend suggestions
	 *
	 * @param array $item  suggestion elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN suggestions
	 * @todo Find proper type-hints
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
	 * @brief Create XML text for DFRN relocations
	 *
	 * @param array $owner Owner record
	 * @param int   $uid   User ID
	 *
	 * @return string DFRN relocations
	 * @todo Find proper type-hints
	 */
	public static function relocate($owner, $uid)
	{

		/* get site pubkey. this could be a new installation with no site keys*/
		$pubkey = Config::get('system', 'site_pubkey');
		if (! $pubkey) {
			$res = Crypto::newKeypair(1024);
			Config::set('system', 'site_prvkey', $res['prvkey']);
			Config::set('system', 'site_pubkey', $res['pubkey']);
		}

		$rp = q(
			"SELECT `resource-id` , `scale`, type FROM `photo`
				WHERE `profile` = 1 AND `uid` = %d ORDER BY scale;",
			$uid
		);
		$photos = [];
		$ext = Image::supportedTypes();

		foreach ($rp as $p) {
			$photos[$p['scale']] = System::baseUrl().'/photo/'.$p['resource-id'].'-'.$p['scale'].'.'.$ext[$p['type']];
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
		XML::addElement($doc, $relocate, "dfrn:sitepubkey", Config::get('system', 'site_pubkey'));

		$root->appendChild($relocate);

		return trim($doc->saveXML());
	}

	/**
	 * @brief Adds the header elements for the DFRN protocol
	 *
	 * @param object $doc           XML document
	 * @param array  $owner         Owner record
	 * @param string $authorelement Element name for the author
	 * @param string $alternatelink link to profile or category
	 * @param bool   $public        Is it a header for public posts?
	 *
	 * @return object XML root object
	 * @todo Find proper type-hints
	 */
	private static function addHeader($doc, $owner, $authorelement, $alternatelink = "", $public = false)
	{

		if ($alternatelink == "") {
			$alternatelink = $owner['url'];
		}

		$root = $doc->createElementNS(NAMESPACE_ATOM1, 'feed');
		$doc->appendChild($root);

		$root->setAttribute("xmlns:thr", NAMESPACE_THREAD);
		$root->setAttribute("xmlns:at", NAMESPACE_TOMB);
		$root->setAttribute("xmlns:media", NAMESPACE_MEDIA);
		$root->setAttribute("xmlns:dfrn", NAMESPACE_DFRN);
		$root->setAttribute("xmlns:activity", NAMESPACE_ACTIVITY);
		$root->setAttribute("xmlns:georss", NAMESPACE_GEORSS);
		$root->setAttribute("xmlns:poco", NAMESPACE_POCO);
		$root->setAttribute("xmlns:ostatus", NAMESPACE_OSTATUS);
		$root->setAttribute("xmlns:statusnet", NAMESPACE_STATUSNET);

		XML::addElement($doc, $root, "id", System::baseUrl()."/profile/".$owner["nick"]);
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

			$attributes = ["rel" => "salmon", "href" => System::baseUrl()."/salmon/".$owner["nick"]];
			XML::addElement($doc, $root, "link", "", $attributes);

			$attributes = ["rel" => "http://salmon-protocol.org/ns/salmon-replies", "href" => System::baseUrl()."/salmon/".$owner["nick"]];
			XML::addElement($doc, $root, "link", "", $attributes);

			$attributes = ["rel" => "http://salmon-protocol.org/ns/salmon-mention", "href" => System::baseUrl()."/salmon/".$owner["nick"]];
			XML::addElement($doc, $root, "link", "", $attributes);
		}

		// For backward compatibility we keep this element
		if ($owner['page-flags'] == Contact::PAGE_COMMUNITY) {
			XML::addElement($doc, $root, "dfrn:community", 1);
		}

		// The former element is replaced by this one
		XML::addElement($doc, $root, "dfrn:account_type", $owner["account-type"]);

		/// @todo We need a way to transmit the different page flags like "Contact::PAGE_PRVGROUP"

		XML::addElement($doc, $root, "updated", DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner, $authorelement, $public);
		$root->appendChild($author);

		return $root;
	}

	/**
	 * @brief Adds the author element in the header for the DFRN protocol
	 *
	 * @param object  $doc           XML document
	 * @param array   $owner         Owner record
	 * @param string  $authorelement Element name for the author
	 * @param boolean $public        boolean
	 *
	 * @return object XML author object
	 * @todo Find proper type-hints
	 */
	private static function addAuthor($doc, $owner, $authorelement, $public)
	{
		// Is the profile hidden or shouldn't be published in the net? Then add the "hide" element
		$r = q(
			"SELECT `id` FROM `profile` INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE (`hidewall` OR NOT `net-publish`) AND `user`.`uid` = %d",
			intval($owner['uid'])
		);
		if (DBA::isResult($r)) {
			$hidewall = true;
		} else {
			$hidewall = false;
		}

		$author = $doc->createElement($authorelement);

		$namdate = DateTimeFormat::utc($owner['name-date'].'+00:00', DateTimeFormat::ATOM);
		$uridate = DateTimeFormat::utc($owner['uri-date'].'+00:00', DateTimeFormat::ATOM);
		$picdate = DateTimeFormat::utc($owner['avatar-date'].'+00:00', DateTimeFormat::ATOM);

		$attributes = [];

		if (!$public || !$hidewall) {
			$attributes = ["dfrn:updated" => $namdate];
		}

		XML::addElement($doc, $author, "name", $owner["name"], $attributes);
		XML::addElement($doc, $author, "uri", System::baseUrl().'/profile/'.$owner["nickname"], $attributes);
		XML::addElement($doc, $author, "dfrn:handle", $owner["addr"], $attributes);

		$attributes = ["rel" => "photo", "type" => "image/jpeg",
					"media:width" => 175, "media:height" => 175, "href" => $owner['photo']];

		if (!$public || !$hidewall) {
			$attributes["dfrn:updated"] = $picdate;
		}

		XML::addElement($doc, $author, "link", "", $attributes);

		$attributes["rel"] = "avatar";
		XML::addElement($doc, $author, "link", "", $attributes);

		if ($hidewall) {
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
		$r = q(
			"SELECT `profile`.`about`, `profile`.`name`, `profile`.`homepage`, `user`.`nickname`,
				`user`.`timezone`, `profile`.`locality`, `profile`.`region`, `profile`.`country-name`,
				`profile`.`pub_keywords`, `profile`.`xmpp`, `profile`.`dob`
			FROM `profile`
				INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE `profile`.`is-default` AND NOT `user`.`hidewall` AND `user`.`uid` = %d",
			intval($owner['uid'])
		);
		if (DBA::isResult($r)) {
			$profile = $r[0];

			XML::addElement($doc, $author, "poco:displayName", $profile["name"]);
			XML::addElement($doc, $author, "poco:updated", $namdate);

			if (trim($profile["dob"]) > '0001-01-01') {
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
	 * @brief Adds the author elements in the "entry" elements of the DFRN protocol
	 *
	 * @param object $doc         XML document
	 * @param string $element     Element name for the author
	 * @param string $contact_url Link of the contact
	 * @param array  $item        Item elements
	 *
	 * @return object XML author object
	 * @todo Find proper type-hints
	 */
	private static function addEntryAuthor($doc, $element, $contact_url, $item)
	{
		$contact = Contact::getDetailsByURL($contact_url, $item["uid"]);

		$author = $doc->createElement($element);
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

		return $author;
	}

	/**
	 * @brief Adds the activity elements
	 *
	 * @param object $doc      XML document
	 * @param string $element  Element name for the activity
	 * @param string $activity activity value
	 *
	 * @return object XML activity object
	 * @todo Find proper type-hints
	 */
	private static function createActivity($doc, $element, $activity)
	{
		if ($activity) {
			$entry = $doc->createElement($element);

			$r = XML::parseString($activity, false);
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
					$data = XML::parseString("<dummy>" . $r->link . "</dummy>", false);
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
	 * @brief Adds the elements for attachments
	 *
	 * @param object $doc  XML document
	 * @param object $root XML root
	 * @param array  $item Item element
	 *
	 * @return object XML attachment object
	 * @todo Find proper type-hints
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
	 * @brief Adds the "entry" elements for the DFRN protocol
	 *
	 * @param object $doc     XML document
	 * @param string $type    "text" or "html"
	 * @param array  $item    Item element
	 * @param array  $owner   Owner record
	 * @param bool   $comment Trigger the sending of the "comment" element
	 * @param int    $cid     Contact ID of the recipient
	 * @param bool   $single  If set, the entry is created as an XML document with a single "entry" element
	 *
	 * @return object XML entry object
	 * @todo Find proper type-hints
	 */
	private static function entry($doc, $type, array $item, array $owner, $comment = false, $cid = 0, $single = false)
	{
		$mentioned = [];

		if (!$item['parent']) {
			return;
		}

		if ($item['deleted']) {
			$attributes = ["ref" => $item['uri'], "when" => DateTimeFormat::utc($item['edited'] . '+00:00', DateTimeFormat::ATOM)];
			return XML::createElement($doc, "at:deleted-entry", "", $attributes);
		}

		if (!$single) {
			$entry = $doc->createElement("entry");
		} else {
			$entry = $doc->createElementNS(NAMESPACE_ATOM1, 'entry');
			$doc->appendChild($entry);

			$entry->setAttribute("xmlns:thr", NAMESPACE_THREAD);
			$entry->setAttribute("xmlns:at", NAMESPACE_TOMB);
			$entry->setAttribute("xmlns:media", NAMESPACE_MEDIA);
			$entry->setAttribute("xmlns:dfrn", NAMESPACE_DFRN);
			$entry->setAttribute("xmlns:activity", NAMESPACE_ACTIVITY);
			$entry->setAttribute("xmlns:georss", NAMESPACE_GEORSS);
			$entry->setAttribute("xmlns:poco", NAMESPACE_POCO);
			$entry->setAttribute("xmlns:ostatus", NAMESPACE_OSTATUS);
			$entry->setAttribute("xmlns:statusnet", NAMESPACE_STATUSNET);
		}

		if ($item['private']) {
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

			$htmlbody = BBCode::convert($htmlbody, false, 7);
		}

		$author = self::addEntryAuthor($doc, "author", $item["author-link"], $item);
		$entry->appendChild($author);

		$dfrnowner = self::addEntryAuthor($doc, "dfrn:owner", $item["owner-link"], $item);
		$entry->appendChild($dfrnowner);

		if (($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);
			$parent = Item::selectFirst(['guid', 'plink'], ['uri' => $parent_item, 'uid' => $item['uid']]);
			$attributes = ["ref" => $parent_item, "type" => "text/html",
						"href" => $parent['plink'],
						"dfrn:diaspora_guid" => $parent['guid']];
			XML::addElement($doc, $entry, "thr:in-reply-to", "", $attributes);
		}

		// Add conversation data. This is used for OStatus
		$conversation_href = System::baseUrl()."/display/".$owner["nick"]."/".$item["parent"];
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
		XML::addElement($doc, $entry, "dfrn:env", base64url_encode($body, true));

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
				 "href" => System::baseUrl() . "/display/" . $item["guid"]]
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
			XML::addElement($doc, $entry, "dfrn:private", ($item['private'] ? $item['private'] : 1));
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
			$sign = base64_encode(json_encode(['signed_text' => $item['signed_text'],'signature' => $item['signature'],'signer' => $item['signer']]));
			XML::addElement($doc, $entry, "dfrn:diaspora_signature", $sign);
		}

		XML::addElement($doc, $entry, "activity:verb", self::constructVerb($item));

		if ($item['object-type'] != "") {
			XML::addElement($doc, $entry, "activity:object-type", $item['object-type']);
		} elseif ($item['id'] == $item['parent']) {
			XML::addElement($doc, $entry, "activity:object-type", ACTIVITY_OBJ_NOTE);
		} else {
			XML::addElement($doc, $entry, "activity:object-type", ACTIVITY_OBJ_COMMENT);
		}

		$actobj = self::createActivity($doc, "activity:object", $item['object']);
		if ($actobj) {
			$entry->appendChild($actobj);
		}

		$actarg = self::createActivity($doc, "activity:target", $item['target']);
		if ($actarg) {
			$entry->appendChild($actarg);
		}

		$tags = Item::getFeedTags($item);

		/// @TODO Combine this with similar below if() block?
		if (count($tags)) {
			foreach ($tags as $t) {
				if (($type != 'html') || ($t[0] != "@")) {
					XML::addElement($doc, $entry, "category", "", ["scheme" => "X-DFRN:".$t[0].":".$t[1], "term" => $t[2]]);
				}
			}
		}

		if (count($tags)) {
			foreach ($tags as $t) {
				if ($t[0] == "@") {
					$mentioned[$t[1]] = $t[1];
				}
			}
		}

		foreach ($mentioned as $mention) {
			$r = q(
				"SELECT `forum`, `prv` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s'",
				intval($owner["uid"]),
				DBA::escape(normalise_link($mention))
			);

			if (DBA::isResult($r) && ($r[0]["forum"] || $r[0]["prv"])) {
				XML::addElement(
					$doc,
					$entry,
					"link",
					"",
					["rel" => "mentioned",
							"ostatus:object-type" => ACTIVITY_OBJ_GROUP,
							"href" => $mention]
				);
			} else {
				XML::addElement(
					$doc,
					$entry,
					"link",
					"",
					["rel" => "mentioned",
							"ostatus:object-type" => ACTIVITY_OBJ_PERSON,
							"href" => $mention]
				);
			}
		}

		self::getAttachment($doc, $entry, $item);

		return $entry;
	}

	/**
	 * @brief encrypts data via AES
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
	 * @brief decrypts data via AES
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
	 * @brief Delivers the atom content to the contacts
	 *
	 * @param array  $owner    Owner record
	 * @param array  $contact  Contact record of the receiver
	 * @param string $atom     Content that will be transmitted
	 * @param bool   $dissolve (to be documented)
	 *
	 * @return int Deliver status. Negative values mean an error.
	 * @todo Add array type-hint for $owner, $contact
	 */
	public static function deliver($owner, $contact, $atom, $dissolve = false)
	{
		$a = get_app();

		// At first try the Diaspora transport layer
		$ret = self::transmit($owner, $contact, $atom);
		if ($ret >= 200) {
			logger('Delivery via Diaspora transport layer was successful with status ' . $ret);
			return $ret;
		}

		$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

		if ($contact['duplex'] && $contact['dfrn-id']) {
			$idtosend = '0:' . $orig_id;
		}
		if ($contact['duplex'] && $contact['issued-id']) {
			$idtosend = '1:' . $orig_id;
		}

		$rino = Config::get('system', 'rino_encrypt');
		$rino = intval($rino);

		logger("Local rino version: ". $rino, LOGGER_DEBUG);

		$ssl_val = intval(Config::get('system', 'ssl_policy'));
		$ssl_policy = '';

		switch ($ssl_val) {
			case SSL_POLICY_FULL:
				$ssl_policy = 'full';
				break;
			case SSL_POLICY_SELFSIGN:
				$ssl_policy = 'self';
				break;
			case SSL_POLICY_NONE:
			default:
				$ssl_policy = 'none';
				break;
		}

		$url = $contact['notify'] . '&dfrn_id=' . $idtosend . '&dfrn_version=' . DFRN_PROTOCOL_VERSION . (($rino) ? '&rino='.$rino : '');

		logger('dfrn_deliver: ' . $url);

		$ret = Network::curl($url);

		if (!empty($ret["errno"]) && ($ret['errno'] == CURLE_OPERATION_TIMEDOUT)) {
			Contact::markForArchival($contact);
			return -2; // timed out
		}

		$xml = $ret['body'];

		$curl_stat = $a->get_curl_code();
		if (empty($curl_stat)) {
			Contact::markForArchival($contact);
			return -3; // timed out
		}

		logger('dfrn_deliver: ' . $xml, LOGGER_DATA);

		if (empty($xml)) {
			Contact::markForArchival($contact);
			return 3;
		}

		if (strpos($xml, '<?xml') === false) {
			logger('dfrn_deliver: no valid XML returned');
			logger('dfrn_deliver: returned XML: ' . $xml, LOGGER_DATA);
			Contact::markForArchival($contact);
			return 3;
		}

		$res = XML::parseString($xml);

		if (!is_object($res) || (intval($res->status) != 0) || !strlen($res->challenge) || !strlen($res->dfrn_id)) {
			Contact::markForArchival($contact);

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
		$dfrn_version = (float) (($res->dfrn_version) ? $res->dfrn_version : 2.0);
		$rino_remote_version = intval($res->rino);
		$page         = (($owner['page-flags'] == Contact::PAGE_COMMUNITY) ? 1 : 0);

		logger("Remote rino version: ".$rino_remote_version." for ".$contact["url"], LOGGER_DEBUG);

		if ($owner['page-flags'] == Contact::PAGE_PRVGROUP) {
			$page = 2;
		}

		$final_dfrn_id = '';

		if ($perm) {
			if ((($perm == 'rw') && (! intval($contact['writable'])))
				|| (($perm == 'r') && (intval($contact['writable'])))
			) {
				q(
					"update contact set writable = %d where id = %d",
					intval(($perm == 'rw') ? 1 : 0),
					intval($contact['id'])
				);
				$contact['writable'] = (string) 1 - intval($contact['writable']);
			}
		}

		if (($contact['duplex'] && strlen($contact['pubkey']))
			|| ($owner['page-flags'] == Contact::PAGE_COMMUNITY && strlen($contact['pubkey']))
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
			logger('dfrn_deliver: wrong dfrn_id.');
			// did not decode properly - cannot trust this site
			Contact::markForArchival($contact);
			return 3;
		}

		$postvars['dfrn_id']      = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		if ($dissolve) {
			$postvars['dissolve'] = '1';
		}

		if ((($contact['rel']) && ($contact['rel'] != Contact::SHARING) && (! $contact['blocked'])) || ($owner['page-flags'] == Contact::PAGE_COMMUNITY)) {
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
			logger('rino version: '. $rino_remote_version);

			switch ($rino_remote_version) {
				case 1:
					$key = openssl_random_pseudo_bytes(16);
					$data = self::aesEncrypt($postvars['data'], $key);
					break;
				default:
					logger("rino: invalid requested version '$rino_remote_version'");
					Contact::markForArchival($contact);
					return -8;
			}

			$postvars['rino'] = $rino_remote_version;
			$postvars['data'] = bin2hex($data);

			if ($dfrn_version >= 2.1) {
				if (($contact['duplex'] && strlen($contact['pubkey']))
					|| ($owner['page-flags'] == Contact::PAGE_COMMUNITY && strlen($contact['pubkey']))
					|| ($contact['rel'] == Contact::SHARING && strlen($contact['pubkey']))
				) {
					openssl_public_encrypt($key, $postvars['key'], $contact['pubkey']);
				} else {
					openssl_private_encrypt($key, $postvars['key'], $contact['prvkey']);
				}
			} else {
				if (($contact['duplex'] && strlen($contact['prvkey'])) || ($owner['page-flags'] == Contact::PAGE_COMMUNITY)) {
					openssl_private_encrypt($key, $postvars['key'], $contact['prvkey']);
				} else {
					openssl_public_encrypt($key, $postvars['key'], $contact['pubkey']);
				}
			}

			logger('md5 rawkey ' . md5($postvars['key']));

			$postvars['key'] = bin2hex($postvars['key']);
		}


		logger('dfrn_deliver: ' . "SENDING: " . print_r($postvars, true), LOGGER_DATA);

		$xml = Network::post($contact['notify'], $postvars);

		logger('dfrn_deliver: ' . "RECEIVED: " . $xml, LOGGER_DATA);

		$curl_stat = $a->get_curl_code();
		if (empty($curl_stat) || empty($xml)) {
			Contact::markForArchival($contact);
			return -9; // timed out
		}

		if (($curl_stat == 503) && stristr($a->get_curl_headers(), 'retry-after')) {
			Contact::markForArchival($contact);
			return -10;
		}

		if (strpos($xml, '<?xml') === false) {
			logger('dfrn_deliver: phase 2: no valid XML returned');
			logger('dfrn_deliver: phase 2: returned XML: ' . $xml, LOGGER_DATA);
			Contact::markForArchival($contact);
			return 3;
		}

		$res = XML::parseString($xml);

		if (!isset($res->status)) {
			Contact::markForArchival($contact);
			return -11;
		}

		// Possibly old servers had returned an empty value when everything was okay
		if (empty($res->status)) {
			$res->status = 200;
		}

		if (!empty($res->message)) {
			logger('Delivery returned status '.$res->status.' - '.$res->message, LOGGER_DEBUG);
		}

		if (($res->status >= 200) && ($res->status <= 299)) {
			Contact::unmarkForArchival($contact);
		}

		return intval($res->status);
	}

	/**
	 * @brief Transmits atom content to the contacts via the Diaspora transport layer
	 *
	 * @param array  $owner    Owner record
	 * @param array  $contact  Contact record of the receiver
	 * @param string $atom     Content that will be transmitted
	 *
	 * @return int Deliver status. Negative values mean an error.
	 */
	public static function transmit($owner, $contact, $atom, $public_batch = false)
	{
		$a = get_app();

		if (!$public_batch) {
			if (empty($contact['addr'])) {
				logger('Empty contact handle for ' . $contact['id'] . ' - ' . $contact['url'] . ' - trying to update it.');
				if (Contact::updateFromProbe($contact['id'])) {
					$new_contact = DBA::selectFirst('contact', ['addr'], ['id' => $contact['id']]);
					$contact['addr'] = $new_contact['addr'];
				}

				if (empty($contact['addr'])) {
					logger('Unable to find contact handle for ' . $contact['id'] . ' - ' . $contact['url']);
					Contact::markForArchival($contact);
					return -21;
				}
			}

			$fcontact = Diaspora::personByHandle($contact['addr']);
			if (empty($fcontact)) {
				logger('Unable to find contact details for ' . $contact['id'] . ' - ' . $contact['addr']);
				Contact::markForArchival($contact);
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

		$content_type = ($public_batch ? "application/magic-envelope+xml" : "application/json");

		$xml = Network::post($dest_url, $envelope, ["Content-Type: ".$content_type]);

		$curl_stat = $a->get_curl_code();
		if (empty($curl_stat) || empty($xml)) {
			logger('Empty answer from ' . $contact['id'] . ' - ' . $dest_url);
			Contact::markForArchival($contact);
			return -9; // timed out
		}

		if (($curl_stat == 503) && (stristr($a->get_curl_headers(), 'retry-after'))) {
			Contact::markForArchival($contact);
			return -10;
		}

		if (strpos($xml, '<?xml') === false) {
			logger('No valid XML returned from ' . $contact['id'] . ' - ' . $dest_url);
			logger('Returned XML: ' . $xml, LOGGER_DATA);
			Contact::markForArchival($contact);
			return 3;
		}

		$res = XML::parseString($xml);

		if (empty($res->status)) {
			Contact::markForArchival($contact);
			return -23;
		}

		if (!empty($res->message)) {
			logger('Transmit to ' . $dest_url . ' returned status '.$res->status.' - '.$res->message, LOGGER_DEBUG);
		}

		if (($res->status >= 200) && ($res->status <= 299)) {
			Contact::unmarkForArchival($contact);
		}

		return intval($res->status);
	}

	/**
	 * @brief Add new birthday event for this person
	 *
	 * @param array  $contact  Contact record
	 * @param string $birthday Birthday of the contact
	 * @return void
	 * @todo Add array type-hint for $contact
	 */
	private static function birthdayEvent($contact, $birthday)
	{
		// Check for duplicates
		$r = q(
			"SELECT `id` FROM `event` WHERE `uid` = %d AND `cid` = %d AND `start` = '%s' AND `type` = '%s' LIMIT 1",
			intval($contact['uid']),
			intval($contact['id']),
			DBA::escape(DateTimeFormat::utc($birthday)),
			DBA::escape('birthday')
		);

		if (DBA::isResult($r)) {
			return;
		}

		logger('updating birthday: ' . $birthday . ' for contact ' . $contact['id']);

		$bdtext = L10n::t('%s\'s birthday', $contact['name']);
		$bdtext2 = L10n::t('Happy Birthday %s', ' [url=' . $contact['url'] . ']' . $contact['name'] . '[/url]');

		$r = q(
			"INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`summary`,`desc`,`type`)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($contact['uid']),
			intval($contact['id']),
			DBA::escape(DateTimeFormat::utcNow()),
			DBA::escape(DateTimeFormat::utcNow()),
			DBA::escape(DateTimeFormat::utc($birthday)),
			DBA::escape(DateTimeFormat::utc($birthday . ' + 1 day ')),
			DBA::escape($bdtext),
			DBA::escape($bdtext2),
			DBA::escape('birthday')
		);
	}

	/**
	 * @brief Fetch the author data from head or entry items
	 *
	 * @param object $xpath     XPath object
	 * @param object $context   In which context should the data be searched
	 * @param array  $importer  Record of the importer user mixed with contact of the content
	 * @param string $element   Element name from which the data is fetched
	 * @param bool   $onlyfetch Should the data only be fetched or should it update the contact record as well
	 * @param string $xml       optional, default empty
	 *
	 * @return array Relevant data of the author
	 * @todo Find good type-hints for all parameter
	 */
	private static function fetchauthor($xpath, $context, $importer, $element, $onlyfetch, $xml = "")
	{
		$author = [];
		$author["name"] = XML::getFirstNodeValue($xpath, $element."/atom:name/text()", $context);
		$author["link"] = XML::getFirstNodeValue($xpath, $element."/atom:uri/text()", $context);

		$fields = ['id', 'uid', 'url', 'network', 'avatar-date', 'avatar', 'name-date', 'uri-date', 'addr',
			'name', 'nick', 'about', 'location', 'keywords', 'xmpp', 'bdyear', 'bd', 'hidden', 'contact-type'];
		$condition = ["`uid` = ? AND `nurl` = ? AND `network` != ?",
			$importer["importer_uid"], normalise_link($author["link"]), NETWORK_STATUSNET];
		$contact_old = DBA::selectFirst('contact', $fields, $condition);

		if (DBA::isResult($contact_old)) {
			$author["contact-id"] = $contact_old["id"];
			$author["network"] = $contact_old["network"];
		} else {
			if (!$onlyfetch) {
				logger("Contact ".$author["link"]." wasn't found for user ".$importer["importer_uid"]." XML: ".$xml, LOGGER_DEBUG);
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

		if (DBA::isResult($contact_old) && !$onlyfetch) {
			logger("Check if contact details for contact " . $contact_old["id"] . " (" . $contact_old["nick"] . ") have to be updated.", LOGGER_DEBUG);

			$poco = ["url" => $contact_old["url"]];

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

			logger("Hidden status for contact " . $contact_old["url"] . ": " . $hide, LOGGER_DEBUG);

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

			if (!in_array($value, ["", "0000-00-00", "0001-01-01"])) {
				$bdyear = date("Y");
				$value = str_replace("0000", $bdyear, $value);

				if (strtotime($value) < time()) {
					$value = str_replace($bdyear, $bdyear + 1, $value);
					$bdyear = $bdyear + 1;
				}

				$poco["bd"] = $value;
			}

			$contact = array_merge($contact_old, $poco);

			if ($contact_old["bdyear"] != $contact["bdyear"]) {
				self::birthdayEvent($contact, $birthday);
			}

			// Get all field names
			$fields = [];
			foreach ($contact_old as $field => $data) {
				$fields[$field] = $data;
			}

			unset($fields["id"]);
			unset($fields["uid"]);
			unset($fields["url"]);
			unset($fields["avatar-date"]);
			unset($fields["avatar"]);
			unset($fields["name-date"]);
			unset($fields["uri-date"]);

			$update = false;
			// Update check for this field has to be done differently
			$datefields = ["name-date", "uri-date"];
			foreach ($datefields as $field) {
				if (strtotime($contact[$field]) > strtotime($contact_old[$field])) {
					logger("Difference for contact " . $contact["id"] . " in field '" . $field . "'. New value: '" . $contact[$field] . "', old value '" . $contact_old[$field] . "'", LOGGER_DEBUG);
					$update = true;
				}
			}

			foreach ($fields as $field => $data) {
				if ($contact[$field] != $contact_old[$field]) {
					logger("Difference for contact " . $contact["id"] . " in field '" . $field . "'. New value: '" . $contact[$field] . "', old value '" . $contact_old[$field] . "'", LOGGER_DEBUG);
					$update = true;
				}
			}

			if ($update) {
				logger("Update contact data for contact " . $contact["id"] . " (" . $contact["nick"] . ")", LOGGER_DEBUG);

				q(
					"UPDATE `contact` SET `name` = '%s', `nick` = '%s', `about` = '%s', `location` = '%s',
					`addr` = '%s', `keywords` = '%s', `bdyear` = '%s', `bd` = '%s', `hidden` = %d,
					`xmpp` = '%s', `name-date`  = '%s', `uri-date` = '%s'
					WHERE `id` = %d AND `network` = '%s'",
					DBA::escape($contact["name"]), DBA::escape($contact["nick"]), DBA::escape($contact["about"]),	DBA::escape($contact["location"]),
					DBA::escape($contact["addr"]), DBA::escape($contact["keywords"]), DBA::escape($contact["bdyear"]),
					DBA::escape($contact["bd"]), intval($contact["hidden"]), DBA::escape($contact["xmpp"]),
					DBA::escape(DateTimeFormat::utc($contact["name-date"])), DBA::escape(DateTimeFormat::utc($contact["uri-date"])),
					intval($contact["id"]),	DBA::escape($contact["network"])
				);
			}

			Contact::updateAvatar(
				$author['avatar'],
				$importer['importer_uid'],
				$contact['id'],
				(strtotime($contact['avatar-date']) > strtotime($contact_old['avatar-date']) || ($author['avatar'] != $contact_old['avatar']))
			);

			/*
			 * The generation is a sign for the reliability of the provided data.
			 * It is used in the socgraph.php to prevent that old contact data
			 * that was relayed over several servers can overwrite contact
			 * data that we received directly.
			 */

			$poco["generation"] = 2;
			$poco["photo"] = $author["avatar"];
			$poco["hide"] = $hide;
			$poco["contact-type"] = $contact["contact-type"];
			$gcid = GContact::update($poco);

			GContact::link($gcid, $importer["importer_uid"], $contact["id"]);
		}

		return $author;
	}

	/**
	 * @brief Transforms activity objects into an XML string
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

		$obj_element = $obj_doc->createElementNS(NAMESPACE_ATOM1, $element);

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
	 * @brief Processes the mail elements
	 *
	 * @param object $xpath    XPath object
	 * @param object $mail     mail elements
	 * @param array  $importer Record of the importer user mixed with contact of the content
	 * @return void
	 * @todo Find good type-hints for all parameter
	 */
	private static function processMail($xpath, $mail, $importer)
	{
		logger("Processing mails");

		/// @TODO Rewrite this to one statement
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
		$msg["seen"] = 0;
		$msg["replied"] = 0;

		DBA::insert('mail', $msg);

		// send notifications.
		/// @TODO Arange this mess
		$notif_params = [
			"type" => NOTIFY_MAIL,
			"notify_flags" => $importer["notify-flags"],
			"language" => $importer["language"],
			"to_name" => $importer["username"],
			"to_email" => $importer["email"],
			"uid" => $importer["importer_uid"],
			"item" => $msg,
			"source_name" => $msg["from-name"],
			"source_link" => $importer["url"],
			"source_photo" => $importer["thumb"],
			"verb" => ACTIVITY_POST,
			"otype" => "mail"
		];

		notification($notif_params);

		logger("Mail is processed, notification was sent.");
	}

	/**
	 * @brief Processes the suggestion elements
	 *
	 * @param object $xpath      XPath object
	 * @param object $suggestion suggestion elements
	 * @param array  $importer   Record of the importer user mixed with contact of the content
	 * @return boolean
	 * @todo Find good type-hints for all parameter
	 */
	private static function processSuggestion($xpath, $suggestion, $importer)
	{
		$a = get_app();

		logger("Processing suggestions");

		/// @TODO Rewrite this to one statement
		$suggest = [];
		$suggest["uid"] = $importer["importer_uid"];
		$suggest["cid"] = $importer["id"];
		$suggest["url"] = $xpath->query("dfrn:url/text()", $suggestion)->item(0)->nodeValue;
		$suggest["name"] = $xpath->query("dfrn:name/text()", $suggestion)->item(0)->nodeValue;
		$suggest["photo"] = $xpath->query("dfrn:photo/text()", $suggestion)->item(0)->nodeValue;
		$suggest["request"] = $xpath->query("dfrn:request/text()", $suggestion)->item(0)->nodeValue;
		$suggest["body"] = $xpath->query("dfrn:note/text()", $suggestion)->item(0)->nodeValue;

		// Does our member already have a friend matching this description?

		$r = q(
			"SELECT `id` FROM `contact` WHERE `name` = '%s' AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
			DBA::escape($suggest["name"]),
			DBA::escape(normalise_link($suggest["url"])),
			intval($suggest["uid"])
		);

		/*
		 * The valid result means the friend we're about to send a friend
		 * suggestion already has them in their contact, which means no further
		 * action is required.
		 *
		 * @see https://github.com/friendica/friendica/pull/3254#discussion_r107315246
		 */
		if (DBA::isResult($r)) {
			return false;
		}

		// Do we already have an fcontact record for this person?

		$fid = 0;
		$r = q(
			"SELECT `id` FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			DBA::escape($suggest["url"]),
			DBA::escape($suggest["name"]),
			DBA::escape($suggest["request"])
		);
		if (DBA::isResult($r)) {
			$fid = $r[0]["id"];

			// OK, we do. Do we already have an introduction for this person ?
			$r = q(
				"SELECT `id` FROM `intro` WHERE `uid` = %d AND `fid` = %d LIMIT 1",
				intval($suggest["uid"]),
				intval($fid)
			);

			/*
			 * The valid result means the friend we're about to send a friend
			 * suggestion already has them in their contact, which means no further
			 * action is required.
			 *
			 * @see https://github.com/friendica/friendica/pull/3254#discussion_r107315246
			 */
			if (DBA::isResult($r)) {
				return false;
			}
		}
		if (!$fid) {
			$r = q(
				"INSERT INTO `fcontact` (`name`,`url`,`photo`,`request`) VALUES ('%s', '%s', '%s', '%s')",
				DBA::escape($suggest["name"]),
				DBA::escape($suggest["url"]),
				DBA::escape($suggest["photo"]),
				DBA::escape($suggest["request"])
			);
		}
		$r = q(
			"SELECT `id` FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			DBA::escape($suggest["url"]),
			DBA::escape($suggest["name"]),
			DBA::escape($suggest["request"])
		);

		/*
		 * If no record in fcontact is found, below INSERT statement will not
		 * link an introduction to it.
		 */
		if (!DBA::isResult($r)) {
			// Database record did not get created. Quietly give up.
			killme();
		}

		$fid = $r[0]["id"];

		$hash = random_string();

		$r = q(
			"INSERT INTO `intro` (`uid`, `fid`, `contact-id`, `note`, `hash`, `datetime`, `blocked`)
			VALUES(%d, %d, %d, '%s', '%s', '%s', %d)",
			intval($suggest["uid"]),
			intval($fid),
			intval($suggest["cid"]),
			DBA::escape($suggest["body"]),
			DBA::escape($hash),
			DBA::escape(DateTimeFormat::utcNow()),
			intval(0)
		);

		notification(
			[
				"type"         => NOTIFY_SUGGEST,
				"notify_flags" => $importer["notify-flags"],
				"language"     => $importer["language"],
				"to_name"      => $importer["username"],
				"to_email"     => $importer["email"],
				"uid"          => $importer["importer_uid"],
				"item"         => $suggest,
				"link"         => System::baseUrl()."/notifications/intros",
				"source_name"  => $importer["name"],
				"source_link"  => $importer["url"],
				"source_photo" => $importer["photo"],
				"verb"         => ACTIVITY_REQ_FRIEND,
				"otype"        => "intro"]
		);

		return true;
	}

	/**
	 * @brief Processes the relocation elements
	 *
	 * @param object $xpath      XPath object
	 * @param object $relocation relocation elements
	 * @param array  $importer   Record of the importer user mixed with contact of the content
	 * @return boolean
	 * @todo Find good type-hints for all parameter
	 */
	private static function processRelocation($xpath, $relocation, $importer)
	{
		logger("Processing relocations");

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
			logger("Query failed to execute, no result returned in " . __FUNCTION__);
			return false;
		}

		$old = $r[0];

		// Update the gcontact entry
		$relocate["server_url"] = preg_replace("=(https?://)(.*)/profile/(.*)=ism", "$1$2", $relocate["url"]);

		$fields = ['name' => $relocate["name"], 'photo' => $relocate["avatar"],
			'url' => $relocate["url"], 'nurl' => normalise_link($relocate["url"]),
			'addr' => $relocate["addr"], 'connect' => $relocate["addr"],
			'notify' => $relocate["notify"], 'server_url' => $relocate["server_url"]];
		DBA::update('gcontact', $fields, ['nurl' => normalise_link($old["url"])]);

		// Update the contact table. We try to find every entry.
		$fields = ['name' => $relocate["name"], 'avatar' => $relocate["avatar"],
			'url' => $relocate["url"], 'nurl' => normalise_link($relocate["url"]),
			'addr' => $relocate["addr"], 'request' => $relocate["request"],
			'confirm' => $relocate["confirm"], 'notify' => $relocate["notify"],
			'poll' => $relocate["poll"], 'site-pubkey' => $relocate["sitepubkey"]];
		$condition = ["(`id` = ?) OR (`nurl` = ?)", $importer["id"], normalise_link($old["url"])];

		DBA::update('contact', $fields, $condition);

		Contact::updateAvatar($relocate["avatar"], $importer["importer_uid"], $importer["id"], true);

		logger('Contacts are updated.');

		/// @TODO
		/// merge with current record, current contents have priority
		/// update record, set url-updated
		/// update profile photos
		/// schedule a scan?
		return true;
	}

	/**
	 * @brief Updates an item
	 *
	 * @param array $current   the current item record
	 * @param array $item      the new item record
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param int   $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @return mixed
	 * @todo set proper type-hints (array?)
	 */
	private static function updateContent($current, $item, $importer, $entrytype)
	{
		$changed = false;

		if (self::isEditedTimestampNewer($current, $item)) {
			// do not accept (ignore) an earlier edit than one we currently have.
			if (DateTimeFormat::utc($item["edited"]) < $current["edited"]) {
				return false;
			}

			$fields = ['title' => defaults($item, 'title', ''), 'body' => defaults($item, 'body', ''),
					'tag' => defaults($item, 'tag', ''), 'changed' => DateTimeFormat::utcNow(),
					'edited' => DateTimeFormat::utc($item["edited"])];

			$condition = ["`uri` = ? AND `uid` IN (0, ?)", $item["uri"], $importer["importer_uid"]];
			Item::update($fields, $condition);

			$changed = true;
		}
		return $changed;
	}

	/**
	 * @brief Detects the entry type of the item
	 *
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param array $item     the new item record
	 *
	 * @return int Is it a toplevel entry, a comment or a relayed comment?
	 * @todo set proper type-hints (array?)
	 */
	private static function getEntryType($importer, $item)
	{
		if ($item["parent-uri"] != $item["uri"]) {
			$community = false;

			if ($importer["page-flags"] == Contact::PAGE_COMMUNITY || $importer["page-flags"] == Contact::PAGE_PRVGROUP) {
				$sql_extra = "";
				$community = true;
				logger("possible community action");
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
				logger("not a community action");
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
	 * @brief Send a "poke"
	 *
	 * @param array $item      the new item record
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param int   $posted_id The record number of item record that was just posted
	 * @return void
	 * @todo set proper type-hints (array?)
	 */
	private static function doPoke($item, $importer, $posted_id)
	{
		$verb = urldecode(substr($item["verb"], strpos($item["verb"], "#")+1));
		if (!$verb) {
			return;
		}
		$xo = XML::parseString($item["object"], false);

		if (($xo->type == ACTIVITY_OBJ_PERSON) && ($xo->id)) {
			// somebody was poked/prodded. Was it me?
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

			if ($Blink && link_compare($Blink, System::baseUrl() . "/profile/" . $importer["nickname"])) {
				$author = DBA::selectFirst('contact', ['name', 'thumb', 'url'], ['id' => $item['author-id']]);

				// send a notification
				notification(
					[
					"type"         => NOTIFY_POKE,
					"notify_flags" => $importer["notify-flags"],
					"language"     => $importer["language"],
					"to_name"      => $importer["username"],
					"to_email"     => $importer["email"],
					"uid"          => $importer["importer_uid"],
					"item"         => $item,
					"link"         => System::baseUrl()."/display/".urlencode(Item::getGuidById($posted_id)),
					"source_name"  => $author["name"],
					"source_link"  => $author["url"],
					"source_photo" => $author["thumb"],
					"verb"         => $item["verb"],
					"otype"        => "person",
					"activity"     => $verb,
					"parent"       => $item["parent"]]
				);
			}
		}
	}

	/**
	 * @brief Processes several actions, depending on the verb
	 *
	 * @param int   $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @param array $importer  Record of the importer user mixed with contact of the content
	 * @param array $item      the new item record
	 * @param bool  $is_like   Is the verb a "like"?
	 *
	 * @return bool Should the processing of the entries be continued?
	 * @todo set proper type-hints (array?)
	 */
	private static function processVerbs($entrytype, $importer, &$item, &$is_like)
	{
		logger("Process verb ".$item["verb"]." and object-type ".$item["object-type"]." for entrytype ".$entrytype, LOGGER_DEBUG);

		if (($entrytype == DFRN::TOP_LEVEL)) {
			// The filling of the the "contact" variable is done for legcy reasons
			// The functions below are partly used by ostatus.php as well - where we have this variable
			$r = q("SELECT * FROM `contact` WHERE `id` = %d", intval($importer["id"]));
			$contact = $r[0];
			$nickname = $contact["nick"];

			// Big question: Do we need these functions? They were part of the "consume_feed" function.
			// This function once was responsible for DFRN and OStatus.
			if (activity_match($item["verb"], ACTIVITY_FOLLOW)) {
				logger("New follower");
				Contact::addRelationship($importer, $contact, $item, $nickname);
				return false;
			}
			if (activity_match($item["verb"], ACTIVITY_UNFOLLOW)) {
				logger("Lost follower");
				Contact::removeFollower($importer, $contact, $item);
				return false;
			}
			if (activity_match($item["verb"], ACTIVITY_REQ_FRIEND)) {
				logger("New friend request");
				Contact::addRelationship($importer, $contact, $item, $nickname, true);
				return false;
			}
			if (activity_match($item["verb"], ACTIVITY_UNFRIEND)) {
				logger("Lost sharer");
				Contact::removeSharer($importer, $contact, $item);
				return false;
			}
		} else {
			if (($item["verb"] == ACTIVITY_LIKE)
				|| ($item["verb"] == ACTIVITY_DISLIKE)
				|| ($item["verb"] == ACTIVITY_ATTEND)
				|| ($item["verb"] == ACTIVITY_ATTENDNO)
				|| ($item["verb"] == ACTIVITY_ATTENDMAYBE)
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
			} else {
				$is_like = false;
			}

			if (($item["verb"] == ACTIVITY_TAG) && ($item["object-type"] == ACTIVITY_OBJ_TAGTERM)) {
				$xo = XML::parseString($item["object"], false);
				$xt = XML::parseString($item["target"], false);

				if ($xt->type == ACTIVITY_OBJ_NOTE) {
					$item_tag = Item::selectFirst(['id', 'tag'], ['uri' => $xt->id, 'uid' => $importer["importer_uid"]]);

					if (!DBA::isResult($item_tag)) {
						logger("Query failed to execute, no result returned in " . __FUNCTION__);
						return false;
					}

					// extract tag, if not duplicate, add to parent item
					if ($xo->content) {
						if (!stristr($item_tag["tag"], trim($xo->content))) {
							$tag = $item_tag["tag"] . (strlen($item_tag["tag"]) ? ',' : '') . '#[url=' . $xo->id . ']'. $xo->content . '[/url]';
							Item::update(['tag' => $tag], ['id' => $item_tag["id"]]);
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * @brief Processes the link elements
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
						$enclosure = $href;
						if (strlen($item["attach"])) {
							$item["attach"] .= ",";
						}

						$item["attach"] .= '[attach]href="' . $href . '" length="' . $length . '" type="' . $type . '" title="' . $title . '"[/attach]';
						break;
				}
			}
		}
	}

	/**
	 * @brief Processes the entry elements which contain the items and comments
	 *
	 * @param array  $header   Array of the header elements that always stay the same
	 * @param object $xpath    XPath object
	 * @param object $entry    entry elements
	 * @param array  $importer Record of the importer user mixed with contact of the content
	 * @param object $xml      xml
	 * @return void
	 * @todo Add type-hints
	 */
	private static function processEntry($header, $xpath, $entry, $importer, $xml)
	{
		logger("Processing entries");

		$item = $header;

		$item["protocol"] = PROTOCOL_DFRN;

		$item["source"] = $xml;

		// Get the uri
		$item["uri"] = XML::getFirstNodeValue($xpath, "atom:id/text()", $entry);

		$item["edited"] = XML::getFirstNodeValue($xpath, "atom:updated/text()", $entry);

		$current = Item::selectFirst(['id', 'uid', 'edited', 'body'],
			['uri' => $item["uri"], 'uid' => $importer["importer_uid"]]
		);
		// Is there an existing item?
		if (DBA::isResult($current) && !self::isEditedTimestampNewer($current, $item)) {
			logger("Item ".$item["uri"]." (".$item['edited'].") already existed.", LOGGER_DEBUG);
			return;
		}

		// Fetch the owner
		$owner = self::fetchauthor($xpath, $entry, $importer, "dfrn:owner", true);

		$owner_unknown = (isset($owner["contact-unknown"]) && $owner["contact-unknown"]);

		$item["owner-link"] = $owner["link"];
		$item["owner-id"] = Contact::getIdForURL($owner["link"], 0);

		// fetch the author
		$author = self::fetchauthor($xpath, $entry, $importer, "atom:author", true);

		$item["author-link"] = $author["link"];
		$item["author-id"] = Contact::getIdForURL($author["link"], 0);

		$item["title"] = XML::getFirstNodeValue($xpath, "atom:title/text()", $entry);

		$item["created"] = XML::getFirstNodeValue($xpath, "atom:published/text()", $entry);

		$item["body"] = XML::getFirstNodeValue($xpath, "dfrn:env/text()", $entry);
		$item["body"] = str_replace([' ',"\t","\r","\n"], ['','','',''], $item["body"]);
		// make sure nobody is trying to sneak some html tags by us
		$item["body"] = notags(base64url_decode($item["body"]));

		$item["body"] = BBCode::limitBodySize($item["body"]);

		/// @todo Do we really need this check for HTML elements? (It was copied from the old function)
		if ((strpos($item['body'], '<') !== false) && (strpos($item['body'], '>') !== false)) {
			$base_url = get_app()->get_baseurl();
			$item['body'] = reltoabs($item['body'], $base_url);

			$item['body'] = html2bb_video($item['body']);

			$item['body'] = OEmbed::HTML2BBCode($item['body']);

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);

			// we shouldn't need a whitelist, because the bbcode converter
			// will strip out any unsupported tags.

			$purifier = new HTMLPurifier($config);
			$item['body'] = $purifier->purify($item['body']);

			$item['body'] = @HTML::toBBCode($item['body']);
		}

		/// @todo We should check for a repeated post and if we know the repeated author.

		// We don't need the content element since "dfrn:env" is always present
		//$item["body"] = $xpath->query("atom:content/text()", $entry)->item(0)->nodeValue;

		$item["location"] = XML::getFirstNodeValue($xpath, "dfrn:location/text()", $entry);

		$item["coord"] = XML::getFirstNodeValue($xpath, "georss:point", $entry);

		$item["private"] = XML::getFirstNodeValue($xpath, "dfrn:private/text()", $entry);

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

		// We store the data from "dfrn:diaspora_signature" in a different table, this is done in "Item::insert"
		$dsprsig = unxmlify(XML::getFirstNodeValue($xpath, "dfrn:diaspora_signature/text()", $entry));
		if ($dsprsig != "") {
			$item["dsprsig"] = $dsprsig;
		}

		$item["verb"] = XML::getFirstNodeValue($xpath, "activity:verb/text()", $entry);

		if (XML::getFirstNodeValue($xpath, "activity:object-type/text()", $entry) != "") {
			$item["object-type"] = XML::getFirstNodeValue($xpath, "activity:object-type/text()", $entry);
		}

		$object = $xpath->query("activity:object", $entry)->item(0);
		$item["object"] = self::transformActivity($xpath, $object, "object");

		if (trim($item["object"]) != "") {
			$r = XML::parseString($item["object"], false);
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
						$termhash = array_shift($parts);
						$termurl = implode(":", $parts);

						if (!empty($item["tag"])) {
							$item["tag"] .= ",";
						} else {
							$item["tag"] = "";
						}

						$item["tag"] .= $termhash . "[url=" . $termurl . "]" . $term . "[/url]";
					}
				}
			}
		}

		$enclosure = "";

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
				$item["object-type"] = ACTIVITY_OBJ_COMMENT;
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

		if ($entrytype == DFRN::REPLY_RC) {
			$item["wall"] = 1;
		} elseif ($entrytype == DFRN::TOP_LEVEL) {
			if (!isset($item["object-type"])) {
				$item["object-type"] = ACTIVITY_OBJ_NOTE;
			}

			// Is it an event?
			if (($item["object-type"] == ACTIVITY_OBJ_EVENT) && !$owner_unknown) {
				logger("Item ".$item["uri"]." seems to contain an event.", LOGGER_DEBUG);
				$ev = Event::fromBBCode($item["body"]);
				if ((x($ev, "desc") || x($ev, "summary")) && x($ev, "start")) {
					logger("Event in item ".$item["uri"]." was found.", LOGGER_DEBUG);
					$ev["cid"]     = $importer["id"];
					$ev["uid"]     = $importer["importer_uid"];
					$ev["uri"]     = $item["uri"];
					$ev["edited"]  = $item["edited"];
					$ev["private"] = $item["private"];
					$ev["guid"]    = $item["guid"];
					$ev["plink"]   = $item["plink"];

					$r = q(
						"SELECT `id` FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						DBA::escape($item["uri"]),
						intval($importer["importer_uid"])
					);
					if (DBA::isResult($r)) {
						$ev["id"] = $r[0]["id"];
					}

					$event_id = Event::store($ev);
					logger("Event ".$event_id." was stored", LOGGER_DEBUG);
					return;
				}
			}
		}

		if (!self::processVerbs($entrytype, $importer, $item, $is_like)) {
			logger("Exiting because 'processVerbs' told us so", LOGGER_DEBUG);
			return;
		}

		// This check is done here to be able to receive connection requests in "processVerbs"
		if (($entrytype == DFRN::TOP_LEVEL) && $owner_unknown) {
			logger("Item won't be stored because user " . $importer["importer_uid"] . " doesn't follow " . $item["owner-link"] . ".", LOGGER_DEBUG);
			return;
		}


		// Update content if 'updated' changes
		if (DBA::isResult($current)) {
			if (self::updateContent($current, $item, $importer, $entrytype)) {
				logger("Item ".$item["uri"]." was updated.", LOGGER_DEBUG);
			} else {
				logger("Item " . $item["uri"] . " already existed.", LOGGER_DEBUG);
			}
			return;
		}

		if (in_array($entrytype, [DFRN::REPLY, DFRN::REPLY_RC])) {
			$posted_id = Item::insert($item);
			$parent = 0;

			if ($posted_id) {
				logger("Reply from contact ".$item["contact-id"]." was stored with id ".$posted_id, LOGGER_DEBUG);

				if ($item['uid'] == 0) {
					Item::distribute($posted_id);
				}

				return true;
			}
		} else { // $entrytype == DFRN::TOP_LEVEL
			if (($importer["uid"] == 0) && ($importer["importer_uid"] != 0)) {
				logger("Contact ".$importer["id"]." isn't known to user ".$importer["importer_uid"].". The post will be ignored.", LOGGER_DEBUG);
				return;
			}
			if (!link_compare($item["owner-link"], $importer["url"])) {
				/*
				 * The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery,
				 * but otherwise there's a possible data mixup on the sender's system.
				 * the tgroup delivery code called from Item::insert will correct it if it's a forum,
				 * but we're going to unconditionally correct it here so that the post will always be owned by our contact.
				 */
				logger('Correcting item owner.', LOGGER_DEBUG);
				$item["owner-link"] = $importer["url"];
				$item["owner-id"] = Contact::getIdForURL($importer["url"], 0);
			}

			if (($importer["rel"] == Contact::FOLLOWER) && (!self::tgroupCheck($importer["importer_uid"], $item))) {
				logger("Contact ".$importer["id"]." is only follower and tgroup check was negative.", LOGGER_DEBUG);
				return;
			}

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.
			$notify = Item::isRemoteSelf($importer, $item);

			$posted_id = Item::insert($item, false, $notify);

			if ($notify) {
				$posted_id = $notify;
			}

			logger("Item was stored with id ".$posted_id, LOGGER_DEBUG);

			if ($item['uid'] == 0) {
				Item::distribute($posted_id);
			}

			if (stristr($item["verb"], ACTIVITY_POKE)) {
				self::doPoke($item, $importer, $posted_id);
			}
		}
	}

	/**
	 * @brief Deletes items
	 *
	 * @param object $xpath    XPath object
	 * @param object $deletion deletion elements
	 * @param array  $importer Record of the importer user mixed with contact of the content
	 * @return void
	 * @todo set proper type-hints
	 */
	private static function processDeletion($xpath, $deletion, $importer)
	{
		logger("Processing deletions");
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
		$item = Item::selectFirst(['id', 'parent', 'contact-id', 'file', 'deleted'], $condition);
		if (!DBA::isResult($item)) {
			logger("Item with uri " . $uri . " for user " . $importer["importer_uid"] . " wasn't found.", LOGGER_DEBUG);
			return;
		}

		if (strstr($item['file'], '[')) {
			logger("Item with uri " . $uri . " for user " . $importer["importer_uid"] . " is filed. So it won't be deleted.", LOGGER_DEBUG);
			return;
		}

		// When it is a starting post it has to belong to the person that wants to delete it
		if (($item['id'] == $item['parent']) && ($item['contact-id'] != $importer["id"])) {
			logger("Item with uri " . $uri . " don't belong to contact " . $importer["id"] . " - ignoring deletion.", LOGGER_DEBUG);
			return;
		}

		// Comments can be deleted by the thread owner or comment owner
		if (($item['id'] != $item['parent']) && ($item['contact-id'] != $importer["id"])) {
			$condition = ['id' => $item['parent'], 'contact-id' => $importer["id"]];
			if (!Item::exists($condition)) {
				logger("Item with uri " . $uri . " wasn't found or mustn't be deleted by contact " . $importer["id"] . " - ignoring deletion.", LOGGER_DEBUG);
				return;
			}
		}

		if ($item["deleted"]) {
			return;
		}

		logger('deleting item '.$item['id'].' uri='.$uri, LOGGER_DEBUG);

		Item::delete(['id' => $item['id']]);
	}

	/**
	 * @brief Imports a DFRN message
	 *
	 * @param string $xml          The DFRN message
	 * @param array  $importer     Record of the importer user mixed with contact of the content
	 * @param bool   $sort_by_date Is used when feeds are polled
	 * @return integer Import status
	 * @todo set proper type-hints
	 */
	public static function import($xml, $importer, $sort_by_date = false)
	{
		if ($xml == "") {
			return 400;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace("atom", NAMESPACE_ATOM1);
		$xpath->registerNamespace("thr", NAMESPACE_THREAD);
		$xpath->registerNamespace("at", NAMESPACE_TOMB);
		$xpath->registerNamespace("media", NAMESPACE_MEDIA);
		$xpath->registerNamespace("dfrn", NAMESPACE_DFRN);
		$xpath->registerNamespace("activity", NAMESPACE_ACTIVITY);
		$xpath->registerNamespace("georss", NAMESPACE_GEORSS);
		$xpath->registerNamespace("poco", NAMESPACE_POCO);
		$xpath->registerNamespace("ostatus", NAMESPACE_OSTATUS);
		$xpath->registerNamespace("statusnet", NAMESPACE_STATUSNET);

		$header = [];
		$header["uid"] = $importer["importer_uid"];
		$header["network"] = NETWORK_DFRN;
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

		logger("Import DFRN message for user " . $importer["importer_uid"] . " from contact " . $importer["id"], LOGGER_DEBUG);

		// is it a public forum? Private forums aren't exposed with this method
		$forum = intval(XML::getFirstNodeValue($xpath, "/atom:feed/dfrn:community/text()"));

		// The account type is new since 3.5.1
		if ($xpath->query("/atom:feed/dfrn:account_type")->length > 0) {
			$accounttype = intval(XML::getFirstNodeValue($xpath, "/atom:feed/dfrn:account_type/text()"));

			if ($accounttype != $importer["contact-type"]) {
				DBA::update('contact', ['contact-type' => $accounttype], ['id' => $importer["id"]]);
			}
			// A forum contact can either have set "forum" or "prv" - but not both
			if (($accounttype == Contact::ACCOUNT_TYPE_COMMUNITY) && (($forum != $importer["forum"]) || ($forum == $importer["prv"]))) {
				$condition = ['(`forum` != ? OR `prv` != ?) AND `id` = ?', $forum, !$forum, $importer["id"]];
				DBA::update('contact', ['forum' => $forum, 'prv' => !$forum], $condition);
			}
		} elseif ($forum != $importer["forum"]) { // Deprecated since 3.5.1
			$condition = ['`forum` != ? AND `id` = ?', $forum, $importer["id"]];
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
		logger("Import done for user " . $importer["importer_uid"] . " from contact " . $importer["id"], LOGGER_DEBUG);
		return 200;
	}

	/**
	 * @param App    $a            App
	 * @param string $contact_nick contact nickname
	 */
	public static function autoRedir(App $a, $contact_nick)
	{
		// prevent looping
		if (x($_REQUEST, 'redir') && intval($_REQUEST['redir'])) {
			return;
		}

		if ((! $contact_nick) || ($contact_nick === $a->user['nickname'])) {
			return;
		}

		if (local_user()) {
			// We need to find out if $contact_nick is a user on this hub, and if so, if I
			// am a contact of that user. However, that user may have other contacts with the
			// same nickname as me on other hubs or other networks. Exclude these by requiring
			// that the contact have a local URL. I will be the only person with my nickname at
			// this URL, so if a result is found, then I am a contact of the $contact_nick user.
			//
			// We also have to make sure that I'm a legitimate contact--I'm not blocked or pending.

			$baseurl = System::baseUrl();
			$domain_st = strpos($baseurl, "://");
			if ($domain_st === false) {
				return;
			}
			$baseurl = substr($baseurl, $domain_st + 3);
			$nurl = normalise_link($baseurl);

			/// @todo Why is there a query for "url" *and* "nurl"? Especially this normalising is strange.
			$r = q("SELECT `id` FROM `contact` WHERE `uid` = (SELECT `uid` FROM `user` WHERE `nickname` = '%s' LIMIT 1)
					AND `nick` = '%s' AND NOT `self` AND (`url` LIKE '%%%s%%' OR `nurl` LIKE '%%%s%%') AND NOT `blocked` AND NOT `pending` LIMIT 1",
				DBA::escape($contact_nick),
				DBA::escape($a->user['nickname']),
				DBA::escape($baseurl),
				DBA::escape($nurl)
			);
			if ((! DBA::isResult($r)) || $r[0]['id'] == remote_user()) {
				return;
			}

			$r = q("SELECT * FROM contact WHERE nick = '%s'
					AND network = '%s' AND uid = %d  AND url LIKE '%%%s%%' LIMIT 1",
				DBA::escape($contact_nick),
				DBA::escape(NETWORK_DFRN),
				intval(local_user()),
				DBA::escape($baseurl)
			);
			if (! DBA::isResult($r)) {
				return;
			}

			$cid = $r[0]['id'];

			$dfrn_id = (($r[0]['issued-id']) ? $r[0]['issued-id'] : $r[0]['dfrn-id']);

			if ($r[0]['duplex'] && $r[0]['issued-id']) {
				$orig_id = $r[0]['issued-id'];
				$dfrn_id = '1:' . $orig_id;
			}
			if ($r[0]['duplex'] && $r[0]['dfrn-id']) {
				$orig_id = $r[0]['dfrn-id'];
				$dfrn_id = '0:' . $orig_id;
			}

			// ensure that we've got a valid ID. There may be some edge cases with forums and non-duplex mode
			// that may have triggered some of the "went to {profile/intro} and got an RSS feed" issues

			if (strlen($dfrn_id) < 3) {
				return;
			}

			$sec = random_string();

			DBA::insert('profile_check', ['uid' => local_user(), 'cid' => $cid, 'dfrn_id' => $dfrn_id, 'sec' => $sec, 'expire' => time() + 45]);

			$url = curPageURL();

			logger('auto_redir: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG);
			$dest = (($url) ? '&destination_url=' . $url : '');
			goaway($r[0]['poll'] . '?dfrn_id=' . $dfrn_id
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest);
		}

		return;
	}

	/**
	 * @brief Returns the activity verb
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
		return ACTIVITY_POST;
	}

	private static function tgroupCheck($uid, $item)
	{
		$mention = false;

		// check that the message originated elsewhere and is a top-level post

		if ($item['wall'] || $item['origin'] || ($item['uri'] != $item['parent-uri'])) {
			return false;
		}

		$u = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($uid)
		);
		if (!DBA::isResult($u)) {
			return false;
		}

		$community_page = ($u[0]['page-flags'] == Contact::PAGE_COMMUNITY);
		$prvgroup = ($u[0]['page-flags'] == Contact::PAGE_PRVGROUP);

		$link = normalise_link(System::baseUrl() . '/profile/' . $u[0]['nickname']);

		/*
		 * Diaspora uses their own hardwired link URL in @-tags
		 * instead of the one we supply with webfinger
		 */
		$dlink = normalise_link(System::baseUrl() . '/u/' . $u[0]['nickname']);

		$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (link_compare($link, $mtch[1]) || link_compare($dlink, $mtch[1])) {
					$mention = true;
					logger('mention found: ' . $mtch[2]);
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
	 */
	private static function isEditedTimestampNewer($existing, $update)
	{
		if (!x($existing, 'edited') || !$existing['edited']) {
			return true;
		}
		if (!x($update, 'edited') || !$update['edited']) {
			return false;
		}

		$existing_edited = DateTimeFormat::utc($existing['edited']);
		$update_edited = DateTimeFormat::utc($update['edited']);

		return (strcmp($existing_edited, $update_edited) < 0);
	}
}
