<?php
require_once("include/html2bbcode.php");
require_once("include/items.php");

function feed_import($xml,$importer,&$contact, &$hub) {

	$a = get_app();

	logger("Import Atom/RSS feed", LOGGER_DEBUG);

	if ($xml == "")
		return;

	$doc = new DOMDocument();
	@$doc->loadXML($xml);
	$xpath = new DomXPath($doc);
	$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
	$xpath->registerNamespace('dc', "http://purl.org/dc/elements/1.1/");
	$xpath->registerNamespace('content', "http://purl.org/rss/1.0/modules/content/");
	$xpath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
	$xpath->registerNamespace('rss', "http://purl.org/rss/1.0/");
	$xpath->registerNamespace('media', "http://search.yahoo.com/mrss/");

	$author = array();

	// Is it RDF?
	if ($xpath->query('/rdf:RDF/rss:channel')->length > 0) {
		//$author["author-link"] = $xpath->evaluate('/rdf:RDF/rss:channel/rss:link/text()')->item(0)->nodeValue;
		$author["author-name"] = $xpath->evaluate('/rdf:RDF/rss:channel/rss:title/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "")
			$author["author-name"] = $xpath->evaluate('/rdf:RDF/rss:channel/rss:description/text()')->item(0)->nodeValue;

		$entries = $xpath->query('/rdf:RDF/rss:item');
	}

	// Is it Atom?
	if ($xpath->query('/atom:feed/atom:entry')->length > 0) {
		//$self = $xpath->query("/atom:feed/atom:link[@rel='self']")->item(0)->attributes;
		//if (is_object($self))
		//	foreach($self AS $attributes)
		//		if ($attributes->name == "href")
		//			$author["author-link"] = $attributes->textContent;

		//if ($author["author-link"] == "") {
		//	$alternate = $xpath->query("/atom:feed/atom:link[@rel='alternate']")->item(0)->attributes;
		//	if (is_object($alternate))
		//		foreach($alternate AS $attributes)
		//			if ($attributes->name == "href")
		//				$author["author-link"] = $attributes->textContent;
		//}

		$author["author-name"] = $xpath->evaluate('/atom:feed/atom:title/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "")
			$author["author-name"] = $xpath->evaluate('/atom:feed/atom:subtitle/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "")
			$author["author-name"] = $xpath->evaluate('/atom:feed/atom:author/atom:name/text()')->item(0)->nodeValue;

		//$author["author-avatar"] = $xpath->evaluate('/atom:feed/atom:logo/text()')->item(0)->nodeValue;

		$author["edited"] = $author["created"] = $xpath->query('/atom:feed/atom:updated/text()')->item(0)->nodeValue;

		$author["app"] = $xpath->evaluate('/atom:feed/atom:generator/text()')->item(0)->nodeValue;

		$entries = $xpath->query('/atom:feed/atom:entry');
	}

	// Is it RSS?
	if ($xpath->query('/rss/channel')->length > 0) {
		//$author["author-link"] = $xpath->evaluate('/rss/channel/link/text()')->item(0)->nodeValue;
		$author["author-name"] = $xpath->evaluate('/rss/channel/title/text()')->item(0)->nodeValue;
		//$author["author-avatar"] = $xpath->evaluate('/rss/channel/image/url/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "")
			$author["author-name"] = $xpath->evaluate('/rss/channel/copyright/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "")
			$author["author-name"] = $xpath->evaluate('/rss/channel/description/text()')->item(0)->nodeValue;

		$author["edited"] = $author["created"] = $xpath->query('/rss/channel/pubDate/text()')->item(0)->nodeValue;

		$author["app"] = $xpath->evaluate('/rss/channel/generator/text()')->item(0)->nodeValue;

		$entries = $xpath->query('/rss/channel/item');
	}

	//if ($author["author-link"] == "")
		$author["author-link"] = $contact["url"];

	if ($author["author-name"] == "")
		$author["author-name"] = $contact["name"];

	//if ($author["author-avatar"] == "")
		$author["author-avatar"] = $contact["thumb"];

	$author["owner-link"] = $contact["url"];
	$author["owner-name"] = $contact["name"];
	$author["owner-avatar"] = $contact["thumb"];

	$header = array();
	$header["uid"] = $importer["uid"];
	$header["network"] = NETWORK_FEED;
	$header["type"] = "remote";
	$header["wall"] = 0;
	$header["origin"] = 0;
	$header["gravity"] = GRAVITY_PARENT;
	$header["private"] = 2;
	$header["verb"] = ACTIVITY_POST;
	$header["object-type"] = ACTIVITY_OBJ_NOTE;

	$header["contact-id"] = $contact["id"];

	if(!strlen($contact["notify"])) {
		// one way feed - no remote comment ability
		$header["last-child"] = 0;
	}

	if (!is_object($entries))
		return;

	$entrylist = array();

	foreach ($entries AS $entry)
		$entrylist[] = $entry;

	foreach (array_reverse($entrylist) AS $entry) {
		$item = array_merge($header, $author);

		$item["title"] = $xpath->evaluate('atom:title/text()', $entry)->item(0)->nodeValue;

		if ($item["title"] == "")
			$item["title"] = $xpath->evaluate('title/text()', $entry)->item(0)->nodeValue;

		if ($item["title"] == "")
			$item["title"] = $xpath->evaluate('rss:title/text()', $entry)->item(0)->nodeValue;

		$alternate = $xpath->query("atom:link[@rel='alternate']", $entry)->item(0)->attributes;
		if (!is_object($alternate))
			$alternate = $xpath->query("atom:link", $entry)->item(0)->attributes;

		if (is_object($alternate))
			foreach($alternate AS $attributes)
				if ($attributes->name == "href")
					$item["plink"] = $attributes->textContent;

		if ($item["plink"] == "")
			$item["plink"] = $xpath->evaluate('link/text()', $entry)->item(0)->nodeValue;

		if ($item["plink"] == "")
			$item["plink"] = $xpath->evaluate('rss:link/text()', $entry)->item(0)->nodeValue;

		$item["plink"] = original_url($item["plink"]);

		$item["uri"] = $xpath->evaluate('atom:id/text()', $entry)->item(0)->nodeValue;

		if ($item["uri"] == "")
			$item["uri"] = $xpath->evaluate('guid/text()', $entry)->item(0)->nodeValue;

		if ($item["uri"] == "")
			$item["uri"] = $item["plink"];

		$item["parent-uri"] = $item["uri"];

		$published = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;

		if ($published == "")
			$published = $xpath->query('pubDate/text()', $entry)->item(0)->nodeValue;

		if ($published == "")
			$published = $xpath->query('dc:date/text()', $entry)->item(0)->nodeValue;

		$updated = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;

		if ($updated == "")
			$updated = $published;

		if ($published != "")
			$item["created"] = $published;

		if ($updated != "")
			$item["edited"] = $updated;

		$creator = $xpath->query('author/text()', $entry)->item(0)->nodeValue;

		if ($creator == "")
			$creator = $xpath->query('atom:author/atom:name/text()', $entry)->item(0)->nodeValue;

		if ($creator == "")
			$creator = $xpath->query('dc:creator/text()', $entry)->item(0)->nodeValue;

		if ($creator != "")
			$item["author-name"] = $creator;

		if ($pubDate != "")
			$item["edited"] = $item["created"] = $pubDate;

		$creator = $xpath->query('dc:creator/text()', $entry)->item(0)->nodeValue;

		if ($creator != "")
			$item["author-name"] = $creator;

		//$item["object"] = $xml;

		$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` = '%s'",
			intval($importer["uid"]), dbesc($item["uri"]), dbesc(NETWORK_FEED));
		if ($r) {
			logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already existed under id ".$r[0]["id"], LOGGER_DEBUG);
			continue;
		}

		// To-Do?
		// <category>Ausland</category>
		// <media:thumbnail width="152" height="76" url="http://www.taz.de/picture/667875/192/14388767.jpg"/>

		$attachments = array();

		$enclosures = $xpath->query("enclosure", $entry);
		foreach ($enclosures AS $enclosure) {
			$href = "";
			$length = "";
			$type = "";
			$title = "";

			foreach($enclosure->attributes AS $attributes) {
				if ($attributes->name == "url")
					$href = $attributes->textContent;
				elseif ($attributes->name == "length")
					$length = $attributes->textContent;
				elseif ($attributes->name == "type")
					$type = $attributes->textContent;
			}
			if(strlen($item["attach"]))
				$item["attach"] .= ',';

			$attachments[] = array("link" => $href, "type" => $type, "length" => $length);

			$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'"[/attach]';
		}

		if ($contact["fetch_further_information"]) {
			$preview = "";

			// Handle enclosures and treat them as preview picture
			foreach ($attachments AS $attachment)
				if ($attachment["type"] == "image/jpeg")
					$preview = $attachment["link"];

			$item["body"] = $item["title"].add_page_info($item["plink"], false, $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_blacklist"]);
			$item["tag"] = add_page_keywords($item["plink"], false, $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_blacklist"]);
			$item["title"] = "";
			$item["object-type"] = ACTIVITY_OBJ_BOOKMARK;
			unset($item["attach"]);
		} else {
			$body = trim($xpath->evaluate('atom:content/text()', $entry)->item(0)->nodeValue);

			if ($body == "")
				$body = trim($xpath->evaluate('content:encoded/text()', $entry)->item(0)->nodeValue);

			if ($body == "")
				$body = trim($xpath->evaluate('description/text()', $entry)->item(0)->nodeValue);

			if ($body == "")
				$body = trim($xpath->evaluate('atom:summary/text()', $entry)->item(0)->nodeValue);

			// remove the content of the title if it is identically to the body
			// This helps with auto generated titles e.g. from tumblr
			if (title_is_body($item["title"], $body))
				$item["title"] = "";

			$item["body"] = html2bbcode($body);
		}

		logger("Stored feed: ".print_r($item, true), LOGGER_DEBUG);

		$notify = item_is_remote_self($contact, $item);
		$id = item_store($item, false, $notify);

		//print_r($item);

		logger("Feed for contact ".$contact["url"]." stored under id ".$id);
	}
}
?>
