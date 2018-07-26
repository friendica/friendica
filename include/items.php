<?php
/**
 * @file include/items.php
 */

use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Feed;
use Friendica\Protocol\OStatus;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Temporal;

require_once 'include/text.php';
require_once 'mod/share.php';
require_once 'include/enotify.php';

function add_page_info_data($data, $no_photos = false) {
	Addon::callHooks('page_info_data', $data);

	// It maybe is a rich content, but if it does have everything that a link has,
	// then treat it that way
	if (($data["type"] == "rich") && is_string($data["title"]) &&
		is_string($data["text"]) && !empty($data["images"])) {
		$data["type"] = "link";
	}

	$data["title"] = defaults($data, "title", "");

	if ((($data["type"] != "link") && ($data["type"] != "video") && ($data["type"] != "photo")) || ($data["title"] == $data["url"])) {
		return "";
	}

	if ($no_photos && ($data["type"] == "photo")) {
		return "";
	}

	// Escape some bad characters
	$data["url"] = str_replace(["[", "]"], ["&#91;", "&#93;"], htmlentities($data["url"], ENT_QUOTES, 'UTF-8', false));
	$data["title"] = str_replace(["[", "]"], ["&#91;", "&#93;"], htmlentities($data["title"], ENT_QUOTES, 'UTF-8', false));

	$text = "[attachment type='".$data["type"]."'";

	if (empty($data["text"])) {
		$data["text"] = $data["title"];
	}

	if (empty($data["text"])) {
		$data["text"] = $data["url"];
	}

	if (!empty($data["url"])) {
		$text .= " url='".$data["url"]."'";
	}

	if (!empty($data["title"])) {
		$text .= " title='".$data["title"]."'";
	}

	if (!empty($data["images"])) {
		$preview = str_replace(["[", "]"], ["&#91;", "&#93;"], htmlentities($data["images"][0]["src"], ENT_QUOTES, 'UTF-8', false));
		// if the preview picture is larger than 500 pixels then show it in a larger mode
		// But only, if the picture isn't higher than large (To prevent huge posts)
		if (!Config::get('system', 'always_show_preview') && ($data["images"][0]["width"] >= 500)
			&& ($data["images"][0]["width"] >= $data["images"][0]["height"])) {
			$text .= " image='".$preview."'";
		} else {
			$text .= " preview='".$preview."'";
		}
	}

	$text .= "]".$data["text"]."[/attachment]";

	$hashtags = "";
	if (isset($data["keywords"]) && count($data["keywords"])) {
		$hashtags = "\n";
		foreach ($data["keywords"] AS $keyword) {
			/// @TODO make a positive list of allowed characters
			$hashtag = str_replace([" ", "+", "/", ".", "#", "'", "’", "`", "(", ")", "„", "“"],
						["", "", "", "", "", "", "", "", "", "", "", ""], $keyword);
			$hashtags .= "#[url=" . System::baseUrl() . "/search?tag=" . rawurlencode($hashtag) . "]" . $hashtag . "[/url] ";
		}
	}

	return "\n".$text.$hashtags;
}

function query_page_info($url, $photo = "", $keywords = false, $keyword_blacklist = "") {

	$data = ParseUrl::getSiteinfoCached($url, true);

	if ($photo != "") {
		$data["images"][0]["src"] = $photo;
	}

	logger('fetch page info for ' . $url . ' ' . print_r($data, true), LOGGER_DEBUG);

	if (!$keywords && isset($data["keywords"])) {
		unset($data["keywords"]);
	}

	if (($keyword_blacklist != "") && isset($data["keywords"])) {
		$list = explode(", ", $keyword_blacklist);
		foreach ($list AS $keyword) {
			$keyword = trim($keyword);
			$index = array_search($keyword, $data["keywords"]);
			if ($index !== false) {
				unset($data["keywords"][$index]);
			}
		}
	}

	return $data;
}

function add_page_keywords($url, $photo = "", $keywords = false, $keyword_blacklist = "") {
	$data = query_page_info($url, $photo, $keywords, $keyword_blacklist);

	$tags = "";
	if (isset($data["keywords"]) && count($data["keywords"])) {
		foreach ($data["keywords"] AS $keyword) {
			$hashtag = str_replace([" ", "+", "/", ".", "#", "'"],
				["", "", "", "", "", ""], $keyword);

			if ($tags != "") {
				$tags .= ", ";
			}

			$tags .= "#[url=" . System::baseUrl() . "/search?tag=" . rawurlencode($hashtag) . "]" . $hashtag . "[/url]";
		}
	}

	return $tags;
}

function add_page_info($url, $no_photos = false, $photo = "", $keywords = false, $keyword_blacklist = "") {
	$data = query_page_info($url, $photo, $keywords, $keyword_blacklist);

	$text = add_page_info_data($data, $no_photos);

	return $text;
}

function add_page_info_to_body($body, $texturl = false, $no_photos = false) {

	logger('add_page_info_to_body: fetch page info for body ' . $body, LOGGER_DEBUG);

	$URLSearchString = "^\[\]";

	// Fix for Mastodon where the mentions are in a different format
	$body = preg_replace("/\[url\=([$URLSearchString]*)\]([#!@])(.*?)\[\/url\]/ism",
		'$2[url=$1]$3[/url]', $body);

	// Adding these spaces is a quick hack due to my problems with regular expressions :)
	preg_match("/[^!#@]\[url\]([$URLSearchString]*)\[\/url\]/ism", " " . $body, $matches);

	if (!$matches) {
		preg_match("/[^!#@]\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", " " . $body, $matches);
	}

	// Convert urls without bbcode elements
	if (!$matches && $texturl) {
		preg_match("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", " ".$body, $matches);

		// Yeah, a hack. I really hate regular expressions :)
		if ($matches) {
			$matches[1] = $matches[2];
		}
	}

	if ($matches) {
		$footer = add_page_info($matches[1], $no_photos);
	}

	// Remove the link from the body if the link is attached at the end of the post
	if (isset($footer) && (trim($footer) != "") && (strpos($footer, $matches[1]))) {
		$removedlink = trim(str_replace($matches[1], "", $body));
		if (($removedlink == "") || strstr($body, $removedlink)) {
			$body = $removedlink;
		}

		$url = str_replace(['/', '.'], ['\/', '\.'], $matches[1]);
		$removedlink = preg_replace("/\[url\=" . $url . "\](.*?)\[\/url\]/ism", '', $body);
		if (($removedlink == "") || strstr($body, $removedlink)) {
			$body = $removedlink;
		}
	}

	// Add the page information to the bottom
	if (isset($footer) && (trim($footer) != "")) {
		$body .= $footer;
	}

	return $body;
}

/**
 *
 * consume_feed - process atom feed and update anything/everything we might need to update
 *
 * $xml = the (atom) feed to consume - RSS isn't as fully supported but may work for simple feeds.
 *
 * $importer = the contact_record (joined to user_record) of the local user who owns this relationship.
 *             It is this person's stuff that is going to be updated.
 * $contact =  the person who is sending us stuff. If not set, we MAY be processing a "follow" activity
 *             from an external network and MAY create an appropriate contact record. Otherwise, we MUST
 *             have a contact record.
 * $hub = should we find a hub declation in the feed, pass it back to our calling process, who might (or
 *        might not) try and subscribe to it.
 * $datedir sorts in reverse order
 * $pass - by default ($pass = 0) we cannot guarantee that a parent item has been
 *      imported prior to its children being seen in the stream unless we are certain
 *      of how the feed is arranged/ordered.
 * With $pass = 1, we only pull parent items out of the stream.
 * With $pass = 2, we only pull children (comments/likes).
 *
 * So running this twice, first with pass 1 and then with pass 2 will do the right
 * thing regardless of feed ordering. This won't be adequate in a fully-threaded
 * model where comments can have sub-threads. That would require some massive sorting
 * to get all the feed items into a mostly linear ordering, and might still require
 * recursion.
 *
 * @TODO find proper type-hints
 */
function consume_feed($xml, $importer, $contact, &$hub, $datedir = 0, $pass = 0) {
	if ($contact['network'] === NETWORK_OSTATUS) {
		if ($pass < 2) {
			// Test - remove before flight
			//$tempfile = tempnam(get_temppath(), "ostatus2");
			//file_put_contents($tempfile, $xml);
			logger("Consume OStatus messages ", LOGGER_DEBUG);
			OStatus::import($xml, $importer, $contact, $hub);
		}
		return;
	}

	if ($contact['network'] === NETWORK_FEED) {
		if ($pass < 2) {
			logger("Consume feeds", LOGGER_DEBUG);
			Feed::import($xml, $importer, $contact, $hub);
		}
		return;
	}

	if ($contact['network'] === NETWORK_DFRN) {
		logger("Consume DFRN messages", LOGGER_DEBUG);

		$r = q("SELECT `contact`.*, `contact`.`uid` AS `importer_uid`,
					`contact`.`pubkey` AS `cpubkey`,
					`contact`.`prvkey` AS `cprvkey`,
					`contact`.`thumb` AS `thumb`,
					`contact`.`url` as `url`,
					`contact`.`name` as `senderName`,
					`user`.*
			FROM `contact`
			LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`id` = %d AND `user`.`uid` = %d",
			DBA::escape($contact["id"]), DBA::escape($importer["uid"])
		);
		if (DBA::isResult($r)) {
			logger("Now import the DFRN feed");
			DFRN::import($xml, $r[0], true);
			return;
		}
	}
}

function subscribe_to_hub($url, $importer, $contact, $hubmode = 'subscribe') {

	$a = get_app();
	$r = null;

	if (is_array($importer)) {
		$r = q("SELECT `nickname` FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer['uid'])
		);
	}

	/*
	 * Diaspora has different message-ids in feeds than they do
	 * through the direct Diaspora protocol. If we try and use
	 * the feed, we'll get duplicates. So don't.
	 */
	if ((!DBA::isResult($r)) || $contact['network'] === NETWORK_DIASPORA) {
		return;
	}

	$push_url = System::baseUrl() . '/pubsub/' . $r[0]['nickname'] . '/' . $contact['id'];

	// Use a single verify token, even if multiple hubs
	$verify_token = ((strlen($contact['hub-verify'])) ? $contact['hub-verify'] : random_string());

	$params= 'hub.mode=' . $hubmode . '&hub.callback=' . urlencode($push_url) . '&hub.topic=' . urlencode($contact['poll']) . '&hub.verify=async&hub.verify_token=' . $verify_token;

	logger('subscribe_to_hub: ' . $hubmode . ' ' . $contact['name'] . ' to hub ' . $url . ' endpoint: '  . $push_url . ' with verifier ' . $verify_token);

	if (!strlen($contact['hub-verify']) || ($contact['hub-verify'] != $verify_token)) {
		DBA::update('contact', ['hub-verify' => $verify_token], ['id' => $contact['id']]);
	}

	Network::post($url, $params);

	logger('subscribe_to_hub: returns: ' . $a->get_curl_code(), LOGGER_DEBUG);

	return;

}

/// @TODO type-hint is array
function drop_items($items) {
	$uid = 0;

	if (!local_user() && !remote_user()) {
		return;
	}

	if (count($items)) {
		foreach ($items as $item) {
			$owner = Item::deleteForUser(['id' => $item], local_user());
			if ($owner && !$uid)
				$uid = $owner;
		}
	}
}

function drop_item($id) {

	$a = get_app();

	// locate item to be deleted

	$fields = ['id', 'uid', 'contact-id', 'deleted'];
	$item = Item::selectFirstForUser(local_user(), $fields, ['id' => $id]);

	if (!DBA::isResult($item)) {
		notice(L10n::t('Item not found.') . EOL);
		goaway(System::baseUrl() . '/' . $_SESSION['return_url']);
	}

	if ($item['deleted']) {
		return 0;
	}

	$contact_id = 0;

	// check if logged in user is either the author or owner of this item

	if (is_array($_SESSION['remote'])) {
		foreach ($_SESSION['remote'] as $visitor) {
			if ($visitor['uid'] == $item['uid'] && $visitor['cid'] == $item['contact-id']) {
				$contact_id = $visitor['cid'];
				break;
			}
		}
	}

	if ((local_user() == $item['uid']) || $contact_id) {
		// Check if we should do HTML-based delete confirmation
		if ($_REQUEST['confirm']) {
			// <form> can't take arguments in its "action" parameter
			// so add any arguments as hidden inputs
			$query = explode_querystring($a->query_string);
			$inputs = [];
			foreach ($query['args'] as $arg) {
				if (strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
				}
			}

			return replace_macros(get_markup_template('confirm.tpl'), [
				'$method' => 'get',
				'$message' => L10n::t('Do you really want to delete this item?'),
				'$extra_inputs' => $inputs,
				'$confirm' => L10n::t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => L10n::t('Cancel'),
			]);
		}
		// Now check how the user responded to the confirmation query
		if ($_REQUEST['canceled']) {
			goaway(System::baseUrl() . '/' . $_SESSION['return_url']);
		}

		// delete the item
		Item::deleteForUser(['id' => $item['id']], local_user());

		goaway(System::baseUrl() . '/' . $_SESSION['return_url']);
		//NOTREACHED
	} else {
		notice(L10n::t('Permission denied.') . EOL);
		goaway(System::baseUrl() . '/' . $_SESSION['return_url']);
		//NOTREACHED
	}
}

/* arrange the list in years */
function list_post_dates($uid, $wall) {
	$dnow = DateTimeFormat::localNow('Y-m-d');

	$dthen = Item::firstPostDate($uid, $wall);
	if (!$dthen) {
		return [];
	}

	// Set the start and end date to the beginning of the month
	$dnow = substr($dnow, 0, 8) . '01';
	$dthen = substr($dthen, 0, 8) . '01';

	$ret = [];

	/*
	 * Starting with the current month, get the first and last days of every
	 * month down to and including the month of the first post
	 */
	while (substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
		$dyear = intval(substr($dnow, 0, 4));
		$dstart = substr($dnow, 0, 8) . '01';
		$dend = substr($dnow, 0, 8) . Temporal::getDaysInMonth(intval($dnow), intval(substr($dnow, 5)));
		$start_month = DateTimeFormat::utc($dstart, 'Y-m-d');
		$end_month = DateTimeFormat::utc($dend, 'Y-m-d');
		$str = day_translate(DateTimeFormat::utc($dnow, 'F'));

		if (empty($ret[$dyear])) {
			$ret[$dyear] = [];
		}

		$ret[$dyear][] = [$str, $end_month, $start_month];
		$dnow = DateTimeFormat::utc($dnow . ' -1 month', 'Y-m-d');
	}
	return $ret;
}

function posted_date_widget($url, $uid, $wall) {
	$o = '';

	if (!Feature::isEnabled($uid, 'archives')) {
		return $o;
	}

	// For former Facebook folks that left because of "timeline"
	/*
	 * @TODO old-lost code?
	if ($wall && intval(PConfig::get($uid, 'system', 'no_wall_archive_widget')))
		return $o;
	*/

	$visible_years = PConfig::get($uid,'system','archive_visible_years');
	if (!$visible_years) {
		$visible_years = 5;
	}

	$ret = list_post_dates($uid, $wall);

	if (!DBA::isResult($ret)) {
		return $o;
	}

	$cutoff_year = intval(DateTimeFormat::localNow('Y')) - $visible_years;
	$cutoff = ((array_key_exists($cutoff_year, $ret))? true : false);

	$o = replace_macros(get_markup_template('posted_date_widget.tpl'),[
		'$title' => L10n::t('Archives'),
		'$size' => $visible_years,
		'$cutoff_year' => $cutoff_year,
		'$cutoff' => $cutoff,
		'$url' => $url,
		'$dates' => $ret,
		'$showmore' => L10n::t('show more')

	]);
	return $o;
}
