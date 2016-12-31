<?php

/** 
 * @file mod/parse_url.php
 * @brief The parse_url module
 * 
 * This module does parse an url for embedable content (audio, video, image files or link)
 * information and does format this information to BBCode or html (this depends
 * on the user settings - default is BBCode output).
 * If the user has enabled the richtext editor setting the output will be in html
 * (Note: This is not always possible and in some case not useful because
 * the richtext editor doesn't support all kind of html).
 * Otherwise the output will be constructed BBCode.
 * 
 * @see ParseUrl::getSiteinfo() for more information about scraping embeddable content 
*/

use \Friendica\ParseUrl;

require_once("include/items.php");

function parse_url_content(&$a) {

	$text = null;
	$str_tags = "";

	$textmode = false;

	if (local_user() && (!feature_enabled(local_user(), "richtext"))) {
		$textmode = true;
	}

	$br = (($textmode) ? "\n" : "<br />");

	if (x($_GET,"binurl")) {
		$url = trim(hex2bin($_GET["binurl"]));
	} else {
		$url = trim($_GET["url"]);
	}

	if ($_GET["title"]) {
		$title = strip_tags(trim($_GET["title"]));
	}

	if ($_GET["description"]) {
		$text = strip_tags(trim($_GET["description"]));
	}

	if ($_GET["tags"]) {
		$arr_tags = ParseUrl::convertTagsToArray($_GET["tags"]);
		if (count($arr_tags)) {
			$str_tags = $br . implode(" ", $arr_tags) . $br;
		}
	}

	// Add url scheme if it is missing
	$arrurl = parse_url($url);
	if (!x($arrurl, "scheme")) {
		if (x($arrurl, "host")) {
			$url = "http:".$url;
		} else {
			$url = "http://".$url;
		}
	}

	logger("prse_url: " . $url);

	// Check if the URL is an image, video or audio file. If so format
	// the URL with the corresponding BBCode media tag
	$redirects = 0;
	// Fetch the header of the URL
	$result = z_fetch_url($url, false, $redirects, array("novalidate" => true, "nobody" => true));
	if($result["success"]) {
		// Convert the header fields into an array
		$hdrs = array();
		$h = explode("\n", $result["header"]);
		foreach ($h as $l) {
			list($k,$v) = array_map("trim", explode(":", trim($l), 2));
			$hdrs[$k] = $v;
		}
		if (array_key_exists("Content-Type", $hdrs)) {
			$type = $hdrs["Content-Type"];
		}
		if ($type) {
			if(stripos($type, "image/") !== false) {
				echo $br . "[img]" . $url . "[/img]" . $br;
				killme();
			}
			if (stripos($type, "video/") !== false) {
				echo $br . "[video]" . $url . "[/video]" . $br;
				killme();
			}
			if (stripos($type, "audio/") !== false) {
				echo $br . "[audio]" . $url . "[/audio]" . $br;
				killme();
			}
		}
	}

	if ($textmode) {
		$template = "[bookmark=%s]%s[/bookmark]%s";
	} else {
		$template = "<a class=\"bookmark\" href=\"%s\" >%s</a>%s";
	}

	$arr = array("url" => $url, "text" => "");

	call_hooks("parse_link", $arr);

	if (strlen($arr["text"])) {
		echo $arr["text"];
		killme();
	}

	// If there is allready some content information submitted we don't
	// need to parse the url for content.
	if ($url && $title && $text) {

		$title = str_replace(array("\r","\n"),array("",""),$title);

		if ($textmode) {
			$text = "[quote]" . trim($text) . "[/quote]" . $br;
		} else {
			$text = "<blockquote>" . htmlspecialchars(trim($text)) . "</blockquote><br />";
			$title = htmlspecialchars($title);
		}

		$result = sprintf($template, $url, ($title) ? $title : $url, $text) . $str_tags;

		logger("parse_url (unparsed): returns: " . $result);

		echo $result;
		killme();
	}

	// Fetch the information directly from the webpage
	$siteinfo = ParseUrl::getSiteinfo($url);

	unset($siteinfo["keywords"]);

	// Format it as BBCode attachment
	$info = add_page_info_data($siteinfo);

	if (!$textmode) {
		// Replace ' with ’ - not perfect - but the richtext editor has problems otherwise
		$info = str_replace(array("&#039;"), array("&#8217;"), $info);
	}

	echo $info;

	killme();
}

/**
 * @brief Legacy function to call ParseUrl::getSiteinfoCached
 * 
 * Note: We have moved the function to ParseUrl.php. This function is only for
 * legacy support and will be remove in the future
 * 
 * @param type $url The url of the page which should be scraped
 * @param type $no_guessing If true the parse doens't search for
 *    preview pictures
 * @param type $do_oembed The false option is used by the function fetch_oembed()
 *    to avoid endless loops
 * 
 * @return array which contains needed data for embedding
 * 
 * @see ParseUrl::getSiteinfoCached()
 * 
 * @todo Remove this function after all Addons has been changed to use
 *    ParseUrl::getSiteinfoCached
 */
function parseurl_getsiteinfo_cached($url, $no_guessing = false, $do_oembed = true) {
	$siteinfo = ParseUrl::getSiteinfoCached($url, $no_guessing, $do_oembed);
	return $siteinfo;
}
