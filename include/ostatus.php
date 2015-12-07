<?php
require_once("include/Contact.php");
require_once("include/threads.php");
require_once("include/html2bbcode.php");
require_once("include/items.php");
require_once("mod/share.php");
require_once("include/enotify.php");
require_once("include/socgraph.php");
require_once("include/Photo.php");
require_once("include/Scrape.php");
require_once("include/follow.php");

define('OSTATUS_DEFAULT_POLL_INTERVAL', 30); // given in minutes
define('OSTATUS_DEFAULT_POLL_TIMEFRAME', 1440); // given in minutes
define('OSTATUS_DEFAULT_POLL_TIMEFRAME_MENTIONS', 14400); // given in minutes

function ostatus_check_follow_friends() {
	$r = q("SELECT `uid`,`v` FROM `pconfig` WHERE `cat`='system' AND `k`='ostatus_legacy_contact' AND `v` != ''");

	if (!$r)
		return;

	foreach ($r AS $contact) {
		ostatus_follow_friends($contact["uid"], $contact["v"]);
		set_pconfig($contact["uid"], "system", "ostatus_legacy_contact", "");
	}
}

// This function doesn't work reliable by now.
function ostatus_follow_friends($uid, $url) {
	$contact = probe_url($url);

	if (!$contact)
		return;

	$api = $contact["baseurl"]."/api/";

	// Fetching friends
	$data = z_fetch_url($api."statuses/friends.json?screen_name=".$contact["nick"]);

	if (!$data["success"])
		return;

	$friends = json_decode($data["body"]);

	foreach ($friends AS $friend) {
		$url = $friend->statusnet_profile_url;
		$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND
			(`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s') AND
			`network` != '%s' LIMIT 1",
			intval($uid), dbesc(normalise_link($url)),
			dbesc(normalise_link($url)), dbesc($url), dbesc(NETWORK_STATUSNET));
		if (!$r) {
			$data = probe_url($friend->statusnet_profile_url);
			if ($data["network"] == NETWORK_OSTATUS) {
				$result = new_contact($uid,$friend->statusnet_profile_url);
				if ($result["success"])
					logger($friend->name." ".$url." - success", LOGGER_DEBUG);
				else
					logger($friend->name." ".$url." - failed", LOGGER_DEBUG);
			} else
				logger($friend->name." ".$url." - not OStatus", LOGGER_DEBUG);
		}
	}
}

function ostatus_fetchauthor($xpath, $context, $importer, &$contact, $onlyfetch) {

	$author = array();
	$author["author-link"] = $xpath->evaluate('atom:author/atom:uri/text()', $context)->item(0)->nodeValue;
	$author["author-name"] = $xpath->evaluate('atom:author/atom:name/text()', $context)->item(0)->nodeValue;

	// Preserve the value
	$authorlink = $author["author-link"];

	$alternate = $xpath->query("atom:author/atom:link[@rel='alternate']", $context)->item(0)->attributes;
	if (is_object($alternate))
		foreach($alternate AS $attributes)
			if ($attributes->name == "href")
				$author["author-link"] = $attributes->textContent;

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` IN ('%s', '%s') AND `network` != '%s'",
		intval($importer["uid"]), dbesc(normalise_link($author["author-link"])),
		dbesc(normalise_link($authorlink)), dbesc(NETWORK_STATUSNET));
	if ($r) {
		$contact = $r[0];
		$author["contact-id"] = $r[0]["id"];
	} else
		$author["contact-id"] = $contact["id"];

	$avatarlist = array();
	$avatars = $xpath->query("atom:author/atom:link[@rel='avatar']", $context);
	foreach($avatars AS $avatar) {
		$href = "";
		$width = 0;
		foreach($avatar->attributes AS $attributes) {
			if ($attributes->name == "href")
				$href = $attributes->textContent;
			if ($attributes->name == "width")
				$width = $attributes->textContent;
		}
		if (($width > 0) AND ($href != ""))
			$avatarlist[$width] = $href;
	}
	if (count($avatarlist) > 0) {
		krsort($avatarlist);
		$author["author-avatar"] = current($avatarlist);
	}

	$displayname = $xpath->evaluate('atom:author/poco:displayName/text()', $context)->item(0)->nodeValue;
	if ($displayname != "")
		$author["author-name"] = $displayname;

	$author["owner-name"] = $author["author-name"];
	$author["owner-link"] = $author["author-link"];
	$author["owner-avatar"] = $author["author-avatar"];

	if ($r AND !$onlyfetch) {
		// Update contact data
		$update_contact = ($r[0]['name-date'] < datetime_convert('','','now -12 hours'));
		if ($update_contact) {
			logger("Update contact data for contact ".$contact["id"], LOGGER_DEBUG);

			$value = $xpath->evaluate('atom:author/poco:displayName/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["name"] = $value;

			$value = $xpath->evaluate('atom:author/poco:preferredUsername/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["nick"] = $value;

			$value = $xpath->evaluate('atom:author/poco:note/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["about"] = $value;

			$value = $xpath->evaluate('atom:author/poco:address/poco:formatted/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["location"] = $value;

			q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `about` = '%s', `location` = '%s', `name-date` = '%s' WHERE `id` = %d",
				dbesc($contact["name"]), dbesc($contact["nick"]), dbesc($contact["about"]), dbesc($contact["location"]),
				dbesc(datetime_convert()), intval($contact["id"]));

			poco_check($contact["url"], $contact["name"], $contact["network"], $author["author-avatar"], $contact["about"], $contact["location"],
					"", "", "", datetime_convert(), 2, $contact["id"], $contact["uid"]);
		}

		$update_photo = ($r[0]['avatar-date'] < datetime_convert('','','now -12 hours'));

		if ($update_photo AND isset($author["author-avatar"])) {
			logger("Update profile picture for contact ".$contact["id"], LOGGER_DEBUG);

			$photos = import_profile_photo($author["author-avatar"], $importer["uid"], $contact["id"]);

			q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s', `avatar-date` = '%s' WHERE `id` = %d",
				dbesc($photos[0]), dbesc($photos[1]), dbesc($photos[2]),
				dbesc(datetime_convert()), intval($contact["id"]));
		}
	}

	return($author);
}

function ostatus_salmon_author($xml, $importer) {
	$a = get_app();

	if ($xml == "")
		return;

	$doc = new DOMDocument();
	@$doc->loadXML($xml);

	$xpath = new DomXPath($doc);
	$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
	$xpath->registerNamespace('thr', "http://purl.org/syndication/thread/1.0");
	$xpath->registerNamespace('georss', "http://www.georss.org/georss");
	$xpath->registerNamespace('activity', "http://activitystrea.ms/spec/1.0/");
	$xpath->registerNamespace('media', "http://purl.org/syndication/atommedia");
	$xpath->registerNamespace('poco', "http://portablecontacts.net/spec/1.0");
	$xpath->registerNamespace('ostatus', "http://ostatus.org/schema/1.0");
	$xpath->registerNamespace('statusnet', "http://status.net/schema/api/1/");

	$entries = $xpath->query('/atom:entry');

	foreach ($entries AS $entry) {
		// fetch the author
		$author = ostatus_fetchauthor($xpath, $entry, $importer, $contact, true);
		return $author;
	}
}

function ostatus_import($xml,$importer,&$contact, &$hub) {

	$a = get_app();

	logger("Import OStatus message", LOGGER_DEBUG);

	if ($xml == "")
		return;

	$doc = new DOMDocument();
	@$doc->loadXML($xml);

	$xpath = new DomXPath($doc);
	$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
	$xpath->registerNamespace('thr', "http://purl.org/syndication/thread/1.0");
	$xpath->registerNamespace('georss', "http://www.georss.org/georss");
	$xpath->registerNamespace('activity', "http://activitystrea.ms/spec/1.0/");
	$xpath->registerNamespace('media', "http://purl.org/syndication/atommedia");
	$xpath->registerNamespace('poco', "http://portablecontacts.net/spec/1.0");
	$xpath->registerNamespace('ostatus', "http://ostatus.org/schema/1.0");
	$xpath->registerNamespace('statusnet', "http://status.net/schema/api/1/");

	$gub = "";
	$hub_attributes = $xpath->query("/atom:feed/atom:link[@rel='hub']")->item(0)->attributes;
	if (is_object($hub_attributes))
		foreach($hub_attributes AS $hub_attribute)
			if ($hub_attribute->name == "href") {
				$hub = $hub_attribute->textContent;
				logger("Found hub ".$hub, LOGGER_DEBUG);
			}

	$header = array();
	$header["uid"] = $importer["uid"];
	$header["network"] = NETWORK_OSTATUS;
	$header["type"] = "remote";
	$header["wall"] = 0;
	$header["origin"] = 0;
	$header["gravity"] = GRAVITY_PARENT;

	// it could either be a received post or a post we fetched by ourselves
	// depending on that, the first node is different
	$first_child = $doc->firstChild->tagName;

	if ($first_child == "feed")
		$entries = $xpath->query('/atom:feed/atom:entry');
	else
		$entries = $xpath->query('/atom:entry');

	$conversation = "";
	$conversationlist = array();
	$item_id = 0;

	// Reverse the order of the entries
	$entrylist = array();

	foreach ($entries AS $entry)
		$entrylist[] = $entry;

	foreach (array_reverse($entrylist) AS $entry) {

		$mention = false;

		// fetch the author
		if ($first_child == "feed")
			$author = ostatus_fetchauthor($xpath, $doc->firstChild, $importer, $contact, false);
		else
			$author = ostatus_fetchauthor($xpath, $entry, $importer, $contact, false);

		$value = $xpath->evaluate('atom:author/poco:preferredUsername/text()', $context)->item(0)->nodeValue;
		if ($value != "")
			$nickname = $value;
		else
			$nickname = $author["author-name"];

		$item = array_merge($header, $author);

		// Now get the item
		$item["uri"] = $xpath->query('atom:id/text()', $entry)->item(0)->nodeValue;

		$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
			intval($importer["uid"]), dbesc($item["uri"]));
		if ($r) {
			logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already existed under id ".$r[0]["id"], LOGGER_DEBUG);
			continue;
		}

		$item["body"] = add_page_info_to_body(html2bbcode($xpath->query('atom:content/text()', $entry)->item(0)->nodeValue));
		$item["object-type"] = $xpath->query('activity:object-type/text()', $entry)->item(0)->nodeValue;

		if (($item["object-type"] == ACTIVITY_OBJ_BOOKMARK) OR ($item["object-type"] == ACTIVITY_OBJ_EVENT)) {
			$item["title"] = $xpath->query('atom:title/text()', $entry)->item(0)->nodeValue;
			$item["body"] = $xpath->query('atom:summary/text()', $entry)->item(0)->nodeValue;
		} elseif ($item["object-type"] == ACTIVITY_OBJ_QUESTION)
			$item["title"] = $xpath->query('atom:title/text()', $entry)->item(0)->nodeValue;

		$item["object"] = $xml;
		$item["verb"] = $xpath->query('activity:verb/text()', $entry)->item(0)->nodeValue;

		// To-Do:
		// Delete a message
		if ($item["verb"] == "qvitter-delete-notice") {
			// ignore "Delete" messages (by now)
			logger("Ignore delete message ".print_r($item, true));
			continue;
		}

		if ($item["verb"] == ACTIVITY_JOIN) {
			// ignore "Join" messages
			logger("Ignore join message ".print_r($item, true));
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

		if ($item["verb"] == NAMESPACE_OSTATUS."/unfavorite") {
			// Ignore "Unfavorite" message
			logger("Ignore unfavorite message ".print_r($item, true));
			continue;
		}

		// http://activitystrea.ms/schema/1.0/rsvp-yes
		if (!in_array($item["verb"], array(ACTIVITY_POST, ACTIVITY_LIKE, ACTIVITY_SHARE)))
			logger("Unhandled verb ".$item["verb"]." ".print_r($item, true));

		$item["created"] = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;
		$item["edited"] = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;
		$conversation = $xpath->query('ostatus:conversation/text()', $entry)->item(0)->nodeValue;

		$related = "";

		$inreplyto = $xpath->query('thr:in-reply-to', $entry);
		if (is_object($inreplyto->item(0))) {
			foreach($inreplyto->item(0)->attributes AS $attributes) {
				if ($attributes->name == "ref")
					$item["parent-uri"] = $attributes->textContent;
				if ($attributes->name == "href")
					$related = $attributes->textContent;
			}
		}

		$georsspoint = $xpath->query('georss:point', $entry);
		if ($georsspoint)
			$item["coord"] = $georsspoint->item(0)->nodeValue;

		// To-Do
		// $item["location"] =

		$categories = $xpath->query('atom:category', $entry);
		if ($categories) {
			foreach ($categories AS $category) {
				foreach($category->attributes AS $attributes)
					if ($attributes->name == "term") {
						$term = $attributes->textContent;
						if(strlen($item["tag"]))
							$item["tag"] .= ',';
						$item["tag"] .= "#[url=".$a->get_baseurl()."/search?tag=".$term."]".$term."[/url]";
					}
			}
		}

		$self = "";
		$enclosure = "";

		$links = $xpath->query('atom:link', $entry);
		if ($links) {
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
							if (($item["object-type"] == ACTIVITY_OBJ_QUESTION) OR
								($item["object-type"] == ACTIVITY_OBJ_EVENT))
								$item["body"] .= add_page_info($href);
							break;
						case "ostatus:conversation":
							$conversation = $href;
							break;
						case "enclosure":
							$enclosure = $href;
							if(strlen($item["attach"]))
								$item["attach"] .= ',';

							$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'" title="'.$title.'"[/attach]';
							break;
						case "related":
							if ($item["object-type"] != ACTIVITY_OBJ_BOOKMARK) {
								if (!isset($item["parent-uri"]))
									$item["parent-uri"] = $href;

								if ($related == "")
									$related = $href;
							} else
								$item["body"] .= add_page_info($href);
							break;
						case "self":
							$self = $href;
							break;
						case "mentioned":
							// Notification check
							if ($importer["nurl"] == normalise_link($href))
								$mention = true;
							break;
					}
			}
		}

		$local_id = "";
		$repeat_of = "";

		$notice_info = $xpath->query('statusnet:notice_info', $entry);
		if ($notice_info AND ($notice_info->length > 0)) {
			foreach($notice_info->item(0)->attributes AS $attributes) {
				if ($attributes->name == "source")
					$item["app"] = strip_tags($attributes->textContent);
				if ($attributes->name == "local_id")
					$local_id = $attributes->textContent;
				if ($attributes->name == "repeat_of")
					$repeat_of = $attributes->textContent;
			}
		}

		// Is it a repeated post?
		if ($repeat_of != "") {
			$activityobjects = $xpath->query('activity:object', $entry)->item(0);

			if (is_object($activityobjects)) {

				$orig_uri = $xpath->query("activity:object/atom:id", $activityobjects)->item(0)->nodeValue;
				if (!isset($orig_uri))
					$orig_uri = $xpath->query('atom:id/text()', $activityobjects)->item(0)->nodeValue;

				$orig_links = $xpath->query("activity:object/atom:link[@rel='alternate']", $activityobjects);
				if ($orig_links AND ($orig_links->length > 0))
					foreach($orig_links->item(0)->attributes AS $attributes)
						if ($attributes->name == "href")
							$orig_link = $attributes->textContent;

				if (!isset($orig_link))
					$orig_link = $xpath->query("atom:link[@rel='alternate']", $activityobjects)->item(0)->nodeValue;

				if (!isset($orig_link))
					$orig_link =  ostatus_convert_href($orig_uri);

				$orig_body = $xpath->query('activity:object/atom:content/text()', $activityobjects)->item(0)->nodeValue;
				if (!isset($orig_body))
					$orig_body = $xpath->query('atom:content/text()', $activityobjects)->item(0)->nodeValue;

				$orig_created = $xpath->query('atom:published/text()', $activityobjects)->item(0)->nodeValue;

				$orig_contact = $contact;
				$orig_author = ostatus_fetchauthor($xpath, $activityobjects, $importer, $orig_contact, false);

				//if (!intval(get_config('system','wall-to-wall_share'))) {
				//	$prefix = share_header($orig_author['author-name'], $orig_author['author-link'], $orig_author['author-avatar'], "", $orig_created, $orig_link);
				//	$item["body"] = $prefix.add_page_info_to_body(html2bbcode($orig_body))."[/share]";
				//} else {
					$item["author-name"] = $orig_author["author-name"];
					$item["author-link"] = $orig_author["author-link"];
					$item["author-avatar"] = $orig_author["author-avatar"];
					$item["body"] = add_page_info_to_body(html2bbcode($orig_body));
					$item["created"] = $orig_created;

					$item["uri"] = $orig_uri;
					$item["plink"] = $orig_link;
				//}

				$item["verb"] = $xpath->query('activity:verb/text()', $activityobjects)->item(0)->nodeValue;

				$item["object-type"] = $xpath->query('activity:object/activity:object-type/text()', $activityobjects)->item(0)->nodeValue;
				if (!isset($item["object-type"]))
					$item["object-type"] = $xpath->query('activity:object-type/text()', $activityobjects)->item(0)->nodeValue;
			}
		}

		//if ($enclosure != "")
		//	$item["body"] .= add_page_info($enclosure);

		if (isset($item["parent-uri"])) {
			$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
				intval($importer["uid"]), dbesc($item["parent-uri"]));

			if (!$r AND ($related != "")) {
				$reply_path = str_replace("/notice/", "/api/statuses/show/", $related).".atom";

				if ($reply_path != $related) {
					logger("Fetching related items for user ".$importer["uid"]." from ".$reply_path, LOGGER_DEBUG);
					$reply_xml = fetch_url($reply_path);

					$reply_contact = $contact;
					ostatus_import($reply_xml,$importer,$reply_contact, $reply_hub);

					// After the import try to fetch the parent item again
					$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
						intval($importer["uid"]), dbesc($item["parent-uri"]));
				}
			}
			if ($r) {
				$item["type"] = 'remote-comment';
				$item["gravity"] = GRAVITY_COMMENT;
			}
		} else
			$item["parent-uri"] = $item["uri"];

		$item_id = ostatus_completion($conversation, $importer["uid"], $item);

		if (!$item_id) {
			logger("Error storing item", LOGGER_DEBUG);
			continue;
		}

		logger("Item was stored with id ".$item_id, LOGGER_DEBUG);
		$item["id"] = $item_id;

		if ($mention) {
			$u = q("SELECT `notify-flags`, `language`, `username`, `email` FROM user WHERE uid = %d LIMIT 1", intval($item['uid']));
			$r = q("SELECT `parent` FROM `item` WHERE `id` = %d", intval($item_id));

			notification(array(
				'type'         => NOTIFY_TAGSELF,
				'notify_flags' => $u[0]["notify-flags"],
				'language'     => $u[0]["language"],
				'to_name'      => $u[0]["username"],
				'to_email'     => $u[0]["email"],
				'uid'          => $item["uid"],
				'item'         => $item,
				'link'         => $a->get_baseurl().'/display/'.urlencode(get_item_guid($item_id)),
				'source_name'  => $item["author-name"],
				'source_link'  => $item["author-link"],
				'source_photo' => $item["author-avatar"],
				'verb'         => ACTIVITY_TAG,
				'otype'        => 'item',
				'parent'       => $r[0]["parent"]
			));
		}
	}
}

function ostatus_convert_href($href) {
	$elements = explode(":",$href);

	if ((count($elements) <= 2) OR ($elements[0] != "tag"))
		return $href;

	$server = explode(",", $elements[1]);
	$conversation = explode("=", $elements[2]);

	if ((count($elements) == 4) AND ($elements[2] == "post"))
		return "http://".$server[0]."/notice/".$elements[3];

	if ((count($conversation) != 2) OR ($conversation[1] ==""))
		return $href;

	if ($elements[3] == "objectType=thread")
		return "http://".$server[0]."/conversation/".$conversation[1];
	else
		return "http://".$server[0]."/notice/".$conversation[1];

	return $href;
}

function check_conversations($mentions = false, $override = false) {
	$last = get_config('system','ostatus_last_poll');

	$poll_interval = intval(get_config('system','ostatus_poll_interval'));
	if(! $poll_interval)
		$poll_interval = OSTATUS_DEFAULT_POLL_INTERVAL;

	// Don't poll if the interval is set negative
	if (($poll_interval < 0) AND !$override)
		return;

	if (!$mentions) {
		$poll_timeframe = intval(get_config('system','ostatus_poll_timeframe'));
		if (!$poll_timeframe)
			$poll_timeframe = OSTATUS_DEFAULT_POLL_TIMEFRAME;
	} else {
		$poll_timeframe = intval(get_config('system','ostatus_poll_timeframe'));
		if (!$poll_timeframe)
			$poll_timeframe = OSTATUS_DEFAULT_POLL_TIMEFRAME_MENTIONS;
	}


	if ($last AND !$override) {
		$next = $last + ($poll_interval * 60);
		if ($next > time()) {
			logger('poll interval not reached');
			return;
		}
	}

	logger('cron_start');

	$start = date("Y-m-d H:i:s", time() - ($poll_timeframe * 60));

	if ($mentions)
		$conversations = q("SELECT `term`.`oid`, `term`.`url`, `term`.`uid` FROM `term`
					STRAIGHT_JOIN `thread` ON `thread`.`iid` = `term`.`oid` AND `thread`.`uid` = `term`.`uid`
					WHERE `term`.`type` = 7 AND `term`.`term` > '%s' AND `thread`.`mention`
					GROUP BY `term`.`url`, `term`.`uid` ORDER BY `term`.`term` DESC", dbesc($start));
	else
		$conversations = q("SELECT `oid`, `url`, `uid` FROM `term`
					WHERE `type` = 7 AND `term` > '%s'
					GROUP BY `url`, `uid` ORDER BY `term` DESC", dbesc($start));

	foreach ($conversations AS $conversation) {
		ostatus_completion($conversation['url'], $conversation['uid']);
	}

	logger('cron_end');

	set_config('system','ostatus_last_poll', time());
}

function ostatus_completion($conversation_url, $uid, $item = array()) {

	$a = get_app();

	$item_stored = -1;

	$conversation_url = ostatus_convert_href($conversation_url);

	// If the thread shouldn't be completed then store the item and go away
	if ((intval(get_config('system','ostatus_poll_interval')) == -2) AND (count($item) > 0)) {
		//$arr["app"] .= " (OStatus-NoCompletion)";
		$item_stored = item_store($item, true);
		return($item_stored);
	}

	// Get the parent
	$parents = q("SELECT `id`, `parent`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `id` IN
			(SELECT `parent` FROM `item` WHERE `id` IN
				(SELECT `oid` FROM `term` WHERE `uid` = %d AND `otype` = %d AND `type` = %d AND `url` = '%s'))",
			intval($uid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION), dbesc($conversation_url));

	if ($parents)
		$parent = $parents[0];
	elseif (count($item) > 0) {
		$parent = $item;
		$parent["type"] = "remote";
		$parent["verb"] = ACTIVITY_POST;
		$parent["visible"] = 1;
	} else {
		// Preset the parent
		$r = q("SELECT `id` FROM `contact` WHERE `self` AND `uid`=%d", $uid);
		if (!$r)
			return(-2);

		$parent = array();
		$parent["id"] = 0;
		$parent["parent"] = 0;
		$parent["uri"] = "";
		$parent["contact-id"] = $r[0]["id"];
		$parent["type"] = "remote";
		$parent["verb"] = ACTIVITY_POST;
		$parent["visible"] = 1;
	}

	$conv = str_replace("/conversation/", "/api/statusnet/conversation/", $conversation_url).".as";
	$pageno = 1;
	$items = array();

	logger('fetching conversation url '.$conv.' for user '.$uid);

	do {
		$conv_arr = z_fetch_url($conv."?page=".$pageno);

		// If it is a non-ssl site and there is an error, then try ssl or vice versa
		if (!$conv_arr["success"] AND (substr($conv, 0, 7) == "http://")) {
			$conv = str_replace("http://", "https://", $conv);
			$conv_as = fetch_url($conv."?page=".$pageno);
		} elseif (!$conv_arr["success"] AND (substr($conv, 0, 8) == "https://")) {
			$conv = str_replace("https://", "http://", $conv);
			$conv_as = fetch_url($conv."?page=".$pageno);
		} else
			$conv_as = $conv_arr["body"];

		$conv_as = str_replace(',"statusnet:notice_info":', ',"statusnet_notice_info":', $conv_as);
		$conv_as = json_decode($conv_as);

		$no_of_items = sizeof($items);

		if (@is_array($conv_as->items))
			foreach ($conv_as->items AS $single_item)
				$items[$single_item->id] = $single_item;

		if ($no_of_items == sizeof($items))
			break;

		$pageno++;

	} while (true);

	logger('fetching conversation done. Found '.count($items).' items');

	if (!sizeof($items)) {
		if (count($item) > 0) {
			//$arr["app"] .= " (OStatus-NoConvFetched)";
			$item_stored = item_store($item, true);

			if ($item_stored) {
				logger("Conversation ".$conversation_url." couldn't be fetched. Item uri ".$item["uri"]." stored: ".$item_stored, LOGGER_DEBUG);
				ostatus_store_conversation($item_id, $conversation_url);
			}

			return($item_stored);
		} else
			return(-3);
	}

	$items = array_reverse($items);

	$r = q("SELECT `nurl` FROM `contact` WHERE `uid` = %d AND `self`", intval($uid));
	$importer = $r[0];

	foreach ($items as $single_conv) {

		// Test - remove before flight
		//$tempfile = tempnam(get_temppath(), "conversation");
		//file_put_contents($tempfile, json_encode($single_conv));

		$mention = false;

		if (isset($single_conv->object->id))
			$single_conv->id = $single_conv->object->id;

		$plink = ostatus_convert_href($single_conv->id);
		if (isset($single_conv->object->url))
			$plink = ostatus_convert_href($single_conv->object->url);

		if (@!$single_conv->id)
			continue;

		logger("Got id ".$single_conv->id, LOGGER_DEBUG);

		if ($first_id == "") {
			$first_id = $single_conv->id;

			// The first post of the conversation isn't our first post. There are three options:
			// 1. Our conversation hasn't the "real" thread starter
			// 2. This first post is a post inside our thread
			// 3. This first post is a post inside another thread
			if (($first_id != $parent["uri"]) AND ($parent["uri"] != "")) {
				$new_parents = q("SELECT `id`, `parent`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `id` IN
							(SELECT `parent` FROM `item`
								WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s')) LIMIT 1",
					intval($uid), dbesc($first_id), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
				if ($new_parents) {
					if ($new_parents[0]["parent"] == $parent["parent"]) {
						// Option 2: This post is already present inside our thread - but not as thread starter
						logger("Option 2: uri present in our thread: ".$first_id, LOGGER_DEBUG);
						$first_id = $parent["uri"];
					} else {
						// Option 3: Not so good. We have mixed parents. We have to see how to clean this up.
						// For now just take the new parent.
						$parent = $new_parents[0];
						$first_id = $parent["uri"];
						logger("Option 3: mixed parents for uri ".$first_id, LOGGER_DEBUG);
					}
				} else {
					// Option 1: We hadn't got the real thread starter
					// We have to clean up our existing messages.
					$parent["id"] = 0;
					$parent["uri"] = $first_id;
					logger("Option 1: we have a new parent: ".$first_id, LOGGER_DEBUG);
				}
			} elseif ($parent["uri"] == "") {
				$parent["id"] = 0;
				$parent["uri"] = $first_id;
			}
		}

		$parent_uri = $parent["uri"];

		// "context" only seems to exist on older servers
		if (isset($single_conv->context->inReplyTo->id)) {
			$parent_exists = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
						intval($uid), dbesc($single_conv->context->inReplyTo->id), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
			if ($parent_exists)
				$parent_uri = $single_conv->context->inReplyTo->id;
		}

		// This is the current way
		if (isset($single_conv->object->inReplyTo->id)) {
			$parent_exists = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
						intval($uid), dbesc($single_conv->object->inReplyTo->id), dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
			if ($parent_exists)
				$parent_uri = $single_conv->object->inReplyTo->id;
		}

		$message_exists = q("SELECT `id`, `parent`, `uri` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s','%s') LIMIT 1",
						intval($uid), dbesc($single_conv->id),
						dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));
		if ($message_exists) {
			logger("Message ".$single_conv->id." already existed on the system", LOGGER_DEBUG);

			if ($parent["id"] != 0) {
				$existing_message = $message_exists[0];

				// We improved the way we fetch OStatus messages, this shouldn't happen very often now
				// To-Do: we have to change the shadow copies as well. This way here is really ugly.
				if ($existing_message["parent"] != $parent["id"]) {
					logger('updating id '.$existing_message["id"].' with parent '.$existing_message["parent"].' to parent '.$parent["id"].' uri '.$parent["uri"].' thread '.$parent_uri, LOGGER_DEBUG);

					// Update the parent id of the selected item
					$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s' WHERE `id` = %d",
						intval($parent["id"]), dbesc($parent["uri"]), intval($existing_message["id"]));

					// Update the parent uri in the thread - but only if it points to itself
					$r = q("UPDATE `item` SET `thr-parent` = '%s' WHERE `id` = %d AND `uri` = `thr-parent`",
						dbesc($parent_uri), intval($existing_message["id"]));

					// try to change all items of the same parent
					$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s' WHERE `parent` = %d",
						intval($parent["id"]), dbesc($parent["uri"]), intval($existing_message["parent"]));

					// Update the parent uri in the thread - but only if it points to itself
					$r = q("UPDATE `item` SET `thr-parent` = '%s' WHERE (`parent` = %d) AND (`uri` = `thr-parent`)",
						dbesc($parent["uri"]), intval($existing_message["parent"]));

					// Now delete the thread
					delete_thread($existing_message["parent"]);
				}
			}

			// The item we are having on the system is the one that we wanted to store via the item array
			if (isset($item["uri"]) AND ($item["uri"] == $existing_message["uri"])) {
				$item = array();
				$item_stored = 0;
			}

			continue;
		}

		if (is_array($single_conv->to))
			foreach($single_conv->to AS $to)
				if ($importer["nurl"] == normalise_link($to->id))
					$mention = true;

		$actor = $single_conv->actor->id;
		if (isset($single_conv->actor->url))
			$actor = $single_conv->actor->url;

		$contact = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` != '%s'",
				$uid, normalise_link($actor), NETWORK_STATUSNET);

		if (count($contact)) {
			logger("Found contact for url ".$actor, LOGGER_DEBUG);
			$contact_id = $contact[0]["id"];
		} else {
			logger("No contact found for url ".$actor, LOGGER_DEBUG);

			// Adding a global contact
			// To-Do: Use this data for the post
			$global_contact_id = get_contact($actor, 0);

			logger("Global contact ".$global_contact_id." found for url ".$actor, LOGGER_DEBUG);

			$contact_id = $parent["contact-id"];
		}

		$arr = array();
		$arr["network"] = NETWORK_OSTATUS;
		$arr["uri"] = $single_conv->id;
		$arr["plink"] = $plink;
		$arr["uid"] = $uid;
		$arr["contact-id"] = $contact_id;
		$arr["parent-uri"] = $parent_uri;
		$arr["created"] = $single_conv->published;
		$arr["edited"] = $single_conv->published;
		$arr["owner-name"] = $single_conv->actor->displayName;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->contact->displayName;
		if ($arr["owner-name"] == '')
			$arr["owner-name"] = $single_conv->actor->portablecontacts_net->displayName;

		$arr["owner-link"] = $actor;
		$arr["owner-avatar"] = $single_conv->actor->image->url;
		$arr["author-name"] = $arr["owner-name"];
		$arr["author-link"] = $actor;
		$arr["author-avatar"] = $single_conv->actor->image->url;
		$arr["body"] = add_page_info_to_body(html2bbcode($single_conv->content));

		if (isset($single_conv->status_net->notice_info->source))
			$arr["app"] = strip_tags($single_conv->status_net->notice_info->source);
		elseif (isset($single_conv->statusnet->notice_info->source))
			$arr["app"] = strip_tags($single_conv->statusnet->notice_info->source);
		elseif (isset($single_conv->statusnet_notice_info->source))
			$arr["app"] = strip_tags($single_conv->statusnet_notice_info->source);
		elseif (isset($single_conv->provider->displayName))
			$arr["app"] = $single_conv->provider->displayName;
		else
			$arr["app"] = "OStatus";

		//$arr["app"] .= " (Conversation)";

		$arr["object"] = json_encode($single_conv);
		$arr["verb"] = $parent["verb"];
		$arr["visible"] = $parent["visible"];
		$arr["location"] = $single_conv->location->displayName;
		$arr["coord"] = trim($single_conv->location->lat." ".$single_conv->location->lon);

		// Is it a reshared item?
		if (isset($single_conv->verb) AND ($single_conv->verb == "share") AND isset($single_conv->object)) {
			if (is_array($single_conv->object))
				$single_conv->object = $single_conv->object[0];

			logger("Found reshared item ".$single_conv->object->id);

			// $single_conv->object->context->conversation;

			if (isset($single_conv->object->object->id))
				$arr["uri"] = $single_conv->object->object->id;
			else
				$arr["uri"] = $single_conv->object->id;

			if (isset($single_conv->object->object->url))
				$plink = ostatus_convert_href($single_conv->object->object->url);
			else
				$plink = ostatus_convert_href($single_conv->object->url);

			if (isset($single_conv->object->object->content))
				$arr["body"] = add_page_info_to_body(html2bbcode($single_conv->object->object->content));
			else
				$arr["body"] = add_page_info_to_body(html2bbcode($single_conv->object->content));

			$arr["plink"] = $plink;

			$arr["created"] = $single_conv->object->published;
			$arr["edited"] = $single_conv->object->published;

			$arr["author-name"] = $single_conv->object->actor->displayName;
			if ($arr["owner-name"] == '')
				$arr["author-name"] = $single_conv->object->actor->contact->displayName;

			$arr["author-link"] = $single_conv->object->actor->url;
			$arr["author-avatar"] = $single_conv->object->actor->image->url;

			$arr["app"] = $single_conv->object->provider->displayName."#";
			//$arr["verb"] = $single_conv->object->verb;

			$arr["location"] = $single_conv->object->location->displayName;
			$arr["coord"] = trim($single_conv->object->location->lat." ".$single_conv->object->location->lon);
		}

		if ($arr["location"] == "")
			unset($arr["location"]);

		if ($arr["coord"] == "")
			unset($arr["coord"]);

		// Copy fields from given item array
		if (isset($item["uri"]) AND (($item["uri"] == $arr["uri"]) OR ($item["uri"] ==  $single_conv->id))) {
			$copy_fields = array("owner-name", "owner-link", "owner-avatar", "author-name", "author-link", "author-avatar",
						"gravity", "body", "object-type", "object", "verb", "created", "edited", "coord", "tag",
						"title", "attach", "app", "type", "location", "contact-id", "uri");
			foreach ($copy_fields AS $field)
				if (isset($item[$field]))
					$arr[$field] = $item[$field];

			//$arr["app"] .= " (OStatus)";
		}

		$newitem = item_store($arr);
		if (!$newitem) {
			logger("Item wasn't stored ".print_r($arr, true), LOGGER_DEBUG);
			continue;
		}

		if (isset($item["uri"]) AND ($item["uri"] == $arr["uri"])) {
			$item = array();
			$item_stored = $newitem;
		}

		logger('Stored new item '.$plink.' for parent '.$arr["parent-uri"].' under id '.$newitem, LOGGER_DEBUG);

		// Add the conversation entry (but don't fetch the whole conversation)
		ostatus_store_conversation($newitem, $conversation_url);

		if ($mention) {
			$u = q("SELECT `notify-flags`, `language`, `username`, `email` FROM user WHERE uid = %d LIMIT 1", intval($uid));
			$r = q("SELECT `parent` FROM `item` WHERE `id` = %d", intval($newitem));

			notification(array(
				'type'         => NOTIFY_TAGSELF,
				'notify_flags' => $u[0]["notify-flags"],
				'language'     => $u[0]["language"],
				'to_name'      => $u[0]["username"],
				'to_email'     => $u[0]["email"],
				'uid'          => $uid,
				'item'         => $arr,
				'link'         => $a->get_baseurl().'/display/'.urlencode(get_item_guid($newitem)),
				'source_name'  => $arr["author-name"],
				'source_link'  => $arr["author-link"],
				'source_photo' => $arr["author-avatar"],
				'verb'         => ACTIVITY_TAG,
				'otype'        => 'item',
				'parent'       => $r[0]["parent"]
			));
		}

		// If the newly created item is the top item then change the parent settings of the thread
		// This shouldn't happen anymore. This is supposed to be absolote.
		if ($arr["uri"] == $first_id) {
			logger('setting new parent to id '.$newitem);
			$new_parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
				intval($uid), intval($newitem));
			if ($new_parents)
				$parent = $new_parents[0];
		}
	}

	if (($item_stored < 0) AND (count($item) > 0)) {
		//$arr["app"] .= " (OStatus-NoConvFound)";
		$item_stored = item_store($item, true);
		if ($item_stored) {
			logger("Uri ".$item["uri"]." wasn't found in conversation ".$conversation_url, LOGGER_DEBUG);
			ostatus_store_conversation($item_stored, $conversation_url);
		}
	}

	return($item_stored);
}

function ostatus_store_conversation($itemid, $conversation_url) {
	global $a;

	$conversation_url = ostatus_convert_href($conversation_url);

	$messages = q("SELECT `uid`, `parent`, `created`, `received`, `guid` FROM `item` WHERE `id` = %d LIMIT 1", intval($itemid));
	if (!$messages)
		return;
	$message = $messages[0];

	// Store conversation url if not done before
	$conversation = q("SELECT `url` FROM `term` WHERE `uid` = %d AND `oid` = %d AND `otype` = %d AND `type` = %d",
		intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION));

	if (!$conversation) {
		$r = q("INSERT INTO `term` (`uid`, `oid`, `otype`, `type`, `term`, `url`, `created`, `received`, `guid`) VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s')",
			intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION),
			dbesc($message["created"]), dbesc($conversation_url), dbesc($message["created"]), dbesc($message["received"]), dbesc($message["guid"]));
		logger('Storing conversation url '.$conversation_url.' for id '.$itemid);
	}
}
?>
