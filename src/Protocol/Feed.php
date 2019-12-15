<?php
/**
 * @file src/Protocol/Feed.php
 * @brief Imports RSS/RDF/Atom feeds
 *
 */
namespace Friendica\Protocol;

use DOMDocument;
use DOMXPath;
use Friendica\Content\Text\HTML;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Util\ParseUrl;
use Friendica\Util\Network;
use Friendica\Util\XML;

/**
 * @brief This class contain functions to import feeds
 *
 */
class Feed {
	/**
	 * @brief Read a RSS/RDF/Atom feed and create an item entry for it
	 *
	 * @param string $xml      The feed data
	 * @param array  $importer The user record of the importer
	 * @param array  $contact  The contact record of the feed
	 * @param string $hub      Unused dummy value for compatibility reasons
	 * @param bool   $simulate If enabled, no data is imported
	 *
	 * @return array In simulation mode it returns the header and the first item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function import($xml, $importer, &$contact, &$hub, $simulate = false) {

		$a = \get_app();

		if (!$simulate) {
			Logger::log("Import Atom/RSS feed '".$contact["name"]."' (Contact ".$contact["id"].") for user ".$importer["uid"], Logger::DEBUG);
		} else {
			Logger::log("Test Atom/RSS feed", Logger::DEBUG);
		}
		if (empty($xml)) {
			Logger::log('XML is empty.', Logger::DEBUG);
			return;
		}

		if (!empty($contact['poll'])) {
			$basepath = $contact['poll'];
		} elseif (!empty($contact['url'])) {
			$basepath = $contact['url'];
		} else {
			$basepath = '';
		}

		$doc = new DOMDocument();
		@$doc->loadXML(trim($xml));
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('dc', "http://purl.org/dc/elements/1.1/");
		$xpath->registerNamespace('content', "http://purl.org/rss/1.0/modules/content/");
		$xpath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		$xpath->registerNamespace('rss', "http://purl.org/rss/1.0/");
		$xpath->registerNamespace('media', "http://search.yahoo.com/mrss/");
		$xpath->registerNamespace('poco', ActivityNamespace::POCO);

		$author = [];
		$entries = null;

		// Is it RDF?
		if ($xpath->query('/rdf:RDF/rss:channel')->length > 0) {
			$author["author-link"] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:link/text()');
			$author["author-name"] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:title/text()');

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:description/text()');
			}
			$entries = $xpath->query('/rdf:RDF/rss:item');
		}

		// Is it Atom?
		if ($xpath->query('/atom:feed')->length > 0) {
			$alternate = XML::getFirstAttributes($xpath, "atom:link[@rel='alternate']");
			if (is_object($alternate)) {
				foreach ($alternate AS $attribute) {
					if ($attribute->name == "href") {
						$author["author-link"] = $attribute->textContent;
					}
				}
			}

			if (empty($author["author-link"])) {
				$self = XML::getFirstAttributes($xpath, "atom:link[@rel='self']");
				if (is_object($self)) {
					foreach ($self AS $attribute) {
						if ($attribute->name == "href") {
							$author["author-link"] = $attribute->textContent;
						}
					}
				}
			}

			if (empty($author["author-link"])) {
				$author["author-link"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:id/text()');
			}
			$author["author-avatar"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:logo/text()');

			$author["author-name"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:title/text()');

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:subtitle/text()');
			}
			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:author/atom:name/text()');
			}
			$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:displayName/text()');
			if ($value != "") {
				$author["author-name"] = $value;
			}
			if ($simulate) {
				$author["author-id"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:author/atom:id/text()');

				// See https://tools.ietf.org/html/rfc4287#section-3.2.2
				$value = XML::getFirstNodeValue($xpath, 'atom:author/atom:uri/text()');
				if ($value != "") {
					$author["author-link"] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:preferredUsername/text()');
				if ($value != "") {
					$author["author-nick"] = $value;
				}
				$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:address/poco:formatted/text()');
				if ($value != "") {
					$author["author-location"] = $value;
				}
				$value = XML::getFirstNodeValue($xpath, 'atom:author/poco:note/text()');
				if ($value != "") {
					$author["author-about"] = $value;
				}
				$avatar = XML::getFirstAttributes($xpath, "atom:author/atom:link[@rel='avatar']");
				if (is_object($avatar)) {
					foreach ($avatar AS $attribute) {
						if ($attribute->name == "href") {
							$author["author-avatar"] = $attribute->textContent;
						}
					}
				}
			}

			$author["edited"] = $author["created"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:updated/text()');

			$author["app"] = XML::getFirstNodeValue($xpath, '/atom:feed/atom:generator/text()');

			$entries = $xpath->query('/atom:feed/atom:entry');
		}

		// Is it RSS?
		if ($xpath->query('/rss/channel')->length > 0) {
			$author["author-link"] = XML::getFirstNodeValue($xpath, '/rss/channel/link/text()');

			$author["author-name"] = XML::getFirstNodeValue($xpath, '/rss/channel/title/text()');
			$author["author-avatar"] = XML::getFirstNodeValue($xpath, '/rss/channel/image/url/text()');

			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/rss/channel/copyright/text()');
			}
			if (empty($author["author-name"])) {
				$author["author-name"] = XML::getFirstNodeValue($xpath, '/rss/channel/description/text()');
			}
			$author["edited"] = $author["created"] = XML::getFirstNodeValue($xpath, '/rss/channel/pubDate/text()');

			$author["app"] = XML::getFirstNodeValue($xpath, '/rss/channel/generator/text()');

			$entries = $xpath->query('/rss/channel/item');
		}

		if (!$simulate) {
			$author["author-link"] = $contact["url"];

			if (empty($author["author-name"])) {
				$author["author-name"] = $contact["name"];
			}
			$author["author-avatar"] = $contact["thumb"];

			$author["owner-link"] = $contact["url"];
			$author["owner-name"] = $contact["name"];
			$author["owner-avatar"] = $contact["thumb"];
		}

		$header = [];
		$header["uid"] = $importer["uid"];
		$header["network"] = Protocol::FEED;
		$header["wall"] = 0;
		$header["origin"] = 0;
		$header["gravity"] = GRAVITY_PARENT;
		$header["private"] = 2;
		$header["verb"] = Activity::POST;
		$header["object-type"] = Activity\ObjectType::NOTE;

		$header["contact-id"] = $contact["id"];

		if (!is_object($entries)) {
			Logger::log("There are no entries in this feed.", Logger::DEBUG);
			return;
		}

		$items = [];
		// Importing older entries first
		for($i = $entries->length - 1; $i >= 0;--$i) {
			$entry = $entries->item($i);

			$item = array_merge($header, $author);

			$alternate = XML::getFirstAttributes($xpath, "atom:link[@rel='alternate']", $entry);
			if (!is_object($alternate)) {
				$alternate = XML::getFirstAttributes($xpath, "atom:link", $entry);
			}
			if (is_object($alternate)) {
				foreach ($alternate AS $attribute) {
					if ($attribute->name == "href") {
						$item["plink"] = $attribute->textContent;
					}
				}
			}
			if (empty($item["plink"])) {
				$item["plink"] = XML::getFirstNodeValue($xpath, 'link/text()', $entry);
			}
			if (empty($item["plink"])) {
				$item["plink"] = XML::getFirstNodeValue($xpath, 'rss:link/text()', $entry);
			}

			$item["uri"] = XML::getFirstNodeValue($xpath, 'atom:id/text()', $entry);

			if (empty($item["uri"])) {
				$item["uri"] = XML::getFirstNodeValue($xpath, 'guid/text()', $entry);
			}
			if (empty($item["uri"])) {
				$item["uri"] = $item["plink"];
			}

			$orig_plink = $item["plink"];

			$item["plink"] = Network::finalUrl($item["plink"]);

			$item["parent-uri"] = $item["uri"];

			if (!$simulate) {
				$condition = ["`uid` = ? AND `uri` = ? AND `network` IN (?, ?)",
					$importer["uid"], $item["uri"], Protocol::FEED, Protocol::DFRN];
				$previous = Item::selectFirst(['id'], $condition);
				if (DBA::isResult($previous)) {
					Logger::log("Item with uri ".$item["uri"]." for user ".$importer["uid"]." already existed under id ".$previous["id"], Logger::DEBUG);
					continue;
				}
			}

			$item["title"] = XML::getFirstNodeValue($xpath, 'atom:title/text()', $entry);

			if (empty($item["title"])) {
				$item["title"] = XML::getFirstNodeValue($xpath, 'title/text()', $entry);
			}
			if (empty($item["title"])) {
				$item["title"] = XML::getFirstNodeValue($xpath, 'rss:title/text()', $entry);
			}

			$item["title"] = html_entity_decode($item["title"], ENT_QUOTES, 'UTF-8');

			$published = XML::getFirstNodeValue($xpath, 'atom:published/text()', $entry);

			if (empty($published)) {
				$published = XML::getFirstNodeValue($xpath, 'pubDate/text()', $entry);
			}
			if (empty($published)) {
				$published = XML::getFirstNodeValue($xpath, 'dc:date/text()', $entry);
			}
			$updated = XML::getFirstNodeValue($xpath, 'atom:updated/text()', $entry);

			if (empty($updated) && !empty($published)) {
				$updated = $published;
			}

			if (empty($published) && !empty($updated)) {
				$published = $updated;
			}

			if ($published != "") {
				$item["created"] = $published;
			}
			if ($updated != "") {
				$item["edited"] = $updated;
			}
			$creator = XML::getFirstNodeValue($xpath, 'author/text()', $entry);

			if (empty($creator)) {
				$creator = XML::getFirstNodeValue($xpath, 'atom:author/atom:name/text()', $entry);
			}
			if (empty($creator)) {
				$creator = XML::getFirstNodeValue($xpath, 'dc:creator/text()', $entry);
			}
			if ($creator != "") {
				$item["author-name"] = $creator;
			}
			$creator = XML::getFirstNodeValue($xpath, 'dc:creator/text()', $entry);

			if ($creator != "") {
				$item["author-name"] = $creator;
			}

			/// @TODO ?
			// <category>Ausland</category>
			// <media:thumbnail width="152" height="76" url="http://www.taz.de/picture/667875/192/14388767.jpg"/>

			$attachments = [];

			$enclosures = $xpath->query("enclosure|atom:link[@rel='enclosure']", $entry);
			foreach ($enclosures AS $enclosure) {
				$href = "";
				$length = "";
				$type = "";

				foreach ($enclosure->attributes AS $attribute) {
					if (in_array($attribute->name, ["url", "href"])) {
						$href = $attribute->textContent;
					} elseif ($attribute->name == "length") {
						$length = $attribute->textContent;
					} elseif ($attribute->name == "type") {
						$type = $attribute->textContent;
					}
				}
				if (!empty($item["attach"])) {
					$item["attach"] .= ',';
				} else {
					$item["attach"] = '';
				}

				$attachments[] = ["link" => $href, "type" => $type, "length" => $length];

				$item["attach"] .= '[attach]href="'.$href.'" length="'.$length.'" type="'.$type.'"[/attach]';
			}

			$tags = '';
			$categories = $xpath->query("category", $entry);
			foreach ($categories AS $category) {
				$hashtag = $category->nodeValue;
				if ($tags != '') {
					$tags .= ', ';
				}

				$taglink = "#[url=" . System::baseUrl() . "/search?tag=" . $hashtag . "]" . $hashtag . "[/url]";
				$tags .= $taglink;
			}

			$body = trim(XML::getFirstNodeValue($xpath, 'atom:content/text()', $entry));

			if (empty($body)) {
				$body = trim(XML::getFirstNodeValue($xpath, 'content:encoded/text()', $entry));
			}

			$summary = trim(XML::getFirstNodeValue($xpath, 'atom:summary/text()', $entry));

			if (empty($summary)) {
				$summary = trim(XML::getFirstNodeValue($xpath, 'description/text()', $entry));
			}

			if (empty($body)) {
				$body = $summary;
				$summary = '';
			}

			if ($body == $summary) {
				$summary = '';
			}

			// remove the content of the title if it is identically to the body
			// This helps with auto generated titles e.g. from tumblr
			if (self::titleIsBody($item["title"], $body)) {
				$item["title"] = "";
			}
			$item["body"] = HTML::toBBCode($body, $basepath);

			if (($item["body"] == '') && ($item["title"] != '')) {
				$item["body"] = $item["title"];
				$item["title"] = '';
			}

			$preview = '';
			if (!empty($contact["fetch_further_information"]) && ($contact["fetch_further_information"] < 3)) {
				// Handle enclosures and treat them as preview picture
				foreach ($attachments AS $attachment) {
					if ($attachment["type"] == "image/jpeg") {
						$preview = $attachment["link"];
					}
				}

				// Remove a possible link to the item itself
				$item["body"] = str_replace($item["plink"], '', $item["body"]);
				$item["body"] = trim(preg_replace('/\[url\=\](\w+.*?)\[\/url\]/i', '', $item["body"]));

				// Replace the content when the title is longer than the body
				$replace = (strlen($item["title"]) > strlen($item["body"]));

				// Replace it, when there is an image in the body
				if (strstr($item["body"], '[/img]')) {
					$replace = true;
				}

				// Replace it, when there is a link in the body
				if (strstr($item["body"], '[/url]')) {
					$replace = true;
				}

				if ($replace) {
					$item["body"] = trim($item["title"]);
				}

			        $data = ParseUrl::getSiteinfoCached($item['plink'], true);
				if (!empty($data['text']) && !empty($data['title']) && (mb_strlen($item['body']) < mb_strlen($data['text']))) {
					// When the fetched page info text is longer than the body, we do try to enhance the body
					if (!empty($item['body']) && (strpos($data['title'], $item['body']) === false) && (strpos($data['text'], $item['body']) === false)) {
						// The body is not part of the fetched page info title or page info text. So we add the text to the body
						$item['body'] .= "\n\n" . $data['text'];
					} else {
						// Else we replace the body with the page info text
						$item['body'] = $data['text'];
					}
				}

				// We always strip the title since it will be added in the page information
				$item["title"] = "";
				$item["body"] = $item["body"].add_page_info($item["plink"], false, $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_blacklist"]);
				$item["tag"] = add_page_keywords($item["plink"], $preview, ($contact["fetch_further_information"] == 2), $contact["ffi_keyword_blacklist"]);
				$item["object-type"] = Activity\ObjectType::BOOKMARK;
				unset($item["attach"]);
			} else {
				if (!empty($summary)) {
					$item["body"] = '[abstract]' . HTML::toBBCode($summary, $basepath) . "[/abstract]\n" . $item["body"];
				}

				if ($contact["fetch_further_information"] == 3) {
					if (!empty($tags)) {
						$item["tag"] = $tags;
					} else {
						// @todo $preview is never set in this case, is it intended? - @MrPetovan 2018-02-13
						$item["tag"] = add_page_keywords($item["plink"], $preview, true, $contact["ffi_keyword_blacklist"]);
					}
					$item["body"] .= "\n".$item['tag'];
				}
				// Add the link to the original feed entry if not present in feed
				if (($item['plink'] != '') && !strstr($item["body"], $item['plink'])) {
					$item["body"] .= "[hr][url]".$item['plink']."[/url]";
				}
			}

			if (!$simulate) {
				Logger::log("Stored feed: ".print_r($item, true), Logger::DEBUG);

				$notify = Item::isRemoteSelf($contact, $item);

				// Distributed items should have a well formatted URI.
				// Additionally we have to avoid conflicts with identical URI between imported feeds and these items.
				if ($notify) {
					$item['guid'] = Item::guidFromUri($orig_plink, DI::baseUrl()->getHostname()());
					unset($item['uri']);
					unset($item['parent-uri']);

					// Set the delivery priority for "remote self" to "medium"
					$notify = PRIORITY_MEDIUM;
				}

				$id = Item::insert($item, false, $notify);

				Logger::log("Feed for contact ".$contact["url"]." stored under id ".$id);
			} else {
				$items[] = $item;
			}
			if ($simulate) {
				break;
			}
		}

		if ($simulate) {
			return ["header" => $author, "items" => $items];
		}
	}

	private static function titleIsBody($title, $body)
	{
		$title = strip_tags($title);
		$title = trim($title);
		$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
		$title = str_replace(["\n", "\r", "\t", " "], ["", "", "", ""], $title);

		$body = strip_tags($body);
		$body = trim($body);
		$body = html_entity_decode($body, ENT_QUOTES, 'UTF-8');
		$body = str_replace(["\n", "\r", "\t", " "], ["", "", "", ""], $body);

		if (strlen($title) < strlen($body)) {
			$body = substr($body, 0, strlen($title));
		}

		if (($title != $body) && (substr($title, -3) == "...")) {
			$pos = strrpos($title, "...");
			if ($pos > 0) {
				$title = substr($title, 0, $pos);
				$body = substr($body, 0, $pos);
			}
		}
		return ($title == $body);
	}
}
