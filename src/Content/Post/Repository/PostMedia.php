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

	public function __construct(Database $database, LoggerInterface $logger, PostMediaFactory $factory, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, BaseURL $baseURL, Item $item)
	{
		parent::__construct($database, $logger, $factory);

		$this->baseURL = $baseURL;
		$this->pConfig = $pConfig;
		$this->config  = $config;
		$this->item    = $item;
	}

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
	 * Returns a single PostMedia entity, selected by their id.
	 *
	 * @param int $postMediaId
	 * @return PostMediaEntity
	 * @throws NotFoundException
	 */
	public function selectById(int $postMediaId): PostMediaEntity
	{
		$fields = $this->_selectFirstRowAsArray(['id' => $postMediaId]);

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * Select PostMedia collection for the given uri-id and the given types
	 *
	 * @param integer $uriId
	 * @param array $types
	 * @return PostMediasCollection
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
	 * Select PostMedia entity for the given uri-id, the media url and the given types
	 *
	 * @param integer $uriId
	 * @param string $url
	 * @param array $types
	 * @return PostMediaEntity
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
	 * Split the attachment media in the three segments "visual", "link" and "additional"
	 *
	 * @param int    $uri_id URI id
	 * @param array  $links list of links that shouldn't be added
	 * @param bool   $has_media
	 * @return PostMediasCollection[] Three collections in "visual", "link" and "additional" keys
	 */
	public function splitAttachments(int $uri_id, array $links = [], bool $has_media = true): array
	{
		$attachments = [
			'visual'     => new PostMediasCollection(),
			'link'       => new PostMediasCollection(),
			'additional' => new PostMediasCollection(),
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
		foreach ($PostMedias as $PostMedia) {
			if ($PostMedia->type == PostMediaEntity::TYPE_HLS) {
				$is_hls = true;
			}
			if ($PostMedia->type == PostMediaEntity::TYPE_TORRENT) {
				$is_torrent = true;
			}
		}

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

			//$PostMedia->filetype = $filetype;
			//$PostMedia->subtype = $subtype;

			if ($PostMedia->type == PostMediaEntity::TYPE_HTML) {
				$attachments['link'][] = $PostMedia;
				continue;
			}

			if (in_array($PostMedia->type, [PostMediaEntity::TYPE_AUDIO, PostMediaEntity::TYPE_IMAGE, PostMediaEntity::TYPE_HLS])) {
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

	public function createFromUrl(string $url): PostMediaEntity
	{
		$data  = ParseUrl::getSiteinfoCached($url);
		$media = $this->factory->createFromParseUrl($data);
		return $this->fetchAdditionalData($media);
	}

	public function fetchAdditionalData(PostMediaEntity $postMedia): PostMediaEntity
	{
		$data = $this->getFields($postMedia, true);
		$data = Post\Media::fetchAdditionalData($data);
		return $this->factory->createFromTableRow($data);
	}

	/**
	 * Embed remote media, that had been added with the [embed] element
	 *
	 * @param string $html    The already rendered HTML output
	 * @param integer $uid    The user the output is rendered for
	 * @param integer $uri_id The uri-id of the item that is rendered
	 * @return string
	 */
	public function addEmbed(string $html, int $uid, int $uri_id): string
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

			if ($media->type === Post\Media::AUDIO) {
				$player = $this->getAudioAttachment($media);
			} elseif (in_array($media->type, [Post\Media::VIDEO, Post\Media::HLS])) {
				$player = $this->getVideoAttachment($media, $uid);
			} elseif ($allow_embed && !empty($media->playerUrl)) {
				$player = $this->getPlayerIframe($media);
			} elseif ($allow_embed && !empty($media->embedHtml)) {
				$player = '<span class="embedded-media">' . $this->getEmbedIframe($media) . '</span>';
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

	public function getVideoAttachment(PostMediaEntity $postMedia, int $uid): string
	{
		if ($postMedia->preview || ($postMedia->blurhash && $this->config->get('system', 'ffmpeg_installed'))) {
			$preview_url = $this->baseURL . $postMedia->getPreviewPath(Proxy::SIZE_MEDIUM);
		} else {
			$preview_url = '';
		}

		if (($postMedia->height ?? 0) > ($postMedia->width ?? 0)) {
			$height = min($this->config->get('system', 'max_height') ?: '100%', $postMedia->height);
			$width  = 'auto';
		} else {
			$height = 'auto';
			$width  = '100%';
		}

		if ($this->pConfig->get($uid, 'system', 'embed_media', false) && ($postMedia->playerUrl != '') && ($postMedia->playerHeight > 0)) {
			$media = $this->getPlayerIframe($postMedia);
		} elseif ($this->pConfig->get($uid, 'system', 'embed_media', false) && ($postMedia->embedHtml != '')) {
			$media = $this->getEmbedIframe($postMedia);
		} else {
			/// @todo Move the template to /content as well
			$media = Renderer::replaceMacros(Renderer::getMarkupTemplate($postMedia->type == Post\Media::HLS ? 'hls_top.tpl' : 'video_top.tpl'), [
				'$video' => [
					'id'          => $postMedia->id,
					'src'         => (string)$postMedia->url,
					'name'        => $postMedia->name ?: $postMedia->url,
					'preview'     => $preview_url,
					'mime'        => (string)$postMedia->mimetype,
					'height'      => $height,
					'width'       => $width,
					'description' => $postMedia->description,
				],
			]);
		}
		return $media;
	}

	public function getPlayerIframe(PostMediaEntity $postMedia): string
	{
		if ($postMedia->playerUrl == '') {
			return '';
		}

		$div_style    = '';
		$iframe_style = '';
		$height       = min($this->config->get('system', 'max_height'), $postMedia->playerHeight);

		if ($postMedia->playerWidth != 0 && $postMedia->playerHeight != 0 && $postMedia->playerWidth > $this->config->get('system', 'max_width') && $postMedia->playerWidth > $postMedia->playerHeight) {
			$factor = round($postMedia->playerHeight / $postMedia->playerWidth, 2) * 100;
			$height = '100%';
			$iframe_style .= 'position:absolute;left:0px;top:0px;';
			$div_style .= 'position:relative;padding-bottom:' . $factor . '%;';
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/iframe.tpl'), [
			'src'          => $postMedia->playerUrl,
			'height'       => $height,
			'width'        => $postMedia->embedWidth && $postMedia->embedWidth <= $this->config->get('system', 'max_width') ? $postMedia->embedWidth : '100%',
			'div_style'    => $div_style,
			'iframe_style' => $iframe_style,
		]);
	}

	public function getEmbedIframe(PostMediaEntity $postMedia): string
	{
		if ($postMedia->embedHtml == '') {
			return '';
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate($postMedia->embedHeight ? 'content/embed-iframe.tpl' : 'content/embed-iframe-resize.tpl'), [
			'id'     => 'iframe-' . hash('md5', $postMedia->embedHtml),
			'src'    => $postMedia->embedHtml,
			'height' => $postMedia->embedHeight + 20,
			'width'  => $postMedia->embedWidth && $postMedia->embedWidth <= $this->config->get('system', 'max_width') ? $postMedia->embedWidth : '100%',
		]);
	}

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

	public function getLinkAttachment(PostMediaEntity $postMedia): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/link.tpl'), [
			'$url'   => $postMedia->url,
			'$title' => $postMedia->name,
		]);
	}
}
