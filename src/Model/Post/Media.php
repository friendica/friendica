<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use FFMpeg\FFMpeg;
use Friendica\Content\PageInfo;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Cache\Enum\Duration;
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
use Friendica\Object\Image;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use getID3;
use GuzzleHttp\Psr7\Uri;

/**
 * Class Media
 *
 * This Model class handles media interactions.
 * This tables stores medias (images, videos, audio files) related to posts.
 */
class Media
{
	/** @deprecated 2026.08 Use PostMedia::TYPE_UNKNOWN instead */
	public const UNKNOWN = PostMedia::TYPE_UNKNOWN;
	/** @deprecated 2026.08 Use PostMedia::TYPE_IMAGE instead */
	public const IMAGE = PostMedia::TYPE_IMAGE;
	/** @deprecated 2026.08 Use PostMedia::TYPE_VIDEO instead */
	public const VIDEO = PostMedia::TYPE_VIDEO;
	/** @deprecated 2026.08 Use PostMedia::TYPE_AUDIO instead */
	public const AUDIO = PostMedia::TYPE_AUDIO;
	/** @deprecated 2026.08 Use PostMedia::TYPE_TEXT instead */
	public const TEXT = PostMedia::TYPE_TEXT;
	/** @deprecated 2026.08 Use PostMedia::TYPE_APPLICATION instead */
	public const APPLICATION = PostMedia::TYPE_APPLICATION;
	/** @deprecated 2026.08 Use PostMedia::TYPE_TORRENT instead */
	public const TORRENT = PostMedia::TYPE_TORRENT;
	/** @deprecated 2026.08 Use PostMedia::TYPE_HTML instead */
	public const HTML = PostMedia::TYPE_HTML;
	/** @deprecated 2026.08 Use PostMedia::TYPE_XML instead */
	public const XML = PostMedia::TYPE_XML;
	/** @deprecated 2026.08 Use PostMedia::TYPE_PLAIN instead */
	public const PLAIN = PostMedia::TYPE_PLAIN;
	/** @deprecated 2026.08 Use PostMedia::TYPE_ACTIVITY instead */
	public const ACTIVITY = PostMedia::TYPE_ACTIVITY;
	/** @deprecated 2026.08 Use PostMedia::TYPE_ACCOUNT instead */
	public const ACCOUNT = PostMedia::TYPE_ACCOUNT;
	/** @deprecated 2026.08 Use PostMedia::TYPE_HLS instead */
	public const HLS = PostMedia::TYPE_HLS;
	/** @deprecated 2026.08 Use PostMedia::TYPE_JSON instead */
	public const JSON = PostMedia::TYPE_JSON;
	/** @deprecated 2026.08 Use PostMedia::TYPE_LD instead */
	public const LD = PostMedia::TYPE_LD;
	/** @deprecated 2026.08 Use PostMedia::TYPE_DOCUMENT instead */
	public const DOCUMENT = PostMedia::TYPE_DOCUMENT;

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
		if (!$force && !empty($found) && (!in_array($found['type'], [PostMedia::TYPE_UNKNOWN, PostMedia::TYPE_DOCUMENT]) || ($media['type'] == PostMedia::TYPE_DOCUMENT))) {
			DI::logger()->info('Media already exists', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'found' => $found['type'], 'new' => $media['type']]);
			return false;
		}

		if (!ItemURI::exists($media['uri-id'])) {
			DI::logger()->info('Media referenced URI ID not found', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
			return false;
		}

		$media['url'] = Network::sanitizeUrl($media['url']);

		if (!empty($media['player-url']) && !self::isEmbeddablePlayerUrl($media['player-url'])) {
			DI::logger()->notice('Dropped unsafe player-url', ['uri-id' => $media['uri-id'], 'player-url' => $media['player-url']]);
			unset($media['player-url']);
		}

		$media = self::unsetEmptyFields($media);
		$media = DI::dbaDefinition()->truncateFieldsForTable('post-media', $media);

		// We are storing as fast as possible to avoid duplicated network requests
		// when fetching additional information for pictures and other content.
		$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
		DI::logger()->info('Stored media', ['result' => $result, 'media' => $media]);
		$stored = $media;

		$media = self::fetchAdditionalData($media);
		$exif  = $media['exif'] ?? null;
		$media = self::unsetEmptyFields($media);
		$media = DI::dbaDefinition()->truncateFieldsForTable('post-media', $media);

		if (array_diff_assoc($media, $stored)) {
			$result = DBA::insert('post-media', $media, Database::INSERT_UPDATE);
			$id     = $media['id'] ?? DBA::lastInsertId();
			if (empty($id)) {
				// When the entry had already existed, the insert turned into an update
				// and no insert id is provided. So we fetch the id via the unique key.
				$existing = DBA::selectFirst('post-media', ['id'], ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
				$id       = $existing['id'] ?? null;
			}
			DI::logger()->info('Updated media', ['result' => $result, 'id' => $id, 'media' => $media]);
		} else {
			$id = null;
			DI::logger()->info('Nothing to update', ['media' => $media]);
		}

		if (isset($id) && isset($exif)) {
			MediaExif::insert($id, $media['uri-id'], $exif);
		}

		if (isset($id)) {
			self::addLinks($media);
		}

		return $result;
	}

	/**
	 * Add post-link entries for preview data
	 *
	 * @param array $media
	 * @return void
	 */
	private static function addLinks(array $media)
	{
		if (isset($media['preview'])) {
			Post\Link::getByLink($media['uri-id'], $media['preview']);
		}
		if (isset($media['author-image'])) {
			Post\Link::getByLink($media['uri-id'], $media['author-image']);
		}
		if (isset($media['publisher-image'])) {
			Post\Link::getByLink($media['uri-id'], $media['publisher-image']);
		}
	}

	/**
	 * Checks whether a player URL is safe to render into an iframe "src".
	 *
	 * The player iframe (content/iframe.tpl) is sandboxed with
	 * "allow-same-origin allow-scripts", so a same-origin src runs with this
	 * instance's origin and could read the viewer's DOM and cookies. Only
	 * absolute, cross-origin http(s) URLs (including protocol-relative
	 * "//host/…" ones) are considered safe; relative paths, URLs pointing back
	 * at the local host and non-HTTP schemes such as "javascript:"/"data:" are
	 * rejected.
	 *
	 * @param string $url
	 * @return bool
	 */
	private static function isEmbeddablePlayerUrl(string $url): bool
	{
		$url = trim($url);
		if ($url === '') {
			return false;
		}

		// Resolve protocol-relative URLs (//host/path) against https for the check
		if (str_starts_with($url, '//')) {
			$url = 'https:' . $url;
		}

		$scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
		$host   = parse_url($url, PHP_URL_HOST);

		if (empty($host) || !in_array($scheme, ['http', 'https'], true)) {
			return false;
		}

		return !DI::baseUrl()->isLocalUrl($url);
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
			if (!isset($media[$field]) || is_null($media[$field]) || $media[$field] === '') {
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
			'type'        => PostMedia::TYPE_DOCUMENT,
			'url'         => $href,
			'size'        => $length,
			'mimetype'    => $type,
			'description' => $title,
		]);

		return '[attach]href="' . $media['url'] . '" length="' . $media['size']
			. '" type="' . $media['mimetype'] . '" title="' . $media['description'] . '"[/attach]';
	}

	private static function setModified(array $media, string $lastModified): array
	{
		if (isset($media['modified']) && $media['modified'] != '') {
			return $media;
		}

		if ($lastModified == '') {
			return $media;
		}

		$media['modified'] = DateTimeFormat::utc($lastModified);
		$media['published'] ??= $media['modified'];

		return $media;
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
			if (preg_match('|.*?/search\?(.+)|', (string) $media['url'], $matches)) {
				return $media;
			}
			if (empty($media['mimetype']) || empty($media['size'])) {
				DI::logger()->debug('Unknown local link', ['url' => $media['url']]);
			}
		}

		if (($media['type'] == PostMedia::TYPE_HLS) && empty($media['mimetype'])) {
			$media['mimetype'] = 'application/vnd.apple.mpegurl';
		}

		// Fetch the mimetype or size if missing.
		if (Network::isValidHttpUrl($media['url']) && (empty($media['mimetype']) || $media['type'] == PostMedia::TYPE_HTML) && ($media['type'] != PostMedia::TYPE_IMAGE)) {
			$timeout = DI::config()->get('system', 'xrd_timeout');
			try {
				$curlResult = DI::httpClient()->head($media['url'], [HttpClientOptions::ACCEPT_CONTENT => HttpClientAccept::AS_DEFAULT, HttpClientOptions::TIMEOUT => $timeout, HttpClientOptions::REQUEST => HttpClientRequest::CONTENTTYPE]);
				$is_head    = true;

				// Workaround for systems that can't handle a HEAD request
				if (!$curlResult->isSuccess() && in_array($curlResult->getReturnCode(), [400, 403, 405])) {
					$curlResult = DI::httpClient()->get($media['url'], HttpClientAccept::AS_DEFAULT, [HttpClientOptions::TIMEOUT => $timeout, HttpClientOptions::HEADERS => ['Range' => 'bytes=0-100000'], HttpClientOptions::REQUEST => HttpClientRequest::CONTENTTYPE]);
					$is_head    = false;
				}
				if ($curlResult->isSuccess()) {
					if (!empty($curlResult->getContentType())) {
						$media['mimetype'] = $curlResult->getContentType();
					}
					if (empty($media['size']) && $is_head) {
						$media['size'] = (int) ($curlResult->getHeader('Content-Length')[0] ?? strlen($curlResult->getBodyString() ?? ''));
					}
					$media = self::setModified($media, $curlResult->getHeader('Last-Modified')[0] ?? '');
				} else {
					DI::logger()->notice('Could not fetch head', ['media' => $media, 'code' => $curlResult->getReturnCode()]);
				}
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			}
		}

		if (($media['type'] != PostMedia::TYPE_DOCUMENT) && !empty($media['mimetype'])) {
			$media = self::addType($media);
		}

		DI::logger()->debug('Got type for url', ['type' => $media['type'], 'mimetype' => $media['mimetype'] ?? '', 'url' => $media['url']]);

		if ($media['type'] == PostMedia::TYPE_IMAGE) {
			$imagedata = Images::getInfoFromURLCached($media['url'], empty($media['description']));
			if ($imagedata) {
				$media['mimetype'] = $imagedata['mime'];
				$media['size']     = $imagedata['size'];
				$media['width']    = $imagedata[0];
				$media['height']   = $imagedata[1];
				$media['blurhash'] = $imagedata['blurhash'] ?? null;
				$media['exif']     = $imagedata['exif']     ?? null;
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

		if ($media['type'] === PostMedia::TYPE_VIDEO) {
			$media = self::getVideoInformationByFFMPEG($media);
			$media = self::getVideoDimensionsByID3($media);
		}

		if ($media['type'] === PostMedia::TYPE_HLS) {
			$media = self::getHLSVideoDimensions($media);
		}

		if (in_array($media['type'], [PostMedia::TYPE_TEXT, PostMedia::TYPE_ACTIVITY, PostMedia::TYPE_LD, PostMedia::TYPE_JSON, PostMedia::TYPE_HTML, PostMedia::TYPE_XML, PostMedia::TYPE_PLAIN])) {
			$media = self::addAccount($media);
		}

		if (in_array($media['type'], [PostMedia::TYPE_ACTIVITY, PostMedia::TYPE_LD, PostMedia::TYPE_JSON]) || (self::isFederatedServer($media['url']) && !in_array($media['type'], [PostMedia::TYPE_HLS, PostMedia::TYPE_AUDIO, PostMedia::TYPE_VIDEO]))) {
			$media = self::addActivity($media);
		}

		if (in_array($media['type'], [PostMedia::TYPE_HTML, PostMedia::TYPE_LD, PostMedia::TYPE_JSON])) {
			$media = self::addPage($media);
		}

		if (empty($media['name'])) {
			$media['name'] = basename(parse_url((string) $media['url'], PHP_URL_PATH));
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

			return DBA::exists('gserver', ['nurl' => Strings::normaliseLink($baseurl), 'network' => Protocol::FEDERATED]);
		} catch (\Throwable $e) {
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
			$media['type'] = $media['type'] == PostMedia::TYPE_ACTIVITY ? PostMedia::TYPE_JSON : $media['type'];
			return $media;
		}

		$item = Post::selectFirst([], ['id' => $id, 'network' => Protocol::FEDERATED]);
		if (empty($item['id'])) {
			DI::logger()->debug('Not a federated activity', ['id' => $id, 'uri-id' => $media['uri-id'], 'url' => $media['url']]);
			$media['type'] = $media['type'] == PostMedia::TYPE_ACTIVITY ? PostMedia::TYPE_JSON : $media['type'];
			return $media;
		}

		if ($item['uri-id'] == $media['uri-id']) {
			DI::logger()->info('Media-Uri-Id is identical to Uri-Id', ['uri-id' => $media['uri-id']]);
			$media['type'] = $media['type'] == PostMedia::TYPE_ACTIVITY ? PostMedia::TYPE_JSON : $media['type'];
			return $media;
		}

		if (
			!empty($item['plink']) && Strings::compareLink($item['plink'], $media['url'])
			&& parse_url((string) $item['plink'], PHP_URL_HOST) != parse_url((string) $item['uri'], PHP_URL_HOST)
		) {
			DI::logger()->debug('Not a link to an activity', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'plink' => $item['plink'], 'uri' => $item['uri']]);
			$media['type'] = $media['type'] == PostMedia::TYPE_ACTIVITY ? PostMedia::TYPE_JSON : $media['type'];
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

		$media['type']            = PostMedia::TYPE_ACTIVITY;
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

		if (!empty($item['language'])) {
			$media['language'] = array_key_first(json_decode((string) $item['language'], true));
		}

		$media['published'] = $item['created'];
		$media['modified']  = $item['changed'];

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

		$media['type']            = PostMedia::TYPE_ACCOUNT;
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
		$media['language']        = null;
		$media['published']       = $contact['created'];
		$media['modified']        = $contact['updated'];

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
		// @todo Add detected AT Protocol activities and accounts here
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

		$media['type']            = PostMedia::TYPE_HTML;
		$media['size']            = $data['size']             ?? null;
		$media['author-url']      = $data['author_url']       ?? null;
		$media['author-name']     = $data['author_name']      ?? null;
		$media['author-image']    = $data['author_img']       ?? null;
		$media['publisher-url']   = $data['publisher_url']    ?? null;
		$media['publisher-name']  = $data['publisher_name']   ?? null;
		$media['publisher-image'] = $data['publisher_img']    ?? null;
		$media['player-url']      = $data['player']['embed']  ?? null;
		$media['player-height']   = $data['player']['height'] ?? null;
		$media['player-width']    = $data['player']['width']  ?? null;
		$media['embed-type']      = $data['embed']['type']    ?? null;
		$media['embed-html']      = $data['embed']['html']    ?? null;
		$media['embed-height']    = $data['embed']['height']  ?? null;
		$media['embed-width']     = $data['embed']['width']   ?? null;
		$media['page-type']       = $data['pagetype']         ?? null;
		$media['language']        = $data['language']         ?? null;
		$media['published']       = $data['published']        ?? null;
		$media['modified']        = $data['modified']         ?? null;
		$media['schematypes']     = isset($data['schematypes']) ? json_encode($data['schematypes']) : null;

		if (!isset($media['player-url']) && !isset($media['embed-html']) && DI::config()->get('system', 'add_page_media')) {
			if (isset($data['audio']) && sizeof($data['audio']) == 1) {
				foreach ($data['audio'] as $entry) {
					self::insertMedia($entry, $media['uri-id']);
				}
			}

			if (isset($data['video']) && sizeof($data['video']) == 1) {
				foreach ($data['video'] as $entry) {
					self::insertMedia($entry, $media['uri-id']);
				}
			}
		}

		return $media;
	}

	private static function insertMedia(array $element, int $uri_id)
	{
		if (empty($element['src']) || $uri_id <= 0) {
			return;
		}

		$media                = ['uri-id' => $uri_id];
		$media['type']        = PostMedia::TYPE_UNKNOWN;
		$media['url']         = $element['src'];
		$media['mimetype']    = $element['contenttype'] ?? null;
		$media['name']        = $element['name']        ?? null;
		$media['description'] = $element['description'] ?? null;
		$media['size']        = $element['size']        ?? null;
		$media['height']      = $element['height']      ?? null;
		$media['width']       = $element['width']       ?? null;
		if (!empty($element['uploaded'])) {
			$media['modified'] = DateTimeFormat::utc($element['uploaded']);
		}
		$result = self::insert($media);
		DI::logger()->debug('Found media in page', ['result' => $result, 'uri-id' => $uri_id, 'media' => $media]);
	}

	/**
	 * Fetch media data from local resources
	 * @param array $media
	 * @return array media with added data
	 */
	private static function fetchLocalData(array $media): array
	{
		if (preg_match('|.*?/attach/(\d+)|', (string) $media['url'], $matches)) {
			$attachment = Attach::selectFirst(['id', 'filename', 'filetype', 'filesize'], ['id' => $matches[1]]);
			if (!empty($attachment)) {
				$media['attach-id'] = $attachment['id'];
				$media['name']      = $attachment['filename'];
				$media['mimetype']  = $attachment['filetype'];
				$media['size']      = $attachment['filesize'];
			}
			return $media;
		}

		if (!preg_match('|.*?/photo/(.*[a-fA-F0-9])\-(.*[0-9])\..*[\w]|', (string) $media['url'], $matches)) {
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
			return PostMedia::TYPE_UNKNOWN;
		}

		$filetype = strtolower($type[0]);
		$subtype  = strtolower($type[1]);

		if ($filetype == 'image') {
			$type = PostMedia::TYPE_IMAGE;
		} elseif (($filetype == 'video') && in_array($subtype, ['x-mpegurl', 'mpegurl'])) {
			$type = PostMedia::TYPE_HLS;
		} elseif ($filetype == 'video') {
			$type = PostMedia::TYPE_VIDEO;
		} elseif (($filetype == 'audio') && in_array($subtype, ['x-mpegurl', 'mpegurl'])) {
			$type = PostMedia::TYPE_HLS;
		} elseif ($filetype == 'audio') {
			$type = PostMedia::TYPE_AUDIO;
		} elseif (($filetype == 'text') && ($subtype == 'html')) {
			$type = PostMedia::TYPE_HTML;
		} elseif (($filetype == 'text') && ($subtype == 'xml')) {
			$type = PostMedia::TYPE_XML;
		} elseif (($filetype == 'text') && ($subtype == 'plain')) {
			$type = PostMedia::TYPE_PLAIN;
		} elseif ($filetype == 'text') {
			$type = PostMedia::TYPE_TEXT;
		} elseif (($filetype == 'application') && ($subtype == 'x-bittorrent')) {
			$type = PostMedia::TYPE_TORRENT;
		} elseif (($filetype == 'application') && in_array($subtype, ['vnd.apple.mpegurl', 'x-mpegurl', 'mpegurl'])) {
			$type = PostMedia::TYPE_HLS;
		} elseif (($filetype == 'application') && ($subtype == 'activity+json')) {
			$type = PostMedia::TYPE_ACTIVITY;
		} elseif (($filetype == 'application') && ($subtype == 'ld+json')) {
			$type = PostMedia::TYPE_LD;
		} elseif (($filetype == 'application') && ($subtype == 'json')) {
			$type = PostMedia::TYPE_JSON;
		} elseif ($filetype == 'application') {
			$type = PostMedia::TYPE_APPLICATION;
		} else {
			$type = PostMedia::TYPE_UNKNOWN;
			DI::logger()->info('Unknown type', ['filetype' => $filetype, 'subtype' => $subtype, 'media' => $mimeType]);
		}

		DI::logger()->debug('Detected type', ['type' => $type, 'filetype' => $filetype, 'subtype' => $subtype, 'media' => $mimeType]);
		return $type;
	}

	/**
	 * Fetch video information (dimensions and blurhash) using ffmpeg
	 *
	 * @param array $media Media array
	 * @return array media with added dimensions and blurhash
	 */
	private static function getVideoInformationByFFMPEG(array $media): array
	{
		if (!DI::config()->get('system', 'ffmpeg_installed')) {
			return $media;
		}

		if (isset($media['width']) && isset($media['height']) && is_numeric($media['width']) && is_numeric($media['height']) && isset($media['blurhash'])) {
			return $media;
		}

		DI::logger()->debug('Fetch video information', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);

		$image = new Image('');
		$image->getFromVideoUrl($media['url']);
		if ($image->isValid()) {
			$media['blurhash'] = $image->getBlurHash();
			$media['width']    = $image->getWidth();
			$media['height']   = $image->getHeight();
			DI::logger()->debug('Detected video dimensions via FFMpeg preview', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'width' => $media['width'], 'height' => $media['height']]);
			return $media;
		} else {
			try {
				$ffmpeg = FFMpeg::create();
				/** @var \FFMpeg\Media\Video $video */
				$video = $ffmpeg->open($media['url']);

				$has_video = false;
				$has_audio = false;
				foreach ($video->getStreams() as $stream) {
					if ($stream->isVideo()) {
						$has_video = true;

						$media['width']  = $stream->get('width');
						$media['height'] = $stream->get('height');
						DI::logger()->debug('Detected video dimensions via FFMpeg', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'width' => $media['width'], 'height' => $media['height']]);
					}
					if ($stream->isAudio()) {
						$has_audio = true;
					}
				}
				if ($has_audio && !$has_video) {
					$media['width']  = 0;
					$media['height'] = 0;
					DI::logger()->debug('Detected audio file via FFMpeg', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
				}
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['url' => $media['url'], 'code' => $th->getCode(), 'message' => $th->getMessage()]);
			}
		}

		return $media;
	}

	/**
	 * Fetch video dimensions using getID3
	 *
	 * @param array $media     Media array
	 * @return array media with added dimensions
	 */
	private static function getVideoDimensionsByID3(array $media): array
	{
		if (isset($media['width']) && isset($media['height']) && is_numeric($media['width']) && is_numeric($media['height'])) {
			return $media;
		}

		DI::logger()->debug('Fetch video dimensions', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
		$timestamp  = microtime(true);
		$timeout    = DI::config()->get('system', 'xrd_timeout');
		$options    = [HttpClientOptions::TIMEOUT => $timeout, HttpClientOptions::HEADERS => ['Range' => 'bytes=0-1000000'], HttpClientOptions::REQUEST => HttpClientRequest::MEDIAVERIFIER];
		$curlResult = DI::httpClient()->get($media['url'], HttpClientAccept::VIDEO, $options);
		if (!$curlResult->isSuccess()) {
			DI::logger()->notice('Could not fetch video', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'code' => $curlResult->getReturnCode()]);
			return $media;
		}

		$media = self::setModified($media, $curlResult->getHeader('Last-Modified')[0] ?? '');

		$video = $curlResult->getBodyString() ?? '';
		if (!$video) {
			DI::logger()->notice('Empty video content', ['uri-id' => $media['uri-id'], 'media' => $media]);
			return $media;
		}

		$tempfile = tempnam(System::getTempPath(), 'video-');
		file_put_contents($tempfile, $video);
		$getID3 = new getID3();
		$info   = $getID3->analyze($tempfile);
		unlink($tempfile);
		$runtime = number_format(microtime(true) - $timestamp, 3);

		if (isset($info['video']['resolution_x']) && isset($info['video']['resolution_y'])) {
			$media['width']  = $info['video']['resolution_x'];
			$media['height'] = $info['video']['resolution_y'];
			DI::logger()->debug('Detected video dimensions', ['runtime' => $runtime, 'uri-id' => $media['uri-id'], 'url' => $media['url'], 'width' => $media['width'], 'height' => $media['height']]);
		} elseif (isset($info['audio'])) {
			$media['width']  = 0;
			$media['height'] = 0;
			DI::logger()->debug('Detected audio file', ['runtime' => $runtime, 'uri-id' => $media['uri-id'], 'url' => $media['url']]);
		} elseif (isset($info['error'])) {
			DI::logger()->info('Error analyzing video', ['runtime' => $runtime, 'uri-id' => $media['uri-id'], 'url' => $media['url'], 'error' => $info['error']]);
		} else {
			DI::logger()->info('No video dimensions found', ['runtime' => $runtime, 'uri-id' => $media['uri-id'], 'url' => $media['url'], 'info' => $info]);
		}
		return $media;
	}

	/**
	 * Fetch HLS video dimensions from the playlist
	 *
	 * @param array $media Media array
	 * @return array media with added dimensions
	 */
	private static function getHLSVideoDimensions(array $media): array
	{
		if (isset($media['width']) && isset($media['height']) && is_numeric($media['width']) && is_numeric($media['height'])) {
			return $media;
		}

		$resolutions = [];

		$curlResult = DI::httpClient()->get($media['url'], HttpClientAccept::HLS, [HttpClientOptions::REQUEST => HttpClientRequest::MEDIAVERIFIER]);
		if (!$curlResult->isSuccess()) {
			DI::logger()->notice('Could not fetch video', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'code' => $curlResult->getReturnCode()]);
			return $media;
		}

		$media = self::setModified($media, $curlResult->getHeader('Last-Modified')[0] ?? '');

		foreach (explode("\n", $curlResult->getBodyString() ?? '') as $line) {
			if (str_starts_with(trim($line), '#EXT-X-STREAM-INF')) {
				if (preg_match('/RESOLUTION=([\d]+)x([\d]+)/', $line, $matches)) {
					$resolutions[$matches[1]] = [(int) $matches[1], (int) $matches[2]];
				}
			}
		}

		if (!$resolutions) {
			DI::logger()->debug('No resolutions found', ['uri-id' => $media['uri-id'], 'url' => $media['url']]);
			return $media;
		}

		krsort($resolutions);
		$resolution = current($resolutions);

		$media['width']  = $resolution[0];
		$media['height'] = $resolution[1];

		DI::logger()->debug('Detected HLS resolutions', ['uri-id' => $media['uri-id'], 'url' => $media['url'], 'resolution' => $resolution]);

		return $media;
	}

	/**
	 * Resolve an HLS playlist URL to the address the browser finally ends up on
	 *
	 * Some sites hand out a permalink that only redirects
	 * to a CDN. A browser following that redirect fails CORS on the intermediate
	 * response, so hls.js has to be pointed at the resolved URL directly. The
	 * redirect target may change over time, hence this runs at render time and is
	 * only cached for a short while.
	 *
	 * @param string $url Playlist URL as stored with the media
	 * @return string Resolved URL, or the input when it can't be resolved
	 */
	public static function resolvePlaylistUrl(string $url): string
	{
		$cacheKey = 'hls-playlist-url:' . $url;

		$resolved = DI::cache()->get($cacheKey);
		if (!is_null($resolved)) {
			return $resolved;
		}

		$curlResult = DI::httpClient()->get($url, HttpClientAccept::HLS, [HttpClientOptions::REQUEST => HttpClientRequest::MEDIAVERIFIER]);

		$resolved = ($curlResult->isSuccess() && $curlResult->isRedirectUrl() && $curlResult->getRedirectUrl() !== '')
			? $curlResult->getRedirectUrl()
			: $url;

		DI::cache()->set($cacheKey, $resolved, Duration::QUARTER_HOUR);

		return $resolved;
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
		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]$endmatchpattern#ism", (string) $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (self::isLinkToImagePage($picture[1], $picture[2])) {
					$body  = str_replace($picture[0], '', $body);
					$image = str_replace(['-1.', '-2.'], '-0.', $picture[2]);

					$attachments[$image] = [
						'uri-id'      => $uriid,
						'type'        => PostMedia::TYPE_IMAGE,
						'url'         => $image,
						'preview'     => $picture[2],
						'description' => $picture[3],
					];
				} elseif (self::isLinkToPhoto($picture[1], $picture[2])) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => PostMedia::TYPE_IMAGE,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => $picture[3],
					];
				} elseif ($removepicturelinks) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => PostMedia::TYPE_UNKNOWN,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => $picture[3],
					];
				}
			}
		}

		if (preg_match_all("/\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]$endmatchpattern/Usi", (string) $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);

				$attachments[$picture[1]] = ['uri-id' => $uriid, 'type' => PostMedia::TYPE_IMAGE, 'url' => $picture[1], 'description' => $picture[2]];
			}
		}

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]$endmatchpattern#ism", (string) $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (self::isLinkToImagePage($picture[1], $picture[2])) {
					$body  = str_replace($picture[0], '', $body);
					$image = str_replace(['-1.', '-2.'], '-0.', $picture[2]);

					$attachments[$image] = [
						'uri-id'      => $uriid,
						'type'        => PostMedia::TYPE_IMAGE,
						'url'         => $image,
						'preview'     => $picture[2],
						'description' => null,
					];
				} elseif (self::isLinkToPhoto($picture[1], $picture[2])) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => PostMedia::TYPE_IMAGE,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => null,
					];
				} elseif ($removepicturelinks) {
					$body = str_replace($picture[0], '', $body);

					$attachments[$picture[1]] = [
						'uri-id'      => $uriid,
						'type'        => PostMedia::TYPE_UNKNOWN,
						'url'         => $picture[1],
						'preview'     => $picture[2],
						'description' => null,
					];
				}
			}
		}

		if (preg_match_all("/\[img\]([^\[\]]*)\[\/img\]$endmatchpattern/ism", (string) $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$body = str_replace($picture[0], '', $body);

				$attachments[$picture[1]] = ['uri-id' => $uriid, 'type' => PostMedia::TYPE_IMAGE, 'url' => $picture[1]];
			}
		}

		if (preg_match_all("/\[audio\]([^\[\]]*)\[\/audio\]$endmatchpattern/ism", (string) $body, $audios, PREG_SET_ORDER)) {
			foreach ($audios as $audio) {
				$body = str_replace($audio[0], '', $body);

				$attachments[$audio[1]] = ['uri-id' => $uriid, 'type' => PostMedia::TYPE_AUDIO, 'url' => $audio[1]];
			}
		}

		if (preg_match_all("/\[video\]([^\[\]]*)\[\/video\]$endmatchpattern/ism", (string) $body, $videos, PREG_SET_ORDER)) {
			foreach ($videos as $video) {
				$body = str_replace($video[0], '', $body);

				$attachments[$video[1]] = ['uri-id' => $uriid, 'type' => PostMedia::TYPE_VIDEO, 'url' => $video[1]];
			}
		}

		if (preg_match_all("/\[embed\]([^\[\]]*)\[\/embed\]$endmatchpattern/ism", (string) $body, $embeds, PREG_SET_ORDER)) {
			foreach ($embeds as $embed) {
				$body = str_replace($embed[0], '', $body);

				$attachments[$embed[1]] = ['uri-id' => $uriid, 'type' => PostMedia::TYPE_UNKNOWN, 'url' => $embed[1]];
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

		return trim((string) $body);
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
		if (preg_match_all("/\[url\](https?:.*?)\[\/url\]/ism", (string) $body, $matches)) {
			foreach ($matches[1] as $url) {
				DI::logger()->info('Got page url (link without description)', ['uri-id' => $uriid, 'url' => $url]);
				$result = self::insert(['uri-id' => $uriid, 'type' => PostMedia::TYPE_UNKNOWN, 'url' => $url], false);
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
		if (preg_match_all("#\[url=(https?://.+?)].+?\[/url]#ism", (string) $body, $matches)) {
			foreach ($matches[1] as $url) {
				DI::logger()->info('Got page url (link with description)', ['uri-id' => $uriid, 'url' => $url]);
				$result = self::insert(['uri-id' => $uriid, 'type' => PostMedia::TYPE_UNKNOWN, 'url' => $url], false);
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
		DBA::update('post-media', ['type' => PostMedia::TYPE_UNKNOWN], ['uri-id' => $uriid, 'type' => PostMedia::TYPE_HTML, 'url' => $url]);
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
			'type'           => PostMedia::TYPE_HTML,
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
			$media['type']        = PostMedia::TYPE_DOCUMENT;
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
		$condition = ["`uri-id` = ? AND `type` != ?", $uri_id, PostMedia::TYPE_UNKNOWN];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return DBA::selectToArray('post-media', [], $condition, ['order' => ['id']]);
	}

	public static function getByURL(int $uri_id, string $url, array $types = [])
	{
		$condition = ["`uri-id` = ? AND `url` = ? AND `type` != ?", $uri_id, $url, PostMedia::TYPE_UNKNOWN];

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
		$condition = ["`uri-id` = ? AND `type` != ?", $uri_id, PostMedia::TYPE_UNKNOWN];

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
	public static function addAttachmentsToBody(int $uriid, string $body = '', array $types = [PostMedia::TYPE_IMAGE, PostMedia::TYPE_AUDIO, PostMedia::TYPE_VIDEO]): string
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

		if (preg_match("/.*(\[attachment.*?\].*?\[\/attachment\]).*/ism", (string) $original_body, $match)) {
			$body .= "\n" . $match[1];
		}

		return $body;
	}

	public static function addAttachmentToBody(array $media, string $body): string
	{
		if (Item::containsLink($body, $media['preview'] ?? $media['url'], $media['type'])) {
			return $body;
		}

		if ($media['type'] == PostMedia::TYPE_IMAGE) {
			$body .= "\n" . Images::getBBCodeByUrl($media['url'], $media['preview'], $media['description'] ?? '');
		} elseif ($media['type'] == PostMedia::TYPE_AUDIO) {
			$body .= "\n[audio]" . $media['url'] . "[/audio]\n";
		} elseif ($media['type'] == PostMedia::TYPE_VIDEO) {
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

		$links = self::getByURIId($uriid, [PostMedia::TYPE_HTML]);
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
			]],
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
		$links = self::getByURIId($uriid, [PostMedia::TYPE_HTML]);
		if (empty($links)) {
			return $body;
		}

		if (strpos($body, (string) $links[0]['url'])) {
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
		return DI::baseUrl() . '/photo/preview/'
			. (Proxy::getPixelsFromSize($size) ? Proxy::getPixelsFromSize($size) . '/' : '')
			. $id;
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
		return DI::baseUrl() . '/photo/media/'
			. (Proxy::getPixelsFromSize($size) ? Proxy::getPixelsFromSize($size) . '/' : '')
			. $id;
	}

	/**
	 * Fetch the uri-id of an attached uri-post for a given uri-id
	 *
	 * @param integer $uri_id Uri-Id of the post
	 * @return integer uri-id of the first attached post
	 */
	public static function getActivityUriId(int $uri_id): int
	{
		$posts = self::getByURIId($uri_id, [PostMedia::TYPE_ACTIVITY]);
		if (!$posts) {
			return 0;
		}
		return reset($posts)['media-uri-id'];
	}
}
