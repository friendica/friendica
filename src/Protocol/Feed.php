<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Friendica\App;
use Friendica\Contact\LocalRelationship\Entity\LocalRelationship;
use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use GuzzleHttp\Exception\TransferException;

/**
 * This class contain functions to import feeds (RSS/RDF/Atom)
 */
class Feed
{
	/**
	 * Read a RSS/RDF/Atom feed and create an item entry for it
	 *
	 * @param string $xml      The feed data
	 * @param array  $importer The user record of the importer
	 * @param array  $contact  The contact record of the feed
	 *
	 * @return array Returns the header and the first item in dry run mode
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function import(string $xml, array $importer = [], array $contact = []): array
	{
		$dryRun = empty($importer) && empty($contact);

		if ($dryRun) {
			DI::logger()->info("Test Atom/RSS feed");
		} else {
			DI::logger()->info('Import Atom/RSS feed "' . $contact['name'] . '" (Contact ' . $contact['id'] . ') for user ' . $importer['uid']);
		}

		$xml = trim($xml);

		if (empty($xml)) {
			DI::logger()->info('XML is empty.');
			return [];
		}

		$basepath = '';

		if (!empty($contact['poll'])) {
			$basepath = (string) $contact['poll'];
		} elseif (!empty($contact['url'])) {
			$basepath = (string) $contact['url'];
		}

		$doc = new DOMDocument();
		@$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);

		$xpath->registerNamespace('atom', ActivityNamespace::ATOM1);
		$xpath->registerNamespace('atom03', ActivityNamespace::ATOM03);
		$xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		$xpath->registerNamespace('content', 'http://purl.org/rss/1.0/modules/content/');
		$xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		$xpath->registerNamespace('rss', 'http://purl.org/rss/1.0/');
		$xpath->registerNamespace('media', 'http://search.yahoo.com/mrss/');
		$xpath->registerNamespace('poco', ActivityNamespace::POCO);

		$author   = [];
		$atomns   = 'atom';
		$entries  = null;
		$protocol = Conversation::PARCEL_UNKNOWN;

		// Is it RDF?
		if ($xpath->query('/rdf:RDF/rss:channel')->length > 0) {
			$protocol              = Conversation::PARCEL_RDF;
			$author['author-link'] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:link/text()');
			$author['author-name'] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:title/text()');

			if (empty($author['author-name'])) {
				$author['author-name'] = XML::getFirstNodeValue($xpath, '/rdf:RDF/rss:channel/rss:description/text()');
			}
			$entries = $xpath->query('/rdf:RDF/rss:item');
		}

		if ($xpath->query('/opml')->length > 0) {
			$protocol              = Conversation::PARCEL_OPML;
			$author['author-name'] = XML::getFirstNodeValue($xpath, '/opml/head/title/text()');
			$entries               = $xpath->query('/opml/body/outline');
		}

		// Is it Atom?
		if ($xpath->query('/atom:feed')->length > 0) {
			$protocol = Conversation::PARCEL_ATOM;
		} elseif ($xpath->query('/atom03:feed')->length > 0) {
			$protocol = Conversation::PARCEL_ATOM03;
			$atomns   = 'atom03';
		}

		if (in_array($protocol, [Conversation::PARCEL_ATOM, Conversation::PARCEL_ATOM03])) {
			$alternate = XML::getFirstAttributes($xpath, $atomns . ":link[@rel='alternate']");
			if (is_object($alternate)) {
				foreach ($alternate as $attribute) {
					if ($attribute->name == 'href') {
						$author['author-link'] = $attribute->textContent;
					}
				}
			}

			if (empty($author['author-link'])) {
				$self = XML::getFirstAttributes($xpath, $atomns . ":link[@rel='self']");
				if (is_object($self)) {
					foreach ($self as $attribute) {
						if ($attribute->name == 'href') {
							$author['author-link'] = $attribute->textContent;
						}
					}
				}
			}

			if (empty($author['author-link'])) {
				$author['author-link'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':id/text()');
			}
			$author['author-avatar'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':logo/text()');

			$author['author-name'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':title/text()');

			if (empty($author['author-name'])) {
				$author['author-name'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':subtitle/text()');
			}

			if (empty($author['author-name'])) {
				$author['author-name'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':author/' . $atomns . ':name/text()');
			}

			$value = XML::getFirstNodeValue($xpath, '' . $atomns . ':author/poco:displayName/text()');
			if ($value != '') {
				$author['author-name'] = $value;
			}

			if ($dryRun) {
				$author['author-id'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':author/' . $atomns . ':id/text()');

				// See https://tools.ietf.org/html/rfc4287#section-3.2.2
				$value = XML::getFirstNodeValue($xpath, $atomns . ':author/' . $atomns . ':uri/text()');
				if ($value != '') {
					$author['author-link'] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, $atomns . ':author/poco:preferredUsername/text()');
				if ($value != '') {
					$author['author-nick'] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, $atomns . ':author/poco:address/poco:formatted/text()');
				if ($value != '') {
					$author['author-location'] = $value;
				}

				$value = XML::getFirstNodeValue($xpath, $atomns . ':author/poco:note/text()');
				if ($value != '') {
					$author['author-about'] = $value;
				}

				$avatar = XML::getFirstAttributes($xpath, $atomns . ":author/' . $atomns . ':link[@rel='avatar']");
				if (is_object($avatar)) {
					foreach ($avatar as $attribute) {
						if ($attribute->name == 'href') {
							$author['author-avatar'] = $attribute->textContent;
						}
					}
				}
			}

			$author['edited'] = $author['created'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':updated/text()');

			$author['app'] = XML::getFirstNodeValue($xpath, '/' . $atomns . ':feed/' . $atomns . ':generator/text()');

			$entries = $xpath->query('/' . $atomns . ':feed/' . $atomns . ':entry');
		}

		// Is it RSS?
		if ($xpath->query('/rss/channel')->length > 0) {
			$protocol              = Conversation::PARCEL_RSS;
			$author['author-link'] = XML::getFirstNodeValue($xpath, '/rss/channel/link/text()');

			$author['author-name'] = XML::getFirstNodeValue($xpath, '/rss/channel/title/text()');

			if (empty($author['author-name'])) {
				$author['author-name'] = XML::getFirstNodeValue($xpath, '/rss/channel/copyright/text()');
			}

			if (empty($author['author-name'])) {
				$author['author-name'] = XML::getFirstNodeValue($xpath, '/rss/channel/description/text()');
			}

			$author['author-avatar'] = XML::getFirstNodeValue($xpath, '/rss/channel/image/url/text()');

			if (empty($author['author-avatar'])) {
				$avatar = XML::getFirstAttributes($xpath, '/rss/channel/itunes:image');
				if (is_object($avatar)) {
					foreach ($avatar as $attribute) {
						if ($attribute->name == 'href') {
							$author['author-avatar'] = $attribute->textContent;
						}
					}
				}
			}

			$author['author-about'] = HTML::toBBCode(XML::getFirstNodeValue($xpath, '/rss/channel/description/text()'), $basepath);

			if (empty($author['author-about'])) {
				$author['author-about'] = XML::getFirstNodeValue($xpath, '/rss/channel/itunes:summary/text()');
			}

			$author['edited'] = $author['created'] = XML::getFirstNodeValue($xpath, '/rss/channel/pubDate/text()');

			$author['app'] = XML::getFirstNodeValue($xpath, '/rss/channel/generator/text()');

			$entries = $xpath->query('/rss/channel/item');
		}

		if (!$dryRun) {
			$author['author-link'] = $contact['url'];

			if (empty($author['author-name'])) {
				$author['author-name'] = $contact['name'];
			}

			$author['author-avatar'] = $contact['thumb'];

			$author['owner-link']   = $contact['url'];
			$author['owner-name']   = $contact['name'];
			$author['owner-avatar'] = $contact['thumb'];
		}

		$header = [
			'uid'         => $importer['uid'] ?? 0,
			'network'     => Protocol::FEED,
			'wall'        => 0,
			'origin'      => 0,
			'gravity'     => Item::GRAVITY_PARENT,
			'private'     => Item::PUBLIC,
			'verb'        => Activity::POST,
			'object-type' => Activity\ObjectType::NOTE,
			'post-type'   => Item::PT_ARTICLE,
			'contact-id'  => $contact['id'] ?? 0,
		];

		$datarray['protocol']  = $protocol;
		$datarray['direction'] = Conversation::PULL;

		if (!is_object($entries)) {
			DI::logger()->info("There are no entries in this feed.");
			return [];
		}

		$items          = [];
		$creation_dates = [];

		// Limit the number of items that are about to be fetched
		$total_items = ($entries->length - 1);
		$max_items   = DI::config()->get('system', 'max_feed_items');
		if (($max_items > 0) && ($total_items > $max_items)) {
			$total_items = $max_items;
		}

		$postings = self::importOlderEntries($entries, $total_items, $header, $author, $contact, $importer, $xpath, $atomns, $basepath, $dryRun);

		if (!empty($postings)) {
			$min_posting = DI::config()->get('system', 'minimum_posting_interval', 0);
			$total       = count($postings);
			if ($total > 1) {
				// Posts shouldn't be delayed more than a day
				$interval = min(1440, self::getPollInterval($contact));
				$delay    = max(round(($interval * 60) / $total), 60 * $min_posting);
				DI::logger()->info('Got posting delay', ['delay' => $delay, 'interval' => $interval, 'items' => $total, 'cid' => $contact['id'], 'url' => $contact['url']]);
			} else {
				$delay = 0;
			}

			$post_delay = 0;

			foreach ($postings as $posting) {
				if ($delay > 0) {
					$publish_time = time() + $post_delay;
					$post_delay += $delay;
				} else {
					$publish_time = time();
				}

				$last_publish = DI::pConfig()->get($posting['item']['uid'], 'system', 'last_publish', 0, true);
				$next_publish = max($last_publish + (60 * $min_posting), time());
				if ($publish_time < $next_publish) {
					$publish_time = $next_publish;
				}
				$publish_at = date(DateTimeFormat::MYSQL, $publish_time);

				if (Post\Delayed::add($posting['item']['uri'], $posting['item'], $posting['notify'], Post\Delayed::PREPARED, $publish_at, $posting['taglist'], $posting['attachments'])) {
					DI::pConfig()->set($posting['item']['uid'], 'system', 'last_publish', $publish_time);
				}
			}
		}

		if (!$dryRun && DI::config()->get('system', 'adjust_poll_frequency')) {
			self::adjustPollFrequency($contact, $creation_dates);
		}

		return ['header' => $author, 'items' => $items];
	}

	private static function getTitleFromItemOrEntry(array $item, DOMXPath $xpath, string $atomns, ?DOMNode $entry): string
	{
		$title = (string) $item['title'];

		if (empty($title)) {
			$title = XML::getFirstNodeValue($xpath, $atomns . ':title/text()', $entry);
		}

		if (empty($title)) {
			$title = XML::getFirstNodeValue($xpath, 'title/text()', $entry);
		}

		if (empty($title)) {
			$title = XML::getFirstNodeValue($xpath, 'rss:title/text()', $entry);
		}

		if (empty($title)) {
			$title = XML::getFirstNodeValue($xpath, 'itunes:title/text()', $entry);
		}

		$title = trim(html_entity_decode($title, ENT_QUOTES, 'UTF-8'));

		return $title;
	}

	private static function importOlderEntries(DOMNodeList $entries, int $total_items, array $header, array $author, array $contact, array $importer, DOMXPath $xpath, string $atomns, string $basepath, bool $dryRun): array
	{
		$postings = [];

		// Importing older entries first
		for ($i = $total_items; $i >= 0; --$i) {
			$entry = $entries->item($i);

			$item = array_merge($header, $author);
			$body = '';

			$alternate = XML::getFirstAttributes($xpath, $atomns . ":link[@rel='alternate']", $entry);
			if (!is_object($alternate)) {
				$alternate = XML::getFirstAttributes($xpath, $atomns . ':link', $entry);
			}
			if (is_object($alternate)) {
				foreach ($alternate as $attribute) {
					if ($attribute->name == 'href') {
						$item['plink'] = $attribute->textContent;
					}
				}
			}

			if ($entry->nodeName == 'outline') {
				$isrss = false;
				$plink = '';
				$uri   = '';
				foreach ($entry->attributes as $attribute) {
					switch ($attribute->nodeName) {
						case 'title':
							$item['title'] = $attribute->nodeValue;
							break;

						case 'text':
							$body = $attribute->nodeValue;
							break;

						case 'htmlUrl':
							$plink = $attribute->nodeValue;
							break;

						case 'xmlUrl':
							$uri = $attribute->nodeValue;
							break;

						case 'type':
							$isrss = $attribute->nodeValue == 'rss';
							break;
					}
				}
				$item['plink'] = $plink ?: $uri;
				$item['uri']   = $uri ?: $plink;
				if (!$isrss || empty($item['uri'])) {
					continue;
				}
			}

			if (empty($item['plink'])) {
				$item['plink'] = XML::getFirstNodeValue($xpath, 'link/text()', $entry);
			}

			if (empty($item['plink'])) {
				$item['plink'] = XML::getFirstNodeValue($xpath, 'rss:link/text()', $entry);
			}

			// Add the base path if missing
			$item['plink'] = Network::addBasePath($item['plink'], $basepath);

			if (empty($item['uri'])) {
				$item['uri'] = XML::getFirstNodeValue($xpath, $atomns . ':id/text()', $entry);
			}

			$guid = XML::getFirstNodeValue($xpath, 'guid/text()', $entry);
			$host = self::getHostname($item, $guid, $basepath);
			if (!empty($guid)) {
				if (empty($item['uri'])) {
					$item['uri'] = $guid;
				}

				// Don't use the GUID value directly but instead use it as a basis for the GUID
				$item['guid'] = Item::guidFromUri($guid, $host);
			}

			if (empty($item['uri'])) {
				$item['uri'] = $item['plink'];
			}

			if (!parse_url($item['uri'], PHP_URL_HOST)) {
				$item['uri'] = 'feed::' . $host . ':' . $item['uri'];
			}

			$orig_plink = $item['plink'];

			if (!$dryRun) {
				try {
					$item['plink'] = DI::httpClient()->finalUrl($item['plink']);
				} catch (TransferException $exception) {
					DI::logger()->notice('Item URL couldn\'t get expanded', ['url' => $item['plink'], 'exception' => $exception]);
				}
			}

			$item['title'] = self::getTitleFromItemOrEntry($item, $xpath, $atomns, $entry);

			$published = XML::getFirstNodeValue($xpath, $atomns . ':published/text()', $entry);

			if (empty($published)) {
				$published = XML::getFirstNodeValue($xpath, 'pubDate/text()', $entry);
			}

			if (empty($published)) {
				$published = XML::getFirstNodeValue($xpath, 'dc:date/text()', $entry);
			}

			$updated = XML::getFirstNodeValue($xpath, $atomns . ':updated/text()', $entry);

			if (empty($updated) && !empty($published)) {
				$updated = $published;
			}

			if (empty($published) && !empty($updated)) {
				$published = $updated;
			}

			if ($published != '') {
				$item['created'] = trim($published);
			}

			if ($updated != '') {
				$item['edited'] = trim($updated);
			}

			if (!$dryRun) {
				$condition = [
					"`uid` = ? AND `uri` = ? AND `network` IN (?, ?)",
					$importer['uid'], $item['uri'], Protocol::FEED, Protocol::DFRN
				];
				$previous = Post::selectFirst(['id', 'created'], $condition);
				if (DBA::isResult($previous)) {
					// Use the creation date when the post had been stored. It can happen this date changes in the feed.
					$creation_dates[] = $previous['created'];
					DI::logger()->info('Item with URI ' . $item['uri'] . ' for user ' . $importer['uid'] . ' already existed under id ' . $previous['id']);
					continue;
				}
				$creation_dates[] = DateTimeFormat::utc($item['created']);
			}

			$creator = XML::getFirstNodeValue($xpath, 'author/text()', $entry);

			if (empty($creator)) {
				$creator = XML::getFirstNodeValue($xpath, $atomns . ':author/' . $atomns . ':name/text()', $entry);
			}

			if (empty($creator)) {
				$creator = XML::getFirstNodeValue($xpath, 'dc:creator/text()', $entry);
			}

			if ($creator != '') {
				$item['author-name'] = $creator;
			}

			$creator = XML::getFirstNodeValue($xpath, 'dc:creator/text()', $entry);

			if ($creator != '') {
				$item['author-name'] = $creator;
			}

			/// @TODO ?
			// <category>Ausland</category>
			// <media:thumbnail width="152" height="76" url="http://www.taz.de/picture/667875/192/14388767.jpg"/>

			$attachments = [];

			$enclosures = $xpath->query("enclosure|$atomns:link[@rel='enclosure']", $entry);
			if (!empty($enclosures)) {
				foreach ($enclosures as $enclosure) {
					$href   = '';
					$length = null;
					$type   = null;

					foreach ($enclosure->attributes as $attribute) {
						if (in_array($attribute->name, ['url', 'href'])) {
							$href = $attribute->textContent;
						} elseif ($attribute->name == 'length') {
							$length = (int)$attribute->textContent;
						} elseif ($attribute->name == 'type') {
							$type = $attribute->textContent;
						}
					}

					if (!empty($href)) {
						$attachment = ['uri-id' => -1, 'type' => Post\Media::UNKNOWN, 'url' => $href, 'mimetype' => $type, 'size' => $length];

						$attachment = Post\Media::fetchAdditionalData($attachment);

						// By now we separate the visible media types (audio, video, image) from the rest
						// In the future we should try to avoid the DOCUMENT type and only use the real one - but not in the RC phase.
						if (!in_array($attachment['type'], [Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO])) {
							$attachment['type'] = Post\Media::DOCUMENT;
						}
						$attachments[] = $attachment;
					}
				}
			}

			$taglist    = [];
			$categories = $xpath->query('category', $entry);
			foreach ($categories as $category) {
				$taglist[] = $category->nodeValue;
			}

			if (empty($body)) {
				$body = trim(XML::getFirstNodeValue($xpath, $atomns . ':content/text()', $entry));
			}

			if (empty($body)) {
				$body = trim(XML::getFirstNodeValue($xpath, 'content:encoded/text()', $entry));
			}

			$summary = trim(XML::getFirstNodeValue($xpath, $atomns . ':summary/text()', $entry));

			if (empty($summary)) {
				$summary = trim(XML::getFirstNodeValue($xpath, 'description/text()', $entry));
			}

			if (empty($body)) {
				$body    = $summary;
				$summary = '';
			}

			// remove the content of the title if it is identically to the body
			// This helps with auto generated titles e.g. from tumblr
			if (self::titleIsBody($item['title'], $body)) {
				$item['title'] = '';
			}

			$item['body'] = self::formatBody($body, $basepath);
			$summary      = self::formatBody($summary, $basepath);

			if (($item['body'] == '') && ($item['title'] != '')) {
				$item['body']  = $item['title'];
				$item['title'] = '';
			}

			if ($dryRun) {
				$item['attachments'] = $attachments;
				$items[]             = $item;
				break;
			} elseif (!Item::isValid($item)) {
				DI::logger()->info('Feed item is invalid', ['created' => $item['created'], 'uid' => $item['uid'], 'uri' => $item['uri']]);
				continue;
			} elseif (DI::contentItem()->isTooOld($item['created'], $item['uid'])) {
				DI::logger()->info('Feed is too old', ['created' => $item['created'], 'uid' => $item['uid'], 'uri' => $item['uri']]);
				continue;
			}

			if (!empty($item['plink'])) {
				$fetch_further_information = $contact['fetch_further_information'] ?? LocalRelationship::FFI_NONE;
			} else {
				$fetch_further_information = LocalRelationship::FFI_NONE;
			}

			$preview = '';
			if (in_array($fetch_further_information, [LocalRelationship::FFI_INFORMATION, LocalRelationship::FFI_BOTH])) {
				// Handle enclosures and treat them as preview picture
				foreach ($attachments as $attachment) {
					if (Images::isSupportedMimeType($attachment['mimetype'])) {
						$preview = $attachment['url'];
					}
				}

				// Remove a possible link to the item itself
				$item['body'] = str_replace($item['plink'], '', $item['body']);
				$item['body'] = trim(preg_replace('/\[url\=\](\w+.*?)\[\/url\]/i', '', $item['body']));

				$summary = str_replace($item['plink'], '', $summary);
				$summary = trim(preg_replace('/\[url\=\](\w+.*?)\[\/url\]/i', '', $summary));

				if (!empty($summary) && self::replaceBodyWithTitle($summary, $item['title'])) {
					$summary = '';
				}

				$saved_body  = $item['body'];
				$saved_title = $item['title'];

				if (self::replaceBodyWithTitle($item['body'], $item['title'])) {
					$item['body'] = $summary ?: $item['title'];
				}

				$data = ParseUrl::getSiteinfoCached($item['plink']);
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

				$data = PageInfo::queryUrl(
					$item['plink'],
					false,
					$fetch_further_information == LocalRelationship::FFI_BOTH,
					$contact['ffi_keyword_denylist'] ?? ''
				);

				if (!empty($data)) {
					// Take the data that was provided by the feed if the query is empty
					if (($data['type'] == 'link') && empty($data['title']) && empty($data['text'])) {
						$data['title'] = $saved_title;
						$item['body']  = $saved_body;
					}

					$data_text = strip_tags(trim($data['text'] ?? ''));
					$item_body = strip_tags(trim($item['body'] ?? ''));

					if (!empty($data_text) && (($data_text == $item_body) || strstr($item_body, $data_text))) {
						$data['text'] = '';
					}

					// We always strip the title since it will be added in the page information
					$item['title']       = '';
					$item['body']        = $item['body'] . "\n" . PageInfo::getFooterFromData($data, false);
					$taglist             = $fetch_further_information == LocalRelationship::FFI_BOTH ? PageInfo::getTagsFromUrl($item['plink'], $preview, $contact['ffi_keyword_denylist'] ?? '') : [];
					$item['object-type'] = Activity\ObjectType::BOOKMARK;
					$attachments         = [];

					foreach (['audio', 'video'] as $elementname) {
						if (!empty($data[$elementname])) {
							foreach ($data[$elementname] as $element) {
								if (!empty($element['src'])) {
									$src = $element['src'];
								} elseif (!empty($element['content'])) {
									$src = $element['content'];
								} else {
									continue;
								}

								$attachments[] = [
									'type'        => ($elementname == 'audio') ? Post\Media::AUDIO : Post\Media::VIDEO,
									'url'         => $src,
									'preview'     => $element['image']       ?? null,
									'mimetype'    => $element['contenttype'] ?? null,
									'name'        => $element['name']        ?? null,
									'description' => $element['description'] ?? null,
								];
							}
						}
					}
				}
			} else {
				if ($fetch_further_information == LocalRelationship::FFI_KEYWORD) {
					if (empty($taglist)) {
						$taglist = PageInfo::getTagsFromUrl($item['plink'], $preview, $contact['ffi_keyword_denylist'] ?? '');
					}
					$item['body'] .= "\n" . self::tagToString($taglist);
				} else {
					$taglist = [];
				}

				// Add the link to the original feed entry if not present in feed
				if (($item['plink'] != '') && !strstr($item['body'], $item['plink']) && !in_array($item['plink'], array_column($attachments, 'url'))) {
					$item['body'] .= '[hr][url]' . $item['plink'] . '[/url]';
				}
			}

			if (empty($item['title'])) {
				$item['post-type'] = Item::PT_NOTE;
			}

			DI::logger()->info('Stored feed', ['item' => $item]);

			$notify       = Item::isRemoteSelf($contact, $item);
			$item['wall'] = (bool)$notify;

			// Distributed items should have a well-formatted URI.
			// Additionally, we have to avoid conflicts with identical URI between imported feeds and these items.
			if ($notify) {
				$item['guid'] = Item::guidFromUri($orig_plink, DI::baseUrl()->getHost());
				$item['uri']  = Item::newURI($item['guid']);
				unset($item['plink']);
				unset($item['thr-parent']);
				unset($item['parent-uri']);

				// Set the delivery priority for "remote self" to "medium"
				$notify = Worker::PRIORITY_MEDIUM;
			}

			$condition = ['uid' => $item['uid'], 'uri' => $item['uri']];
			if (!Post::exists($condition) && !Post\Delayed::exists($item['uri'], $item['uid'])) {
				if (!$notify) {
					Post\Delayed::publish($item, $notify, $taglist, $attachments);
				} else {
					$postings[] = [
						'item'    => $item, 'notify' => $notify,
						'taglist' => $taglist, 'attachments' => $attachments
					];
				}
			} else {
				DI::logger()->info('Post already created or exists in the delayed posts queue', ['uid' => $item['uid'], 'uri' => $item['uri']]);
			}
		}

		return $postings;
	}

	/**
	 * Return the hostname out of a variety of provided URL
	 *
	 * @param array $item
	 * @param string|null $guid
	 * @param string|null $basepath
	 * @return string
	 */
	private static function getHostname(array $item, string $guid = null, string $basepath = null): string
	{
		$host = parse_url($item['plink'], PHP_URL_HOST);
		if (!empty($host)) {
			return $host;
		}

		$host = parse_url($item['uri'], PHP_URL_HOST);
		if (!empty($host)) {
			return $host;
		}

		$host = parse_url($guid, PHP_URL_HOST);
		if (!empty($host)) {
			return $host;
		}

		$host = parse_url($item['author-link'], PHP_URL_HOST);
		if (!empty($host)) {
			return $host;
		}

		return parse_url($basepath, PHP_URL_HOST);
	}
	/**
	 * Automatically adjust the poll frequency according to the post frequency
	 *
	 * @param array $contact Contact array
	 * @param array $creation_dates
	 * @return void
	 */
	private static function adjustPollFrequency(array $contact, array $creation_dates)
	{
		if ($contact['network'] != Protocol::FEED) {
			DI::logger()->info('Contact is no feed, skip.', ['id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url'], 'network' => $contact['network']]);
			return;
		}

		if (!empty($creation_dates)) {
			// Count the post frequency and the earliest and latest post date
			$frequency   = [];
			$oldest      = time();
			$newest      = 0;
			$newest_date = '';

			foreach ($creation_dates as $date) {
				$timestamp = strtotime($date);
				$day       = intdiv($timestamp, 86400);
				$hour      = $timestamp % 86400;

				// Only have a look at values from the last seven days
				if (((time() / 86400) - $day) < 7) {
					if (empty($frequency[$day])) {
						$frequency[$day] = ['count' => 1, 'low' => $hour, 'high' => $hour];
					} else {
						++$frequency[$day]['count'];
						if ($frequency[$day]['low'] > $hour) {
							$frequency[$day]['low'] = $hour;
						}
						if ($frequency[$day]['high'] < $hour) {
							$frequency[$day]['high'] = $hour;
						}
					}
				}
				if ($oldest > $day) {
					$oldest = $day;
				}

				if ($newest < $day) {
					$newest      = $day;
					$newest_date = $date;
				}
			}

			if (count($creation_dates) == 1) {
				DI::logger()->info('Feed had posted a single time, switching to daily polling', ['newest' => $newest_date, 'id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
				$priority = 8; // Poll once a day
			}

			if (empty($priority) && (((time() / 86400) - $newest) > 730)) {
				DI::logger()->info('Feed had not posted for two years, switching to monthly polling', ['newest' => $newest_date, 'id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
				$priority = 10; // Poll every month
			}

			if (empty($priority) && (((time() / 86400) - $newest) > 365)) {
				DI::logger()->info('Feed had not posted for a year, switching to weekly polling', ['newest' => $newest_date, 'id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
				$priority = 9; // Poll every week
			}

			if (empty($priority) && empty($frequency)) {
				DI::logger()->info('Feed had not posted for at least a week, switching to daily polling', ['newest' => $newest_date, 'id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
				$priority = 8; // Poll once a day
			}

			if (empty($priority)) {
				// Calculate the highest "posts per day" value
				$max = 0;
				foreach ($frequency as $entry) {
					if (($entry['count'] == 1) || ($entry['high'] == $entry['low'])) {
						continue;
					}

					// We take the earliest and latest post day and interpolate the number of post per day
					// that would had been created with this post frequency

					// Assume at least four hours between oldest and newest post per day - should be okay for news outlets
					$duration = max($entry['high'] - $entry['low'], 14400);
					$ppd      = (86400 / $duration) * $entry['count'];
					if ($ppd > $max) {
						$max = $ppd;
					}
				}
				if ($max > 48) {
					$priority = 1; // Poll every quarter hour
				} elseif ($max > 24) {
					$priority = 2; // Poll half an hour
				} elseif ($max > 12) {
					$priority = 3; // Poll hourly
				} elseif ($max > 8) {
					$priority = 4; // Poll every two hours
				} elseif ($max > 4) {
					$priority = 5; // Poll every three hours
				} elseif ($max > 2) {
					$priority = 6; // Poll every six hours
				} else {
					$priority = 7; // Poll twice a day
				}
				DI::logger()->info('Calculated priority by the posts per day', ['priority' => $priority, 'max' => round($max, 2), 'id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
			}
		} else {
			DI::logger()->info('No posts, switching to daily polling', ['id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
			$priority = 8; // Poll once a day
		}

		if ($contact['rating'] != $priority) {
			DI::logger()->notice('Adjusting priority', ['old' => $contact['rating'], 'new' => $priority, 'id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $contact['url']]);
			Contact::update(['rating' => $priority], ['id' => $contact['id']]);
		}
	}

	/**
	 * Get the poll interval for the given contact array
	 *
	 * @param array $contact
	 * @return int Poll interval in minutes
	 */
	public static function getPollInterval(array $contact): int
	{
		if (in_array($contact['network'], [Protocol::MAIL, Protocol::FEED])) {
			$ratings = [0, 3, 7, 8, 9, 10];
			if (DI::config()->get('system', 'adjust_poll_frequency') && ($contact['network'] == Protocol::FEED)) {
				$rating = $contact['rating'];
			} elseif (array_key_exists($contact['priority'], $ratings)) {
				$rating = $ratings[$contact['priority']];
			} else {
				$rating = -1;
			}
		} else {
			// Check once a week per default for all other networks
			$rating = 9;
		}

		// Check archived contacts or contacts with unsupported protocols once a month
		if ($contact['archive'] || in_array($contact['network'], [Protocol::ZOT, Protocol::PHANTOM])) {
			$rating = 10;
		}

		if ($rating < 0) {
			return 0;
		}
		/*
		 * Based on $contact['priority'], should we poll this site now? Or later?
		 */

		$min_poll_interval = max(1, DI::config()->get('system', 'min_poll_interval'));

		$poll_intervals = [$min_poll_interval, 15, 30, 60, 120, 180, 360, 720, 1440, 10080, 43200];

		//$poll_intervals = [$min_poll_interval . ' minute', '15 minute', '30 minute',
		//	'1 hour', '2 hour', '3 hour', '6 hour', '12 hour' ,'1 day', '1 week', '1 month'];

		return $poll_intervals[$rating];
	}

	/**
	 * Convert a tag array to a tag string
	 *
	 * @param array $tags
	 * @return string tag string
	 */
	private static function tagToString(array $tags): string
	{
		$tagstr = '';

		foreach ($tags as $tag) {
			if ($tagstr != '') {
				$tagstr .= ', ';
			}

			$tagstr .= '#[url=' . DI::baseUrl() . '/search?tag=' . urlencode($tag) . ']' . $tag . '[/url]';
		}

		return $tagstr;
	}

	private static function titleIsBody(string $title, string $body): bool
	{
		$title = strip_tags($title);
		$title = trim($title);
		$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
		$title = str_replace(["\n", "\r", "\t", " "], ['', '', '', ''], $title);

		$body = strip_tags($body);
		$body = trim($body);
		$body = html_entity_decode($body, ENT_QUOTES, 'UTF-8');
		$body = str_replace(["\n", "\r", "\t", " "], ['', '', '', ''], $body);

		if (strlen($title) < strlen($body)) {
			$body = substr($body, 0, strlen($title));
		}

		if (($title != $body) && (substr($title, -3) == '...')) {
			$pos = strrpos($title, '...');
			if ($pos > 0) {
				$title = substr($title, 0, $pos);
				$body  = substr($body, 0, $pos);
			}
		}
		return ($title == $body);
	}

	/**
	 * Creates the Atom feed for a given nickname
	 *
	 * Supported filters:
	 * - activity (default): all the public posts
	 * - posts: all the public top-level posts
	 * - comments: all the public replies
	 *
	 * Updates the provided last_update parameter if the result comes from the
	 * cache or it is empty
	 *
	 * @param array   $owner       owner-view record of the feed owner
	 * @param string  $last_update Date of the last update
	 * @param integer $max_items   Number of maximum items to fetch
	 * @param string  $filter      Feed items filter (activity, posts or comments)
	 * @param boolean $nocache     Wether to bypass caching
	 *
	 * @return string Atom feed
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function atom(array $owner, string $last_update, int $max_items = 300, string $filter = 'activity', bool $nocache = false)
	{
		$stamp = microtime(true);

		// Display events in the user's timezone
		if (strlen($owner['timezone'])) {
			DI::appHelper()->setTimeZone($owner['timezone']);
		}

		$previous_created = $last_update;

		$check_date = empty($last_update) ? '' : DateTimeFormat::utc($last_update);
		$authorid   = Contact::getIdForURL($owner['url']);

		$condition = [
			"`uid` = ? AND `received` > ? AND NOT `deleted` AND `gravity` IN (?, ?)
			AND `private` != ? AND `visible` AND `wall` AND `parent-network` IN (?, ?, ?)",
			$owner['uid'], $check_date, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT,
			Item::PRIVATE, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA
		];

		if ($filter === 'comments') {
			$condition[0] .= " AND `gravity` = ? ";
			$condition[] = Item::GRAVITY_COMMENT;
		}

		if ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY) {
			$condition[0] .= " AND `contact-id` = ? AND `author-id` = ?";
			$condition[] = $owner['id'];
			$condition[] = $authorid;
		}

		$params = ['order' => ['received' => true], 'limit' => $max_items];

		if ($filter === 'posts') {
			$ret = Post::selectOriginThread(Item::DELIVER_FIELDLIST, $condition, $params);
		} else {
			$ret = Post::selectOrigin(Item::DELIVER_FIELDLIST, $condition, $params);
		}

		$items = Post::toArray($ret);

		$doc               = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = self::addHeader($doc, $owner, $filter);

		foreach ($items as $item) {
			$entry = self::noteEntry($doc, $item, $owner);
			$root->appendChild($entry);

			if ($last_update < $item['created']) {
				$last_update = $item['created'];
			}
		}

		$feeddata = trim($doc->saveXML());

		DI::logger()->info('Feed duration', ['seconds' => number_format(microtime(true) - $stamp, 3), 'nick' => $owner['nickname'], 'filter' => $filter, 'created' => $previous_created]);

		return $feeddata;
	}

	/**
	 * Adds the header elements to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $owner     Contact data of the poster
	 * @param string      $filter    The related feed filter (activity, posts or comments)
	 *
	 * @return DOMElement Header root element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addHeader(DOMDocument $doc, array $owner, string $filter): DOMElement
	{
		$root = $doc->createElementNS(ActivityNamespace::ATOM1, 'feed');
		$doc->appendChild($root);

		$title   = '';
		$selfUri = '/feed/' . $owner['nick'] . '/';
		switch ($filter) {
			case 'activity':
				$title = DI::l10n()->t('%s\'s timeline', $owner['name']);
				$selfUri .= $filter;
				break;
			case 'posts':
				$title = DI::l10n()->t('%s\'s posts', $owner['name']);
				break;
			case 'comments':
				$title = DI::l10n()->t('%s\'s comments', $owner['name']);
				$selfUri .= $filter;
				break;
		}

		$attributes = ['uri' => 'https://friendi.ca', 'version' => App::VERSION . '-' . DB_UPDATE_VERSION];
		XML::addElement($doc, $root, 'generator', App::PLATFORM, $attributes);
		XML::addElement($doc, $root, 'id', DI::baseUrl() . '/profile/' . $owner['nick']);
		XML::addElement($doc, $root, 'title', $title);
		XML::addElement($doc, $root, 'subtitle', sprintf("Updates from %s on %s", $owner['name'], DI::config()->get('config', 'sitename')));
		XML::addElement($doc, $root, 'logo', User::getAvatarUrl($owner, Proxy::SIZE_SMALL));
		XML::addElement($doc, $root, 'updated', DateTimeFormat::utcNow(DateTimeFormat::ATOM));

		$author = self::addAuthor($doc, $owner);
		$root->appendChild($author);

		$attributes = ['href' => $owner['url'], 'rel' => 'alternate', 'type' => 'text/html'];
		XML::addElement($doc, $root, 'link', '', $attributes);

		$attributes = ['href' => DI::baseUrl() . $selfUri, 'rel' => 'self', 'type' => 'application/atom+xml'];
		XML::addElement($doc, $root, 'link', '', $attributes);

		return $root;
	}

	/**
	 * Adds the author element to the XML document
	 *
	 * @param DOMDocument $doc          XML document
	 * @param array       $owner        Contact data of the poster
	 * @return DOMElement author element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function addAuthor(DOMDocument $doc, array $owner): DOMElement
	{
		$author = $doc->createElement('author');
		XML::addElement($doc, $author, 'uri', $owner['url']);
		XML::addElement($doc, $author, 'name', $owner['nick']);
		XML::addElement($doc, $author, 'email', $owner['addr']);

		return $author;
	}

	/**
	 * Adds a regular entry element
	 *
	 * @param DOMDocument $doc       XML document
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 * @return DOMElement Entry element
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function noteEntry(DOMDocument $doc, array $item, array $owner): DOMElement
	{
		if (($item['gravity'] != Item::GRAVITY_PARENT) && (Strings::normaliseLink($item['author-link']) != Strings::normaliseLink($owner['url']))) {
			DI::logger()->info('Feed entry author does not match feed owner', ['owner' => $owner['url'], 'author' => $item['author-link']]);
		}

		$entry = self::entryHeader($doc, $owner, $item, false);

		self::entryContent($doc, $entry, $item, self::getTitle($item), '', true);

		self::entryFooter($doc, $entry, $item, $owner);

		return $entry;
	}

	/**
	 * Adds elements to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param \DOMElement $entry     Entry element where the content is added
	 * @param array       $item      Data of the item that is to be posted
	 * @param string      $title     Title for the post
	 * @param string      $verb      The activity verb
	 * @param bool        $complete  Add the "status_net" element?
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function entryContent(DOMDocument $doc, DOMElement $entry, array $item, $title, string $verb = '', bool $complete = true): void
	{
		if ($verb == '') {
			$verb = self::constructVerb($item);
		}

		XML::addElement($doc, $entry, 'id', $item['uri']);
		XML::addElement($doc, $entry, 'title', html_entity_decode($title, ENT_QUOTES, 'UTF-8'));

		$body = Post\Media::addAttachmentsToBody($item['uri-id'], DI::contentItem()->addSharedPost($item));
		$body = Post\Media::addHTMLLinkToBody($item['uri-id'], $body);

		$body = BBCode::convertForUriId($item['uri-id'], $body, BBCode::ACTIVITYPUB);

		XML::addElement($doc, $entry, 'content', $body, ['type' => 'html']);

		XML::addElement(
			$doc,
			$entry,
			'link',
			'',
			[
				'rel'  => 'alternate', 'type' => 'text/html',
				'href' => DI::baseUrl() . '/display/' . $item['guid']
			]
		);

		XML::addElement($doc, $entry, 'published', DateTimeFormat::utc($item['created'] . '+00:00', DateTimeFormat::ATOM));
		XML::addElement($doc, $entry, 'updated', DateTimeFormat::utc($item['edited'] . '+00:00', DateTimeFormat::ATOM));
	}

	/**
	 * Adds the elements at the foot of an entry to the XML document
	 *
	 * @param DOMDocument $doc       XML document
	 * @param object      $entry     The entry element where the elements are added
	 * @param array       $item      Data of the item that is to be posted
	 * @param array       $owner     Contact data of the poster
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function entryFooter(DOMDocument $doc, $entry, array $item, array $owner): void
	{
		$mentioned = [];

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			$parent = Post::selectFirst(['guid', 'author-link', 'owner-link'], ['id' => $item['parent']]);

			$thrparent = Post::selectFirst(['guid', 'author-link', 'owner-link', 'plink'], ['uid' => $owner['uid'], 'uri' => $item['thr-parent']]);

			if (DBA::isResult($thrparent)) {
				$mentioned[$thrparent['author-link']] = $thrparent['author-link'];
				$mentioned[$thrparent['owner-link']]  = $thrparent['owner-link'];
				$parent_plink                         = $thrparent['plink'];
			} elseif (DBA::isResult($parent)) {
				$mentioned[$parent['author-link']] = $parent['author-link'];
				$mentioned[$parent['owner-link']]  = $parent['owner-link'];
				$parent_plink                      = DI::baseUrl() . '/display/' . $parent['guid'];
			} else {
				DI::logger()->notice('Missing parent and thr-parent for child item', ['item' => $item]);
			}

			if (isset($parent_plink)) {
				$attributes = [
					'ref'  => $item['thr-parent'],
					'href' => $parent_plink
				];
				XML::addElement($doc, $entry, 'thr:in-reply-to', '', $attributes);

				$attributes = [
					'rel'  => 'related',
					'href' => $parent_plink
				];
				XML::addElement($doc, $entry, 'link', '', $attributes);
			}
		}

		// uri-id isn't present for follow entry pseudo-items
		$tags = Tag::getByURIId($item['uri-id'] ?? 0);
		foreach ($tags as $tag) {
			$mentioned[$tag['url']] = $tag['url'];
		}

		foreach ($tags as $tag) {
			if ($tag['type'] == Tag::HASHTAG) {
				XML::addElement($doc, $entry, 'category', '', ['term' => $tag['name']]);
			}
		}

		self::getAttachment($doc, $entry, $item);
	}

	/**
	 * Fetch or create title for feed entry
	 *
	 * @param array $item
	 * @return string title
	 */
	private static function getTitle(array $item): string
	{
		if ($item['title'] != '') {
			return BBCode::convertForUriId($item['uri-id'], $item['title'], BBCode::ACTIVITYPUB);
		}

		// Fetch information about the post
		$media = Post\Media::getByURIId($item['uri-id'], [Post\Media::HTML]);
		if (!empty($media) && !empty($media[0]['name']) && ($media[0]['name'] != $media[0]['url'])) {
			return $media[0]['name'];
		}

		// If no bookmark is found then take the first line
		// Remove the share element before fetching the first line
		$title = trim(preg_replace("/\[share.*?\](.*?)\[\/share\]/ism", "\n$1\n", $item['body']));

		$title   = BBCode::toPlaintext($title) . "\n";
		$pos     = strpos($title, "\n");
		$trailer = '';
		if (($pos == 0) || ($pos > 100)) {
			$pos     = 100;
			$trailer = '...';
		}

		return substr($title, 0, $pos) . $trailer;
	}

	private static function formatBody(string $body, string $basepath): string
	{
		if (!HTML::isHTML($body)) {
			$html = BBCode::convert($body, false, BBCode::EXTERNAL);
			if ($body != $html) {
				DI::logger()->debug('Body contained no HTML', ['original' => $body, 'converted' => $html]);
				$body = $html;
			}
		}

		$body = HTML::toBBCode($body, $basepath);

		// Remove tracking pixels
		return preg_replace("/\[img=1x1\]([^\[\]]*)\[\/img\]/Usi", '', $body);
	}

	private static function replaceBodyWithTitle(string $body, string $title): bool
	{
		// Replace the content when the title is longer than the body
		$replace = (strlen($title) > strlen($body));

		// Replace it, when there is an image in the body
		if (strstr($body, '[/img]')) {
			$replace = true;
		}

		// Replace it, when there is a link in the body
		if (strstr($body, '[/url]')) {
			$replace = true;
		}
		return $replace;
	}

	/**
	 * Adds attachment data to the XML document
	 *
	 * @param DOMDocument $doc  XML document
	 * @param DOMElement  $root XML root element where the hub links are added
	 * @param array       $item Data of the item that is to be posted
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getAttachment(DOMDocument $doc, DOMElement $root, array $item)
	{
		foreach (Post\Media::getByURIId($item['uri-id'], [Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO, Post\Media::DOCUMENT, Post\Media::TORRENT]) as $attachment) {
			$attributes = ['rel' => 'enclosure',
				'href'              => $attachment['url'],
				'type'              => $attachment['mimetype']];

			if (!empty($attachment['size'])) {
				$attributes['length'] = intval($attachment['size']);
			}
			if (!empty($attachment['description'])) {
				$attributes['title'] = $attachment['description'];
			}

			XML::addElement($doc, $root, 'link', '', $attributes);
		}
	}

	/**
	 * @TODO Picture attachments should look like this:
	 *	<a href="https://status.pirati.ca/attachment/572819" title="https://status.pirati.ca/file/heluecht-20151202T222602-rd3u49p.gif"
	 *	class="attachment thumbnail" id="attachment-572819" rel="nofollow external">https://status.pirati.ca/attachment/572819</a>
	 */

	/**
	 * Returns the given activity if present - otherwise returns the "post" activity
	 *
	 * @param array $item Data of the item that is to be posted
	 * @return string activity
	 */
	private static function constructVerb(array $item): string
	{
		if (!empty($item['verb'])) {
			return $item['verb'];
		}

		return Activity::POST;
	}

	/**
	 * Adds a header element to the XML document
	 *
	 * @param DOMDocument $doc      XML document
	 * @param array       $owner    Contact data of the poster
	 * @param array       $item
	 * @param bool        $toplevel Is it for en entry element (false) or a feed entry (true)?
	 * @return DOMElement The entry element where the elements are added
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function entryHeader(DOMDocument $doc, array $owner, array $item, bool $toplevel): DOMElement
	{
		if (!$toplevel) {
			$entry = $doc->createElement('entry');

			if ($owner['contact-type'] == Contact::TYPE_COMMUNITY) {
				$entry->setAttribute('xmlns:activity', ActivityNamespace::ACTIVITY);

				$contact             = Contact::getByURL($item['author-link']) ?: $owner;
				$contact['nickname'] = $contact['nickname'] ?? $contact['nick'];
				$author              = self::addAuthor($doc, $contact);
				$entry->appendChild($author);
			}
		} else {
			$entry = $doc->createElementNS(ActivityNamespace::ATOM1, 'entry');

			$entry->setAttribute('xmlns:thr', ActivityNamespace::THREAD);
			$entry->setAttribute('xmlns:poco', ActivityNamespace::POCO);

			$author = self::addAuthor($doc, $owner);
			$entry->appendChild($author);
		}

		return $entry;
	}
}
