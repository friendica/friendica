<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Post\Repository;

use DOMDocument;
use DOMXPath;
use Friendica\App\BaseURL;
use Friendica\BaseRepository;
use Friendica\Content\Item;
use Friendica\Content\Post\Collection\PostMedias as PostMediasCollection;
use Friendica\Content\Post\Entity\PostMedia as PostMediaEntity;
use Friendica\Content\Post\Factory\PostMedia as PostMediaFactory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\ParseUrl;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Repository for PostMedia entities.
 *
 * Provides persistence and rendering helpers for post media objects
 * (images, audio, video, embeds, etc.).
 *
 * @package Friendica\Content\Post\Repository
 */
class PostMedia extends BaseRepository
{
	protected static $table_name = 'post-media';

	/** @var PostMediaFactory */
	protected $factory;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var IManageConfigValues */
	private $config;
	/** @var BaseURL */
	private $baseURL;
	/** @var Item */
	private $item;

	/**
	 * PostMedia repository constructor.
	 *
	 * @param Database $database Database connection wrapper
	 * @param LoggerInterface $logger PSR-3 logger
	 * @param PostMediaFactory $factory Factory for creating entities
	 * @param IManagePersonalConfigValues $pConfig Personal configuration access
	 * @param IManageConfigValues $config Global configuration access
	 * @param BaseURL $baseURL Base URL helper
	 * @param Item $item Item helper
	 */
	public function __construct(Database $database, LoggerInterface $logger, PostMediaFactory $factory, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, BaseURL $baseURL, Item $item)
	{
		parent::__construct($database, $logger, $factory);

		$this->baseURL = $baseURL;
		$this->pConfig = $pConfig;
		$this->config  = $config;
		$this->item    = $item;
	}

	/**
	 * Low level select helper used by higher-level selectors.
	 *
	 * Queries the `post-media` table and maps rows to entities. Invalid rows
	 * are logged and skipped.
	 *
	 * @param array $condition DBA-style condition array
	 * @param array $params Optional positional parameters
	 * @return PostMediasCollection Collection of PostMediaEntity objects
	 */
	protected function _select(array $condition, array $params = []): PostMediasCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new PostMediasCollection();
		foreach ($rows as $fields) {
			try {
				$Entities[] = $this->factory->createFromTableRow($fields);
			} catch (\Throwable $e) {
				$this->logger->warning('Invalid media row', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'fields' => $fields]);
			}
		}

		return $Entities;
	}

	/**
	 * Return a single PostMedia entity selected by its id.
	 *
	 * @param int $postMediaId Primary key of the media row
	 * @return PostMediaEntity The entity for the requested id
	 * @throws NotFoundException
	 */
	public function selectById(int $postMediaId): PostMediaEntity
	{
		$fields = $this->_selectFirstRowAsArray(['id' => $postMediaId]);

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * Select PostMedia collection for the given uri-id and optional types.
	 *
	 * @param int $uriId URI id to select media for
	 * @param array $types Optional list of media types to restrict the result
	 * @return PostMediasCollection Collection of PostMedia entities
	 */
	public function selectByUriId(int $uriId, array $types = []): PostMediasCollection
	{
		$condition = ["`uri-id` = ? AND `type` != ?", $uriId, Post\Media::UNKNOWN];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		return $this->_select($condition);
	}

	/**
	 * Select a single PostMedia entity for the given uri-id and media URL.
	 *
	 * @param int $uriId URI id to search within
	 * @param string $url Full media URL to match
	 * @param array $types Optional list of media types to restrict the search
	 * @return PostMediaEntity|null Matching media entity or null when not found
	 */
	public function selectByURL(int $uriId, string $url, array $types = []): ?PostMediaEntity
	{
		$condition = ["`uri-id` = ? AND `url` = ? AND `type` != ?", $uriId, $url, Post\Media::UNKNOWN];

		if (!empty($types)) {
			$condition = DBA::mergeConditions($condition, ['type' => $types]);
		}

		$medias = $this->_select($condition);
		return $medias[0] ?? null;
	}

	/**
	 * Map a PostMedia entity to an array of storage fields.
	 *
	 * The returned array is suitable for insert/update operations on the
	 * `post-media` table. When $includeId is true the `id` field is included.
	 *
	 * @param PostMediaEntity $PostMedia Entity to map
	 * @param bool $includeId Include the `id` field when true
	 * @return array Associative array of storage fields
	 */
	public function getFields(PostMediaEntity $PostMedia, bool $includeId = false): array
	{
		$fields = [
			'uri-id'          => $PostMedia->uriId,
			'url'             => $PostMedia->url->__toString(),
			'type'            => $PostMedia->type,
			'mimetype'        => $PostMedia->mimetype->__toString(),
			'height'          => $PostMedia->height,
			'width'           => $PostMedia->width,
			'size'            => $PostMedia->size,
			'preview'         => $PostMedia->preview ? $PostMedia->preview->__toString() : null,
			'preview-height'  => $PostMedia->previewHeight,
			'preview-width'   => $PostMedia->previewWidth,
			'description'     => $PostMedia->description,
			'name'            => $PostMedia->name,
			'author-url'      => $PostMedia->authorUrl ? $PostMedia->authorUrl->__toString() : null,
			'author-name'     => $PostMedia->authorName,
			'author-image'    => $PostMedia->authorImage ? $PostMedia->authorImage->__toString() : null,
			'publisher-url'   => $PostMedia->publisherUrl ? $PostMedia->publisherUrl->__toString() : null,
			'publisher-name'  => $PostMedia->publisherName,
			'publisher-image' => $PostMedia->publisherImage ? $PostMedia->publisherImage->__toString() : null,
			'media-uri-id'    => $PostMedia->activityUriId,
			'blurhash'        => $PostMedia->blurhash,
			'player-url'      => $PostMedia->playerUrl,
			'player-height'   => $PostMedia->playerHeight,
			'player-width'    => $PostMedia->playerWidth,
			'embed-type'      => $PostMedia->embedType,
			'embed-html'      => $PostMedia->embedHtml,
			'embed-height'    => $PostMedia->embedHeight,
			'embed-width'     => $PostMedia->embedWidth,
			'page-type'       => $PostMedia->pageType,
			'schematypes'     => $PostMedia->schemaTypes ? json_encode($PostMedia->schemaTypes) : null,
			'attach-id'       => $PostMedia->attachId,
			'language'        => $PostMedia->language,
			'published'       => $PostMedia->published,
			'modified'        => $PostMedia->modified,
		];

		if ($includeId) {
			$fields['id'] = $PostMedia->id;
		}
		return $fields;
	}

	/**
	 * Save a PostMedia entity to the database.
	 *
	 * Updates when an id is present, otherwise inserts and returns the
	 * persisted entity.
	 *
	 * @param PostMediaEntity $PostMedia Entity to persist
	 * @return PostMediaEntity Persisted entity
	 */
	public function save(PostMediaEntity $PostMedia): PostMediaEntity
	{
		$fields = $this->getFields($PostMedia);

		if ($PostMedia->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $PostMedia->id]);
		} else {
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$newPostMediaId = $this->db->lastInsertId();

			$PostMedia = $this->selectById($newPostMediaId);
		}

		return $PostMedia;
	}


	/**
	 * Split the attachment media into four segments: `visual`, `link`, `additional`, 'hidden'.
	 *
	 * @param int $uri_id URI id the attachments belong to
	 * @param array $links List of link URLs that should be ignored
	 * @param bool $has_media Whether the item has media at all
	 * @param bool $local_visitor Whether the viewer is local (affects remote-media handling)
	 * @return PostMediasCollection[] Associative array with keys `visual`, `link`, `additional`, 'hidden'
	 */
	public function splitAttachments(int $uri_id, array $links = [], bool $has_media = true, bool $local_visitor = true): array
	{
		$attachments = [
			'visual'     => new PostMediasCollection(),
			'link'       => new PostMediasCollection(),
			'additional' => new PostMediasCollection(),
			'hidden'     => new PostMediasCollection(),
		];

		if (!$has_media) {
			return $attachments;
		}

		$PostMedias = $this->selectByUriId($uri_id);
		if (!count($PostMedias)) {
			return $attachments;
		}

		$heights    = [];
		$selected   = '';
		$previews   = [];
		$video      = [];
		$is_hls     = false;
		$is_torrent = false;

		// Check if there is any HLS media
		// This is used to determine if we should suppress some of the media types
		/** @var PostMediaEntity $PostMedia */
		foreach ($PostMedias as $PostMedia) {
			if ($PostMedia->type == PostMediaEntity::TYPE_HLS) {
				$is_hls = true;
			}
			if ($PostMedia->type == PostMediaEntity::TYPE_TORRENT) {
				$is_torrent = true;
			}
		}

		/** @var PostMediaEntity $PostMedia */
		foreach ($PostMedias as $PostMedia) {
			foreach ($links as $link) {
				if (Strings::compareLink($link, $PostMedia->url)) {
					continue 2;
				}
			}

			// Avoid adding separate media entries for previews
			foreach ($previews as $preview) {
				if (Strings::compareLink($preview, $PostMedia->url)) {
					continue 2;
				}
			}

			// Currently these two types are ignored here.
			// Posts are added differently and contacts are not displayed as attachments.
			if (in_array($PostMedia->type, [PostMediaEntity::TYPE_ACCOUNT, PostMediaEntity::TYPE_ACTIVITY])) {
				continue;
			}

			if ($is_hls && in_array($PostMedia->type, [PostMediaEntity::TYPE_VIDEO, PostMediaEntity::TYPE_TORRENT])) {
				// If there is HLS media, we don't want to show any other video or torrents.
				// This is mainly a workaround for Peertube.
				continue;
			}

			if (!empty($PostMedia->preview)) {
				$previews[] = $PostMedia->preview;
			}

			if ($PostMedia->type == PostMediaEntity::TYPE_HTML) {
				$attachments['link'][] = $PostMedia;
				continue;
			}

			if (!$local_visitor && !$this->displayMedia($PostMedia)) {
				$attachments['hidden'][] = $PostMedia;
			} elseif (in_array($PostMedia->type, [PostMediaEntity::TYPE_AUDIO, PostMediaEntity::TYPE_IMAGE, PostMediaEntity::TYPE_HLS])) {
				$attachments['visual'][] = $PostMedia;
			} elseif ($PostMedia->type == PostMediaEntity::TYPE_VIDEO) {
				if ($is_torrent) {
					// We stored older Peertube videos not as HLS, but with many different resolutions.
					// We pick a moderate one. We detect Peertube via their also existing Torrent link.
					$heights[$PostMedia->height]     = (string)$PostMedia->url;
					$video[(string) $PostMedia->url] = $PostMedia;
				} else {
					$attachments['visual'][] = $PostMedia;
				}
			} else {
				$attachments['additional'][] = $PostMedia;
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
	 * Decide whether a media item should be displayed based on configuration.
	 *
	 * @param PostMediaEntity $PostMedia Media entity to check
	 * @return bool display mode
	 */
	private function displayMedia(PostMediaEntity $PostMedia): bool
	{
		if (!in_array($PostMedia->type, [PostMediaEntity::TYPE_AUDIO, PostMediaEntity::TYPE_IMAGE, PostMediaEntity::TYPE_HLS, PostMediaEntity::TYPE_VIDEO])) {
			return true;
		}

		if ($this->baseURL->isLocalUri($PostMedia->url)) {
			return $this->config->get('system', 'display_local_media');
		} else {
			return $this->config->get('system', 'display_remote_media');
		}
	}

	/**
	 * Create a PostMedia entity from a remote URL using siteinfo parsing.
	 *
	 * @param string $url Remote resource URL
	 * @return PostMediaEntity Created media entity
	 */
	public function createFromUrl(string $url): PostMediaEntity
	{
		$data  = ParseUrl::getSiteinfoCached($url);
		$media = $this->factory->createFromParseUrl($data);
		return $this->fetchAdditionalData($media);
	}

	/**
	 * Fetch additional metadata for a PostMedia and return an updated entity.
	 *
	 * @param PostMediaEntity $postMedia Media entity to enrich
	 * @return PostMediaEntity Enriched media entity
	 */
	public function fetchAdditionalData(PostMediaEntity $postMedia): PostMediaEntity
	{
		$data = $this->getFields($postMedia, true);
		$data = Post\Media::fetchAdditionalData($data);
		return $this->factory->createFromTableRow($data);
	}

	/**
	 * Replace `[embed]` link placeholders in the provided HTML with embed markup.
	 *
	 * @param string $html HTML that may contain anchor tags with class `embed`
	 * @param int $uid Viewer user id used for personal preferences
	 * @param int $uri_id URI id of the item being rendered
	 * @param bool $local_visitor Whether the viewer is local
	 * @return string Modified HTML with embeds replaced
	 */
	public function addEmbed(string $html, int $uid, int $uri_id, bool $local_visitor = true): string
	{
		if ($html == '') {
			return $html;
		}

		$allow_embed = $this->pConfig->get($uid, 'system', 'embed_remote_media', false);

		$changed = false;

		$tmp = new DOMDocument();
		$doc = new DOMDocument();
		@$doc->loadHTML(mb_convert_encoding('<span>' . $html . '</span>', 'HTML-ENTITIES', "UTF-8"), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$xpath = new DOMXPath($doc);
		$list  = $xpath->query('//a[@class="embed"]');
		foreach ($list as $node) {
			$href = '';
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					if ($attribute->name == 'href') {
						$href = $attribute->value;
						break;
					}
				}
			}
			if (empty($href)) {
				continue;
			}

			if ($uri_id > 0) {
				$media = $this->selectByURL($uri_id, $href, [Post\Media::HTML, Post\Media::AUDIO, Post\Media::VIDEO, Post\Media::HLS]);
			}
			if (!isset($media)) {
				$media = $this->createFromUrl($href);
				if ($uri_id > 0) {
					$fields = $this->getFields($media);

					$fields['uri-id'] = $uri_id;

					$result = Post\Media::insert($fields);
					$this->logger->debug('Media is now assigned to this post', ['uri-id' => $uri_id, 'uid' => $uid, 'result' => $result, 'fields' => $fields]);
				}
			}

			if (!$local_visitor && !$this->displayMedia($media)) {
				$player = '<span></span>';
			} elseif ($media->type === Post\Media::AUDIO) {
				$player = $this->getAudioAttachment($media);
			} elseif (in_array($media->type, [Post\Media::VIDEO, Post\Media::HLS])) {
				$player = $this->getVideoAttachment($media, $uid);
			} elseif ($allow_embed && $media->hasPlayerUrl() && $media->hasPlayerHeight()) {
				$player = $this->getPlayerIframe($media);
			} elseif ($allow_embed && $media->hasEmbedHtml() && !$media->isPhoto()) {
				$player = $this->getEmbedIframe($media);
			} else {
				$player = $this->getLinkAttachment($media);
			}
			@$tmp->loadHTML(mb_convert_encoding($player, 'HTML-ENTITIES', "UTF-8"), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			$div      = $tmp->documentElement;
			$imported = $doc->importNode($div, true);
			$node->parentNode->replaceChild($imported, $node);
			$changed = true;
		}

		if (!$changed) {
			return $html;
		}

		$html = trim($doc->saveHTML());
		if (substr($html, 0, 6) == '<span>' && substr($html, -7) == '</span>') {
			$html = substr($html, 6, -7);
		}
		return $html;
	}

	/**
	 * Render a video attachment as HTML.
	 *
	 * @param PostMediaEntity $postMedia Media entity to render
	 * @param int $uid Viewer user id
	 * @return string HTML snippet for the video attachment
	 */
	public function getVideoAttachment(PostMediaEntity $postMedia, int $uid): string
	{
		if ($postMedia->preview || ($postMedia->blurhash && $this->config->get('system', 'ffmpeg_installed'))) {
			$preview_url = $this->baseURL . $postMedia->getPreviewPath(Proxy::SIZE_MEDIUM);
		} else {
			$preview_url = '';
		}

		if ($this->pConfig->get($uid, 'system', 'embed_media', false) && $postMedia->hasPlayerUrl() && $postMedia->hasPlayerHeight()) {
			$media = $this->getPlayerIframe($postMedia);
		} elseif ($this->pConfig->get($uid, 'system', 'embed_media', false) && $postMedia->hasEmbedHtml() && !$postMedia->isPhoto()) {
			$media = $this->getEmbedIframe($postMedia);
		} else {
			if ($postMedia->width === 0 && $postMedia->height === 0) {
				return $this->getAudioAttachment($postMedia);
			}
			if ($this->config->get('system', 'videojs')) {
				$template = 'content/videojs.tpl';
			} else {
				$template = $postMedia->type == Post\Media::HLS ? 'content/hls.tpl' : 'content/video.tpl';
			}
			$media = Renderer::replaceMacros(Renderer::getMarkupTemplate($template), [
				'$video' => [
					'id'          => $postMedia->id,
					'src'         => (string)$postMedia->url,
					'name'        => $postMedia->name ?: $postMedia->url,
					'preview'     => $preview_url,
					'mime'        => (string)$postMedia->mimetype,
					'height'      => 'auto',
					'width'       => '100%',
					'style'       => 'aspect-ratio:' . $postMedia->width . '/' . $postMedia->height . ';',
					'description' => $postMedia->description,
				],
			]);
		}
		return $media;
	}

	/**
	 * Build an iframe-based player markup for the given media.
	 *
	 * @param PostMediaEntity $postMedia Media entity providing player URL and dimensions
	 * @return string HTML markup for the iframe player
	 */
	public function getPlayerIframe(PostMediaEntity $postMedia): string
	{
		if ($postMedia->playerUrl == '') {
			return '';
		}

		$iframe_style = '';
		$height       = '100%';
		$width        = '100%';

		if ($postMedia->isVideo()) {
			if ($postMedia->hasPlayerWidth() && $postMedia->hasPlayerHeight()) {
				$iframe_style .= 'aspect-ratio:' . $postMedia->playerWidth . '/' . $postMedia->playerHeight . ';';
				if ($postMedia->playerWidth < $postMedia->playerHeight) {
					$height = $postMedia->playerHeight;
					$width  = '';
				} else {
					$height = '';
				}
			}
		} else {
			if ($postMedia->hasPlayerHeight()) {
				$height = $postMedia->playerHeight . 'px';
			}
			if ($postMedia->hasPlayerWidth()) {
				$width = $postMedia->playerWidth . 'px';
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/iframe.tpl'), [
			'src'          => $postMedia->playerUrl,
			'height'       => $height,
			'width'        => $width,
			'iframe_style' => $iframe_style,
		]);
	}

	/**
	 * Build an iframe wrapper for embed HTML provided by the media.
	 *
	 * @param PostMediaEntity $postMedia Media entity containing embed HTML and dimensions
	 * @return string Rendered iframe embedding the media
	 */
	public function getEmbedIframe(PostMediaEntity $postMedia): string
	{
		if ($postMedia->embedHtml == '') {
			return '';
		}

		$iframe_style = '';
		$height       = '100%';
		$width        = '100%';

		if ($postMedia->isVideo()) {
			if ($postMedia->hasEmbedWidth() && $postMedia->hasEmbedHeight()) {
				$iframe_style .= 'aspect-ratio:' . $postMedia->embedWidth . '/' . $postMedia->embedHeight . ';';
				if ($postMedia->embedWidth < $postMedia->embedHeight) {
					$height = $postMedia->embedHeight;
					$width  = '';
				} else {
					$height = '';
				}
			}
		} else {
			if ($postMedia->hasEmbedHeight()) {
				$height = $postMedia->embedHeight . 'px';
			}
			if ($postMedia->hasEmbedWidth()) {
				$width = $postMedia->embedWidth . 'px';
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate($postMedia->embedHeight ? 'content/embed-iframe.tpl' : 'content/embed-iframe-resize.tpl'), [
			'id'           => 'iframe-' . hash('md5', $postMedia->embedHtml),
			'src'          => $postMedia->embedHtml,
			'height'       => $height,
			'width'        => $width,
			'iframe_style' => $iframe_style,
		]);
	}

	/**
	 * Render an audio attachment as HTML.
	 *
	 * @param PostMediaEntity $postMedia Media entity to render
	 * @return string HTML snippet for the audio player
	 */
	public function getAudioAttachment(PostMediaEntity $postMedia): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/audio.tpl'), [
			'$audio' => [
				'id'   => $postMedia->id,
				'src'  => (string)$postMedia->url,
				'name' => $postMedia->name ?: $postMedia->url,
				'mime' => (string)$postMedia->mimetype,
			],
		]);
	}

	/**
	 * Render a generic link attachment.
	 *
	 * @param PostMediaEntity $postMedia Media entity to render
	 * @return string HTML snippet for the link attachment
	 */
	public function getLinkAttachment(PostMediaEntity $postMedia): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/link.tpl'), [
			'$url'   => $postMedia->url,
			'$title' => $postMedia->name,
		]);
	}
}
