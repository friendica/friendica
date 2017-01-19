<?php
/**
 * @file include/ostatus.php
 */

require_once("include/Contact.php");
require_once("include/threads.php");
require_once("include/html2bbcode.php");
require_once("include/bbcode.php");
require_once("include/items.php");
require_once("mod/share.php");
require_once("include/enotify.php");
require_once("include/socgraph.php");
require_once("include/Photo.php");
require_once("include/Scrape.php");
require_once("include/follow.php");
require_once("include/api.php");
require_once("mod/proxy.php");
require_once("include/xml.php");

/**
 * @brief This class contain functions for the OStatus protocol
 *
 */
class ostatus {
	const OSTATUS_DEFAULT_POLL_INTERVAL = 30; // given in minutes
	const OSTATUS_DEFAULT_POLL_TIMEFRAME = 1440; // given in minutes
	const OSTATUS_DEFAULT_POLL_TIMEFRAME_MENTIONS = 14400; // given in minutes

	/**
	 * @brief Fetches author data
	 *
	 * @param object $xpath The xpath object
	 * @param object $context The xml context of the author detals
	 * @param array $importer user record of the importing user
	 * @param array $contact Called by reference, will contain the fetched contact
	 * @param bool $onlyfetch Only fetch the header without updating the contact entries
	 *
	 * @return array Array of author related entries for the item
	 */
	private function fetchauthor($xpath, $context, $importer, &$contact, $onlyfetch) {

		$author = array();
		$author["author-link"] = $xpath->evaluate('atom:author/atom:uri/text()', $context)->item(0)->nodeValue;
		$author["author-name"] = $xpath->evaluate('atom:author/atom:name/text()', $context)->item(0)->nodeValue;

		$aliaslink = $author["author-link"];

		$alternate = $xpath->query("atom:author/atom:link[@rel='alternate']", $context)->item(0)->attributes;
		if (is_object($alternate))
			foreach($alternate AS $attributes)
				if ($attributes->name == "href")
					$author["author-link"] = $attributes->textContent;

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` IN ('%s', '%s') AND `network` != '%s'",
			intval($importer["uid"]), dbesc(normalise_link($author["author-link"])),
			dbesc(normalise_link($aliaslink)), dbesc(NETWORK_STATUSNET));
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

		// Only update the contacts if it is an OStatus contact
		if ($r AND !$onlyfetch AND ($contact["network"] == NETWORK_OSTATUS)) {

			// Update contact data

			// This query doesn't seem to work
			// $value = $xpath->query("atom:link[@rel='salmon']", $context)->item(0)->nodeValue;
			// if ($value != "")
			//	$contact["notify"] = $value;

			// This query doesn't seem to work as well - I hate these queries
			// $value = $xpath->query("atom:link[@rel='self' and @type='application/atom+xml']", $context)->item(0)->nodeValue;
			// if ($value != "")
			//	$contact["poll"] = $value;

			$value = $xpath->evaluate('atom:author/atom:uri/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["alias"] = $value;

			$value = $xpath->evaluate('atom:author/poco:displayName/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["name"] = $value;

			$value = $xpath->evaluate('atom:author/poco:preferredUsername/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["nick"] = $value;

			$value = $xpath->evaluate('atom:author/poco:note/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["about"] = html2bbcode($value);

			$value = $xpath->evaluate('atom:author/poco:address/poco:formatted/text()', $context)->item(0)->nodeValue;
			if ($value != "")
				$contact["location"] = $value;

			if (($contact["name"] != $r[0]["name"]) OR ($contact["nick"] != $r[0]["nick"]) OR ($contact["about"] != $r[0]["about"]) OR
				($contact["alias"] != $r[0]["alias"]) OR ($contact["location"] != $r[0]["location"])) {

				logger("Update contact data for contact ".$contact["id"], LOGGER_DEBUG);

				q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `alias` = '%s', `about` = '%s', `location` = '%s', `name-date` = '%s' WHERE `id` = %d",
					dbesc($contact["name"]), dbesc($contact["nick"]), dbesc($contact["alias"]),
					dbesc($contact["about"]), dbesc($contact["location"]),
					dbesc(datetime_convert()), intval($contact["id"]));

				poco_check($contact["url"], $contact["name"], $contact["network"], $author["author-avatar"], $contact["about"], $contact["location"],
							"", "", "", datetime_convert(), 2, $contact["id"], $contact["uid"]);
			}

			if (isset($author["author-avatar"]) AND ($author["author-avatar"] != $r[0]['avatar'])) {
				logger("Update profile picture for contact ".$contact["id"], LOGGER_DEBUG);

				update_contact_avatar($author["author-avatar"], $importer["uid"], $contact["id"]);
			}

			// Ensure that we are having this contact (with uid=0)
			$cid = get_contact($author["author-link"], 0);

			if ($cid) {
				// Update it with the current values
				q("UPDATE `contact` SET `url` = '%s', `name` = '%s', `nick` = '%s', `alias` = '%s',
						`about` = '%s', `location` = '%s',
						`success_update` = '%s', `last-update` = '%s'
					WHERE `id` = %d",
					dbesc($author["author-link"]), dbesc($contact["name"]), dbesc($contact["nick"]),
					dbesc($contact["alias"]), dbesc($contact["about"]), dbesc($contact["location"]),
					dbesc(datetime_convert()), dbesc(datetime_convert()), intval($cid));

				// Update the avatar
				update_contact_avatar($author["author-avatar"], 0, $cid);
			}

			$contact["generation"] = 2;
			$contact["hide"] = false; // OStatus contacts are never hidden
			$contact["photo"] = $author["author-avatar"];
			update_gcontact($contact);
		}

		return($author);
	}

	/**
	 * @brief Fetches author data from a given XML string
	 *
	 * @param string $xml The XML
	 * @param array $importer user record of the importing user
	 *
	 * @return array Array of author related entries for the item
	 */
	public static function salmon_author($xml, $importer) {

		if ($xml == "")
			return;

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

		foreach ($entries AS $entry) {
			// fetch the author
			$author = self::fetchauthor($xpath, $entry, $importer, $contact, true);
			return $author;
		}
	}

	/**
	 * @brief Imports an XML string containing OStatus elements
	 *
	 * @param string $xml The XML
	 * @param array $importer user record of the importing user
	 * @param $contact
	 * @param array $hub Called by reference, returns the fetched hub data
	 */
	public static function import($xml,$importer,&$contact, &$hub) {
		/// @todo this function is too long. It has to be split in many parts

		logger("Import OStatus message", LOGGER_DEBUG);

		if ($xml == "")
			return;

		//$tempfile = tempnam(get_temppath(), "import");
		//file_put_contents($tempfile, $xml);

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
				$author = self::fetchauthor($xpath, $doc->firstChild, $importer, $contact, false);
			else
				$author = self::fetchauthor($xpath, $entry, $importer, $contact, false);

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

			/// @TODO
			/// Delete a message
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

			$categories = $xpath->query('atom:category', $entry);
			if ($categories) {
				foreach ($categories AS $category) {
					foreach($category->attributes AS $attributes)
						if ($attributes->name == "term") {
							$term = $attributes->textContent;
							if(strlen($item["tag"]))
								$item["tag"] .= ',';
							$item["tag"] .= "#[url=".App::get_baseurl()."/search?tag=".$term."]".$term."[/url]";
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
			if (($repeat_of != "") OR ($item["verb"] == ACTIVITY_SHARE)) {
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
						$orig_link =  self::convert_href($orig_uri);

					$orig_body = $xpath->query('activity:object/atom:content/text()', $activityobjects)->item(0)->nodeValue;
					if (!isset($orig_body))
						$orig_body = $xpath->query('atom:content/text()', $activityobjects)->item(0)->nodeValue;

					$orig_created = $xpath->query('atom:published/text()', $activityobjects)->item(0)->nodeValue;
					$orig_edited = $xpath->query('atom:updated/text()', $activityobjects)->item(0)->nodeValue;

					$orig_contact = $contact;
					$orig_author = self::fetchauthor($xpath, $activityobjects, $importer, $orig_contact, false);

					$item["author-name"] = $orig_author["author-name"];
					$item["author-link"] = $orig_author["author-link"];
					$item["author-avatar"] = $orig_author["author-avatar"];
					$item["body"] = add_page_info_to_body(html2bbcode($orig_body));
					$item["created"] = $orig_created;
					$item["edited"] = $orig_edited;

					$item["uri"] = $orig_uri;
					$item["plink"] = $orig_link;

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

				// Only fetch missing stuff if it is a comment or reshare.
				if (in_array($item["verb"], array(ACTIVITY_POST, ACTIVITY_SHARE)) AND
					!dbm::is_result($r) AND ($related != "")) {
					$reply_path = str_replace("/notice/", "/api/statuses/show/", $related).".atom";

					if ($reply_path != $related) {
						logger("Fetching related items for user ".$importer["uid"]." from ".$reply_path, LOGGER_DEBUG);
						$reply_xml = fetch_url($reply_path);

						$reply_contact = $contact;
						self::import($reply_xml,$importer,$reply_contact, $reply_hub);

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

			$item_id = self::completion($conversation, $importer["uid"], $item, $self);

			if (!$item_id) {
				logger("Error storing item", LOGGER_DEBUG);
				continue;
			}

			logger("Item was stored with id ".$item_id, LOGGER_DEBUG);
		}
	}

	/**
	 * @brief Create an url out of an uri
	 *
	 * @param string $href URI in the format "parameter1:parameter1:..."
	 *
	 * @return string URL in the format http(s)://....
	 */
	public static function convert_href($href) {
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

	/**
	 * @brief Checks if there are entries in conversations that aren't present on our side
	 *
	 * @param bool $mentions Fetch conversations where we are mentioned
	 * @param bool $override Override the interval setting
	 */
	public static function check_conversations($mentions = false, $override = false) {
		$last = get_config('system','ostatus_last_poll');

		$poll_interval = intval(get_config('system','ostatus_poll_interval'));
		if (!$poll_interval) {
			$poll_interval = self::OSTATUS_DEFAULT_POLL_INTERVAL;
		}

		// Don't poll if the interval is set negative
		if (($poll_interval < 0) AND !$override) {
			return;
		}

		if (!$mentions) {
			$poll_timeframe = intval(get_config('system','ostatus_poll_timeframe'));
			if (!$poll_timeframe) {
				$poll_timeframe = self::OSTATUS_DEFAULT_POLL_TIMEFRAME;
			}
		} else {
			$poll_timeframe = intval(get_config('system','ostatus_poll_timeframe'));
			if (!$poll_timeframe) {
				$poll_timeframe = self::OSTATUS_DEFAULT_POLL_TIMEFRAME_MENTIONS;
			}
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

		if ($mentions) {
			$conversations = q("SELECT `term`.`oid`, `term`.`url`, `term`.`uid` FROM `term`
						STRAIGHT_JOIN `thread` ON `thread`.`iid` = `term`.`oid` AND `thread`.`uid` = `term`.`uid`
						WHERE `term`.`type` = 7 AND `term`.`term` > '%s' AND `thread`.`mention`
						GROUP BY `term`.`url`, `term`.`uid` ORDER BY `term`.`term` DESC", dbesc($start));
		} else {
			$conversations = q("SELECT `oid`, `url`, `uid` FROM `term`
						WHERE `type` = 7 AND `term` > '%s'
						GROUP BY `url`, `uid` ORDER BY `term` DESC", dbesc($start));
		}

		foreach ($conversations AS $conversation) {
			self::completion($conversation['url'], $conversation['uid']);
		}

		logger('cron_end');

		set_config('system','ostatus_last_poll', time());
	}

	/**
	 * @brief Updates the gcontact table with actor data from the conversation
	 *
	 * @param object $actor The actor object that contains the contact data
	 */
	private function conv_fetch_actor($actor) {

		// We set the generation to "3" since the data here is not as reliable as the data we get on other occasions
		$contact = array("network" => NETWORK_OSTATUS, "generation" => 3);

		if (isset($actor->url))
			$contact["url"] = $actor->url;

		if (isset($actor->displayName))
			$contact["name"] = $actor->displayName;

		if (isset($actor->portablecontacts_net->displayName))
			$contact["name"] = $actor->portablecontacts_net->displayName;

		if (isset($actor->portablecontacts_net->preferredUsername))
			$contact["nick"] = $actor->portablecontacts_net->preferredUsername;

		if (isset($actor->id))
			$contact["alias"] = $actor->id;

		if (isset($actor->summary))
			$contact["about"] = $actor->summary;

		if (isset($actor->portablecontacts_net->note))
			$contact["about"] = $actor->portablecontacts_net->note;

		if (isset($actor->portablecontacts_net->addresses->formatted))
			$contact["location"] = $actor->portablecontacts_net->addresses->formatted;


		if (isset($actor->image->url))
			$contact["photo"] = $actor->image->url;

		if (isset($actor->image->width))
			$avatarwidth = $actor->image->width;

		if (is_array($actor->status_net->avatarLinks))
			foreach ($actor->status_net->avatarLinks AS $avatar) {
				if ($avatarsize < $avatar->width) {
					$contact["photo"] = $avatar->url;
					$avatarsize = $avatar->width;
				}
			}

		$contact["hide"] = false; // OStatus contacts are never hidden
		update_gcontact($contact);
	}

	/**
	 * @brief Fetches the conversation url for a given item link or conversation id
	 *
	 * @param string $self The link to the posting
	 * @param string $conversation_id The conversation id
	 *
	 * @return string The conversation url
	 */
	private function fetch_conversation($self, $conversation_id = "") {

		if ($conversation_id != "") {
			$elements = explode(":", $conversation_id);

			if ((count($elements) <= 2) OR ($elements[0] != "tag"))
				return $conversation_id;
		}

		if ($self == "")
			return "";

		$json = str_replace(".atom", ".json", $self);

		$raw = fetch_url($json);
		if ($raw == "")
			return "";

		$data = json_decode($raw);
		if (!is_object($data))
			return "";

		$conversation_id = $data->statusnet_conversation_id;

		$pos = strpos($self, "/api/statuses/show/");
		$base_url = substr($self, 0, $pos);

		return $base_url."/conversation/".$conversation_id;
	}

	/**
	 * @brief Fetches actor details of a given actor and user id
	 *
	 * @param string $actor The actor url
	 * @param int $uid The user id
	 * @param int $contact_id The default contact-id
	 *
	 * @return array Array with actor details
	 */
	private function get_actor_details($actor, $uid, $contact_id) {

		$details = array();

		$contact = q("SELECT `id`, `rel`, `network` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` != '%s'",
					$uid, normalise_link($actor), NETWORK_STATUSNET);

		if (!$contact)
			$contact = q("SELECT `id`, `rel`, `network` FROM `contact` WHERE `uid` = %d AND `alias` IN ('%s', '%s') AND `network` != '%s'",
					$uid, $actor, normalise_link($actor), NETWORK_STATUSNET);

		if ($contact) {
			logger("Found contact for url ".$actor, LOGGER_DEBUG);
			$details["contact_id"] = $contact[0]["id"];
			$details["network"] = $contact[0]["network"];

			$details["not_following"] = !in_array($contact[0]["rel"], array(CONTACT_IS_SHARING, CONTACT_IS_FRIEND));
		} else {
			logger("No contact found for user ".$uid." and url ".$actor, LOGGER_DEBUG);

			// Adding a global contact
			/// @TODO Use this data for the post
			$details["global_contact_id"] = get_contact($actor, 0);

			logger("Global contact ".$global_contact_id." found for url ".$actor, LOGGER_DEBUG);

			$details["contact_id"] = $contact_id;
			$details["network"] = NETWORK_OSTATUS;

			$details["not_following"] = true;
		}

		return $details;
	}

	/**
	 * @brief Stores an item and completes the thread
	 *
	 * @param string $conversation_url The URI of the conversation
	 * @param integer $uid The user id
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return integer The item id of the posted item array
	 */
	private function completion($conversation_url, $uid, $item = array(), $self = "") {

		/// @todo This function is totally ugly and has to be rewritten totally

		$item_stored = -1;

		$conversation_url = self::fetch_conversation($self, $conversation_url);

		// If the thread shouldn't be completed then store the item and go away
		// Don't do a completion on liked content
		if (((intval(get_config('system','ostatus_poll_interval')) == -2) AND (count($item) > 0)) OR
			($item["verb"] == ACTIVITY_LIKE) OR ($conversation_url == "")) {
			$item_stored = item_store($item, true);
			return($item_stored);
		}

		// Get the parent
		$parents = q("SELECT `item`.`id`, `item`.`parent`, `item`.`uri`, `item`.`contact-id`, `item`.`type`,
				`item`.`verb`, `item`.`visible` FROM `term`
				STRAIGHT_JOIN `item` AS `thritem` ON `thritem`.`parent` = `term`.`oid`
				STRAIGHT_JOIN `item` ON `item`.`parent` = `thritem`.`parent`
				WHERE `term`.`uid` = %d AND `term`.`otype` = %d AND `term`.`type` = %d AND `term`.`url` = '%s'",
				intval($uid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION), dbesc($conversation_url));

/*		2016-10-23: The old query will be kept until we are sure that the query above is a good and fast replacement

		$parents = q("SELECT `id`, `parent`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `id` IN
				(SELECT `parent` FROM `item` WHERE `id` IN
					(SELECT `oid` FROM `term` WHERE `uid` = %d AND `otype` = %d AND `type` = %d AND `url` = '%s'))",
				intval($uid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION), dbesc($conversation_url));
*/
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

		logger('fetching conversation url '.$conv.' (Self: '.$self.') for user '.$uid);

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
				$item_stored = item_store($item, true);

				if ($item_stored) {
					logger("Conversation ".$conversation_url." couldn't be fetched. Item uri ".$item["uri"]." stored: ".$item_stored, LOGGER_DEBUG);
					self::store_conversation($item_id, $conversation_url);
				}

				return($item_stored);
			} else
				return(-3);
		}

		$items = array_reverse($items);

		$r = q("SELECT `nurl` FROM `contact` WHERE `uid` = %d AND `self`", intval($uid));
		$importer = $r[0];

		$new_parent = true;

		foreach ($items as $single_conv) {

			// Update the gcontact table
			self::conv_fetch_actor($single_conv->actor);

			// Test - remove before flight
			//$tempfile = tempnam(get_temppath(), "conversation");
			//file_put_contents($tempfile, json_encode($single_conv));

			$mention = false;

			if (isset($single_conv->object->id))
				$single_conv->id = $single_conv->object->id;

			$plink = self::convert_href($single_conv->id);
			if (isset($single_conv->object->url))
				$plink = self::convert_href($single_conv->object->url);

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

					$new_parent = true;

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
					/// @TODO We have to change the shadow copies as well. This way here is really ugly.
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

			$details = self::get_actor_details($actor, $uid, $parent["contact-id"]);

			// Do we only want to import threads that were started by our contacts?
			if ($details["not_following"] AND $new_parent AND get_config('system','ostatus_full_threads')) {
				logger("Don't import uri ".$first_id." because user ".$uid." doesn't follow the person ".$actor, LOGGER_DEBUG);
				continue;
			}

			$arr = array();
			$arr["network"] = $details["network"];
			$arr["uri"] = $single_conv->id;
			$arr["plink"] = $plink;
			$arr["uid"] = $uid;
			$arr["contact-id"] = $details["contact_id"];
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
					$plink = self::convert_href($single_conv->object->object->url);
				else
					$plink = self::convert_href($single_conv->object->url);

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
			self::store_conversation($newitem, $conversation_url);

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

			if (get_config('system','ostatus_full_threads')) {
				$details = self::get_actor_details($item["owner-link"], $uid, $item["contact-id"]);
				if ($details["not_following"]) {
					logger("Don't import uri ".$item["uri"]." because user ".$uid." doesn't follow the person ".$item["owner-link"], LOGGER_DEBUG);
					return false;
				}
			}

			$item_stored = item_store($item, true);
			if ($item_stored) {
				logger("Uri ".$item["uri"]." wasn't found in conversation ".$conversation_url, LOGGER_DEBUG);
				self::store_conversation($item_stored, $conversation_url);
			}
		}

		return($item_stored);
	}

	/**
	 * @brief Stores conversation data into the database
	 *
	 * @param integer $itemid The id of the item
	 * @param string $conversation_url The uri of the conversation
	 */
	private function store_conversation($itemid, $conversation_url) {

		$conversation_url = self::convert_href($conversation_url);

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

	/**
	 * @brief Checks if the current post is a reshare
	 *
	 * @param array $item The item array of thw post
	 *
	 * @return string The guid if the post is a reshare
	 */
	private function get_reshared_guid($item) {
		$body = trim($item["body"]);

		// Skip if it isn't a pure repeated messages
		// Does it start with a share?
		if (strpos($body, "[share") > 0)
			return("");

		// Does it end with a share?
		if (strlen($body) > (strrpos($body, "[/share]") + 8))
			return("");

		$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$1",$body);
		// Skip if there is no shared message in there
		if ($body == $attributes)
			return(false);

		$guid = "";
		preg_match("/guid='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "")
			$guid = $matches[1];

		preg_match('/guid="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "")
			$guid = $matches[1];

		return $guid;
	}

	/**
	 * @brief Cleans the body of a post if it contains picture links
	 *
	 * @param string $body The body
	 *
	 * @return string The cleaned body
	 */
	private function format_picture_post($body) {
		$siteinfo = get_attached_data($body);

		if (($siteinfo["type"] == "photo")) {
			if (isset($siteinfo["preview"]))
				$preview = $siteinfo["preview"];
			else
				$preview = $siteinfo["image"];

			// Is it a remote picture? Then make a smaller preview here
			$preview = proxy_url($preview, false, PROXY_SIZE_SMALL);

			// Is it a local picture? Then make it smaller here
			$preview = str_replace(array("-0.jpg", "-0.png"), array("-2.jpg", "-2.png"), $preview);
			$preview = str_replace(array("-1.jpg", "-1.png"), array("-2.jpg", "-2.png"), $preview);

			if (isset($siteinfo["url"]))
				$url = $siteinfo["url"];
			else
				$url = $siteinfo["image"];

			$body = trim($siteinfo["text"])." [url]".$url."[/url]\n[img]".$preview."[/img]";
		}

		return $body;
	}

	/**
	 * @brief Adds the header elements to the XML document
	 *
	 * @param object $doc XML document
	 * @param array $owner Contact data of the poster
	 *
	 * @return object header root element
	 */
	private function add_header($doc, $owner) {

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

		$attributes = array("uri" => "https://friendi.ca", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION);
		xml::add_element($doc, $root, "generator", FRIENDICA_PLATFORM, $attributes);
		xml::add_element($doc, $root, "id", App::get_baseurl()."/profile/".$owner["nick"]);
		xml::add_element($doc, $root, "title", sprintf("%s timeline", $owner["name"]));
		xml::add_element($doc, $root, "subtitle", sprintf("Updates from %s on %s", $owner["name"], $a->config["sitename"]));
		xml::add_element($doc, $root, "logo", $owner["photo"]);
		xml::add_element($doc, $root, "updated", datetime_convert("UTC", "UTC", "now", ATOM_TIME));

		$author = self::add_author($doc, $owner);
		$root->appendChild($author);

		$attributes = array("href" => $owner["url"], "rel" => "alternate", "type" => "text/html");
		xml::add_element($doc, $root, "link", "", $attributes);

		/// @TODO We have to find out what this is
		/// $attributes = array("href" => App::get_baseurl()."/sup",
		///		"rel" => "http://api.friendfeed.com/2008/03#sup",
		///		"type" => "application/json");
		/// xml::add_element($doc, $root, "link", "", $attributes);

		self::hublinks($doc, $root);

		$attributes = array("href" => App::get_baseurl()."/salmon/".$owner["nick"], "rel" => "salmon");
		xml::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("href" => App::get_baseurl()."/salmon/".$owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-replies");
		xml::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("href" => App::get_baseurl()."/salmon/".$owner["nick"], "rel" => "http://salmon-protocol.org/ns/salmon-mention");
		xml::add_element($doc, $root, "link", "", $attributes);

		$attributes = array("href" => App::get_baseurl()."/api/statuses/user_timeline/".$owner["nick"].".atom",
				"rel" => "self", "type" => "application/atom+xml");
		xml::add_element($doc, $root, "link", "", $attributes);

		return $root;
	}

	/**
	 * @brief Add the link to the push hubs to the XML document
	 *
	 * @param object $doc XML document
	 * @param object $root XML root element where the hub links are added
	 */
	public static function hublinks($doc, $root) {
		$hub = get_config('system','huburl');

		$hubxml = '';
		if(strlen($hub)) {
			$hubs = explode(',', $hub);
			if(count($hubs)) {
				foreach($hubs as $h) {
					$h = trim($h);
					if(! strlen($h))
						continue;
					if ($h === '[internal]')
						$h = App::get_baseurl() . '/pubsubhubbub';
					xml::add_element($doc, $root, "link", "", array("href" => $h, "rel" => "hub"));
				}
			}
		}
	}

	/**
	 * @brief Adds attachement data to the XML document
	 *
	 * @param object $doc XML document
	 * @param object $root XML root element where the hub links are added
	 * @param array $item Data of the item that is to be posted
	 */
	private function get_attachment($doc, $root, $item) {
		$o = "";
		$siteinfo = get_attached_data($item["body"]);

		switch($siteinfo["type"]) {
			case 'link':
				$attributes = array("rel" => "enclosure",
						"href" => $siteinfo["url"],
						"type" => "text/html; charset=UTF-8",
						"length" => "",
						"title" => $siteinfo["title"]);
				xml::add_element($doc, $root, "link", "", $attributes);
				break;
			case 'photo':
				$imgdata = get_photo_info($siteinfo["image"]);
				$attributes = array("rel" => "enclosure",
						"href" => $siteinfo["image"],
						"type" => $imgdata["mime"],
						"length" => intval($imgdata["size"]));
				xml::add_element($doc, $root, "link", "", $attributes);
				break;
			case 'video':
				$attributes = array("rel" => "enclosure",
						"href" => $siteinfo["url"],
						"type" => "text/html; charset=UTF-8",
						"length" => "",
						"title" => $siteinfo["title"]);
				xml::add_element($doc, $root, "link", "", $attributes);
				break;
			default:
				break;
		}

		if (($siteinfo["type"] != "photo") AND isset($siteinfo["image"])) {
			$photodata = get_photo_info($siteinfo["image"]);

			$attributes = array("rel" => "preview", "href" => $siteinfo["image"], "media:width" => $photodata[0], "media:height" => $photodata[1]);
			xml::add_element($doc, $root, "link", "", $attributes);
		}


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
	 * @brief Adds the author element to the XML document
	 *
	 * @param object $doc XML document
	 * @param array $owner Contact data of the poster
	 *
	 * @return object author element
	 */
	private function add_author($doc, $owner) {

		$r = q("SELECT `homepage` FROM `profile` WHERE `uid` = %d AND `is-default` LIMIT 1", intval($owner["uid"]));
		if ($r)
			$profile = $r[0];

		$author = $doc->createElement("author");
		xml::add_element($doc, $author, "activity:object-type", ACTIVITY_OBJ_PERSON);
		xml::add_element($doc, $author, "uri", $owner["url"]);
		xml::add_element($doc, $author, "name", $owner["name"]);
		xml::add_element($doc, $author, "summary", bbcode($owner["about"], false, false, 7));

		$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $owner["url"]);
		xml::add_element($doc, $author, "link", "", $attributes);

		$attributes = array(
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 175,
				"media:height" => 175,
				"href" => $owner["photo"]);
		xml::add_element($doc, $author, "link", "", $attributes);

		if (isset($owner["thumb"])) {
			$attributes = array(
					"rel" => "avatar",
					"type" => "image/jpeg", // To-Do?
					"media:width" => 80,
					"media:height" => 80,
					"href" => $owner["thumb"]);
			xml::add_element($doc, $author, "link", "", $attributes);
		}

		xml::add_element($doc, $author, "poco:preferredUsername", $owner["nick"]);
		xml::add_element($doc, $author, "poco:displayName", $owner["name"]);
		xml::add_element($doc, $author, "poco:note", bbcode($owner["about"], false, false, 7));

		if (trim($owner["location"]) != "") {
			$element = $doc->createElement("poco:address");
			xml::add_element($doc, $element, "poco:formatted", $owner["location"]);
			$author->appendChild($element);
		}

		if (trim($profile["homepage"]) != "") {
			$urls = $doc->createElement("poco:urls");
			xml::add_element($doc, $urls, "poco:type", "homepage");
			xml::add_element($doc, $urls, "poco:value", $profile["homepage"]);
			xml::add_element($doc, $urls, "poco:primary", "true");
			$author->appendChild($urls);
		}

		if (count($profile)) {
			xml::add_element($doc, $author, "followers", "", array("url" => App::get_baseurl()."/viewcontacts/".$owner["nick"]));
			xml::add_element($doc, $author, "statusnet:profile_info", "", array("local_id" => $owner["uid"]));
		}

		return $author;
	}

	/**
	 * @TODO Picture attachments should look like this:
	 *	<a href="https://status.pirati.ca/attachment/572819" title="https://status.pirati.ca/file/heluecht-20151202T222602-rd3u49p.gif"
	 *	class="attachment thumbnail" id="attachment-572819" rel="nofollow external">https://status.pirati.ca/attachment/572819</a>
	 *
	*/

	/**
	 * @brief Returns the given activity if present - otherwise returns the "post" activity
	 *
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return string activity
	 */
	function construct_verb($item) {
		if ($item['verb'])
			return $item['verb'];
		return ACTIVITY_POST;
	}

	/**
	 * @brief Returns the given object type if present - otherwise returns the "note" object type
	 *
	 * @param array $item Data of the item that is to be posted
	 *
	 * @return string Object type
	 */
	function construct_objecttype($item) {
		if (in_array($item['object-type'], array(ACTIVITY_OBJ_NOTE, ACTIVITY_OBJ_COMMENT)))
			return $item['object-type'];
		return ACTIVITY_OBJ_NOTE;
	}

	/**
	 * @brief Adds an entry element to the XML document
	 *
	 * @param object $doc XML document
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 * @param bool $toplevel
	 *
	 * @return object Entry element
	 */
	private function entry($doc, $item, $owner, $toplevel = false) {
		$repeated_guid = self::get_reshared_guid($item);
		if ($repeated_guid != "")
			$xml = self::reshare_entry($doc, $item, $owner, $repeated_guid, $toplevel);

		if ($xml)
			return $xml;

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
	 * @param object $doc XML document
	 * @param array $contact Array of the contact that is added
	 *
	 * @return object Source element
	 */
	private function source_entry($doc, $contact) {
		$source = $doc->createElement("source");
		xml::add_element($doc, $source, "id", $contact["poll"]);
		xml::add_element($doc, $source, "title", $contact["name"]);
		xml::add_element($doc, $source, "link", "", array("rel" => "alternate",
								"type" => "text/html",
								"href" => $contact["alias"]));
		xml::add_element($doc, $source, "link", "", array("rel" => "self",
								"type" => "application/atom+xml",
								"href" => $contact["poll"]));
		xml::add_element($doc, $source, "icon", $contact["photo"]);
		xml::add_element($doc, $source, "updated", datetime_convert("UTC","UTC",$contact["success_update"]."+00:00",ATOM_TIME));

		return $source;
	}

	/**
	 * @brief Fetches contact data from the contact or the gcontact table
	 *
	 * @param string $url URL of the contact
	 * @param array $owner Contact data of the poster
	 *
	 * @return array Contact array
	 */
	private function contact_entry($url, $owner) {

		$r = q("SELECT * FROM `contact` WHERE `nurl` = '%s' AND `uid` IN (0, %d) ORDER BY `uid` DESC LIMIT 1",
			dbesc(normalise_link($url)), intval($owner["uid"]));
		if ($r) {
			$contact = $r[0];
			$contact["uid"] = -1;
		}

		if (!$r) {
			$r = q("SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
				dbesc(normalise_link($url)));
			if ($r) {
				$contact = $r[0];
				$contact["uid"] = -1;
				$contact["success_update"] = $contact["updated"];
			}
		}

		if (!$r)
			$contact = owner;

		if (!isset($contact["poll"])) {
			$data = probe_url($url);
			$contact["poll"] = $data["poll"];

			if (!$contact["alias"])
				$contact["alias"] = $data["alias"];
		}

		if (!isset($contact["alias"]))
			$contact["alias"] = $contact["url"];

		return $contact;
	}

	/**
	 * @brief Adds an entry element with reshared content
	 *
	 * @param object $doc XML document
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 * @param $repeated_guid
	 * @param bool $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private function reshare_entry($doc, $item, $owner, $repeated_guid, $toplevel) {

		if (($item["id"] != $item["parent"]) AND (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entry_header($doc, $entry, $owner, $toplevel);

		$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' AND NOT `private` AND `network` IN ('%s', '%s', '%s') LIMIT 1",
			intval($owner["uid"]), dbesc($repeated_guid),
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS));
		if ($r)
			$repeated_item = $r[0];
		else
			return false;

		$contact = self::contact_entry($repeated_item['author-link'], $owner);

		$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

		$title = $owner["nick"]." repeated a notice by ".$contact["nick"];

		self::entry_content($doc, $entry, $item, $owner, $title, ACTIVITY_SHARE, false);

		$as_object = $doc->createElement("activity:object");

		xml::add_element($doc, $as_object, "activity:object-type", NAMESPACE_ACTIVITY_SCHEMA."activity");

		self::entry_content($doc, $as_object, $repeated_item, $owner, "", "", false);

		$author = self::add_author($doc, $contact);
                $as_object->appendChild($author);

		$as_object2 = $doc->createElement("activity:object");

		xml::add_element($doc, $as_object2, "activity:object-type", self::construct_objecttype($repeated_item));

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
	 * @param object $doc XML document
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 * @param bool $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element with "like"
	 */
	private function like_entry($doc, $item, $owner, $toplevel) {

		if (($item["id"] != $item["parent"]) AND (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entry_header($doc, $entry, $owner, $toplevel);

		$verb = NAMESPACE_ACTIVITY_SCHEMA."favorite";
		self::entry_content($doc, $entry, $item, $owner, "Favorite", $verb, false);

		$as_object = $doc->createElement("activity:object");

		$parent = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d",
			dbesc($item["thr-parent"]), intval($item["uid"]));
		$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

		xml::add_element($doc, $as_object, "activity:object-type", self::construct_objecttype($parent[0]));

		self::entry_content($doc, $as_object, $parent[0], $owner, "New entry");

		$entry->appendChild($as_object);

		self::entry_footer($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * @brief Adds the person object element to the XML document
	 *
	 * @param object $doc XML document
	 * @param array $owner Contact data of the poster
	 * @param array $contact Contact data of the target
	 *
	 * @return object author element
	 */
	private function add_person_object($doc, $owner, $contact) {

		$object = $doc->createElement("activity:object");
		xml::add_element($doc, $object, "activity:object-type", ACTIVITY_OBJ_PERSON);

		if ($contact['network'] == NETWORK_PHANTOM) {
			xml::add_element($doc, $object, "id", $contact['url']);
			return $object;
		}

		xml::add_element($doc, $object, "id", $contact["alias"]);
		xml::add_element($doc, $object, "title", $contact["nick"]);

		$attributes = array("rel" => "alternate", "type" => "text/html", "href" => $contact["url"]);
		xml::add_element($doc, $object, "link", "", $attributes);

		$attributes = array(
				"rel" => "avatar",
				"type" => "image/jpeg", // To-Do?
				"media:width" => 175,
				"media:height" => 175,
				"href" => $contact["photo"]);
		xml::add_element($doc, $object, "link", "", $attributes);

		xml::add_element($doc, $object, "poco:preferredUsername", $contact["nick"]);
		xml::add_element($doc, $object, "poco:displayName", $contact["name"]);

		if (trim($contact["location"]) != "") {
			$element = $doc->createElement("poco:address");
			xml::add_element($doc, $element, "poco:formatted", $contact["location"]);
			$object->appendChild($element);
		}

		return $object;
	}

	/**
	 * @brief Adds a follow/unfollow entry element
	 *
	 * @param object $doc XML document
	 * @param array $item Data of the follow/unfollow message
	 * @param array $owner Contact data of the poster
	 * @param bool $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private function follow_entry($doc, $item, $owner, $toplevel) {

		$item["id"] = $item["parent"] = 0;
		$item["created"] = $item["edited"] = date("c");
		$item["private"] = true;

		$contact = Probe::uri($item['follow']);

		if ($contact['alias'] == '') {
			$contact['alias'] = $contact["url"];
		} else {
			$item['follow'] = $contact['alias'];
		}

		$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s'",
			intval($owner['uid']), dbesc(normalise_link($contact["url"])));

		if (dbm::is_result($r)) {
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

		$item["uri"] = $item['parent-uri'] = $item['thr-parent'] =
				'tag:'.get_app()->get_hostname().
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
	 * @param object $doc XML document
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 * @param bool $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return object Entry element
	 */
	private function note_entry($doc, $item, $owner, $toplevel) {

		if (($item["id"] != $item["parent"]) AND (normalise_link($item["author-link"]) != normalise_link($owner["url"]))) {
			logger("OStatus entry is from author ".$owner["url"]." - not from ".$item["author-link"].". Quitting.", LOGGER_DEBUG);
		}

		$title = self::entry_header($doc, $entry, $owner, $toplevel);

		xml::add_element($doc, $entry, "activity:object-type", ACTIVITY_OBJ_NOTE);

		self::entry_content($doc, $entry, $item, $owner, $title);

		self::entry_footer($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * @brief Adds a header element to the XML document
	 *
	 * @param object $doc XML document
	 * @param object $entry The entry element where the elements are added
	 * @param array $owner Contact data of the poster
	 * @param bool $toplevel Is it for en entry element (false) or a feed entry (true)?
	 *
	 * @return string The title for the element
	 */
	private function entry_header($doc, &$entry, $owner, $toplevel) {
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

			$author = self::add_author($doc, $owner);
			$entry->appendChild($author);

			$title = sprintf("New comment by %s", $owner["nick"]);
		}
		return $title;
	}

	/**
	 * @brief Adds elements to the XML document
	 *
	 * @param object $doc XML document
	 * @param object $entry Entry element where the content is added
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 * @param string $title Title for the post
	 * @param string $verb The activity verb
	 * @param bool $complete Add the "status_net" element?
	 */
	private function entry_content($doc, $entry, $item, $owner, $title, $verb = "", $complete = true) {

		if ($verb == "")
			$verb = self::construct_verb($item);

		xml::add_element($doc, $entry, "id", $item["uri"]);
		xml::add_element($doc, $entry, "title", $title);

		$body = self::format_picture_post($item['body']);

		if ($item['title'] != "")
			$body = "[b]".$item['title']."[/b]\n\n".$body;

		$body = bbcode($body, false, false, 7);

		xml::add_element($doc, $entry, "content", $body, array("type" => "html"));

		xml::add_element($doc, $entry, "link", "", array("rel" => "alternate", "type" => "text/html",
								"href" => App::get_baseurl()."/display/".$item["guid"]));

		if ($complete AND ($item["id"] > 0))
			xml::add_element($doc, $entry, "status_net", "", array("notice_id" => $item["id"]));

		xml::add_element($doc, $entry, "activity:verb", $verb);

		xml::add_element($doc, $entry, "published", datetime_convert("UTC","UTC",$item["created"]."+00:00",ATOM_TIME));
		xml::add_element($doc, $entry, "updated", datetime_convert("UTC","UTC",$item["edited"]."+00:00",ATOM_TIME));
	}

	/**
	 * @brief Adds the elements at the foot of an entry to the XML document
	 *
	 * @param object $doc XML document
	 * @param object $entry The entry element where the elements are added
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 * @param $complete
	 */
	private function entry_footer($doc, $entry, $item, $owner, $complete = true) {

		$mentioned = array();

		if (($item['parent'] != $item['id']) || ($item['parent-uri'] !== $item['uri']) || (($item['thr-parent'] !== '') && ($item['thr-parent'] !== $item['uri']))) {
			$parent = q("SELECT `guid`, `author-link`, `owner-link` FROM `item` WHERE `id` = %d", intval($item["parent"]));
			$parent_item = (($item['thr-parent']) ? $item['thr-parent'] : $item['parent-uri']);

			$attributes = array(
					"ref" => $parent_item,
					"type" => "text/html",
					"href" => App::get_baseurl()."/display/".$parent[0]["guid"]);
			xml::add_element($doc, $entry, "thr:in-reply-to", "", $attributes);

			$attributes = array(
					"rel" => "related",
					"href" => App::get_baseurl()."/display/".$parent[0]["guid"]);
			xml::add_element($doc, $entry, "link", "", $attributes);

			$mentioned[$parent[0]["author-link"]] = $parent[0]["author-link"];
			$mentioned[$parent[0]["owner-link"]] = $parent[0]["owner-link"];

			$thrparent = q("SELECT `guid`, `author-link`, `owner-link` FROM `item` WHERE `uid` = %d AND `uri` = '%s'",
					intval($owner["uid"]),
					dbesc($parent_item));
			if ($thrparent) {
				$mentioned[$thrparent[0]["author-link"]] = $thrparent[0]["author-link"];
				$mentioned[$thrparent[0]["owner-link"]] = $thrparent[0]["owner-link"];
			}
		}

		if (intval($item["parent"]) > 0) {
			$conversation = App::get_baseurl()."/display/".$owner["nick"]."/".$item["parent"];
			xml::add_element($doc, $entry, "link", "", array("rel" => "ostatus:conversation", "href" => $conversation));
			xml::add_element($doc, $entry, "ostatus:conversation", $conversation);
		}

		$tags = item_getfeedtags($item);

		if(count($tags))
			foreach($tags as $t)
				if ($t[0] == "@")
					$mentioned[$t[1]] = $t[1];

		// Make sure that mentions are accepted (GNU Social has problems with mixing HTTP and HTTPS)
		$newmentions = array();
		foreach ($mentioned AS $mention) {
			$newmentions[str_replace("http://", "https://", $mention)] = str_replace("http://", "https://", $mention);
			$newmentions[str_replace("https://", "http://", $mention)] = str_replace("https://", "http://", $mention);
		}
		$mentioned = $newmentions;

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

		if (!$item["private"]) {
			xml::add_element($doc, $entry, "link", "", array("rel" => "ostatus:attention",
									"href" => "http://activityschema.org/collection/public"));
			xml::add_element($doc, $entry, "link", "", array("rel" => "mentioned",
									"ostatus:object-type" => "http://activitystrea.ms/schema/1.0/collection",
									"href" => "http://activityschema.org/collection/public"));
		}

		if(count($tags))
			foreach($tags as $t)
				if ($t[0] != "@")
					xml::add_element($doc, $entry, "category", "", array("term" => $t[2]));

		self::get_attachment($doc, $entry, $item);

		if ($complete AND ($item["id"] > 0)) {
			$app = $item["app"];
			if ($app == "")
				$app = "web";

			$attributes = array("local_id" => $item["id"], "source" => $app);

			if (isset($parent["id"]))
				$attributes["repeat_of"] = $parent["id"];

			if ($item["coord"] != "")
				xml::add_element($doc, $entry, "georss:point", $item["coord"]);

			xml::add_element($doc, $entry, "statusnet:notice_info", "", $attributes);
		}
	}

	/**
	 * @brief Creates the XML feed for a given nickname
	 *
	 * @param app $a The application class
	 * @param string $owner_nick Nickname of the feed owner
	 * @param string $last_update Date of the last update
	 *
	 * @return string XML feed
	 */
	public static function feed(App $a, $owner_nick, $last_update) {

		$r = q("SELECT `contact`.*, `user`.`nickname`, `user`.`timezone`, `user`.`page-flags`
				FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`self` AND `user`.`nickname` = '%s' LIMIT 1",
				dbesc($owner_nick));
		if (!$r)
			return;

		$owner = $r[0];

		if(!strlen($last_update))
			$last_update = 'now -30 days';

		$check_date = datetime_convert('UTC','UTC',$last_update,'Y-m-d H:i:s');
		$authorid = get_contact($owner["url"], 0);

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id` FROM `item` USE INDEX (`uid_contactid_created`)
				STRAIGHT_JOIN `thread` ON `thread`.`iid` = `item`.`parent`
				WHERE `item`.`uid` = %d AND `item`.`contact-id` = %d AND
					`item`.`author-id` = %d AND `item`.`created` > '%s' AND
					NOT `item`.`deleted` AND NOT `item`.`private` AND
					`thread`.`network` IN ('%s', '%s')
				ORDER BY `item`.`created` DESC LIMIT 300",
				intval($owner["uid"]), intval($owner["id"]),
				intval($authorid), dbesc($check_date),
				dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN));

/*		2016-10-23: The old query will be kept until we are sure that the query above is a good and fast replacement

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id` FROM `item`
				STRAIGHT_JOIN `thread` ON `thread`.`iid` = `item`.`parent`
				LEFT JOIN `item` AS `thritem` ON `thritem`.`uri`=`item`.`thr-parent` AND `thritem`.`uid`=`item`.`uid`
				WHERE `item`.`uid` = %d AND `item`.`received` > '%s' AND NOT `item`.`private` AND NOT `item`.`deleted`
					AND `item`.`allow_cid` = '' AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
					AND ((`item`.`wall` AND (`item`.`parent` = `item`.`id`))
						OR (`item`.`network` = '%s' AND ((`thread`.`network` IN ('%s', '%s')) OR (`thritem`.`network` IN ('%s', '%s')))) AND `thread`.`mention`)
					AND ((`item`.`owner-link` IN ('%s', '%s') AND (`item`.`parent` = `item`.`id`))
						OR (`item`.`author-link` IN ('%s', '%s')))
				ORDER BY `item`.`id` DESC
				LIMIT 0, 300",
				intval($owner["uid"]), dbesc($check_date), dbesc(NETWORK_DFRN),
				//dbesc(NETWORK_OSTATUS), dbesc(NETWORK_OSTATUS),
				//dbesc(NETWORK_OSTATUS), dbesc(NETWORK_OSTATUS),
				dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN),
				dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DFRN),
				dbesc($owner["nurl"]), dbesc(str_replace("http://", "https://", $owner["nurl"])),
				dbesc($owner["nurl"]), dbesc(str_replace("http://", "https://", $owner["nurl"]))
			);
*/
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::add_header($doc, $owner);

		foreach ($items AS $item) {
			$entry = self::entry($doc, $item, $owner);
			$root->appendChild($entry);
		}

		return(trim($doc->saveXML()));
	}

	/**
	 * @brief Creates the XML for a salmon message
	 *
	 * @param array $item Data of the item that is to be posted
	 * @param array $owner Contact data of the poster
	 *
	 * @return string XML for the salmon
	 */
	public static function salmon($item,$owner) {

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$entry = self::entry($doc, $item, $owner, true);

		$doc->appendChild($entry);

		return(trim($doc->saveXML()));
	}
}
?>
