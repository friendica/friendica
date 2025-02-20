<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ATProtocol;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use GuzzleHttp\Psr7\Uri;

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
	const HLS         = 22;
	const JSON        = 23;
	const LD          = 24;
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
			DI::logger()->warning('Incomplete media data', ['media' => $media]);
			return false;
		}

		if (DBA::exists('post-media', ['uri-id' => $media['uri-id'], 'preview' => $media['url']])) {
			DI::logger()->info('Media already exists as preview', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
			return false;
		}

		// "document" has got the lowest priority. So when the same file is both attached as document
		// and embedded as picture then we only store the picture or replace the document
		$found = DBA::selectFirst('post-media', ['type'], ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
		if (!$force && !empty($found) && (!in_array($found['type'], [self::UNKNOWN, self::DOCUMENT]) || ($media['type'] == self::DOCUMENT))) {
			DI::logger()->info('Media already exists', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'found' => $found['type'], 'new' => $media['type']]);
			return false;
		}

		if (!ItemURI::exists($media['uri-id'])) {
			DI::logger()->info('Media referenced URI ID not found', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
			return false;
		}

		$media['url'] = Network::sanitizeUrl($media['url']);

		$media = self::unsetEmptyFields($media);
		$media = DI::dbaDefinition()->truncateFieldsForTable('post-media', $media);

		// We are storing as fast as possible to avoid duplicated network requests
		// when fetching additional information for pictures and other content.
		$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
		DI::logger()->info('Stored media', ['result' => $result, 'media' => $media]);
		$stored = $media;

		$media = self::fetchAdditionalData($media);
		$media = self::unsetEmptyFields($media);
		$media = DI::dbaDefinition()->truncateFieldsForTable('post-media', $media);

		if (array_diff_assoc($media, $stored)) {
			$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
			DI::logger()->info('Updated media', ['result' => $result, 'media' => $media]);
		} else {
			DI::logger()->info('Nothing to update', ['media' => $media]);
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
			'type'        => self::DOCUMENT,
			'url'         => $href,
			'size'        => $length,
			'mimetype'    => $type,
			'description' => $title
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
		if (DI::baseUrl()->isLocalUrl($media['url'])) {
			$media = self::fetchLocalData($media);
			if (preg_match('|.*?/search\?(.+)|', $media['url'], $matches)) {
				return $media;
			}
			if (empty($media['mimetype']) || empty($media['size'])) {
				DI::logger()->debug('Unknown local link', ['url' => $media['url']]);
			}
		}

		// Fetch the mimetype or size if missing.
		if (Network::isValidHttpUrl($media['url']) && (empty($media['mimetype']) || $media['type'] == self::HTML) && !in_array($media['type'], [self::IMAGE, self::HLS])) {
			$timeout = DI::config()->get('system', 'xrd_timeout');
			try {
				$curlResult = DI::httpClient()->head($media['url'], [HttpClientOptions::ACCEPT_CONTENT => HttpClientAccept::AS_DEFAULT, HttpClientOptions::TIMEOUT => $timeout, HttpClientOptions::REQUEST => HttpClientRequest::CONTENTTYPE]);

				// Workaround for systems that can't handle a HEAD request
				if (!$curlResult->isSuccess() && in_array($curlResult->getReturnCode(), [400, 403, 405])) {
					$curlResult = DI::httpClient()->get($media['url'], HttpClientAccept::AS_DEFAULT, [HttpClientOptions::TIMEOUT => $timeout]);
				}
				if ($curlResult->isSuccess()) {
					if (!empty($curlResult->getContentType())) {
						$media['mimetype'] = $curlResult->getContentType();
					}
					if (empty($media['size'])) {
						$media['size'] = (int)($curlResult->getHeader('Content-Length')[0] ?? strlen($curlResult->getBodyString() ?? ''));
					}
				} else {
					DI::logger()->notice('Could not fetch head', ['media' => $media, 'code' => $curlResult->getReturnCode()]);
				}
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			}
		}

		if (($media['type'] != self::DOCUMENT) && !empty($media['mimetype'])) {
			$media = self::addType($media);
		}

		DI::logger()->debug('Got type for url', ['type' => $media['type'], 'mimetype' => $media['mimetype'] ?? '', 'url' => $media['url']]);

		if ($media['type'] == self::IMAGE) {
			$imagedata = Images::getInfoFromURLCached($media['url'], empty($media['description']));
			if ($imagedata) {
				$media['mimetype'] = $imagedata['mime'];
				$media['size']     = $imagedata['size'];
				$media['width']    = $imagedata[0];
				$media['height']   = $imagedata[1];
				$media['blurhash'] = $imagedata['blurhash'] ?? null;
				if (!empty($imagedata['description']) && empty($media['description'])) {
					$media['description'] = $imagedata['description'];
					DI::logger()->debug('Detected text for image', $media);
				}
			} else {
				DI::logger()->notice('No image data', ['media' => $media]);
			}
		}

		if (!empty($media['preview'])) {
			$media = self::addPreviewData($media);
		}

		if (in_array($media['type'], [self::TEXT, self::ACTIVITY, self::LD, self::JSON, self::HTML, self::XML, self::PLAIN])) {
			$media = self::addAccount($media);
		}

		if (in_array($media['type'], [self::ACTIVITY, self::LD, self::JSON]) || self::isFederatedServer($media['url'])) {
			$media = self::addActivity($media);
		}

		if (in_array($media['type'], [self::HTML, self::LD, self::JSON])) {
			$media = self::addPage($media);
		}

		return $media;
	}

	private static function isFederatedServer(string $url): bool
	{
		try {
			$baseurl = Network::getBaseUrl(new Uri($url));
			if (empty($baseurl)) {
				return false;
			}

			if (Strings::compareLink($baseurl, ATProtocol::WEB)) {
				return true;
			}

			return DBA::exists('gserver', ['nurl' => Strings::normaliseLink($baseurl), 'network' => Protocol::FEDERATED]);
		} catch(\Throwable $e) {
			DI::logger()->notice('Invalid URL provided', ['url' => $url, 'exception' => $e, 'callstack' => System::callstack(10)]);
			return false;
		}
	}

	private static function addPreviewData(array $media): array
	{
		if (!empty($media['preview-width']) && !empty($media['preview-height'])) {
			return $media;
		}

		$imagedata = Images::getInfoFromURLCached($media['preview']);
		if ($imagedata) {
			$media['blurhash'] = $imagedata['blurhash'] ?? null;

			// When the original picture is potentially animated but the preview isn't, we override the preview
			if (in_array($media['mimetype'] ?? '', ['image/gif', 'image/png']) && !in_array($imagedata['mime'], ['image/gif', 'image/png'])) {
				$media['preview']        = $media['url'];
				$media['preview-width']  = $media['width']  ?? $imagedata[0];
				$media['preview-height'] = $media['height'] ?? $imagedata[1];
				return $media;
			}

			$media['preview-width']  = $imagedata[0];
			$media['preview-height'] = $imagedata[1];
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
		$id = Item::fetchByLink($media['url'], 0, ActivityPub\Receiver::COMPLETION_ASYNC, $media['mimetype'] ?? '');
		if (empty($id)) {
			$media['type'] = $media['type'] == self::ACTIVITY ? self::JSON : $media['type'];
			return $media;
		}

		$item = Post::selectFirst([], ['id' => $id, 'network' => Protocol::FEDERATED]);
		if (empty($item['id'])) {
			DI::logger()->debug('Not a federated activity', ['id' => $id, 'uri-id' => $media['uri-id'], 'url' => $media['url']]);
			$media['type'] = $media['type'] == self::ACTIVITY ? self::JSON : $media['type'];
			return $media;
		}

		if ($item['uri-id'] == $media['uri-id']) {
			DI::logger()->info('Media-Uri-Id is identical to Uri-Id', ['uri-id' => $media['uri-id']]);
			$media['type'] = $media['type'] == self::ACTIVITY ? self::JSON : $media['type'];
			return $media;
		}

		if (
			!empty($item['plink']) && Strings::compareLink($item['plink'], $media['url']) &&
			parse_url($item['plink'], PHP_URL_HOST) != parse_url($item['uri'], PHP_URL_HOST)
		) {
			DI::logger()->debug('Not a link to an activity', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'plink' => $item['plink'], 'uri' => $item['uri']]);
			$media['type'] = $media['type'] == self::ACTIVITY ? self::JSON : $media['type'];
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

		$media['type']            = self::ACTIVITY;
		$media['media-uri-id']    = $item['uri-id'];
		$media['height']          = null;
		$media['width']           = null;
		$media['preview']         = null;
		$media['preview-height']  = null;
		$media['preview-width']   = null;
		$media['blurhash']        = null;
		$media['description']     = $item['body'];
		$media['name']            = $item['title'];
		$media['author-url']      = $item['author-link'];
		$media['author-name']     = $item['author-name'];
		$media['author-image']    = $contact['avatar']    ?? $item['author-avatar'];
		$media['publisher-url']   = $gserver['url']       ?? null;
		$media['publisher-name']  = $gserver['site_name'] ?? null;
		$media['publisher-image'] = null;

		DI::logger()->debug('Activity detected', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'plink' => $item['plink'], 'uri' => $item['uri']]);
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

		$media['type']            = self::ACCOUNT;
		$media['media-uri-id']    = $contact['uri-id'];
		$media['height']          = null;
		$media['width']           = null;
		$media['preview']         = null;
		$media['preview-height']  = null;
		$media['preview-width']   = null;
		$media['blurhash']        = null;
		$media['description']     = $contact['about'];
		$media['name']            = $contact['name'];
		$media['author-url']      = $contact['url'];
		$media['author-name']     = $contact['name'];
		$media['author-image']    = $contact['avatar'];
		$media['publisher-url']   = $gserver['url']       ?? null;
		$media['publisher-name']  = $gserver['site_name'] ?? null;
		$media['publisher-image'] = null;

		DI::logger()->debug('Account detected', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'uri' => $contact['url']]);
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
		$data = ParseUrl::getSiteinfoCached($media['url'], $media['mimetype'] ?? '');
		if (empty($data['images'][0]['src']) && empty($data['text']) && empty($data['title'])) {
			if (!empty($media['preview'])) {
				$media = self::addPreviewData($media);
				DI::logger()->debug('Detected site data is empty, use suggested media data instead', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'type' => $data['type']]);
			}
		} else {
			$media['preview']        = $data['images'][0]['src']      ?? null;
			$media['preview-height'] = $data['images'][0]['height']   ?? null;
			$media['preview-width']  = $data['images'][0]['width']    ?? null;
			$media['blurhash']       = $data['images'][0]['blurhash'] ?? null;
			$media['description']    = $data['text']                  ?? null;
			$media['name']           = $data['title']                 ?? null;
		}

		$media['type']            = self::HTML;
		$media['size']            = $data['size']           ?? null;
		$media['author-url']      = $data['author_url']     ?? null;
		$media['author-name']     = $data['author_name']    ?? null;
		$media['author-image']    = $data['author_img']     ?? null;
		$media['publisher-url']   = $data['publisher_url']  ?? null;
		$media['publisher-name']  = $data['publisher_name'] ?? null;
		$media['publisher-image'] = $data['publisher_img']  ?? null;

		return $media;
	}

	/**
	 * Fetch media data from local resources
	 * @param array $media
	 * @return array media with added data
	 */
	private static function fetchLocalData(array $media): array
	{
		if (preg_match('|.*?/attach/(\d+)|', $media['url'], $matches)) {
			$attachment = Attach::selectFirst(['id', 'filename', 'filetype', 'filesize'], ['id' => $matches[1]]);
			if (!empty($attachment)) {
				$media['attach-id'] = $attachment['id'];
				$media['name']      = $attachment['filename'];
				$media['mimetype']  = $attachment['filetype'];
				$media['size']      = $attachment['filesize'];
			}
			return $media;
		}

		if (!preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $media['url'], $matches)) {
			return $media;
		}
		$photo = Photo::selectFirst(['type', 'datasize', 'width', 'height', 'blurhash'], ['resource-id' => $matches[1], 'scale' => $matches[2]]);
		if (!empty($photo)) {
			$media['mimetype'] = $photo['type'];
			$media['size']     = $photo['datasize'];
			$media['width']    = $photo['width'];
			$media['height']   = $photo['height'];
			$media['blurhash'] = $photo['blurhash'];
		}

		if (!preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', $media['preview'] ?? '', $matches)) {
			return $media;
		}
		$photo = Photo::selectFirst(['width', 'height'], ['resource-id' => $matches[1], 'scale' => $matches[2]]);
		if (!empty($photo)) {
			$media['preview-width']  = $photo['width'];
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
			DI::logger()->info('No MimeType provided', ['media' => $data]);
			return $data;
		}

		$data['type'] = self::getType($data['mimetype']);
		return $data;
	}

	public static function getType(string $mimeType): int
	{
		$type = explode('/', current(explode(';', $mimeType)));
		if (count($type) < 2) {
			DI::logger()->info('Unknown MimeType', ['type' => $type, 'media' => $mimeType]);
			return self::UNKNOWN;
		}

		$filetype = strtolower($type[0]);
		$subtype  = strtolower($type[1]);

		if ($filetype == 'image') {
			$type = self::IMAGE;
		} elseif ($filetype == 'video') {
			$type = self::VIDEO;
		} elseif ($filetype == 'audio') {
			$type = self::AUDIO;
		} elseif (($filetype == 'text') && ($subtype == 'html')) {
			$type = self::HTML;
		} elseif (($filetype == 'text') && ($subtype == 'xml')) {
			$type = self::XML;
		} elseif (($filetype == 'text') && ($subtype == 'plain')) {
			$type = self::PLAIN;
		} elseif ($filetype == 'text') {
			$type = self::TEXT;
		} elseif (($filetype == 'application') && ($subtype == 'x-bittorrent')) {
			$type = self::TORRENT;
		} elseif (($filetype == 'application') && ($subtype == 'vnd.apple.mpegurl')) {
			$type = self::HLS;
		} elseif (($filetype == 'application') && ($subtype == 'activity+json')) {
			$type = self::ACTIVITY;
		} elseif (($filetype == 'application') && ($subtype == 'ld+json')) {
			$type = self::LD;
		} elseif (($filetype == 'application') && ($subtype == 'json')) {
			$type = self::JSON;
		} elseif ($filetype == 'application') {
			$type = self::APPLICATION;
		} else {
			$type = self::UNKNOWN;
			DI::logger()->info('Unknown type', ['filetype' => $filetype, 'subtype' => $subtype, 'media' => $mimeType]);
		}

		DI::logger()->debug('Detected type', ['type' => $type, 'filetype' => $filetype, 'subtype' => $subtype, 'media' => $mimeType]);
		return $type;
	}

	/**
	 * Tests for path patterns that are used for picture links in Friendica
	 *
	 * @param string $page    Link to the image page
	 * @param string $preview Preview picture
	 * @return boolean
	 */
	private static function isLinkToPhoto(string $page, string $preview): bool
	{
		return preg_match('#/photo/.*-0\.#ism', $page) && preg_match('#/photo/.*-[012]\.#ism', $preview);
	}

	/**
	 * Tests for path patterns that are used for picture links in Friendica
	 *
	 * @param string $page    Link to the image page
	 * @param string $preview Preview picture
	 * @return boolean
	 */
	private static function isLinkToImagePage(string $page, string $preview): bool
	{
		return preg_match('#/photos/.*/image/#ism', $page) && preg_match('#/photo/.*-[012]\.#ism', $preview);
	}

	/**
	 * Replace the image link in Friendica image posts with a link to the image
	 *
	 * @param string $body
	 * @return string
	 */
	public static function replaceImage(string $body): string
	{
		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (self::isLinkToImagePage($picture[1], $picture[2])) {
					$body = str_replace($picture[0], Images::getBBCodeByUrl(str_replace(['-1.', '-2.'], '-0.', $picture[2]), $picture[2], $picture[3]), $body);
				}
			}
		}

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (self::isLinkToImagePage($picture[1], $picture[2])) {
					$body = str_replace($picture[0], Images::getBBCodeByUrl(str_replace(['-1.', '-2.'], '-0.', $picture[2]), $picture[2]), $body);
				}
			}
		}

		return $body;
	}

	/**
	 * Add media links and remove them from the body
	 *
	 * @param integer $uriid
	 * @param string  $body
	 * @param bool    $endmatch
	 * @param bool    $removepicturelinks
	 * @return string Body without media links
	 */
	public static function insertFromBody(int $uriid, string $body, bool $endmatch = false, bool $removepicturelinks = false): string
	{
		$endmatchpattern = $endmatch ? '\z' : '';
		// Simplify image codes
		$unshared_body = $body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]$endmatchpattern/ism", '[img]$3[/img]', $body);

		$attachments = [];
		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]$endmatchpattern#ism", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (self::isLinkToImagePage($picture[1], $picture[2])) {
					$body  = str_replace($picture[0], '', $body);
					$image = str_replace(['-1.', '-2.'], '-0.', $picture[2]);

					$attachments[$image] = [
						'uri-id'      => $uriid,
						'type'        => self::IMAGE,
						'url'         => $image,
						'preview'     => $picture[2],
						'description' => $picture[3]
					];
				} elseif (self::isLinkToPhoto($picture[1], $picture[2])) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => self::IMAGE,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => $picture[3]
					];
				} elseif ($removepicturelinks) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => self::UNKNOWN,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => $picture[3]
					];
				}
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
				if (self::isLinkToImagePage($picture[1], $picture[2])) {
					$body  = str_replace($picture[0], '', $body);
					$image = str_replace(['-1.', '-2.'], '-0.', $picture[2]);

					$attachments[$image] = [
						'uri-id'      => $uriid,
						'type'        => self::IMAGE,
						'url'         => $image,
						'preview'     => $picture[2],
						'description' => null
					];
				} elseif (self::isLinkToPhoto($picture[1], $picture[2])) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => self::IMAGE,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => null
					];
				} elseif ($removepicturelinks) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => self::UNKNOWN,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => null
					];
				}
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
			$body    = self::insertFromBody(0, $body, true);
		} while ($prebody != $body);
		return $body;
	}

	/**
	 * Remove media from the body
	 *
	 * @param string $body
	 * @return string
	 */
	public static function removeFromBody(string $body): string
	{
		do {
			$prebody = $body;
			$body    = self::insertFromBody(0, $body, false, true);
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
				DI::logger()->info('Got page url (link without description)', ['uri-id' => $uriid, 'url' => $url]);
				$result = self::insert(['uri-id' => $uriid, 'type' => self::UNKNOWN, 'url' => $url], false);
				if ($result && !in_array($network, [Protocol::ACTIVITYPUB, Protocol::DIASPORA])) {
					self::revertHTMLType($uriid, $url, $fullbody);
					DI::logger()->debug('Revert HTML type', ['uri-id' => $uriid, 'url' => $url]);
				} elseif ($result) {
					DI::logger()->debug('Media had been added', ['uri-id' => $uriid, 'url' => $url]);
				} else {
					DI::logger()->debug('Media had not been added', ['uri-id' => $uriid, 'url' => $url]);
				}
			}
		}

		// Search for links with descriptions
		if (preg_match_all("#\[url=(https?://.+?)].+?\[/url]#ism", $body, $matches)) {
			foreach ($matches[1] as $url) {
				DI::logger()->info('Got page url (link with description)', ['uri-id' => $uriid, 'url' => $url]);
				$result = self::insert(['uri-id' => $uriid, 'type' => self::UNKNOWN, 'url' => $url], false);
				if ($result && !in_array($network, [Protocol::ACTIVITYPUB, Protocol::DIASPORA])) {
					self::revertHTMLType($uriid, $url, $fullbody);
					DI::logger()->debug('Revert HTML type', ['uri-id' => $uriid, 'url' => $url]);
				} elseif ($result) {
					DI::logger()->debug('Media has been added', ['uri-id' => $uriid, 'url' => $url]);
				} else {
					DI::logger()->debug('Media has not been added', ['uri-id' => $uriid, 'url' => $url]);
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

		DI::logger()->info('Adding attachment data', ['data' => $data]);
		$attachment = [
			'uri-id'         => $uriid,
			'type'           => self::HTML,
			'url'            => $data['url'],
			'preview'        => $data['preview']       ?? null,
			'description'    => $data['description']   ?? null,
			'name'           => $data['title']         ?? null,
			'author-url'     => $data['author_url']    ?? null,
			'author-name'    => $data['author_name']   ?? null,
			'publisher-url'  => $data['provider_url']  ?? null,
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
			$media['type']        = self::DOCUMENT;
			$media['uri-id']      = $uriid;
			$media['url']         = $attachment[1];
			$media['size']        = $attachment[2];
			$media['mimetype']    = $attachment[3];
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

	public static function getByURL(int $uri_id, string $url, array $types = [])
	{
		$condition = ["`uri-id` = ? AND `url` = ? AND `type` != ?", $uri_id, $url, self::UNKNOWN];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::selectFirst('post-media', [], $condition);
	}

	/**
	 * Retrieves the media attachment with the provided media id.
	 *
	 * @param int $id  id
	 * @return array|bool Array on success, false on error
	 * @throws \Exception
	 */
	public static function getById(int $id)
	{
		return DBA::selectFirst('post-media', [], ['id' => $id]);
	}

	/**
	 * Update post-media entries by id
	 *
	 * @param array $fields
	 * @param int $id
	 * @return bool
	 */
	public static function updateById(array $fields, int $id): bool
	{
		return DBA::update('post-media', $fields, ['id' => $id]);
	}

	/**
	 * Update post-media entries
	 *
	 * @param array $fields
	 * @param array $condition
	 * @return boolean
	 */
	public static function update(array $fields, array $condition): bool
	{
		return DBA::update('post-media', $fields, $condition);
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
	 * @return bool result of deletion
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
	 * Delete media by id
	 *
	 * @param int $id media id
	 * @return bool result of deletion
	 * @throws \Exception
	 */
	public static function deleteById(int $id): bool
	{
		return DBA::delete('post-media', ['id' => $id]);
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
			$body = self::addAttachmentToBody($media, $body);
		}

		if (preg_match("/.*(\[attachment.*?\].*?\[\/attachment\]).*/ism", $original_body, $match)) {
			$body .= "\n" . $match[1];
		}

		return $body;
	}

	public static function addAttachmentToBody(array $media, string $body): string
	{
		if (Item::containsLink($body, $media['preview'] ?? $media['url'], $media['type'])) {
			return $body;
		}

		if ($media['type'] == self::IMAGE) {
			$body .= "\n" . Images::getBBCodeByUrl($media['url'], $media['preview'], $media['description'] ?? '');
		} elseif ($media['type'] == self::AUDIO) {
			$body .= "\n[audio]" . $media['url'] . "[/audio]\n";
		} elseif ($media['type'] == self::VIDEO) {
			$body .= "\n[video]" . $media['url'] . "[/video]\n";
		} else {
			$body .= "\n[url]" . $media['url'] . "[/url]\n";
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
			'type'           => 'link',
			'url'            => $links[0]['url'],
			'title'          => $links[0]['name'],
			'text'           => $links[0]['description'],
			'publisher_name' => $links[0]['publisher-name'],
			'publisher_url'  => $links[0]['publisher-url'],
			'publisher_img'  => $links[0]['publisher-image'],
			'author_name'    => $links[0]['author-name'],
			'author_url'     => $links[0]['author-url'],
			'author_img'     => $links[0]['author-image'],
			'images'         => [[
				'src'    => $links[0]['preview'],
				'height' => $links[0]['preview-height'],
				'width'  => $links[0]['preview-width'],
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
		return DI::baseUrl() . '/photo/preview/' .
			(Proxy::getPixelsFromSize($size) ? Proxy::getPixelsFromSize($size) . '/' : '') .
			$id;
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
		return DI::baseUrl() . '/photo/media/' .
			(Proxy::getPixelsFromSize($size) ? Proxy::getPixelsFromSize($size) . '/' : '') .
			$id;
	}

	/**
	 * Fetch the uri-id of an attached uri-post for a given uri-id
	 *
	 * @param integer $uri_id Uri-Id of the post
	 * @return integer uri-id of the first attached post
	 */
	public static function getActivityUriId(int $uri_id): int
	{
		$posts = self::getByURIId($uri_id, [self::ACTIVITY]);
		if (!$posts) {
			return 0;
		}
		return reset($posts)['media-uri-id'];
	}
}
