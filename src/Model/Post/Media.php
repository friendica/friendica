<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
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
	const ACTIVITY    = 20;
	const ACCOUNT     = 21;
	const DOCUMENT    = 128;

	/**
	 * Insert a post-media record
	 *
	 * @param array $media
	 * @param bool  $force
	 * @return bool
	 */
	public static function insert(array $media, bool $force = false): bool
	{
		if (empty($media['url']) || empty($media['uri-id']) || !isset($media['type'])) {
			Logger::warning('Incomplete media data', ['media' => $media]);
			return false;
		}

		if (DBA::exists('post-media', ['uri-id' => $media['uri-id'], 'preview' => $media['url']])) {
			Logger::info('Media already exists as preview', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'callstack' => System::callstack()]);
			return false;
		}

		// "document" has got the lowest priority. So when the same file is both attached as document
		// and embedded as picture then we only store the picture or replace the document
		$found = DBA::selectFirst('post-media', ['type'], ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
		if (!$force && !empty($found) && (($found['type'] != self::DOCUMENT) || ($media['type'] == self::DOCUMENT))) {
			Logger::info('Media already exists', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'callstack' => System::callstack()]);
			return false;
		}

		if (!ItemURI::exists($media['uri-id'])) {
			Logger::info('Media referenced URI ID not found', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'callstack' => System::callstack()]);
			return false;
		}

		$media = self::unsetEmptyFields($media);
		$media = DI::dbaDefinition()->truncateFieldsForTable('post-media', $media);

		// We are storing as fast as possible to avoid duplicated network requests
		// when fetching additional information for pictures and other content.
		$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
		Logger::info('Stored media', ['result' => $result, 'media' => $media, 'callstack' => System::callstack()]);
		$stored = $media;

		$media = self::fetchAdditionalData($media);
		$media = self::unsetEmptyFields($media);
		$media = DI::dbaDefinition()->truncateFieldsForTable('post-media', $media);

		if (array_diff_assoc($media, $stored)) {
			$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
			Logger::info('Updated media', ['result' => $result, 'media' => $media]);
		} else {
			Logger::info('Nothing to update', ['media' => $media]);
		}
		return $result;
	}

	/**
	 * Remove empty media fields
	 *
	 * @param array $media
	 * @return array cleaned media array
	 */
	private static function unsetEmptyFields(array $media): array
	{
		$fields = ['mimetype', 'height', 'width', 'size', 'preview', 'preview-height', 'preview-width', 'blurhash', 'description'];
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
	public static function getAttachElement(string $href, int $length, string $type, string $title = ''): string
	{
		$media = self::fetchAdditionalData([
			'type' => self::DOCUMENT, 'url' => $href,
			'size' => $length, 'mimetype' => $type, 'description' => $title
		]);

		return '[attach]href="' . $media['url'] . '" length="' . $media['size'] .
			'" type="' . $media['mimetype'] . '" title="' . $media['description'] . '"[/attach]';
	}

	/**
	 * Fetch additional data for the provided media array
	 *
	 * @param array $media
	 * @return array media array with additional data
	 */
	public static function fetchAdditionalData(array $media): array
	{
		if (Network::isLocalLink($media['url'])) {
			$media = self::fetchLocalData($media);
		}

		// Fetch the mimetype or size if missing.
		if (Network::isValidHttpUrl($media['url']) && (empty($media['mimetype']) || empty($media['size']))) {
			$timeout = DI::config()->get('system', 'xrd_timeout');
			$curlResult = DI::httpClient()->head($media['url'], [HttpClientOptions::TIMEOUT => $timeout]);

			// Workaround for systems that can't handle a HEAD request
			if (!$curlResult->isSuccess() && ($curlResult->getReturnCode() == 405)) {
				$curlResult = DI::httpClient()->get($media['url'], HttpClientAccept::DEFAULT, [HttpClientOptions::TIMEOUT => $timeout]);
			}

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
			if ($imagedata) {
				$media['mimetype'] = $imagedata['mime'];
				$media['size'] = $imagedata['size'];
				$media['width'] = $imagedata[0];
				$media['height'] = $imagedata[1];
				$media['blurhash'] = $imagedata['blurhash'] ?? null;
			} else {
				Logger::notice('No image data', ['media' => $media]);
			}
			if (!empty($media['preview'])) {
				$imagedata = Images::getInfoFromURLCached($media['preview']);
				if ($imagedata) {
					$media['preview-width'] = $imagedata[0];
					$media['preview-height'] = $imagedata[1];
				}
			}
		}

		if ($media['type'] != self::DOCUMENT) {
			$media = self::addType($media);
		}

		if (in_array($media['type'], [self::TEXT, self::APPLICATION, self::HTML, self::XML, self::PLAIN])) {
			$media = self::addActivity($media);
		}

		if (in_array($media['type'], [self::TEXT, self::APPLICATION, self::HTML, self::XML, self::PLAIN])) {
			$media = self::addAccount($media);
		}

		if ($media['type'] == self::HTML) {
			$media = self::addPage($media);
		}

		return $media;
	}

	/**
	 * Adds the activity type if the media entry is linked to an activity
	 *
	 * @param array $media
	 * @return array
	 */
	private static function addActivity(array $media): array
	{
		$id = Item::fetchByLink($media['url']);
		if (empty($id)) {
			return $media;
		}

		$item = Post::selectFirst([], ['id' => $id, 'network' => Protocol::FEDERATED]);
		if (empty($item['id'])) {
			Logger::debug('Not a federated activity', ['id' => $id, 'uri-id' => $media['uri-id'], 'url' => $media['url']]);
			return $media;
		}

		if (
			!empty($item['plink']) && Strings::compareLink($item['plink'], $media['url']) &&
			parse_url($item['plink'], PHP_URL_HOST) != parse_url($item['uri'], PHP_URL_HOST)
		) {
			Logger::debug('Not a link to an activity', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'plink' => $item['plink'], 'uri' => $item['uri']]);
			return $media;
		}

		if (in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			$media['mimetype'] = 'application/activity+json';
		} elseif ($item['network'] == Protocol::DIASPORA) {
			$media['mimetype'] = 'application/xml';
		}

		$contact = Contact::getById($item['author-id'], ['avatar', 'gsid']);
		if (!empty($contact['gsid'])) {
			$gserver = DBA::selectFirst('gserver', ['url', 'site_name'], ['id' => $contact['gsid']]);
		}

		$media['type'] = self::ACTIVITY;
		$media['media-uri-id'] = $item['uri-id'];
		$media['height'] = null;
		$media['width'] = null;
		$media['preview'] = null;
		$media['preview-height'] = null;
		$media['preview-width'] = null;
		$media['blurhash'] = null;
		$media['description'] = $item['body'];
		$media['name'] = $item['title'];
		$media['author-url'] = $item['author-link'];
		$media['author-name'] = $item['author-name'];
		$media['author-image'] = $contact['avatar'] ?? $item['author-avatar'];
		$media['publisher-url'] = $gserver['url'] ?? null;
		$media['publisher-name'] = $gserver['site_name'] ?? null;
		$media['publisher-image'] = null;

		Logger::debug('Activity detected', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'plink' => $item['plink'], 'uri' => $item['uri']]);
		return $media;
	}

	/**
	 * Adds the account type if the media entry is linked to an account
	 *
	 * @param array $media
	 * @return array
	 */
	private static function addAccount(array $media): array
	{
		$contact = Contact::getByURL($media['url'], false);
		if (empty($contact) || ($contact['network'] == Protocol::PHANTOM)) {
			return $media;
		}

		if (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			$media['mimetype'] = 'application/activity+json';
		}

		if (!empty($contact['gsid'])) {
			$gserver = DBA::selectFirst('gserver', ['url', 'site_name'], ['id' => $contact['gsid']]);
		}

		$media['type'] = self::ACCOUNT;
		$media['media-uri-id'] = $contact['uri-id'];
		$media['height'] = null;
		$media['width'] = null;
		$media['preview'] = null;
		$media['preview-height'] = null;
		$media['preview-width'] = null;
		$media['blurhash'] = null;
		$media['description'] = $contact['about'];
		$media['name'] = $contact['name'];
		$media['author-url'] = $contact['url'];
		$media['author-name'] = $contact['name'];
		$media['author-image'] = $contact['avatar'];
		$media['publisher-url'] = $gserver['url'] ?? null;
		$media['publisher-name'] = $gserver['site_name'] ?? null;
		$media['publisher-image'] = null;

		Logger::debug('Account detected', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'uri' => $contact['url']]);
		return $media;
	}

	/**
	 * Add page infos for HTML entries
	 *
	 * @param array $media
	 * @return array
	 */
	private static function addPage(array $media): array
	{
		$data = ParseUrl::getSiteinfoCached($media['url'], false);
		$media['preview'] = $data['images'][0]['src'] ?? null;
		$media['preview-height'] = $data['images'][0]['height'] ?? null;
		$media['preview-width'] = $data['images'][0]['width'] ?? null;
		$media['blurhash'] = $data['images'][0]['blurhash'] ?? null;
		$media['description'] = $data['text'] ?? null;
		$media['name'] = $data['title'] ?? null;
		$media['author-url'] = $data['author_url'] ?? null;
		$media['author-name'] = $data['author_name'] ?? null;
		$media['author-image'] = $data['author_img'] ?? null;
		$media['publisher-url'] = $data['publisher_url'] ?? null;
		$media['publisher-name'] = $data['publisher_name'] ?? null;
		$media['publisher-image'] = $data['publisher_img'] ?? null;

		return $media;
	}

	/**
	 * Fetch media data from local resources
	 * @param array $media
	 * @return array media with added data
	 */
	private static function fetchLocalData(array $media): array
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
			$media['blurhash'] = $photo['blurhash'];
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
	public static function addType(array $data): array
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
	private static function isPictureLink(string $page, string $preview): bool
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
	public static function insertFromBody(int $uriid, string $body, bool $endmatch = false): string
	{
		$endmatchpattern = $endmatch ? '\z' : '';
		// Simplify image codes
		$unshared_body = $body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]$endmatchpattern/ism", '[img]$3[/img]', $body);

		$attachments = [];
		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]$endmatchpattern#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (!self::isPictureLink($picture[1], $picture[2])) {
					continue;
				}
				$body = str_replace($picture[0], '', $body);
				$image = str_replace('-1.', '-0.', $picture[2]);
				$attachments[$image] = [
					'uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $image,
					'preview' => $picture[2], 'description' => $picture[3]
				];
			}
		}

		if (preg_match_all("/\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]$endmatchpattern/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);
				$attachments[$picture[1]] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $picture[1], 'description' => $picture[2]];
			}
		}

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]$endmatchpattern#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (!self::isPictureLink($picture[1], $picture[2])) {
					continue;
				}
				$body = str_replace($picture[0], '', $body);
				$image = str_replace('-1.', '-0.', $picture[2]);
				$attachments[$image] = [
					'uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $image,
					'preview' => $picture[2], 'description' => null
				];
			}
		}

		if (preg_match_all("/\[img\]([^\[\]]*)\[\/img\]$endmatchpattern/ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);
				$attachments[$picture[1]] = ['uri-id' => $uriid, 'type' => self::IMAGE, 'url' => $picture[1]];
			}
		}

		if (preg_match_all("/\[audio\]([^\[\]]*)\[\/audio\]$endmatchpattern/ism", $body, $audios, PREG_SET_ORDER)) {
			foreach ($audios as $audio) {
				$body = str_replace($audio[0], '', $body);
				$attachments[$audio[1]] = ['uri-id' => $uriid, 'type' => self::AUDIO, 'url' => $audio[1]];
			}
		}

		if (preg_match_all("/\[video\]([^\[\]]*)\[\/video\]$endmatchpattern/ism", $body, $videos, PREG_SET_ORDER)) {
			foreach ($videos as $video) {
				$body = str_replace($video[0], '', $body);
				$attachments[$video[1]] = ['uri-id' => $uriid, 'type' => self::VIDEO, 'url' => $video[1]];
			}
		}

		if ($uriid != 0) {
			foreach ($attachments as $attachment) {
				if (Post\Link::exists($uriid, $attachment['preview'] ?? $attachment['url'])) {
					continue;
				}

				// Only store attachments that are part of the unshared body
				if (Item::containsLink($unshared_body, $attachment['preview'] ?? $attachment['url'], $attachment['type'])) {
					self::insert($attachment);
				}
			}
		}

		return trim($body);
	}

	/**
	 * Remove media that is at the end of the body
	 *
	 * @param string $body
	 * @return string
	 */
	public static function removeFromEndOfBody(string $body): string
	{
		do {
			$prebody = $body;
			$body = self::insertFromBody(0, $body, true);
		} while ($prebody != $body);
		return $body;
	}

	/**
	 * Add media links from a relevant url in the body
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return void
	 */
	public static function insertFromRelevantUrl(int $uriid, string $body, string $fullbody, string $network)
	{
		// Remove all hashtags and mentions
		$body = preg_replace("/([#@!])\[url\=(.*?)\](.*?)\[\/url\]/ism", '', $body);

		// Search for pure links
		if (preg_match_all("/\[url\](https?:.*?)\[\/url\]/ism", $body, $matches)) {
			foreach ($matches[1] as $url) {
				Logger::info('Got page url (link without description)', ['uri-id' => $uriid, 'url' => $url]);
				$result = self::insert(['uri-id' => $uriid, 'type' => self::UNKNOWN, 'url' => $url], false, $network);
				if ($result && ($network == Protocol::DFRN)) {
					self::revertHTMLType($uriid, $url, $fullbody);
					Logger::debug('Revert HTML type', ['uri-id' => $uriid, 'url' => $url]);
				} elseif ($result) {
					Logger::debug('Media had been added', ['uri-id' => $uriid, 'url' => $url]);
				} else {
					Logger::debug('Media had not been added', ['uri-id' => $uriid, 'url' => $url]);
				}
			}
		}

		// Search for links with descriptions
		if (preg_match_all("/\[url\=(https?:.*?)\].*?\[\/url\]/ism", $body, $matches)) {
			foreach ($matches[1] as $url) {
				Logger::info('Got page url (link with description)', ['uri-id' => $uriid, 'url' => $url]);
				$result = self::insert(['uri-id' => $uriid, 'type' => self::UNKNOWN, 'url' => $url], false, $network);
				if ($result && ($network == Protocol::DFRN)) {
					self::revertHTMLType($uriid, $url, $fullbody);
					Logger::debug('Revert HTML type', ['uri-id' => $uriid, 'url' => $url]);
				} elseif ($result) {
					Logger::debug('Media has been added', ['uri-id' => $uriid, 'url' => $url]);
				} else {
					Logger::debug('Media has not been added', ['uri-id' => $uriid, 'url' => $url]);
				}
			}
		}
	}

	/**
	 * Revert the media type of links to UNKNOWN for DFRN posts when they aren't attached
	 *
	 * @param integer $uriid
	 * @param string $url
	 * @param string $body
	 * @return void
	 */
	private static function revertHTMLType(int $uriid, string $url, string $body)
	{
		$attachment = BBCode::getAttachmentData($body);
		if (!empty($attachment['url']) && Network::getUrlMatch($attachment['url'], $url)) {
			return;
		}
		DBA::update('post-media', ['type' => self::UNKNOWN], ['uri-id' => $uriid, 'type' => self::HTML, 'url' => $url]);
	}

	/**
	 * Add media links from the attachment field
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return void
	 */
	public static function insertFromAttachmentData(int $uriid, string $body)
	{
		$data = BBCode::getAttachmentData($body);
		if (empty($data)) {
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
	 * @param int $uri_id URI id
	 * @param array $types Media types
	 * @return array|bool Array on success, false on error
	 * @throws \Exception
	 */
	public static function getByURIId(int $uri_id, array $types = [])
	{
		$condition = ["`uri-id` = ? AND `type` != ?", $uri_id, self::UNKNOWN];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::selectToArray('post-media', [], $condition, ['order' => ['id']]);
	}

	/**
	 * Checks if media attachments are associated with the provided item ID.
	 *
	 * @param int $uri_id URI id
	 * @param array $types Media types
	 * @return bool Whether media attachment exists
	 * @throws \Exception
	 */
	public static function existsByURIId(int $uri_id, array $types = []): bool
	{
		$condition = ["`uri-id` = ? AND `type` != ?", $uri_id, self::UNKNOWN];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::exists('post-media', $condition);
	}

	/**
	 * Delete media by uri-id and media type
	 *
	 * @param int $uri_id URI id
	 * @param array $types Media types
	 * @return bool Whether media attachment exists
	 * @throws \Exception
	 */
	public static function deleteByURIId(int $uri_id, array $types = []): bool
	{
		$condition = ['uri-id' => $uri_id];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::delete('post-media', $condition);
	}

	/**
	 * Split the attachment media in the three segments "visual", "link" and "additional"
	 *
	 * @param int    $uri_id URI id
	 * @param array  $links list of links that shouldn't be added
	 * @param bool   $has_media
	 * @return array attachments
	 */
	public static function splitAttachments(int $uri_id, array $links = [], bool $has_media = true): array
	{
		$attachments = ['visual' => [], 'link' => [], 'additional' => []];

		if (!$has_media) {
			return $attachments;
		}

		$media = self::getByURIId($uri_id);
		if (empty($media)) {
			return $attachments;
		}

		$heights = [];
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

			// Currently these two types are ignored here.
			// Posts are added differently and contacts are not displayed as attachments.
			if (in_array($medium['type'], [self::ACCOUNT, self::ACTIVITY])) {
				continue;
			}

			if (!empty($medium['preview'])) {
				$previews[] = $medium['preview'];
			}

			$type = explode('/', explode(';', $medium['mimetype'] ?? '')[0]);
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

			if (
				in_array($medium['type'], [self::AUDIO, self::IMAGE]) ||
				in_array($filetype, ['audio', 'image'])
			) {
				$attachments['visual'][] = $medium;
			} elseif (($medium['type'] == self::VIDEO) || ($filetype == 'video')) {
				if (!empty($medium['height'])) {
					// Peertube videos are delivered in many different resolutions. We pick a moderate one.
					// Since only Peertube provides a "height" parameter, this wouldn't be executed
					// when someone for example on Mastodon was sharing multiple videos in a single post.
					$heights[$medium['height']] = $medium['url'];
					$video[$medium['url']] = $medium;
				} else {
					$attachments['visual'][] = $medium;
				}
			} else {
				$attachments['additional'][] = $medium;
			}
		}

		if (!empty($heights)) {
			ksort($heights);
			foreach ($heights as $height => $url) {
				if (empty($selected) || $height <= 480) {
					$selected = $url;
				}
			}

			if (!empty($selected)) {
				$attachments['visual'][] = $video[$selected];
				unset($video[$selected]);
				foreach ($video as $element) {
					$attachments['additional'][] = $element;
				}
			}
		}

		return $attachments;
	}

	/**
	 * Add media attachments to the body
	 *
	 * @param int    $uriid
	 * @param string $body
	 * @param array  $types
	 *
	 * @return string body
	 */
	public static function addAttachmentsToBody(int $uriid, string $body = '', array $types = [self::IMAGE, self::AUDIO, self::VIDEO]): string
	{
		if (empty($body)) {
			$item = Post::selectFirst(['body'], ['uri-id' => $uriid]);
			if (!DBA::isResult($item)) {
				return '';
			}
			$body = $item['body'];
		}
		$original_body = $body;

		$body = BBCode::removeAttachment($body);

		foreach (self::getByURIId($uriid, $types) as $media) {
			if (Item::containsLink($body, $media['preview'] ?? $media['url'], $media['type'])) {
				continue;
			}

			if ($media['type'] == self::IMAGE) {
				if (!empty($media['preview'])) {
					if (!empty($media['description'])) {
						$body .= "\n[url=" . $media['url'] . "][img=" . $media['preview'] . ']' . $media['description'] . '[/img][/url]';
					} else {
						$body .= "\n[url=" . $media['url'] . "][img]" . $media['preview'] . '[/img][/url]';
					}
				} else {
					if (!empty($media['description'])) {
						$body .= "\n[img=" . $media['url'] . ']' . $media['description'] . '[/img]';
					} else {
						$body .= "\n[img]" . $media['url'] . '[/img]';
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
	 * Add an [attachment] element to the body for a given uri-id with a HTML media element
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return string
	 */
	public static function addHTMLAttachmentToBody(int $uriid, string $body): string
	{
		if (preg_match("/.*(\[attachment.*?\].*?\[\/attachment\]).*/ism", $body, $match)) {
			return $body;
		}

		$links = self::getByURIId($uriid, [self::HTML]);
		if (empty($links)) {
			return $body;
		}

		$data = [
			'type' => 'link',
			'url'  => $links[0]['url'],
			'title' => $links[0]['name'],
			'text' => $links[0]['description'],
			'publisher_name' => $links[0]['publisher-name'],
			'publisher_url' => $links[0]['publisher-url'],
			'publisher_img' => $links[0]['publisher-image'],
			'author_name' => $links[0]['author-name'],
			'author_url' => $links[0]['author-url'],
			'author_img' => $links[0]['author-image'],
			'images' => [[
				'src' => $links[0]['preview'],
				'height' => $links[0]['preview-height'],
				'width' => $links[0]['preview-width'],
			]]
		];
		$body .= "\n" . PageInfo::getFooterFromData($data);

		return $body;
	}

	/**
	 * Add a link to the body for a given uri-id with a HTML media element
	 *
	 * @param integer $uriid
	 * @param string $body
	 * @return string
	 */
	public static function addHTMLLinkToBody(int $uriid, string $body): string
	{
		$links = self::getByURIId($uriid, [self::HTML]);
		if (empty($links)) {
			return $body;
		}

		if (strpos($body, $links[0]['url'])) {
			return $body;
		}

		if (!empty($links[0]['name']) && ($links[0]['name'] != $links[0]['url'])) {
			return $body . "\n[url=" . $links[0]['url'] . ']' . $links[0]['name'] . "[/url]";
		} else {
			return $body . "\n[url]" . $links[0]['url'] . "[/url]";
		}
	}

	/**
	 * Add an [attachment] element to the body and a link to raw-body for a given uri-id with a HTML media element
	 *
	 * @param array $item
	 * @return array
	 */
	public static function addHTMLAttachmentToItem(array $item): array
	{
		if (($item['gravity'] == Item::GRAVITY_ACTIVITY) || empty($item['uri-id'])) {
			return $item;
		}

		$item['body'] = self::addHTMLAttachmentToBody($item['uri-id'], $item['body']);

		if (!empty($item['raw-body'])) {
			$item['raw-body'] = self::addHTMLLinkToBody($item['uri-id'], $item['raw-body']);
		}

		return $item;
	}

	/**
	 * Get preview link for given media id
	 *
	 * @param integer $id   media id
	 * @param string  $size One of the Proxy::SIZE_* constants
	 * @return string preview link
	 */
	public static function getPreviewUrlForId(int $id, string $size = ''): string
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
	 * @param string  $size One of the Proxy::SIZE_* constants
	 * @return string media link
	 */
	public static function getUrlForId(int $id, string $size = ''): string
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
