<?php
/**
 * @file include/dfrn.php
 * @brief The implementation of the dfrn protocol
 *
 * @see https://github.com/friendica/friendica/wiki/Protocol and
 * https://github.com/friendica/friendica/blob/master/spec/dfrn2.pdf
 */

require_once("include/Contact.php");
require_once("include/ostatus.php");
require_once("include/enotify.php");
require_once("include/threads.php");
require_once("include/socgraph.php");
require_once("include/items.php");
require_once("include/tags.php");
require_once("include/files.php");
require_once("include/event.php");
require_once("include/text.php");
require_once("include/oembed.php");
require_once("include/html2bbcode.php");
require_once("include/bbcode.php");
require_once("include/xml.php");

/**
 * @brief This class contain functions to create and send DFRN XML files
 *
 */
class dfrn {

	const DFRN_TOP_LEVEL = 0;	// Top level posting
	const DFRN_REPLY = 1;		// Regular reply that is stored locally
	const DFRN_REPLY_RC = 2;	// Reply that will be relayed

	/**
	 * @brief Generates the atom entries for delivery.php
	 *
	 * This function is used whenever content is transmitted via DFRN.
	 *
	 * @param array $items Item elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN entries
	 */
	public static function entries($items,$owner) {

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		if(! count($items))
			return trim($doc->saveXML());

		foreach($items as $item) {
			$entry = self::entry($doc, "text", $item, $owner, $item["entry:comment-allow"], $item["entry:cid"]);
			$root->appendChild($entry);
		}

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Generate an atom feed for the given user
	 *
	 * This function is called when another server is pulling data from the user feed.
	 *
	 * @param string $dfrn_id DFRN ID from the requesting party
	 * @param string $owner_nick Owner nick name
	 * @param string $last_update Date of the last update
	 * @param int $direction Can be -1, 0 or 1.
	 * @param boolean $onlyheader Output only the header without content? (Default is "no")
	 *
	 * @return string DFRN feed entries
	 */
	public static function feed($dfrn_id, $owner_nick, $last_update, $direction = 0, $onlyheader = false) {

		$a = get_app();

		$sitefeed    = ((strlen($owner_nick)) ? false : true); // not yet implemented, need to rewrite huge chunks of following logic
		$public_feed = (($dfrn_id) ? false : true);
		$starred     = false;   // not yet implemented, possible security issues
		$converse    = false;

		if($public_feed && $a->argc > 2) {
			for($x = 2; $x < $a->argc; $x++) {
				if($a->argv[$x] == 'converse')
					$converse = true;
				if($a->argv[$x] == 'starred')
					$starred = true;
				if($a->argv[$x] == 'category' && $a->argc > ($x + 1) && strlen($a->argv[$x+1]))
					$category = $a->argv[$x+1];
			}
		}



		// default permissions - anonymous user

		$sql_extra = " AND `item`.`allow_cid` = '' AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' ";

		$r = q("SELECT `contact`.*, `user`.`nickname`, `user`.`timezone`, `user`.`page-flags`, `user`.`account-type`
			FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `contact`.`self` AND `user`.`nickname` = '%s' LIMIT 1",
			dbesc($owner_nick)
		);

		if (! dbm::is_result($r)) {
			killme();
		}

		$owner = $r[0];
		$owner_id = $owner['uid'];
		$owner_nick = $owner['nickname'];

		$sql_post_table = "";

		if(! $public_feed) {

			$sql_extra = '';
			switch($direction) {
				case (-1):
					$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
					$my_id = $dfrn_id;
					break;
				case 0:
					$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
					$my_id = '1:' . $dfrn_id;
					break;
				case 1:
					$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
					$my_id = '0:' . $dfrn_id;
					break;
				default:
					return false;
					break; // NOTREACHED
			}

			$r = q("SELECT * FROM `contact` WHERE NOT `blocked` AND `contact`.`uid` = %d $sql_extra LIMIT 1",
				intval($owner_id)
			);

			if (! dbm::is_result($r)) {
				killme();
			}

			$contact = $r[0];
			require_once('include/security.php');
			$groups = init_groups_visitor($contact['id']);

			if(count($groups)) {
				for($x = 0; $x < count($groups); $x ++)
					$groups[$x] = '<' . intval($groups[$x]) . '>' ;
				$gs = implode('|', $groups);
			} else
				$gs = '<<>>' ; // Impossible to match

			$sql_extra = sprintf("
				AND ( `allow_cid` = '' OR     `allow_cid` REGEXP '<%d>' )
				AND ( `deny_cid`  = '' OR NOT `deny_cid`  REGEXP '<%d>' )
				AND ( `allow_gid` = '' OR     `allow_gid` REGEXP '%s' )
				AND ( `deny_gid`  = '' OR NOT `deny_gid`  REGEXP '%s')
			",
				intval($contact['id']),
				intval($contact['id']),
				dbesc($gs),
				dbesc($gs)
			);
		}

		if($public_feed)
			$sort = 'DESC';
		else
			$sort = 'ASC';

		if(! strlen($last_update))
			$last_update = 'now -30 days';

		if(isset($category)) {
			$sql_post_table = sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
					dbesc(protect_sprintf($category)), intval(TERM_OBJ_POST), intval(TERM_CATEGORY), intval($owner_id));
			//$sql_extra .= file_tag_file_query('item',$category,'category');
		}

		if($public_feed) {
			if(! $converse)
				$sql_extra .= " AND `contact`.`self` = 1 ";
		}

		$check_date = datetime_convert('UTC','UTC',$last_update,'Y-m-d H:i:s');

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`,
			`contact`.`name`, `contact`.`network`, `contact`.`photo`, `contact`.`url`,
			`contact`.`name-date`, `contact`.`uri-date`, `contact`.`avatar-date`,
			`contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`sign`.`signed_text`, `sign`.`signature`, `sign`.`signer`
			FROM `item` USE INDEX (`uid_wall_changed`, `uid_type_changed`) $sql_post_table
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			AND (NOT `contact`.`blocked` OR `contact`.`pending`)
			LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id`
			WHERE `item`.`uid` = %d AND `item`.`visible` AND NOT `item`.`moderated` AND `item`.`parent` != 0
			AND `item`.`wall` AND `item`.`changed` > '%s'
			$sql_extra
			ORDER BY `item`.`parent` ".$sort.", `item`.`created` ASC LIMIT 0, 300",
			intval($owner_id),
			dbesc($check_date),
			dbesc($sort)
		);

		// Will check further below if this actually returned results.
		// We will provide an empty feed if that is the case.

		$items = $r;

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$alternatelink = $owner['url'];

		if(isset($category))
			$alternatelink .= "/category/".$category;

		if ($public_feed)
			$author = "dfrn:owner";
		else
			$author = "author";

		$root = self::add_header($doc, $owner, $author, $alternatelink, true);

		// This hook can't work anymore
		//	call_hooks('atom_feed', $atom);

		if (!count($items) OR $onlyheader) {
			$atom = trim($doc->saveXML());

			call_hooks('atom_feed_end', $atom);

			return $atom;
		}

		foreach($items as $item) {

			// prevent private email from leaking.
			if($item['network'] == NETWORK_MAIL)
				continue;

			// public feeds get html, our own nodes use bbcode

			if($public_feed) {
				$type = 'html';
				// catch any email that's in a public conversation and make sure it doesn't leak
				if($item['private'])
					continue;
			} else
				$type = 'text';

			$entry = self::entry($doc, $type, $item, $owner, true);
			$root->appendChild($entry);

		}

		$atom = trim($doc->saveXML());

		call_hooks('atom_feed_end', $atom);

		return $atom;
	}

	/**
	 * @brief Create XML text for DFRN mails
	 *
	 * @param array $item message elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN mail
	 */
	public static function mail($item, $owner) {
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		$mail = $doc->createElement("dfrn:mail");
		$sender = $doc->createElement("dfrn:sender");

		xml::add_element($doc, $sender, "dfrn:name", $owner['name']);
		xml::add_element($doc, $sender, "dfrn:uri", $owner['url']);
		xml::add_element($doc, $sender, "dfrn:avatar", $owner['thumb']);

		$mail->appendChild($sender);

		xml::add_element($doc, $mail, "dfrn:id", $item['uri']);
		xml::add_element($doc, $mail, "dfrn:in-reply-to", $item['parent-uri']);
		xml::add_element($doc, $mail, "dfrn:sentdate", datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME));
		xml::add_element($doc, $mail, "dfrn:subject", $item['title']);
		xml::add_element($doc, $mail, "dfrn:content", $item['body']);

		$root->appendChild($mail);

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Create XML text for DFRN friend suggestions
	 *
	 * @param array $item suggestion elements
	 * @param array $owner Owner record
	 *
	 * @return string DFRN suggestions
	 */
	public static function fsuggest($item, $owner) {
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		$suggest = $doc->createElement("dfrn:suggest");

		xml::add_element($doc, $suggest, "dfrn:url", $item['url']);
		xml::add_element($doc, $suggest, "dfrn:name", $item['name']);
		xml::add_element($doc, $suggest, "dfrn:photo", $item['photo']);
		xml::add_element($doc, $suggest, "dfrn:request", $item['request']);
		xml::add_element($doc, $suggest, "dfrn:note", $item['note']);

		$root->appendChild($suggest);

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Create XML text for DFRN relocations
	 *
	 * @param array $owner Owner record
	 * @param int $uid User ID
	 *
	 * @return string DFRN relocations
	 */
	public static function relocate($owner, $uid) {

		/* get site pubkey. this could be a new installation with no site keys*/
		$pubkey = get_config('system','site_pubkey');
		if(! $pubkey) {
			$res = new_keypair(1024);
			set_config('system','site_prvkey', $res['prvkey']);
			set_config('system','site_pubkey', $res['pubkey']);
		}

		$rp = q("SELECT `resource-id` , `scale`, type FROM `photo`
				WHERE `profile` = 1 AND `uid` = %d ORDER BY scale;", $uid);
		$photos = array();
		$ext = Photo::supportedTypes();

		foreach($rp as $p)
			$photos[$p['scale']] = app::get_baseurl().'/photo/'.$p['resource-id'].'-'.$p['scale'].'.'.$ext[$p['type']];

		unset($rp, $ext);

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner, "dfrn:owner", "", false);

		$relocate = $doc->createElement("dfrn:relocate");

		xml::add_element($doc, $relocate, "dfrn:url", $owner['url']);
		xml::add_element($doc, $relocate, "dfrn:name", $owner['name']);
		xml::add_element($doc, $relocate, "dfrn:addr", $owner['addr']);
		xml::add_element($doc, $relocate, "dfrn:avatar", $owner['avatar']);
		xml::add_element($doc, $relocate, "dfrn:photo", $photos[4]);
		xml::add_element($doc, $relocate, "dfrn:thumb", $photos[5]);
		xml::add_element($doc, $relocate, "dfrn:micro", $photos[6]);
		xml::add_element($doc, $relocate, "dfrn:request", $owner['request']);
		xml::add_element($doc, $relocate, "dfrn:confirm", $owner['confirm']);
		xml::add_element($doc, $relocate, "dfrn:notify", $owner['notify']);
		xml::add_element($doc, $relocate, "dfrn:poll", $owner['poll']);
		xml::add_element($doc, $relocate, "dfrn:sitepubkey", get_config('system','site_pubkey'));

		$root->appendChild($relocate);

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Adds the header elements for the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param array $owner Owner record
	 * @param string $authorelement Element name for the author
	 * @param string $alternatelink link to profile or category
	 * @param bool $public Is it a header for public posts?
	 *
	 * @return object XML root object
	 */
	private function add_header($doc, $owner, $authorelement, $alternatelink = "", $public = false) {

		if ($alternatelink == "")
			$alternatelink = $owner['url'];

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

		xml::add_element($doc, $root, "id", app::get_baseurl()."/profile/".$owner["nick"]);
		xml::add_element($doc, $root, "title", $owner["name"]);

		$attributes = array("uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION);
		xml::add_element($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);

		$attributes = array("rel" => "license", "href" => "http://creativecommons.org/licenses/by/3.0/");
		xml::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $alternatelink);
		xml::add_element($doc, $root, "link", "", $attributes);


		if ($public) {
			// DFRN itself doesn't uses this. But maybe someone else wants to subscribe to the public feed.
			ostatus::hublinks($doc, $root);

			$attributes = array("rel" => "salmon", "href" => app::get_baseurl()."/salmon/".$owner["nick"]);
			xml::add_element($doc, $root, "link", "", $attributes);

			$attributes = array("rel" => "http://salmon-protocol.org/ns/salmon-replies", "href" => app::get_baseurl()."/salmon/".$owner["nick"]);
			xml::add_element($doc, $root, "link", "", $attributes);

			$attributes = array("rel" => "http://salmon-protocol.org/ns/salmon-mention", "href" => app::get_baseurl()."/salmon/".$owner["nick"]);
			xml::add_element($doc, $root, "link", "", $attributes);
		}

		// For backward compatibility we keep this element
		if ($owner['page-flags'] == PAGE_COMMUNITY)
			xml::add_element($doc, $root, "dfrn:community", 1);

		// The former element is replaced by this one
		xml::add_element($doc, $root, "dfrn:account_type", $owner["account-type"]);

		/// @todo We need a way to transmit the different page flags like "PAGE_PRVGROUP"

		xml::add_element($doc, $root, "updated", datetime_convert("UTC", "UTC", "now", ATOM_TIME));

		$author = self::add_author($doc, $owner, $authorelement, $public);
		$root->appendChild($author);

		return $root;
	}

	/**
	 * @brief Adds the author element in the header for the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param array $owner Owner record
	 * @param string $authorelement Element name for the author
	 *
	 * @return object XML author object
	 */
	private function add_author($doc, $owner, $authorelement, $public) {

		// Is the profile hidden or shouldn't be published in the net? Then add the "hide" element
		$r = q("SELECT `id` FROM `profile` INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE (`hidewall` OR NOT `net-publish`) AND `user`.`uid` = %d",
			intval($owner['uid']));
		if ($r)
			$hidewall = true;
		else
			$hidewall = false;

		$author = $doc->createElement($authorelement);

		$namdate = datetime_convert('UTC', 'UTC', $owner['name-date'].'+00:00', ATOM_TIME);
		$uridate = datetime_convert('UTC', 'UTC', $owner['uri-date'].'+00:00', ATOM_TIME);
		$picdate = datetime_convert('UTC', 'UTC', $owner['avatar-date'].'+00:00', ATOM_TIME);

		if (!$public OR !$hidewall)
			$attributes = array("dfrn:updated" => $namdate);
		else
			$attributes = array();

		xml::add_element($doc, $author, "name", $owner["name"], $attributes);
		xml::add_element($doc, $author, "uri", app::get_baseurl().'/profile/'.$owner["nickname"], $attributes);
		xml::add_element($doc, $author, "dfrn:handle", $owner["addr"], $attributes);

		$attributes = array("rel" => "photo", "type" => "image/jpeg",
					"media:width" => 175, "media:height" => 175, "href" => $owner['photo']);

		if (!$public OR !$hidewall)
			$attributes["dfrn:updated"] = $picdate;

		xml::add_element($doc, $author, "link", "", $attributes);

		$attributes["rel"] = "avatar";
		xml::add_element($doc, $author, "link", "", $attributes);

		if ($hidewall)
			xml::add_element($doc, $author, "dfrn:hide", "true");

		// The following fields will only be generated if the data isn't meant for a public feed
		if ($public)
			return $author;

		$birthday = feed_birthday($owner['uid'], $owner['timezone']);

		if ($birthday)
			xml::add_element($doc, $author, "dfrn:birthday", $birthday);

		// Only show contact details when we are allowed to
		$r = q("SELECT `profile`.`about`, `profile`.`name`, `profile`.`homepage`, `user`.`nickname`,
				`user`.`timezone`, `profile`.`locality`, `profile`.`region`, `profile`.`country-name`,
				`profile`.`pub_keywords`, `profile`.`xmpp`, `profile`.`dob`
			FROM `profile`
				INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE `profile`.`is-default` AND NOT `user`.`hidewall` AND `user`.`uid` = %d",
			intval($owner['uid']));
		if ($r) {
			$profile = $r[0];

			xml::add_element($doc, $author, "poco:displayName", $profile["name"]);
			xml::add_element($doc, $author, "poco:updated", $namdate);

			if (trim($profile["dob"]) != "0000-00-00")
				xml::add_element($doc, $author, "poco:birthday", "0000-".date("m-d", strtotime($profile["dob"])));

			xml::add_element($doc, $author, "poco:note", $profile["about"]);
			xml::add_element($doc, $author, "poco:preferredUsername", $profile["nickname"]);

			$savetz = date_default_timezone_get();
			date_default_timezone_set($profile["timezone"]);
			xml::add_element($doc, $author, "poco:utcOffset", date("P"));
			date_default_timezone_set($savetz);

			if (trim($profile["homepage"]) != "") {
				$urls = $doc->createElement("poco:urls");
				xml::add_element($doc, $urls, "poco:type", "homepage");
				xml::add_element($doc, $urls, "poco:value", $profile["homepage"]);
				xml::add_element($doc, $urls, "poco:primary", "true");
				$author->appendChild($urls);
			}

			if (trim($profile["pub_keywords"]) != "") {
				$keywords = explode(",", $profile["pub_keywords"]);

				foreach ($keywords AS $keyword)
					xml::add_element($doc, $author, "poco:tags", trim($keyword));

			}

			if (trim($profile["xmpp"]) != "") {
				$ims = $doc->createElement("poco:ims");
				xml::add_element($doc, $ims, "poco:type", "xmpp");
				xml::add_element($doc, $ims, "poco:value", $profile["xmpp"]);
				xml::add_element($doc, $ims, "poco:primary", "true");
				$author->appendChild($ims);
			}

			if (trim($profile["locality"].$profile["region"].$profile["country-name"]) != "") {
				$element = $doc->createElement("poco:address");

				xml::add_element($doc, $element, "poco:formatted", formatted_location($profile));

				if (trim($profile["locality"]) != "")
					xml::add_element($doc, $element, "poco:locality", $profile["locality"]);

				if (trim($profile["region"]) != "")
					xml::add_element($doc, $element, "poco:region", $profile["region"]);

				if (trim($profile["country-name"]) != "")
					xml::add_element($doc, $element, "poco:country", $profile["country-name"]);

				$author->appendChild($element);
			}
		}

		return $author;
	}

	/**
	 * @brief Adds the author elements in the "entry" elements of the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param string $element Element name for the author
	 * @param string $contact_url Link of the contact
	 * @param array $items Item elements
	 *
	 * @return object XML author object
	 */
	private function add_entry_author($doc, $element, $contact_url, $item) {

		$contact = get_contact_details_by_url($contact_url, $item["uid"]);

		$author = $doc->createElement($element);
		xml::add_element($doc, $author, "name", $contact["name"]);
		xml::add_element($doc, $author, "uri", $contact["url"]);
		xml::add_element($doc, $author, "dfrn:handle", $contact["addr"]);

		/// @Todo
		/// - Check real image type and image size
		/// - Check which of these boths elements we should use
		$attributes = array(
				"rel" => "photo",
				"type" => "image/jpeg",
				"media:width" => 80,
				"media:height" => 80,
				"href" => $contact["photo"]);
		xml::add_element($doc, $author, "link", "", $attributes);

		$attributes = array(
				"rel" => "avatar",
				"type" => "image/jpeg",
				"media:width" => 80,
				"media:height" => 80,
				"href" => $contact["photo"]);
		xml::add_element($doc, $author, "link", "", $attributes);

		return $author;
	}

	/**
	 * @brief Adds the activity elements
	 *
	 * @param object $doc XML document
	 * @param string $element Element name for the activity
	 * @param string $activity activity value
	 *
	 * @return object XML activity object
	 */
	private function create_activity($doc, $element, $activity) {

		if($activity) {
			$entry = $doc->createElement($element);

			$r = parse_xml_string($activity, false);
			if(!$r)
				return false;
			if($r->type)
				xml::add_element($doc, $entry, "activity:object-type", $r->type);
			if($r->id)
				xml::add_element($doc, $entry, "id", $r->id);
			if($r->title)
				xml::add_element($doc, $entry, "title", $r->title);
			if($r->link) {
				if(substr($r->link,0,1) == '<') {
					if(strstr($r->link,'&') && (! strstr($r->link,'&amp;')))
						$r->link = str_replace('&','&amp;', $r->link);

					$r->link = preg_replace('/\<link(.*?)\"\>/','<link$1"/>',$r->link);

					// XML does need a single element as root element so we add a dummy element here
					$data = parse_xml_string("<dummy>".$r->link."</dummy>", false);
					if (is_object($data)) {
						foreach ($data->link AS $link) {
							$attributes = array();
							foreach ($link->attributes() AS $parameter => $value)
								$attributes[$parameter] = $value;
							xml::add_element($doc, $entry, "link", "", $attributes);
						}
					}
				} else {
					$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $r->link);
					xml::add_element($doc, $entry, "link", "", $attributes);
				}
			}
			if($r->content)
				xml::add_element($doc, $entry, "content", bbcode($r->content), array("type" => "html"));

			return $entry;
		}

		return false;
	}

	/**
	 * @brief Adds the elements for attachments
	 *
	 * @param object $doc XML document
	 * @param object $root XML root
	 * @param array $item Item element
	 *
	 * @return object XML attachment object
	 */
	private function get_attachment($doc, $root, $item) {
		$arr = explode('[/attach],',$item['attach']);
		if(count($arr)) {
			foreach($arr as $r) {
				$matches = false;
				$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"|',$r,$matches);
				if($cnt) {
					$attributes = array("rel" => "enclosure",
							"href" => $matches[1],
							"type" => $matches[3]);

					if(intval($matches[2]))
						$attributes["length"] = intval($matches[2]);

					if(trim($matches[4]) != "")
						$attributes["title"] = trim($matches[4]);

					xml::add_element($doc, $root, "link", "", $attributes);
				}
			}
		}
	}

	/**
	 * @brief Adds the "entry" elements for the DFRN protocol
	 *
	 * @param object $doc XML document
	 * @param string $type "text" or "html"
	 * @param array $item Item element
	 * @param array $owner Owner record
	 * @param bool $comment Trigger the sending of the "comment" element
	 * @param int $cid Contact ID of the recipient
	 *
	 * @return object XML entry object
	 */
	private function entry($doc, $type, $item, $owner, $comment = false, $cid = 0) {

		$mentioned = array();

		if(!$item['parent'])
			return;

		if($item['deleted']) {
			$attributes = array("ref" => $item['uri'], "when" => datetime_convert('UTC','UTC',$item['edited'] . '+00:00',ATOM_TIME));
			return xml::create_element($doc, "at:deleted-entry", "", $attributes);
		}

		$entry = $doc->createElement("entry");

		if($item['allow_cid'] || $item['allow_gid'] || $item['deny_cid'] || $item['deny_gid'])
			$body = fix_private_photos($item['body'],$owner['uid'],$item,$cid);
		else
			$body = $item['body'];

		// Remove the abstract element. It is only locally important.
		$body = remove_abstract($body);

		if ($type == 'html') {
			$htmlbody = $body;

			if ($item['title'] != "")
				$htmlbody = "[b]".$item['title']."[/b]\n\n".$htmlbody;

			$htmlbody = bbcode($htmlbody, false, false, 7);
		}

		$author = self::add_entry_author($doc, "author", $item["author-link"], $item);
		$entry->appendChild($author);

		$dfrnowner = self::add_entry_author($doc, "dfrn:owner", $item["owner-link"], $item);
		$entry->appendChild($dfrnowner);

		if(($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
			$parent = q("SELECT `guid` FROM `item` WHERE `id` = %d", intval($item["parent"]));
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);
			$attributes = array("ref" => $parent_item, "type" => "text/html",
						"href" => app::get_baseurl().'/display/'.$parent[0]['guid'],
						"dfrn:diaspora_guid" => $parent[0]['guid']);
			xml::add_element($doc, $entry, "thr:in-reply-to", "", $attributes);
		}

		xml::add_element($doc, $entry, "id", $item["uri"]);
		xml::add_element($doc, $entry, "title", $item["title"]);

		xml::add_element($doc, $entry, "published", datetime_convert("UTC","UTC",$item["created"]."+00:00",ATOM_TIME));
		xml::add_element($doc, $entry, "updated", datetime_convert("UTC","UTC",$item["edited"]."+00:00",ATOM_TIME));

		// "dfrn:env" is used to read the content
		xml::add_element($doc, $entry, "dfrn:env", base64url_encode($body, true));

		// The "content" field is not read by the receiver. We could remove it when the type is "text"
		// We keep it at the moment, maybe there is some old version that doesn't read "dfrn:env"
		xml::add_element($doc, $entry, "content", (($type == 'html') ? $htmlbody : $body), array("type" => $type));

		// We save this value in "plink". Maybe we should read it from there as well?
		xml::add_element($doc, $entry, "link", "", array("rel" => "alternate", "type" => "text/html",
								"href" => app::get_baseurl()."/display/".$item["guid"]));

		// "comment-allow" is some old fashioned stuff for old Friendica versions.
		// It is included in the rewritten code for completeness
		if ($comment)
			xml::add_element($doc, $entry, "dfrn:comment-allow", intval($item['last-child']));

		if($item['location'])
			xml::add_element($doc, $entry, "dfrn:location", $item['location']);

		if($item['coord'])
			xml::add_element($doc, $entry, "georss:point", $item['coord']);

		if(($item['private']) || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
			xml::add_element($doc, $entry, "dfrn:private", (($item['private']) ? $item['private'] : 1));

		if($item['extid'])
			xml::add_element($doc, $entry, "dfrn:extid", $item['extid']);

		if($item['bookmark'])
			xml::add_element($doc, $entry, "dfrn:bookmark", "true");

		if($item['app'])
			xml::add_element($doc, $entry, "statusnet:notice_info", "", array("local_id" => $item['id'], "source" => $item['app']));

		xml::add_element($doc, $entry, "dfrn:diaspora_guid", $item["guid"]);

		// The signed text contains the content in Markdown, the sender handle and the signatur for the content
		// It is needed for relayed comments to Diaspora.
		if($item['signed_text']) {
			$sign = base64_encode(json_encode(array('signed_text' => $item['signed_text'],'signature' => $item['signature'],'signer' => $item['signer'])));
			xml::add_element($doc, $entry, "dfrn:diaspora_signature", $sign);
		}

		xml::add_element($doc, $entry, "activity:verb", construct_verb($item));

		if ($item['object-type'] != "")
			xml::add_element($doc, $entry, "activity:object-type", $item['object-type']);
		elseif ($item['id'] == $item['parent'])
			xml::add_element($doc, $entry, "activity:object-type", ACTIVITY_OBJ_NOTE);
		else
			xml::add_element($doc, $entry, "activity:object-type", ACTIVITY_OBJ_COMMENT);

		$actobj = self::create_activity($doc, "activity:object", $item['object']);
		if ($actobj)
			$entry->appendChild($actobj);

		$actarg = self::create_activity($doc, "activity:target", $item['target']);
		if ($actarg)
			$entry->appendChild($actarg);

		$tags = item_getfeedtags($item);

		if(count($tags)) {
			foreach($tags as $t)
				if (($type != 'html') OR ($t[0] != "@"))
					xml::add_element($doc, $entry, "category", "", array("scheme" => "X-DFRN:".$t[0].":".$t[1], "term" => $t[2]));
		}

		if(count($tags))
			foreach($tags as $t)
				if ($t[0] == "@")
					$mentioned[$t[1]] = $t[1];

		foreach ($mentioned AS $mention) {
			$r = q("SELECT `forum`, `prv` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s'",
				intval($owner["uid"]),
				dbesc(normalise_link($mention)));
			if ($r[0]["forum"] OR $r[0]["prv"])
				xml::add_element($doc, $entry, "link", "", array("rel" => "mentioned",
											"ostatus:object-type" => ACTIVITY_OBJ_GROUP,
											"href" => $mention));
			else
				xml::add_element($doc, $entry, "link", "", array("rel" => "mentioned",
											"ostatus:object-type" => ACTIVITY_OBJ_PERSON,
											"href" => $mention));
		}

		self::get_attachment($doc, $entry, $item);

		return $entry;
	}

	/**
	 * @brief Delivers the atom content to the contacts
	 *
	 * @param array $owner Owner record
	 * @param array $contactr Contact record of the receiver
	 * @param string $atom Content that will be transmitted
	 * @param bool $dissolve (to be documented)
	 *
	 * @return int Deliver status. -1 means an error.
	 */
	public static function deliver($owner,$contact,$atom, $dissolve = false) {

		$a = get_app();

		$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);

		if($contact['duplex'] && $contact['dfrn-id'])
			$idtosend = '0:' . $orig_id;
		if($contact['duplex'] && $contact['issued-id'])
			$idtosend = '1:' . $orig_id;


		$rino = get_config('system','rino_encrypt');
		$rino = intval($rino);
		// use RINO1 if mcrypt isn't installed and RINO2 was selected
		if ($rino==2 and !function_exists('mcrypt_create_iv')) $rino=1;

		logger("Local rino version: ". $rino, LOGGER_DEBUG);

		$ssl_val = intval(get_config('system','ssl_policy'));
		$ssl_policy = '';

		switch($ssl_val){
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

		$xml = fetch_url($url);

		$curl_stat = $a->get_curl_code();
		if(! $curl_stat)
			return(-1); // timed out

		logger('dfrn_deliver: ' . $xml, LOGGER_DATA);

		if(! $xml)
			return 3;

		if(strpos($xml,'<?xml') === false) {
			logger('dfrn_deliver: no valid XML returned');
			logger('dfrn_deliver: returned XML: ' . $xml, LOGGER_DATA);
			return 3;
		}

		$res = parse_xml_string($xml);

		if((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id)))
			return (($res->status) ? $res->status : 3);

		$postvars     = array();
		$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
		$challenge    = hex2bin((string) $res->challenge);
		$perm         = (($res->perm) ? $res->perm : null);
		$dfrn_version = (float) (($res->dfrn_version) ? $res->dfrn_version : 2.0);
		$rino_remote_version = intval($res->rino);
		$page         = (($owner['page-flags'] == PAGE_COMMUNITY) ? 1 : 0);

		logger("Remote rino version: ".$rino_remote_version." for ".$contact["url"], LOGGER_DEBUG);

		if($owner['page-flags'] == PAGE_PRVGROUP)
			$page = 2;

		$final_dfrn_id = '';

		if($perm) {
			if((($perm == 'rw') && (! intval($contact['writable'])))
				|| (($perm == 'r') && (intval($contact['writable'])))) {
				q("update contact set writable = %d where id = %d",
					intval(($perm == 'rw') ? 1 : 0),
					intval($contact['id'])
				);
				$contact['writable'] = (string) 1 - intval($contact['writable']);
			}
		}

		if(($contact['duplex'] && strlen($contact['pubkey']))
			|| ($owner['page-flags'] == PAGE_COMMUNITY && strlen($contact['pubkey']))
			|| ($contact['rel'] == CONTACT_IS_SHARING && strlen($contact['pubkey']))) {
			openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
			openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);
		} else {
			openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['prvkey']);
			openssl_private_decrypt($challenge,$postvars['challenge'],$contact['prvkey']);
		}

		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

		if(strpos($final_dfrn_id,':') == 1)
			$final_dfrn_id = substr($final_dfrn_id,2);

		if($final_dfrn_id != $orig_id) {
			logger('dfrn_deliver: wrong dfrn_id.');
			// did not decode properly - cannot trust this site
			return 3;
		}

		$postvars['dfrn_id']      = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		if($dissolve)
			$postvars['dissolve'] = '1';


		if((($contact['rel']) && ($contact['rel'] != CONTACT_IS_SHARING) && (! $contact['blocked'])) || ($owner['page-flags'] == PAGE_COMMUNITY)) {
			$postvars['data'] = $atom;
			$postvars['perm'] = 'rw';
		} else {
			$postvars['data'] = str_replace('<dfrn:comment-allow>1','<dfrn:comment-allow>0',$atom);
			$postvars['perm'] = 'r';
		}

		$postvars['ssl_policy'] = $ssl_policy;

		if($page)
			$postvars['page'] = $page;


		if($rino>0 && $rino_remote_version>0 && (! $dissolve)) {
			logger('rino version: '. $rino_remote_version);

			switch($rino_remote_version) {
				case 1:
					// Deprecated rino version!
					$key = substr(random_string(),0,16);
					$data = aes_encrypt($postvars['data'],$key);
					break;
				case 2:
					// RINO 2 based on php-encryption
					try {
						$key = Crypto::createNewRandomKey();
					} catch (CryptoTestFailed $ex) {
						logger('Cannot safely create a key');
						return -1;
					} catch (CannotPerformOperation $ex) {
						logger('Cannot safely create a key');
						return -1;
					}
					try {
						$data = Crypto::encrypt($postvars['data'], $key);
					} catch (CryptoTestFailed $ex) {
						logger('Cannot safely perform encryption');
						return -1;
					} catch (CannotPerformOperation $ex) {
						logger('Cannot safely perform encryption');
						return -1;
					}
					break;
				default:
					logger("rino: invalid requested verision '$rino_remote_version'");
					return -1;
			}

			$postvars['rino'] = $rino_remote_version;
			$postvars['data'] = bin2hex($data);

			#logger('rino: sent key = ' . $key, LOGGER_DEBUG);


			if($dfrn_version >= 2.1) {
				if(($contact['duplex'] && strlen($contact['pubkey']))
					|| ($owner['page-flags'] == PAGE_COMMUNITY && strlen($contact['pubkey']))
					|| ($contact['rel'] == CONTACT_IS_SHARING && strlen($contact['pubkey'])))

					openssl_public_encrypt($key,$postvars['key'],$contact['pubkey']);
				else
					openssl_private_encrypt($key,$postvars['key'],$contact['prvkey']);

			} else {
				if(($contact['duplex'] && strlen($contact['prvkey'])) || ($owner['page-flags'] == PAGE_COMMUNITY))
					openssl_private_encrypt($key,$postvars['key'],$contact['prvkey']);
				else
					openssl_public_encrypt($key,$postvars['key'],$contact['pubkey']);

			}

			logger('md5 rawkey ' . md5($postvars['key']));

			$postvars['key'] = bin2hex($postvars['key']);
		}


		logger('dfrn_deliver: ' . "SENDING: " . print_r($postvars,true), LOGGER_DATA);

		$xml = post_url($contact['notify'],$postvars);

		logger('dfrn_deliver: ' . "RECEIVED: " . $xml, LOGGER_DATA);

		$curl_stat = $a->get_curl_code();
		if((! $curl_stat) || (! strlen($xml)))
			return(-1); // timed out

		if(($curl_stat == 503) && (stristr($a->get_curl_headers(),'retry-after')))
			return(-1);

		if(strpos($xml,'<?xml') === false) {
			logger('dfrn_deliver: phase 2: no valid XML returned');
			logger('dfrn_deliver: phase 2: returned XML: ' . $xml, LOGGER_DATA);
			return 3;
		}

		if($contact['term-date'] != '0000-00-00 00:00:00') {
			logger("dfrn_deliver: $url back from the dead - removing mark for death");
			require_once('include/Contact.php');
			unmark_for_death($contact);
		}

		$res = parse_xml_string($xml);

		return $res->status;
	}

	/**
	 * @brief Add new birthday event for this person
	 *
	 * @param array $contact Contact record
	 * @param string $birthday Birthday of the contact
	 *
	 */
	private function birthday_event($contact, $birthday) {

		logger("updating birthday: ".$birthday." for contact ".$contact["id"]);

		$bdtext = sprintf(t("%s\'s birthday"), $contact["name"]);
		$bdtext2 = sprintf(t("Happy Birthday %s"), " [url=".$contact["url"]."]".$contact["name"]."[/url]") ;


		$r = q("INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`summary`,`desc`,`type`)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($contact["uid"]),
			intval($contact["id"]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert("UTC","UTC", $birthday)),
			dbesc(datetime_convert("UTC","UTC", $birthday." + 1 day ")),
			dbesc($bdtext),
			dbesc($bdtext2),
			dbesc("birthday")
		);
	}

	/**
	 * @brief Fetch the author data from head or entry items
	 *
	 * @param object $xpath XPath object
	 * @param object $context In which context should the data be searched
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param string $element Element name from which the data is fetched
	 * @param bool $onlyfetch Should the data only be fetched or should it update the contact record as well
	 *
	 * @return Returns an array with relevant data of the author
	 */
	private function fetchauthor($xpath, $context, $importer, $element, $onlyfetch, $xml = "") {

		$author = array();
		$author["name"] = $xpath->evaluate($element."/atom:name/text()", $context)->item(0)->nodeValue;
		$author["link"] = $xpath->evaluate($element."/atom:uri/text()", $context)->item(0)->nodeValue;

		$r = q("SELECT `id`, `uid`, `url`, `network`, `avatar-date`, `name-date`, `uri-date`, `addr`,
				`name`, `nick`, `about`, `location`, `keywords`, `xmpp`, `bdyear`, `bd`, `hidden`, `contact-type`
				FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` != '%s'",
			intval($importer["uid"]), dbesc(normalise_link($author["link"])), dbesc(NETWORK_STATUSNET));
		if ($r) {
			$contact = $r[0];
			$author["contact-id"] = $r[0]["id"];
			$author["network"] = $r[0]["network"];
		} else {
			if (!$onlyfetch)
				logger("Contact ".$author["link"]." wasn't found for user ".$importer["uid"]." XML: ".$xml, LOGGER_DEBUG);

			$author["contact-id"] = $importer["id"];
			$author["network"] = $importer["network"];
			$onlyfetch = true;
		}

		// Until now we aren't serving different sizes - but maybe later
		$avatarlist = array();
		/// @todo check if "avatar" or "photo" would be the best field in the specification
		$avatars = $xpath->query($element."/atom:link[@rel='avatar']", $context);
		foreach($avatars AS $avatar) {
			$href = "";
			$width = 0;
			foreach($avatar->attributes AS $attributes) {
				if ($attributes->name == "href")
					$href = $attributes->textContent;
				if ($attributes->name == "width")
					$width = $attributes->textContent;
				if ($attributes->name == "updated")
					$contact["avatar-date"] = $attributes->textContent;
			}
			if (($width > 0) AND ($href != ""))
				$avatarlist[$width] = $href;
		}
		if (count($avatarlist) > 0) {
			krsort($avatarlist);
			$author["avatar"] = current($avatarlist);
		}

		if ($r AND !$onlyfetch) {
			logger("Check if contact details for contact ".$r[0]["id"]." (".$r[0]["nick"].") have to be updated.", LOGGER_DEBUG);

			$poco = array("url" => $contact["url"]);

			// When was the last change to name or uri?
			$name_element = $xpath->query($element."/atom:name", $context)->item(0);
			foreach($name_element->attributes AS $attributes)
				if ($attributes->name == "updated")
					$poco["name-date"] = $attributes->textContent;

			$link_element = $xpath->query($element."/atom:link", $context)->item(0);
			foreach($link_element->attributes AS $attributes)
				if ($attributes->name == "updated")
					$poco["uri-date"] = $attributes->textContent;

			// Update contact data
			$value = $xpath->evaluate($element."/dfrn:handle/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$poco["addr"] = $value;

			$value = $xpath->evaluate($element."/poco:displayName/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$poco["name"] = $value;

			$value = $xpath->evaluate($element."/poco:preferredUsername/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$poco["nick"] = $value;

			$value = $xpath->evaluate($element."/poco:note/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$poco["about"] = $value;

			$value = $xpath->evaluate($element."/poco:address/poco:formatted/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$poco["location"] = $value;

			/// @todo Only search for elements with "poco:type" = "xmpp"
			$value = $xpath->evaluate($element."/poco:ims/poco:value/text()", $context)->item(0)->nodeValue;
			if ($value != "")
				$poco["xmpp"] = $value;

			/// @todo Add support for the following fields that we don't support by now in the contact table:
			/// - poco:utcOffset
			/// - poco:urls
			/// - poco:locality
			/// - poco:region
			/// - poco:country

			// If the "hide" element is present then the profile isn't searchable.
			$hide = intval($xpath->evaluate($element."/dfrn:hide/text()", $context)->item(0)->nodeValue == "true");

			logger("Hidden status for contact ".$contact["url"].": ".$hide, LOGGER_DEBUG);

			// If the contact isn't searchable then set the contact to "hidden".
			// Problem: This can be manually overridden by the user.
			if ($hide)
				$contact["hidden"] = true;

			// Save the keywords into the contact table
			$tags = array();
			$tagelements = $xpath->evaluate($element."/poco:tags/text()", $context);
			foreach($tagelements AS $tag)
				$tags[$tag->nodeValue] = $tag->nodeValue;

			if (count($tags))
				$poco["keywords"] = implode(", ", $tags);

			// "dfrn:birthday" contains the birthday converted to UTC
			$old_bdyear = $contact["bdyear"];

			$birthday = $xpath->evaluate($element."/dfrn:birthday/text()", $context)->item(0)->nodeValue;

			if (strtotime($birthday) > time()) {
				$bd_timestamp = strtotime($birthday);

				$poco["bdyear"] = date("Y", $bd_timestamp);
			}

			// "poco:birthday" is the birthday in the format "yyyy-mm-dd"
			$value = $xpath->evaluate($element."/poco:birthday/text()", $context)->item(0)->nodeValue;

			if (!in_array($value, array("", "0000-00-00"))) {
				$bdyear = date("Y");
				$value = str_replace("0000", $bdyear, $value);

				if (strtotime($value) < time()) {
					$value = str_replace($bdyear, $bdyear + 1, $value);
					$bdyear = $bdyear + 1;
				}

				$poco["bd"] = $value;
			}

			$contact = array_merge($contact, $poco);

			if ($old_bdyear != $contact["bdyear"])
				self::birthday_event($contact, $birthday);

			// Get all field names
			$fields = array();
			foreach ($r[0] AS $field => $data)
				$fields[$field] = $data;

			unset($fields["id"]);
			unset($fields["uid"]);
			unset($fields["url"]);
			unset($fields["avatar-date"]);
			unset($fields["name-date"]);
			unset($fields["uri-date"]);

			// Update check for this field has to be done differently
			$datefields = array("name-date", "uri-date");
			foreach ($datefields AS $field)
				if (strtotime($contact[$field]) > strtotime($r[0][$field])) {
					logger("Difference for contact ".$contact["id"]." in field '".$field."'. New value: '".$contact[$field]."', old value '".$r[0][$field]."'", LOGGER_DEBUG);
					$update = true;
				}

			foreach ($fields AS $field => $data)
				if ($contact[$field] != $r[0][$field]) {
					logger("Difference for contact ".$contact["id"]." in field '".$field."'. New value: '".$contact[$field]."', old value '".$r[0][$field]."'", LOGGER_DEBUG);
					$update = true;
				}

			if ($update) {
				logger("Update contact data for contact ".$contact["id"]." (".$contact["nick"].")", LOGGER_DEBUG);

				q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `about` = '%s', `location` = '%s',
					`addr` = '%s', `keywords` = '%s', `bdyear` = '%s', `bd` = '%s', `hidden` = %d,
					`xmpp` = '%s', `name-date`  = '%s', `uri-date` = '%s'
					WHERE `id` = %d AND `network` = '%s'",
					dbesc($contact["name"]), dbesc($contact["nick"]), dbesc($contact["about"]), dbesc($contact["location"]),
					dbesc($contact["addr"]), dbesc($contact["keywords"]), dbesc($contact["bdyear"]),
					dbesc($contact["bd"]), intval($contact["hidden"]), dbesc($contact["xmpp"]),
					dbesc($contact["name-date"]), dbesc($contact["uri-date"]),
					intval($contact["id"]), dbesc($contact["network"]));
			}

			update_contact_avatar($author["avatar"], $importer["uid"], $contact["id"],
						(strtotime($contact["avatar-date"]) > strtotime($r[0]["avatar-date"])));

			// The generation is a sign for the reliability of the provided data.
			// It is used in the socgraph.php to prevent that old contact data
			// that was relayed over several servers can overwrite contact
			// data that we received directly.

			$poco["generation"] = 2;
			$poco["photo"] = $author["avatar"];
			$poco["hide"] = $hide;
			$poco["contact-type"] = $contact["contact-type"];
			update_gcontact($poco);
		}

		return($author);
	}

	/**
	 * @brief Transforms activity objects into an XML string
	 *
	 * @param object $xpath XPath object
	 * @param object $activity Activity object
	 * @param text $element element name
	 *
	 * @return string XML string
	 */
	private function transform_activity($xpath, $activity, $element) {
		if (!is_object($activity))
			return "";

		$obj_doc = new DOMDocument("1.0", "utf-8");
		$obj_doc->formatOutput = true;

		$obj_element = $obj_doc->createElementNS(NAMESPACE_ATOM1, $element);

		$activity_type = $xpath->query("activity:object-type/text()", $activity)->item(0)->nodeValue;
		xml::add_element($obj_doc, $obj_element, "type", $activity_type);

		$id = $xpath->query("atom:id", $activity)->item(0);
		if (is_object($id))
			$obj_element->appendChild($obj_doc->importNode($id, true));

		$title = $xpath->query("atom:title", $activity)->item(0);
		if (is_object($title))
			$obj_element->appendChild($obj_doc->importNode($title, true));

		$links = $xpath->query("atom:link", $activity);
		if (is_object($links))
			foreach ($links AS $link)
				$obj_element->appendChild($obj_doc->importNode($link, true));

		$content = $xpath->query("atom:content", $activity)->item(0);
		if (is_object($content))
			$obj_element->appendChild($obj_doc->importNode($content, true));

		$obj_doc->appendChild($obj_element);

		$objxml = $obj_doc->saveXML($obj_element);

		/// @todo This isn't totally clean. We should find a way to transform the namespaces
		$objxml = str_replace("<".$element.' xmlns="http://www.w3.org/2005/Atom">', "<".$element.">", $objxml);
		return($objxml);
	}

	/**
	 * @brief Processes the mail elements
	 *
	 * @param object $xpath XPath object
	 * @param object $mail mail elements
	 * @param array $importer Record of the importer user mixed with contact of the content
	 */
	private function process_mail($xpath, $mail, $importer) {

		logger("Processing mails");

		$msg = array();
		$msg["uid"] = $importer["importer_uid"];
		$msg["from-name"] = $xpath->query("dfrn:sender/dfrn:name/text()", $mail)->item(0)->nodeValue;
		$msg["from-url"] = $xpath->query("dfrn:sender/dfrn:uri/text()", $mail)->item(0)->nodeValue;
		$msg["from-photo"] = $xpath->query("dfrn:sender/dfrn:avatar/text()", $mail)->item(0)->nodeValue;
		$msg["contact-id"] = $importer["id"];
		$msg["uri"] = $xpath->query("dfrn:id/text()", $mail)->item(0)->nodeValue;
		$msg["parent-uri"] = $xpath->query("dfrn:in-reply-to/text()", $mail)->item(0)->nodeValue;
		$msg["created"] = $xpath->query("dfrn:sentdate/text()", $mail)->item(0)->nodeValue;
		$msg["title"] = $xpath->query("dfrn:subject/text()", $mail)->item(0)->nodeValue;
		$msg["body"] = $xpath->query("dfrn:content/text()", $mail)->item(0)->nodeValue;
		$msg["seen"] = 0;
		$msg["replied"] = 0;

		dbesc_array($msg);

		$r = dbq("INSERT INTO `mail` (`".implode("`, `", array_keys($msg))."`) VALUES ('".implode("', '", array_values($msg))."')");

		// send notifications.

		$notif_params = array(
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
		);

		notification($notif_params);

		logger("Mail is processed, notification was sent.");
	}

	/**
	 * @brief Processes the suggestion elements
	 *
	 * @param object $xpath XPath object
	 * @param object $suggestion suggestion elements
	 * @param array $importer Record of the importer user mixed with contact of the content
	 */
	private function process_suggestion($xpath, $suggestion, $importer) {
		$a = get_app();

		logger("Processing suggestions");

		$suggest = array();
		$suggest["uid"] = $importer["importer_uid"];
		$suggest["cid"] = $importer["id"];
		$suggest["url"] = $xpath->query("dfrn:url/text()", $suggestion)->item(0)->nodeValue;
		$suggest["name"] = $xpath->query("dfrn:name/text()", $suggestion)->item(0)->nodeValue;
		$suggest["photo"] = $xpath->query("dfrn:photo/text()", $suggestion)->item(0)->nodeValue;
		$suggest["request"] = $xpath->query("dfrn:request/text()", $suggestion)->item(0)->nodeValue;
		$suggest["body"] = $xpath->query("dfrn:note/text()", $suggestion)->item(0)->nodeValue;

		// Does our member already have a friend matching this description?

		$r = q("SELECT `id` FROM `contact` WHERE `name` = '%s' AND `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($suggest["name"]),
			dbesc(normalise_link($suggest["url"])),
			intval($suggest["uid"])
		);
		if (dbm::is_result($r))
			return false;

		// Do we already have an fcontact record for this person?

		$fid = 0;
		$r = q("SELECT `id` FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($suggest["url"]),
			dbesc($suggest["name"]),
			dbesc($suggest["request"])
		);
		if (dbm::is_result($r)) {
			$fid = $r[0]["id"];

			// OK, we do. Do we already have an introduction for this person ?
			$r = q("SELECT `id` FROM `intro` WHERE `uid` = %d AND `fid` = %d LIMIT 1",
				intval($suggest["uid"]),
				intval($fid)
			);
			if (dbm::is_result($r))
				return false;
		}
		if(!$fid)
			$r = q("INSERT INTO `fcontact` (`name`,`url`,`photo`,`request`) VALUES ('%s', '%s', '%s', '%s')",
			dbesc($suggest["name"]),
			dbesc($suggest["url"]),
			dbesc($suggest["photo"]),
			dbesc($suggest["request"])
		);
		$r = q("SELECT `id` FROM `fcontact` WHERE `url` = '%s' AND `name` = '%s' AND `request` = '%s' LIMIT 1",
			dbesc($suggest["url"]),
			dbesc($suggest["name"]),
			dbesc($suggest["request"])
		);
		if (dbm::is_result($r))
			$fid = $r[0]["id"];
		else
			// database record did not get created. Quietly give up.
			return false;


		$hash = random_string();

		$r = q("INSERT INTO `intro` (`uid`, `fid`, `contact-id`, `note`, `hash`, `datetime`, `blocked`)
			VALUES(%d, %d, %d, '%s', '%s', '%s', %d)",
			intval($suggest["uid"]),
			intval($fid),
			intval($suggest["cid"]),
			dbesc($suggest["body"]),
			dbesc($hash),
			dbesc(datetime_convert()),
			intval(0)
		);

		notification(array(
			"type"         => NOTIFY_SUGGEST,
			"notify_flags" => $importer["notify-flags"],
			"language"     => $importer["language"],
			"to_name"      => $importer["username"],
			"to_email"     => $importer["email"],
			"uid"          => $importer["importer_uid"],
			"item"         => $suggest,
			"link"         => App::get_baseurl()."/notifications/intros",
			"source_name"  => $importer["name"],
			"source_link"  => $importer["url"],
			"source_photo" => $importer["photo"],
			"verb"         => ACTIVITY_REQ_FRIEND,
			"otype"        => "intro"
		));

		return true;

	}

	/**
	 * @brief Processes the relocation elements
	 *
	 * @param object $xpath XPath object
	 * @param object $relocation relocation elements
	 * @param array $importer Record of the importer user mixed with contact of the content
	 */
	private function process_relocation($xpath, $relocation, $importer) {

		logger("Processing relocations");

		$relocate = array();
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

		if (($relocate["avatar"] == "") AND ($relocate["photo"] != ""))
			$relocate["avatar"] = $relocate["photo"];

		if ($relocate["addr"] == "")
			$relocate["addr"] = preg_replace("=(https?://)(.*)/profile/(.*)=ism", "$3@$2", $relocate["url"]);

		// update contact
		$r = q("SELECT `photo`, `url` FROM `contact` WHERE `id` = %d AND `uid` = %d;",
			intval($importer["id"]),
			intval($importer["importer_uid"]));
		if (!$r)
			return false;

		$old = $r[0];

		// Update the gcontact entry
		$relocate["server_url"] = preg_replace("=(https?://)(.*)/profile/(.*)=ism", "$1$2", $relocate["url"]);

		$x = q("UPDATE `gcontact` SET
					`name` = '%s',
					`photo` = '%s',
					`url` = '%s',
					`nurl` = '%s',
					`addr` = '%s',
					`connect` = '%s',
					`notify` = '%s',
					`server_url` = '%s'
			WHERE `nurl` = '%s';",
					dbesc($relocate["name"]),
					dbesc($relocate["avatar"]),
					dbesc($relocate["url"]),
					dbesc(normalise_link($relocate["url"])),
					dbesc($relocate["addr"]),
					dbesc($relocate["addr"]),
					dbesc($relocate["notify"]),
					dbesc($relocate["server_url"]),
					dbesc(normalise_link($old["url"])));

		// Update the contact table. We try to find every entry.
		$x = q("UPDATE `contact` SET
					`name` = '%s',
					`avatar` = '%s',
					`url` = '%s',
					`nurl` = '%s',
					`addr` = '%s',
					`request` = '%s',
					`confirm` = '%s',
					`notify` = '%s',
					`poll` = '%s',
					`site-pubkey` = '%s'
			WHERE (`id` = %d AND `uid` = %d) OR (`nurl` = '%s');",
					dbesc($relocate["name"]),
					dbesc($relocate["avatar"]),
					dbesc($relocate["url"]),
					dbesc(normalise_link($relocate["url"])),
					dbesc($relocate["addr"]),
					dbesc($relocate["request"]),
					dbesc($relocate["confirm"]),
					dbesc($relocate["notify"]),
					dbesc($relocate["poll"]),
					dbesc($relocate["sitepubkey"]),
					intval($importer["id"]),
					intval($importer["importer_uid"]),
					dbesc(normalise_link($old["url"])));

		update_contact_avatar($relocate["avatar"], $importer["importer_uid"], $importer["id"], true);

		if ($x === false)
			return false;

		// update items
		/// @todo This is an extreme performance killer
		$fields = array(
			'owner-link' => array($old["url"], $relocate["url"]),
			'author-link' => array($old["url"], $relocate["url"]),
			//'owner-avatar' => array($old["photo"], $relocate["photo"]),
			//'author-avatar' => array($old["photo"], $relocate["photo"]),
			);
		foreach ($fields as $n=>$f) {
			$r = q("SELECT `id` FROM `item` WHERE `%s` = '%s' AND `uid` = %d LIMIT 1",
					$n, dbesc($f[0]),
					intval($importer["importer_uid"]));

			if ($r) {
				$x = q("UPDATE `item` SET `%s` = '%s' WHERE `%s` = '%s' AND `uid` = %d",
						$n, dbesc($f[1]),
						$n, dbesc($f[0]),
						intval($importer["importer_uid"]));
					if ($x === false)
						return false;
			}
		}

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
	 * @param array $current the current item record
	 * @param array $item the new item record
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param int $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 */
	private function update_content($current, $item, $importer, $entrytype) {
		$changed = false;

		if (edited_timestamp_is_newer($current, $item)) {

			// do not accept (ignore) an earlier edit than one we currently have.
			if(datetime_convert("UTC","UTC",$item["edited"]) < $current["edited"])
				return(false);

			$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `edited` = '%s', `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
				dbesc($item["title"]),
				dbesc($item["body"]),
				dbesc($item["tag"]),
				dbesc(datetime_convert("UTC","UTC",$item["edited"])),
				dbesc(datetime_convert()),
				dbesc($item["uri"]),
				intval($importer["importer_uid"])
			);
			create_tags_from_itemuri($item["uri"], $importer["importer_uid"]);
			update_thread_uri($item["uri"], $importer["importer_uid"]);

			$changed = true;

			if ($entrytype == DFRN_REPLY_RC)
				proc_run(PRIORITY_HIGH, "include/notifier.php","comment-import", $current["id"]);
		}

		// update last-child if it changes
		if($item["last-child"] AND ($item["last-child"] != $current["last-child"])) {
			$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
				dbesc(datetime_convert()),
				dbesc($item["parent-uri"]),
				intval($importer["importer_uid"])
			);
			$r = q("UPDATE `item` SET `last-child` = %d , `changed` = '%s' WHERE `uri` = '%s' AND `uid` = %d",
				intval($item["last-child"]),
				dbesc(datetime_convert()),
				dbesc($item["uri"]),
				intval($importer["importer_uid"])
			);
		}
		return $changed;
	}

	/**
	 * @brief Detects the entry type of the item
	 *
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param array $item the new item record
	 *
	 * @return int Is it a toplevel entry, a comment or a relayed comment?
	 */
	private function get_entry_type($importer, $item) {
		if ($item["parent-uri"] != $item["uri"]) {
			$community = false;

			if($importer["page-flags"] == PAGE_COMMUNITY || $importer["page-flags"] == PAGE_PRVGROUP) {
				$sql_extra = "";
				$community = true;
				logger("possible community action");
			} else
				$sql_extra = " AND `contact`.`self` AND `item`.`wall` ";

			// was the top-level post for this action written by somebody on this site?
			// Specifically, the recipient?

			$is_a_remote_action = false;

			$r = q("SELECT `item`.`parent-uri` FROM `item`
				WHERE `item`.`uri` = '%s'
				LIMIT 1",
				dbesc($item["parent-uri"])
			);
			if (dbm::is_result($r)) {
				$r = q("SELECT `item`.`forum_mode`, `item`.`wall` FROM `item`
					INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
					WHERE `item`.`uri` = '%s' AND (`item`.`parent-uri` = '%s' OR `item`.`thr-parent` = '%s')
					AND `item`.`uid` = %d
					$sql_extra
					LIMIT 1",
					dbesc($r[0]["parent-uri"]),
					dbesc($r[0]["parent-uri"]),
					dbesc($r[0]["parent-uri"]),
					intval($importer["importer_uid"])
				);
				if (dbm::is_result($r))
					$is_a_remote_action = true;
			}

			// Does this have the characteristics of a community or private group action?
			// If it's an action to a wall post on a community/prvgroup page it's a
			// valid community action. Also forum_mode makes it valid for sure.
			// If neither, it's not.

			if($is_a_remote_action && $community) {
				if((!$r[0]["forum_mode"]) && (!$r[0]["wall"])) {
					$is_a_remote_action = false;
					logger("not a community action");
				}
			}

			if ($is_a_remote_action)
				return DFRN_REPLY_RC;
			else
				return DFRN_REPLY;

		} else
			return DFRN_TOP_LEVEL;

	}

	/**
	 * @brief Send a "poke"
	 *
	 * @param array $item the new item record
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param int $posted_id The record number of item record that was just posted
	 */
	private function do_poke($item, $importer, $posted_id) {
		$verb = urldecode(substr($item["verb"],strpos($item["verb"], "#")+1));
		if(!$verb)
			return;
		$xo = parse_xml_string($item["object"],false);

		if(($xo->type == ACTIVITY_OBJ_PERSON) && ($xo->id)) {

			// somebody was poked/prodded. Was it me?
			foreach($xo->link as $l) {
				$atts = $l->attributes();
				switch($atts["rel"]) {
					case "alternate":
						$Blink = $atts["href"];
						break;
					default:
						break;
				}
			}

			if($Blink && link_compare($Blink,App::get_baseurl()."/profile/".$importer["nickname"])) {

				// send a notification
				notification(array(
					"type"         => NOTIFY_POKE,
					"notify_flags" => $importer["notify-flags"],
					"language"     => $importer["language"],
					"to_name"      => $importer["username"],
					"to_email"     => $importer["email"],
					"uid"          => $importer["importer_uid"],
					"item"         => $item,
					"link"         => App::get_baseurl()."/display/".urlencode(get_item_guid($posted_id)),
					"source_name"  => stripslashes($item["author-name"]),
					"source_link"  => $item["author-link"],
					"source_photo" => ((link_compare($item["author-link"],$importer["url"]))
						? $importer["thumb"] : $item["author-avatar"]),
					"verb"         => $item["verb"],
					"otype"        => "person",
					"activity"     => $verb,
					"parent"       => $item["parent"]
				));
			}
		}
	}

	/**
	 * @brief Processes several actions, depending on the verb
	 *
	 * @param int $entrytype Is it a toplevel entry, a comment or a relayed comment?
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param array $item the new item record
	 * @param bool $is_like Is the verb a "like"?
	 *
	 * @return bool Should the processing of the entries be continued?
	 */
	private function process_verbs($entrytype, $importer, &$item, &$is_like) {

		logger("Process verb ".$item["verb"]." and object-type ".$item["object-type"]." for entrytype ".$entrytype, LOGGER_DEBUG);

		if (($entrytype == DFRN_TOP_LEVEL)) {
			// The filling of the the "contact" variable is done for legcy reasons
			// The functions below are partly used by ostatus.php as well - where we have this variable
			$r = q("SELECT * FROM `contact` WHERE `id` = %d", intval($importer["id"]));
			$contact = $r[0];
			$nickname = $contact["nick"];

			// Big question: Do we need these functions? They were part of the "consume_feed" function.
			// This function once was responsible for DFRN and OStatus.
			if(activity_match($item["verb"],ACTIVITY_FOLLOW)) {
				logger("New follower");
				new_follower($importer, $contact, $item, $nickname);
				return false;
			}
			if(activity_match($item["verb"],ACTIVITY_UNFOLLOW))  {
				logger("Lost follower");
				lose_follower($importer, $contact, $item);
				return false;
			}
			if(activity_match($item["verb"],ACTIVITY_REQ_FRIEND)) {
				logger("New friend request");
				new_follower($importer, $contact, $item, $nickname, true);
				return false;
			}
			if(activity_match($item["verb"],ACTIVITY_UNFRIEND))  {
				logger("Lost sharer");
				lose_sharer($importer, $contact, $item);
				return false;
			}
		} else {
			if(($item["verb"] == ACTIVITY_LIKE)
				|| ($item["verb"] == ACTIVITY_DISLIKE)
				|| ($item["verb"] == ACTIVITY_ATTEND)
				|| ($item["verb"] == ACTIVITY_ATTENDNO)
				|| ($item["verb"] == ACTIVITY_ATTENDMAYBE)) {
				$is_like = true;
				$item["type"] = "activity";
				$item["gravity"] = GRAVITY_LIKE;
				// only one like or dislike per person
				// splitted into two queries for performance issues
				$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `parent-uri` = '%s' AND NOT `deleted` LIMIT 1",
					intval($item["uid"]),
					dbesc($item["author-link"]),
					dbesc($item["verb"]),
					dbesc($item["parent-uri"])
				);
				if (dbm::is_result($r))
					return false;

				$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `author-link` = '%s' AND `verb` = '%s' AND `thr-parent` = '%s' AND NOT `deleted` LIMIT 1",
					intval($item["uid"]),
					dbesc($item["author-link"]),
					dbesc($item["verb"]),
					dbesc($item["parent-uri"])
				);
				if (dbm::is_result($r))
					return false;
			} else
				$is_like = false;

			if(($item["verb"] == ACTIVITY_TAG) && ($item["object-type"] == ACTIVITY_OBJ_TAGTERM)) {

				$xo = parse_xml_string($item["object"],false);
				$xt = parse_xml_string($item["target"],false);

				if($xt->type == ACTIVITY_OBJ_NOTE) {
					$r = q("SELECT `id`, `tag` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($xt->id),
						intval($importer["importer_uid"])
					);

					if (!dbm::is_result($r))
						return false;

					// extract tag, if not duplicate, add to parent item
					if($xo->content) {
						if(!(stristr($r[0]["tag"],trim($xo->content)))) {
							q("UPDATE `item` SET `tag` = '%s' WHERE `id` = %d",
								dbesc($r[0]["tag"] . (strlen($r[0]["tag"]) ? ',' : '') . '#[url=' . $xo->id . ']'. $xo->content . '[/url]'),
								intval($r[0]["id"])
							);
							create_tags_from_item($r[0]["id"]);
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
	 * @param array $item the item record
	 */
	private function parse_links($links, &$item) {
		$rel = "";
		$href = "";
		$type = "";
		$length = "0";
		$title = "";
		foreach ($links AS $link) {
			foreach($link->attributes AS $attributes) {
				if ($attributes->name == "href")
					$href = $attributes->textContent;
				if ($attributes->name == "rel")
					$rel = $attributes->textContent;
				if ($attributes->name == "type")
					$type = $attributes->textContent;
				if ($attributes->name == "length")
					$length = $attributes->textContent;
				if ($attributes->name == "title")
					$title = $attributes->textContent;
			}
			if (($rel != "") AND ($href != ""))
				switch($rel) {
					case "alternate":
						$item["plink"] = $href;
						break;
					case "enclosure":
						$enclosure = $href;
						if(strlen($item["attach"]))
							$item["attach"] .= ",";

						$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'" title="'.$title.'"[/attach]';
						break;
				}
		}
	}

	/**
	 * @brief Processes the entry elements which contain the items and comments
	 *
	 * @param array $header Array of the header elements that always stay the same
	 * @param object $xpath XPath object
	 * @param object $entry entry elements
	 * @param array $importer Record of the importer user mixed with contact of the content
	 */
	private function process_entry($header, $xpath, $entry, $importer) {

		logger("Processing entries");

		$item = $header;

		// Get the uri
		$item["uri"] = $xpath->query("atom:id/text()", $entry)->item(0)->nodeValue;

		// Fetch the owner
		$owner = self::fetchauthor($xpath, $entry, $importer, "dfrn:owner", true);

		$item["owner-name"] = $owner["name"];
		$item["owner-link"] = $owner["link"];
		$item["owner-avatar"] = $owner["avatar"];

		// fetch the author
		$author = self::fetchauthor($xpath, $entry, $importer, "atom:author", true);

		$item["author-name"] = $author["name"];
		$item["author-link"] = $author["link"];
		$item["author-avatar"] = $author["avatar"];

		$item["title"] = $xpath->query("atom:title/text()", $entry)->item(0)->nodeValue;

		$item["created"] = $xpath->query("atom:published/text()", $entry)->item(0)->nodeValue;
		$item["edited"] = $xpath->query("atom:updated/text()", $entry)->item(0)->nodeValue;

		$item["body"] = $xpath->query("dfrn:env/text()", $entry)->item(0)->nodeValue;
		$item["body"] = str_replace(array(' ',"\t","\r","\n"), array('','','',''),$item["body"]);
		// make sure nobody is trying to sneak some html tags by us
		$item["body"] = notags(base64url_decode($item["body"]));

		$item["body"] = limit_body_size($item["body"]);

		/// @todo Do we really need this check for HTML elements? (It was copied from the old function)
		if((strpos($item['body'],'<') !== false) && (strpos($item['body'],'>') !== false)) {

			$item['body'] = reltoabs($item['body'],$base_url);

			$item['body'] = html2bb_video($item['body']);

			$item['body'] = oembed_html2bbcode($item['body']);

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);

			// we shouldn't need a whitelist, because the bbcode converter
			// will strip out any unsupported tags.

			$purifier = new HTMLPurifier($config);
			$item['body'] = $purifier->purify($item['body']);

			$item['body'] = @html2bbcode($item['body']);
		}

		/// @todo We should check for a repeated post and if we know the repeated author.

		// We don't need the content element since "dfrn:env" is always present
		//$item["body"] = $xpath->query("atom:content/text()", $entry)->item(0)->nodeValue;

		$item["last-child"] = $xpath->query("dfrn:comment-allow/text()", $entry)->item(0)->nodeValue;
		$item["location"] = $xpath->query("dfrn:location/text()", $entry)->item(0)->nodeValue;

		$georsspoint = $xpath->query("georss:point", $entry);
		if ($georsspoint)
			$item["coord"] = $georsspoint->item(0)->nodeValue;

		$item["private"] = $xpath->query("dfrn:private/text()", $entry)->item(0)->nodeValue;

		$item["extid"] = $xpath->query("dfrn:extid/text()", $entry)->item(0)->nodeValue;

		if ($xpath->query("dfrn:bookmark/text()", $entry)->item(0)->nodeValue == "true")
			$item["bookmark"] = true;

		$notice_info = $xpath->query("statusnet:notice_info", $entry);
		if ($notice_info AND ($notice_info->length > 0)) {
			foreach($notice_info->item(0)->attributes AS $attributes) {
				if ($attributes->name == "source")
					$item["app"] = strip_tags($attributes->textContent);
			}
		}

		$item["guid"] = $xpath->query("dfrn:diaspora_guid/text()", $entry)->item(0)->nodeValue;

		// We store the data from "dfrn:diaspora_signature" in a different table, this is done in "item_store"
		$dsprsig = unxmlify($xpath->query("dfrn:diaspora_signature/text()", $entry)->item(0)->nodeValue);
		if ($dsprsig != "")
			$item["dsprsig"] = $dsprsig;

		$item["verb"] = $xpath->query("activity:verb/text()", $entry)->item(0)->nodeValue;

		if ($xpath->query("activity:object-type/text()", $entry)->item(0)->nodeValue != "")
			$item["object-type"] = $xpath->query("activity:object-type/text()", $entry)->item(0)->nodeValue;

		$object = $xpath->query("activity:object", $entry)->item(0);
		$item["object"] = self::transform_activity($xpath, $object, "object");

		if (trim($item["object"]) != "") {
			$r = parse_xml_string($item["object"], false);
			if (isset($r->type))
				$item["object-type"] = $r->type;
		}

		$target = $xpath->query("activity:target", $entry)->item(0);
		$item["target"] = self::transform_activity($xpath, $target, "target");

		$categories = $xpath->query("atom:category", $entry);
		if ($categories) {
			foreach ($categories AS $category) {
				$term = "";
				$scheme = "";
				foreach($category->attributes AS $attributes) {
					if ($attributes->name == "term")
						$term = $attributes->textContent;

					if ($attributes->name == "scheme")
						$scheme = $attributes->textContent;
				}

				if (($term != "") AND ($scheme != "")) {
					$parts = explode(":", $scheme);
					if ((count($parts) >= 4) AND (array_shift($parts) == "X-DFRN")) {
						$termhash = array_shift($parts);
						$termurl = implode(":", $parts);

						if(strlen($item["tag"]))
							$item["tag"] .= ",";

						$item["tag"] .= $termhash."[url=".$termurl."]".$term."[/url]";
					}
				}
			}
		}

		$enclosure = "";

		$links = $xpath->query("atom:link", $entry);
		if ($links)
			self::parse_links($links, $item);

		// Is it a reply or a top level posting?
		$item["parent-uri"] = $item["uri"];

		$inreplyto = $xpath->query("thr:in-reply-to", $entry);
		if (is_object($inreplyto->item(0)))
			foreach($inreplyto->item(0)->attributes AS $attributes)
				if ($attributes->name == "ref")
					$item["parent-uri"] = $attributes->textContent;

		// Get the type of the item (Top level post, reply or remote reply)
		$entrytype = self::get_entry_type($importer, $item);

		// Now assign the rest of the values that depend on the type of the message
		if (in_array($entrytype, array(DFRN_REPLY, DFRN_REPLY_RC))) {
			if (!isset($item["object-type"]))
				$item["object-type"] = ACTIVITY_OBJ_COMMENT;

			if ($item["contact-id"] != $owner["contact-id"])
				$item["contact-id"] = $owner["contact-id"];

			if (($item["network"] != $owner["network"]) AND ($owner["network"] != ""))
				$item["network"] = $owner["network"];

			if ($item["contact-id"] != $author["contact-id"])
				$item["contact-id"] = $author["contact-id"];

			if (($item["network"] != $author["network"]) AND ($author["network"] != ""))
				$item["network"] = $author["network"];

			// This code was taken from the old DFRN code
			// When activated, forums don't work.
			// And: Why should we disallow commenting by followers?
			// the behaviour is now similar to the Diaspora part.
			//if($importer["rel"] == CONTACT_IS_FOLLOWER) {
			//	logger("Contact ".$importer["id"]." is only follower. Quitting", LOGGER_DEBUG);
			//	return;
			//}
		}

		if ($entrytype == DFRN_REPLY_RC) {
			$item["type"] = "remote-comment";
			$item["wall"] = 1;
		} elseif ($entrytype == DFRN_TOP_LEVEL) {
			if (!isset($item["object-type"]))
				$item["object-type"] = ACTIVITY_OBJ_NOTE;

			// Is it an event?
			if ($item["object-type"] == ACTIVITY_OBJ_EVENT) {
				logger("Item ".$item["uri"]." seems to contain an event.", LOGGER_DEBUG);
				$ev = bbtoevent($item["body"]);
				if((x($ev, "desc") || x($ev, "summary")) && x($ev, "start")) {
					logger("Event in item ".$item["uri"]." was found.", LOGGER_DEBUG);
					$ev["cid"] = $importer["id"];
					$ev["uid"] = $importer["uid"];
					$ev["uri"] = $item["uri"];
					$ev["edited"] = $item["edited"];
					$ev['private'] = $item['private'];
					$ev["guid"] = $item["guid"];

					$r = q("SELECT `id` FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($item["uri"]),
						intval($importer["uid"])
					);
					if (dbm::is_result($r))
						$ev["id"] = $r[0]["id"];

					$event_id = event_store($ev);
					logger("Event ".$event_id." was stored", LOGGER_DEBUG);
					return;
				}
			}
		}

		$r = q("SELECT `id`, `uid`, `last-child`, `edited`, `body` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($item["uri"]),
			intval($importer["importer_uid"])
		);

		if (!self::process_verbs($entrytype, $importer, $item, $is_like)) {
			logger("Exiting because 'process_verbs' told us so", LOGGER_DEBUG);
			return;
		}

		// Update content if 'updated' changes
		if (dbm::is_result($r)) {
			if (self::update_content($r[0], $item, $importer, $entrytype))
				logger("Item ".$item["uri"]." was updated.", LOGGER_DEBUG);
			else
				logger("Item ".$item["uri"]." already existed.", LOGGER_DEBUG);
			return;
		}

		if (in_array($entrytype, array(DFRN_REPLY, DFRN_REPLY_RC))) {
			$posted_id = item_store($item);
			$parent = 0;

			if($posted_id) {

				logger("Reply from contact ".$item["contact-id"]." was stored with id ".$posted_id, LOGGER_DEBUG);

				$item["id"] = $posted_id;

				$r = q("SELECT `parent`, `parent-uri` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
					intval($posted_id),
					intval($importer["importer_uid"])
				);
				if (dbm::is_result($r)) {
					$parent = $r[0]["parent"];
					$parent_uri = $r[0]["parent-uri"];
				}

				if(!$is_like) {
					$r1 = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `uid` = %d AND `parent` = %d",
						dbesc(datetime_convert()),
						intval($importer["importer_uid"]),
						intval($r[0]["parent"])
					);

					$r2 = q("UPDATE `item` SET `last-child` = 1, `changed` = '%s' WHERE `uid` = %d AND `id` = %d",
						dbesc(datetime_convert()),
						intval($importer["importer_uid"]),
						intval($posted_id)
					);
				}

				if($posted_id AND $parent AND ($entrytype == DFRN_REPLY_RC)) {
					logger("Notifying followers about comment ".$posted_id, LOGGER_DEBUG);
					proc_run(PRIORITY_HIGH, "include/notifier.php", "comment-import", $posted_id);
				}

				return true;
			}
		} else { // $entrytype == DFRN_TOP_LEVEL
			if(!link_compare($item["owner-link"],$importer["url"])) {
				// The item owner info is not our contact. It's OK and is to be expected if this is a tgroup delivery,
				// but otherwise there's a possible data mixup on the sender's system.
				// the tgroup delivery code called from item_store will correct it if it's a forum,
				// but we're going to unconditionally correct it here so that the post will always be owned by our contact.
				logger('Correcting item owner.', LOGGER_DEBUG);
				$item["owner-name"]   = $importer["senderName"];
				$item["owner-link"]   = $importer["url"];
				$item["owner-avatar"] = $importer["thumb"];
			}

			if(($importer["rel"] == CONTACT_IS_FOLLOWER) && (!tgroup_check($importer["importer_uid"], $item))) {
				logger("Contact ".$importer["id"]." is only follower and tgroup check was negative.", LOGGER_DEBUG);
				return;
			}

			// This is my contact on another system, but it's really me.
			// Turn this into a wall post.
			$notify = item_is_remote_self($importer, $item);

			$posted_id = item_store($item, false, $notify);

			logger("Item was stored with id ".$posted_id, LOGGER_DEBUG);

			if(stristr($item["verb"],ACTIVITY_POKE))
				self::do_poke($item, $importer, $posted_id);
		}
	}

	/**
	 * @brief Deletes items
	 *
	 * @param object $xpath XPath object
	 * @param object $deletion deletion elements
	 * @param array $importer Record of the importer user mixed with contact of the content
	 */
	private function process_deletion($xpath, $deletion, $importer) {

		logger("Processing deletions");

		foreach($deletion->attributes AS $attributes) {
			if ($attributes->name == "ref")
				$uri = $attributes->textContent;
			if ($attributes->name == "when")
				$when = $attributes->textContent;
		}
		if ($when)
			$when = datetime_convert("UTC", "UTC", $when, "Y-m-d H:i:s");
		else
			$when = datetime_convert("UTC", "UTC", "now", "Y-m-d H:i:s");

		if (!$uri OR !$importer["id"])
			return false;

		/// @todo Only select the used fields
		$r = q("SELECT `item`.*, `contact`.`self` FROM `item` INNER JOIN `contact` on `item`.`contact-id` = `contact`.`id`
				WHERE `uri` = '%s' AND `item`.`uid` = %d AND `contact-id` = %d AND NOT `item`.`file` LIKE '%%[%%' LIMIT 1",
				dbesc($uri),
				intval($importer["uid"]),
				intval($importer["id"])
			);
		if (!dbm::is_result($r)) {
			logger("Item with uri ".$uri." from contact ".$importer["id"]." for user ".$importer["uid"]." wasn't found.", LOGGER_DEBUG);
			return;
		} else {

			$item = $r[0];

			$entrytype = self::get_entry_type($importer, $item);

			if(!$item["deleted"])
				logger('deleting item '.$item["id"].' uri='.$uri, LOGGER_DEBUG);
			else
				return;

			if($item["object-type"] == ACTIVITY_OBJ_EVENT) {
				logger("Deleting event ".$item["event-id"], LOGGER_DEBUG);
				event_delete($item["event-id"]);
			}

			if(($item["verb"] == ACTIVITY_TAG) && ($item["object-type"] == ACTIVITY_OBJ_TAGTERM)) {

				$xo = parse_xml_string($item["object"],false);
				$xt = parse_xml_string($item["target"],false);

				if($xt->type == ACTIVITY_OBJ_NOTE) {
					$i = q("SELECT `id`, `contact-id`, `tag` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($xt->id),
						intval($importer["importer_uid"])
					);
					if (dbm::is_result($i)) {

						// For tags, the owner cannot remove the tag on the author's copy of the post.

						$owner_remove = (($item["contact-id"] == $i[0]["contact-id"]) ? true: false);
						$author_remove = (($item["origin"] && $item["self"]) ? true : false);
						$author_copy = (($item["origin"]) ? true : false);

						if($owner_remove && $author_copy)
							return;
						if($author_remove || $owner_remove) {
							$tags = explode(',',$i[0]["tag"]);
							$newtags = array();
							if(count($tags)) {
								foreach($tags as $tag)
									if(trim($tag) !== trim($xo->body))
										$newtags[] = trim($tag);
							}
							q("UPDATE `item` SET `tag` = '%s' WHERE `id` = %d",
								dbesc(implode(',',$newtags)),
								intval($i[0]["id"])
							);
							create_tags_from_item($i[0]["id"]);
						}
					}
				}
			}

			if($entrytype == DFRN_TOP_LEVEL) {
				$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
						`body` = '', `title` = ''
					WHERE `parent-uri` = '%s' AND `uid` = %d",
						dbesc($when),
						dbesc(datetime_convert()),
						dbesc($uri),
						intval($importer["uid"])
					);
				create_tags_from_itemuri($uri, $importer["uid"]);
				create_files_from_itemuri($uri, $importer["uid"]);
				update_thread_uri($uri, $importer["uid"]);
			} else {
				$r = q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s',
						`body` = '', `title` = ''
					WHERE `uri` = '%s' AND `uid` = %d",
						dbesc($when),
						dbesc(datetime_convert()),
						dbesc($uri),
						intval($importer["uid"])
					);
				create_tags_from_itemuri($uri, $importer["uid"]);
				create_files_from_itemuri($uri, $importer["uid"]);
				update_thread_uri($uri, $importer["importer_uid"]);
				if($item["last-child"]) {
					// ensure that last-child is set in case the comment that had it just got wiped.
					q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d ",
						dbesc(datetime_convert()),
						dbesc($item["parent-uri"]),
						intval($item["uid"])
					);
					// who is the last child now?
					$r = q("SELECT `id` FROM `item` WHERE `parent-uri` = '%s' AND `type` != 'activity' AND `deleted` = 0 AND `moderated` = 0 AND `uid` = %d
						ORDER BY `created` DESC LIMIT 1",
							dbesc($item["parent-uri"]),
							intval($importer["uid"])
					);
					if (dbm::is_result($r)) {
						q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d",
							intval($r[0]["id"])
						);
					}
				}
				// if this is a relayed delete, propagate it to other recipients

				if($entrytype == DFRN_REPLY_RC) {
					logger("Notifying followers about deletion of post ".$item["id"], LOGGER_DEBUG);
					proc_run(PRIORITY_HIGH, "include/notifier.php","drop", $item["id"]);
				}
			}
		}
	}

	/**
	 * @brief Imports a DFRN message
	 *
	 * @param text $xml The DFRN message
	 * @param array $importer Record of the importer user mixed with contact of the content
	 * @param bool $sort_by_date Is used when feeds are polled
	 */
	public static function import($xml,$importer, $sort_by_date = false) {

		if ($xml == "")
			return;

		if($importer["readonly"]) {
			// We aren't receiving stuff from this person. But we will quietly ignore them
			// rather than a blatant "go away" message.
			logger('ignoring contact '.$importer["id"]);
			return;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);

		$xpath = new DomXPath($doc);
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

		$header = array();
		$header["uid"] = $importer["uid"];
		$header["network"] = NETWORK_DFRN;
		$header["type"] = "remote";
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["contact-id"] = $importer["id"];

		// Update the contact table if the data has changed

		// The "atom:author" is only present in feeds
		if ($xpath->query("/atom:feed/atom:author")->length > 0)
			self::fetchauthor($xpath, $doc->firstChild, $importer, "atom:author", false, $xml);

		// Only the "dfrn:owner" in the head section contains all data
		if ($xpath->query("/atom:feed/dfrn:owner")->length > 0)
			self::fetchauthor($xpath, $doc->firstChild, $importer, "dfrn:owner", false, $xml);

		logger("Import DFRN message for user ".$importer["uid"]." from contact ".$importer["id"], LOGGER_DEBUG);

		// The account type is new since 3.5.1
		if ($xpath->query("/atom:feed/dfrn:account_type")->length > 0) {
			$accounttype = intval($xpath->evaluate("/atom:feed/dfrn:account_type/text()", $context)->item(0)->nodeValue);

			if ($accounttype != $importer["contact-type"])
				q("UPDATE `contact` SET `contact-type` = %d WHERE `id` = %d",
					intval($accounttype),
					intval($importer["id"])
				);
		}

		// is it a public forum? Private forums aren't supported with this method
		// This is deprecated since 3.5.1
		$forum = intval($xpath->evaluate("/atom:feed/dfrn:community/text()", $context)->item(0)->nodeValue);

		if ($forum != $importer["forum"])
			q("UPDATE `contact` SET `forum` = %d WHERE `forum` != %d AND `id` = %d",
				intval($forum), intval($forum),
				intval($importer["id"])
			);

		$mails = $xpath->query("/atom:feed/dfrn:mail");
		foreach ($mails AS $mail)
			self::process_mail($xpath, $mail, $importer);

		$suggestions = $xpath->query("/atom:feed/dfrn:suggest");
		foreach ($suggestions AS $suggestion)
			self::process_suggestion($xpath, $suggestion, $importer);

		$relocations = $xpath->query("/atom:feed/dfrn:relocate");
		foreach ($relocations AS $relocation)
			self::process_relocation($xpath, $relocation, $importer);

		$deletions = $xpath->query("/atom:feed/at:deleted-entry");
		foreach ($deletions AS $deletion)
			self::process_deletion($xpath, $deletion, $importer);

		if (!$sort_by_date) {
			$entries = $xpath->query("/atom:feed/atom:entry");
			foreach ($entries AS $entry)
				self::process_entry($header, $xpath, $entry, $importer);
		} else {
			$newentries = array();
			$entries = $xpath->query("/atom:feed/atom:entry");
			foreach ($entries AS $entry) {
				$created = $xpath->query("atom:published/text()", $entry)->item(0)->nodeValue;
				$newentries[strtotime($created)] = $entry;
			}

			// Now sort after the publishing date
			ksort($newentries);

			foreach ($newentries AS $entry)
				self::process_entry($header, $xpath, $entry, $importer);
		}
		logger("Import done for user ".$importer["uid"]." from contact ".$importer["id"], LOGGER_DEBUG);
	}
}
?>
