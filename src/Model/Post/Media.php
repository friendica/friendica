<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model\Post;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Network\HTTPClientOptions;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;

/**
 * Class Media
 *
 * This Model class handles media interactions.
 * This tables stores medias (images, videos, audio files) related to posts.
 */
class Media
{
	const UNKNOWN     = 0;
	const IMAGE       = 1;
	const VIDEO       = 2;
	const AUDIO       = 3;
	const TEXT        = 4;
	const APPLICATION = 5;
	const TORRENT     = 16;
	const HTML        = 17;
	const XML         = 18;
	const PLAIN       = 19;
	const DOCUMENT    = 128;

	/**
	 * Insert a post-media record
	 *
	 * @param array $media
	 * @return void
	 */
	public static function insert(array $media, bool $force = false)
	{
		if (empty($media['url']) || empty($media['uri-id']) || !isset($media['type'])) {
			Logger::warning('Incomplete media data', ['media' => $media]);
			return;
		}

		if (DBA::exists('post-media', ['uri-id' => $media['uri-id'], 'preview' => $media['url']])) {
			Logger::info('Media already exists as preview', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'callstack' => System::callstack()]);
			return;
		}

		// "document" has got the lowest priority. So when the same file is both attached as document
		// and embedded as picture then we only store the picture or replace the document
		$found = DBA::selectFirst('post-media', ['type'], ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
		if (!$force && !empty($found) && (($found['type'] != self::DOCUMENT) || ($media['type'] == self::DOCUMENT))) {
			Logger::info('Media already exists', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'callstack' => System::callstack()]);
			return;
		}

		$media = self::unsetEmptyFields($media);

		// We are storing as fast as possible to avoid duplicated network requests
		// when fetching additional information for pictures and other content.
		$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
		Logger::info('Stored media', ['result' => $result, 'media' => $media, 'callstack' => System::callstack()]);
		$stored = $media;

		$media = self::fetchAdditionalData($media);
		$media = self::unsetEmptyFields($media);

		if (array_diff_assoc($media, $stored)) {
			$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
			Logger::info('Updated media', ['result' => $result, 'media' => $media]);
		} else {
			Logger::info('Nothing to update', ['media' => $media]);
		}
	}

	/**
	 * Remove empty media fields
	 *
	 * @param array $media
	 * @return array cleaned media array
	 */
	private static function unsetEmptyFields(array $media)
	{
		$fields = ['mimetype', 'height', 'width', 'size', 'preview', 'preview-height', 'preview-width', 'description'];
		foreach ($fields as $field) {
			if (empty($media[$field])) {
				unset($media[$field]);
			}
		}
		return $media;
	}

	/**
	 * Copy attachments from one uri-id to another
	 *
	 * @param integer $from_uri_id
	 * @param integer $to_uri_id
	 * @return void
	 */
	public static function copy(int $from_uri_id, int $to_uri_id)
	{
		$attachments = self::getByURIId($from_uri_id);
		foreach ($attachments as $attachment) {
			$attachment['uri-id'] = $to_uri_id;
			self::insert($attachment);
		}
	}

	/**
	 * Creates the "[attach]" element from the given attributes
	 *
	 * @param string $href
	 * @param integer $length
	 * @param string $type
	 * @param string $title
	 * @return string "[attach]" element
	 */
	public static function getAttachElement(string $href, int $length, string $type, string $title = '')
	{
		$media = self::fetchAdditionalData(['type' => self::DOCUMENT, 'url' => $href,
			'size' => $length, 'mimetype' => $type, 'description' => $title]);

		return '[attach]href="' . $media['url'] . '" length="' . $media['size'] .
			'" type="' . $media['mimetype'] . '" title="' . $media['description'] . '"[/attach]';
	}

	/**
	 * Fetch additional data for the provided media array
	 *
	 * @param array $media
	 * @return array media array with additional data
	 */
	public static function fetchAdditionalData(array $media)
	{
		if (Network::isLocalLink($media['url'])) {
			$media = self::fetchLocalData($media);
		}

		// Fetch the mimetype or size if missing.
		if (empty($media['mimetype']) || empty($media['size'])) {
			$timeout = DI::config()->get('system', 'xrd_timeout');
			$curlResult = DI::httpClient()->head($media['url'], [HTTPClientOptions::TIMEOUT => $timeout]);
			if ($curlResult->isSuccess()) {
				if (empty($media['mimetype'])) {
					$media['mimetype'] = $curlResult->getHeader('Content-Type')[0] ?? '';
				}
				if (empty($media['size'])) {
					$media['size'] = (int)($curlResult->getHeader('Content-Length')[0] ?? 0);
				}
			} else {
				Logger::notice('Could not fetch head', ['media' => $media]);
			}
		}

		$filetype = !empty($media['mimetype']) ? strtolower(current(explode('/', $media['mimetype']))) : '';

		if (($media['type'] == self::IMAGE) || ($filetype == 'image')) {
			$imagedata = Images::getInfoFromURLCached($media['url']);
			if (!empty($imagedata)) {
				$media['mimetype'] = $imagedata['mime'];
				$media['size'] = $imagedata['size'];
				$media['width'] = $imagedata[0];
				$media['height'] = $imagedata[1];
			} else {
				Logger::notice('No image data', ['media' => $media]);
			}
			if (!empty($media['preview'])) {
				$imagedata = Images::getInfoFromURLCached($media['preview']);
				if (!empty($imagedata)) {
					$media['preview-width'] = $imagedata[0];
					$media['preview-height'] = $imagedata[1];
				}
			}
		}

		if ($media['type'] != self::DOCUMENT) {
			$media = self::addType($media);
		}

		if ($media['type'] == self::HTML) {
			$data = ParseUrl::getSiteinfoCached($media['url'], false);
			$media['preview'] = $data['images'][0]['src'] ?? null;
			$media['preview-height'] = $data['images'][0]['height'] ?? null;
			$media['preview-width'] = $data['images'][0]['width'] ?? null;
			$media['description'] = $data['text'] ?? null;
			$media['name'] = $data['title'] ?? null;
			$media['author-url'] = $data['author_url'] ?? null;
			$media['author-name'] = $data['author_name'] ?? null;
			$media['author-image'] = $data['author_img'] ?? null;
			$media['publisher-url'] = $data['publisher_url'] ?? null;
			$media['publisher-name'] = $data['publisher_name'] ?? null;
			$media['publisher-image'] = $data['publisher_img'] ?? null;
		}
		return $media;
	}

	/**
	 * Fetch media data from local resources
	 * @param array $media
	 * @return array media with added data
	 */
	private static function fetchLocalData(array $media)
	{
		if (!preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $media['url'] ?? '', $matches)) {
			return $media;
		}
		$photo = Photo::selectFirst([], ['resource-id' => $matches[1], 'scale' => $matches[2]]);
		if (!empty($photo)) {
			$media['mimetype'] = $photo['type'];
			$media['size'] = $photo['datasize'];
			$media['width'] = $photo['width'];
			$media['height'] = $photo['height'];
		}

		if (!preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $media['preview'] ?? '', $matches)) {
			return $media;
		}
		$photo = Photo::selectFirst([], ['resource-id' => $matches[1], 'scale' => $matches[2]]);
		if (!empty($photo)) {
			$media['preview-width'] = $photo['width'];
			$media['preview-height'] = $photo['height'];
		}

		return $media;
	}

	/**
	 * Add the detected type to the media array
	 *
	 * @param array $data
	 * @return array data array with the detected type
	 */
	public static function addType(array $data)
	{
		if (empty($data['mimetype'])) {
			Logger::info('No MimeType provided', ['media' => $data]);
			return $data;
		}

		$type = explode('/', current(explode(';', $data['mimetype'])));
		if (count($type) < 2) {
			Logger::info('Unknown MimeType', ['type' => $type, 'media' => $data]);
			$data['type'] = self::UNKNOWN;
			return $data;
		}

		$filetype = strtolower($type[0]);
		$subtype = strtolower($type[1]);

		if ($filetype == 'image') {
			$data['type'] = self::IMAGE;
		} elseif ($filetype == 'video') {
			$data['type'] = self::VIDEO;
		} elseif ($filetype == 'audio') {
			$data['type'] = self::AUDIO;
		} elseif (($filetype == 'text') && ($subtype == 'html')) {
			$data['type'] = self::HTML;
		} elseif (($filetype == 'text') && ($subtype == 'xml')) {
			$data['type'] = self::XML;
		} elseif (($filetype == 'text') && ($subtype == 'plain')) {
			$data['type'] = self::PLAIN;
		} elseif ($filetype == 'text') {
			$data['type'] = self::TEXT;
		} elseif (($filetype == 'application') && ($subtype == 'x-bittorrent')) {
			$data['type'] = self::TORRENT;
		} elseif ($filetype == 'application') {
			$data['type'] = self::APPLICATION;
		} else {
			$data['type'] = self::UNKNOWN;
			Logger::info('Unknown type', ['filetype' => $filetype, 'subtype' => $subtype, 'media' => $data]);
			return $data;
		}

		Logger::debug('Detected type', ['filetype' => $filetype, 'subtype' => $subtype, 'media' => $data]);
		return $data;
	}

	/**
	 * Tests for path patterns that are usef for picture links in Friendica
	 *
	 * @param string $page    Link to the image page
	 * @param string $preview Preview picture
	 * @return boolean
	 */
	private static function isPictureLink(string $page, string $preview)
	{
		return preg_match('#/photos/.*/image/#ism', $page) && preg_match('#/photo/.*-1\.#ism', $preview);
	}

	/**
	 * Add media links and remove them from the body
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return string Body without media links
	 */
	public static function insertFromBody(int $uriid, string $body)
	{
		// Simplify image codes
		$unshared_body = $body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);

		// Only remove the shared data from "real" reshares
		$shared = BBCode::fetchShareAttributes($body);
		if (!empty($shared['guid'])) {
			$unshared_body = preg_replace("/\s*\[share .*?\].*?\[\/share\]\s*/ism", '', $body);
		}

		$attachments = [];
		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (!self::isPictureLink($picture[1], $picture[2])) {
					continue;
				}
				$body = str_replace($picture[0], '', $body);
				$image = str_replace('-1.', '-0.', $picture[2]);
				$attachments[$image] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $image,
					'preview' => $picture[2], 'description' => $picture[3]];
			}
		}

		if (preg_match_all("/\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);
				$attachments[$picture[1]] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $picture[1], 'description' => $picture[2]];
			}
		}

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (!self::isPictureLink($picture[1], $picture[2])) {
					continue;
				}
				$body = str_replace($picture[0], '', $body);
				$image = str_replace('-1.', '-0.', $picture[2]);
				$attachments[$image] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $image,
					'preview' => $picture[2], 'description' => null];
			}
		}

		if (preg_match_all("/\[img\]([^\[\]]*)\[\/img\]/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);
				$attachments[$picture[1]] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $picture[1]];
			}
		}

		if (preg_match_all("/\[audio\]([^\[\]]*)\[\/audio\]/ism", $body, $audios, PREG_SET_ORDER)) {
			foreach ($audios as $audio) {
				$body = str_replace($audio[0], '', $body);
				$attachments[$audio[1]] = ['uri-id' => $uriid, 'type' => self::AUDIO, 'url' => $audio[1]];
			}
		}

		if (preg_match_all("/\[video\]([^\[\]]*)\[\/video\]/ism", $body, $videos, PREG_SET_ORDER)) {
			foreach ($videos as $video) {
				$body = str_replace($video[0], '', $body);
				$attachments[$video[1]] = ['uri-id' => $uriid, 'type' => self::VIDEO, 'url' => $video[1]];
			}
		}

		foreach ($attachments as $attachment) {
			// Only store attachments that are part of the unshared body
			if (Item::containsLink($unshared_body, $attachment['preview'] ?? $attachment['url'], $attachment['type'])) {
				self::insert($attachment);
			}
		}

		return trim($body);
	}

	/**
	 * Add media links from a relevant url in the body
	 *
	 * @param integer $uriid
	 * @param string $body
	 */
	public static function insertFromRelevantUrl(int $uriid, string $body)
	{
		// Only remove the shared data from "real" reshares
		$shared = BBCode::fetchShareAttributes($body);
		if (!empty($shared['guid'])) {
			// Don't look at the shared content
			$body = preg_replace("/\s*\[share .*?\].*?\[\/share\]\s*/ism", '', $body);
		}

		// Remove all hashtags and mentions
		$body = preg_replace("/([#@!])\[url\=(.*?)\](.*?)\[\/url\]/ism", '', $body);

		// Search for pure links
		if (preg_match_all("/\[url\](https?:.*?)\[\/url\]/ism", $body, $matches)) {
			foreach ($matches[1] as $url) {
				Logger::info('Got page url (link without description)', ['uri-id' => $uriid, 'url' => $url]);
				self::insert(['uri-id' => $uriid, 'type' => self::UNKNOWN, 'url' => $url]);
			}
		}

		// Search for links with descriptions
		if (preg_match_all("/\[url\=(https?:.*?)\].*?\[\/url\]/ism", $body, $matches)) {
			foreach ($matches[1] as $url) {
				Logger::info('Got page url (link with description)', ['uri-id' => $uriid, 'url' => $url]);
				self::insert(['uri-id' => $uriid, 'type' => self::UNKNOWN, 'url' => $url]);
			}
		}
	}

	/**
	 * Add media links from the attachment field
	 *
	 * @param integer $uriid
	 * @param string $body
	 */
	public static function insertFromAttachmentData(int $uriid, string $body)
	{
		// Don't look at the shared content
		$body = preg_replace("/\s*\[share .*?\].*?\[\/share\]\s*/ism", '', $body);

		$data = BBCode::getAttachmentData($body);
		if (empty($data))  {
			return;
		}

		Logger::info('Adding attachment data', ['data' => $data]);
		$attachment = [
			'uri-id' => $uriid,
			'type' => self::HTML,
			'url' => $data['url'],
			'preview' => $data['preview'] ?? null,
			'description' => $data['description'] ?? null,
			'name' => $data['title'] ?? null,
			'author-url' => $data['author_url'] ?? null,
			'author-name' => $data['author_name'] ?? null,
			'publisher-url' => $data['provider_url'] ?? null,
			'publisher-name' => $data['provider_name'] ?? null,
		];
		if (!empty($data['image'])) {
			$attachment['preview'] = $data['image'];
		}
		self::insert($attachment);
	}

	/**
	 * Add media links from the attach field
	 *
	 * @param integer $uriid
	 * @param string $attach
	 * @return void
	 */
	public static function insertFromAttachment(int $uriid, string $attach)
	{
		if (!preg_match_all('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\"(?: title=\"(.*?)\")?|', $attach, $matches, PREG_SET_ORDER)) {
			return;
		}

		foreach ($matches as $attachment) {
			$media['type'] = self::DOCUMENT;
			$media['uri-id'] = $uriid;
			$media['url'] = $attachment[1];
			$media['size'] = $attachment[2];
			$media['mimetype'] = $attachment[3];
			$media['description'] = $attachment[4] ?? '';

			self::insert($media);
		}
	}

	/**
	 * Retrieves the media attachments associated with the provided item ID.
	 *
	 * @param int $uri_id
	 * @param array $types
	 * @return array
	 * @throws \Exception
	 */
	public static function getByURIId(int $uri_id, array $types = [])
	{
		$condition = ['uri-id' => $uri_id];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::selectToArray('post-media', [], $condition);
	}

	/**
	 * Checks if media attachments are associated with the provided item ID.
	 *
	 * @param int $uri_id
	 * @param array $types
	 * @return array
	 * @throws \Exception
	 */
	public static function existsByURIId(int $uri_id, array $types = [])
	{
		$condition = ['uri-id' => $uri_id];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::exists('post-media', $condition);
	}

	/**
	 * Split the attachment media in the three segments "visual", "link" and "additional"
	 *
	 * @param int    $uri_id
	 * @param string $guid
	 * @param array  $links list of links that shouldn't be added
	 * @return array attachments
	 */
	public static function splitAttachments(int $uri_id, string $guid = '', array $links = [])
	{
		$attachments = ['visual' => [], 'link' => [], 'additional' => []];

		$media = self::getByURIId($uri_id);
		if (empty($media)) {
			return $attachments;
		}

		$height = 0;
		$selected = '';
		$previews = [];

		foreach ($media as $medium) {
			foreach ($links as $link) {
				if (Strings::compareLink($link, $medium['url'])) {
					continue 2;
				}
			}

			// Avoid adding separate media entries for previews
			foreach ($previews as $preview) {
				if (Strings::compareLink($preview, $medium['url'])) {
					continue 2;
				}
			}

			if (!empty($medium['preview'])) {
				$previews[] = $medium['preview'];
			}

			$type = explode('/', current(explode(';', $medium['mimetype'])));
			if (count($type) < 2) {
				Logger::info('Unknown MimeType', ['type' => $type, 'media' => $medium]);
				$filetype = 'unkn';
				$subtype = 'unkn';
			} else {
				$filetype = strtolower($type[0]);
				$subtype = strtolower($type[1]);
			}

			$medium['filetype'] = $filetype;
			$medium['subtype'] = $subtype;

			if ($medium['type'] == self::HTML || (($filetype == 'text') && ($subtype == 'html'))) {
				$attachments['link'][] = $medium;
				continue;
			}

			if (in_array($medium['type'], [self::AUDIO, self::IMAGE]) ||
				in_array($filetype, ['audio', 'image'])) {
				$attachments['visual'][] = $medium;
			} elseif (($medium['type'] == self::VIDEO) || ($filetype == 'video')) {
				if (strpos($medium['url'], $guid) !== false) {
					// Peertube videos are delivered in many different resolutions. We pick a moderate one.
					// By checking against the GUID we also ensure to only work this way on Peertube posts.
					// This wouldn't be executed when someone for example on Mastodon was sharing multiple videos in a single post.
					if (empty($height) || ($height > $medium['height']) && ($medium['height'] >= 480)) {
						$height = $medium['height'];
						$selected = $medium['url'];
					}
					$video[$medium['url']] = $medium;
				} else {
					$attachments['visual'][] = $medium;
				}
			} else {
				$attachments['additional'][] = $medium;
			}
		}
		if (!empty($selected)) {
			$attachments['visual'][] = $video[$selected];
			unset($video[$selected]);
			foreach ($video as $element) {
				$attachments['additional'][] = $element;
			}
		}
		return $attachments;
	}

	/**
	 * Add media attachments to the body
	 *
	 * @param int $uriid
	 * @param string $body
	 * @return string body
	 */
	public static function addAttachmentsToBody(int $uriid, string $body = '')
	{
		if (empty($body)) {
			$item = Post::selectFirst(['body'], ['uri-id' => $uriid]);
			if (!DBA::isResult($item)) {
				return '';
			}
			$body = $item['body'];
		}
		$original_body = $body;

		$body = preg_replace("/\s*\[attachment .*?\].*?\[\/attachment\]\s*/ism", '', $body);

		foreach (self::getByURIId($uriid, [self::IMAGE, self::AUDIO, self::VIDEO]) as $media) {
			if (Item::containsLink($body, $media['preview'] ?? $media['url'], $media['type'])) {
				continue;
			}

			if ($media['type'] == self::IMAGE) {
				if (!empty($media['preview'])) {
					if (!empty($media['description'])) {
						$body .= "\n[url=" . $media['url'] . "][img=" . $media['preview'] . ']' . $media['description'] .'[/img][/url]';
					} else {
						$body .= "\n[url=" . $media['url'] . "][img]" . $media['preview'] .'[/img][/url]';
					}
				} else {
					if (!empty($media['description'])) {
						$body .= "\n[img=" . $media['url'] . ']' . $media['description'] .'[/img]';
					} else {
						$body .= "\n[img]" . $media['url'] .'[/img]';
					}
				}
			} elseif ($media['type'] == self::AUDIO) {
				$body .= "\n[audio]" . $media['url'] . "[/audio]\n";
			} elseif ($media['type'] == self::VIDEO) {
				$body .= "\n[video]" . $media['url'] . "[/video]\n";
			}
		}

		if (preg_match("/.*(\[attachment.*?\].*?\[\/attachment\]).*/ism", $original_body, $match)) {
			$body .= "\n" . $match[1];
		}

		return $body;
	}

	/**
	 * Get preview link for given media id
	 *
	 * @param integer $id   media id
	 * @param string  $size One of the ProxyUtils::SIZE_* constants
	 * @return string preview link
	 */
	public static function getPreviewUrlForId(int $id, string $size = ''):string
	{
		$url = DI::baseUrl() . '/photo/preview/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;
			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;
			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;
			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;
			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}
		return $url . $id;
	}

	/**
	 * Get media link for given media id
	 *
	 * @param integer $id   media id
	 * @param string  $size One of the ProxyUtils::SIZE_* constants
	 * @return string media link
	 */
	public static function getUrlForId(int $id, string $size = ''):string
	{
		$url = DI::baseUrl() . '/photo/media/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;
			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;
			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;
			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;
			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}
		return $url . $id;
	}
}
