<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Post\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Content\Post\Entity\PostMedia as PostMediaEntity;
use Friendica\Model\Post;
use Friendica\Network\Entity\MimeType as MimeTypeEntity;
use Friendica\Network\Factory\MimeType as MimeTypeFactory;
use Friendica\Util\Images;
use Friendica\Util\Network as UtilNetwork;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use stdClass;

class PostMedia extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var MimeTypeFactory */
	private $mimeTypeFactory;

	public function __construct(MimeTypeFactory $mimeTypeFactory, LoggerInterface $logger)
	{
		parent::__construct($logger);

		$this->mimeTypeFactory = $mimeTypeFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): PostMediaEntity
	{
		return new PostMediaEntity(
			$row['uri-id'],
			UtilNetwork::createUriFromString($row['url']),
			$row['type'],
			$this->mimeTypeFactory->createFromContentType($row['mimetype']),
			$row['media-uri-id'],
			$row['width'],
			$row['height'],
			$row['size'],
			UtilNetwork::createUriFromString($row['preview']),
			$row['preview-width'],
			$row['preview-height'],
			$row['description'],
			$row['name'],
			UtilNetwork::createUriFromString($row['author-url']),
			$row['author-name'],
			UtilNetwork::createUriFromString($row['author-image']),
			UtilNetwork::createUriFromString($row['publisher-url']),
			$row['publisher-name'],
			UtilNetwork::createUriFromString($row['publisher-image']),
			$row['blurhash'],
			UtilNetwork::createUriFromString($row['player-url']),
			$row['player-width'],
			$row['player-height'],
			$row['id'],
			$row['attach-id'],
			$row['language'],
			$row['published'],
			$row['modified'],
			$row['embed-type'],
			$row['embed-html'],
			$row['embed-width'],
			$row['embed-height']
		);
	}

	public function createFromBlueskyImageEmbed(int $uriId, stdClass $image): PostMediaEntity
	{
		return new PostMediaEntity(
			$uriId,
			new Uri($image->fullsize),
			PostMediaEntity::TYPE_IMAGE,
			new MimeTypeEntity('unkn', 'unkn'),
			null,
			null,
			null,
			null,
			new Uri($image->thumb),
			null,
			null,
			$image->alt,
		);
	}


	public function createFromBlueskyExternalEmbed(int $uriId, stdClass $external): PostMediaEntity
	{
		return new PostMediaEntity(
			$uriId,
			new Uri($external->uri),
			PostMediaEntity::TYPE_HTML,
			new MimeTypeEntity('text', 'html'),
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$external->description,
			$external->title
		);
	}

	public function createFromAttachment(array $attachment, int $uriId = 0, int $id = 0)
	{
		$row = [
			'id'              => $id,
			'uri-id'          => $uriId,
			'url'             => $attachment['url'],
			'type'            => Post\Media::HTML,
			'mimetype'        => null,
			'media-uri-id'    => null,
			'width'           => null,
			'height'          => null,
			'size'            => null,
			'preview'         => $attachment['image'] ?? $attachment['preview'] ?? null,
			'preview-width'   => null,
			'preview-height'  => null,
			'description'     => $attachment['description'] ?? null,
			'name'            => $attachment['title']       ?? null,
			'author-url'      => $attachment['author_url']  ?? null,
			'author-name'     => $attachment['author_name'] ?? null,
			'author-image'    => null,
			'publisher-url'   => $attachment['provider_url']  ?? null,
			'publisher-name'  => $attachment['provider_name'] ?? null,
			'publisher-image' => null,
			'blurhash'        => null,
			'player-url'      => $attachment['player_url']    ?? null,
			'player-width'    => $attachment['player_width']  ?? null,
			'player-height'   => $attachment['player_height'] ?? null,
			'embed-type'      => $attachment['embed_type']    ?? null,
			'embed-html'      => $attachment['embed_html']    ?? null,
			'embed-width'     => $attachment['embed_width']   ?? null,
			'embed-height'    => $attachment['embed_height']  ?? null,
			'attach-id'       => null,
			'language'        => null,
			'published'       => null,
			'modified'        => null,
		];

		if (isset($row['preview'])) {
			$imagedata = Images::getInfoFromURLCached($row['preview']);
			if ($imagedata) {
				$row['preview-width']  = $imagedata[0];
				$row['preview-height'] = $imagedata[1];
				$row['blurhash']       = $imagedata['blurhash'] ?? null;
			}
		}

		return $this->createFromTableRow($row);
	}

	public function createFromParseUrl(array $data, int $uriId = 0, int $id = 0)
	{
		$row = [
			'id'              => $id,
			'uri-id'          => $uriId,
			'url'             => $data['url'],
			'type'            => Post\Media::getType($data['mimetype'] ?? ''),
			'mimetype'        => $data['mimetype'] ?? null,
			'media-uri-id'    => null,
			'width'           => null,
			'height'          => null,
			'size'            => $data['size']                  ?? null,
			'preview'         => $data['images'][0]['src']      ?? null,
			'preview-width'   => $data['images'][0]['width']    ?? null,
			'preview-height'  => $data['images'][0]['height']   ?? null,
			'description'     => $data['text']                  ?? null,
			'name'            => $data['title']                 ?? null,
			'author-url'      => $data['author_url']            ?? null,
			'author-name'     => $data['author_name']           ?? null,
			'author-image'    => $data['author_img']            ?? null,
			'publisher-url'   => $data['publisher_url']         ?? null,
			'publisher-name'  => $data['publisher_name']        ?? null,
			'publisher-image' => $data['publisher_img']         ?? null,
			'blurhash'        => $data['images'][0]['blurhash'] ?? null,
			'player-url'      => $data['player']['embed']       ?? null,
			'player-width'    => $data['player']['width']       ?? null,
			'player-height'   => $data['player']['height']      ?? null,
			'embed-type'      => $data['embed']['type']         ?? null,
			'embed-html'      => $data['embed']['html']         ?? null,
			'embed-width'     => $data['embed']['width']        ?? null,
			'embed-height'    => $data['embed']['height']       ?? null,
			'attach-id'       => null,
			'language'        => $data['language']  ?? null,
			'published'       => $data['published'] ?? null,
			'modified'        => $data['modified']  ?? null,
		];

		return $this->createFromTableRow($row);
	}
}
