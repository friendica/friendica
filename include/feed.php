<?php
require_once("include/html2bbcode.php");
require_once("include/items.php");

/**
 * @brief Read a RSS/RDF/Atom feed and create an item entry for it
 *
 * @param string $xml The feed data
 * @param array $importer The user record of the importer
 * @param array $contact The contact record of the feed
 * @param string $hub Unused dummy value for compatibility reasons
 * @param bool $simulate If enabled, no data is imported
 *
 * @return array In simulation mode it returns the header and the first item
 */
function feed_import($xml,$importer,&$contact, &$hub, $simulate = false) {

	$a = get_app();

	if (!$simulate) {
		logger("Import Atom/RSS feed '".$contact["name"]."' (Contact ".$contact["id"].") for user ".$importer["uid"], LOGGER_DEBUG);
	} else {
		logger("Test Atom/RSS feed", LOGGER_DEBUG);
	}
	if ($xml == "") {
		logger('XML is empty.', LOGGER_DEBUG);
		return;
	}

	$doc = new DOMDocument();
	@$doc->loadXML($xml);
	$xpath = new DomXPath($doc);
	$xpath->registerNamespace('atom', NAMESPACE_ATOM1);
	$xpath->registerNamespace('dc', "http://purl.org/dc/elements/1.1/");
	$xpath->registerNamespace('content', "http://purl.org/rss/1.0/modules/content/");
	$xpath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
	$xpath->registerNamespace('rss', "http://purl.org/rss/1.0/");
	$xpath->registerNamespace('media', "http://search.yahoo.com/mrss/");
	$xpath->registerNamespace('poco', NAMESPACE_POCO);

	$author = array();

	// Is it RDF?
	if ($xpath->query('/rdf:RDF/rss:channel')->length > 0) {
		$author["author-link"] = $xpath->evaluate('/rdf:RDF/rss:channel/rss:link/text()')->item(0)->nodeValue;
		$author["author-name"] = $xpath->evaluate('/rdf:RDF/rss:channel/rss:title/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "") {
			$author["author-name"] = $xpath->evaluate('/rdf:RDF/rss:channel/rss:description/text()')->item(0)->nodeValue;
		}
		$entries = $xpath->query('/rdf:RDF/rss:item');
	}

	// Is it Atom?
	if ($xpath->query('/atom:feed')->length > 0) {
		$alternate = $xpath->query("atom:link[@rel='alternate']")->item(0)->attributes;
		if (is_object($alternate)) {
			foreach($alternate AS $attributes) {
				if ($attributes->name == "href") {
					$author["author-link"] = $attributes->textContent;
				}
			}
		}

		if ($author["author-link"] == "") {
			$author["author-link"] = $author["author-id"];
		}
		if ($author["author-link"] == "") {
			$self = $xpath->query("atom:link[@rel='self']")->item(0)->attributes;
			if (is_object($self)) {
				foreach($self AS $attributes) {
					if ($attributes->name == "href") {
						$author["author-link"] = $attributes->textContent;
					}
				}
			}
		}

		if ($author["author-link"] == "") {
			$author["author-link"] = $xpath->evaluate('/atom:feed/atom:id/text()')->item(0)->nodeValue;
		}
		$author["author-avatar"] = $xpath->evaluate('/atom:feed/atom:logo/text()')->item(0)->nodeValue;

		$author["author-name"] = $xpath->evaluate('/atom:feed/atom:title/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "") {
			$author["author-name"] = $xpath->evaluate('/atom:feed/atom:subtitle/text()')->item(0)->nodeValue;
		}
		if ($author["author-name"] == "") {
			$author["author-name"] = $xpath->evaluate('/atom:feed/atom:author/atom:name/text()')->item(0)->nodeValue;
		}
		$value = $xpath->evaluate('atom:author/poco:displayName/text()')->item(0)->nodeValue;
		if ($value != "") {
			$author["author-name"] = $value;
		}
		if ($simulate) {
			$author["author-id"] = $xpath->evaluate('/atom:feed/atom:author/atom:uri/text()')->item(0)->nodeValue;

			$value = $xpath->evaluate('atom:author/poco:preferredUsername/text()')->item(0)->nodeValue;
			if ($value != "") {
				$author["author-nick"] = $value;
			}
			$value = $xpath->evaluate('atom:author/poco:address/poco:formatted/text()', $context)->item(0)->nodeValue;
			if ($value != "") {
				$author["author-location"] = $value;
			}
			$value = $xpath->evaluate('atom:author/poco:note/text()')->item(0)->nodeValue;
			if ($value != "") {
				$author["author-about"] = $value;
			}
		}

		$author["edited"] = $author["created"] = $xpath->query('/atom:feed/atom:updated/text()')->item(0)->nodeValue;

		$author["app"] = $xpath->evaluate('/atom:feed/atom:generator/text()')->item(0)->nodeValue;

		$entries = $xpath->query('/atom:feed/atom:entry');
	}

	// Is it RSS?
	if ($xpath->query('/rss/channel')->length > 0) {
		$author["author-link"] = $xpath->evaluate('/rss/channel/link/text()')->item(0)->nodeValue;

		$author["author-name"] = $xpath->evaluate('/rss/channel/title/text()')->item(0)->nodeValue;
		$author["author-avatar"] = $xpath->evaluate('/rss/channel/image/url/text()')->item(0)->nodeValue;

		if ($author["author-name"] == "") {
			$author["author-name"] = $xpath->evaluate('/rss/channel/copyright/text()')->item(0)->nodeValue;
		}
		if ($author["author-name"] == "") {
			$author["author-name"] = $xpath->evaluate('/rss/channel/description/text()')->item(0)->nodeValue;
		}
		$author["edited"] = $author["created"] = $xpath->query('/rss/channel/pubDate/text()')->item(0)->nodeValue;

		$author["app"] = $xpath->evaluate('/rss/channel/generator/text()')->item(0)->nodeValue;

		$entries = $xpath->query('/rss/channel/item');
	}

	if (!$simulate) {
		$author["author-link"] = $contact["url"];

		if ($author["author-name"] == "") {
			$author["author-name"] = $contact["name"];
		}
		$author["author-avatar"] = $contact["thumb"];

		$author["owner-link"] = $contact["url"];
		$author["owner-name"] = $contact["name"];
		$author["owner-avatar"] = $contact["thumb"];
	}

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

	if (!is_object($entries)) {
		logger("There are no entries in this feed.", LOGGER_DEBUG);
		return;
	}

	$items = array();

	$entrylist = array();

	foreach ($entries AS $entry) {
		$entrylist[] = $entry;
	}
	foreach (array_reverse($entrylist) AS $entry) {
		$item = array_merge($header, $author);

		$alternate = $xpath->query("atom:link[@rel='alternate']", $entry)->item(0)->attributes;
		if (!is_object($alternate)) {
			$alternate = $xpath->query("atom:link", $entry)->item(0)->attributes;
		}
		if (is_object($alternate)) {
			foreach($alternate AS $attributes) {
				if ($attributes->name == "href") {
					$item["plink"] = $attributes->textContent;
				}
			}
		}
		if ($item["plink"] == "") {
			$item["plink"] = $xpath->evaluate('link/text()', $entry)->item(0)->nodeValue;
		}
		if ($item["plink"] == "") {
			$item["plink"] = $xpath->evaluate('rss:link/text()', $entry)->item(0)->nodeValue;
		}
		$item["plink"] = original_url($item["plink"]);

		$item["uri"] = $xpath->evaluate('atom:id/text()', $entry)->item(0)->nodeValue;

		if ($item["uri"] == "") {
			$item["uri"] = $xpath->evaluate('guid/text()', $entry)->item(0)->nodeValue;
		}
		if ($item["uri"] == "") {
			$item["uri"] = $item["plink"];
		}
		$item["parent-uri"] = $item["uri"];

		if (!$simulate) {
			$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `network` IN ('%s', '%s')",
				intval($importer["uid"]), dbesc($item["uri"]), dbesc(NETWORK_FEED), dbesc(NETWORK_DFRN));
			if ($r) {
				logger("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already existed under id ".$r[0]["id"], LOGGER_DEBUG);
				continue;
			}
		}

		$item["title"] = $xpath->evaluate('atom:title/text()', $entry)->item(0)->nodeValue;

		if ($item["title"] == "") {
			$item["title"] = $xpath->evaluate('title/text()', $entry)->item(0)->nodeValue;
		}
		if ($item["title"] == "") {
			$item["title"] = $xpath->evaluate('rss:title/text()', $entry)->item(0)->nodeValue;
		}
		$published = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;

		if ($published == "") {
			$published = $xpath->query('pubDate/text()', $entry)->item(0)->nodeValue;
		}
		if ($published == "") {
			$published = $xpath->query('dc:date/text()', $entry)->item(0)->nodeValue;
		}
		$updated = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;

		if ($updated == "") {
			$updated = $published;
		}
		if ($published != "") {
			$item["created"] = $published;
		}
		if ($updated != "") {
			$item["edited"] = $updated;
		}
		$creator = $xpath->query('author/text()', $entry)->item(0)->nodeValue;

		if ($creator == "") {
			$creator = $xpath->query('atom:author/atom:name/text()', $entry)->item(0)->nodeValue;
		}
		if ($creator == "") {
			$creator = $xpath->query('dc:creator/text()', $entry)->item(0)->nodeValue;
		}
		if ($creator != "") {
			$item["author-name"] = $creator;
		}
		if ($pubDate != "") {
			$item["edited"] = $item["created"] = $pubDate;
		}
		$creator = $xpath->query('dc:creator/text()', $entry)->item(0)->nodeValue;

		if ($creator != "") {
			$item["author-name"] = $creator;
		}
		/// @TODO ?
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
				if ($attributes->name == "url") {
					$href = $attributes->textContent;
				} elseif ($attributes->name == "length") {
					$length = $attributes->textContent;
				} elseif ($attributes->name == "type") {
					$type = $attributes->textContent;
				}
			}
			if(strlen($item["attach"]))
				$item["attach"] .= ',';

			$attachments[] = array("link" => $href, "type" => $type, "length" => $length);

			$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'"[/attach]';
		}

		if ($contact["fetch_further_information"]) {
			$preview = "";

			// Handle enclosures and treat them as preview picture
			foreach ($attachments AS $attachment) {
				if ($attachment["type"] == "image/jpeg") {
					$preview = $attachment["link"];
				}
			}

			$item["body"] = $item["title"].add_page_info($item["plink"], false, $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_blacklist"]);
			$item["tag"] = add_page_keywords($item["plink"], false, $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_blacklist"]);
			$item["title"] = "";
			$item["object-type"] = ACTIVITY_OBJ_BOOKMARK;
			unset($item["attach"]);
		} else {
			$body = trim($xpath->evaluate('atom:content/text()', $entry)->item(0)->nodeValue);

			if ($body == "") {
				$body = trim($xpath->evaluate('content:encoded/text()', $entry)->item(0)->nodeValue);
			}
			if ($body == "") {
				$body = trim($xpath->evaluate('description/text()', $entry)->item(0)->nodeValue);
			}
			if ($body == "") {
				$body = trim($xpath->evaluate('atom:summary/text()', $entry)->item(0)->nodeValue);
			}
			// remove the content of the title if it is identically to the body
			// This helps with auto generated titles e.g. from tumblr
			if (title_is_body($item["title"], $body)) {
				$item["title"] = "";
			}
			$item["body"] = html2bbcode($body);
		}

		if (!$simulate) {
			logger("Stored feed: ".print_r($item, true), LOGGER_DEBUG);

			$notify = item_is_remote_self($contact, $item);

			// Distributed items should have a well formatted URI.
			// Additionally we have to avoid conflicts with identical URI between imported feeds and these items.
			if ($notify) {
				unset($item['uri']);
				unset($item['parent-uri']);
			}

			$id = item_store($item, false, $notify);

			logger("Feed for contact ".$contact["url"]." stored under id ".$id);
		} else {
			$items[] = $item;
		}
		if ($simulate) {
			break;
		}
	}

	if ($simulate) {
		return array("header" => $author, "items" => $items);
	}
}
?>
